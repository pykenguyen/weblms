<?php
declare(strict_types=1);

// Bắt đầu session nếu chưa có.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yêu cầu các file cần thiết.
// Giả sử config.php và helpers.php nằm cùng thư mục với logout.php
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Xóa tất cả các biến session.
$_SESSION = [];

// Hủy cookie session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session trên server.
session_destroy();

// Thay vì trả về JSON, chuyển hướng người dùng về trang đăng nhập.
header('Location: login.php');
exit();