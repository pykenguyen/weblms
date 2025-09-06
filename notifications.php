<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$user = require_login();
$user_id = (int)$user['id'];
$pdo = db();

header('Content-Type: application/json');

// Nhận dữ liệu POST JSON
$input = json_decode(file_get_contents('php://input'), true);

// --- Xử lý đánh dấu đã đọc ---
if (!empty($input['action']) && $input['action'] === 'read' && !empty($input['ids'])) {
    $ids = $input['ids'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND id IN ($placeholders)");
    if ($stmt->execute(array_merge([$user_id], $ids))) {
        echo json_encode(['success'=>true]);
        exit;
    } else {
        echo json_encode(['success'=>false]);
        exit;
    }
}

// --- Lấy thông báo mới ---
$stmt_unread = $pdo->prepare("
SELECT COUNT(*) FROM notifications 
WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW()) AND is_read=0
");
$stmt_unread->execute([$user_id]);
$unread_count = (int)$stmt_unread->fetchColumn();

$stmt_recent = $pdo->prepare("
SELECT id, title, message, created_at, is_read
FROM notifications
WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW())
ORDER BY created_at DESC
LIMIT 5
");
$stmt_recent->execute([$user_id]);
$recent_notifications = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'unread' => $unread_count,
    'recent' => $recent_notifications
]);
