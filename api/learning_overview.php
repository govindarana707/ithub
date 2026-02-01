<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Database.php';
require_once '../models/User.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';
require_once '../models/ProgressTracking.php';
require_once '../models/RecommendationSystem.php';

// Ensure user is logged in
requireUser();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnection();

try {
    // Get comprehensive learning overview data
    $overviewData = [
        'user_profile' => getUserProfile($conn, $userId),
        'learning_stats' => getLearningStatistics($conn, $userId),
        'progress_metrics' => getProgressMetrics($conn, $userId),
        'engagement_data' => getEngagementData($conn, $userId),
        'skill_analysis' => getSkillAnalysis($conn, $userId),
        'learning_trends' => getLearningTrends($conn, $userId),
        'achievements' => getAchievements($conn, $userId),
        'recommendations' => getSmartRecommendations($userId),
        'time_management' => getTimeManagement($conn, $userId)
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $overviewData,
        'timestamp' => date('Y-m-d H:i:s'),
        'cache_time' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
    ]);
    
} catch (Exception $e) {
    error_log("Learning Overview Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load learning overview data',
        'error' => $e->getMessage()
    ]);
}

/**
 * Get comprehensive user profile for learning
 */
function getUserProfile($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM enrollments WHERE student_id = ?) as total_enrollments,
               (SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'completed') as completed_courses,
               (SELECT AVG(progress_percentage) FROM enrollments WHERE student_id = ? AND progress_percentage > 0) as avg_progress,
               (SELECT SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100 
                FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id 
                WHERE qa.student_id = ? AND qa.status = 'completed') as quiz_success_rate
        FROM users_new u
        WHERE u.id = ?
    ");
    
    $stmt->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    
    // Calculate learning velocity (courses completed per month)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed_count,
               MIN(DATEDIFF(NOW(), completed_at)) as days_since_first
        FROM enrollments 
        WHERE student_id = ? AND status = 'completed' AND completed_at IS NOT NULL
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $velocityData = $stmt->get_result()->fetch_assoc();
    
    $profile['learning_velocity'] = $velocityData['days_since_first'] > 0 ? 
        ($velocityData['completed_count'] / ($velocityData['days_since_first'] / 30)) : 0;
    
    return $profile;
}

/**
 * Get detailed learning statistics
 */
function getLearningStatistics($conn, $userId) {
    $stats = [];
    
    // Course completion stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_enrolled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            AVG(progress_percentage) as avg_progress,
            MAX(progress_percentage) as best_progress,
            MIN(CASE WHEN progress_percentage > 0 THEN progress_percentage END) as min_progress
        FROM enrollments
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $courseStats = $stmt->get_result()->fetch_assoc();
    
    $stats['courses'] = [
        'total_enrolled' => (int)($courseStats['total_enrolled'] ?? 0),
        'completed' => (int)($courseStats['completed'] ?? 0),
        'in_progress' => (int)($courseStats['total_enrolled'] ?? 0) - (int)($courseStats['completed'] ?? 0),
        'completion_rate' => $courseStats['total_enrolled'] > 0 ? 
            round(($courseStats['completed'] / $courseStats['total_enrolled']) * 100, 1) : 0,
        'avg_progress' => round($courseStats['avg_progress'] ?? 0, 1),
        'best_progress' => round($courseStats['best_progress'] ?? 0, 1),
        'min_progress' => round($courseStats['min_progress'] ?? 0, 1)
    ];
    
    // Quiz performance stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            AVG(percentage) as avg_score,
            MAX(percentage) as best_score,
            MIN(percentage) as worst_score,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed,
            SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed
        FROM quiz_attempts
        WHERE student_id = ? AND status = 'completed'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $quizStats = $stmt->get_result()->fetch_assoc();
    
    $stats['quizzes'] = [
        'total_attempts' => (int)($quizStats['total_attempts'] ?? 0),
        'avg_score' => round($quizStats['avg_score'] ?? 0, 1),
        'best_score' => round($quizStats['best_score'] ?? 0, 1),
        'worst_score' => round($quizStats['worst_score'] ?? 0, 1),
        'passed' => (int)($quizStats['passed'] ?? 0),
        'failed' => (int)($quizStats['failed'] ?? 0),
        'success_rate' => $quizStats['total_attempts'] > 0 ? 
            round(($quizStats['passed'] / $quizStats['total_attempts']) * 100, 1) : 0
    ];
    
    // Time investment stats
    $stmt = $conn->prepare("
        SELECT 
            SUM(l.duration_minutes) as total_minutes,
            COUNT(DISTINCT DATE(lp.completed_at)) as active_days,
            AVG(l.duration_minutes) as avg_lesson_duration
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.student_id = ? AND lp.completed = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $timeStats = $stmt->get_result()->fetch_assoc();
    
    $stats['time'] = [
        'total_minutes' => (int)($timeStats['total_minutes'] ?? 0),
        'total_hours' => round(($timeStats['total_minutes'] ?? 0) / 60, 1),
        'active_days' => (int)($timeStats['active_days'] ?? 0),
        'avg_lesson_duration' => round($timeStats['avg_lesson_duration'] ?? 0, 1),
        'learning_efficiency' => ($timeStats['total_minutes'] ?? 0) > 0 ? 
            round(($courseStats['completed'] ?? 0) / (($timeStats['total_minutes'] ?? 0) / 60), 2) : 0
    ];
    
    return $stats;
}

/**
 * Get detailed progress metrics
 */
function getProgressMetrics($conn, $userId) {
    $metrics = [];
    
    // Progress by category
    $stmt = $conn->prepare("
        SELECT 
            cat.name as category,
            COUNT(e.id) as enrolled_courses,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
            AVG(e.progress_percentage) as avg_progress
        FROM enrollments e
        JOIN courses_new c ON e.course_id = c.id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE e.student_id = ?
        GROUP BY cat.id, cat.name
        ORDER BY avg_progress DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $categoryProgress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $metrics['by_category'] = array_map(function($cat) {
        return [
            'category' => $cat['category'],
            'enrolled' => (int)$cat['enrolled_courses'],
            'completed' => (int)$cat['completed_courses'],
            'completion_rate' => $cat['enrolled_courses'] > 0 ? 
                round(($cat['completed_courses'] / $cat['enrolled_courses']) * 100, 1) : 0,
            'avg_progress' => round($cat['avg_progress'] ?? 0, 1)
        ];
    }, $categoryProgress);
    
    // Progress distribution
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN progress_percentage >= 90 THEN 'Excellent (90-100%)'
                WHEN progress_percentage >= 75 THEN 'Good (75-89%)'
                WHEN progress_percentage >= 50 THEN 'Average (50-74%)'
                WHEN progress_percentage >= 25 THEN 'Below Average (25-49%)'
                ELSE 'Just Started (0-24%)'
            END as progress_range,
            COUNT(*) as count
        FROM enrollments
        WHERE student_id = ? AND progress_percentage IS NOT NULL
        GROUP BY progress_range
        ORDER BY MIN(progress_percentage) DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $progressDist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $metrics['distribution'] = array_map(function($dist) {
        return [
            'range' => $dist['progress_range'],
            'count' => (int)$dist['count'],
            'percentage' => round(($dist['count'] / array_sum(array_column($progressDist, 'count'))) * 100, 1)
        ];
    }, $progressDist);
    
    // Recent progress activity
    $stmt = $conn->prepare("
        SELECT 
            DATE(lp.completed_at) as date,
            COUNT(*) as lessons_completed,
            AVG(l.duration_minutes) as avg_duration
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.student_id = ? AND lp.completed = 1
            AND lp.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(lp.completed_at)
        ORDER BY date DESC
        LIMIT 7
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $recentActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $metrics['recent_activity'] = array_map(function($activity) {
        return [
            'date' => $activity['date'],
            'lessons_completed' => (int)$activity['lessons_completed'],
            'avg_duration' => round($activity['avg_duration'] ?? 0, 1),
            'total_minutes' => (int)$activity['lessons_completed'] * ($activity['avg_duration'] ?? 0)
        ];
    }, $recentActivity);
    
    return $metrics;
}

/**
 * Get engagement data
 */
function getEngagementData($conn, $userId) {
    $engagement = [];
    
    // Login frequency
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT DATE(last_login)) as active_days,
            MAX(last_login) as last_login,
            AVG(DATEDIFF(NOW(), last_login)) as avg_days_between_logins
        FROM users_new
        WHERE id = ? AND last_login IS NOT NULL
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $loginData = $stmt->get_result()->fetch_assoc();
    
    $engagement['login_frequency'] = [
        'active_days_last_30' => (int)($loginData['active_days'] ?? 0),
        'last_login' => $loginData['last_login'],
        'days_since_last_login' => $loginData['last_login'] ? 
            floor((strtotime('now') - strtotime($loginData['last_login'])) / (60 * 60 * 24)) : null,
        'avg_days_between_logins' => round($loginData['avg_days_between_logins'] ?? 0, 1)
    ];
    
    // Interaction patterns
    $stmt = $conn->prepare("
        SELECT 
            interaction_type,
            COUNT(*) as count,
            AVG(interaction_value) as avg_value,
            MAX(created_at) as last_interaction
        FROM user_interactions
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY interaction_type
        ORDER BY count DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $interactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $engagement['interaction_patterns'] = array_map(function($interaction) {
        return [
            'type' => $interaction['interaction_type'],
            'count' => (int)$interaction['count'],
            'avg_value' => round($interaction['avg_value'] ?? 0, 2),
            'last_interaction' => $interaction['last_interaction']
        ];
    }, $interactions);
    
    // Learning streak
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT DATE(completed_at)) as current_streak,
            MAX(DATEDIFF(completed_at, 
                (SELECT MAX(completed_at) 
                 FROM lesson_progress lp2 
                 WHERE lp2.student_id = ? AND lp2.completed_at < lp.completed_at 
                 AND DATEDIFF(lp.completed_at, lp2.completed_at) > 1
                )
            )) as longest_streak
        FROM lesson_progress
        WHERE student_id = ? AND completed = 1
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $streakData = $stmt->get_result()->fetch_assoc();
    
    $engagement['learning_streak'] = [
        'current' => (int)$streakData['current_streak'],
        'longest' => (int)$streakData['longest_streak'],
        'streak_status' => getStreakStatus($streakData['current_streak'])
    ];
    
    return $engagement;
}

/**
 * Get skill analysis based on completed courses
 */
function getSkillAnalysis($conn, $userId) {
    $analysis = [];
    
    // Get completed courses with categories
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.title,
            c.description,
            c.category_id,
            cat.name as category_name,
            e.progress_percentage,
            e.completed_at
        FROM enrollments e
        JOIN courses_new c ON e.course_id = c.id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE e.student_id = ? AND e.status = 'completed'
        ORDER BY e.completed_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $completedCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Analyze skill distribution
    $skillDistribution = [];
    foreach ($completedCourses as $course) {
        $category = $course['category_name'] ?: 'General';
        if (!isset($skillDistribution[$category])) {
            $skillDistribution[$category] = [
                'category' => $category,
                'courses' => [],
                'total_progress' => 0,
                'avg_progress' => 0,
                'completion_dates' => []
            ];
        }
        $skillDistribution[$category]['courses'][] = [
            'id' => $course['id'],
            'title' => $course['title'],
            'progress' => $course['progress_percentage'],
            'completed_at' => $course['completed_at']
        ];
        $skillDistribution[$category]['total_progress'] += $course['progress_percentage'];
        $skillDistribution[$category]['completion_dates'][] = $course['completed_at'];
    }
    
    // Calculate averages and trends
    foreach ($skillDistribution as $category => &$data) {
        $data['course_count'] = count($data['courses']);
        $data['avg_progress'] = $data['course_count'] > 0 ? 
            round($data['total_progress'] / $data['course_count'], 1) : 0;
        
        // Calculate learning trend (time between completions)
        if (count($data['completion_dates']) > 1) {
            rsort($data['completion_dates']);
            $timeDiffs = [];
            for ($i = 1; $i < count($data['completion_dates']); $i++) {
                $diff = strtotime($data['completion_dates'][$i-1]) - strtotime($data['completion_dates'][$i]);
                $timeDiffs[] = $diff;
            }
            $data['avg_completion_time'] = round(array_sum($timeDiffs) / count($timeDiffs) / (60 * 60 * 24), 1);
        } else {
            $data['avg_completion_time'] = 0;
        }
    }
    
    $analysis['skill_distribution'] = array_values($skillDistribution);
    
    // Identify strongest and weakest areas
    $analysis['strongest_areas'] = array_slice(array_values($skillDistribution), 0, 3);
    $analysis['weakest_areas'] = array_slice(array_reverse(array_values($skillDistribution)), 0, 3);
    
    // Calculate skill diversity
    $analysis['skill_diversity'] = count($skillDistribution);
    $analysis['skill_balance'] = calculateSkillBalance($skillDistribution);
    
    return $analysis;
}

/**
 * Get learning trends over time
 */
function getLearningTrends($conn, $userId) {
    $trends = [];
    
    // Monthly progress trend
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as enrollments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completions
        FROM enrollments
        WHERE student_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $monthlyTrends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $trends['monthly_enrollments'] = array_map(function($trend) {
        return [
            'month' => $trend['month'],
            'enrollments' => (int)$trend['enrollments'],
            'completions' => (int)$trend['completions'],
            'completion_rate' => $trend['enrollments'] > 0 ? 
                round(($trend['completions'] / $trend['enrollments']) * 100, 1) : 0
        ];
    }, $monthlyTrends);
    
    // Weekly activity trend
    $stmt = $conn->prepare("
        SELECT 
            DAYOFWEEK(created_at) as day_of_week,
            DAYNAME(created_at) as day_name,
            COUNT(*) as activities
        FROM user_interactions
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
        GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
        ORDER BY DAYOFWEEK(created_at)
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $weeklyActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $trends['weekly_activity'] = array_map(function($activity) {
        return [
            'day_of_week' => (int)$activity['day_of_week'],
            'day_name' => $activity['day_name'],
            'activities' => (int)$activity['activities']
        ];
    }, $weeklyActivity);
    
    // Progress velocity trend
    $stmt = $stmt->prepare("
        SELECT 
            DATE(completed_at) as date,
            COUNT(*) as lessons_completed,
            AVG(progress_percentage) as avg_progress
        FROM enrollments e
        WHERE student_id = ? AND status = 'in_progress' AND progress_percentage IS NOT NULL
        GROUP BY DATE(completed_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $progressVelocity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $trends['progress_velocity'] = array_map(function($velocity) {
        return [
            'date' => $velocity['date'],
            'lessons_completed' => (int)$velocity['lessons_completed'],
            'avg_progress' => round($velocity['avg_progress'] ?? 0, 1)
        ];
    }, $progressVelocity);
    
    return $trends;
}

/**
 * Get achievements and milestones
 */
function getAchievements($conn, $userId) {
    $achievements = [];
    
    // Course completion achievements
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $completedCourses = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($completedCourses >= 1) {
        $achievements[] = [
            'id' => 'first_course',
            'title' => 'First Course Completed',
            'description' => 'Completed your first course successfully',
            'icon' => 'graduation-cap',
            'type' => 'milestone',
            'earned_at' => getFirstCourseCompletionDate($conn, $userId),
            'points' => 100
        ];
    }
    
    if ($completedCourses >= 3) {
        $achievements[] = [
            'id' => 'dedicated_learner',
            'title' => 'Dedicated Learner',
            'description' => 'Completed 3 or more courses',
            'icon' => 'trophy',
            'type' => 'achievement',
            'earned_at' => getThirdCourseCompletionDate($conn, $userId),
            'points' => 300
        ];
    }
    
    if ($completedCourses >= 5) {
        $achievements[] = [
            'id' => 'course_master',
            'title' => 'Course Master',
            'description' => 'Completed 5 or more courses',
            'icon' => 'star',
            'type' => 'achievement',
            'earned_at' => getFifthCourseCompletionDate($conn, $userId),
            'points' => 500
        ];
    }
    
    // Quiz achievements
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE student_id = ? AND passed = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $passedQuizzes = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($passedQuizzes >= 10) {
        $achievements[] = [
            'id' => 'quiz_expert',
            'title' => 'Quiz Expert',
            'description' => 'Passed 10 quizzes successfully',
            'icon' => 'brain',
            'type' => 'achievement',
            'earned_at' => getTenthQuizPassDate($conn, $userId),
            'points' => 250
        ];
    }
    
    // Streak achievements
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
        FROM lesson_progress
        WHERE student_id = ? AND completed = 1
        AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $currentStreak = $stmt->getget_result()->fetch_assoc()['streak_days'];
    
    if ($currentStreak >= 7) {
        $achievements[] = [
            'id' => 'week_streak',
            'title' => 'Week Warrior',
            'description' => '7-day learning streak achieved',
            'icon' => 'fire',
            'type' => 'achievement',
            'earned_at' => date('Y-m-d H:i:s'),
            'points' => 200
        ];
    }
    
    if ($currentStreak >= 30) {
        $achievements[] = [
            'id' => 'month_streak',
            'title' => 'Month Master',
            'description' => '30-day learning streak achieved',
            'icon' => 'calendar-check',
            'type' => 'achievement',
            'earned_at' => date('Y-m-d H:i:s'),
            'points' => 500
        ];
    }
    
    return $achievements;
}

/**
 * Get smart recommendations based on learning patterns
 */
function getSmartRecommendations($userId) {
    $recommendationSystem = new RecommendationSystem();
    
    try {
        // Get KNN recommendations
        $recommendations = $recommendationSystem->getKNNRecommendations($userId, 5);
        
        // Add learning path suggestions
        $learningPath = $recommendationSystem->getPersonalizedLearningPath($userId);
        
        return [
            'courses' => $recommendations,
            'learning_path' => $learningPath,
            'recommendation_type' => 'knn',
            'generated_at' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        // Fallback to basic recommendations
        $course = new Course();
        $basicRecommendations = $course->getRecommendedCourses($userId, 5);
        
        return [
            'courses' => $basicRecommendations,
            'learning_path' => null,
            'recommendation_type' => 'basic',
            'generated_at' => date('Y-m-d H:i:s'),
            'fallback' => true
        ];
    }
}

/**
 * Get time management insights
 */
function getTimeManagement($conn, $userId) {
    $timeManagement = [];
    
    // Learning schedule patterns
    $stmt = $stmt = $conn->prepare("
        SELECT 
            HOUR(completed_at) as hour,
            COUNT(*) as lessons_completed,
            AVG(l.duration_minutes) as avg_duration
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.student_id = ? AND lp.completed = 1
        GROUP BY HOUR(completed_at)
        ORDER BY lessons_completed DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $schedulePatterns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $timeManagement['schedule_patterns'] = array_map(function($pattern) {
        $hour = (int)$pattern['hour'];
        return [
            'hour' => $hour,
            'time_period' => getTimePeriod($hour),
            'lessons_completed' => (int)$pattern['lessons_completed'],
            'avg_duration' => round($pattern['avg_duration'] ?? 0, 1),
            'total_minutes' => (int)$pattern['lessons_completed'] * ($pattern['avg_duration'] ?? 0)
        ];
    }, $schedulePatterns);
    
    // Peak learning hours
    $peakHours = array_filter($schedulePatterns, function($pattern) {
        return $pattern['lessons_completed'] >= 2;
    });
    
    $timeManagement['peak_learning_hours'] = array_map(function($hour) {
        return $hour['hour'];
    }, $peakHours);
    
    // Learning efficiency by time
    $timeManagement['efficiency_by_time'] = array_map(function($pattern) {
        return [
            'hour' => $pattern['hour'],
            'time_period' => $pattern['time_period'],
            'efficiency' => $pattern['avg_duration'] > 0 ? 
                round(60 / $pattern['avg_duration'], 2) : 0
        ];
    }, $schedulePatterns);
    
    // Total time investment
    $stmt = $conn->prepare("
        SELECT 
            SUM(l.duration_minutes) as total_minutes,
            COUNT(*) as total_lessons
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.student_id = ? AND lp.completed = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $totalTime = $stmt->get_result()->fetch_assoc();
    
    $timeManagement['total_investment'] = [
        'total_minutes' => (int)($totalTime['total_minutes'] ?? 0),
        'total_hours' => round(($totalTime['total_minutes'] ?? 0) / 60, 1),
        'total_lessons' => (int)($totalTime['total_lessons'] ?? 0),
        'avg_lesson_time' => $totalTime['total_lessons'] > 0 ? 
            round(($totalTime['total_minutes'] ?? 0) / $totalTime['total_lessons'], 1) : 0
    ];
    
    return $timeManagement;
}

// Helper functions
function getStreakStatus($days) {
    if ($days >= 30) return 'outstanding';
    if ($days >= 14) return 'excellent';
    if ($days >= 7) return 'good';
    if ($days >= 3) return 'fair';
    return 'starting';
}

function getTimePeriod($hour) {
    if ($hour >= 6 && $hour < 12) return 'Morning (6AM-12PM)';
    if ($hour >= 12 && $hour < 17) return 'Afternoon (12PM-5PM)';
    if ($hour >= 17 && $hour < 21) return 'Evening (5PM-9PM)';
    if ($hour >= 21 || $hour < 6) return 'Night (9PM-6AM)';
    return 'Unknown';
}

function getFirstCourseCompletionDate($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT MIN(completed_at) as date
        FROM enrollments
        WHERE student_id = ? AND status = 'completed'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['date'] ?? null;
}

function getThirdCourseCompletionDate($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT completed_at as date
        FROM enrollments
        WHERE student_id = ? AND status = 'completed'
        ORDER BY completed_at ASC
        LIMIT 1 OFFSET 2
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['date'] ?? null;
}

function getFifthCourseCompletionDate($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT completed_at as date
        FROM enrollments
        WHERE student_id = ? AND status = 'completed'
        ORDER BY completed_at ASC
        LIMIT 1 OFFSET 4
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['date'] ?? null;
}

function getTenthQuizPassDate($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT completed_at as date
        FROM quiz_attempts
        WHERE student_id = ? AND passed = 1
        ORDER BY completed_at ASC
        LIMIT 1 OFFSET 9
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['date'] ?? null;
}

function calculateSkillBalance($skillDistribution) {
    if (empty($skillDistribution)) return 0;
    
    $avgProgress = array_sum(array_column($skillDistribution, 'avg_progress')) / count($skillDistribution);
    $variance = 0;
    
    foreach ($skillDistribution as $skill) {
        $variance += pow($skill['avg_progress'] - $avgProgress, 2);
    }
    
    $stdDev = sqrt($variance / count($skillDistribution));
    
    return $stdDev > 0 ? ($avgProgress / $stdDev) : 0;
}

$database->close();
?>
