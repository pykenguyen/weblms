<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = require_role(['teacher','admin']);
  $d = body();
  $course_id = (int)($d['course_id'] ?? 0);
  $title     = trim($d['title'] ?? '');
  $desc      = trim($d['description'] ?? '');
  $due       = trim($d['due_date'] ?? '');
  if ($course_id<=0 || $title==='') json(['message'=>'Thiếu dữ liệu'], 422);

  $pdo = db();
  $ins = $pdo->prepare('INSERT INTO assignments (course_id, title, description, due_date, created_at) VALUES (?,?,?,?,NOW())');
  $ins->execute([$course_id, $title, $desc ?: null, $due ?: null]);

  $students = $pdo->prepare('SELECT user_id FROM course_user WHERE course_id=?');
  $students->execute([$course_id]);
  $notify = $pdo->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');

  $courseName = $pdo->prepare('SELECT title FROM courses WHERE id=?');
  $courseName->execute([$course_id]);
  $cTitle = (string)$courseName->fetchColumn();

  while ($row = $students->fetch()) {
    $notify->execute([(int)$row['user_id'], 'Bài tập mới', "Khoá \"$cTitle\": $title"]);
  }

  json(['message'=>'Đã tạo bài tập', 'assignment_id'=>(int)$pdo->lastInsertId()]);
}

json(['message'=>'Method Not Allowed'], 405);
