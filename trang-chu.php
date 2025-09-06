<?php
declare(strict_types=1);

// Use shared config and helper files
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// Require user to be logged in
$user = require_login();
$user_id = (int)$user['id'];
$pdo = db();

// --- FETCH ENROLLED COURSES and QUIZZES ---
$sql_courses = "
    SELECT
        c.id, c.title, c.thumbnail, t.name as teacher_name,
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM progress_tracking WHERE user_id=? AND lesson_id IN (SELECT id FROM lessons WHERE course_id=c.id) AND is_completed=1) as completed_lessons,
        (SELECT COUNT(*) FROM quizzes WHERE course_id = c.id) as total_quizzes
    FROM courses c
    JOIN course_user cu ON c.id=cu.course_id
    LEFT JOIN users t ON c.teacher_id=t.id
    WHERE cu.user_id=?
";
$stmt_courses = $pdo->prepare($sql_courses);
$stmt_courses->execute([$user_id, $user_id]);
$enrolled_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH UPCOMING ASSIGNMENTS ---
$sql_assignments = "
    SELECT a.id, a.title as assignment_title, c.title as course_title, a.due_date
    FROM assignments a
    JOIN courses c ON a.course_id=c.id
    WHERE a.course_id IN (SELECT course_id FROM course_user WHERE user_id=?)
      AND a.due_date > NOW()
    ORDER BY a.due_date ASC
    LIMIT 5
";
$stmt_assignments = $pdo->prepare($sql_assignments);
$stmt_assignments->execute([$user_id]);
$upcoming_assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH NOTIFICATIONS ---
$stmt_unread = $pdo->prepare("
    SELECT COUNT(*) FROM notifications
    WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW()) AND is_read=0
");
$stmt_unread->execute([$user_id]);
$unread_count = (int)$stmt_unread->fetchColumn();

$stmt_notif_recent = $pdo->prepare("
    SELECT id, title, message, created_at, is_read
    FROM notifications
    WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW())
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt_notif_recent->execute([$user_id]);
$recent_notifications = $stmt_notif_recent->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - MyLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .progress-bar-container { background:#e5e7eb; height:8px; border-radius:4px; }
        .progress-bar { background:#6366f1; height:8px; border-radius:4px; }
        .dropdown { position:relative; display:inline-block; }
        .dropdown-content { display:none; position:absolute; right:0; background:#fff; min-width:250px; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:1000; border-radius:6px; overflow:hidden; }
        .dropdown-content.show { display:block; }
        .notif-item { padding:0.75rem 1rem; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; }
        .notif-item.unread { background:#f0f5ff; }
        .notif-item:last-child { border-bottom:none; }
        .course-card { background:#fff; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05); overflow:hidden; transition:transform 0.2s; }
        .course-card:hover { transform:translateY(-5px); }
        .course-thumbnail { width:100%; height:200px; object-fit:cover; }
        .quiz-card { background:#fff; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05); padding: 1rem; transition:transform 0.2s; }
        .quiz-card:hover { transform:translateY(-5px); }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="trang-chu.php" class="text-2xl font-bold text-indigo-600">MyLMS</a>
            <nav class="flex space-x-4 items-center">
                <a href="trang-chu.php" class="text-indigo-600 font-semibold">Dashboard</a>
                <a href="courses.php" class="text-gray-600 hover:text-indigo-600">Khám Phá</a>
                <a href="profile.php" class="text-gray-600 hover:text-indigo-600">Hồ Sơ</a>

                <div class="dropdown">
                    <button id="notifBtn" class="relative px-3 py-1 rounded bg-indigo-600 text-white">
                        🔔
                        <?php if($unread_count > 0): ?>
                            <span class="absolute top-0 right-0 inline-block w-4 h-4 text-xs text-white bg-red-500 rounded-full text-center leading-4"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notifDropdown" class="dropdown-content">
                        <?php if(empty($recent_notifications)): ?>
                            <div class="p-4 text-gray-500">Không có thông báo.</div>
                        <?php else: ?>
                            <?php foreach($recent_notifications as $n): ?>
                                <div class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $n['id']; ?>">
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($n['title']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($n['message']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></p>
                                    </div>
                                    <?php if(!$n['is_read']): ?>
                                        <button class="mark-read-btn px-2 py-1 text-indigo-600 font-semibold">Đã đọc</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="logout.php" class="text-gray-600 hover:text-indigo-600">Đăng Xuất</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Chào mừng trở lại, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p class="text-lg text-gray-600">Hãy tiếp tục hành trình học tập của bạn.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Các Khóa Học Của Tôi</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php if(empty($enrolled_courses)): ?>
                            <div class="md:col-span-2 text-center bg-white p-8 rounded-lg shadow-sm">
                                <p class="text-gray-600">Bạn chưa được ghi danh vào khóa học nào.</p>
                                <a href="courses.php" class="mt-4 inline-block bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700">Khám phá các khóa học</a>
                            </div>
                        <?php else: ?>
                            <?php foreach($enrolled_courses as $course): 
                                $progress = $course['total_lessons'] > 0 ? round($course['completed_lessons'] / $course['total_lessons'] * 100) : 0;
                                $thumbnail = $course['thumbnail'] ?: 'https://placehold.co/600x400/e0e7ff/4338ca?text='.urlencode($course['title']);
                            ?>
                                <div class="course-card">
                                    <a href="chi-tiet-khoa-hoc.php?id=<?php echo $course['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="Course Thumbnail" class="course-thumbnail">
                                    </a>
                                    <div class="p-6">
                                        <h3 class="text-xl font-bold text-gray-900 mb-2 h-14"><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p class="text-gray-600 text-sm mb-4">GV: <?php echo htmlspecialchars($course['teacher_name'] ?? 'Chưa xác định'); ?></p>
                                        <div class="mb-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span>Tiến độ</span>
                                                <span><?php echo $progress; ?>%</span>
                                            </div>
                                            <div class="progress-bar-container">
                                                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                                            </div>
                                        </div>
                                        <a href="chi-tiet-khoa-hoc.php?id=<?php echo $course['id']; ?>" class="font-semibold text-indigo-600 hover:text-indigo-800">Vào học →</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="mt-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Bài Kiểm Tra Sắp Tới</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php if (empty($enrolled_courses)): ?>
                            <div class="md:col-span-2 text-center bg-white p-8 rounded-lg shadow-sm">
                                <p class="text-gray-600">Bạn chưa có bài kiểm tra nào.</p>
                            </div>
                        <?php else: ?>
                            <?php 
                                $has_quizzes = false;
                                foreach ($enrolled_courses as $course): 
                                    if ($course['total_quizzes'] > 0) {
                                        $has_quizzes = true;
                                        $stmt_quizzes = $pdo->prepare("SELECT id, title, total_points FROM quizzes WHERE course_id = ? ORDER BY id DESC");
                                        $stmt_quizzes->execute([$course['id']]);
                                        $quizzes = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($quizzes as $quiz):
                            ?>
                                            <div class="quiz-card">
                                                <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                                <p class="text-sm text-gray-600">Khóa học: <?php echo htmlspecialchars($course['title']); ?></p>
                                                <div class="mt-2 text-sm text-gray-500">
                                                    Điểm tối đa: <?php echo $quiz['total_points']; ?>
                                                </div>
                                                <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="mt-3 inline-block font-semibold text-indigo-600 hover:text-indigo-800">Làm bài →</a>
                                            </div>
                            <?php
                                        endforeach;
                                    }
                                endforeach; 
                                if (!$has_quizzes):
                            ?>
                                <div class="md:col-span-2 text-center bg-white p-8 rounded-lg shadow-sm">
                                    <p class="text-gray-600">Hiện chưa có bài kiểm tra nào cho các khóa học của bạn.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
                </div>

            <div class="lg:col-span-1">
                <aside class="bg-white p-6 rounded-lg shadow-sm sticky top-24">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">🔔 Bài Tập Sắp Tới Hạn</h3>
                    <ul class="space-y-4">
                        <?php if(empty($upcoming_assignments)): ?>
                            <li><p class="text-gray-500">Tuyệt vời! Bạn không có bài tập nào sắp tới hạn.</p></li>
                        <?php else: ?>
                            <?php foreach($upcoming_assignments as $assignment):
                                $dueDate = new DateTime($assignment['due_date']);
                                $formattedDate = $dueDate->format('H:i, d/m/Y');
                            ?>
                                <li class="border-l-4 border-red-400 pl-4">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($assignment['assignment_title']); ?></p>
                                    <p class="text-sm text-gray-500">Khóa học: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                    <p class="text-sm text-red-600 font-medium">Hạn chót: <?php echo $formattedDate; ?></p>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </aside>
            </div>
        </div>
    </main>
    
    <script>
        feather.replace();

        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        notifBtn.addEventListener('click', () => {
            notifDropdown.classList.toggle('show');
        });

        window.addEventListener('click', e => {
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });

        notifDropdown.addEventListener('click', e => {
            const btn = e.target.closest('.mark-read-btn');
            if (!btn) return;
            const item = btn.closest('.notif-item');
            if (!item) return;
            const id = parseInt(item.dataset.id);
            markAsRead(id);
        });

        async function markAsRead(id) {
            try {
                const res = await fetch('notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'read', ids: [id] })
                });
                const data = await res.json();
                if (data.success) {
                    const item = notifDropdown.querySelector(`.notif-item[data-id='${id}']`);
                    if (item) {
                        item.classList.remove('unread');
                        const btn = item.querySelector('.mark-read-btn');
                        if (btn) btn.remove();
                    }
                    const badge = notifBtn.querySelector('span');
                    if (badge) {
                        let count = parseInt(badge.textContent) - 1;
                        if (count > 0) badge.textContent = count;
                        else badge.remove();
                    }
                }
            } catch (err) {
                console.error('Error marking notification as read', err);
            }
        }

        async function fetchNotifications() {
            try {
                const res = await fetch('notifications.php', { credentials: 'same-origin' });
                if (!res.ok) return;
                const data = await res.json();
                let badge = notifBtn.querySelector('span');
                if (data.unread > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'absolute top-0 right-0 inline-block w-4 h-4 text-xs text-white bg-red-500 rounded-full text-center leading-4';
                        notifBtn.appendChild(badge);
                    }
                    badge.textContent = data.unread;
                } else if (badge) {
                    badge.remove();
                }
                notifDropdown.innerHTML = '';
                if (data.recent && data.recent.length > 0) {
                    data.recent.forEach(n => {
                        const div = document.createElement('div');
                        div.className = 'notif-item ' + (n.is_read ? '' : 'unread');
                        div.dataset.id = n.id;
                        div.innerHTML = `
                            <div>
                                <p class="font-semibold">${n.title}</p>
                                <p class="text-sm text-gray-600">${n.message}</p>
                                <p class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString('vi-VN')}</p>
                            </div>
                            ${n.is_read ? '' : '<button class="mark-read-btn px-2 py-1 text-indigo-600 font-semibold">Đã đọc</button>'}
                        `;
                        notifDropdown.appendChild(div);
                    });
                } else {
                    const noNotifDiv = document.createElement('div');
                    noNotifDiv.className = 'p-4 text-gray-500';
                    noNotifDiv.textContent = 'Không có thông báo.';
                    notifDropdown.appendChild(noNotifDiv);
                }
            } catch (err) {
                console.error('Error fetching notifications', err);
            }
        }

        fetchNotifications();
        setInterval(fetchNotifications, 5000);
    </script>
</body>
</html>