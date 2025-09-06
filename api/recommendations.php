<?php
declare(strict_types=1);

// Import necessary files for configuration and helper functions
echo "Recommendations script is running!";
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// 1. Authenticate the user. Only students can request recommendations.
$user = require_role(['student']);
$student_id = (int)$user['id'];
$pdo = db();

// 2. Find assignments where the student scored below a certain grade (e.g., < 8).
// We are using a threshold of 8 here, but you can adjust this value.
$sql_low_score_assignments = "
    SELECT a.id, a.course_id
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    WHERE s.student_id = ? AND s.grade IS NOT NULL AND s.grade < 8
    GROUP BY a.id, a.course_id
";

$stmt_low_score = $pdo->prepare($sql_low_score_assignments);
$stmt_low_score->execute([$student_id]);
$weakness_areas = $stmt_low_score->fetchAll(PDO::FETCH_ASSOC);

$course_ids = array_column($weakness_areas, 'course_id');

// If the student has no low scores, we can't make a targeted recommendation.
if (empty($course_ids)) {
    json(['message' => 'Bạn không có điểm thấp nào để gợi ý.', 'recommendations' => []]);
    exit();
}

// 3. Find other assignments from those same courses that the student hasn't submitted yet.
$in_clause = implode(',', array_fill(0, count($course_ids), '?'));

$sql_recommendations = "
    SELECT a.id, a.title, a.description, a.due_date, c.title as course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.course_id IN ($in_clause)
    AND a.id NOT IN (
        SELECT assignment_id FROM submissions WHERE student_id = ?
    )
    ORDER BY a.due_date ASC
    LIMIT 5
";

$params = array_merge($course_ids, [$student_id]);
$stmt_reco = $pdo->prepare($sql_recommendations);
$stmt_reco->execute($params);
$recommendations = $stmt_reco->fetchAll(PDO::FETCH_ASSOC);

// 4. Return the recommendations as a JSON response.
json(['recommendations' => $recommendations]);