<?php
declare(strict_types=1);

/* Bảo đảm session sẵn sàng */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/* Trả JSON + status code rồi thoát */
function json($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* Chuyển hướng trình duyệt */
function redirect(string $url, int $code = 302): void {
    header("Location: {$url}", true, $code);
    exit();
}

/* Đọc body JSON (fallback về $_POST nếu không phải JSON) */
function body(): array {
    $raw = file_get_contents('php://input') ?: '';
    $d = json_decode($raw, true);
    return is_array($d) ? $d : ($_POST ?? []);
}

/* User hiện tại (nếu có) */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/* Bắt buộc đăng nhập: trả về user */
function require_login(): array {
    $u = current_user();
    if (!$u) json(['message' => 'Unauthorized'], 401);
    return $u;
}

/**
 * Bắt buộc có 1 trong các role cho phép.
 * Dùng được cả 2 kiểu:
 * require_role('teacher','admin');
 * require_role(['teacher','admin']);
 * Trả về user nếu hợp lệ.
 */
function require_role(...$roles): array {
    $u = require_login();

    // Cho phép truyền mảng
    if (count($roles) === 1 && is_array($roles[0])) {
        $roles = $roles[0];
    }

    // Không truyền thì mặc định cần 'admin'
    if (empty($roles)) {
        $roles = ['admin'];
    }

    if (!in_array($u['role'] ?? '', $roles, true)) {
        json(['message' => 'Forbidden'], 403);
    }
    return $u;
}

/* Tạo thư mục nếu chưa có */
function ensure_dir(string $path): void {
    if (!is_dir($path)) @mkdir($path, 0755, true);
}

/* Làm sạch tên file cơ bản */
function sanitize_filename(string $name): string {
    $name = preg_replace('~[^\w\-.]+~u', '_', $name) ?? 'file';
    return trim($name, '._');
}

/**
 * Lưu file upload an toàn.
 * - $field: tên input file
 * - $subdir: thư mục con trong /uploads (vd: 'lessons', 'assignments')
 * - $allowedExt: các đuôi cho phép (mặc định phổ biến)
 * - $maxMB: giới hạn dung lượng (MB)
 * Trả về path tương đối để ghi DB: 'uploads/<subdir>/<file>'
 */
function save_upload(
    string $field,
    string $subdir,
    array $allowedExt = ['pdf','doc','docx','zip','jpg','jpeg','png','mp4'],
    int $maxMB = 50
): string {
    if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        json(['message' => "Missing file '$field'"], 422);
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        json(['message' => 'Upload error: ' . $f['error']], 422);
    }

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        json(['message' => 'File type not allowed (.' . $ext . ')'], 415);
    }

    $maxBytes = $maxMB * 1024 * 1024;
    if (($f['size'] ?? 0) > $maxBytes) {
        json(['message' => 'File too large'], 413);
    }

    // Thư mục uploads nằm ở gốc dự án (cha của /api)
    $rootUpload = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads';
    ensure_dir($rootUpload);
    $destDir = $rootUpload . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
    ensure_dir($destDir);

    $base = sanitize_filename(pathinfo($f['name'], PATHINFO_FILENAME));
    $name = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = $destDir . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        json(['message' => 'Cannot move uploaded file'], 500);
    }

    return 'uploads/' . trim($subdir, '/\\') . '/' . $name;
}

/* Kiểm tra SV đã enroll khóa chưa */
function is_enrolled(int $userId, int $courseId): bool {
    $q = db()->prepare('SELECT COUNT(*) FROM course_user WHERE user_id=? AND course_id=?');
    $q->execute([$userId, $courseId]);
    return (bool)$q->fetchColumn();
}

/* GV có sở hữu khóa không */
function teacher_owns_course(int $teacherId, int $courseId): bool {
    $q = db()->prepare('SELECT COUNT(*) FROM courses WHERE id=? AND teacher_id=?');
    $q->execute([$courseId, $teacherId]);
    return (bool)$q->fetchColumn();
}
function notify_users(array $user_ids, string $title, string $message, ?string $link = null, ?string $type = 'system', ?string $expires_at = null) {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, link, type, created_at, expires_at)
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");

    foreach($user_ids as $uid){
        $stmt->execute([$uid, $title, $message, $link, $type, $expires_at]);
    }
}
function get_course_title(int $courseId): string {
    $stmt = db()->prepare("SELECT title FROM courses WHERE id=?");
    $stmt->execute([$courseId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['title'] ?? 'Không xác định';
}
