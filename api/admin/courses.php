<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';


// Chỉ cho phép admin truy cập API này
require_role(['admin']);
$pdo = db();

// Lấy danh sách khóa học
$stmt = $pdo->prepare("
    SELECT 
        c.id, 
        c.teacher_id, 
        c.title, 
        c.description, 
        c.thumbnail, 
        c.created_at, 
        c.updated_at,
        -- Sử dụng COALESCE để đảm bảo giá trị không phải NULL, sau đó CAST
        CAST(COALESCE(c.price, 0.00) AS DECIMAL(10,2)) as price, 
        u.name as teacher_name
    FROM courses c
    LEFT JOIN users u ON u.id = c.teacher_id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ép kiểu price thành float cho mỗi khóa học trước khi trả về JSON
foreach ($courses as &$course) {
    // Đảm bảo giá trị price là một số hợp lệ
    // Loại bỏ các ký tự không phải số (trừ dấu chấm thập phân) và chuyển đổi thành float
    // Sử dụng str_replace để thay thế dấu phẩy (nếu có) thành dấu chấm cho parseFloat
    $cleanPrice = str_replace(',', '.', (string)$course['price']); // Thay dấu phẩy thành dấu chấm
    $cleanPrice = preg_replace('/[^0-9.]/', '', $cleanPrice); // Loại bỏ các ký tự không phải số khác
    $course['price'] = (float)$cleanPrice;
}
unset($course); // Bỏ tham chiếu cuối cùng

// Trả về dữ liệu dưới dạng JSON
json(['items' => $courses]);
