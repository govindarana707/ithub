<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';
require_once '../models/Discussion.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only students can access their analytics
if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$courseId = $_GET['course_id'] ?? 0;
$studentId = $_SESSION['user_id'];

if (!$courseId || !is_numeric($courseId)) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit;
}

$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();

// Verify student is enrolled in the course
$enrollment = $course->getEnrollment($studentId, $courseId);
if (empty($enrollment) && getUserRole() !== 'admin') {
    echo json_encode(['error' => 'Not enrolled in this course']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get comprehensive analytics
$analytics = [];

// 1. Course Statistics
$analytics['course_stats'] = $course->getCourseStatistics($courseId);

// 2. Learning Activity Timeline (Last 30 Days)
$stmt = $conn->prepare("
    SELECT DATE(completed_at) as date, COUNT(*) as lessons_completed,
           AVG(TIME_TO_SEC(TIMEDIFF(completed_at, started_at))) as avg_time_spent
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
    AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY date DESC
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['activity_timeline'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 3. Quiz Performance
$stmt = $conn->prepare("
    SELECT q.title, qa.percentage, qa.completed_at, qa.passed,
           q.time_limit, qa.time_taken
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.student_id = ? AND q.course_id = ?
    ORDER BY qa.completed_at DESC
    LIMIT 10
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['quiz_performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 4. Lesson Progress Details
$stmt = $conn->prepare("
    SELECT l.title, l.lesson_type, lp.completed, lp.started_at, lp.completed_at,
           lp.time_spent, lp.score
    FROM lessons l
    LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
    WHERE l.course_id = ?
    ORDER BY l.order_number ASC
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['lesson_progress'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 5. Learning Patterns
$stmt = $conn->prepare("
    SELECT HOUR(completed_at) as hour, COUNT(*) as count
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
    GROUP BY HOUR(completed_at)
    ORDER BY hour
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['learning_patterns'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 6. Progress by Category
$stmt = $conn->prepare("
    SELECT c.name as category, COUNT(l.id) as total_lessons,
           SUM(CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END) as completed_lessons
    FROM lessons l
    LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
    LEFT JOIN courses_new c ON l.course_id = c.id
    WHERE l.course_id = ?
    GROUP BY c.name
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['category_progress'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 7. Time Spent Analysis
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN lp.completed = 1 THEN lp.time_spent ELSE 0 END) as total_time_spent,
        AVG(CASE WHEN lp.completed = 1 THEN lp.time_spent END) as avg_time_per_lesson,
        MAX(CASE WHEN lp.completed = 1 THEN lp.time_spent END) as max_time_per_lesson,
        MIN(CASE WHEN lp.completed = 1 THEN lp.time_spent END) as min_time_per_lesson
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $timeData = $stmt->get_result()->fetch_assoc();
    $analytics['time_analysis'] = $timeData;
}

// 8. Streak Analysis
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT DATE(completed_at)) as total_active_days,
        MAX(CASE WHEN completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_this_week,
        MAX(CASE WHEN completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_this_month
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $streakData = $stmt->get_result()->fetch_assoc();
    $analytics['streak_analysis'] = $streakData;
}

// 9. Completion Rate by Lesson Type
$stmt = $conn->prepare("
    SELECT l.lesson_type, COUNT(l.id) as total,
           SUM(CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END) as completed,
           ROUND(SUM(CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(l.id), 2) as completion_rate
    FROM lessons l
    LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
    WHERE l.course_id = ?
    GROUP BY l.lesson_type
");

if ($stmt) {
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $analytics['completion_by_type'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 10. Recent Activity Summary
$stmt = $conn->prepare("
    (SELECT 'lesson_completed' as activity_type, l.title as activity_title, 
            lp.completed_at as activity_date, 'success' as status
     FROM lesson_progress lp
     JOIN lessons l ON lp.lesson_id = l.id
     WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
     ORDER BY lp.completed_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'quiz_attempt' as activity_type, q.title as activity_title,
            qa.completed_at as activity_date, 
            CASE WHEN qa.passed = 1 THEN 'success' ELSE 'warning' END as status
     FROM quiz_attempts qa
     JOIN quizzes q ON qa.quiz_id = q.id
     WHERE qa.student_id = ? AND q.course_id = ?
     ORDER BY qa.completed_at DESC LIMIT 3)
    ORDER BY activity_date DESC
    LIMIT 5
");

if ($stmt) {
    $stmt->bind_param("iiii", $studentId, $courseId, $studentId, $courseId);
    $stmt->execute();
    $analytics['recent_activity'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Calculate derived metrics
$analytics['derived_metrics'] = [
    'total_lessons' => $analytics['course_stats']['total_lessons'] ?? 0,
    'completed_lessons' => $analytics['course_stats']['completed_lessons'] ?? 0,
    'avg_progress' => $analytics['course_stats']['avg_progress'] ?? 0,
    'completion_percentage' => ($analytics['course_stats']['total_lessons'] ?? 0) > 0 ? 
        round(($analytics['course_stats']['completed_lessons'] ?? 0) / ($analytics['course_stats']['total_lessons'] ?? 1) * 100, 2) : 0,
    'estimated_completion_date' => calculateEstimatedCompletion($analytics),
    'learning_efficiency' => calculateLearningEfficiency($analytics),
    'engagement_score' => calculateEngagementScore($analytics)
];

echo json_encode($analytics);

function calculateEstimatedCompletion($analytics) {
    if (empty($analytics['activity_timeline'])) {
        return null;
    }
    
    $totalDays = count($analytics['activity_timeline']);
    if ($totalDays < 7) {
        return null;
    }
    
    $avgLessonsPerDay = array_sum(array_column($analytics['activity_timeline'], 'lessons_completed')) / $totalDays;
    $remainingLessons = ($analytics['course_stats']['total_lessons'] ?? 0) - ($analytics['course_stats']['completed_lessons'] ?? 0);
    
    if ($avgLessonsPerDay > 0 && $remainingLessons > 0) {
        $daysToComplete = ceil($remainingLessons / $avgLessonsPerDay);
        return date('Y-m-d', strtotime("+$daysToComplete days"));
    }
    
    return null;
}

function calculateLearningEfficiency($analytics) {
    $timeSpent = $analytics['time_analysis']['total_time_spent'] ?? 0;
    $completedLessons = $analytics['course_stats']['completed_lessons'] ?? 0;
    
    if ($completedLessons > 0) {
        $avgTimePerLesson = $timeSpent / $completedLessons;
        // Efficiency score based on time spent vs expected (assuming 30 minutes per lesson as baseline)
        $efficiency = max(0, min(100, 100 - (($avgTimePerLesson - 30) / 30) * 100));
        return round($efficiency, 2);
    }
    
    return 0;
}

function calculateEngagementScore($analytics) {
    $score = 0;
    
    // Activity consistency (40%)
    $activeDays = $analytics['streak_analysis']['total_active_days'] ?? 0;
    $score += min(40, $activeDays * 2);
    
    // Quiz participation (30%)
    $quizAttempts = count($analytics['quiz_performance'] ?? []);
    $score += min(30, $quizAttempts * 10);
    
    // Discussion participation (20%)
    $discussionCount = $analytics['course_stats']['discussion_count'] ?? 0;
    $score += min(20, $discussionCount * 4);
    
    // Regularity (10%)
    if (!empty($analytics['learning_patterns'])) {
        $patternScore = min(10, count($analytics['learning_patterns']) * 2);
        $score += $patternScore;
    }
    
    return round($score, 2);
}
?>
