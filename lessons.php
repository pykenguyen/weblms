<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$user = require_role(['teacher', 'admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json(['message' => 'Method Not Allowed'], 405);
}

$course_id   = (int)($_POST['course_id'] ?? 0);
$title       = trim((string)($_POST['title'] ?? ''));
$contentType = trim((string)($_POST['content_type'] ?? ''));

if ($course_id <= 0 || $title === '' || $contentType === '') {
  json(['message' => 'Thiếu dữ liệu'], 422);
}

$allowedTypes = ['pdf', 'doc', 'docx', 'youtube', 'text'];
if (!in_array($contentType, $allowedTypes, true)) {
  json(['message' => 'content_type không hợp lệ'], 422);
}

/** Nếu là teacher thì phải sở hữu khoá */
if ($user['role'] === 'teacher' && !teacher_owns_course((int)$user['id'], $course_id)) {
  json(['message' => 'Bạn không có quyền tạo bài học trong khoá này'], 403);
}

$filePath   = null; // lưu đường dẫn file cho pdf/doc/docx
$contentUrl = null; // lưu URL cho youtube/text

if (in_array($contentType, ['pdf', 'doc', 'docx'], true)) {
  // Ràng buộc phần mở rộng theo loại
  $exts = $contentType === 'pdf' ? ['pdf'] : ['doc', 'docx'];
  // Trả về dạng "uploads/lessons/xxx.ext"
  $filePath = save_upload('file', 'lessons', $exts, 50);
} else {
  $contentUrl = trim((string)($_POST['content_url'] ?? ''));
  if ($contentUrl === '') {
    json(['message' => 'Thiếu content_url'], 422);
  }
}

$pdo = db();

// LƯU đúng cột: file => file_path, link => content_url
$ins = $pdo->prepare('
  INSERT INTO lessons (course_id, title, content_type, content_url, file_path, created_at)
  VALUES (?, ?, ?, ?, ?, NOW())
');
$ins->execute([$course_id, $title, $contentType, $contentUrl, $filePath]);
$lessonId = (int)$pdo->lastInsertId();

/** Gửi thông báo cho học viên đã enroll khoá */
$stCourse = $pdo->prepare('SELECT title FROM courses WHERE id=?');
$stCourse->execute([$course_id]);
$cTitle = (string)$stCourse->fetchColumn();

$students = $pdo->prepare('SELECT user_id FROM course_user WHERE course_id=?');
$students->execute([$course_id]);

$notify = $pdo->prepare('
  INSERT INTO notifications (user_id, title, message, is_read, created_at)
  VALUES (?, ?, ?, 0, NOW())
');

while ($row = $students->fetch(PDO::FETCH_ASSOC)) {
  $notify->execute([
    (int)$row['user_id'],
    'Bài học mới',
    "Khoá \"$cTitle\": $title"
  ]);
}

json([
  'message'     => 'Đã tạo bài học',
  'lesson_id'   => $lessonId,
  'file_path'   => $filePath,
  'content_url' => $contentUrl
]);
