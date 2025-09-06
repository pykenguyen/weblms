<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// ---------------------------------------------------------------------
// GET: /api/quizzes.php?course_id=1           -> danh sách quiz theo khóa
// GET: /api/quizzes.php?id=5&with_items=1     -> 1 quiz + câu hỏi + đáp án (chỉ teacher/admin)
// POST: tạo quiz + câu hỏi + đáp án
// DELETE: /api/quizzes.php?id=5               -> xóa quiz (và dữ liệu liên quan)
// ---------------------------------------------------------------------

$pdo = db();
$reqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($reqMethod === 'GET') {
    // by id (optional include items)
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $st = $pdo->prepare('SELECT * FROM quizzes WHERE id=?');
        $st->execute([$id]);
        $quiz = $st->fetch(PDO::FETCH_ASSOC);
        if (!$quiz) json(['message'=>'Quiz not found'], 404);

        // tổng số câu
        $qc = $pdo->prepare('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=?');
        $qc->execute([$id]);
        $quiz['question_count'] = (int)$qc->fetchColumn();

        $with = (int)($_GET['with_items'] ?? 0);
        if ($with) {
            // chỉ teacher/admin mới xem được đáp án đúng
            $u = current_user();
            $role = $u['role'] ?? 'student';
            $showAnswer = in_array($role, ['teacher','admin'], true);

            $qst = $pdo->prepare('SELECT id, question_text, points, type FROM quiz_questions WHERE quiz_id=? ORDER BY id');
            $qst->execute([$id]);
            $questions = $qst->fetchAll(PDO::FETCH_ASSOC);

            $optSt = $pdo->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id=? ORDER BY id');
            foreach ($questions as &$q) {
                $optSt->execute([$q['id']]);
                $opts = $optSt->fetchAll(PDO::FETCH_ASSOC);
                if (!$showAnswer) {
                    foreach ($opts as &$o) unset($o['is_correct']);
                }
                $q['options'] = $opts;
            }
            $quiz['questions'] = $questions;
        }

        json(['item'=>$quiz]);
    }

    // by course
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    if ($course_id <= 0) json(['items'=>[]]);

    $stmt = $pdo->prepare("
        SELECT q.*,
          (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) AS question_count
        FROM quizzes q
        WHERE q.course_id=?
        ORDER BY q.id DESC
    ");
    $stmt->execute([$course_id]);
    json(['items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ---------------------------------------------------------------------
// CREATE
// ---------------------------------------------------------------------
if ($reqMethod === 'POST') {
    // chỉ teacher/admin
    require_role(['teacher','admin']);

    // nhận dữ liệu json hoặc form
    $input = body(); // helpers.php

    // chấp nhận dán cả object {questions:[...]} hoặc chỉ là []
    $questionsRaw = $input['questions'] ?? ($input['questions_json'] ?? null);
    if (is_string($questionsRaw)) {
        $tmp = json_decode($questionsRaw, true);
        $questions = is_array($tmp) ? $tmp : ($tmp['questions'] ?? []);
    } else {
        // nếu người dùng dán nguyên object vào textarea, vẫn lấy ra được
        if (is_array($questionsRaw) && isset($questionsRaw['questions']) && is_array($questionsRaw['questions'])) {
            $questions = $questionsRaw['questions'];
        } else {
            $questions = is_array($questionsRaw) ? $questionsRaw : [];
        }
    }

    $course_id = (int)($input['course_id'] ?? 0);
    $title     = trim((string)($input['title'] ?? ''));
    $desc      = trim((string)($input['description'] ?? ''));
    $limit     = (int)($input['time_limit_minutes'] ?? 0);

    if ($course_id <= 0) json(['message'=>'Missing course_id'], 422);
    if ($title === '')  json(['message'=>'Missing title'], 422);

    // Nếu total_points không truyền, sẽ auto tính theo questions
    $total = (int)($input['total_points'] ?? 0);

    // Chuẩn hóa mảng câu hỏi
    $normQ = [];
    foreach ($questions as $q) {
        if (!is_array($q)) continue;
        $qt = trim((string)($q['question_text'] ?? ''));
        if ($qt === '') continue;

        $pts = (int)($q['points'] ?? 1);
        $tp  = in_array(($q['type'] ?? 'single'), ['single','multiple'], true) ? $q['type'] : 'single';

        $optsIn = $q['options'] ?? [];
        $opts = [];
        foreach ((array)$optsIn as $o) {
            if (!is_array($o)) continue;
            $ot = trim((string)($o['option_text'] ?? ''));
            if ($ot === '') continue;
            $ok = (bool)($o['is_correct'] ?? false);
            $opts[] = ['option_text'=>$ot, 'is_correct'=>$ok];
        }

        if (count($opts) >= 2) {
            $normQ[] = [
                'question_text'=>$qt,
                'points'=>$pts,
                'type'=>$tp,
                'options'=>$opts
            ];
            if ($total === 0) $total += $pts;
        }
    }

    // tạo quiz
    $st = $pdo->prepare('INSERT INTO quizzes (course_id, title, description, time_limit_minutes, total_points) VALUES (?,?,?,?,?)');
    $st->execute([$course_id, $title, $desc, $limit, $total]);
    $quiz_id = (int)$pdo->lastInsertId();

    // chèn câu hỏi + đáp án
    $stQ = $pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_text, points, type) VALUES (?,?,?,?)');
    $stO = $pdo->prepare('INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?,?,?)');

    foreach ($normQ as $q) {
        $stQ->execute([$quiz_id, $q['question_text'], $q['points'], $q['type']]);
        $qid = (int)$pdo->lastInsertId();
        foreach ($q['options'] as $o) {
            $stO->execute([$qid, $o['option_text'], $o['is_correct'] ? 1 : 0]);
        }
    }

    json(['message'=>'created', 'id'=>$quiz_id]);
}

// ---------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------
if ($reqMethod === 'DELETE') {
    require_role(['teacher','admin']);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) json(['message'=>'Missing id'], 422);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('
          DELETE qo FROM quiz_options qo
          JOIN quiz_questions qq ON qo.question_id = qq.id
          WHERE qq.quiz_id = ?
        ')->execute([$id]);

        $pdo->prepare('DELETE FROM quiz_questions WHERE quiz_id=?')->execute([$id]);

        // nếu có bảng quiz_answers thì xóa nốt
        if ($pdo->query("SHOW TABLES LIKE 'quiz_answers'")->rowCount()) {
            $pdo->prepare('DELETE FROM quiz_answers WHERE quiz_id=?')->execute([$id]);
        }

        $pdo->prepare('DELETE FROM quizzes WHERE id=?')->execute([$id]);

        $pdo->commit();
        json(['message'=>'deleted']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json(['message'=>'Delete failed', 'error'=>$e->getMessage()], 500);
    }
}

json(['message'=>'Method not allowed'], 405);
