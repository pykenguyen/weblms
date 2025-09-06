<?php
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Bắt buộc người dùng phải đăng nhập để xem trang này
$user = require_login();
$pdo = db();

// Lấy danh sách các khóa học MÀ NGƯỜỜI DÙNG CHƯA ĐĂNG KÝ
// Đây là dữ liệu cần thiết cho trang "Khám phá khóa học"
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS teacher_name
    FROM courses c
    LEFT JOIN users u ON u.id=c.teacher_id
    WHERE NOT EXISTS (
        SELECT 1 FROM course_user cu WHERE cu.course_id=c.id AND cu.user_id=?
    )
    ORDER BY c.id DESC
");
$stmt->execute([$user['id']]);
$available_courses = $stmt->fetchAll();

// Dữ liệu đã sẵn sàng, bây giờ chúng ta sẽ hiển thị phần HTML bên dưới
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khám Phá Khóa Học - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .course-card {
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        .course-thumbnail {
            width: 100%;
            height: 12rem;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-50">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="trang-chu.php" class="text-2xl font-bold text-indigo-600">MyLMS</a>
            <nav class="hidden md:flex space-x-8 items-center">
                <a href="trang-chu.php" class="text-gray-600 hover:text-indigo-600">Bảng điều khiển</a>
                <a href="courses.php" class="text-indigo-600 font-semibold">Khám Phá</a>
                <a href="profile.php" class="text-gray-600 hover:text-indigo-600">Hồ Sơ</a>
                <a href="logout.php" class="text-gray-600 hover:text-indigo-600">Đăng Xuất</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900">Tất Cả Khóa Học</h1>
            <p class="text-lg text-gray-600 mt-2">Chọn khóa học bạn muốn tham gia!</p>
        </div>
        
        <div id="all-courses-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            </div>
    </main>

    <script>
        // Dữ liệu các khóa học có sẵn được PHP tải trước và nhúng vào đây
        const availableCourses = <?php echo json_encode($available_courses, JSON_NUMERIC_CHECK); ?>;

        function addEnrollmentHandlers() {
            document.querySelectorAll('.enroll-btn').forEach(button => {
                button.addEventListener('click', async (event) => {
                    const courseId = event.target.dataset.courseId;
                    
                    const confirmation = await Swal.fire({
                        title: 'Xác nhận đăng ký',
                        text: "Bạn có chắc chắn muốn đăng ký khóa học này không?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Đồng ý!',
                        cancelButtonText: 'Hủy'
                    });

                    if (!confirmation.isConfirmed) return;

                    button.disabled = true;
                    button.textContent = 'Đang xử lý...';
                    
                    try {
                        const response = await fetch('enroll.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ course_id: courseId })
                        });

                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message || 'Đăng ký thất bại.');
                        
                        await Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: result.message || 'Đăng ký thành công!',
                            confirmButtonColor: '#4f46e5'
                        });

                        window.location.href = 'trang-chu.php';
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Lỗi: ' + error.message,
                            confirmButtonColor: '#4f46e5'
                        });
                        button.disabled = false;
                        button.textContent = 'Đăng ký';
                    }
                });
            });
        }

        // Hàm chính để hiển thị khóa học, sử dụng dữ liệu đã tải sẵn
        document.addEventListener('DOMContentLoaded', () => {
            const coursesGrid = document.getElementById('all-courses-grid');

            if (availableCourses.length > 0) {
                coursesGrid.innerHTML = availableCourses.map(course => {
                    const thumbnail = course.thumbnail || `https://placehold.co/600x400/e0e7ff/4338ca?text=${encodeURIComponent(course.title)}`;
                    return `
                    <div class="course-card">
                        <a href="chi-tiet-khoa-hoc.php?id=${course.id}">
                            <img src="${thumbnail}" alt="Thumbnail" class="course-thumbnail">
                        </a>
                        <div class="p-6 flex-grow flex flex-col">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 h-14">${course.title}</h3>
                            <p class="text-gray-600 text-sm mb-4">GV: ${course.teacher_name || 'Chưa xác định'}</p>
                            <p class="text-gray-700 text-sm mb-4 h-20 overflow-hidden flex-grow">${course.description || ''}</p>
                            <div class="flex items-center justify-between mt-auto pt-4">
                                <a href="chi-tiet-khoa-hoc.php?id=${course.id}" class="font-semibold text-indigo-600 hover:text-indigo-800">Xem chi tiết</a>
                                <button data-course-id="${course.id}" class="enroll-btn px-4 py-2 bg-indigo-600 text-white font-semibold text-sm rounded-lg hover:bg-indigo-700 transition">
                                    Đăng ký
                                </button>
                            </div>
                        </div>
                    </div>`;
                }).join('');

                addEnrollmentHandlers();
            } else {
                coursesGrid.innerHTML = '<p class="col-span-3 text-center text-gray-600">Không có khóa học mới nào, hoặc bạn đã tham gia tất cả các khóa học hiện có.</p>';
            }
        });
    </script>
</body>
</html>