<?php
session_start();
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Kiểm tra quyền giáo viên
require_role(['teacher']);
$pdo = db();

$teacherId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $theme    = $_POST['theme'] ?? 'light';

    // ---- 1. Lưu theme vào bảng settings ----
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'theme'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if ($exists) {
        // Update theme
        $stmt = $pdo->prepare("UPDATE settings 
                               SET setting_value = ?, updated_at = NOW() 
                               WHERE setting_key = 'theme'");
        $stmt->execute([$theme]);
    } else {
        // Insert nếu chưa có
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) 
                               VALUES ('theme', ?, NOW(), NOW())");
        $stmt->execute([$theme]);
    }

    // ---- 2. Upload avatar (nếu có) ----
    $avatarPath = null;
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = __DIR__ . "/uploads/avatars/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = "teacher_" . $teacherId . "_" . time() . "_" . basename($_FILES["avatar"]["name"]);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
            $avatarPath = "uploads/avatars/" . $fileName;
        }
    }

    // ---- 3. Update thông tin giáo viên (không dính theme) ----
    $sql = "UPDATE users SET name = ?, email = ?";
    $params = [$fullname, $email];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_BCRYPT);
    }

    if ($avatarPath) {
        $sql .= ", avatar = ?";
        $params[] = $avatarPath;
    }

    $sql .= " WHERE id = ?";
    $params[] = $teacherId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ---- 4. Redirect ----
    header("Location: Giaovien.php?success=1");
    exit;
} else {
    header("Location: Giaovien.php?error=1");
    exit;
}
