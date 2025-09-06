<?php
declare(strict_types=1);

// SỬA LỖI: Sửa lại đường dẫn cho đúng với cấu trúc dự án
require __DIR__.'/config.php';
require __DIR__.'/helpers.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['message' => 'Phương thức không hợp lệ'], 405);
}

// Yêu cầu người dùng phải đăng nhập và có vai trò là sinh viên
$u = require_role('student');
$user_id = (int)$u['id'];

// Lấy dữ liệu (dùng hàm body() từ helpers.php)
$data = body();
$course_id = (int)($data['course_id'] ?? 0);

if ($course_id <= 0) {
    json(['message' => 'Thiếu ID khóa học'], 422);
}

$pdo = db();

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();

    // 1. Xóa tiến độ học tập (bảng progress_tracking)
    $stmt_progress = $pdo->prepare("
        DELETE FROM progress_tracking 
        WHERE user_id = ? 
        AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)
    ");
    $stmt_progress->execute([$user_id, $course_id]);

    // 2. Xóa các bài đã nộp (bảng submissions)
    $stmt_submissions = $pdo->prepare("
        DELETE FROM submissions 
        WHERE student_id = ? 
        AND assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)
    ");
    $stmt_submissions->execute([$user_id, $course_id]);

    // 3. Xóa ghi danh chính (bảng course_user)
    $stmt_enrollment = $pdo->prepare("DELETE FROM course_user WHERE user_id = ? AND course_id = ?");
    $stmt_enrollment->execute([$user_id, $course_id]);

    // Hoàn tất transaction
    $pdo->commit();

    if ($stmt_enrollment->rowCount() > 0) {
        json(['message' => 'Hủy đăng ký và xóa toàn bộ dữ liệu liên quan thành công.']);
    } else {
        json(['message' => 'Không tìm thấy thông tin đăng ký để hủy.'], 404);
    }

} catch (PDOException $e) {
    // Nếu có lỗi, hoàn tác các thay đổi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Trả về lỗi (sẽ được global handler trong config.php xử lý nếu DEBUG=false)
    json(['message' => 'Lỗi cơ sở dữ liệu khi hủy đăng ký.', 'error' => $e->getMessage()], 500);
}
?>