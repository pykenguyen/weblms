<?php
declare(strict_types=1);

// Sửa lại đường dẫn cho đúng với cấu trúc dự án
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

$error_message = '';
$success_message = '';
$token = trim($_GET['token'] ?? '');

// Ẩn form nếu token không hợp lệ ngay từ đầu
$hide_form = empty($token);
if ($hide_form) {
    $error_message = "Token không hợp lệ. Vui lòng kiểm tra lại đường link.";
}

// Xử lý khi người dùng gửi form
if (!$hide_form && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $posted_token = trim($_POST['token'] ?? '');
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // --- Kiểm tra dữ liệu (Validation) ---
    if ($posted_token === '' || $new_password === '' || $confirm_password === '') {
        $error_message = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } else {
        // --- Nếu dữ liệu hợp lệ, xử lý với CSDL ---
        $pdo = db();
        
        // Kiểm tra token hợp lệ và thời gian hết hạn
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$posted_token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error_message = 'Liên kết không hợp lệ hoặc đã hết hạn.';
        } else {
            // Hash mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update mật khẩu và xóa token
            $stmt_update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if ($stmt_update->execute([$hashed_password, $user['id']])) {
                $success_message = 'Mật khẩu đã được đặt lại thành công! Bạn sẽ được chuyển đến trang đăng nhập sau 3 giây.';
                $hide_form = true; // Ẩn form đi sau khi thành công
                header("refresh:3;url=login.php");
            } else {
                $error_message = 'Đã có lỗi xảy ra, không thể cập nhật mật khẩu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="auth.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg w-full max-w-md auth-container">
        <h2 class="text-2xl font-bold mb-4">Đặt lại mật khẩu</h2>

        <div class="mb-4 text-center text-sm font-semibold">
            <?php if (!empty($error_message)): ?>
                <p class="text-red-600"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <p class="text-green-600"><?php echo $success_message; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!$hide_form): ?>
        <form id="resetPasswordForm" method="POST" action="new_password.php?token=<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
                <input type="password" id="password" name="password" required class="mt-1 w-full border px-4 py-2 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 w-full border px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700">
                Đặt lại mật khẩu
            </button>
        </form>
        <?php else: ?>
            <div class="text-center mt-6">
                <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Đi đến trang Đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>
    </body>
</html>