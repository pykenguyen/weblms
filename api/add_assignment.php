<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$pdo = db();

$title = $_POST['title'] ?? '';
$course_id = (int)($_POST['course_id'] ?? 0);
$description = $_POST['description'] ?? '';
$due_date = $_POST['due_date'] ?? '';

if (!$title || !$course_id || !$due_date) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

$due_date = date('Y-m-d H:i:s', strtotime($due_date));

$filePath = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/assignments/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = time() . '_' . basename($_FILES['file']['name']);
    $fullPath = $uploadDir . $filename; 
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $fullPath)) {
        // SỬA LỖI CÚ PHÁP Ở ĐÂY
        $filePath = 'uploads/assignments/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi di chuyển tệp tin.']);
        exit;
    }
}

$stmt = $pdo->prepare("INSERT INTO assignments (title, course_id, description, due_date, file_path) VALUES (?, ?, ?, ?, ?)");
$success = $stmt->execute([$title, $course_id, $description, $due_date, $filePath]);

if ($success) {
    $assignment_id = $pdo->lastInsertId();

    $stmt_users = $pdo->prepare("SELECT user_id FROM course_user WHERE course_id=?");
    $stmt_users->execute([$course_id]);
    $user_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

    notify_users(
        $user_ids,
        "Bài tập mới: $title",
        "Khóa học: " . get_course_title($course_id) . ". Hạn nộp: $due_date",
        "assignment-detail.php?id=$assignment_id",
        'course'
    );

    echo json_encode(['success' => true, 'message' => 'Thêm bài tập thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm bài tập vào cơ sở dữ liệu.']);
}