<?php
declare(strict_types=1);

// SỬA LỖI: Thêm dấu gạch chéo '/' sau __DIR__
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Kiểm tra đăng nhập bằng hàm trợ giúp
$user = require_login();
$user_id = (int)$user['id'];
$user_name = $user['name'];

// Lấy kết nối CSDL từ hàm db()
$pdo = db();

// LẤY DỮ LIỆU KHÓA HỌC
$sql_courses = "SELECT
                    c.id, c.title, c.thumbnail, t.name as teacher_name,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                    (SELECT COUNT(*) FROM progress_tracking WHERE user_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = c.id) AND is_completed = 1) as completed_lessons
                FROM courses c
                JOIN course_user cu ON c.id = cu.course_id
                LEFT JOIN users t ON c.teacher_id = t.id
                WHERE cu.user_id = ?";

$stmt_courses = $pdo->prepare($sql_courses);
$stmt_courses->execute([$user_id, $user_id]);
$courses_data = $stmt_courses->fetchAll();

// LẤY DỮ LIỆU BÀI TẬP
$sql_assignments = "SELECT
                        a.title as assignment_title, c.title as course_title, a.due_date
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.id
                    WHERE a.course_id IN (SELECT course_id FROM course_user WHERE user_id = ?)
                    AND a.due_date > NOW()
                    ORDER BY a.due_date ASC
                    LIMIT 5";

$stmt_assignments = $pdo->prepare($sql_assignments);
$stmt_assignments->execute([$user_id]);
$assignments_data = $stmt_assignments->fetchAll();

// TRẢ VỀ DỮ LIỆU DƯỚI DẠNG JSON bằng hàm trợ giúp
json([
    'status' => 'success',
    'userName' => $user_name,
    'courses' => $courses_data,
    'assignments' => $assignments_data
]);

?>