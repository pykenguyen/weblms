<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Chỉ cho phép admin truy cập
require_role(['admin']);
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

// Nhận dữ liệu JSON từ request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Kiểm tra dữ liệu đầu vào
if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Dữ liệu không hợp lệ. Vui lòng điền đầy đủ các trường bắt buộc.']);
    exit;
}

try {
    // Hash mật khẩu
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Chuẩn bị câu lệnh SQL để thêm học viên
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->execute([$data['name'], $data['email'], $hashedPassword]);

    // Trả về phản hồi thành công
    http_response_code(201); // Created
    echo json_encode(['message' => 'Học viên đã được thêm thành công!']);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Lỗi server: ' . $e->getMessage()]);
}

exit;
?>