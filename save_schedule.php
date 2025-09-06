<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require_role(['teacher']);

$pdo = db();
$teacherId = $_SESSION['user']['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacherId) {
    $course_id   = (int)$_POST['course_id'];
    $day_of_week = (int)$_POST['day_of_week'];
    $start_time  = $_POST['start_time'];
    $end_time    = $_POST['end_time'];
    $location    = trim($_POST['location']);

    // 1. Thêm lịch giảng dạy
    $stmt = $pdo->prepare("
        INSERT INTO schedules (teacher_id, course_id, day_of_week, start_time, end_time, location)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$teacherId, $course_id, $day_of_week, $start_time, $end_time, $location]);

    // 2. Lấy thông tin giáo viên & môn học
    $teacher = $pdo->prepare("SELECT name FROM users WHERE id=?");
    $teacher->execute([$teacherId]);
    $teacherName = $teacher->fetchColumn() ?: "Giáo viên";

    $course = $pdo->prepare("SELECT title FROM courses WHERE id=?");
    $course->execute([$course_id]);
    $courseTitle = $course->fetchColumn() ?: "Môn học";

    // 3. Lấy danh sách học sinh
    $students = $pdo->query("SELECT id FROM users WHERE role='student'")->fetchAll(PDO::FETCH_COLUMN);

    // 4. Tạo thông báo cho học sinh
    $stmtNotif = $pdo->prepare("
        INSERT INTO notifications
        (user_id, title, message, type, link, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $title = "Lịch giảng dạy mới";
    $message = "Giáo viên $teacherName đã thêm lịch cho môn $courseTitle vào $start_time - $end_time.";
    $link = "/student/schedule.php"; // link tới trang lịch của học sinh

    foreach ($students as $uid) {
        $stmtNotif->execute([$uid, $title, $message, 'system', $link]);
    }

    // 5. Quay về trang giáo viên
    header("Location: giaovien.php?tab=lichgiangday");
    exit;
} else {
    echo "Yêu cầu không hợp lệ.";
}
