<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

require_role(['teacher']);

$pdo = db();
$teacherId = $_SESSION['user']['id'] ?? null;
if (!$teacherId) json(['items'=>[]]);

// Xử lý GET: lấy danh sách submissions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $course_id = (int)($_GET['course_id'] ?? 0);

    // Nếu course_id > 0, kiểm tra giáo viên có quyền
    if ($course_id > 0 && !teacher_owns_course($teacherId, $course_id)) {
        json(['items'=>[], 'message'=>'Forbidden']);
    }

    $sql = "
        SELECT s.id AS submission_id, u.id AS student_id, u.name AS student_name,
               a.id AS assignment_id, a.title AS assignment_title,
               c.id AS course_id, c.title AS course_title,
               s.grade, s.feedback, s.submitted_at
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
    ";
    $params = [$teacherId];

    if ($course_id > 0) {
        $sql .= " AND c.id = ?";
        $params[] = $course_id;
    }

    $sql .= " ORDER BY s.submitted_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json(['items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Xử lý POST: chấm điểm và tạo thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = body();
    $submission_id = (int)($data['submission_id'] ?? 0);
    $grade = isset($data['grade']) ? (float)$data['grade'] : null;
    $feedback = $data['feedback'] ?? null;

    if ($submission_id <= 0) json(['message'=>'Missing submission_id'], 422);

    $stmt = $pdo->prepare("
        SELECT s.student_id, a.course_id, a.title
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE s.id=?
    ");
    $stmt->execute([$submission_id]);
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meta) json(['message'=>'Submission not found'], 404);

    if (!teacher_owns_course($teacherId, (int)$meta['course_id'])) {
        json(['message'=>'Forbidden'], 403);
    }

    // Cập nhật điểm & nhận xét
    $upd = $pdo->prepare("UPDATE submissions SET grade=?, feedback=? WHERE id=?");
    $upd->execute([$grade, $feedback, $submission_id]);

    // Tạo thông báo đầy đủ
    $nt = $pdo->prepare("
        INSERT INTO notifications
        (user_id, title, message, is_read, created_at, expires_at, type, link)
        VALUES (?, ?, ?, 0, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?)
    ");
    $nt->execute([
        (int)$meta['student_id'],
        'Bài tập đã chấm',
        'Bạn đã được chấm bài: '.$meta['title'].($grade!==null ? ' ('.$grade.')':''), 
        'grade',
        '/student/assignments.php?id='.$meta['course_id']
    ]);

    json(['message'=>'Đã lưu']);
}

// Nếu không phải GET hoặc POST
json(['message'=>'Method Not Allowed'], 405);
