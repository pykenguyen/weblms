<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$u = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if ($u['role'] === 'student') {
    $q = $pdo->prepare('SELECT * FROM submissions WHERE student_id=? ORDER BY submitted_at DESC');
    $q->execute([(int)$u['id']]);
    json(['items'=>$q->fetchAll()]);
  }

  $course_id = (int)($_GET['course_id'] ?? 0);
  if ($course_id <= 0) json(['message'=>'Missing course_id'], 422);

  if ($u['role'] === 'teacher' && !teacher_owns_course((int)$u['id'], $course_id)) {
    json(['message'=>'Forbidden'], 403);
  }

  $q = $pdo->prepare("
    SELECT s.*, a.title AS assignment_title, u.name AS student_name, u.email AS student_email
    FROM submissions s
    JOIN assignments a ON a.id=s.assignment_id AND a.course_id=?
    LEFT JOIN users u ON u.id=s.student_id
    ORDER BY s.submitted_at DESC
  ");
  $q->execute([$course_id]);
  json(['items'=>$q->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $d = body();
  $submission_id = (int)($d['submission_id'] ?? 0);
  if ($submission_id <= 0) json(['message'=>'Missing submission_id'], 422);

  $info = $pdo->prepare("
    SELECT s.student_id, a.course_id, a.title
    FROM submissions s
    JOIN assignments a ON a.id=s.assignment_id
    WHERE s.id=?");
  $info->execute([$submission_id]);
  $meta = $info->fetch();
  if (!$meta) json(['message'=>'Submission not found'], 404);

  if ($u['role'] === 'teacher' && !teacher_owns_course((int)$u['id'], (int)$meta['course_id'])) {
    json(['message'=>'Forbidden'], 403);
  }
  if (!in_array($u['role'], ['teacher','admin'], true)) json(['message'=>'Forbidden'], 403);

  $grade = $d['grade'] ?? null;
  $feedback = $d['feedback'] ?? null;

  $upd = $pdo->prepare('UPDATE submissions SET grade=?, feedback=? WHERE id=?');
  $upd->execute([is_null($grade)? null : (float)$grade, $feedback, $submission_id]);

  $nt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
  $nt->execute([(int)$meta['student_id'], 'Bài tập đã chấm', 'Bạn đã được chấm bài: '.$meta['title'].(isset($grade)? ' ('.$grade.')':'')]);

  json(['message'=>'Đã lưu']);
}

json(['message'=>'Method Not Allowed'], 405);
