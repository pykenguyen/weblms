<?php
declare(strict_types=1);

// Yêu cầu các tệp cấu hình và trợ giúp
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Chỉ cho phép học viên truy cập API này
$user = require_role(['student']);
$student_id = (int)$user['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['message' => 'Method Not Allowed'], 405);
}

// 1. Lấy tất cả các bài nộp đã được chấm của học viên
$sql_submitted = "
    SELECT s.grade, a.id AS assignment_id, a.title AS assignment_title, c.title AS course_title
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = ? AND s.grade IS NOT NULL
    ORDER BY s.submitted_at DESC
";

$stmt_submitted = $pdo->prepare($sql_submitted);
$stmt_submitted->execute([$student_id]);
$submitted_assignments = $stmt_submitted->fetchAll(PDO::FETCH_ASSOC);

// 2. Phân tích kết quả để tìm ra các điểm yếu (bài tập có điểm dưới 8)
$weaknesses = [];
foreach ($submitted_assignments as $assignment) {
    if ($assignment['grade'] < 8) {
        $weaknesses[] = [
            'assignment_id'    => $assignment['assignment_id'],
            'assignment_title' => $assignment['assignment_title'],
            'course_title'     => $assignment['course_title'],
            'grade'            => $assignment['grade']
        ];
    }
}

// 3. Trả về kết quả dưới dạng JSON
json([
    'status' => 'success',
    'analysis' => [
        'total_submitted' => count($submitted_assignments),
        'weaknesses' => $weaknesses,
        'message' => empty($weaknesses) 
            ? 'Bạn đã hoàn thành tốt tất cả các bài tập.' 
            : 'Hãy xem lại các bài tập có điểm thấp.'
    ]
]);