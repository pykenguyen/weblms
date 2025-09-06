<?php
$users_to_update = [
'admin@lms.com' => 'admin123',
'teacher.anhvan@lms.com' => 'anhvan123',
'teacher.toan@lms.com' => 'toan123',
'student.an@lms.com' => 'tan123',
'student.binh@lms.com' => 'binh123',
'student.cuong@lms.com' => 'cuong123',
'student.dung@lms.com' => 'dung123',
'student.giang@lms.com' => 'giang123',
'student.huong@lms.com' => 'huong123',
'student.khanh@lms.com' => 'khanh123'
];

header('Content-Type: text/plain; charset=utf-8');

echo "-- ==============================================================\n";
echo "-- SAO CHÉP TOÀN BỘ NỘI DUNG BÊN DƯỚI VÀ DÁN VÀO TAB SQL CỦA PHPMYADMIN\n";
echo "-- ==============================================================\n";

foreach ($users_to_update as $email => $plain_password) {
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

$sql_command = "UPDATE `users` SET `password` = '" . $hashed_password . "' WHERE `email` = '" . $email . "';\n";

echo $sql_command;
}

echo "\n-- ==============================================================\n";
echo "-- KẾT THÚC\n";
echo "-- ==============================================================\n";
?>