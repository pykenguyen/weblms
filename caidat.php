<?php
session_start();
// Nạp các tệp cấu hình và hàm hỗ trợ của bạn
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require __DIR__ . '/admin_header.php'; 

// Bắt buộc người dùng phải đăng nhập với vai trò admin
$user = require_role('admin');
$pdo = db();

// ---- XỬ LÝ KHI NGƯỜI DÙNG LƯU CÀI ĐẶT (REQUEST POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Lấy dữ liệu JSON từ request body
        $data = body();

        // Chuẩn bị câu lệnh SQL để chèn hoặc cập nhật (UPSERT)
        // Giúp mã ngắn gọn và hiệu quả, không cần kiểm tra tồn tại trước
        $stmt = $pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES (:key, :value) 
             ON DUPLICATE KEY UPDATE setting_value = :value"
        );

        // Bắt đầu một transaction để đảm bảo tất cả cài đặt được lưu hoặc không có gì cả
        $pdo->beginTransaction();

        // Lặp qua dữ liệu nhận được và lưu vào CSDL
        foreach ($data as $key => $value) {
            // Chỉ cho phép lưu các key đã được định nghĩa để bảo mật
            if (in_array($key, ['site_name', 'contact_email', 'items_per_page'])) {
                $stmt->execute(['key' => $key, 'value' => trim($value)]);
            }
        }
        
        // Hoàn tất transaction
        $pdo->commit();

        // Trả về thông báo thành công
        json(['success' => true, 'message' => 'Cài đặt đã được lưu thành công!']);

    } catch (Exception $e) {
        // Nếu có lỗi, rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Trả về thông báo lỗi
        json(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
    }
    
    // Dừng thực thi sau khi xử lý POST
    exit();
}

// ---- HIỂN THỊ TRANG (REQUEST GET) ----

// Lấy tất cả cài đặt từ CSDL
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
// Dùng FETCH_KEY_PAIR để có mảng dạng ['setting_key' => 'setting_value']
$db_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Gán giá trị vào biến với giá trị mặc định nếu chưa có trong CSDL
$site_name = htmlspecialchars($db_settings['site_name'] ?? 'MyLMS');
$contact_email = htmlspecialchars($db_settings['contact_email'] ?? '');
$items_per_page = htmlspecialchars($db_settings['items_per_page'] ?? '10');

// Dữ liệu động cho sidebar
$sidebar_nav_items = [
    ['href' => 'dashboard.php', 'icon' => 'fa-gauge-high', 'text' => 'Dashboard'],
    ['href' => 'khoahoc.php', 'icon' => 'fa-book', 'text' => 'Khóa học'],
    ['href' => 'hocvien.php', 'icon' => 'fa-users', 'text' => 'Học viên'],
    ['href' => 'giangvien.php', 'icon' => 'fa-chalkboard-user', 'text' => 'Giảng viên'],
    ['href' => 'baocao.php', 'icon' => 'fa-chart-bar', 'text' => 'Báo cáo'],
    ['href' => 'caidat.php', 'icon' => 'fa-cog', 'text' => 'Cài đặt', 'active' => true],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin/caidat.css">
</head>
<body>
 
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-cog me-2"></i> Cài đặt hệ thống</h2>
            </div>
            <div id="alert-placeholder"></div>
            <div class="content-section">
                <h5 class="section-title"><i class="fas fa-cogs me-2"></i> Cài đặt chung</h5>
                <form id="settingsForm">
                    <div class="mb-3">
                        <label for="siteName" class="form-label">Tên trang web</label>
                        <input type="text" class="form-control" id="siteName" value="<?php echo $site_name; ?>" placeholder="Tên trang web của bạn">
                    </div>
                    <div class="mb-3">
                        <label for="contactEmail" class="form-label">Email liên hệ</label>
                        <input type="email" class="form-control" id="contactEmail" value="<?php echo $contact_email; ?>" placeholder="Email hỗ trợ, liên hệ">
                    </div>
                    <div class="mb-3">
                        <label for="itemsPerPage" class="form-label">Số mục mỗi trang</label>
                        <input type="number" class="form-control" id="itemsPerPage" value="<?php echo $items_per_page; ?>" placeholder="Số lượng học viên/khóa học tối đa mỗi trang">
                    </div>
                    <button type="button" id="saveSettingsBtn" class="btn btn-primary"><i class="fas fa-save me-1"></i> Lưu cài đặt</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const saveBtn = document.getElementById('saveSettingsBtn');
            const alertPlaceholder = document.getElementById('alert-placeholder');

            const showAlert = (message, type = 'success') => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = [
                    `<div class="alert alert-${type} alert-dismissible" role="alert">`,
                    `   <div>${message}</div>`,
                    '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
                    '</div>'
                ].join('');
                alertPlaceholder.append(wrapper);
                 // Tự động ẩn sau 5 giây
                setTimeout(() => {
                    wrapper.remove();
                }, 5000);
            };
            
            saveBtn.addEventListener('click', async function() {
                // Hiển thị trạng thái đang lưu trên nút
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...';

                const settingsData = {
                    site_name: document.getElementById('siteName').value,
                    contact_email: document.getElementById('contactEmail').value,
                    items_per_page: document.getElementById('itemsPerPage').value
                };

                try {
                    // Form sẽ POST đến chính nó, logic PHP ở trên sẽ xử lý
                    const response = await fetch('caidat.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(settingsData)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        showAlert('Cài đặt đã được lưu thành công!', 'success');
                    } else {
                        showAlert('Lỗi khi lưu cài đặt: ' + (result.message || 'Lỗi không xác định.'), 'danger');
                    }
                } catch (error) {
                    console.error('Lỗi khi gửi dữ liệu:', error);
                    showAlert('Có lỗi xảy ra, không thể kết nối đến máy chủ.', 'danger');
                } finally {
                    // Trả lại trạng thái ban đầu cho nút
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu cài đặt';
                }
            });

            // Không cần hàm loadSettings nữa vì dữ liệu đã được PHP điền sẵn.
        });
    </script>
</body>
</html>