<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


// Chỉ cho phép admin
$user = require_role(['admin']);
$pdo = db();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$id = (int)$data['id'];
$title = trim($data['title'] ?? '');
$teacher = trim($data['teacher'] ?? '');
$language = trim($data['language'] ?? '');
$price = (int)($data['price'] ?? 0);

if ($title === '' || $teacher === '' || $language === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE courses SET title=?, teacher_name=?, language=?, price=? WHERE id=?");
    $stmt->execute([$title, $teacher, $language, $price, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
