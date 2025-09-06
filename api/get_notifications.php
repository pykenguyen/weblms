<?php
declare(strict_types=1);
require __DIR__.'/helpers.php';
$user = require_login(); // bắt buộc đăng nhập
$userId = $user['id'];

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, title, message, link, type, created_at, is_read
    FROM notifications
    WHERE user_id=?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

json(['items' => $notifications]);
