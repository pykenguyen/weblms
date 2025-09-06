<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

try {
    header('Content-Type: application/json');
    require_role(['admin']);
    $pdo = db();
    
    $course_id = (int)($_GET['id'] ?? 0);
    
    if ($course_id <= 0) {
        throw new Exception('ID khóa học không hợp lệ.');
    }

    // Lấy thông tin giảng viên của khóa học
    $stmt_teacher = $pdo->prepare("
        SELECT u.name as instructor_name 
        FROM courses c
        JOIN users u ON c.teacher_id = u.id
        WHERE c.id = ?
    ");
    $stmt_teacher->execute([$course_id]);
    $teacher = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

    // Lấy danh sách học viên đã đăng ký khóa học
    $stmt_students = $pdo->prepare("
        SELECT u.id, u.name, u.email 
        FROM course_user cu
        JOIN users u ON cu.user_id = u.id
        WHERE cu.course_id = ? AND u.role = 'student'
        ORDER BY u.name ASC
    ");
    $stmt_students->execute([$course_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    if (!$teacher) {
        throw new Exception('Không tìm thấy khóa học.');
    }
    
    // Gộp dữ liệu và trả về
    $response_data = [
        'instructor_name' => $teacher['instructor_name'],
        'students' => $students
    ];
    
    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit();
?>