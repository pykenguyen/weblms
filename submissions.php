<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$u = require_role('student');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json(['message'=>'Method Not Allowed'], 405);

$assignment_id = (int)($_POST['assignment_id'] ?? 0);
if ($assignment_id <= 0) json(['message'=>'Missing assignment_id'], 422);

$path = save_upload('file', 'submissions', ['zip','pdf','doc','docx','docm','ppt','pptx','txt']);

$pdo = db();
$chk = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id=? AND student_id=?');
$chk->execute([$assignment_id, (int)$u['id']]);
$existed = $chk->fetchColumn();

if ($existed) {
  $up = $pdo->prepare('UPDATE submissions SET file_path=?, submitted_at=NOW() WHERE id=?');
  $up->execute([$path, (int)$existed]);
  json(['message'=>'Đã cập nhật bài nộp', 'submission_id'=>(int)$existed, 'file_path'=>$path]);
} else {
  $ins = $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_path, submitted_at) VALUES (?,?,?,NOW())');
  $ins->execute([$assignment_id, (int)$u['id'], $path]);
  json(['message'=>'Đã nộp bài', 'submission_id'=>(int)$pdo->lastInsertId(), 'file_path'=>$path]);
}
