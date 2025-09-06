<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

$user = require_login();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$name || !$email) {
    die('Vui lòng điền đầy đủ thông tin.');
}

$pdo = db();

try {
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, password=? WHERE id=?');
        $stmt->execute([$name, $email, $hash, $user['id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?');
        $stmt->execute([$name, $email, $user['id']]);
    }

    header('Location: ../giaovien.php?success=1');
    exit();
} catch (Throwable $e) {
    die('Cập nhật thất bại: ' . $e->getMessage());
}
