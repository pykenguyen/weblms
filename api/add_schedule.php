<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

session_start();
require __DIR__.'/config.php';
require __DIR__.'/helpers.php';

// Bắt buộc là giáo viên
$teacher = require_role(['teacher']);
$teacherId = $teacher['id'];

// Lấy dữ liệu JSON từ fetch
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$course_id   = isset($data['course_id']) ? (int)$data['course_id'] : 0;
$day_of_week = isset($data['day_of_week']) ? (int)$data['day_of_week'] : 0;
$start_time  = $data['start_time'] ?? '';
$end_time    = $data['end_time'] ?? '';
$location    = trim($data['location'] ?? '');

if (!$course_id || !$day_of_week || !$start_time || !$end_time) {
    echo json_encode(['message'=>'Thiếu dữ liệu bắt buộc']);
    exit;
}

// Kiểm tra giáo viên sở hữu khóa học
if (!teacher_owns_course($teacherId, $course_id)) {
    echo json_encode(['message'=>'Bạn không sở hữu khóa học này']);
    exit;
}

// Kiểm tra thời gian hợp lệ
if (strtotime($start_time) >= strtotime($end_time)) {
    echo json_encode(['message'=>'Giờ bắt đầu phải nhỏ hơn giờ kết thúc']);
    exit;
}

try {
    $pdo = db();

    // Thêm lịch giảng dạy
    $stmt = $pdo->prepare("
        INSERT INTO schedules (teacher_id, course_id, day_of_week, start_time, end_time, location)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$teacherId, $course_id, $day_of_week, $start_time, $end_time, $location]);
    $scheduleId = (int)$pdo->lastInsertId();

    // Lấy danh sách học sinh của khóa học
    $stmt_users = $pdo->prepare("SELECT user_id FROM course_user WHERE course_id=?");
    $stmt_users->execute([$course_id]);
    $user_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

    // Tạo thông báo
    if ($user_ids) {
        notify_users(
            $user_ids,
            "Lịch học mới",
            "Môn " . get_course_title($course_id) . " vào " . date('H:i', strtotime($start_time)) . " tại $location",
            "schedule-detail.php?id=$scheduleId",
            'schedule'
        );
    }

    echo json_encode(['message'=>'Đã thêm vào lịch giảng dạy']);
} catch (PDOException $e) {
    echo json_encode(['message'=>'Lỗi DB: '.$e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['message'=>'Lỗi hệ thống: '.$e->getMessage()]);
}
