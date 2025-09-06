<?php
declare(strict_types=1);

// Yêu cầu các tệp cấu hình và trợ giúp
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

try {
    // Lấy kết nối CSDL
    $pdo = db();

    // Chuẩn bị câu lệnh SQL để lấy tất cả các khóa học
    // và tên của giáo viên dạy khóa học đó
    $sql = "
        SELECT 
            c.id, 
            c.title, 
            c.description, 
            c.thumbnail,
            u.name AS teacher_name 
        FROM 
            courses c
        LEFT JOIN 
            users u ON c.teacher_id = u.id
        ORDER BY 
            c.created_at DESC
    ";

    // Thực thi câu lệnh
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về danh sách khóa học dưới dạng JSON
    json($courses);

} catch (Exception $e) {
    // Nếu có lỗi, trả về lỗi server
    json(['message' => 'Không thể lấy dữ liệu khóa học.', 'error' => $e->getMessage()], 500);
}
?>