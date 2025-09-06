<?php
// api/admin/stats.php

// Nạp các tệp cấu hình và hàm hỗ trợ cần thiết
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Bắt buộc đăng nhập với vai trò admin
require_role('admin');
$pdo = db();

// --- Bắt đầu tính toán các chỉ số ---

// 1. Đếm tổng số học viên (phần này của bạn đã chạy đúng)
$totalStudents = (int)$pdo->query("SELECT COUNT(id) FROM users WHERE role = 'student'")->fetchColumn();


// 2. Tính tổng doanh thu trong tháng hiện tại
date_default_timezone_set('Asia/Ho_Chi_Minh');
$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth = date('Y-m-t 23:59:59');

$monthlyRevenue = (float)$pdo->query(
    "SELECT SUM(amount) FROM payments WHERE payment_date BETWEEN '{$startOfMonth}' AND '{$endOfMonth}'"
)->fetchColumn();


// 3. Tính tỷ lệ hoàn thành trung bình TOÀN HỆ THỐNG (logic đã sửa)
$completion_rate_query = $pdo->query("
    -- Bắt đầu logic tính chính xác
    SELECT AVG(completion_percentage) 
    FROM (
        SELECT 
            (COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) * 100.0 / COUNT(l.id)) AS completion_percentage
        FROM course_user cu
        JOIN lessons l ON cu.course_id = l.course_id
        LEFT JOIN progress_tracking pt ON pt.lesson_id = l.id AND pt.user_id = cu.user_id
        WHERE l.id IS NOT NULL -- Đảm bảo chỉ tính các khóa học có bài học
        GROUP BY cu.user_id, cu.course_id
    ) AS user_course_completion
    -- Kết thúc logic tính chính xác
");

$completionRate = 0;
if ($completion_rate_query) {
    // Dùng round() để làm tròn cho đẹp
    $completionRate = round((float)$completion_rate_query->fetchColumn(), 2);
}


// --- Trả về kết quả dưới dạng JSON ---

header('Content-Type: application/json');
echo json_encode([
    'totalStudents' => $totalStudents,
    'monthlyRevenue' => $monthlyRevenue,
    'completionRate' => $completionRate 
]);

?>