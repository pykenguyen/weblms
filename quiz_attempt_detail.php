<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$user = require_role(['student','teacher','admin']);

$attempt_id = (int)($_GET['attempt_id'] ?? $_GET['id'] ?? 0);
if ($attempt_id <= 0) json(['message'=>'Missing attempt_id'], 422);

$pdo = db();

// Lấy attempt + quiz title
$st = $pdo->prepare("
  SELECT a.id, a.quiz_id, a.student_id, a.score, a.max_score, a.started_at, a.submitted_at,
         q.title
  FROM quiz_attempts a
  JOIN quizzes q ON q.id = a.quiz_id
  WHERE a.id = ?
");
$st->execute([$attempt_id]);
$at = $st->fetch(PDO::FETCH_ASSOC);
if (!$at) json(['message'=>'Attempt not found'], 404);

// Nếu student thì chỉ xem được attempt của chính mình
if (($user['role'] ?? '') === 'student' && (int)$at['student_id'] !== (int)$user['id']) {
  json(['message'=>'Forbidden'], 403);
}

// (Tuỳ chọn) Lấy các đáp án đã chọn
$ans = [];
$st2 = $pdo->prepare("
  SELECT qa.question_id, qa.option_id,
         qo.option_text, qo.is_correct,
         qq.question_text
  FROM quiz_answers qa
  JOIN quiz_options qo ON qo.id = qa.option_id
  JOIN quiz_questions qq ON qq.id = qa.question_id
  WHERE qa.attempt_id = ?
  ORDER BY qa.question_id
");
$st2->execute([$attempt_id]);
$ans = $st2->fetchAll(PDO::FETCH_ASSOC);

json([
  'attempt' => [
    'id'           => (int)$at['id'],
    'quiz_id'      => (int)$at['quiz_id'],
    'student_id'   => (int)$at['student_id'],
    'score'        => (float)$at['score'],
    'max_score'    => (float)$at['max_score'],
    'started_at'   => $at['started_at'],
    'submitted_at' => $at['submitted_at'],
  ],
  'quiz' => [
    'id'    => (int)$at['quiz_id'],
    'title' => $at['title'],
  ],
  'answers' => $ans
], 200);
