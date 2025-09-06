<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

session_start();
require __DIR__.'/config.php';
require __DIR__.'/helpers.php';

// Bắt buộc là giáo viên
$teacher = require_role(['teacher']);
$teacherId = $teacher['id'];

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.course_id,
            s.day_of_week,
            s.start_time,
            s.end_time,
            s.location,
            c.title AS course_title
        FROM schedules s
        JOIN courses c ON s.course_id = c.id
        WHERE s.teacher_id = ?
        ORDER BY s.day_of_week, s.start_time
    ");
    $stmt->execute([$teacherId]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['items' => $schedule]);
} catch (PDOException $e) {
    echo json_encode(['items' => [], 'message' => 'Lỗi DB: '.$e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['items' => [], 'message' => 'Lỗi hệ thống: '.$e->getMessage()]);
}
