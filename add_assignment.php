<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$pdo = db();

$title = $_POST['title'] ?? '';
$course_id = (int)($_POST['course_id'] ?? 0);
$due_date = $_POST['due_date'] ?? '';

if (!$title || !$course_id || !$due_date) {
    exit('Thiếu dữ liệu');
}

$due_date = date('Y-m-d H:i:s', strtotime($due_date));

// Lưu bài tập vào DB
$stmt = $pdo->prepare("INSERT INTO assignments (title, course_id, due_date) VALUES (?, ?, ?)");
$stmt->execute([$title, $course_id, $due_date]);
$assignment_id = $pdo->lastInsertId();

// Lấy danh sách học sinh của khóa học
$stmt_users = $pdo->prepare("SELECT user_id FROM course_user WHERE course_id=?");
$stmt_users->execute([$course_id]);
$user_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

// Tạo thông báo
notify_users(
    $user_ids,
    "Bài tập mới: $title",
    "Khóa học: ".get_course_title($course_id).". Hạn nộp: $due_date",
    "assignment-detail.php?id=$assignment_id",
    'course'
);

echo "OK";
