<?php
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['user'] ?? null;
$teacherId = $user['id'] ?? 0;

if (!$teacherId) {
    echo json_encode(['items'=>[]], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.title, a.description, a.due_date, a.file_path,
            c.title AS course_title
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$teacherId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn hóa đường dẫn file nếu cần
    foreach ($assignments as &$a) {
        if (!empty($a['file_path'])) {
            $a['file_path'] = '../uploads/' . basename($a['file_path']); // tùy theo thư mục lưu file
        }
    }

    echo json_encode(['items'=>$assignments], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['items'=>[], 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
