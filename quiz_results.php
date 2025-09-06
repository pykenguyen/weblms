<?php
declare(strict_types=1);

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

// --- Kiểm tra user ---
$u = require_role(['student','teacher','admin']);
$pdo = db();

// --- Lấy param ---
$attemptId = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$quizId    = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

$page_data = []; // Biến chứa dữ liệu HTML

if ($attemptId > 0) {
    // --- Xem chi tiết ---
    $st = $pdo->prepare(
        'SELECT a.*, u.name AS student_name, u.email AS student_email,
                qz.title AS quiz_title
         FROM quiz_attempts a
         JOIN users u ON u.id = a.student_id
         JOIN quizzes qz ON qz.id = a.quiz_id
         WHERE a.id = ?'
    );
    $st->execute([$attemptId]);
    $attempt_data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$attempt_data || (($u['role'] === 'student') && (int)$attempt_data['student_id'] !== (int)$u['id'])) {
        die("Không tìm thấy hoặc không có quyền xem kết quả này.");
    }

    // Lấy câu hỏi
    $qs = $pdo->prepare('SELECT id, question_text, type, points FROM quiz_questions WHERE quiz_id=? ORDER BY id');
    $qs->execute([(int)$attempt_data['quiz_id']]);
    $questions = $qs->fetchAll(PDO::FETCH_ASSOC);

    $optSt = $pdo->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id=? ORDER BY id');
    $ansSt = $pdo->prepare('SELECT option_id FROM quiz_answers WHERE attempt_id=? AND question_id=?');

    $question_items = [];
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $optSt->execute([$qid]);
        $opts = $optSt->fetchAll(PDO::FETCH_ASSOC);

        $ansSt->execute([$attemptId, $qid]);
        $picked = array_map('intval', $ansSt->fetchAll(PDO::FETCH_COLUMN));

        $question_items[] = [
            'question_text' => $q['question_text'],
            'points'        => (float)($q['points'] ?? 1),
            'options'       => $opts,
            'picked'        => $picked
        ];
    }

    $page_data = ['mode' => 'detail', 'attempt' => $attempt_data, 'questions' => $question_items];

} elseif ($quizId > 0) {
    // --- Xem danh sách ---
    if ($u['role'] === 'student') {
        $st = $pdo->prepare('SELECT a.* FROM quiz_attempts a WHERE a.quiz_id=? AND a.student_id=? ORDER BY a.id DESC');
        $st->execute([$quizId, (int)$u['id']]);
    } else {
        $st = $pdo->prepare('SELECT a.*, u.name AS student_name, u.email AS student_email
                             FROM quiz_attempts a
                             JOIN users u ON u.id = a.student_id
                             WHERE a.quiz_id=?
                             ORDER BY a.id DESC');
        $st->execute([$quizId]);
    }
    $attempts = $st->fetchAll(PDO::FETCH_ASSOC);
    $page_data = ['mode' => 'list', 'items' => $attempts];

} else {
    die("Thiếu attempt_id hoặc quiz_id.");
}

// --- Hàm định dạng ngày giờ ---
function format_date(?string $v): string {
    if (!$v) return '—';
    try {
        return (new DateTime($v))->format('H:i:s d/m/Y');
    } catch (Exception $e) {
        return '—';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Kết quả bài kiểm tra</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<header class="bg-white/90 backdrop-blur sticky top-0 z-50 shadow">
  <div class="container mx-auto px-6 py-4 flex items-center justify-between">
    <a href="trang-chu.php" class="text-indigo-600 font-bold text-2xl">MyLMS</a>
    <nav class="space-x-6">
      <a href="trang-chu.php" class="text-gray-700 hover:text-indigo-600">Bảng điều khiển</a>
    </nav>
  </div>
</header>

<main class="container mx-auto px-6 py-8 space-y-8">
  <a href="javascript:history.back()" class="text-indigo-600 font-semibold">← Quay lại</a>

  <?php if ($page_data['mode'] === 'list'): ?>
  <section id="list-view">
    <h1 class="text-2xl font-bold mb-4">Kết quả các lần làm bài</h1>
    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="text-left px-4 py-2">Attempt ID</th>
            <?php if ($u['role'] !== 'student'): ?><th class="text-left px-4 py-2">Học viên</th><?php endif; ?>
            <th class="text-left px-4 py-2">Điểm</th>
            <th class="text-left px-4 py-2">Thời gian nộp</th>
            <th class="text-left px-4 py-2">Xem</th>
          </tr>
        </thead>
        <tbody>
            <?php if (empty($page_data['items'])): ?>
                <tr><td colspan="5" class="px-4 py-3 text-gray-500">Chưa có bài nộp.</td></tr>
            <?php else: ?>
                <?php foreach ($page_data['items'] as $it): ?>
                <tr class="border-b">
                    <td class="px-4 py-2 font-medium"><?php echo $it['id']; ?></td>
                    <?php if ($u['role'] !== 'student'): ?>
                    <td class="px-4 py-2">
                        <?php echo htmlspecialchars($it['student_name'] ?? ('SV #'.$it['student_id'])); ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($it['student_email'] ?? ''); ?></div>
                    </td>
                    <?php endif; ?>
                    <td class="px-4 py-2"><?php echo $it['score']; ?>/<?php echo $it['max_score']; ?></td>
                    <td class="px-4 py-2"><?php echo format_date($it['submitted_at']); ?></td>
                    <td class="px-4 py-2">
                        <a class="text-indigo-600 font-semibold" href="quiz_results.php?attempt_id=<?php echo $it['id']; ?>">Xem chi tiết</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($page_data['mode'] === 'detail'): ?>
  <section id="detail-view">
    <?php
        $a = $page_data['attempt'];
        $started_at = !empty($a['started_at']) ? new DateTime($a['started_at']) : null;
        $submitted_at = !empty($a['submitted_at']) ? new DateTime($a['submitted_at']) : null;
        $minutes = '—';
        if ($started_at && $submitted_at) {
            $minutes = round(($submitted_at->getTimestamp() - $started_at->getTimestamp()) / 60);
        }
    ?>
    <div class="flex flex-wrap items-center gap-4">
      <h1 class="text-2xl font-bold">Kết quả chi tiết: <?php echo htmlspecialchars($a['quiz_title']); ?></h1>
      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-700">Điểm: <?php echo $a['score']; ?>/<?php echo $a['max_score']; ?></span>
      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700">Thời gian làm bài: <?php echo $minutes; ?> phút</span>
    </div>

    <div class="mt-6 space-y-6">
        <?php foreach ($page_data['questions'] as $i => $q): ?>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="font-semibold mb-2">Câu <?php echo $i + 1; ?> (<?php echo $q['points']; ?>đ): <?php echo htmlspecialchars($q['question_text']); ?></div>
                <ul class="space-y-2">
                    <?php 
                        $picked_options = array_flip($q['picked']); // an toàn hơn SplFixedArray
                    ?>
                    <?php foreach ($q['options'] as $o):
                        $is_picked = isset($picked_options[(int)$o['id']]);
                        $is_correct = (int)$o['is_correct'] === 1;

                        $class = 'bg-gray-50';
                        $tag = '<span class="text-gray-400 text-xs ml-2">Không chọn</span>';
                        if ($is_correct) {
                            $class = 'bg-green-50 border border-green-300';
                            $tag = '<span class="text-green-700 text-xs ml-2">Đáp án đúng</span>';
                        }
                        if ($is_picked) {
                            $class = $is_correct ? 'bg-green-50 border border-green-300' : 'bg-red-50 border border-red-300';
                            $tag = $is_correct ? '<span class="text-green-700 text-xs ml-2">Bạn chọn (đúng)</span>' : '<span class="text-red-700 text-xs ml-2">Bạn chọn (sai)</span>';
                        }
                    ?>
                    <li class="px-3 py-2 rounded border <?php echo $class; ?>">
                        <?php echo htmlspecialchars($o['option_text']) . $tag; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</main>
</body>
</html>
