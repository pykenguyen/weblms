<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


require_role(['admin']);
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['id']) || empty($data['name']) || empty($data['email'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'student'");
    $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? null, $data['id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Cập nhật học viên thành công!']);
    } else {
        echo json_encode(['message' => 'Không có thay đổi hoặc không tìm thấy học viên.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Lỗi server: ' . $e->getMessage()]);
}

exit;
?>