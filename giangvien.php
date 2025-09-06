<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require __DIR__ . '/admin_header.php'; 
require_role(['admin']);
$pdo = db();

// Dữ liệu cho sidebar menu

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản lý Giảng viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin/hocvien.css"> 
</head>
<body>
   
        
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Quản lý Giảng viên</h1>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus"></i> Thêm giảng viên mới
                </button>
            </div>

            <div class="filters mb-4 p-3 bg-light rounded">
                <form id="filterForm" class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label for="searchInput" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Tên hoặc email giảng viên...">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="button" class="btn btn-secondary" id="resetFiltersBtn">
                            <i class="fas fa-sync"></i> Đặt lại
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Giảng viên</th>
                            <th>Liên hệ</th>
                            <th>Ngày tham gia</th>
                            <th>Số khóa học</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="teacher-table-body">
                        </tbody>
                </table>
            </div>

            <nav aria-label="Phân trang">
                <ul class="pagination justify-content-center">
                    </ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Thêm Giảng viên Mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="addTeacherForm">
                        <div class="mb-3"><label class="form-label">Tên Giảng viên</label><input type="text" class="form-control" id="teacherName" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="teacherEmail" required></div>
                        <div class="mb-3"><label class="form-label">Số điện thoại</label><input type="tel" class="form-control" id="teacherPhone"></div>
                        <div class="mb-3"><label class="form-label">Mật khẩu</label><input type="password" class="form-control" id="teacherPassword" required></div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="saveTeacherBtn">Lưu</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Chỉnh sửa Thông tin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="editTeacherForm">
                        <input type="hidden" id="editTeacherId">
                        <div class="mb-3"><label class="form-label">Tên Giảng viên</label><input type="text" class="form-control" id="editTeacherName" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="editTeacherEmail" required></div>
                        <div class="mb-3"><label class="form-label">Số điện thoại</label><input type="tel" class="form-control" id="editTeacherPhone"></div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="updateTeacherBtn">Cập nhật</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="giangvien.js"></script>
</body>
</html>