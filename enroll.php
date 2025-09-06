<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Bắt đầu một transaction để đảm bảo toàn vẹn dữ liệu
$pdo = db();
$pdo->beginTransaction();

try {
    // 1. Yêu cầu người dùng phải là sinh viên
    $u = require_role('student');

    // 2. Lấy ID khóa học từ request body
    $data = body();
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0) {
        json(['message' => 'Thiếu ID của khóa học.'], 422);
    }

    // 3. Lấy tên khóa học và kiểm tra xem khóa học có tồn tại không
    $stmt = $pdo->prepare('SELECT title FROM courses WHERE id = ?');
    $stmt->execute([$course_id]);
    $course_title = $stmt->fetchColumn();

    if (!$course_title) {
        json(['message' => 'Không tìm thấy khóa học với ID này.'], 404);
    }

    // 4. KIỂM TRA TRÙNG LẶP VÀ CẬP NHẬT THÔNG BÁO
    if (is_enrolled((int)$u['id'], $course_id)) {
        // Trả về lỗi 409 Conflict với thông báo cụ thể
        json(['message' => "Bạn đã đăng ký khóa học \"{$course_title}\" này rồi!"], 409);
    }

    // 5. Nếu mọi thứ hợp lệ, thực hiện ghi danh
    $ins = $pdo->prepare('INSERT INTO course_user (course_id, user_id, created_at) VALUES (?, ?, NOW())');
    $ins->execute([$course_id, (int)$u['id']]);

    // 6. Lưu các thay đổi vào cơ sở dữ liệu
    $pdo->commit();

    // 7. Trả về thông báo thành công
    json(['message' => "Đăng ký thành công khóa học: \"{$course_title}\"!"]);

} catch (Throwable $e) {
    // Nếu có bất kỳ lỗi nào xảy ra, hủy bỏ mọi thay đổi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Gửi thông báo lỗi chung
    json(['message' => 'Đã có lỗi xảy ra trong quá trình đăng ký.'], 500);
}