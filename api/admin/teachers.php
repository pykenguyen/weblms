<?php
// api/admin/teachers.php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

require_role(['admin']);
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        $whereClause = "WHERE role = 'teacher'";
        $params = [];
        if (!empty($search)) {
            $whereClause .= " AND (name LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $totalStmt = $pdo->prepare("SELECT COUNT(id) FROM users {$whereClause}");
        $totalStmt->execute($params);
        $totalItems = $totalStmt->fetchColumn();
        $totalPages = $totalItems > 0 ? ceil($totalItems / $limit) : 1;
        
        $query = "
            SELECT u.id, u.name, u.email, u.phone, u.created_at, COUNT(c.id) as course_count
            FROM users u
            LEFT JOIN courses c ON u.id = c.teacher_id
            {$whereClause}
            GROUP BY u.id, u.name, u.email, u.phone, u.created_at
            ORDER BY u.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json([
            'items' => $teachers,
            'total_items' => (int)$totalItems,
            'total_pages' => (int)$totalPages,
            'current_page' => $page,
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            json(['message' => 'Tên, Email và Mật khẩu không được để trống.'], 400); exit();
        }

        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role, created_at, updated_at) 
             VALUES (?, ?, ?, ?, 'teacher', NOW(), NOW())"
        );
        $stmt->execute([$data['name'], $data['email'], $data['phone'], $hashed_password]);
        json(['message' => 'Thêm giảng viên thành công!'], 201);
        break;

    case 'PUT':
        $id = $_GET['id'] ?? null;
        if (!$id) { exit(json(['message' => 'Thiếu ID.'], 400)); }
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$data['name'], $data['email'], $data['phone'], $id]);
        json(['message' => 'Cập nhật thành công!']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { exit(json(['message' => 'Thiếu ID.'], 400)); }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$id]);
        json(['message' => 'Xóa thành công!']);
        break;

    default:
        json(['message' => 'Phương thức không hợp lệ.'], 405);
        break;
}