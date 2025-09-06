<?php
// Bắt đầu xử lý logic PHP trước khi render HTML
$message = '';
$message_type = ''; // 'success' hoặc 'error'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    // --- SỬA LỖI QUAN TRỌNG ---
    // 1. Sửa lại tên CSDL cho đúng
    // 2. Sửa lại thông tin kết nối cho đúng với các file khác của bạn
    $db_host = '127.0.0.1:3307';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'lcms_db'; // Sửa từ 'tendatabase' thành 'lcms_db'
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Nếu email tồn tại, tạo token và thời gian hết hạn
        $token = bin2hex(random_bytes(32)); // Tăng độ dài token để an toàn hơn
        $expire = date("Y-m-d H:i:s", time() + 60 * 30); // Token hết hạn sau 30 phút

        $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $token, $expire, $email);
        $update_stmt->execute();
        
        // --- CẢI TIẾN BẢO MẬT & TRẢI NGHIỆM NGƯỜI DÙNG ---
        // THAY VÌ HIỂN THỊ LINK TRỰC TIẾP, HÃY GỬI EMAIL
        // Dưới đây là thông báo cho người dùng. Phần gửi email thực tế sẽ cần một thư viện như PHPMailer.
        $message = "Yêu cầu đã được gửi. Nếu email của bạn tồn tại trong hệ thống, bạn sẽ nhận được một liên kết để đặt lại mật khẩu trong ít phút.";
        $message_type = 'success';

        /*
        // --- VÍ DỤ VỀ CÁCH GỬI EMAIL (bạn sẽ cần cài đặt thư viện PHPMailer) ---
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        // Cấu hình SMTP...
        $mail->setFrom('no-reply@mylms.com', 'MyLMS');
        $mail->addAddress($email);
        $mail->Subject = 'Yeu cau dat lai mat khau cho MyLMS';
        $reset_link = "http://localhost/test/new_password.php?token=$token";
        $mail->Body = "Vui lòng nhấn vào link sau để đặt lại mật khẩu: <a href='$reset_link'>$reset_link</a>. Link sẽ hết hạn sau 30 phút.";
        $mail->send();
        */

    } else {
        // Để tăng bảo mật, không nên cho biết email có tồn tại hay không.
        // Luôn hiển thị cùng một thông báo thành công.
        $message = "Yêu cầu đã được gửi. Nếu email của bạn tồn tại trong hệ thống, bạn sẽ nhận được một liên kết để đặt lại mật khẩu trong ít phút.";
        $message_type = 'success';
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100">

    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
            
            <div class="text-center">
                <a href="index.php" class="text-3xl font-bold text-indigo-600">MyLMS</a>
                <h2 class="mt-4 text-2xl font-bold text-gray-900">
                    Đặt Lại Mật Khẩu
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Nhập email của bạn để nhận liên kết khôi phục.
                </p>
            </div>

            <form class="space-y-6" method="POST" action="reset_password.php">
                <?php if (!empty($message)): ?>
                    <div class="p-4 rounded-md <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Địa chỉ email</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Gửi liên kết khôi phục
                    </button>
                </div>
            </form>
            
            <div class="text-center">
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Quay lại trang đăng nhập
                </a>
            </div>
        </div>
    </div>
    <script>
      feather.replace()
    </script>
</body>
</html>