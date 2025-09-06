<?php
session_start();
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Check for admin role using the helper function
require_role(['admin']);

// Get a PDO database connection
$pdo = db();

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_users.php");
    exit();
}

// Fetch all users from the database
$stmt = $pdo->query("SELECT id, name, email, role FROM users");
$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản lý người dùng</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 p-8">
    <div class="container mx-auto">
        <h2 class="text-2xl font-bold mb-4">Danh sách người dùng</h2>
        <table class="min-w-full bg-white rounded-lg shadow-md">
            <thead>
                <tr class="w-full border-b">
                    <th class="py-2 px-4 text-left">ID</th>
                    <th class="py-2 px-4 text-left">Tên người dùng</th>
                    <th class="py-2 px-4 text-left">Email</th>
                    <th class="py-2 px-4 text-left">Vai trò</th>
                    <th class="py-2 px-4 text-left">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row) { ?>
                <tr class="border-t">
                    <td class="py-2 px-4"><?= htmlspecialchars($row['id']) ?></td>
                    <td class="py-2 px-4"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="py-2 px-4"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="py-2 px-4"><?= htmlspecialchars($row['role']) ?></td>
                    <td class="py-2 px-4">
                        <a href="edit_user.php?id=<?= htmlspecialchars($row['id']) ?>" class="text-indigo-600 hover:underline">Sửa</a> |
                        <a href="admin_users.php?delete=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Xóa người dùng này?')" class="text-red-600 hover:underline">Xóa</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>