<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/Quiz.php';
require_once dirname(__DIR__) . '/models/Discussion.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

$studentId = $_SESSION['user_id'];
$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();

// Get student's learning profile
$learningProfile = getLearningProfile($studentId);

// Get personalized recommendations
$recommendations = getPersonalizedRecommendations($studentId, $learningProfile);

// Get learning path suggestions
$learningPaths = getLearningPathSuggestions($studentId, $learningProfile);

// Get skill gaps analysis
$skillGaps = analyzeSkillGaps($studentId, $learningProfile);

function getLearningProfile($studentId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $profile = [];
    
    // Get completed courses and performance
    $stmt = $conn->prepare("
        SELECT c.*, e.progress_percentage, e.enrolled_at,
               cat.name as category_name
        FROM courses_new c
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE e.student_id = ? AND e.progress_percentage >= 100
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $profile['completed_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get current courses
    $stmt = $conn->prepare("
        SELECT c.*, e.progress_percentage, e.enrolled_at,
               cat.name as category_name
        FROM courses_new c
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE e.student_id = ? AND e.progress_percentage < 100
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $profile['current_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get quiz performance
    $stmt = $conn->prepare("
        SELECT q.title, qa.percentage, qa.passed, c.category_id,
               cat.name as category_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN courses_new c ON q.course_id = c.id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE qa.student_id = ?
        ORDER BY qa.completed_at DESC
        LIMIT 20
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $profile['quiz_performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Calculate learning patterns
    $profile['learning_patterns'] = calculateLearningPatterns($studentId);
    
    // Identify strengths and weaknesses
    $profile['strengths'] = identifyStrengths($profile);
    $profile['weaknesses'] = identifyWeaknesses($profile);
    
    return $profile;
}

function calculateLearningPatterns($studentId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $patterns = [];
    
    // Learning frequency
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(completed_at)) as active_days,
               COUNT(*) as total_activities
        FROM lesson_progress lp
        WHERE lp.student_id = ? AND lp.completed = 1
        AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $patterns['active_days_per_month'] = $result['active_days'] ?? 0;
        $patterns['total_activities'] = $result['total_activities'] ?? 0;
    }
    
    // Preferred learning time
    $stmt = $conn->prepare("
        SELECT HOUR(completed_at) as hour, COUNT(*) as count
        FROM lesson_progress lp
        WHERE lp.student_id = ? AND lp.completed = 1
        GROUP BY HOUR(completed_at)
        ORDER BY count DESC
        LIMIT 1
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $patterns['preferred_hour'] = $result['hour'] ?? 12;
    }
    
    // Average completion time
    $stmt = $conn->prepare("
        SELECT AVG(time_spent) as avg_time
        FROM lesson_progress lp
        WHERE lp.student_id = ? AND lp.completed = 1
        AND time_spent > 0
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $patterns['avg_completion_time'] = $result['avg_time'] ?? 0;
    }
    
    return $patterns;
}

function identifyStrengths($profile) {
    $strengths = [];
    
    // Analyze quiz performance
    if (!empty($profile['quiz_performance'])) {
        $categoryScores = [];
        foreach ($profile['quiz_performance'] as $quiz) {
            $category = $quiz['category_name'] ?? 'General';
            if (!isset($categoryScores[$category])) {
                $categoryScores[$category] = [];
            }
            $categoryScores[$category][] = $quiz['percentage'];
        }
        
        foreach ($categoryScores as $category => $scores) {
            $avgScore = array_sum($scores) / count($scores);
            if ($avgScore >= 85) {
                $strengths[] = [
                    'type' => 'category',
                    'name' => $category,
                    'score' => round($avgScore, 2),
                    'description' => "Strong performance in $category"
                ];
            }
        }
    }
    
    // Analyze learning consistency
    if ($profile['learning_patterns']['active_days_per_month'] >= 20) {
        $strengths[] = [
            'type' => 'consistency',
            'name' => 'Consistent Learning',
            'score' => $profile['learning_patterns']['active_days_per_month'],
            'description' => 'Very consistent learning schedule'
        ];
    }
    
    return $strengths;
}

function identifyWeaknesses($profile) {
    $weaknesses = [];
    
    // Analyze quiz performance
    if (!empty($profile['quiz_performance'])) {
        $categoryScores = [];
        foreach ($profile['quiz_performance'] as $quiz) {
            $category = $quiz['category_name'] ?? 'General';
            if (!isset($categoryScores[$category])) {
                $categoryScores[$category] = [];
            }
            $categoryScores[$category][] = $quiz['percentage'];
        }
        
        foreach ($categoryScores as $category => $scores) {
            $avgScore = array_sum($scores) / count($scores);
            if ($avgScore < 70) {
                $weaknesses[] = [
                    'type' => 'category',
                    'name' => $category,
                    'score' => round($avgScore, 2),
                    'description' => "Needs improvement in $category"
                ];
            }
        }
    }
    
    // Analyze learning consistency
    if ($profile['learning_patterns']['active_days_per_month'] < 10) {
        $weaknesses[] = [
            'type' => 'consistency',
            'name' => 'Learning Consistency',
            'score' => $profile['learning_patterns']['active_days_per_month'],
            'description' => 'Needs more consistent learning schedule'
        ];
    }
    
    return $weaknesses;
}

function getPersonalizedRecommendations($studentId, $learningProfile) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $recommendations = [];
    
    // Get courses based on completed courses
    if (!empty($learningProfile['completed_courses'])) {
        $completedCategories = array_unique(array_column($learningProfile['completed_courses'], 'category_id'));
        
        if (!empty($completedCategories)) {
            $placeholders = str_repeat('?,', count($completedCategories) - 1) . '?';
            $stmt = $conn->prepare("
                SELECT c.*, cat.name as category_name,
                       COUNT(e.id) as enrollment_count,
                       AVG(e.progress_percentage) as avg_progress
                FROM courses_new c
                LEFT JOIN categories_new cat ON c.category_id = cat.id
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE c.category_id IN ($placeholders) AND c.status = 'published'
                AND c.id NOT IN (
                    SELECT course_id FROM enrollments WHERE student_id = ?
                )
                GROUP BY c.id
                ORDER BY enrollment_count DESC, avg_progress DESC
                LIMIT 5
            ");
            
            if ($stmt) {
                $params = array_merge($completedCategories, [$studentId]);
                $types = str_repeat('i', count($completedCategories)) . 'i';
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $recommendations['similar_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    // Get courses to address weaknesses
    if (!empty($learningProfile['weaknesses'])) {
        foreach ($learningProfile['weaknesses'] as $weakness) {
            if ($weakness['type'] === 'category') {
                $stmt = $conn->prepare("
                    SELECT c.*, cat.name as category_name,
                           COUNT(e.id) as enrollment_count
                    FROM courses_new c
                    LEFT JOIN categories_new cat ON c.category_id = cat.id
                    LEFT JOIN enrollments e ON c.id = e.course_id
                    WHERE cat.name = ? AND c.status = 'published'
                    AND c.id NOT IN (
                        SELECT course_id FROM enrollments WHERE student_id = ?
                    )
                    ORDER BY enrollment_count DESC
                    LIMIT 3
                ");
                
                if ($stmt) {
                    $stmt->bind_param("si", $weakness['name'], $studentId);
                    $stmt->execute();
                    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $recommendations['improvement_courses'][$weakness['name']] = $courses;
                }
            }
        }
    }
    
    // Get trending courses
    $stmt = $conn->prepare("
        SELECT c.*, cat.name as category_name,
               COUNT(e.id) as enrollment_count,
               AVG(e.progress_percentage) as avg_progress
        FROM courses_new c
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE c.status = 'published'
        AND c.id NOT IN (
            SELECT course_id FROM enrollments WHERE student_id = ?
        )
        AND e.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        GROUP BY c.id
        ORDER BY enrollment_count DESC
        LIMIT 5
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $recommendations['trending_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $recommendations;
}

function getLearningPathSuggestions($studentId, $learningProfile) {
    $paths = [];
    
    // Suggest learning paths based on completed courses
    if (!empty($learningProfile['completed_courses'])) {
        $completedCategories = array_unique(array_column($learningProfile['completed_courses'], 'category_name'));
        
        // Beginner to Advanced path
        if (in_array('Beginner', array_column($learningProfile['completed_courses'], 'difficulty_level'))) {
            $paths[] = [
                'title' => 'Advanced Learning Path',
                'description' => 'Continue your journey with advanced courses',
                'courses' => getAdvancedPathCourses($studentId),
                'estimated_duration' => '3-6 months',
                'difficulty' => 'Advanced'
            ];
        }
        
        // Specialization path
        if (count($completedCategories) >= 2) {
            $paths[] = [
                'title' => 'Specialization Path',
                'description' => 'Deepen your knowledge in specific areas',
                'courses' => getSpecializationCourses($studentId, $completedCategories),
                'estimated_duration' => '4-8 months',
                'difficulty' => 'Intermediate to Advanced'
            ];
        }
    }
    
    // Career-focused paths
    $paths[] = [
        'title' => 'Career Development Path',
        'description' => 'Courses focused on career advancement',
        'courses' => getCareerCourses($studentId),
        'estimated_duration' => '6-12 months',
        'difficulty' => 'Mixed'
    ];
    
    return $paths;
}

function getAdvancedPathCourses($studentId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT c.*, cat.name as category_name
        FROM courses_new c
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE c.difficulty_level = 'Advanced' AND c.status = 'published'
        AND c.id NOT IN (
            SELECT course_id FROM enrollments WHERE student_id = ?
        )
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return [];
}

function getSpecializationCourses($studentId, $categories) {
    // Implementation for specialization courses
    return [];
}

function getCareerCourses($studentId) {
    // Implementation for career-focused courses
    return [];
}

function analyzeSkillGaps($studentId, $learningProfile) {
    $gaps = [];
    
    // Analyze quiz performance for skill gaps
    if (!empty($learningProfile['quiz_performance'])) {
        $skillAnalysis = [];
        foreach ($learningProfile['quiz_performance'] as $quiz) {
            $category = $quiz['category_name'] ?? 'General';
            if (!isset($skillAnalysis[$category])) {
                $skillAnalysis[$category] = ['total' => 0, 'passed' => 0, 'scores' => []];
            }
            $skillAnalysis[$category]['total']++;
            if ($quiz['passed']) {
                $skillAnalysis[$category]['passed']++;
            }
            $skillAnalysis[$category]['scores'][] = $quiz['percentage'];
        }
        
        foreach ($skillAnalysis as $category => $data) {
            $passRate = ($data['passed'] / $data['total']) * 100;
            $avgScore = array_sum($data['scores']) / count($data['scores']);
            
            if ($passRate < 70 || $avgScore < 75) {
                $gaps[] = [
                    'skill' => $category,
                    'pass_rate' => round($passRate, 2),
                    'avg_score' => round($avgScore, 2),
                    'severity' => $passRate < 50 ? 'high' : ($passRate < 70 ? 'medium' : 'low'),
                    'recommendation' => "Focus on improving $category skills"
                ];
            }
        }
    }
    
    return $gaps;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Recommendations - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .recommendation-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .strength-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .weakness-badge {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .skill-gap-card {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
        }
        .skill-gap-card.medium {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .skill-gap-card.low {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        .learning-path-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .profile-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .recommendation-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Student Portal</h5>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="my-courses-advanced.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-graduation-cap me-2"></i> My Courses
                            </a>
                            <a href="learning-recommendations.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-lightbulb me-2"></i> Recommendations
                            </a>
                            <a href="quizzes.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-brain me-2"></i> Quizzes
                            </a>
                            <a href="discussions.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-comments me-2"></i> Discussions
                            </a>
                            <a href="certificates.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-certificate me-2"></i> Certificates
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                            <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1>Learning Recommendations</h1>
                        <p class="text-muted">Personalized course suggestions based on your learning profile</p>
                    </div>
                </div>

                <!-- Learning Profile Section -->
                <div class="profile-section">
                    <h4 class="mb-4"><i class="fas fa-user-graduate me-2"></i>Your Learning Profile</h4>
                    
                    <!-- Learning Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <h3 class="text-primary"><?php echo count($learningProfile['completed_courses'] ?? []); ?></h3>
                                <small class="text-muted">Completed Courses</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <h3 class="text-info"><?php echo count($learningProfile['current_courses'] ?? []); ?></h3>
                                <small class="text-muted">Active Courses</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <h3 class="text-success"><?php echo $learningProfile['learning_patterns']['active_days_per_month'] ?? 0; ?></h3>
                                <small class="text-muted">Active Days/Month</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <h3 class="text-warning"><?php echo round($learningProfile['learning_patterns']['avg_completion_time'] ?? 0); ?>m</h3>
                                <small class="text-muted">Avg. Time/Lesson</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Strengths and Weaknesses -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-star text-success me-2"></i>Your Strengths</h6>
                            <?php if (!empty($learningProfile['strengths'])): ?>
                                <?php foreach ($learningProfile['strengths'] as $strength): ?>
                                    <div class="mb-2">
                                        <span class="strength-badge"><?php echo htmlspecialchars($strength['name']); ?></span>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($strength['description']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Keep learning to discover your strengths!</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Areas for Improvement</h6>
                            <?php if (!empty($learningProfile['weaknesses'])): ?>
                                <?php foreach ($learningProfile['weaknesses'] as $weakness): ?>
                                    <div class="mb-2">
                                        <span class="weakness-badge"><?php echo htmlspecialchars($weakness['name']); ?></span>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($weakness['description']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Great job! No major weaknesses identified.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Skill Gaps Analysis -->
                <?php if (!empty($skillGaps)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line me-2"></i>Skill Gaps Analysis</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($skillGaps as $gap): ?>
                                <div class="skill-gap-card <?php echo $gap['severity']; ?> p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($gap['skill']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($gap['recommendation']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Pass Rate</div>
                                            <div class="fw-bold"><?php echo $gap['pass_rate']; ?>%</div>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $gap['severity'] === 'high' ? 'danger' : ($gap['severity'] === 'medium' ? 'warning' : 'success'); ?>" 
                                             style="width: <?php echo $gap['pass_rate']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Learning Paths -->
                <?php if (!empty($learningPaths)): ?>
                    <div class="mb-4">
                        <h4 class="mb-3"><i class="fas fa-route me-2"></i>Suggested Learning Paths</h4>
                        <div class="row">
                            <?php foreach ($learningPaths as $path): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="learning-path-card p-4">
                                        <h6 class="text-white mb-2"><?php echo htmlspecialchars($path['title']); ?></h6>
                                        <p class="text-white-50 small mb-3"><?php echo htmlspecialchars($path['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-white-50">
                                                <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($path['estimated_duration']); ?>
                                            </small>
                                            <small class="text-white-50">
                                                <i class="fas fa-signal me-1"></i><?php echo htmlspecialchars($path['difficulty']); ?>
                                            </small>
                                        </div>
                                        <button class="btn btn-light btn-sm mt-3 w-100">
                                            <i class="fas fa-play me-1"></i>Start Path
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Course Recommendations -->
                <div class="row">
                    <!-- Similar Courses -->
                    <?php if (!empty($recommendations['similar_courses'])): ?>
                        <div class="col-md-6">
                            <div class="recommendation-card">
                                <div class="recommendation-header">
                                    <h6><i class="fas fa-book me-2"></i>Similar to Your Completed Courses</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recommendations['similar_courses'] as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['category_name']); ?></small>
                                            </div>
                                            <button class="btn btn-primary btn-sm" onclick="window.location.href='../course-details.php?id=<?php echo $course['id']; ?>'">
                                                View
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Trending Courses -->
                    <?php if (!empty($recommendations['trending_courses'])): ?>
                        <div class="col-md-6">
                            <div class="recommendation-card">
                                <div class="recommendation-header">
                                    <h6><i class="fas fa-fire me-2"></i>Trending Courses</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recommendations['trending_courses'] as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i><?php echo $course['enrollment_count']; ?> enrolled
                                                </small>
                                            </div>
                                            <button class="btn btn-primary btn-sm" onclick="window.location.href='../course-details.php?id=<?php echo $course['id']; ?>'">
                                                View
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Improvement Courses -->
                <?php if (!empty($recommendations['improvement_courses'])): ?>
                    <?php foreach ($recommendations['improvement_courses'] as $category => $courses): ?>
                        <div class="recommendation-card mt-4">
                            <div class="recommendation-header">
                                <h6><i class="fas fa-graduation-cap me-2"></i>Improve Your <?php echo htmlspecialchars($category); ?> Skills</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($courses as $course): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...</p>
                                                    <button class="btn btn-primary btn-sm w-100" onclick="window.location.href='../course-details.php?id=<?php echo $course['id']; ?>'">
                                                        View Course
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
