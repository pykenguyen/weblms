<?php
// Import config + helpers để kết nối database
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$pdo = db();

// Lấy nhiều khóa học hơn để hiển thị trong slider (ví dụ: 9 khóa)
$stmt_courses = $pdo->query("
    SELECT c.title, c.thumbnail, u.name as instructor
    FROM courses c
    LEFT JOIN users u ON c.teacher_id = u.id
    ORDER BY c.id DESC
    LIMIT 9
");
$courses_from_db = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Chuyển đổi dữ liệu cho phù hợp với cấu trúc HTML
$courses = [];
foreach ($courses_from_db as $course) {
    $courses[] = [
        'image' => !empty($course['thumbnail']) ? $course['thumbnail'] : 'image/placeholder.jpg',
        'title' => $course['title'],
        'instructor' => $course['instructor'],
        'link' => 'login.php'
    ];
}

// Dữ liệu cho các mục điều hướng
$navItems = [
    ['href' => '#home', 'text' => 'Trang Chủ'],
    ['href' => '#courses', 'text' => 'Khóa Học'],
    ['href' => '#features', 'text' => 'Tính Năng'],
    ['href' => '#about', 'text' => 'Giới Thiệu'],
];

// Dữ liệu cho các tính năng
$features = [
    ['icon' => 'fa-solid fa-chalkboard-user', 'title' => 'Giảng Viên Chuyên Môn Cao', 'description' => 'Học hỏi từ các chuyên gia hàng đầu trong ngành với kinh nghiệm thực chiến dày dặn.'],
    ['icon' => 'fa-solid fa-clock', 'title' => 'Học Tập Linh Hoạt', 'description' => 'Truy cập khóa học mọi lúc, mọi nơi và học theo tốc độ của riêng bạn.'],
    ['icon' => 'fa-solid fa-award', 'title' => 'Chứng Chỉ Uy Tín', 'description' => 'Nhận chứng chỉ sau khi hoàn thành để nâng cao giá trị CV và sự nghiệp của bạn.'],
];
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyLMS - Nền Tảng Học Tập Trực Tuyến</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="style.css">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { 'sans': ['Be Vietnam Pro', 'sans-serif'], } } }
        }
    </script>
    <style>
        .swiper-pagination-bullet-active {
            background-color: #4f46e5 !important;
        }
        .swiper-button-next,
        .swiper-button-prev {
            background-color: white;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: #4f46e5;
        }
        .swiper-button-next::after,
        .swiper-button-prev::after {
            font-size: 18px;
            font-weight: 800;
        }

        /* --- SỬA LỖI Ở ĐÂY --- */
        .swiper-button-next {
            right: 10px; /* Đổi từ -40px thành 10px */
        }
        .swiper-button-prev {
            left: 10px;  /* Đổi từ -40px thành 10px */
        }
        /* ----------------------- */
        
        @media (max-width: 640px) {
            .swiper-button-next,
            .swiper-button-prev {
                display: none;
            }
        }
    </style>
</head>
<body class="font-sans text-gray-800 bg-slate-50">

    <header id="header" class="bg-white/80 backdrop-blur-md sticky top-0 z-50 transition-shadow duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-indigo-600">MyLMS</a>
            <nav class="hidden md:flex space-x-8 items-center">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo $item['href']; ?>" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-300"><?php echo $item['text']; ?></a>
                <?php endforeach; ?>
                <a href="login.php" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-300">Đăng Nhập</a>
                <a href="register.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-sm hover:shadow-md">Đăng Ký</a>
            </nav>
            <button id="mobile-menu-button" class="md:hidden text-gray-700">
                <i data-feather="menu" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden px-6 pb-4 bg-white/80 backdrop-blur-md">
            <nav class="flex flex-col space-y-4">
                 <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo $item['href']; ?>" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-300"><?php echo $item['text']; ?></a>
                <?php endforeach; ?>
                <a href="login.php" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-300">Đăng Nhập</a>
                <a href="register.php" class="w-full text-center bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300">Đăng Ký</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6">
        
        <section id="home" class="text-center py-20 md:py-32">
            <div class="fade-in-section">
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                    Mở Khóa Tiềm Năng, Vững Bước Tương Lai
                </h1>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-10">
                    Tham gia các khóa học chất lượng cao, học hỏi từ chuyên gia hàng đầu và trang bị kỹ năng cần thiết cho sự nghiệp của bạn ngay hôm nay.
                </p>
                <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="#courses" class="w-full sm:w-auto bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 transform hover:scale-105 shadow-lg">Khám Phá Khóa Học</a>
                    <a href="register.php" class="w-full sm:w-auto bg-white text-gray-800 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 border border-gray-300 shadow-lg">Đăng Ký Miễn Phí</a>
                </div>
            </div>
        </section>

        <section id="features" class="py-16">
             <div class="text-center mb-12 fade-in-section">
                <h2 class="text-3xl font-bold text-gray-800">Tại Sao Chọn MyLMS?</h2>
                <p class="text-gray-600 mt-3 max-w-2xl mx-auto">Chúng tôi mang đến một môi trường học tập hiện đại, linh hoạt và hiệu quả.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($features as $feature): ?>
                <div class="bg-white p-8 rounded-xl shadow-lg text-center fade-in-section hover:shadow-indigo-100 transition-shadow duration-300">
                    <div class="text-indigo-500 mx-auto mb-4">
                        <i class="<?php echo $feature['icon']; ?> fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo $feature['title']; ?></h3>
                    <p class="text-gray-600"><?php echo $feature['description']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="courses" class="py-16">
            <div class="text-center mb-12 fade-in-section">
                <h2 class="text-3xl font-bold text-gray-800">Các Khóa Học Nổi Bật</h2>
                <p class="text-gray-600 mt-3">Những khóa học được chọn lọc kỹ nhất.</p>
            </div>
            
            <div class="swiper mySwiper">
                <div class="swiper-wrapper pb-16">
                    <?php foreach ($courses as $course): ?>
                    <div class="swiper-slide h-auto">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl fade-in-section flex flex-col h-full">
                            <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="Course Thumbnail" class="w-full h-56 object-cover">
                            <div class="p-6 flex flex-col flex-grow">
                                <h3 class="text-xl font-bold text-gray-900 mb-2 h-14"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-4">
                                    <i class="fa-solid fa-user-tie mr-2"></i>GV: <?php echo htmlspecialchars($course['instructor']); ?>
                                </p>
                                <div class="mt-auto">
                                    <a href="<?php echo htmlspecialchars($course['link']); ?>" class="font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-300 group">
                                        Xem chi tiết <span class="group-hover:ml-2 transition-all duration-300">&rarr;</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </section>

        <section id="about" class="py-16 bg-white rounded-xl shadow-lg my-16 fade-in-section">
            <div class="grid md:grid-cols-2 gap-10 items-center px-6 md:px-12">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Về Chúng Tôi</h2>
                    <p class="text-gray-600 mb-4">
                        MyLMS được thành lập với sứ mệnh mang tri thức đến cho mọi người thông qua nền tảng học tập trực tuyến hiện đại và dễ tiếp cận. Chúng tôi tin rằng giáo dục là chìa khóa để mở ra những cơ hội mới.
                    </p>
                    <p class="text-gray-600">
                       Nhóm 2 của chúng tôi bao gồm 5 sinh viên luôn nỗ lực không ngừng để cải thiện web lms trải nghiệm học tập.
                    </p>
                </div>
                <div>
                    <img src="image/team2.png" alt="About MyLMS" class="rounded-lg shadow-md">
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-800 text-white">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold text-indigo-400">MyLMS</h3>
                    <p class="text-gray-400 mt-2">Nền tảng học tập cho tương lai.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Khám Phá</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="text-gray-400 hover:text-white">Trang Chủ</a></li>
                        <li><a href="#courses" class="text-gray-400 hover:text-white">Khóa Học</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white">Giới Thiệu</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Liên Hệ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Email: support@mylms.com</li>
                        <li>Phone: (123) 456-7890</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Theo Dõi</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-500">
                <p>&copy; <?php echo date("Y"); ?> MyLMS. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="java.js" defer></script>
    <script>
      var swiper = new Swiper(".mySwiper", {
        slidesPerView: 1, spaceBetween: 30, loop: true,
        pagination: { el: ".swiper-pagination", clickable: true, },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev", },
        breakpoints: { 640: { slidesPerView: 2, spaceBetween: 20, }, 1024: { slidesPerView: 3, spaceBetween: 40, },},
      });
    </script>
</body>
</html>