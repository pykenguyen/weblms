<?php
declare(strict_types=1);

// Nạp các tệp cấu hình và hàm hỗ trợ.
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Kiểm tra quyền truy cập admin.
require_role(['admin']);
$pdo = db();

// Lấy danh sách khóa học và trạng thái để lọc
$courseOptions = $pdo->query("SELECT id, title FROM courses ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$statusOptions = [
    'active' => 'Đang học',
    'completed' => 'Đã hoàn thành',
    'pending' => 'Chờ xác nhận',
    'paused' => 'Tạm dừng'
];

// Nhúng header chung (bao gồm sidebar, css, fonts...)
require __DIR__ . '/admin_header.php';
?>

<head>
    <title>Admin Panel - Quản lý Học viên</title>
</head>

<header class="header d-flex justify-content-between align-items-center">
    <h1 class="page-title mb-0"><i class="fas fa-users me-2"></i> Quản lý Học viên</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus"></i> Thêm học viên mới
    </button>
</header>

<section class="content-section">
    <div class="filters mb-4 p-3 bg-light rounded shadow-sm">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="searchInput" class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Tên, email, SĐT...">
            </div>
            <div class="col-md-3">
                <label for="courseFilter" class="form-label">Khóa học</label>
                <select class="form-select" id="courseFilter">
                    <option value="">Tất cả</option>
                    <?php foreach ($courseOptions as $course): ?>
                        <option value="<?= htmlspecialchars($course['title']) ?>"><?= htmlspecialchars($course['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="statusFilter" class="form-label">Trạng thái</label>
                <select class="form-select" id="statusFilter">
                    <option value="">Tất cả</option>
                    <?php foreach ($statusOptions as $value => $text): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($text) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-secondary" id="resetFiltersBtn">
                    <i class="fas fa-sync"></i> Đặt lại
                </button>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Học viên</th>
                    <th>Liên hệ</th>
                    <th>Khóa học đã đăng ký</th>
                    <th>Ngày tham gia</th>
                    <th>Tiến độ</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="student-table-body">
                </tbody>
        </table>
    </div>

    <nav aria-label="Phân trang" class="mt-4">
        <ul class="pagination justify-content-center">
            </ul>
    </nav>
</section>

<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm Học viên Mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <div class="mb-3"><label for="studentName" class="form-label">Tên Học viên</label><input type="text" class="form-control" id="studentName" required></div>
                    <div class="mb-3"><label for="studentEmail" class="form-label">Email</label><input type="email" class="form-control" id="studentEmail" required></div>
                    <div class="mb-3"><label for="studentPhone" class="form-label">Số điện thoại</label><input type="tel" class="form-control" id="studentPhone"></div>
                    <div class="mb-3"><label for="studentPassword" class="form-label">Mật khẩu</label><input type="password" class="form-control" id="studentPassword" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="saveStudentBtn">Lưu</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa Thông tin Học viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="editStudentForm">
                    <input type="hidden" id="editStudentId">
                    <div class="mb-3"><label for="editStudentName" class="form-label">Tên Học viên</label><input type="text" class="form-control" id="editStudentName" required></div>
                    <div class="mb-3"><label for="editStudentEmail" class="form-label">Email</label><input type="email" class="form-control" id="editStudentEmail" required></div>
                    <div class="mb-3"><label for="editStudentPhone" class="form-label">Số điện thoại</label><input type="tel" class="form-control" id="editStudentPhone"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="updateStudentBtn">Cập nhật</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="studentDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i> Chi tiết Học viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div id="studentDetailContent" class="text-center"><div class="spinner-border text-primary"></div></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allStudents = [];
    let currentPage = 1;
    const studentsPerPage = 10;

    const addModal = new bootstrap.Modal(document.getElementById('addStudentModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('studentDetailModal'));

    async function apiCall(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra.');
            }
            return data;
        } catch (error) {
            console.error('API Error:', error);
            alert('Lỗi: ' + error.message);
            return null;
        }
    }
    
    async function loadStudents(page = 1) {
        const tableBody = document.getElementById('student-table-body');
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;
        const data = await apiCall(`api/admin/students.php?page=${page}`);
        if (data) {
            allStudents = data.items;
            renderStudents(allStudents);
            setupPagination(data.total_items, data.total_pages, page);
        } else {
             tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Không thể tải dữ liệu học viên.</td></tr>`;
        }
    }
    
    function renderStudents(students) {
        const tableBody = document.getElementById('student-table-body');
        tableBody.innerHTML = '';
        if (!students || students.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Không tìm thấy học viên nào.</td></tr>`;
            return;
        }
        students.forEach(student => {
            const row = `
                <tr>
                    <td>
                        <div>
                            <div class="fw-bold">${student.name}</div>
                            <small class="text-muted">ID: ${student.id}</small>
                        </div>
                    </td>
                    <td>
                        <div>${student.email}</div>
                        <small class="text-muted">${student.phone || 'Chưa có SĐT'}</small>
                    </td>
                    <td>${student.enrolled_courses || 'Chưa đăng ký'}</td>
                    <td>${new Date(student.created_at).toLocaleDateString('vi-VN')}</td>
                    <td>0%</td>
                    <td><span class="badge bg-success">active</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary view-btn" data-id="${student.id}" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${student.id}" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${student.id}" title="Xóa"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }
    
    function setupPagination(totalItems, totalPages, currentPage) {
        const paginationContainer = document.querySelector('.pagination');
        paginationContainer.innerHTML = '';
        if (totalPages <= 1) return;

        const createPageItem = (page, text, isDisabled = false, isActive = false) => {
            const disabledClass = isDisabled ? 'disabled' : '';
            const activeClass = isActive ? 'active' : '';
            return `<li class="page-item ${disabledClass} ${activeClass}"><a class="page-link" href="#" data-page="${page}">${text}</a></li>`;
        };

        let paginationHtml = createPageItem(currentPage - 1, 'Trước', currentPage === 1);

        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += createPageItem(i, i, false, currentPage === i);
        }

        paginationHtml += createPageItem(currentPage + 1, 'Sau', currentPage === totalPages);
        paginationContainer.innerHTML = paginationHtml;
    }

    document.querySelector('.pagination').addEventListener('click', function(event) {
        event.preventDefault();
        const pageButton = event.target.closest('.page-link');
        if (!pageButton || pageButton.parentElement.classList.contains('disabled')) return;
        loadStudents(parseInt(pageButton.dataset.page));
    });

    function applyFilters() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const selectedCourse = document.getElementById('courseFilter').value.toLowerCase();
        const selectedStatus = document.getElementById('statusFilter').value.toLowerCase();

        const filteredStudents = allStudents.filter(student => {
            const studentName = (student.name || '').toLowerCase();
            const studentEmail = (student.email || '').toLowerCase();
            const studentCourses = (student.enrolled_courses || '').toLowerCase();
            const studentStatus = (student.status || '').toLowerCase();

            return (studentName.includes(searchText) || studentEmail.includes(searchText)) &&
                   (selectedCourse === '' || studentCourses.includes(selectedCourse)) &&
                   (selectedStatus === '' || studentStatus === selectedStatus);
        });
        
        renderStudents(filteredStudents);
        setupPagination(filteredStudents.length, Math.ceil(filteredStudents.length / studentsPerPage), 1);
    }
    
    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('courseFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);

    document.getElementById('resetFiltersBtn').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        loadStudents(1);
    });
    
    async function showStudentDetailModal(studentId) {
        const contentDiv = document.getElementById('studentDetailContent');
        contentDiv.innerHTML = '<div class="spinner-border text-primary"></div>';
        detailModal.show();
        
        const result = await apiCall(`api/admin/student_detail.php?id=${studentId}`);
        if (result && result.data) {
            const student = result.data;
            const initials = (student.name || '').split(' ').map(n=>n[0]).join('').toUpperCase();
            
            let coursesHtml = '<p class="text-center text-muted mt-3">Chưa đăng ký khóa học nào.</p>';
            if (student.enrolled_courses && student.enrolled_courses.length > 0) {
                 coursesHtml = student.enrolled_courses.map(course => `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 shadow-sm">
                            <img src="${course.thumbnail || 'image/placeholder.jpg'}" class="card-img-top" style="height: 120px; object-fit: cover;">
                            <div class="card-body p-2">
                                <h6 class="card-title small fw-bold mb-1">${course.title}</h6>
                                <p class="card-text text-muted" style="font-size: 0.8rem;">GV: ${course.instructor}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
            
            contentDiv.innerHTML = `
                <div class="row g-4">
                    <div class="col-lg-4 text-center border-end">
                        <div class="d-flex align-items-center justify-content-center fw-bold mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; border-radius: 50%; background-color: var(--primary); color: white;">
                            ${initials}
                        </div>
                        <h5 class="mb-1">${student.name}</h5>
                        <p class="text-muted small">ID: ${student.id}</p>
                        <hr>
                        <ul class="list-unstyled text-start small">
                            <li class="mb-2"><i class="fas fa-envelope me-2 text-muted"></i> ${student.email}</li>
                            <li><i class="fas fa-phone me-2 text-muted"></i> ${student.phone || 'Chưa có SĐT'}</li>
                        </ul>
                    </div>
                    <div class="col-lg-8">
                        <h6 class="text-start fw-bold">Các khóa học đã đăng ký (${(student.enrolled_courses || []).length})</h6>
                        <div class="row mt-3">${coursesHtml}</div>
                    </div>
                </div>
            `;
        } else {
            contentDiv.innerHTML = '<div class="alert alert-danger">Không thể tải chi tiết học viên.</div>';
        }
    }

    async function showEditStudentModal(studentId) {
        const result = await apiCall(`api/admin/get_student.php?id=${studentId}`);
        if (result && result.data) {
            const student = result.data;
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editStudentName').value = student.name;
            document.getElementById('editStudentEmail').value = student.email;
            document.getElementById('editStudentPhone').value = student.phone || '';
            editModal.show();
        }
    }

    async function deleteStudent(studentId) {
        if (confirm(`Bạn có chắc chắn muốn xóa học viên có ID: ${studentId}?`)) {
            const result = await apiCall(`api/admin/delete_student.php?id=${studentId}`, { method: 'DELETE' });
            if (result && result.success) {
                alert('Xóa học viên thành công!');
                loadStudents();
            }
        }
    }

    document.getElementById('student-table-body').addEventListener('click', function(e) {
        const button = e.target.closest('button');
        if (!button) return;
        const studentId = button.dataset.id;

        if (button.classList.contains('view-btn')) showStudentDetailModal(studentId);
        if (button.classList.contains('edit-btn')) showEditStudentModal(studentId);
        if (button.classList.contains('delete-btn')) deleteStudent(studentId);
    });

    document.getElementById('saveStudentBtn').addEventListener('click', async function() {
        const studentData = {
            name: document.getElementById('studentName').value,
            email: document.getElementById('studentEmail').value,
            phone: document.getElementById('studentPhone').value,
            password: document.getElementById('studentPassword').value
        };
        const result = await apiCall('api/admin/add_student.php', { method: 'POST', body: JSON.stringify(studentData), headers: {'Content-Type': 'application/json'} });
        if (result && result.success) {
            addModal.hide();
            document.getElementById('addStudentForm').reset();
            loadStudents();
            alert('Thêm học viên thành công!');
        }
    });

    document.getElementById('updateStudentBtn').addEventListener('click', async function() {
        const studentData = {
            id: document.getElementById('editStudentId').value,
            name: document.getElementById('editStudentName').value,
            email: document.getElementById('editStudentEmail').value,
            phone: document.getElementById('editStudentPhone').value
        };
        const result = await apiCall('api/admin/edit_student.php', { method: 'POST', body: JSON.stringify(studentData), headers: {'Content-Type': 'application/json'} });
        if (result && result.success) {
            editModal.hide();
            loadStudents();
            alert('Cập nhật thành công!');
        }
    });

    loadStudents();
});
</script>

<?php 
require __DIR__ . '/admin_footer.php'; 
?>