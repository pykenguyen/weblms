<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json');

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

// Chỉ cho phép giáo viên được xóa bài tập
require_role(['teacher']);

// Lấy ID bài tập từ yêu cầu POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$assignmentId = $data['id'] ?? 0;

// Kiểm tra tính hợp lệ của ID
if (!$assignmentId) {
    echo json_encode(['success' => false, 'message' => 'ID bài tập không hợp lệ']);
    exit;
}

$pdo = db();

try {
    // Kiểm tra xem bài tập có thuộc về giáo viên hiện tại không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?");
    $stmt->execute([$assignmentId, $_SESSION['user']['id']]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bài tập này']);
        exit;
    }

    // Thực hiện xóa bài tập
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$assignmentId]);

    echo json_encode(['success' => true, 'message' => 'Bài tập đã được xóa thành công']);
} catch (PDOException $e) {
    // Bắt lỗi cơ sở dữ liệu
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}