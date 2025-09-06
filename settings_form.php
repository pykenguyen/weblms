<?php
// Lấy thông tin giáo viên từ database
$stmtProfile = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmtProfile->execute([$teacherId]);
$profile = $stmtProfile->fetch(PDO::FETCH_ASSOC);
// Lấy theme hiện tại
$stmtTheme = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'theme' LIMIT 1");
$stmtTheme->execute();
$currentTheme = $stmtTheme->fetchColumn() ?: 'light';

?>

<form method="POST" action="save_settings.php" enctype="multipart/form-data" class="mt-3">
    <div class="mb-3">
        <label class="form-label">Ảnh đại diện</label><br>
        <input type="file" name="avatar" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Tên hiển thị</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Mật khẩu mới</label>
        <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
    </div>

    <div class="mb-3">
    <label class="form-label">Chế độ hiển thị</label>
    <select name="theme" class="form-select">
        <option value="light" <?= $currentTheme === 'light' ? 'selected' : '' ?>>Sáng</option>
        <option value="dark"  <?= $currentTheme === 'dark'  ? 'selected' : '' ?>>Tối</option>
    </select>
    </div>
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
</form>
