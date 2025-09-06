<?php
// Tự động lấy tên file hiện tại để làm nổi bật mục menu tương ứng
$current_page = basename($_SERVER['PHP_SELF']);

// Mảng chứa các mục trong menu sidebar
$sidebarNavItems = [
    ['href' => 'dashboard.php', 'icon' => 'fa-gauge-high', 'text' => 'Dashboard'],
    ['href' => 'khoahoc.php', 'icon' => 'fa-book', 'text' => 'Khóa học'],
    ['href' => 'giangvien.php', 'icon' => 'fa-chalkboard-user', 'text' => 'Giảng viên'],
    ['href' => 'hocvien.php', 'icon' => 'fa-users', 'text' => 'Học viên'],
    ['href' => 'baocao.php', 'icon' => 'fa-chart-bar', 'text' => 'Báo cáo'],
    ['href' => 'caidat.php', 'icon' => 'fa-cog', 'text' => 'Cài đặt'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="dashboard.css"> 
</head>
<body>
    <div class="admin-container"> 
        <div class="sidebar">
            <div class="sidebar-header"> 
                <h4>ADMIN PANEL</h4> 
            </div>
            <ul class="nav flex-column"> 
                <?php foreach ($sidebarNavItems as $item): ?>
                <li class="nav-item">
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-link <?= ($current_page === $item['href']) ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars($item['icon']) ?> me-2"></i> <?= htmlspecialchars($item['text']) ?>
                    </a> 
                </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        </div>
        <div class="main-content">