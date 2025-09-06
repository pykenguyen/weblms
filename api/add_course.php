<?php
// ... code kết nối database và kiểm tra quyền
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? ''; // Lấy dữ liệu từ trường mô tả

// ...

// Câu lệnh SQL cần được cập nhật để bao gồm cột description
$stmt = $pdo->prepare("INSERT INTO courses (title, description, teacher_id) VALUES (?, ?, ?)");
$stmt->execute([$title, $description, $teacher_id]);
// ...
?>