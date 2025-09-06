<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


require_role(['admin']);
$pdo = db();

// Lấy bộ lọc từ URL
$search = $_GET['search'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

// Câu SQL cơ bản (giống như trên)
$sql = "
    SELECT
        u.name,
        u.email,
        c.title AS course_title,
        cu.created_at,
        pt_completed.count AS completed_lessons,
        pt_total.count AS total_lessons
    FROM users u
    LEFT JOIN course_user cu ON u.id = cu.user_id
    LEFT JOIN courses c ON cu.course_id = c.id
    LEFT JOIN (
        SELECT user_id, COUNT(*) AS count 
        FROM progress_tracking 
        WHERE is_completed = 1 
        GROUP BY user_id
    ) pt_completed ON u.id = pt_completed.user_id
    LEFT JOIN (
        SELECT user_id, COUNT(*) AS count 
        FROM progress_tracking 
        GROUP BY user_id
    ) pt_total ON u.id = pt_total.user_id
    WHERE u.role = 'student'
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xuất CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="danh_sach_hoc_vien.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

fputcsv($output, ['Học viên', 'Email', 'Khóa học', 'Ngày đăng ký', 'Tiến độ', 'Trạng thái']);

foreach ($students as $student) {
    $progress = ($student['total_lessons'] > 0)
        ? round(($student['completed_lessons'] / $student['total_lessons']) * 100) . '%'
        : '0%';

    // Sửa lỗi: kiểm tra null trước khi tạo đối tượng DateTime
    $registered_at_formatted = 'N/A';
    if (!empty($student['created_at'])) {
        $registered_at_formatted = (new DateTime($student['created_at']))->format('d/m/Y');
    }

    $row = [
        $student['name'],
        $student['email'],
        $student['course_title'] ?? 'Chưa đăng ký',
        $registered_at_formatted,
        $progress,
        'Đang học'
    ];
    fputcsv($output, $row);
}

fclose($output);
exit;