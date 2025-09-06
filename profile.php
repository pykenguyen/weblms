<?php
session_start();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
$user_id = (int)$user['id'];
$user_name = $user['name'];

// 2. KẾT NỐI CSDL
$db_host = '127.0.0.1:3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'lcms_db';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Lỗi kết nối CSDL: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 3. LẤY THÔNG TIN USER (BAO GỒM avatar_url)
$stmt_user = $conn->prepare("SELECT email, phone, date_of_birth, avatar_url FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_details = $stmt_user->get_result()->fetch_assoc();
$user_email = $user_details['email'] ?? 'Không tìm thấy email';
$user_phone = $user_details['phone'] ?? '';
$user_dob = $user_details['date_of_birth'] ?? '';
$user_avatar = $user_details['avatar_url'] ?? null;
$stmt_user->close();

// Đếm tổng số khóa học đã đăng ký
$total_courses = 0;
$stmt_courses_count = $conn->prepare("SELECT COUNT(*) as total FROM course_user WHERE user_id = ?");
$stmt_courses_count->bind_param("i", $user_id);
$stmt_courses_count->execute();
$total_courses = $stmt_courses_count->get_result()->fetch_assoc()['total'];
$stmt_courses_count->close();

// Đếm tổng số bài học đã hoàn thành (CHỈ TÍNH CÁC KHÓA HỌC ĐANG THAM GIA)
$lessons_completed = 0;
$stmt_lessons = $conn->prepare("
    SELECT COUNT(pt.id) as total
    FROM progress_tracking pt
    JOIN lessons l ON pt.lesson_id = l.id
    WHERE pt.user_id = ? AND pt.is_completed = 1
    AND l.course_id IN (SELECT course_id FROM course_user WHERE user_id = ?)
");
$stmt_lessons->bind_param("ii", $user_id, $user_id);
$stmt_lessons->execute();
$lessons_completed = $stmt_lessons->get_result()->fetch_assoc()['total'];
$stmt_lessons->close();

// Đếm tổng số bài tập đã nộp (CHỈ TÍNH CÁC KHÓA HỌC ĐANG THAM GIA)
$submissions_count = 0;
$stmt_submissions = $conn->prepare("
    SELECT COUNT(s.id) as total
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    WHERE s.student_id = ?
    AND a.course_id IN (SELECT course_id FROM course_user WHERE user_id = ?)
");
$stmt_submissions->bind_param("ii", $user_id, $user_id);
$stmt_submissions->execute();
$submissions_count = $stmt_submissions->get_result()->fetch_assoc()['total'];
$stmt_submissions->close();


// 4. TRUY VẤN DANH SÁCH KHÓA HỌC VÀ TIẾN ĐỘ
$enrolled_courses = [];
$stmt_enrolled = $conn->prepare("
    SELECT
        c.id, c.title, teacher.name as instructor,
        COUNT(l.id) as total_lessons,
        COUNT(pt.is_completed) as completed_lessons
    FROM course_user cu
    JOIN courses c ON cu.course_id = c.id
    LEFT JOIN users teacher ON c.teacher_id = teacher.id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress_tracking pt ON l.id = pt.lesson_id AND pt.user_id = ? AND pt.is_completed = 1
    WHERE cu.user_id = ?
    GROUP BY c.id, c.title, teacher.name
    ORDER BY c.title
");
$stmt_enrolled->bind_param("ii", $user_id, $user_id);
$stmt_enrolled->execute();
$result_enrolled = $stmt_enrolled->get_result();
while ($row = $result_enrolled->fetch_assoc()) {
    $enrolled_courses[] = $row;
}
$stmt_enrolled->close();

$conn->close();
$user_avatar_initials = mb_strtoupper(mb_substr($user_name, 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Của Tôi - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">

    <header id="header" class="bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="trang-chu.php" class="text-2xl font-bold text-indigo-600">MyLMS</a>
            <nav class="hidden md:flex space-x-8 items-center">
                <a href="trang-chu.php" class="text-gray-600 hover:text-indigo-600">Trang Chủ</a>
                <a href="courses.php" class="text-gray-600 hover:text-indigo-600">Khám Phá</a>
                <a href="profile.php" class="text-indigo-600 font-semibold">Hồ Sơ</a>
                <a href="logout.php" class="text-gray-600 hover:text-indigo-600">Đăng Xuất</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8">Hồ Sơ Cá Nhân</h1>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm text-center">
                    <?php if ($user_avatar && file_exists($user_avatar)): ?>
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover border-2 border-indigo-200">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-bold"><?php echo htmlspecialchars($user_avatar_initials); ?></span>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user_name); ?></h2>
                    <p class="text-gray-500"><?php echo htmlspecialchars($user_email); ?></p>
                    <button id="edit-profile-btn" class="mt-6 w-full bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700 transition duration-300 font-semibold flex items-center justify-center space-x-2">
                        <i data-feather="edit-2" class="w-4 h-4"></i>
                        <span>Chỉnh sửa hồ sơ</span>
                    </button>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Thống Kê Tổng Quan</h3>
                    <div class="max-w-xs mx-auto">
                         <canvas id="learningStatsChart"></canvas>
                         <div id="chart-no-data" class="hidden text-center text-gray-500 py-8">
                             <p>Chưa có dữ liệu để thống kê.</p>
                         </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-2xl shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Các Khóa Học Của Tôi</h3>
                    <div class="space-y-6">
                        <?php if (empty($enrolled_courses)): ?>
                            <p class="text-gray-500 text-center py-4">Bạn chưa tham gia khóa học nào.</p>
                        <?php else: ?>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <?php
                                    $total = (int)$course['total_lessons'];
                                    $completed = (int)$course['completed_lessons'];
                                    $percentage = ($total > 0) ? round(($completed / $total) * 100) : 0;
                                ?>
                                <div class="border bg-white p-4 rounded-lg hover:shadow-md transition-shadow flex flex-col">
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p class="text-sm text-gray-500 mt-1">GV: <?php echo htmlspecialchars($course['instructor']); ?></p>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs font-medium text-gray-500">Tiến độ</span>
                                            <span class="text-sm font-semibold text-indigo-600"><?php echo $percentage; ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <div class="flex justify-between items-center mt-4">
                                            <p class="text-xs text-gray-500">
                                                Hoàn thành: <?php echo $completed; ?>/<?php echo $total; ?> bài học
                                            </p>
                                            <a href="chi-tiet-khoa-hoc.php?id=<?php echo $course['id']; ?>" class="bg-indigo-600 text-white px-4 py-2 text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                                <?php
                                                    if ($percentage == 0) { echo 'Bắt đầu học'; } 
                                                    elseif ($percentage == 100) { echo 'Xem lại khóa học'; } 
                                                    else { echo 'Tiếp tục học'; }
                                                ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="profile-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Chỉnh sửa Hồ sơ</h2>
                <button id="close-modal-btn" class="text-gray-500 hover:text-gray-800">&times;</button>
            </div>
            <form id="profile-form" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ảnh đại diện</label>
                        <div class="mt-2 flex items-center space-x-4">
                            <img id="avatar-preview" src="<?php echo ($user_avatar && file_exists($user_avatar)) ? htmlspecialchars($user_avatar) : 'https://via.placeholder.com/96'; ?>" alt="Xem trước" class="w-16 h-16 rounded-full object-cover">
                            <div class="flex flex-col space-y-2">
                                <label for="avatar" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Thay đổi</span>
                                    <input id="avatar" name="avatar" type="file" class="sr-only" accept="image/png, image/jpeg, image/gif">
                                </label>
                                <button type="button" id="remove-avatar-btn" class="py-2 px-3 border border-transparent rounded-md shadow-sm text-sm leading-4 font-medium text-red-700 bg-red-100 hover:bg-red-200">
                                    Gỡ ảnh
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="remove_avatar" id="remove_avatar_input" value="0">
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Họ và Tên</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_name); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100 text-gray-500">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user_phone); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                    </div>
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Ngày sinh</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo htmlspecialchars($user_dob); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-semibold">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();

            const learningStatsData = {
                courses: <?php echo $total_courses; ?>,
                lessons: <?php echo $lessons_completed; ?>,
                submissions: <?php echo $submissions_count; ?>
            };

            const chartCanvas = document.getElementById('learningStatsChart');
            const noDataMessage = document.getElementById('chart-no-data');
            
            if (chartCanvas && (learningStatsData.courses > 0 || learningStatsData.lessons > 0 || learningStatsData.submissions > 0)) {
                new Chart(chartCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Khóa học tham gia', 'Bài học hoàn thành', 'Bài tập đã nộp'],
                        datasets: [{
                            data: [learningStatsData.courses, learningStatsData.lessons, learningStatsData.submissions],
                            backgroundColor: ['rgba(99, 102, 241, 0.7)', 'rgba(34, 197, 94, 0.7)', 'rgba(245, 158, 11, 0.7)'],
                            borderColor: ['#fff'],
                            borderWidth: 2,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 20 } } }
                    }
                });
            } else if(chartCanvas) {
                chartCanvas.style.display = 'none';
                if(noDataMessage) noDataMessage.classList.remove('hidden');
            }

            const modal = document.getElementById('profile-modal');
            const openBtn = document.getElementById('edit-profile-btn');
            const closeBtn = document.getElementById('close-modal-btn');
            const profileForm = document.getElementById('profile-form');
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatar-preview');
            const removeAvatarBtn = document.getElementById('remove-avatar-btn');
            const removeAvatarInput = document.getElementById('remove_avatar_input');

            if (removeAvatarBtn) {
                removeAvatarBtn.addEventListener('click', () => {
                    avatarPreview.src = 'https://via.placeholder.com/96';
                    avatarInput.value = '';
                    removeAvatarInput.value = '1';
                });
            }

            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        removeAvatarInput.value = '0'; // Reset lại tín hiệu nếu người dùng chọn file mới
                        const reader = new FileReader();
                        reader.onload = (e) => avatarPreview.src = e.target.result;
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (modal && openBtn && closeBtn) {
                openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
                closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.classList.add('hidden');
                });
            }
            
            if (profileForm) {
                profileForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const submitButton = profileForm.querySelector('button[type="submit"]');
                    submitButton.disabled = true;
                    submitButton.innerHTML = 'Đang lưu...';

                    const formData = new FormData(profileForm);

                    try {
                        const response = await fetch('update-profile.php', {
                            method: 'POST',
                            body: formData 
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message || 'Có lỗi xảy ra.');

                        modal.classList.add('hidden');
                        await Swal.fire({
                            icon: 'success', title: 'Thành công!',
                            text: result.message, timer: 2000, showConfirmButton: false
                        });
                        location.reload();

                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Thất bại', text: error.message });
                    } finally {
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Lưu thay đổi';
                    }
                });
            }
        });
    </script>
</body>
</html>