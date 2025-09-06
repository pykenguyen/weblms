<?php
session_start();
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Chỉ cho phép admin truy cập trang này
require_role(['admin']);

// Lấy ID người dùng từ URL, đảm bảo nó là số nguyên
$id = (int)$_GET['id'];

$pdo = db();

// Xử lý yêu cầu POST để cập nhật vai trò người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    
    // Sử dụng prepared statement để ngăn chặn SQL Injection
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $id]);
    
    // Chuyển hướng trở lại trang quản lý người dùng sau khi cập nhật
    header("Location: admin_users.php");
    exit();
}

// Lấy thông tin người dùng để hiển thị trên form
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

// Nếu không tìm thấy người dùng, hiển thị lỗi và thoát
if (!$user) {
    die("Không tìm thấy người dùng.");
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Sửa người dùng</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 p-8">
    <div class="container mx-auto max-w-lg bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Sửa Người Dùng: <?= htmlspecialchars($user['name']) ?></h2>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Username:</label>
                <p class="mt-1 text-lg"><?= htmlspecialchars($user['name']) ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email:</label>
                <p class="mt-1 text-lg"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Vai trò:</label>
                <select name="role" id="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Học viên</option>
                    <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-indigo-700 transition">Cập nhật</button>
        </form>
        <a href="admin_users.php" class="block mt-4 text-center text-indigo-600 hover:underline">← Quay lại</a>
    </div>
</body>
</html>