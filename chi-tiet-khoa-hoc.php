<?php
session_start();

// 1. KIỂM TRA ĐĂNG NHẬP VÀ LẤY THÔNG TIN USER
if (!isset($_SESSION['user']['id'])) {
    // Nếu gọi API, trả về lỗi JSON. Nếu truy cập trang, chuyển hướng.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['message' => 'Vui lòng đăng nhập.']);
    } else {
        header('Location: login.php');
    }
    exit();
}
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'];

// 2. KIỂM TRA ID KHÓA HỌC HỢP LỆ TỪ URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Lỗi: ID khóa học không hợp lệ.");
}
$course_id = (int)$_GET['id'];

// 3. KẾT NỐI CSDL
$db_host = '127.0.0.1:3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'lcms_db';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Lỗi kết nối CSDL: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 4. KIỂM TRA XEM HỌC VIÊN ĐÃ GHI DANH VÀO KHÓA HỌC CHƯA
$is_enrolled = false;
$stmt_check = $conn->prepare("SELECT 1 FROM course_user WHERE user_id = ? AND course_id = ?");
$stmt_check->bind_param("ii", $user_id, $course_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    $is_enrolled = true;
}
$stmt_check->close();

// 5. TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ TRANG
// Lấy thông tin khóa học
$stmt_course = $conn->prepare("SELECT id, title, description FROM courses WHERE id = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$course_result = $stmt_course->get_result();
$course = $course_result->fetch_assoc();
if (!$course) { die("Không tìm thấy khóa học này."); }

// Lấy danh sách bài học và trạng thái hoàn thành (nếu đã ghi danh)
$lessons = [];
if ($is_enrolled) {
    $stmt_lessons = $conn->prepare("
        SELECT l.id, l.title, l.file_path, pt.is_completed
        FROM lessons l
        LEFT JOIN progress_tracking pt ON l.id = pt.lesson_id AND pt.user_id = ?
        WHERE l.course_id = ? ORDER BY l.id ASC
    ");
    $stmt_lessons->bind_param("ii", $user_id, $course_id);
} else {
    $stmt_lessons = $conn->prepare("SELECT id, title, file_path, 0 as is_completed FROM lessons WHERE course_id = ? ORDER BY id ASC");
    $stmt_lessons->bind_param("i", $course_id);
}
$stmt_lessons->execute();
$lessons_result = $stmt_lessons->get_result();
while ($row = $lessons_result->fetch_assoc()) {
    $lessons[] = $row;
}

// Lấy danh sách bài tập
$assignments = [];
$stmt_assignments = $conn->prepare("SELECT id, title, description, due_date, file_path FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
$stmt_assignments->bind_param("i", $course_id);
$stmt_assignments->execute();
$assignments_result = $stmt_assignments->get_result();
while ($row = $assignments_result->fetch_assoc()) {
    $assignments[] = $row;
}

// Lấy danh sách bài nộp của học viên (nếu đã ghi danh)
$submissions = [];
if ($is_enrolled) {
    $stmt_submissions = $conn->prepare("
        SELECT s.assignment_id, s.grade, s.submitted_at
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE s.student_id = ? AND a.course_id = ?
    ");
    $stmt_submissions->bind_param("ii", $user_id, $course_id);
    $stmt_submissions->execute();
    $submissions_result = $stmt_submissions->get_result();
    while ($row = $submissions_result->fetch_assoc()) {
        $submissions[] = $row;
    }
}

// 6. TẠO MỘT OBJECT DỮ LIỆU BAN ĐẦU ĐỂ TRUYỀN SANG JAVASCRIPT
$initial_data = [
    'course' => $course,
    'lessons' => $lessons,
    'assignments' => $assignments,
    'submissions' => $submissions,
    'is_enrolled' => $is_enrolled
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($course['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white/80 backdrop-blur sticky top-0 z-50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="trang-chu.php" class="text-2xl font-bold text-indigo-600">MyLMS</a>
            <nav class="space-x-6">
                <a href="trang-chu.php" class="text-gray-700 hover:text-indigo-600">Bảng điều khiển</a>
                <a href="courses.php" class="text-gray-700 hover:text-indigo-600">Khám Phá</a>
                <button id="btn-logout" class="text-gray-600 hover:text-red-600">Đăng Xuất</button>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-10 space-y-8">
        <a href="courses.php" class="text-indigo-600 font-semibold">← Quay lại danh sách</a>
        <div>
            <h1 id="course-title" class="text-3xl font-extrabold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p id="course-desc" class="text-gray-600 mt-2"><?php echo htmlspecialchars($course['description']); ?></p>
        </div>

        <div id="enroll-box" class="<?php echo $is_enrolled ? 'hidden' : 'flex'; ?> bg-white rounded-lg shadow p-6 items-center justify-between">
            <p class="text-lg font-semibold text-gray-700">Đăng ký để truy cập toàn bộ tính năng!</p>
            <button id="enroll-btn" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                Đăng ký ngay
            </button>
        </div>

        <div id="unenroll-box" class="<?php echo !$is_enrolled ? 'hidden' : 'flex'; ?> justify-end">
            <button id="unenroll-btn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700">
                Hủy đăng ký khóa học
            </button>
        </div>

        <section id="content-view" class="space-y-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">📚 Nội dung bài học</h2>
                <ul id="lesson-list" class="space-y-3"></ul>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">📝 Bài tập</h2>
                <div id="assignment-list" class="space-y-5"></div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">🧠 Bài kiểm tra</h2>
                <div id="quiz-student" class="space-y-3">
                    <p class="text-gray-500">Chưa có bài kiểm tra.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Dữ liệu ban đầu được truyền từ PHP
        const initialData = <?php echo json_encode($initial_data, JSON_NUMERIC_CHECK); ?>;
        
        const courseId = initialData.course.id;
        const qs = (s, p = document) => p.querySelector(s);
        const qsa = (s, p = document) => [...p.querySelectorAll(s)];

        function fmtDate(v) {
            if (!v) return '—';
            const d = new Date(v.replace(' ', 'T'));
            return d.toLocaleString('vi-VN');
        }

        async function fetchAPI(url, options = {}) {
            try {
                const response = await fetch(url, { credentials: 'same-origin', ...options });
                if (response.status === 401) {
                    window.location.href = 'login.php';
                    return null;
                }
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra.');
                }
                return data;
            } catch (error) {
                console.error(`Lỗi khi gọi API ${url}:`, error);
                Swal.fire({ icon: 'error', title: 'Lỗi API', text: error.message, confirmButtonColor: '#4f46e5' });
                throw error;
            }
        }

        function renderLessons(lessons, isEnrolled) {
            const box = qs('#lesson-list');
            if (!lessons || !lessons.length) {
                box.innerHTML = '<p class="text-gray-500">Chưa có bài học.</p>';
                return;
            }
            box.innerHTML = lessons.map(l => {
                const isCompleted = isEnrolled && Number(l.is_completed) === 1;
                const interactiveHTML = isEnrolled
                    ? (isCompleted
                        ? `<span class="text-green-500 font-semibold flex items-center gap-2"><i data-feather="check-circle" class="w-5 h-5"></i> Đã hoàn thành</span>`
                        : `<button data-lesson-id="${l.id}" class="complete-lesson-btn px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600">Đánh dấu hoàn thành</button>`)
                    : '';
                return `
                    <li id="lesson-${l.id}" class="p-4 border rounded-lg ${isCompleted ? 'bg-gray-50' : 'bg-white'} transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i data-feather="${isCompleted ? 'check-circle' : 'circle'}" class="w-5 h-5 ${isCompleted ? 'text-green-500' : 'text-gray-400'}"></i>
                                <p class="font-medium ${isCompleted ? 'line-through text-gray-500' : ''}">${l.title}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                ${l.file_path ? `<a href="files.php?lesson_id=${l.id}" target="_blank" class="text-indigo-600 hover:underline text-sm font-semibold">Xem/Tải về</a>` : ''}
                                ${interactiveHTML}
                            </div>
                        </div>
                    </li>`;
            }).join('');
            feather.replace();
            if (isEnrolled) attachCompleteLessonHandlers();
        }
        
        function attachCompleteLessonHandlers() {
            qsa('.complete-lesson-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    const lessonId = button.dataset.lessonId;
                    button.disabled = true; button.textContent = 'Đang lưu...';
                    try {
                        await fetchAPI('progress.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ lesson_id: lessonId, is_completed: 1 })
                        });
                        const lessonElement = qs(`#lesson-${lessonId}`);
                        lessonElement.classList.add('bg-gray-50');
                        lessonElement.querySelector('p.font-medium').classList.add('line-through', 'text-gray-500');
                        lessonElement.querySelector('i[data-feather="circle"]').outerHTML = `<i data-feather="check-circle" class="w-5 h-5 text-green-500"></i>`;
                        button.outerHTML = `<span class="text-green-500 font-semibold flex items-center gap-2"><i data-feather="check-circle" class="w-5 h-5"></i> Đã hoàn thành</span>`;
                        feather.replace();
                    } catch (error) {
                        button.disabled = false; button.textContent = 'Đánh dấu hoàn thành';
                    }
                });
            });
        }

        function renderAssignments(assignments, submissions, isEnrolled) {
    const box = qs('#assignment-list');
    if (!assignments || !assignments.length) {
        box.innerHTML = '<p class="text-gray-500">Chưa có bài tập.</p>';
        return;
    }
    const subsMap = new Map((submissions || []).map(s => [s.assignment_id, s]));
    box.innerHTML = assignments.map(a => {
        let statusHTML = '', formHTML = '', fileLinkHTML = '';
        if (a.file_path) {
            // Thêm đường dẫn file vào đây
            // Đường dẫn file phải bắt đầu từ thư mục gốc của trang web
            fileLinkHTML = `<a href="/testLong/${a.file_path}" target="_blank" class="text-indigo-600 hover:underline text-sm font-semibold ml-2">Xem/Tải về</a>`;
        }

        if (isEnrolled) {
            const submission = subsMap.get(a.id);
            const now = new Date(), dueDate = a.due_date ? new Date(a.due_date.replace(' ', 'T')) : null;
            const isOverdue = dueDate && dueDate < now;
            if (submission) {
                statusHTML = submission.grade != null
                    ? `Trạng thái: <span class="font-semibold">Điểm: ${submission.grade}</span>`
                    : `Trạng thái: <span class="font-semibold text-blue-600">Đã nộp, chờ chấm</span>`;
            } else {
                if (isOverdue) {
                    statusHTML = `Trạng thái: <span class="font-bold text-red-600">Đã trễ hạn</span>`;
                } else {
                    statusHTML = `Trạng thái: <span class="font-semibold">Chưa nộp</span>`;
                    formHTML = `<form class="mt-3 flex items-center gap-3" data-submit="${a.id}">
                                    <input name="file" type="file" class="border rounded px-3 py-1" required/>
                                    <button class="bg-indigo-600 text-white rounded px-4 py-1">Nộp bài</button>
                                </form>`;
                }
            }
        }
        return `
            <div class="border-l-4 border-indigo-500 pl-4 py-2">
                <p class="font-semibold">${a.title}${fileLinkHTML}</p>
                <p class="text-sm text-gray-500">Hạn nộp: ${fmtDate(a.due_date)}</p>
                ${isEnrolled ? `<p class="text-sm mt-1">${statusHTML}</p>` : ''}
                <p class="text-sm text-gray-500 mt-1">${a.description || ''}</p>
                ${formHTML}
            </div>`;
    }).join('');
    if (isEnrolled) attachSubmitAssignmentHandlers();
}
        function attachSubmitAssignmentHandlers() {
            qsa('[data-submit]').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = Number(form.dataset.submit);
                    const fd = new FormData(form);
                    fd.append('assignment_id', id);
                    const btn = form.querySelector('button');
                    btn.disabled = true; btn.textContent = 'Đang nộp...';
                    try {
                        const result = await fetchAPI('submissions.php', { method: 'POST', body: fd });
                        Swal.fire({ icon: 'success', title: 'Thành công!', text: 'Nộp bài thành công!', confirmButtonColor: '#4f46e5' })
                            .then(() => location.reload());
                    } catch (error) {
                         btn.disabled = false; btn.textContent = 'Nộp bài';
                    }
                });
            });
        }

        async function enrollCourse(button) {
            const { isConfirmed } = await Swal.fire({
                title: 'Xác nhận đăng ký',
                text: "Bạn có chắc chắn muốn đăng ký khóa học này không?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Đồng ý!',
                cancelButtonText: 'Hủy'
            });
            if (!isConfirmed) return;
            button.disabled = true; button.textContent = 'Đang xử lý...';
            try {
                const result = await fetchAPI('enroll.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ course_id: courseId })
                });
                await Swal.fire({ icon: 'success', title: 'Thành công!', text: result.message, confirmButtonColor: '#4f46e5' });
                location.reload();
            } catch (error) {
                button.disabled = false; button.textContent = 'Đăng ký ngay';
            }
        }

        async function unenrollCourse() {
             const { isConfirmed } = await Swal.fire({
                title: 'Bạn có chắc không?',
                text: "Toàn bộ tiến độ và bài nộp của bạn trong khóa học này sẽ bị xóa!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Đồng ý, hủy!',
                cancelButtonText: 'Không'
            });
            if (isConfirmed) {
                try {
                    const response = await fetchAPI('unenroll.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ course_id: courseId })
                    });
                    await Swal.fire('Đã hủy!', response.message, 'success');
                    window.location.href = 'courses.php';
                } catch (error) { /* fetchAPI đã xử lý lỗi */ }
            }
        }

        // --- MAIN EXECUTION ---
        document.addEventListener('DOMContentLoaded', () => {
            renderLessons(initialData.lessons, initialData.is_enrolled);
            renderAssignments(initialData.assignments, initialData.submissions, initialData.is_enrolled);

            qs('#enroll-btn')?.addEventListener('click', (e) => enrollCourse(e.target));
            qs('#unenroll-btn')?.addEventListener('click', unenrollCourse);
            
            qs('#btn-logout').addEventListener('click', async () => {
                await fetch('logout.php', { method: 'POST' });
                location.href = 'login.php';
            });

            feather.replace();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>