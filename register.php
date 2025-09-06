<?php
declare(strict_types=1);

// Sử dụng các file cấu hình và trợ giúp chung
require __DIR__.'/api/config.php';
require __DIR__.'/api/helpers.php';

$error_message = '';
$success_message = '';

// Xử lý khi người dùng gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Kiểm tra dữ liệu (Validation) ---
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Địa chỉ email không hợp lệ.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } else {
        $pdo = db();
        
        // Kiểm tra xem email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Địa chỉ email này đã được sử dụng.';
        } else {
            // --- Nếu tất cả hợp lệ, thêm người dùng mới ---
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hashed_password]);

                $success_message = 'Đăng ký thành công! Bạn sẽ được chuyển đến trang đăng nhập sau giây lát...';
                // Thêm header để tự động chuyển hướng sau 3 giây
                header("refresh:3;url=login.php");

            } catch (PDOException $e) {
                $error_message = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12">
    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <a href="index.php" class="text-3xl font-bold text-indigo-600">MyLMS</a>
            <h2 class="text-2xl font-bold text-gray-800 mt-4">Tạo Tài Khoản Miễn Phí</h2>
            <p class="text-gray-500">Bắt đầu hành trình tri thức của bạn ngay hôm nay.</p>
        </div>
        
        <form id="registerForm" method="POST" action="register.php">
            <div class="mb-4 text-center text-sm font-semibold">
                <?php if (!empty($error_message)): ?>
                    <p class="text-red-600"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <p class="text-green-600"><?php echo $success_message; ?></p>
                <?php endif; ?>
            </div>
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Họ và Tên</label>
                    <input type="text" id="name" name="name" required class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                    <input type="password" id="password" name="password" required class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300">Tạo Tài Khoản</button>
                </div>
            </div>
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    Đã có tài khoản? <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Đăng nhập ngay</a>
                </p>
            </div>
        </form>
    </div>
</body>
</html>