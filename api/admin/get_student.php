<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


require_role(['admin']);
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

$studentId = $_GET['id'] ?? null;

if (!$studentId || !is_numeric($studentId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID học viên không hợp lệ.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        http_response_code(200); // OK
        // Sửa đổi để trả về đối tượng có key 'data'
        echo json_encode(['success' => true, 'data' => $student]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy học viên.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

exit;