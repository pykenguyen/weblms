<?php
declare(strict_types=1);

// Assume config.php and helpers.php handle DB connection details
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


// Authenticate user with 'admin' or 'teacher' role
$user = require_role(['admin', 'teacher']);

// Hàm get date range phải được định nghĩa trước khi sử dụng
function getDateRange(string $filter, string $customStartDate = '', string $customEndDate = ''): array {
    $today = new DateTime();
    $startDate = null;
    $endDate = null;

    switch ($filter) {
        case 'month':
            $startDate = (clone $today)->modify('first day of this month')->format('Y-m-d 00:00:00');
            $endDate = (clone $today)->modify('last day of this month')->format('Y-m-d 23:59:59');
            break;
        case 'last-month':
            $startDate = (clone $today)->modify('first day of last month')->format('Y-m-d 00:00:00');
            $endDate = (clone $today)->modify('last day of last month')->format('Y-m-d 23:59:59');
            break;
        case 'quarter':
            $currentMonth = (int)$today->format('m');
            $quarter = ceil($currentMonth / 3);
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = (new DateTime($today->format('Y') . '-' . sprintf('%02d', $startMonth) . '-01'))->format('Y-m-d 00:00:00');
            $endDate = (new DateTime($today->format('Y') . '-' . sprintf('%02d', $startMonth + 2) . '-01'))->modify('last day of this month')->format('Y-m-d 23:59:59');
            break;
        case 'year':
            $startDate = (clone $today)->modify('first day of January this year')->format('Y-m-d 00:00:00');
            $endDate = (clone $today)->modify('last day of December this year')->format('Y-m-d 23:59:59');
            break;
        case 'custom':
            if (!empty($customStartDate) && !empty($customEndDate)) {
                $startDate = $customStartDate . ' 00:00:00';
                $endDate = $customEndDate . ' 23:59:59';
            }
            break;
    }
    return ['startDate' => $startDate, 'endDate' => $endDate];
}

$timeFilter = $_GET['timeFilter'] ?? 'month';
$reportType = $_GET['reportType'] ?? 'revenue';
$courseFilter = $_GET['courseFilter'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

$pdo = db();
$dateRange = getDateRange($timeFilter, $startDate, $endDate);

// --- Bắt đầu phần truy vấn dữ liệu ---

// Initialize the report data array
$reportData = [
    'stats' => [],
    'revenue_chart' => ['labels' => [], 'datasets' => []],
    'revenue_distribution_chart' => ['labels' => [], 'datasets' => []],
    'students_chart' => ['labels' => [], 'datasets' => []],
    'course_performance_chart' => ['labels' => [], 'datasets' => []],
    'detailed_report' => [],
    'overall_summary' => [],
];

// Statistical Data Queries
$monthlyRevenue = 0;
$totalStudents = 0;
$newStudents = 0;
$completionRate = 0;

$sql_monthly_revenue = "
    SELECT SUM(c.price) as total_revenue
    FROM courses c
    LEFT JOIN course_user cu ON c.id = cu.course_id
    WHERE 1=1
";
$monthlyRevenueQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_monthly_revenue .= " AND cu.created_at BETWEEN ? AND ? ";
    $monthlyRevenueQueryParams[] = $dateRange['startDate'];
    $monthlyRevenueQueryParams[] = $dateRange['endDate'];
}
$stmt = $pdo->prepare($sql_monthly_revenue);
$stmt->execute($monthlyRevenueQueryParams);
$monthlyRevenue = $stmt->fetchColumn() ?? 0;

$sql_total_students = "
    SELECT COUNT(DISTINCT user_id) as total_students
    FROM course_user
";
$stmt = $pdo->prepare($sql_total_students);
$stmt->execute();
$totalStudents = $stmt->fetchColumn() ?? 0;

$sql_new_students = "
    SELECT COUNT(id) as new_students
    FROM users
    WHERE role = 'student'
";
$newStudentsQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_new_students .= " AND created_at BETWEEN ? AND ? ";
    $newStudentsQueryParams[] = $dateRange['startDate'];
    $newStudentsQueryParams[] = $dateRange['endDate'];
}
$stmt = $pdo->prepare($sql_new_students);
$stmt->execute($newStudentsQueryParams);
$newStudents = $stmt->fetchColumn() ?? 0;

$sql_completion_rate = "
    SELECT 
        SUM(CASE WHEN pt.is_completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
        COUNT(pt.id) as total_progress_entries
    FROM progress_tracking pt
    WHERE 1=1
";
$completionRateQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_completion_rate .= " AND pt.last_viewed BETWEEN ? AND ? ";
    $completionRateQueryParams[] = $dateRange['startDate'];
    $completionRateQueryParams[] = $dateRange['endDate'];
}
$stmt = $pdo->prepare($sql_completion_rate);
$stmt->execute($completionRateQueryParams);
$progressData = $stmt->fetch();
$completionRate = ($progressData['total_progress_entries'] > 0)
    ? round(((float)$progressData['completed_lessons'] / (float)$progressData['total_progress_entries']) * 100)
    : 0;

// Prepare final stats data
$reportData['stats'] = [
    'monthly_revenue' => number_format((float)$monthlyRevenue, 0, ',', '.') . 'đ',
    'monthly_revenue_growth' => 0, // Placeholder
    'total_students' => $totalStudents,
    'student_growth' => 0, // Placeholder
    'new_students' => $newStudents,
    'new_student_growth' => 0, // Placeholder
    'completion_rate' => $completionRate,
    'completion_rate_growth' => 0, // Placeholder
];

// Chart Data Queries
$revenueChartLabels = [];
$revenueChartData = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $period = new DatePeriod(
        new DateTime($dateRange['startDate']),
        new DateInterval('P1W'),
        new DateTime($dateRange['endDate'])
    );
    foreach ($period as $dt) {
        $weekStart = $dt->format('Y-m-d 00:00:00');
        $weekEnd = (clone $dt)->modify('+6 days')->format('Y-m-d 23:59:59');
        $revenueChartLabels[] = 'Tuần ' . $dt->format('W');
        $sql_weekly_revenue = "
            SELECT SUM(c.price)
            FROM course_user cu
            JOIN courses c ON cu.course_id = c.id
            WHERE cu.created_at BETWEEN ? AND ?
        ";
        $stmt = $pdo->prepare($sql_weekly_revenue);
        $stmt->execute([$weekStart, $weekEnd]);
        $revenueChartData[] = (float)$stmt->fetchColumn() ?? 0;
    }
}
$reportData['revenue_chart'] = [
    'labels' => $revenueChartLabels,
    'datasets' => [
        [
            'label' => 'Doanh thu',
            'data' => $revenueChartData,
            'borderColor' => '#3b7ddd',
            'backgroundColor' => 'rgba(59, 125, 221, 0.2)',
            'fill' => true,
        ],
    ],
];

// Revenue distribution chart
$sql_revenue_distribution = "
    SELECT c.title as course_title, SUM(c.price) as total_revenue
    FROM course_user cu
    JOIN courses c ON cu.course_id = c.id
    WHERE 1=1
";
$revenueDistributionQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_revenue_distribution .= " AND cu.created_at BETWEEN ? AND ? ";
    $revenueDistributionQueryParams[] = $dateRange['startDate'];
    $revenueDistributionQueryParams[] = $dateRange['endDate'];
}
$sql_revenue_distribution .= "
    GROUP BY c.title
    ORDER BY total_revenue DESC
    LIMIT 5
";
$stmt = $pdo->prepare($sql_revenue_distribution);
$stmt->execute($revenueDistributionQueryParams);
$revenueDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reportData['revenue_distribution_chart'] = [
    'labels' => array_column($revenueDistribution, 'course_title'),
    'datasets' => [
        [
            'label' => 'Phân bổ doanh thu',
            'data' => array_map('floatval', array_column($revenueDistribution, 'total_revenue')),
            'backgroundColor' => ['#3b7ddd', '#1cbb8c', '#f9b84b', '#17a2b8', '#e83e8c'],
        ],
    ],
];

// Students chart
$studentsChartLabels = [];
$studentsChartData = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $period_students = new DatePeriod(
        new DateTime($dateRange['startDate']),
        new DateInterval('P1M'),
        (clone new DateTime($dateRange['endDate']))->modify('+1 month')
    );
    foreach ($period_students as $dt) {
        $monthStart = $dt->format('Y-m-01 00:00:00');
        $monthEnd = (clone $dt)->modify('last day of this month')->format('Y-m-d 23:59:59');
        $studentsChartLabels[] = 'Tháng ' . $dt->format('m');
        $sql_monthly_students = "
            SELECT COUNT(id)
            FROM users
            WHERE role = 'student' AND created_at BETWEEN ? AND ?
        ";
        $stmt = $pdo->prepare($sql_monthly_students);
        $stmt->execute([$monthStart, $monthEnd]);
        $studentsChartData[] = (int)$stmt->fetchColumn() ?? 0;
    }
}
$reportData['students_chart'] = [
    'labels' => $studentsChartLabels,
    'datasets' => [
        [
            'label' => 'Số học viên',
            'data' => $studentsChartData,
            'borderColor' => '#1cbb8c',
            'backgroundColor' => 'rgba(28, 187, 140, 0.2)',
            'fill' => true,
        ],
    ],
];

// Course performance chart
$sql_course_performance = "
    SELECT
        c.title as course_title,
        AVG(CASE WHEN pt.is_completed = 1 THEN 100 ELSE 0 END) as completion_percentage
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress_tracking pt ON l.id = pt.lesson_id
    WHERE 1=1
";
$coursePerformanceQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_course_performance .= " AND pt.last_viewed BETWEEN ? AND ? ";
    $coursePerformanceQueryParams[] = $dateRange['startDate'];
    $coursePerformanceQueryParams[] = $dateRange['endDate'];
}
$sql_course_performance .= "
    GROUP BY c.title
    ORDER BY completion_percentage DESC
    LIMIT 5
";
$stmt = $pdo->prepare($sql_course_performance);
$stmt->execute($coursePerformanceQueryParams);
$coursePerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reportData['course_performance_chart'] = [
    'labels' => array_column($coursePerformance, 'course_title'),
    'datasets' => [
        [
            'label' => 'Tỷ lệ hoàn thành (%)',
            'data' => array_map('floatval', array_column($coursePerformance, 'completion_percentage')),
            'backgroundColor' => ['#3b7ddd', '#17a2b8', '#f9b84b', '#1cbb8c', '#e83e8c'],
        ],
    ],
];

// Detailed Report Data Queries
$sql_detailed_report = "
    SELECT
        c.title as course_title,
        COUNT(DISTINCT cu.user_id) as total_students_enrolled,
        SUM(c.price) as total_revenue_course,
        AVG(CASE WHEN pt.is_completed = 1 THEN 100 ELSE 0 END) as avg_completion_rate,
        AVG(cr.rating) as avg_rating
    FROM courses c
    LEFT JOIN course_user cu ON c.id = cu.course_id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress_tracking pt ON l.id = pt.lesson_id AND pt.user_id = cu.user_id
    LEFT JOIN course_reviews cr ON c.id = cr.course_id AND cr.student_id = cu.user_id
    WHERE 1=1
";
$detailedReportQueryParams = [];
if ($dateRange['startDate'] && $dateRange['endDate']) {
    $sql_detailed_report .= " AND cu.created_at BETWEEN ? AND ? ";
    $detailedReportQueryParams[] = $dateRange['startDate'];
    $detailedReportQueryParams[] = $dateRange['endDate'];
}
$sql_detailed_report .= "
    GROUP BY c.title
    ORDER BY total_revenue_course DESC
";
$stmt = $pdo->prepare($sql_detailed_report);
$stmt->execute($detailedReportQueryParams);
$detailedReport = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formattedDetailedReport = [];
foreach ($detailedReport as $item) {
    $formattedDetailedReport[] = [
        'course' => $item['course_title'],
        'students' => (int)$item['total_students_enrolled'],
        'revenue' => number_format((float)$item['total_revenue_course'], 0, ',', '.') . 'đ',
        'completion_rate' => round((float)$item['avg_completion_rate']) . '%',
        'rating' => round((float)$item['avg_rating'], 1) ?? 'N/A',
    ];
}
$reportData['detailed_report'] = $formattedDetailedReport;

// Overall Summary Data
$reportData['overall_summary'] = [
    'total_revenue' => number_format((float)$monthlyRevenue, 0, ',', '.') . 'đ',
    'total_students' => $totalStudents,
];

// --- Bắt đầu phần xử lý trả về ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Chuẩn bị header và tên file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bao_cao_chi_tiet.csv"');

    // Mở file tạm và viết dữ liệu
    $output = fopen('php://output', 'w');

    // Thêm BOM vào đầu file để Excel nhận diện UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Tiêu đề cột
    fputcsv($output, ['Khóa học', 'Số học viên', 'Doanh thu', 'Tỷ lệ hoàn thành', 'Đánh giá']);

    // Ghi dữ liệu vào file
    foreach ($formattedDetailedReport as $row) {
        $formattedRow = [
            $row['course'],
            $row['students'],
            $row['revenue'],
            $row['completion_rate'],
            $row['rating'],
        ];
        fputcsv($output, $formattedRow);
    }

    fclose($output);
    exit;
}

// Return the final JSON data
echo json_encode($reportData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>