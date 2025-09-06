<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Trả về 1 quiz + questions + options
// GET /api/quiz_detail.php?id=5
// - student: chỉ thấy options, KHÔNG thấy is_correct
// - teacher/admin: thấy cả is_correct

$u = require_login();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) json(['message'=>'Missing id'], 422);

$st = $pdo->prepare('SELECT * FROM quizzes WHERE id=?');
$st->execute([$id]);
$quiz = $st->fetch(PDO::FETCH_ASSOC);
if (!$quiz) json(['message'=>'Quiz not found'], 404);

// Quyền xem: 
// - teacher/admin: ok
// - student: phải enroll khóa học
$role = $u['role'] ?? 'student';
if ($role === 'student') {
    if (!is_enrolled((int)$u['id'], (int)$quiz['course_id'])) {
        json(['message'=>'Forbidden'], 403);
    }
}

$questions = [];
$stQ = $pdo->prepare('SELECT id, question_text, points, type FROM quiz_questions WHERE quiz_id=? ORDER BY id');
$stQ->execute([$id]);
$questions = $stQ->fetchAll(PDO::FETCH_ASSOC);

$showAnswer = in_array($role, ['teacher','admin'], true);

$stO = $pdo->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id=? ORDER BY id');
foreach ($questions as &$q) {
    $stO->execute([$q['id']]);
    $opts = $stO->fetchAll(PDO::FETCH_ASSOC);
    if (!$showAnswer) {
        foreach ($opts as &$o) unset($o['is_correct']);
    }
    $q['options'] = $opts;
}

json(['quiz'=>$quiz, 'questions'=>$questions]);
