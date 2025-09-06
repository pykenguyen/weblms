<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$pdo = db();

$course_id = (int)($_POST['course_id'] ?? 0);
$title = $_POST['title'] ?? '';
$start_time = $_POST['start_time'] ?? '';

if (!$course_id || !$title || !$start_time) {
    exit('Thiếu dữ liệu');
}

$start_time = date('Y-m-d H:i:s', strtotime($start_time));

// Lưu lịch học
$stmt = $pdo->prepare("INSERT INTO schedules (course_id, title, start_time) VALUES (?, ?, ?)");
$stmt->execute([$course_id, $title, $start_time]);
$schedule_id = $pdo->lastInsertId();

// Lấy danh sách học sinh
$stmt_users = $pdo->prepare("SELECT user_id FROM course_user WHERE course_id=?");
$stmt_users->execute([$course_id]);
$user_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

// Tạo thông báo
notify_users(
    $user_ids,
    "Lịch học mới: $title",
    "Khóa học: ".get_course_title($course_id).". Thời gian: $start_time",
    "schedule-detail.php?id=$schedule_id",
    'schedule'
);

echo "OK";
