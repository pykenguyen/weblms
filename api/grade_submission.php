<?php
declare(strict_types=1);
session_start();
require __DIR__.'/config.php';
require __DIR__.'/helpers.php';
require_role(['teacher']);

$pdo = db();
$teacherId = $_SESSION['user']['id'] ?? 0;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

    $sql = "SELECT s.id AS submission_id, u.name AS student_name, a.title AS assignment_title, c.title AS course_title, s.grade, s.feedback, s.submitted_at
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN courses c ON a.course_id = c.id
            JOIN users u ON s.student_id = u.id
            WHERE c.teacher_id = ?";
    $params = [$teacherId];

    if ($course_id > 0) {
        $sql .= " AND c.id = ?";
        $params[] = $course_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['items'=>$items]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $submission_id = $data['submission_id'] ?? 0;
    $grade = $data['grade'] ?? null;
    $feedback = $data['feedback'] ?? '';

    if(!$submission_id || $grade === null){
        echo json_encode(['success'=>false,'message'=>'Dữ liệu không hợp lệ']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE submissions s
                           JOIN assignments a ON s.assignment_id = a.id
                           JOIN courses c ON a.course_id = c.id
                           SET s.grade=?, s.feedback=?
                           WHERE s.id=? AND c.teacher_id=?");
    $stmt->execute([$grade,$feedback,$submission_id,$teacherId]);
    echo json_encode(['success'=>true,'message'=>'Cập nhật điểm thành công']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Phương thức không hợp lệ']);
