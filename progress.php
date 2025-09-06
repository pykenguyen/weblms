<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['message' => 'Method Not Allowed'], 405);
}

// Yêu cầu người dùng phải đăng nhập
$user = require_login();
$user_id = (int)$user['id'];

$data = body();
$lesson_id = (int)($data['lesson_id'] ?? 0);
$is_completed = isset($data['is_completed']) ? (int)(bool)$data['is_completed'] : 1;

if ($lesson_id <= 0) {
    json(['message' => 'Thiếu lesson_id.'], 422);
}

$pdo = db();

// **CẬP NHẬT 1: Thêm bước kiểm tra bảo mật**
// Lấy course_id của bài học để kiểm tra quyền truy cập
$stmt = $pdo->prepare("SELECT course_id FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$course_id = $stmt->fetchColumn();

if (!$course_id) {
    json(['message' => 'Không tìm thấy bài học.'], 404);
}

// Kiểm tra xem người dùng có được ghi danh vào khóa học chứa bài học này không
if (!is_enrolled($user_id, (int)$course_id)) {
    json(['message' => 'Bạn không có quyền truy cập bài học này.'], 403);
}

// **CẬP NHẬT 2: Gộp INSERT và UPDATE thành một câu lệnh cho hiệu quả**
// Dùng INSERT ... ON DUPLICATE KEY UPDATE để vừa tạo mới hoặc cập nhật
// Điều này yêu cầu bạn phải có một UNIQUE KEY trên (user_id, lesson_id) trong bảng progress_tracking
$sql = "
    INSERT INTO progress_tracking (user_id, lesson_id, is_completed, last_viewed) 
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
    is_completed = VALUES(is_completed), last_viewed = NOW()
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $lesson_id, $is_completed]);

json(['message' => 'Đã cập nhật tiến độ.']);
?>