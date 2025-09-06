<?php
declare(strict_types=1);
require __DIR__.'/api/config.php';
require __DIR__.'/api/helpers.php';

// Bắt buộc đăng nhập
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json(['message'=>'Missing id'], 422);

$pdo = db();

// --- THAY ĐỔI QUAN TRỌNG BẮT ĐẦU TỪ ĐÂY ---

// Bước 1: Kiểm tra xem người dùng có thực sự đăng ký khóa học này không và lưu kết quả
$is_enrolled = false; // Mặc định là chưa đăng ký
if ($u['role'] === 'student') {
    $is_enrolled = is_enrolled((int)$u['id'], $id);
} else if ($u['role'] === 'teacher') {
    // Giáo viên sở hữu khóa học cũng được coi là "đã đăng ký" để xem đầy đủ
    $is_enrolled = teacher_owns_course((int)$u['id'], $id);
}


// Bước 2: Lấy thông tin khóa học (không thay đổi)
$c = $pdo->prepare('SELECT * FROM courses WHERE id=?');
$c->execute([$id]);
$course = $c->fetch();
if (!$course) json(['message'=>'Course not found'], 404);

// Bước 3: Lấy danh sách bài học tùy theo trạng thái đăng ký
if ($is_enrolled) {
    // Nếu ĐÃ ĐĂNG KÝ, lấy cả tiến độ hoàn thành
    $ls = $pdo->prepare("
        SELECT l.*, COALESCE(p.is_completed,0) AS is_completed
        FROM lessons l
        LEFT JOIN progress_tracking p ON p.lesson_id=l.id AND p.user_id=?
        WHERE l.course_id=? ORDER BY l.id ASC");
    $ls->execute([(int)$u['id'], $id]);
} else {
    // Nếu CHƯA ĐĂNG KÝ, chỉ lấy danh sách bài học, không có thông tin cá nhân
    $ls = $pdo->prepare("SELECT l.*, 0 AS is_completed FROM lessons l WHERE l.course_id=? ORDER BY l.id ASC");
    $ls->execute([$id]);
}
$lessons = $ls->fetchAll();

// Lấy danh sách bài tập (không thay đổi)
$as = $pdo->prepare("SELECT * FROM assignments WHERE course_id=? ORDER BY id DESC");
$as->execute([$id]);
$assignments = $as->fetchAll();

// Bước 4: Trả về dữ liệu JSON, kèm theo trạng thái đã đăng ký hay chưa
json([
    'course' => $course,
    'lessons' => $lessons,
    'assignments' => $assignments,
    'is_enrolled' => $is_enrolled // <-- Thêm trường này để frontend biết
]);