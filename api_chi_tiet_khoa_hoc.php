<?php
declare(strict_types=1);

// Yêu cầu các tệp cấu hình và trợ giúp
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// 1. KIỂM TRA ĐĂNG NHẬP
$user = require_login();
$user_id = (int)$user['id'];

// 2. KIỂM TRA ID KHÓA HỌC TỪ URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($course_id <= 0) {
    json(['status' => 'error', 'message' => 'ID khóa học không hợp lệ.'], 400);
}

// 3. KẾT NỐI CSDL
$pdo = db();

// 4. KIỂM TRA BẢO MẬT: Người dùng có được ghi danh vào khóa học này không?
if (!is_enrolled($user_id, $course_id)) {
    // Ngoài ra, có thể kiểm tra thêm nếu là giáo viên/admin thì có quyền
    if ($user['role'] !== 'teacher' && $user['role'] !== 'admin') {
        json(['status' => 'error', 'message' => 'Bạn không có quyền truy cập khóa học này.'], 403);
    }
}

// 5. LẤY DỮ LIỆU CHI TIẾT
// Lấy thông tin khóa học
$stmt_course = $pdo->prepare("SELECT title, description FROM courses WHERE id = ?");
$stmt_course->execute([$course_id]);
$course_data = $stmt_course->fetch(PDO::FETCH_ASSOC);
if (!$course_data) {
    json(['status' => 'error', 'message' => 'Không tìm thấy khóa học.'], 404);
}

// Lấy danh sách bài học và trạng thái hoàn thành
$stmt_lessons = $pdo->prepare("
    SELECT l.id, l.title, l.content_type, l.content_url, l.file_path, pt.is_completed
    FROM lessons l
    LEFT JOIN progress_tracking pt ON l.id = pt.lesson_id AND pt.user_id = ?
    WHERE l.course_id = ?
    ORDER BY l.created_at ASC
");
$stmt_lessons->execute([$user_id, $course_id]);
$lessons_data = $stmt_lessons->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bài tập
$stmt_assignments = $pdo->prepare("SELECT id, title, description, due_date FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
$stmt_assignments->execute([$course_id]);
$assignments_data = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

// 6. TRẢ VỀ TOÀN BỘ DỮ LIỆU DƯỚI DẠNG JSON
json([
    'status' => 'success',
    'course' => $course_data,
    'lessons' => $lessons_data,
    'assignments' => $assignments_data
]);
?>