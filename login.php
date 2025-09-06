<?php
declare(strict_types=1);

// Sửa lại đường dẫn cho đúng
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$error_message = '';

// Xử lý khi người dùng gửi form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = 'Thiếu email hoặc mật khẩu';
    } else {
        $pdo = db();
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error_message = 'Email hoặc mật khẩu không đúng';
        } else {
            // Đăng nhập thành công, lưu thông tin vào session
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            
            // Logic chuyển hướng dựa trên vai trò
            $redirect_url = 'trang-chu.php';
            if ($user['role'] === 'admin') {
                $redirect_url = 'hocvien.php'; // Admin vào trang quản lý học viên
            } elseif ($user['role'] === 'teacher') {
                $redirect_url = 'Giaovien.php'; // Teacher vào trang quản lý giáo viên
            }
            
            redirect($redirect_url);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <a href="index.php" class="text-3xl font-bold text-indigo-600">MyLMS</a>
            <h2 class="text-2xl font-bold text-gray-800 mt-4">Đăng Nhập Tài Khoản</h2>
            <p class="text-gray-500">Chào mừng bạn đã quay trở lại!</p>
        </div>
        
        <form id="loginForm" method="POST" action="login.php">
            <?php if (!empty($error_message)): ?>
                <div class="mb-4 text-center text-sm font-semibold text-red-600">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="teacher@example.com">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                    <input type="password" id="password" name="password" required class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="password">
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300">Đăng Nhập</button>
                </div>
            </div>
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    Chưa có tài khoản? <a href="register.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Đăng ký tại đây</a>
                </p>
                <p class="text-sm text-gray-600">
                    Quên mật khẩu? <a href="reset_password.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Reset mật khẩu</a>
                </p>
            </div>
        </form>
    </div>
</body>
</html>
