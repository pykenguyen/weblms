<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/api/config.php';
require __DIR__.'/api/helpers.php';

$u = require_role(['student', 'teacher', 'admin']); // Cho phép GV/Admin làm thử
$user_id = (int)$u['id'];
$pdo = db();

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($quizId <= 0) {
    die("Thiếu ID bài kiểm tra.");
}

// Lấy thông tin quiz để hiển thị
$stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt_quiz->execute([$quizId]);
$quiz = $stmt_quiz->fetch(PDO::FETCH_ASSOC);
if (!$quiz) {
    die("Không tìm thấy bài kiểm tra.");
}


// --- XỬ LÝ KHI SINH VIÊN NỘP BÀI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['q'] ?? [];
    if (empty($answers)) {
        redirect("quiz.php?id=$quizId&error=Bạn chưa trả lời câu nào.");
    }

    try {
        $pdo->beginTransaction();

        // Lấy tất cả câu hỏi và đáp án đúng của quiz này
        $sql_correct = "
            SELECT 
                qq.id as question_id, 
                qo.id as option_id, 
                qq.points 
            FROM quiz_questions qq 
            JOIN quiz_options qo ON qq.id = qo.question_id 
            WHERE qq.quiz_id = ? AND qo.is_correct = 1
        ";
        $stmt_correct = $pdo->prepare($sql_correct);
        $stmt_correct->execute([$quizId]);
        
        $correct_answers = [];
        $max_score = 0;
        $points_map = [];

        foreach ($stmt_correct->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $correct_answers[(int)$row['question_id']][] = (int)$row['option_id'];
            $points_map[(int)$row['question_id']] = (float)$row['points'];
        }
        
        $max_score = array_sum($points_map);

        // Chấm điểm
        $score = 0.0;
        foreach ($answers as $qid => $user_options) {
            $qid = (int)$qid;
            $correct_opts = $correct_answers[$qid] ?? [];
            
            // Đảm bảo user_options luôn là mảng (cho cả radio và checkbox)
            $user_opts = is_array($user_options) ? array_map('intval', $user_options) : [(int)$user_options];
            sort($user_opts);
            sort($correct_opts);

            if ($user_opts === $correct_opts) {
                $score += $points_map[$qid] ?? 0;
            }
        }

        // Lưu kết quả vào CSDL
        $insAttempt = $pdo->prepare('INSERT INTO quiz_attempts (quiz_id, student_id, submitted_at, score, max_score) VALUES (?, ?, NOW(), ?, ?)');
        $insAttempt->execute([$quizId, $user_id, $score, $max_score]);
        $attemptId = (int)$pdo->lastInsertId();

        $insAns = $pdo->prepare('INSERT INTO quiz_answers (attempt_id, question_id, option_id) VALUES (?, ?, ?)');
        foreach ($answers as $qid => $user_options) {
            $user_opts = is_array($user_options) ? $user_options : [$user_options];
            foreach ($user_opts as $oid) {
                $insAns->execute([$attemptId, (int)$qid, (int)$oid]);
            }
        }

        $pdo->commit();

        // Chuyển hướng đến trang kết quả
        redirect("quiz_results.php?attempt_id=$attemptId");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Lỗi khi nộp bài: " . $e->getMessage());
    }
}


// --- HIỂN THỊ ĐỀ BÀI CHO SINH VIÊN ---
$stmt_questions = $pdo->prepare("
    SELECT qq.id, qq.question_text, qq.type, qq.points, qo.id as option_id, qo.option_text 
    FROM quiz_questions qq 
    JOIN quiz_options qo ON qq.id = qo.question_id 
    WHERE qq.quiz_id = ? 
    ORDER BY qq.id, qo.id
");
$stmt_questions->execute([$quizId]);

$questions = [];
while ($row = $stmt_questions->fetch(PDO::FETCH_ASSOC)) {
    $qid = $row['id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'points' => $row['points'],
            'options' => []
        ];
    }
    $questions[$qid]['options'][] = [
        'id' => $row['option_id'],
        'text' => $row['option_text']
    ];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Làm bài kiểm tra: <?php echo htmlspecialchars($quiz['title']); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
  <header class="bg-white/80 backdrop-blur sticky top-0 z-50 shadow">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="trang-chu.php" class="text-2xl font-bold text-indigo-600">MyLMS</a>
      <nav class="space-x-6">
        <a href="trang-chu.php" class="text-gray-700 hover:text-indigo-600">Bảng điều khiển</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-6 py-8">
    <a href="javascript:history.back()" class="text-indigo-600 font-semibold">← Quay lại</a>

    <h1 class="text-3xl font-extrabold mt-3"><?php echo htmlspecialchars($quiz['title']); ?></h1>
    <p class="mt-1 text-gray-600"><?php echo htmlspecialchars($quiz['description']); ?></p>
    <p class="mt-1 text-sm text-gray-500">Thời gian: <?php echo $quiz['time_limit_minutes'] ?: 'Không giới hạn'; ?> phút • Điểm tối đa: <?php echo $quiz['total_points']; ?></p>

    <form method="POST" action="quiz.php?id=<?php echo $quizId; ?>" class="mt-8 space-y-6">
      <?php if (empty($questions)): ?>
          <p class="text-gray-500">Bài kiểm tra chưa có câu hỏi.</p>
      <?php else: ?>
          <?php foreach ($questions as $qid => $q): ?>
              <section class="bg-white border rounded-xl p-5">
                <h3 class="font-semibold mb-2">
                    Câu <?php echo count($questions) - count($questions) + (array_search($qid, array_keys($questions)) + 1); ?>: 
                    <?php echo htmlspecialchars($q['question_text']); ?>
                    <span class="text-xs text-gray-500">(<?php echo $q['points']; ?>đ)</span>
                    <?php if ($q['type'] === 'multiple'): ?>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Chọn nhiều</span>
                    <?php endif; ?>
                </h3>
                <div class="space-y-1">
                    <?php foreach ($q['options'] as $o): ?>
                        <label class="flex items-center gap-2 py-1">
                            <?php if ($q['type'] === 'multiple'): ?>
                                <input type="checkbox" name="q[<?php echo $qid; ?>][]" value="<?php echo $o['id']; ?>" class="h-4 w-4">
                            <?php else: ?>
                                <input type="radio" name="q[<?php echo $qid; ?>]" value="<?php echo $o['id']; ?>" class="h-4 w-4" required>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($o['text']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
              </section>
          <?php endforeach; ?>
      <?php endif; ?>

      <div class="pt-4">
        <?php if (!empty($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
          Nộp bài
        </button>
      </div>
    </form>
  </main>
</body>
</html>