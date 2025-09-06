<?php
// Bắt đầu session và nạp các tệp cần thiết
// Giả định các tệp helpers đã bao gồm session_start()
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Bắt buộc người dùng phải đăng nhập để truy cập hoặc sử dụng chức năng này
$user = require_login();

// XỬ LÝ YÊU CẦU POST (API LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ request body
    $data = body();
    $old_pass = $data['old_pass'] ?? '';
    $new_pass = $data['new_pass'] ?? '';

    // Kiểm tra mật khẩu mới có đủ điều kiện không
    if (strlen($new_pass) < 8) {
        json(['message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'], 422);
    }

    $pdo = db();

    // Lấy mật khẩu hiện tại của người dùng từ database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([(int)$user['id']]);
    $db_user = $stmt->fetch();

    // Xác minh mật khẩu cũ
    if (!$db_user || !password_verify($old_pass, $db_user['password'])) {
        json(['message' => 'Mật khẩu cũ không đúng.'], 401);
    }

    // Hash mật khẩu mới và cập nhật vào database
    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$hashed_pass, (int)$user['id']]);

    json(['message' => 'Đổi mật khẩu thành công!']);
    
    // Dừng thực thi sau khi gửi phản hồi JSON
    exit();
}

// NẾU KHÔNG PHẢI LÀ POST, HIỂN THỊ TRANG HTML BÊN DƯỚI
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="auth.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg w-full max-w-md auth-container">
        <h2 class="text-2xl font-bold mb-4">Đổi mật khẩu</h2>
        <div id="message" class="mb-4 text-center text-sm font-semibold"></div>

        <form id="changePasswordForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Mật khẩu cũ</label>
                <input type="password" id="old_pass" name="old_pass" required class="mt-1 w-full border px-4 py-2 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
                <input type="password" id="new_pass" name="new_pass" required class="mt-1 w-full border px-4 py-2 rounded-lg">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700">
                Đổi mật khẩu
            </button>
        </form>
    </div>

    <script>
        document.getElementById('changePasswordForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const old_pass = document.getElementById('old_pass').value;
            const new_pass = document.getElementById('new_pass').value;
            const messageDiv = document.getElementById('message');

            messageDiv.textContent = 'Đang xử lý...';
            messageDiv.className = 'mb-4 text-center text-sm font-semibold text-blue-600';

            try {
                // Form sẽ POST đến chính nó, và logic PHP ở trên sẽ xử lý
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ old_pass, new_pass })
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.textContent = data.message;
                    messageDiv.className = 'mb-4 text-center text-sm font-semibold text-green-600';
                    // Xóa form sau khi đổi mật khẩu thành công
                    document.getElementById('changePasswordForm').reset();
                } else {
                    messageDiv.textContent = data.message || 'Có lỗi xảy ra.';
                    messageDiv.className = 'mb-4 text-center text-sm font-semibold text-red-600';
                }
            } catch (error) {
                messageDiv.textContent = 'Lỗi kết nối. Vui lòng thử lại.';
                messageDiv.className = 'mb-4 text-center text-sm font-semibold text-red-600';
            }
        });
    </script>
</body>
</html>