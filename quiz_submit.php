<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Cho phép SV/GV/Admin nộp để test
$user = require_role(['student', 'teacher', 'admin']);

// --- Hàm gửi thông báo cho giáo viên ---
function notify_teacher_quiz_completion(PDO $pdo, int $attemptId, int $studentId, float $score, float $maxScore): void {
    // Lấy thông tin quiz và tên học sinh
    $st = $pdo->prepare('
        SELECT q.id, q.title, q.teacher_id, u.name AS student_name
        FROM quizzes q
        JOIN users u ON u.id = ?
        WHERE q.id = (SELECT quiz_id FROM quiz_attempts WHERE id = ?)
    ');
    $st->execute([$studentId, $attemptId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['teacher_id'])) return;

    $teacherId   = (int)$row['teacher_id'];
    $quizTitle   = $row['title'];
    $studentName = $row['student_name'];

    $title   = "Học sinh hoàn thành bài kiểm tra";
    $message = "Học sinh <strong>{$studentName}</strong> đã hoàn thành bài kiểm tra \"{$quizTitle}\" với điểm {$score}/{$maxScore}. 
                <a href=\"quiz_results.php?attempt_id={$attemptId}\">Xem chi tiết</a>";

    $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)');
    $ins->execute([$teacherId, $title, $message]);
}

// --- Lấy dữ liệu từ client ---
$input   = body();                       
$quizId  = (int)($input['quiz_id'] ?? 0);
$answers = $input['answers'] ?? [];

if ($quizId <= 0) json(['message' => 'Missing quiz_id'], 422);
if (!is_array($answers) || !$answers) json(['message' => 'Missing answers'], 422);

$pdo = db();

try {
    $pdo->beginTransaction();

    // Lấy quiz
    $st = $pdo->prepare('SELECT id, total_points FROM quizzes WHERE id=?');
    $st->execute([$quizId]);
    $quiz = $st->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) json(['message' => 'Quiz not found'], 404);

    // Lấy câu hỏi + options
    $qs = $pdo->prepare('SELECT id, type, points FROM quiz_questions WHERE quiz_id=?');
    $qs->execute([$quizId]);
    $questions = $qs->fetchAll(PDO::FETCH_ASSOC);
    if (!$questions) json(['message' => 'Quiz has no questions'], 422);

    // Chuẩn bị map để chấm
    $qMap = [];                  
    $optMap = [];                
    $maxScore = 0.0;

    $optStmt = $pdo->prepare('SELECT id, is_correct FROM quiz_options WHERE question_id=?');
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $pts = (float)($q['points'] ?? 1);
        $qMap[$qid] = ['type' => $q['type'], 'points' => $pts];
        $maxScore  += $pts;

        $optStmt->execute([$qid]);
        $opts = $optStmt->fetchAll(PDO::FETCH_ASSOC);
        $optMap[$qid] = [];
        foreach ($opts as $o) {
            $optMap[$qid][(int)$o['id']] = (int)$o['is_correct'];
        }
    }

    // Gom lựa chọn từ client (chỉ nhận option hợp lệ)
    $picked = []; 
    foreach ($answers as $a) {
        $qid = (int)($a['question_id'] ?? 0);
        $oid = (int)($a['option_id'] ?? 0);
        if (!$qid || !$oid) continue;
        if (!isset($qMap[$qid])) continue;
        if (!isset($optMap[$qid][$oid])) continue;

        $picked[$qid] ??= [];
        if (!in_array($oid, $picked[$qid], true)) $picked[$qid][] = $oid;
    }

    // Chấm điểm
    $score = 0.0;
    foreach ($qMap as $qid => $meta) {
        $type    = $meta['type'];
        $points  = (float)$meta['points'];
        $userSel = $picked[$qid] ?? [];

        $correct = [];
        foreach ($optMap[$qid] as $oid => $isC) if ($isC) $correct[] = $oid;

        if ($type === 'multiple') {
            sort($userSel); sort($correct);
            if ($correct && $userSel === $correct) $score += $points;
        } else { 
            if (count($userSel) === 1 && in_array($userSel[0], $correct, true)) $score += $points;
        }
    }

    // Lưu attempt
    $insAttempt = $pdo->prepare(
        'INSERT INTO quiz_attempts (quiz_id, student_id, started_at, submitted_at, score, max_score)
         VALUES (?, ?, NOW(), NOW(), ?, ?)'
    );
    $insAttempt->execute([$quizId, (int)$user['id'], $score, $maxScore]);
    $attemptId = (int)$pdo->lastInsertId();

    // Lưu từng lựa chọn
    if (!empty($picked)) {
        $insAns = $pdo->prepare(
            'INSERT INTO quiz_answers (attempt_id, question_id, option_id) VALUES (?, ?, ?)'
        );
        foreach ($picked as $qid => $oids) {
            foreach ($oids as $oid) $insAns->execute([$attemptId, $qid, $oid]);
        }
    }

    $pdo->commit();

    // --- Gửi thông báo giáo viên ---
    notify_teacher_quiz_completion($pdo, $attemptId, (int)$user['id'], $score, $maxScore);

    // Trả về JSON cho FE
    json(['attempt_id' => $attemptId, 'score' => $score, 'max_score' => $maxScore], 201);

} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    json(['message' => 'Save failed', 'error' => $e->getMessage()], 500);
}
