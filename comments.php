<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
$u = require_login();
$pdo = db();

$lesson_id = (int)($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);
if ($lesson_id <= 0) json(['message'=>'Missing lesson_id'], 422);

$st = $pdo->prepare('SELECT course_id, title FROM lessons WHERE id=?');
$st->execute([$lesson_id]);
$lesson = $st->fetch();
if (!$lesson) json(['message'=>'Lesson not found'], 404);

$course_id = (int)$lesson['course_id'];
$allowed = false;
if ($u['role'] === 'admin') $allowed = true;
elseif ($u['role'] === 'teacher') $allowed = teacher_owns_course((int)$u['id'], $course_id);
else $allowed = is_enrolled((int)$u['id'], $course_id);
if (!$allowed) json(['message'=>'Forbidden'], 403);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $q = $pdo->prepare("
    SELECT c.id, c.content, c.created_at, u.name AS user_name, u.email AS user_email, u.id AS user_id
    FROM comments c
    LEFT JOIN users u ON u.id=c.user_id
    WHERE c.lesson_id=?
    ORDER BY c.id ASC
  ");
  $q->execute([$lesson_id]);
  json(['lesson_title'=>$lesson['title'], 'items'=>$q->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $content = trim($_POST['content'] ?? '');
  if ($content === '') json(['message'=>'Nội dung trống'], 422);

  $ins = $pdo->prepare('INSERT INTO comments (user_id, lesson_id, content, created_at) VALUES (?, ?, ?, NOW())');
  $ins->execute([(int)$u['id'], $lesson_id, $content]);

  $t = $pdo->prepare('SELECT teacher_id FROM courses WHERE id=?');
  $t->execute([$course_id]);
  $teacherId = (int)$t->fetchColumn();
  if ($teacherId && $teacherId !== (int)$u['id']) {
    $nt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
    $nt->execute([$teacherId, 'Bình luận mới', ($u['name'] ?? 'Học viên').' bình luận ở bài: '.$lesson['title']]);
  }

  json(['message'=>'Đã gửi bình luận']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) json(['message'=>'Missing id'], 422);

  $q = $pdo->prepare('SELECT user_id FROM comments WHERE id=? AND lesson_id=?');
  $q->execute([$id, $lesson_id]);
  $owner = $q->fetchColumn();
  if (!$owner) json(['message'=>'Comment not found'], 404);

  $can = $u['role']==='admin' || (int)$owner === (int)$u['id'] || ($u['role']==='teacher' && teacher_owns_course((int)$u['id'], $course_id));
  if (!$can) json(['message'=>'Forbidden'], 403);

  $pdo->prepare('DELETE FROM comments WHERE id=?')->execute([$id]);
  json(['message'=>'Đã xóa']);
}

json(['message'=>'Method Not Allowed'], 405);
