<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

try {
    // Yêu cầu người dùng phải đăng nhập
    $user = require_login();
    $pdo = db();

    // 1. Lấy và kiểm tra ID bài học từ URL
    $lessonId = (int)($_GET['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        throw new Exception('ID bài học không hợp lệ.', 422);
    }

    // 2. Lấy thông tin bài học từ database
    $stmt = $pdo->prepare('SELECT course_id, file_path, content_type FROM lessons WHERE id=?');
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        throw new Exception('Không tìm thấy bài học.', 404);
    }

    // 3. Kiểm tra quyền truy cập của người dùng
    if (($user['role'] ?? '') === 'student') {
        $checkStmt = $pdo->prepare('SELECT 1 FROM course_user WHERE user_id=? AND course_id=?');
        $checkStmt->execute([(int)$user['id'], (int)$lesson['course_id']]);
        if (!$checkStmt->fetchColumn()) {
            throw new Exception('Bạn không có quyền truy cập tài liệu này.', 403);
        }
    }

    // 4. Lấy thông tin file và loại nội dung
    $filePathFromDB = $lesson['file_path'];
    $contentType = strtolower((string)($lesson['content_type'] ?? ''));

    if (empty($filePathFromDB)) {
        throw new Exception('Bài học này không có tệp đính kèm.', 404);
    }

    // 5. Chỉ cho phép tải các loại file được định nghĩa
    $allowedFileTypes = ['pdf', 'doc', 'docx', 'zip', 'rar']; // Có thể mở rộng thêm
    if (!in_array($contentType, $allowedFileTypes, true)) {
        throw new Exception('Loại nội dung này không phải tệp để tải về.', 415);
    }
    
    // 6. Xây dựng và xác thực đường dẫn tệp (Cách làm an toàn và chính xác hơn)
    // $_SERVER['DOCUMENT_ROOT'] sẽ lấy thư mục gốc của web server, ví dụ: "C:/xampp/htdocs"
    // str_replace để thay thế dấu gạch chéo cho đồng nhất
    $projectRoot = str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']);
    
    // Tách phần tên dự án ra khỏi đường dẫn (ví dụ: /test1)
    $projectName = '';
    // Lấy đường dẫn của file config.php để suy ra thư mục dự án
    $scriptPath = str_replace($projectRoot, '', __DIR__);
    $pathParts = explode(DIRECTORY_SEPARATOR, $scriptPath);
    if(isset($pathParts[1])) {
        $projectName = DIRECTORY_SEPARATOR . $pathParts[1];
    }

    // Tạo đường dẫn tuyệt đối đến file
    $absolutePath = $projectRoot . $projectName . DIRECTORY_SEPARATOR . $filePathFromDB;
    $absolutePath = realpath($absolutePath); // Chuẩn hóa đường dẫn

    // Kiểm tra file tồn tại
    if (!$absolutePath || !is_file($absolutePath)) {
        throw new Exception('File không tồn tại trên máy chủ.', 404);
    }
    
    // 7. Gửi file đến trình duyệt
    $filename = basename($absolutePath);
    $mime = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
    ][$contentType] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($absolutePath));
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Pragma: public');
    header('Cache-Control: must-revalidate');
    
    ob_clean();
    flush();
    readfile($absolutePath);
    exit;

} catch (Exception $e) {
    // Xử lý và hiển thị lỗi nếu có
    $code = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 400;
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>