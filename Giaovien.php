<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    // Nếu không còn session user, chuyển về login
    header('Location: login.php');
    exit();
}

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';
require_role(['teacher']); // vẫn giữ nếu bạn muốn kiểm tra role
$pdo = db();


try {
    $teacherId = $_SESSION['user']['id'] ?? null;
    if (!$teacherId) throw new Exception("Teacher ID not found");

    // Giáo viên
// Giáo viên
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id=?");
$stmt->execute([$teacherId]);
$teacherInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Giáo viên', 'email' => ''];
    // Thống kê
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $totalClasses  = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id=?");
    $stmt->execute([$teacherId]);
    $totalCourses = $stmt->fetchColumn();

    // Học sinh
    $students = $pdo->query("SELECT id,name,email,created_at FROM users WHERE role='student' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // Lớp học
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id=? LIMIT 10");
    $stmt->execute([$teacherId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Môn học
    $stmt = $pdo->prepare("SELECT id,title,description FROM courses WHERE teacher_id=? LIMIT 10");
    $stmt->execute([$teacherId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thông báo
    $stmt = $pdo->prepare("SELECT id,title,message,created_at,is_read FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$teacherId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Lỗi: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>EduManager - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/gv/gv.css">
<style>
/* Thêm CSS cơ bản nếu chưa có */
.sidebar{width:220px;background:#343a40;color:#fff;height:100vh;position:fixed;top:0;left:0;overflow-y:auto;}
.sidebar a{color:#fff;text-decoration:none;}
.sidebar a.active{background:#495057;}
.content-area{margin-left:220px;padding:20px;}
.page-content{display:none;}
.page-content.active{display:block;}
.stats-card{padding:20px;background:#f8f9fa;border-radius:10px;text-align:center;margin-bottom:15px;}
.user-profile{display:flex;align-items:center;gap:10px;}
.user-avatar{width:35px;height:35px;border-radius:50%;}
</style>
</head>
<body>

<div class="sidebar d-flex flex-column p-3">
    <h3 class="text-white mb-4">EduManager</h3>
    <ul class="nav nav-pills flex-column">
        <li><a href="#dashboard" class="nav-link active"><i class="fa fa-home"></i> Dashboard</a></li>
        <li><a href="#hocsinh" class="nav-link"><i class="fa fa-user-graduate"></i> Học sinh</a></li>
        <li><a href="#lophoc" class="nav-link"><i class="fa fa-chalkboard"></i> Lớp học</a></li>
        <li><a href="#monhoc" class="nav-link"><i class="fa fa-book"></i> Môn học</a></li>
        <li><a href="#baitap" class="nav-link"><i class="fa fa-tasks"></i> Bài tập</a></li>
        <li><a href="#diemso" class="nav-link"><i class="fa fa-star"></i> Điểm số</a></li>
        <li><a href="#lichgiangday" class="nav-link"><i class="fa fa-calendar"></i> Lịch giảng dạy</a></li>
        <li><a href="#tinnhan" class="nav-link"><i class="fa fa-envelope"></i> Tin nhắn</a></li>
        <li><a href="#caidat" class="nav-link"><i class="fa fa-cog"></i> Cài đặt</a></li>
        <li><a href="logout.php" class="nav-link"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="content-area">
<nav class="navbar navbar-light px-4">
    <div class="ms-auto user-profile">
    <img src="image/default-avatar.png" class="user-avatar" alt="avatar">
    <span><?= htmlspecialchars($teacherInfo['name']) ?></span>
</div>
</nav>

<div class="main-content">

<div id="dashboard" class="page-content active">
    <h1>Dashboard</h1>
    <div class="row">
        <div class="col-md-4"><div class="card stats-card"><i class="fa fa-user-graduate"></i><h2><?= $totalStudents ?></h2><p>Học sinh</p></div></div>
        <div class="col-md-4"><div class="card stats-card"><i class="fa fa-chalkboard"></i><h2><?= $totalClasses ?></h2><p>Lớp học</p></div></div>
        <div class="col-md-4"><div class="card stats-card"><i class="fa fa-book"></i><h2><?= $totalCourses ?></h2><p>Môn học</p></div></div>
    </div>
</div>

<div id="hocsinh" class="page-content">
    <h1>Danh sách học sinh</h1>
    <table class="table">
        <thead><tr><th>Tên</th><th>Email</th><th>Ngày tạo</th></tr></thead>
        <tbody>
        <?php foreach($students as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="lophoc" class="page-content">
    <h1>Danh sách lớp học</h1>
    <table class="table">
        <thead><tr><th>Tên lớp</th></tr></thead>
        <tbody>
        <?php foreach($classes as $c): ?>
            <tr><td><?= htmlspecialchars($c['name']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="monhoc" class="page-content">
    <h1>Danh sách môn học</h1>
    <table class="table">
        <thead><tr><th>Tên môn</th><th>Mô tả</th></tr></thead>
        <tbody>
        <?php foreach($courses as $c): ?>
            <tr><td><?= htmlspecialchars($c['title']) ?></td><td><?= htmlspecialchars($c['description']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="baitap" class="page-content">
    <h1>Bài tập</h1>
    <div class="card mb-4 p-3">
        <h5>Thêm bài tập</h5>
        <form id="add-assignment-form" enctype="multipart/form-data" class="row g-3">
    <div class="col-md-6"><input type="text" id="assignment-title" name="title" class="form-control" placeholder="Tiêu đề" required></div>
    <div class="col-md-6">
        <select id="assignment-course" name="course_id" class="form-select" required>
            <option value="">-- Chọn môn --</option>
            <?php foreach($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <textarea id="assignment-description" name="description" class="form-control" placeholder="Mô tả bài tập (tùy chọn)"></textarea>
    </div>
    <div class="col-md-6"><input type="file" id="assignment-file" name="file" class="form-control"></div>
    <div class="col-md-6"><input type="datetime-local" id="due-date" name="due_date" class="form-control" required></div>
    <div class="col-12"><button type="submit" class="btn btn-success">Thêm</button></div>
</form>
        <div id="add-assignment-result" class="mt-2"></div>
    </div>
    <table class="table" id="assignments-table">
        <thead><tr><th>Tiêu đề</th><th>Môn học</th><th>Hạn nộp</th><th>File</th></tr></thead>
        <tbody><tr><td colspan="4">Đang tải...</td></tr></tbody>
    </table>
</div>

<div id="diemso" class="page-content">
    <h1>Điểm số</h1>
    <div class="mb-3">
        <label for="select-course" class="form-label">Chọn môn học:</label>
        <select id="select-course" class="form-select">
            <option value="0">Tất cả môn</option>
            <?php foreach($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Học sinh</th>
                <th>Bài tập</th>
                <th>Môn học</th>
                <th>Điểm</th>
                <th>Feedback</th>
                <th>Ngày nộp</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="grades-body">
            <tr><td colspan="7">Đang tải dữ liệu...</td></tr>
        </tbody>
    </table>
</div>

<div id="lichgiangday" class="page-content">
    <h1>Lịch giảng dạy</h1>
    <div class="card mb-4 p-3">
        <h5>Thêm lớp vào lịch giảng dạy</h5>
        <form id="schedule-form" class="row g-3">
            <div class="col-md-4">
                <select id="select-course-schedule" class="form-select" required>
                    <option value="">-- Chọn môn --</option>
                    <?php foreach($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="day-of-week" class="form-select" required>
                    <option value="1">Thứ Hai</option>
                    <option value="2">Thứ Ba</option>
                    <option value="3">Thứ Tư</option>
                    <option value="4">Thứ Năm</option>
                    <option value="5">Thứ Sáu</option>
                    <option value="6">Thứ Bảy</option>
                    <option value="7">Chủ Nhật</option>
                </select>
            </div>
            <div class="col-md-2"><input type="time" id="start-time" class="form-control" required></div>
            <div class="col-md-2"><input type="time" id="end-time" class="form-control" required></div>
            <div class="col-md-2"><input type="text" id="location" class="form-control" placeholder="Địa điểm" required></div>
            <div class="col-12"><button type="submit" class="btn btn-success">Thêm vào lịch</button></div>
        </form>
        <div id="schedule-result" class="mt-2"></div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Giờ</th>
                <th>Môn học</th>
                <th>Địa điểm</th>
            </tr>
        </thead>
        <tbody id="schedule-body">
            <tr><td colspan="4">Đang tải dữ liệu...</td></tr>
        </tbody>
    </table>
</div>

<div id="tinnhan" class="page-content">
    <h1>Tin nhắn</h1>
    <table class="table">
        <thead><tr><th>Tiêu đề</th><th>Nội dung</th><th>Thời gian</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach($notifications as $n): ?>
            <tr class="<?= $n['is_read'] ? 'table-secondary' : '' ?>">
                <td><?= htmlspecialchars($n['title']) ?></td>
                <td><?= htmlspecialchars($n['message']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></td>
                <td><?= $n['is_read']?'Đã đọc':'Chưa đọc' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="caidat" class="page-content">
    <h1>Cài đặt tài khoản</h1>
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Cập nhật thành công!</div>
    <?php endif; ?>
    <form method="POST" action="api/update_profile.php">
        <div class="mb-3">
    <label for="name" class="form-label">Tên hiển thị</label>
    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($teacherInfo['name']) ?>" required>
</div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" id="email" name="email" class="form-control" value="<?= htmlspecialchars($teacherInfo['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
            <input type="password" id="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Lưu cài đặt</button>
    </form>
</div>

</div></div><script>
document.addEventListener('DOMContentLoaded', ()=>{

    // Sidebar navigation
    document.querySelectorAll('.sidebar .nav-link').forEach(link=>{
    link.addEventListener('click', e=>{
        const href = link.getAttribute('href');
        // Chỉ ngăn chặn hành vi mặc định nếu href là một ID (ví dụ: #dashboard)
        if (href && href.startsWith('#')) {
            e.preventDefault();
            document.querySelectorAll('.sidebar .nav-link').forEach(l=>l.classList.remove('active'));
            link.classList.add('active');
            document.querySelectorAll('.page-content').forEach(p=>p.classList.remove('active'));
            const target = document.querySelector(href);
            if(target) target.classList.add('active');
        }
    });
});

    // --- Load bài tập ---
    function loadAssignments(){
        fetch('api/get_assignments.php')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#assignments-table tbody');
            tbody.innerHTML = '';
            if(!data.items || data.items.length === 0){
                tbody.innerHTML = '<tr><td colspan="4">Chưa có bài tập</td></tr>';
                return;
            }
            data.items.forEach(a => {
    const fileLink = a.file_path
        ? `<a href="${a.file_path}" target="_blank">Xem file</a>`
        : 'Không có';
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${a.title}</td>
        <td>${a.course_title}</td>
        <td>${new Date(a.due_date).toLocaleString('vi-VN')}</td>
        <td>${fileLink}</td>
        <td><button class="btn btn-sm btn-danger delete-btn" data-id="${a.id}">Xóa</button></td>
    `;
    const deleteButton = row.querySelector('.delete-btn');
    deleteButton.addEventListener('click', () => {
        if (confirm('Bạn có chắc chắn muốn xóa bài tập này?')) {
            deleteAssignment(a.id);
        }
    });
    tbody.appendChild(row);
});
        })
        .catch(()=>document.querySelector('#assignments-table tbody').innerHTML='<tr><td colspan="4">Lỗi tải dữ liệu</td></tr>');
    }

    // Thêm bài tập
    const addAssignmentForm = document.getElementById('add-assignment-form');
if (addAssignmentForm) {
    addAssignmentForm.addEventListener('submit', e => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('title', document.getElementById('assignment-title').value.trim());
        formData.append('course_id', document.getElementById('assignment-course').value);
        formData.append('description', document.getElementById('assignment-description').value.trim());
        formData.append('due_date', document.getElementById('due-date').value);

        const fileInput = document.getElementById('assignment-file');
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        const resultDiv = document.getElementById('add-assignment-result');
        resultDiv.textContent = 'Đang thêm...';

        fetch('api/add_assignment.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                resultDiv.textContent = data.message || '';
                if (data.success) {
                    addAssignmentForm.reset();
                    loadAssignments();
                }
            })
            .catch(() => resultDiv.textContent = 'Lỗi thêm bài tập');
    });
}

    // --- Load điểm số ---
    function loadGrades(course_id=0){
        const tbody = document.getElementById('grades-body');
        tbody.innerHTML = '<tr><td colspan="7">Đang tải dữ liệu...</td></tr>';
        fetch(`api/grade_submission.php?course_id=${course_id}`)
        .then(res=>res.json())
        .then(data=>{
            tbody.innerHTML = '';
            if(!data.items || data.items.length===0){
                tbody.innerHTML = '<tr><td colspan="7">Chưa có dữ liệu</td></tr>';
                return;
            }
            data.items.forEach(g=>{
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${g.student_name}</td>
                    <td>${g.assignment_title}</td>
                    <td>${g.course_title}</td>
                    <td><input type="number" min="0" max="10" step="0.1" value="${g.grade??''}" class="form-control grade-input"></td>
                    <td><input type="text" value="${g.feedback??''}" class="form-control feedback-input"></td>
                    <td>${new Date(g.submitted_at).toLocaleString('vi-VN')}</td>
                    <td><button class="btn btn-sm btn-primary save-btn">Lưu</button></td>
                `;
                row.querySelector('.save-btn').addEventListener('click', ()=>{
                    const payload = {
                        submission_id: g.submission_id,
                        grade: row.querySelector('.grade-input').value,
                        feedback: row.querySelector('.feedback-input').value
                    };
                    fetch('api/grade_submission.php',{
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body:JSON.stringify(payload)
                    })
                    .then(r=>r.json())
                    .then(d=>alert(d.message || 'Đã lưu'))
                    .catch(()=>alert('Lỗi lưu điểm'));
                });
                tbody.appendChild(row);
            });
        })
        .catch(()=>tbody.innerHTML='<tr><td colspan="7">Lỗi tải dữ liệu</td></tr>');
    }
    const selectCourse = document.getElementById('select-course');
    if(selectCourse){
        selectCourse.addEventListener('change', e=>loadGrades(e.target.value));
    }

    // --- Lịch giảng dạy ---
    function loadSchedule(){
        fetch('api/get_schedule.php')
        .then(r=>r.json())
        .then(data=>{
            const tbody = document.getElementById('schedule-body');
            tbody.innerHTML='';
            const day_map={1:'Thứ Hai',2:'Thứ Ba',3:'Thứ Tư',4:'Thứ Năm',5:'Thứ Sáu',6:'Thứ Bảy',7:'Chủ Nhật'};
            if(!data.items || data.items.length===0){
                tbody.innerHTML='<tr><td colspan="4">Chưa có lịch</td></tr>';
                return;
            }
            data.items.forEach(s=>{
                const row = document.createElement('tr');
                row.innerHTML = `<td>${day_map[s.day_of_week]}</td><td>${s.start_time} - ${s.end_time}</td><td>${s.course_title}</td><td>${s.location}</td>`;
                tbody.appendChild(row);
            });
        })
        .catch(()=>document.getElementById('schedule-body').innerHTML='<tr><td colspan="4">Lỗi tải dữ liệu</td></tr>');
    }
    const scheduleForm = document.getElementById('schedule-form');
    if(scheduleForm){
        scheduleForm.addEventListener('submit', e=>{
            e.preventDefault();
            const payload = {
                course_id: document.getElementById('select-course-schedule').value,
                day_of_week: document.getElementById('day-of-week').value,
                start_time: document.getElementById('start-time').value,
                end_time: document.getElementById('end-time').value,
                location: document.getElementById('location').value
            };
            fetch('api/add_schedule.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify(payload)
            })
            .then(r=>r.json())
            .then(data=>{
                document.getElementById('schedule-result').textContent = data.message||'Đã thêm';
                if(data.success){
                    e.target.reset();
                    loadSchedule();
                }
            })
            .catch(()=>document.getElementById('schedule-result').textContent='Lỗi thêm lịch');
        });
    }
function deleteAssignment(id) {
    fetch('api/delete_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadAssignments(); // Tải lại danh sách sau khi xóa thành công
        } else {
            alert(data.message || 'Lỗi xóa bài tập');
        }
    })
    .catch(error => {
        alert('Lỗi kết nối đến máy chủ.');
        console.error('Lỗi:', error);
    });
}
    // --- Load ban đầu ---
    loadAssignments();
    loadGrades(selectCourse ? selectCourse.value : 0);
    loadSchedule();

});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>