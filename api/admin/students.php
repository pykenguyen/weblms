<?php
declare(strict_types=1);

// Sửa đường dẫn để nạp đúng tệp
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Bắt buộc người dùng phải đăng nhập với vai trò admin
$user = require_role('admin');
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

// Lấy tham số trang từ URL, mặc định là trang 1 và 10 học viên/trang
$page = (int) ($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // 1. Lấy tổng số học viên để tính tổng số trang
    $totalStudentsStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudentsStmt->execute();
    $totalStudents = $totalStudentsStmt->fetchColumn();
    $totalPages = (int) ceil($totalStudents / $limit);

    // 2. Lấy danh sách học viên cho trang hiện tại
    // Đã sửa lại truy vấn để liên kết chính xác bảng progress_tracking với lessons
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.created_at,
               GROUP_CONCAT(DISTINCT c.title) AS enrolled_courses,
               COUNT(DISTINCT l.id) AS total_lessons,
               COUNT(DISTINCT pt.lesson_id) AS completed_lessons,
               'active' AS status_text,
               'active' AS status
        FROM users u
        LEFT JOIN course_user cu ON u.id = cu.user_id
        LEFT JOIN courses c ON cu.course_id = c.id
        LEFT JOIN lessons l ON l.course_id = c.id
        LEFT JOIN progress_tracking pt ON pt.user_id = u.id AND pt.lesson_id = l.id
        WHERE u.role = 'student'
        GROUP BY u.id, u.name, u.email, u.phone, u.created_at
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về dữ liệu bao gồm cả thông tin phân trang
    echo json_encode([
        'items' => $students,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'total_items' => $totalStudents
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    // Bắt và xử lý lỗi PDO để trả về JSON
    http_response_code(500);
    echo json_encode(['message' => 'Lỗi server: ' . $e->getMessage()]);
}

exit;
?>