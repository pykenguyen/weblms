<?php
header('Content-Type: application/json');

// Thông tin kết nối CSDL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lcms_db";

// Tắt báo lỗi PHP để tránh xuất ra HTML
error_reporting(0);
ini_set('display_errors', 0);

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // In ra lỗi dưới dạng JSON để client có thể xử lý
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Câu lệnh SQL để lấy dữ liệu khóa học và tên giáo viên
$sql = "
    SELECT 
        c.id, 
        c.title, 
        c.description,
        c.created_at, 
        c.price,
        u.name AS teacher_name
    FROM courses AS c
    LEFT JOIN users AS u ON c.teacher_id = u.id
";
$result = $conn->query($sql);

$courses = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $courses[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'teacher_name' => $row['teacher_name'] ?? 'Chưa xác định',
                'created_at' => $row['created_at'],
                'price' => (float)$row['price'],
                'language' => 'Tiếng Anh' // Giả định ngôn ngữ
            ];
        }
    }
} else {
    // Xử lý lỗi SQL nếu có
    die(json_encode(["success" => false, "message" => "SQL Error: " . $conn->error]));
}

$conn->close();

echo json_encode(["success" => true, "data" => $courses]);