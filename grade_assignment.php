<?php
declare(strict_types=1);
require __DIR__.'/helpers.php';
require __DIR__.'/config.php';

$teacher = require_role('teacher');
$teacherId = $teacher['id'];

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$submission_id = (int)($data['submission_id'] ?? 0);
$score         = isset($data['grade']) ? floatval($data['grade']) : null;
$feedback      = trim($data['feedback'] ?? '');

if (!$submission_id || $score === null) {
    json(['message'=>'Thiếu dữ liệu'], 422);
}

$pdo = db();

// --- Lấy thông tin submission + assignment ---
$stmt = $pdo->prepare("
    SELECT s.student_id, s.assignment_id, a.course_id, a.title AS assignment_title, c.teacher_id
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub || (int)$sub['teacher_id'] !== $teacherId) {
    json(['message'=>'Bạn không có quyền chấm bài này'], 403);
}

// --- Cập nhật grade + feedback ---
$stmt_update = $pdo->prepare("
    UPDATE submissions
    SET grade = ?, feedback = ?, graded_at = NOW()
    WHERE id = ?
");
$stmt_update->execute([$score, $feedback, $submission_id]);

// --- Gửi thông báo cho sinh viên ---
$title = "Bài tập '{$sub['assignment_title']}' đã được chấm";
$message = "Giáo viên {$teacher['name']} đã chấm: $score điểm";
$link = "submission-detail.php?id=$submission_id";
$type = "grade";

notify_users([$sub['student_id']], $title, $message, $link, $type);

json(['success'=>true, 'message'=>'Đã chấm điểm và gửi thông báo']);
