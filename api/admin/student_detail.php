<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

try {
    header('Content-Type: application/json');
    require_role(['admin']);
    $pdo = db();
    
    $student_id = (int)($_GET['id'] ?? 0);
    
    if ($student_id <= 0) {
        throw new Exception('ID học viên không hợp lệ.');
    }

    // Lấy thông tin cơ bản của học viên
    $stmt_student = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ? AND role = 'student'");
    $stmt_student->execute([$student_id]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('Không tìm thấy học viên.');
    }

    // Lấy danh sách các khóa học mà học viên đã đăng ký
    $stmt_courses = $pdo->prepare("
        SELECT c.title, c.thumbnail, u.name as instructor
        FROM course_user cu
        JOIN courses c ON cu.course_id = c.id
        JOIN users u ON c.teacher_id = u.id
        WHERE cu.user_id = ?
    ");
    $stmt_courses->execute([$student_id]);
    $enrolled_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

    // Gộp tất cả dữ liệu lại và trả về
    $response_data = $student;
    $response_data['enrolled_courses'] = $enrolled_courses;

    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit();
?>