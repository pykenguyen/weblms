<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


require_role(['admin']);
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Phương thức không được phép.']);
    exit;
}

$studentId = $_GET['id'] ?? null;

if (!$studentId || !is_numeric($studentId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'ID học viên không hợp lệ.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$studentId]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['message' => 'Xóa học viên thành công!']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['message' => 'Không tìm thấy học viên để xóa.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Lỗi server: ' . $e->getMessage()]);
}

exit;
?>