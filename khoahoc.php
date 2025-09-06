<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// 1. BẢO MẬT VÀ KHỞI TẠO
$user = require_role(['admin']);
$pdo = db();

$feedback_message = '';
$feedback_type = '';

// Hàm xử lý upload ảnh vào thư mục 'uploads/thumbnails/'
function handle_image_upload($file_input_name, $current_thumbnail = '') {
    $upload_dir = __DIR__ . '/uploads/thumbnails/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_input_name];
        $file_name = uniqid() . '-' . basename($file['name']);
        $target_path = $upload_dir . $file_name;
        if (!empty($current_thumbnail) && file_exists(__DIR__ . '/' . $current_thumbnail)) {
            unlink(__DIR__ . '/' . $current_thumbnail);
        }
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return 'uploads/thumbnails/' . $file_name;
        } else {
            throw new Exception("Không thể di chuyển file đã upload.");
        }
    }
    return $current_thumbnail; 
}

// 2. XỬ LÝ FORM KHI GỬI LÊN (THÊM, SỬA, XÓA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';
    try {
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0.0);
            $created_at = trim($_POST['created_at'] ?? date('Y-m-d'));
            $thumbnail = handle_image_upload('thumbnail');

            if (!empty($title) && $teacher_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO courses (title, description, teacher_id, price, created_at, thumbnail, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $teacher_id, $price, $created_at, $thumbnail]);
                $feedback_message = "Đã thêm khóa học '{$title}' thành công!";
                $feedback_type = 'success';
            } else {
                throw new Exception("Vui lòng điền đầy đủ tên khóa học và chọn giảng viên.");
            }
        } elseif ($action === 'update') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0.0);
            $created_at = trim($_POST['created_at'] ?? date('Y-m-d'));
            $current_thumbnail = trim($_POST['current_thumbnail'] ?? '');
            $thumbnail = handle_image_upload('thumbnail', $current_thumbnail);

            if (!empty($title) && $teacher_id > 0 && $course_id > 0) {
                 $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, teacher_id = ?, price = ?, created_at = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $description, $teacher_id, $price, $created_at, $thumbnail, $course_id]);
                $feedback_message = "Đã cập nhật khóa học '{$title}' thành công!";
                $feedback_type = 'success';
            } else {
                 throw new Exception("Dữ liệu cập nhật không hợp lệ.");
            }
        } elseif ($action === 'delete') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            if ($course_id > 0) {
                $stmt_find = $pdo->prepare("SELECT thumbnail FROM courses WHERE id = ?");
                $stmt_find->execute([$course_id]);
                $course_to_delete = $stmt_find->fetch(PDO::FETCH_ASSOC);
                if ($course_to_delete && !empty($course_to_delete['thumbnail']) && file_exists(__DIR__ . '/' . $course_to_delete['thumbnail'])) {
                    unlink(__DIR__ . '/' . $course_to_delete['thumbnail']);
                }
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                $feedback_message = "Đã xóa khóa học thành công!";
                $feedback_type = 'success';
            } else {
                throw new Exception("ID khóa học không hợp lệ.");
            }
        }
        header('Location: khoahoc.php?feedback=' . urlencode($feedback_message) . '&type=' . $feedback_type);
        exit();
    } catch (Exception $e) {
        $feedback_message = "Lỗi: " . $e->getMessage();
        $feedback_type = 'danger';
    }
}
if (isset($_GET['feedback'])) {
    $feedback_message = htmlspecialchars($_GET['feedback']);
    $feedback_type = htmlspecialchars($_GET['type'] ?? 'danger');
}

// 3. LẤY DỮ LIỆU
$stmt_courses = $pdo->query("SELECT c.*, u.name as teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id ORDER BY c.id DESC");
$all_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
$stmt_teachers = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name");
$teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/admin_header.php';
?>

<head>
    <title>Admin Panel - Quản lý Khóa học</title>
</head>

<header class="header d-flex justify-content-between align-items-center">
    <h1 class="page-title mb-0"><i class="fas fa-book me-2"></i> Quản lý Khóa học</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus me-1"></i> Thêm Khóa học</button>
</header>

<?php if ($feedback_message): ?>
<div class="alert alert-<?php echo $feedback_type === 'success' ? 'success' : 'danger'; ?> mt-3 alert-dismissible fade show">
    <?php echo $feedback_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<section class="content-section">
    <div class="row mt-4" id="course-list"></div>
</section>

<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm khóa học mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_action" value="create">
                <div class="modal-body">
                    <div class="mb-3"><label for="addCourseTitle" class="form-label">Tên khóa học</label><input type="text" id="addCourseTitle" name="title" class="form-control" required></div>
                    <div class="mb-3"><label for="addCourseThumbnail" class="form-label">Ảnh đại diện</label><input type="file" id="addCourseThumbnail" name="thumbnail" class="form-control" accept="image/*"></div>
                    <div class="mb-3"><label for="addCourseTeacher" class="form-label">Giảng viên</label><select id="addCourseTeacher" name="teacher_id" class="form-select" required><option value="">-- Chọn giảng viên --</option><?php foreach ($teachers as $teacher): ?><option value="<?= htmlspecialchars((string)$teacher['id']); ?>"><?= htmlspecialchars($teacher['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="row"><div class="col-md-6 mb-3"><label for="addCourseDate" class="form-label">Ngày tạo</label><input type="date" id="addCourseDate" name="created_at" class="form-control" value="<?= date('Y-m-d'); ?>" required></div><div class="col-md-6 mb-3"><label for="addCoursePrice" class="form-label">Giá tiền (VND)</label><input type="number" id="addCoursePrice" name="price" class="form-control" min="0" value="0" step="1000" required></div></div>
                    <div class="mb-3"><label for="addCourseDescription" class="form-label">Mô tả</label><textarea id="addCourseDescription" name="description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu khóa học</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa khóa học</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_action" value="update">
                <input type="hidden" name="course_id" id="editCourseId">
                <input type="hidden" name="current_thumbnail" id="editCourseCurrentThumbnail">
                <div class="modal-body">
                    <div class="mb-3"><label for="editCourseTitle" class="form-label">Tên khóa học</label><input type="text" id="editCourseTitle" name="title" class="form-control" required></div>
                    <div class="mb-3">
                        <label for="editCourseThumbnail" class="form-label">Ảnh đại diện mới (chọn nếu muốn thay đổi)</label>
                        <input type="file" id="editCourseThumbnail" name="thumbnail" class="form-control" accept="image/*">
                        <div class="mt-2">Ảnh hiện tại: <img id="currentThumbnailPreview" src="" alt="Ảnh xem trước" style="max-width: 100px; max-height: 100px; display: none; vertical-align: middle; margin-left: 10px;"></div>
                    </div>
                    <div class="mb-3"><label for="editCourseTeacher" class="form-label">Giảng viên</label><select id="editCourseTeacher" name="teacher_id" class="form-select" required><option value="">-- Chọn giảng viên --</option><?php foreach ($teachers as $teacher): ?><option value="<?= htmlspecialchars((string)$teacher['id']); ?>"><?= htmlspecialchars($teacher['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="row"><div class="col-md-6 mb-3"><label for="editCourseDate" class="form-label">Ngày tạo</label><input type="date" id="editCourseDate" name="created_at" class="form-control" required></div><div class="col-md-6 mb-3"><label for="editCoursePrice" class="form-label">Giá tiền (VND)</label><input type="number" id="editCoursePrice" name="price" class="form-control" min="0" step="1000" required></div></div>
                    <div class="mb-3"><label for="editCourseDescription" class="form-label">Mô tả</label><textarea id="editCourseDescription" name="description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu thay đổi</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="courseDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-chalkboard-teacher me-2"></i> Chi tiết Khóa học</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="courseDetailContent"><div class="text-center p-5"><div class="spinner-border text-primary"></div></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>

<form method="POST" id="deleteCourseForm" style="display: none;">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="course_id" id="course_id_to_delete">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseList = document.getElementById('course-list');
    const editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('courseDetailModal'));
    const allCoursesData = <?php echo json_encode($all_courses); ?>;

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(parseFloat(amount) || 0);
    }

    function renderCourses(courses) {
        courseList.innerHTML = '';
        if (!courses || courses.length === 0) {
            courseList.innerHTML = '<div class="col-12 text-center text-muted mt-4"><h4>Chưa có khóa học nào.</h4></div>';
            return;
        }
        courses.forEach(course => {
            const thumbnailSrc = course.thumbnail ? `${course.thumbnail}?t=${new Date().getTime()}` : 'image/placeholder.jpg';
            const courseCard = `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="${thumbnailSrc}" class="card-img-top" alt="Thumbnail" style="height: 180px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">${course.title}</h5>
                            <p class="card-text text-muted small mb-1"><i class="fas fa-user-tie me-2"></i>${course.teacher_name || 'N/A'}</p>
                            <p class="card-text text-muted small"><i class="fas fa-calendar-alt me-2"></i>${new Date(course.created_at).toLocaleDateString('vi-VN')}</p>
                            <h6 class="card-subtitle mt-2 mb-3 text-primary">${formatCurrency(course.price)}</h6>
                            <div class="mt-auto d-flex">
                                <button class="btn btn-outline-secondary btn-sm detail-btn" data-id="${course.id}"><i class="fas fa-users"></i> Chi tiết</button>
                                <div class="ms-auto">
                                    <button class="btn btn-primary btn-sm me-1 edit-btn" data-id="${course.id}"><i class="fas fa-edit"></i> Sửa</button>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="${course.id}"><i class="fas fa-trash"></i> Xóa</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            courseList.innerHTML += courseCard;
        });
        attachEventListeners();
    }

    async function apiCall(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Lỗi không xác định');
            return data;
        } catch (error) {
            console.error('API Error:', error);
            alert('Lỗi: ' + error.message);
            return null;
        }
    }

    async function showCourseDetailModal(courseId) {
        const contentDiv = document.getElementById('courseDetailContent');
        contentDiv.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
        detailModal.show();

        const result = await apiCall(`api/admin/get_course_details.php?id=${courseId}`);
        if (result && result.data) {
            const details = result.data;
            let studentsHtml = '<p class="text-muted">Chưa có học viên nào đăng ký khóa học này.</p>';
            if (details.students && details.students.length > 0) {
                studentsHtml = '<ul class="list-group list-group-flush">';
                details.students.forEach(student => {
                    studentsHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">${student.name}<span class="badge bg-secondary rounded-pill">${student.email}</span></li>`;
                });
                studentsHtml += '</ul>';
            }
            contentDiv.innerHTML = `
                <div class="mb-4"><strong>Giảng viên phụ trách:</strong><p class="fs-5">${details.instructor_name}</p></div><hr>
                <div><strong>Danh sách học viên (${details.students.length}):</strong><div class="mt-2">${studentsHtml}</div></div>`;
        } else {
            contentDiv.innerHTML = '<div class="alert alert-danger">Không thể tải dữ liệu chi tiết.</div>';
        }
    }
    
    function attachEventListeners() {
        courseList.addEventListener('click', function(e) {
            const button = e.target.closest('button');
            if (!button) return;
            const courseId = button.dataset.id;
            
            if (button.classList.contains('detail-btn')) {
                showCourseDetailModal(courseId);
            }
            
            if (button.classList.contains('edit-btn')) {
                const courseData = allCoursesData.find(c => c.id == courseId);
                if (courseData) {
                    document.getElementById('editCourseId').value = courseData.id;
                    document.getElementById('editCourseTitle').value = courseData.title;
                    const currentThumbnailInput = document.getElementById('editCourseCurrentThumbnail');
                    const thumbnailPreview = document.getElementById('currentThumbnailPreview');
                    currentThumbnailInput.value = courseData.thumbnail || '';
                    if(courseData.thumbnail) {
                        thumbnailPreview.src = courseData.thumbnail;
                        thumbnailPreview.style.display = 'inline-block';
                    } else {
                        thumbnailPreview.style.display = 'none';
                    }
                    document.getElementById('editCourseThumbnail').value = '';
                    document.getElementById('editCourseTeacher').value = courseData.teacher_id;
                    document.getElementById('editCourseDate').value = courseData.created_at.split(' ')[0];
                    document.getElementById('editCoursePrice').value = courseData.price;
                    document.getElementById('editCourseDescription').value = courseData.description;
                    editModal.show();
                }
            }

            if (button.classList.contains('delete-btn')) {
                if (confirm('Bạn có chắc chắn muốn xóa khóa học này không? Hành động này không thể hoàn tác.')) {
                    document.getElementById('course_id_to_delete').value = courseId;
                    document.getElementById('deleteCourseForm').submit();
                }
            }
        });
    }

    renderCourses(allCoursesData);
});
</script>

<?php 
require __DIR__ . '/admin_footer.php'; 
?>