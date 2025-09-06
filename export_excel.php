<?php
declare(strict_types=1);

// Nạp các tệp cần thiết
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require __DIR__ . '/api/SimpleXLSXGen.php'; 

// Dùng namespace của thư viện
use Shuchkin\SimpleXLSXGen;

// Yêu cầu quyền admin
require_role(['admin']);
$pdo = db();

// Lấy bộ lọc thời gian từ URL
$time_filter = $_GET['timeFilter'] ?? 'month';
date_default_timezone_set('Asia/Ho_Chi_Minh');

switch ($time_filter) {
    case 'year':
        $start_date = new DateTime(date('Y-01-01'));
        $end_date = new DateTime(date('Y-12-31'));
        break;
    default: // 'month'
        $start_date = new DateTime('first day of this month');
        $end_date = new DateTime('last day of this month');
        break;
}
$start_date_str = $start_date->format('Y-m-d 00:00:00');
$end_date_str = $end_date->format('Y-m-d 23:59:59');

// Truy vấn dữ liệu báo cáo chi tiết
$detailed_report_stmt = $pdo->query("
    SELECT
        c.title AS course,
        COUNT(DISTINCT cu.user_id) AS students,
        IFNULL((SELECT SUM(p.amount) FROM payments p WHERE p.course_id = c.id AND p.payment_date BETWEEN '{$start_date_str}' AND '{$end_date_str}'), 0) AS revenue,
        IFNULL(course_completion.avg_course_completion, 0) AS completion_rate
    FROM courses c
    LEFT JOIN course_user cu ON c.id = cu.course_id
    LEFT JOIN (
        WITH user_course_progress AS (
            SELECT
                l.course_id,
                (COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) * 100.0) / COUNT(l.id) as user_completion_percentage
            FROM course_user cu
            JOIN lessons l ON cu.course_id = l.course_id
            LEFT JOIN progress_tracking pt ON pt.lesson_id = l.id AND pt.user_id = cu.user_id
            GROUP BY cu.user_id, l.course_id
        )
        SELECT course_id, AVG(user_completion_percentage) as avg_course_completion
        FROM user_course_progress
        GROUP BY course_id
    ) course_completion ON c.id = course_completion.course_id
    GROUP BY c.id
    ORDER BY revenue DESC
");

$detailed_report = $detailed_report_stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu cho file Excel
$excel_data = [];
$excel_data[] = ['<b>Khóa học</b>', '<b>Số học viên</b>', '<b>Doanh thu (VND)</b>', '<b>Tỷ lệ hoàn thành (%)</b>'];

$total_revenue = 0;
foreach ($detailed_report as $row) {
    $excel_data[] = [
        $row['course'],
        (int)$row['students'],
        (float)$row['revenue'],
        round((float)$row['completion_rate'], 2)
    ];
    $total_revenue += (float)$row['revenue'];
}

$excel_data[] = []; // Dòng trống
$excel_data[] = ['<b>TỔNG DOANH THU</b>', '', $total_revenue, ''];


// Tạo và tải file Excel
$filename = "BaoCao_" . date('Y-m-d') . ".xlsx";

// === SỬA LỖI Ở ĐÂY ===
// Tạo đối tượng XLSX từ mảng dữ liệu
$xlsx = SimpleXLSXGen::fromArray($excel_data); 

// DÒNG GÂY LỖI ĐÃ ĐƯỢC XÓA
// $xlsx->sheetName('BaoCaoDoanhThu'); 

// Tải file về với tên đã định
$xlsx->downloadAs($filename);

exit();
?>