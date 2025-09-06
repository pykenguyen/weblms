<?php
declare(strict_types=1);

/** DEBUG: bật true khi dev, false khi lên prod */
const DEBUG = true;

/**
 * Global error & exception handlers
 */
set_exception_handler(function (Throwable $e) {
    http_response_code(500);

    if (defined('API_MODE') && API_MODE) {
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['message' => 'Server error'];
        if (DEBUG) $payload['error'] = $e->getMessage();
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        if (DEBUG) {
            echo "<h1>Lỗi hệ thống</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        } else {
            echo "<h1>Đã xảy ra lỗi, vui lòng thử lại sau.</h1>";
        }
    }
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

ini_set('display_errors', DEBUG ? '1' : '0');

/**
 * Xác định API_MODE tự động
 * Nếu gọi từ thư mục /api/ -> true, ngược lại -> false
 */
if (!defined('API_MODE')) {
    define('API_MODE', str_contains(__DIR__, DIRECTORY_SEPARATOR . 'api'));
}

/**
 * Kết nối database
 */
$dsn    = 'mysql:host=127.0.0.1;port=3307;dbname=lcms_db;charset=utf8mb4';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    throw new RuntimeException('Kết nối database thất bại: ' . $e->getMessage());
}

/**
 * Khởi tạo session an toàn
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'path' => '/',
    ]);
    session_start();
}

/**
 * Helper để gọi DB
 */
function db(): PDO {
    global $pdo;
    return $pdo;
}
