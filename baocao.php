<?php
session_start();
// Nạp các tệp cấu hình và hàm hỗ trợ của bạn
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require __DIR__ . '/admin_header.php'; 
// Bắt buộc người dùng phải đăng nhập với vai trò admin
$user = require_role('admin');
$pdo = db();

// --- PHẦN 1: XỬ LÝ LỌC THỜI GIAN VÀ CHUẨN BỊ TRUY VẤN ---

// Mặc định là báo cáo theo tháng hiện tại
$time_filter = $_GET['timeFilter'] ?? 'month';
$start_date_str = '';
$end_date_str = '';
$previous_start_date_str = '';
$previous_end_date_str = '';

// Lấy múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Xác định khoảng thời gian cho báo cáo
switch ($time_filter) {
    case 'year':
        $start_date = new DateTime(date('Y-01-01'));
        $end_date = new DateTime(date('Y-12-31'));
        $previous_start_date = (clone $start_date)->modify('-1 year');
        $previous_end_date = (clone $end_date)->modify('-1 year');
        break;
    default: // Mặc định là 'month'
        $start_date = new DateTime('first day of this month');
        $end_date = new DateTime('last day of this month');
        $previous_start_date = (clone $start_date)->modify('-1 month');
        $previous_end_date = (clone $end_date)->modify('-1 month');
        break;
}

// Định dạng ngày tháng cho truy vấn SQL
$start_date_str = $start_date->format('Y-m-d 00:00:00');
$end_date_str = $end_date->format('Y-m-d 23:59:59');
$previous_start_date_str = $previous_start_date->format('Y-m-d 00:00:00');
$previous_end_date_str = $previous_end_date->format('Y-m-d 23:59:59');

$date_condition = "AND payment_date BETWEEN '{$start_date_str}' AND '{$end_date_str}'";
$prev_date_condition = "AND payment_date BETWEEN '{$previous_start_date_str}' AND '{$previous_end_date_str}'";

// --- PHẦN 2: TRUY VẤN CƠ SỞ DỮ LIỆU ĐỂ LẤY DỮ LIỆU BÁO CÁO ---

// Hàm tính toán tăng trưởng
function calculate_growth($current, $previous) {
    if ($previous == 0) return ($current > 0) ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 2);
}

// 1. Thống kê tổng quan (Stats)
$current_revenue = (float)$pdo->query("SELECT SUM(amount) FROM payments WHERE 1 {$date_condition}")->fetchColumn();
$previous_revenue = (float)$pdo->query("SELECT SUM(amount) FROM payments WHERE 1 {$prev_date_condition}")->fetchColumn();

$total_students = (int)$pdo->query("SELECT COUNT(id) FROM users WHERE role = 'student'")->fetchColumn();
$new_students_current = (int)$pdo->query("SELECT COUNT(id) FROM users WHERE role = 'student' AND created_at BETWEEN '{$start_date_str}' AND '{$end_date_str}'")->fetchColumn();
$new_students_previous = (int)$pdo->query("SELECT COUNT(id) FROM users WHERE role = 'student' AND created_at BETWEEN '{$previous_start_date_str}' AND '{$previous_end_date_str}'")->fetchColumn();

// Tỷ lệ hoàn thành (Đây là truy vấn phức tạp, có thể cần tối ưu cho hệ thống lớn)
$completion_rate_query = $pdo->query("
    SELECT AVG(completion_percentage) 
    FROM (
        SELECT 
            (COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) * 100.0 / COUNT(l.id)) AS completion_percentage
        FROM course_user cu
        JOIN lessons l ON cu.course_id = l.course_id
        LEFT JOIN progress_tracking pt ON pt.lesson_id = l.id AND pt.user_id = cu.user_id
        GROUP BY cu.user_id, cu.course_id
    ) AS user_course_completion
");
$completion_rate = $completion_rate_query ? (float)$completion_rate_query->fetchColumn() : 0;


$stats = [
    "monthly_revenue" => number_format($current_revenue, 0, ',', '.') . 'đ',
    "monthly_revenue_growth" => calculate_growth($current_revenue, $previous_revenue),
    "total_students" => $total_students,
    "student_growth" => 0, // Cần logic phức tạp hơn để tính toán chính xác
    "new_students" => $new_students_current,
    "new_student_growth" => calculate_growth($new_students_current, $new_students_previous),
    "completion_rate" => round($completion_rate, 2),
    "completion_rate_growth" => 0, // Cần logic phức tạp hơn
];

// 2. Dữ liệu cho các biểu đồ
// Biểu đồ doanh thu theo tuần trong tháng
$revenue_by_week_stmt = $pdo->prepare("
    SELECT WEEK(payment_date, 1) as week_num, SUM(amount) as weekly_revenue
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY week_num ORDER BY week_num ASC
");
$revenue_by_week_stmt->execute([$start_date_str, $end_date_str]);
$revenue_by_week = $revenue_by_week_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$revenue_chart_labels = [];
$revenue_chart_data = [];
$num_weeks = (int)$start_date->format('W') - (int)(new DateTime('first day of this month'))->format('W') + 5;
for ($i = 1; $i <= $num_weeks; $i++) {
    $week_num = (int)(new DateTime('first day of this month'))->format('W') + $i - 1;
    $revenue_chart_labels[] = "Tuần " . $i;
    $revenue_chart_data[] = $revenue_by_week[$week_num] ?? 0;
}


// Biểu đồ phân bổ doanh thu theo khóa học
$revenue_dist_stmt = $pdo->query("
    SELECT c.title, SUM(p.amount) as total_revenue
    FROM payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.payment_date BETWEEN '{$start_date_str}' AND '{$end_date_str}'
    GROUP BY c.title ORDER BY total_revenue DESC LIMIT 5
");
$revenue_dist = $revenue_dist_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Báo cáo chi tiết dạng bảng
// --- BẮT ĐẦU PHẦN SỬA LỖI ---
$detailed_report_stmt = $pdo->query("
    SELECT
        c.title AS course,
        COUNT(DISTINCT cu.user_id) AS students,
        IFNULL(p_sum.total_revenue, 0) AS revenue,
        IFNULL(course_completion.avg_course_completion, 0) AS completion_rate
    FROM courses c
    LEFT JOIN course_user cu ON c.id = cu.course_id
    LEFT JOIN (
        SELECT course_id, SUM(amount) as total_revenue
        FROM payments
        WHERE payment_date BETWEEN '{$start_date_str}' AND '{$end_date_str}'
        GROUP BY course_id
    ) p_sum ON c.id = p_sum.course_id
    LEFT JOIN (
        -- Bước 1: Tính % hoàn thành của TỪNG HỌC VIÊN trong mỗi khóa học
        WITH user_course_progress AS (
            SELECT
                cu.user_id,
                l.course_id,
                (COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) * 100.0) / COUNT(l.id) as user_completion_percentage
            FROM course_user cu
            JOIN lessons l ON cu.course_id = l.course_id
            LEFT JOIN progress_tracking pt ON pt.lesson_id = l.id AND pt.user_id = cu.user_id
            WHERE l.course_id IS NOT NULL AND l.id IS NOT NULL
            GROUP BY cu.user_id, l.course_id
        )
        -- Bước 2: Lấy trung bình cộng của các % đã tính ở trên cho mỗi khóa học
        SELECT
            course_id,
            AVG(user_completion_percentage) as avg_course_completion
        FROM user_course_progress
        GROUP BY course_id
    ) course_completion ON c.id = course_completion.course_id
    GROUP BY c.id
    ORDER BY revenue DESC
");
// --- KẾT THÚC PHẦN SỬA LỖI ---
$detailed_report = $detailed_report_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- PHẦN 3: TỔNG HỢP DỮ LIỆU CHO JAVASCRIPT ---

$initial_report_data = [
    "stats" => $stats,
    "revenue_chart" => [
        "labels" => $revenue_chart_labels,
        "datasets" => [[
            "label" => "Doanh thu", "data" => $revenue_chart_data,
            "borderColor" => "#3b7ddd", "fill" => false,
        ]],
    ],
    "revenue_distribution_chart" => [
        "labels" => array_column($revenue_dist, 'title'),
        "datasets" => [[
            "data" => array_column($revenue_dist, 'total_revenue'),
            "backgroundColor" => ["#3b7ddd", "#1cbb8c", "#f9b84b", "#e83e8c", "#17a2b8"],
        ]],
    ],
    "students_chart" => [ // Dữ liệu này cần truy vấn riêng, hiện đang để trống
        "labels" => [], "datasets" => [],
    ],
    "course_performance_chart" => [ // Dữ liệu này cần truy vấn riêng, hiện đang để trống
        "labels" => [], "datasets" => [],
    ],
    "detailed_report" => array_map(function($item) {
        return [
            "course" => $item['course'],
            "students" => (int)$item['students'],
            "revenue" => number_format((float)$item['revenue'], 0, ',', '.') . 'đ',
            "completion_rate" => round((float)$item['completion_rate'], 2) . '%',
            "rating" => "N/A", // Cần bảng reviews để lấy dữ liệu này
        ];
    }, $detailed_report),
    "overall_summary" => ["total_revenue" => number_format($current_revenue, 0, ',', '.') . 'đ'],
];


// --- PHẦN 4: DỮ LIỆU ĐỘNG CHO CÁC THÀNH PHẦN UI ---
$sidebar_nav_items = [
    ['href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'text' => 'Dashboard'],
    ['href' => 'khoahoc.php', 'icon' => 'fa-book', 'text' => 'Khóa học'],
    ['href' => 'hocvien.php', 'icon' => 'fa-users', 'text' => 'Học viên'],
    ['href' => 'baocao.php', 'icon' => 'fa-chart-bar', 'text' => 'Báo cáo', 'active' => true],
    ['href' => 'caidat.php', 'icon' => 'fa-cog', 'text' => 'Cài đặt'],
];
// Lấy danh sách khóa học cho bộ lọc
$courses_for_filter = $pdo->query("SELECT title FROM courses ORDER BY title ASC")->fetchAll(PDO::FETCH_COLUMN);

?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Báo cáo & Thống kê</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #3b7ddd; --secondary: #6c757d; --success: #1cbb8c; --info: #17a2b8; --warning: #f9b84b; --danger: #e83e8c; --light: #f8f9fa; --dark: #343a40; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f6f8; color: #495057; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--dark); color: white; padding: 20px 0; flex-shrink: 0; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); padding: 12px 20px; margin: 4px 0; border-radius: 5px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255, 255, 255, 0.1); color: white; }
        .sidebar .nav-link i { width: 20px; margin-right: 10px; }
        .main-content { flex-grow: 1; padding: 20px; overflow-y: auto; }
        .header { background-color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .stats-card { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); margin-bottom: 20px; text-align: center; transition: transform 0.3s; }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-number { font-size: 24px; font-weight: bold; color: var(--primary); }
        .stats-label { font-size: 14px; color: var(--secondary); }
        .content-section { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .section-title { padding-bottom: 15px; margin-bottom: 20px; border-bottom: 2px solid #e9ecef; font-weight: 600; color: var(--dark); }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        .filter-card { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .table-responsive { border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); }
        .table th { background-color: var(--primary); color: white; }
        .report-summary { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef; }
        .summary-item { text-align: center; flex: 1; }
        .summary-value { font-size: 18px; font-weight: bold; color: var(--primary); }
        .summary-label { font-size: 12px; color: var(--secondary); }
        @media print { .sidebar, .header, .filter-card, .btn, .d-flex.justify-content-between.align-items-center.mb-4 { display: none !important; } .main-content { padding: 0; margin: 0; width: 100%; } .table th { background-color: #3b7ddd !important; -webkit-print-color-adjust: exact; color: white !important; } body { background-color: white !important; } }
    </style>
</head>
<body>
    

        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-chart-bar me-2"></i> Báo cáo & Thống kê</h2>
            </div>

            <div class="filter-card">
                 <h5 class="section-title"><i class="fas fa-filter me-2"></i> Tùy chọn báo cáo</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Thời gian</label>
                        <select class="form-select" id="timeFilter">
                            <option value="month" selected>Tháng này</option>
                            <option value="year">Năm nay</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Khóa học</label>
                        <select class="form-select" id="courseFilter">
                            <option value="" selected>Tất cả khóa học</option>
                            <?php foreach ($courses_for_filter as $course_title): ?>
                            <option value="<?php echo htmlspecialchars($course_title); ?>"><?php echo htmlspecialchars($course_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-primary" id="generateReport" onclick="handleGenerateReport()">
                                <i class="fas fa-sync-alt me-1"></i> Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-4" id="stats-summary">
                </div>

            <div class="row mt-4">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="content-section h-100">
                        <h5 class="section-title"><i class="fas fa-chart-line me-2"></i> Doanh thu</h5>
                        <div class="chart-container"><canvas id="revenueChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="content-section h-100">
                        <h5 class="section-title"><i class="fas fa-chart-pie me-2"></i> Phân bổ doanh thu</h5>
                        <div class="chart-container"><canvas id="revenueDistributionChart"></canvas></div>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="section-title mb-0"><i class="fas fa-table me-2"></i> Báo cáo chi tiết theo khóa học</h5>
                    <div>
                        <button id="printReportBtn" class="btn btn-outline-primary me-2"><i class="fas fa-print me-1"></i> In</button>
                        <button id="exportExcelBtn" class="btn btn-success">
                <i class="fas fa-download me-1"></i> Xuất Excel
            </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Khóa học</th>
                                <th>Số học viên</th>
                                <th>Doanh thu</th>
                                <th>Tỷ lệ hoàn thành</th>
                                <th>Đánh giá</th>
                            </tr>
                        </thead>
                        <tbody id="detailed-report-body"></tbody>
                    </table>
                </div>
                <div class="report-summary" id="overall-summary"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const initialReportData = <?php echo json_encode($initial_report_data, JSON_NUMERIC_CHECK); ?>;
        
        // Hàm này sẽ được gọi khi người dùng bấm nút "Tạo báo cáo"
        function handleGenerateReport() {
            const timeFilter = document.getElementById('timeFilter').value;
            // Chuyển hướng trang với tham số filter mới
            window.location.href = `baocao.php?timeFilter=${timeFilter}`;
        }
        
        function updateUI(data) {
            const stats = data.stats;
            document.getElementById('stats-summary').innerHTML = `
                <div class="col-md-3 col-6"><div class="stats-card"><div class="stats-number">${stats.monthly_revenue}</div><div class="stats-label">Doanh thu</div><div class="mt-2"><span class="badge bg-${stats.monthly_revenue_growth >= 0 ? 'success' : 'danger'}"><i class="fas fa-arrow-${stats.monthly_revenue_growth >= 0 ? 'up' : 'down'} me-1"></i> ${stats.monthly_revenue_growth}%</span></div></div></div>
                <div class="col-md-3 col-6"><div class="stats-card"><div class="stats-number">${stats.total_students}</div><div class="stats-label">Tổng học viên</div></div></div>
                <div class="col-md-3 col-6"><div class="stats-card"><div class="stats-number">${stats.new_students}</div><div class="stats-label">Học viên mới</div><div class="mt-2"><span class="badge bg-${stats.new_student_growth >= 0 ? 'success' : 'danger'}"><i class="fas fa-arrow-${stats.new_student_growth >= 0 ? 'up' : 'down'} me-1"></i> ${stats.new_student_growth}%</span></div></div></div>
                <div class="col-md-3 col-6"><div class="stats-card"><div class="stats-number">${stats.completion_rate}%</div><div class="stats-label">Tỷ lệ hoàn thành</div></div></div>
            `;
            updateRevenueChart(data.revenue_chart);
            updateRevenueDistributionChart(data.revenue_distribution_chart);
            const detailedTableBody = document.getElementById('detailed-report-body');
            detailedTableBody.innerHTML = '';
            data.detailed_report.forEach(item => {
                detailedTableBody.innerHTML += `
                    <tr>
                        <td>${item.course}</td>
                        <td>${item.students}</td>
                        <td>${item.revenue}</td>
                        <td><div class="progress" style="height: 5px;"><div class="progress-bar" style="width: ${item.completion_rate};"></div></div> ${item.completion_rate}</td>
                        <td><i class="fas fa-star text-warning"></i> ${item.rating}</td>
                    </tr>`;
            });
            document.getElementById('overall-summary').innerHTML = `<div class="summary-item"><div class="summary-value">${data.overall_summary.total_revenue}</div><div class="summary-label">TỔNG DOANH THU</div></div>`;
        }
        
        let revenueChart, revenueDistributionChart;
        const chartOptions = { responsive: true, maintainAspectRatio: false, animation: { duration: 1000 } };

        function updateRevenueChart(chartData) {
            if (revenueChart) revenueChart.destroy();
            revenueChart = new Chart(document.getElementById('revenueChart').getContext('2d'), { type: 'line', data: chartData, options: chartOptions });
        }
        function updateRevenueDistributionChart(chartData) {
            if (revenueDistributionChart) revenueDistributionChart.destroy();
            revenueDistributionChart = new Chart(document.getElementById('revenueDistributionChart').getContext('2d'), { type: 'doughnut', data: chartData, options: chartOptions });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateUI(initialReportData);
            document.getElementById('printReportBtn').addEventListener('click', () => window.print());
        });
        document.getElementById('exportExcelBtn').addEventListener('click', function() {
        
        // 1. Lấy giá trị đang được chọn trong bộ lọc thời gian (Tháng này / Năm nay)
        const timeFilter = document.getElementById('timeFilter').value;
        
        // 2. Chuyển hướng trình duyệt đến file export_excel.php, 
        //    kèm theo bộ lọc thời gian để file Excel được xuất ra chính xác.
        //    Trình duyệt sẽ tự động tải file về.
        window.location.href = `export_excel.php?timeFilter=${timeFilter}`;
        });
    </script>
</body>
</html>