<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Quiz.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

$studentId = $_SESSION['user_id'];
$course = new Course();
$quiz = new Quiz();

// Get enrolled courses
$enrolledCourses = $course->getEnrolledCourses($studentId);

// Get available quizzes for enrolled courses
$availableQuizzes = [];
$quizAttempts = [];

foreach ($enrolledCourses as $course) {
    // Get quizzes for this course
    $courseQuizzes = $quiz->getCourseQuizzes($course['id']);
    
    foreach ($courseQuizzes as $courseQuiz) {
        // Check if student has attempted this quiz
        $attempts = $quiz->getStudentQuizAttemptsForQuiz($studentId, $courseQuiz['id']);
        $bestAttempt = null;
        
        if (!empty($attempts)) {
            $bestAttempt = $attempts[0]; // Assuming attempts are ordered by score desc
        }
        
        $availableQuizzes[] = [
            'quiz' => $courseQuiz,
            'course' => $course,
            'best_attempt' => $bestAttempt,
            'attempts_count' => count($attempts),
            'can_retake' => $bestAttempt ? (!$bestAttempt['passed'] && count($attempts) < 3) : true
        ];
    }
}

// Get quiz statistics
$conn = connectDB();

// Total available quizzes
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT q.id) as total 
    FROM quizzes q 
    JOIN enrollments_new e ON q.course_id = e.course_id 
    WHERE e.student_id = ?
");
if ($stmt === false) {
    $totalAvailableQuizzes = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $totalAvailableQuizzes = $stmt->get_result()->fetch_assoc()['total'];
}

// Completed quizzes
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT qa.quiz_id) as total 
    FROM quiz_attempts qa 
    WHERE qa.student_id = ? AND qa.status = 'completed' AND qa.passed = TRUE
");
if ($stmt === false) {
    $completedQuizzes = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $completedQuizzes = $stmt->get_result()->fetch_assoc()['total'];
}

// Average score
$stmt = $conn->prepare("
    SELECT AVG(percentage) as avg_score 
    FROM quiz_attempts qa 
    WHERE qa.student_id = ? AND qa.status = 'completed'
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$avgScore = $stmt->get_result()->fetch_assoc()['avg_score'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        /* Force Royal Blue Colors */
        :root {
            --primary-color: #4169E1 !important;
            --secondary-color: #2563EB !important;
            --gradient-primary: linear-gradient(135deg, #4169E1 0%, #2563EB 100%) !important;
        }
        
        /* Force Royal Blue for Sidebar */
        .sidebar-nav {
            background: linear-gradient(135deg, #4169E1 0%, #2563EB 100%) !important;
        }
        
        /* Force Royal Blue for Universal Header */
        .universal-header {
            background: linear-gradient(135deg, #4169E1 0%, #2563EB 100%) !important;
        }
        
        /* Force Royal Blue for any remaining elements */
        .btn-primary {
            background: linear-gradient(135deg, #4169E1 0%, #2563EB 100%) !important;
            border: none !important;
        }
        
        .bg-primary {
            background: linear-gradient(135deg, #4169E1 0%, #2563EB 100%) !important;
        }
        
        .text-primary {
            color: #4169E1 !important;
        }
        
        .border-primary {
            border-color: #4169E1 !important;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/universal_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php require_once 'includes/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-brain me-2"></i>Available Quizzes</h1>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <h3><?php echo $totalAvailableQuizzes; ?></h3>
                            <p>Available Quizzes</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3><?php echo $completedQuizzes; ?></h3>
                            <p>Passed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <h3><?php echo $avgScore ? round($avgScore, 1) : 0; ?>%</h3>
                            <p>Average Score</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <h3><?php echo count($availableQuizzes); ?></h3>
                            <p>Ready to Take</p>
                        </div>
                    </div>
                </div>

                <!-- Available Quizzes -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3><i class="fas fa-list me-2"></i>Quiz List</h3>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" onclick="filterQuizzes('all')">
                                All
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="filterQuizzes('passed')">
                                Passed
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="filterQuizzes('failed')">
                                Failed
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="filterQuizzes('available')">
                                Available
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($availableQuizzes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                            <h5>No quizzes available</h5>
                            <p class="text-muted">Enroll in courses to see available quizzes here.</p>
                            <a href="my-courses.php" class="btn btn-primary">My Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="row" id="quizContainer">
                            <?php foreach ($availableQuizzes as $index => $quizData): ?>
                                <div class="col-md-6 mb-3 quiz-item" data-status="<?php 
                                    echo $quizData['best_attempt'] ? 
                                        ($quizData['best_attempt']['passed'] ? 'passed' : 'failed') : 'available'; 
                                ?>">
                                    <div class="card h-100 quiz-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title"><?php echo htmlspecialchars($quizData['quiz']['title']); ?></h6>
                                                <?php if ($quizData['best_attempt'] && $quizData['best_attempt']['passed']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Passed
                                                    </span>
                                                <?php elseif ($quizData['best_attempt']): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Failed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-play me-1"></i>Available
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="card-text small text-muted mb-2">
                                                <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quizData['course']['title']); ?>
                                            </p>
                                            
                                            <div class="quiz-details mb-3">
                                                <div class="row small text-muted">
                                                    <div class="col-6">
                                                        <i class="fas fa-question-circle me-1"></i>
                                                        <?php echo $quizData['quiz']['question_count'] ?? 'N/A'; ?> questions
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $quizData['quiz']['time_limit_minutes'] ?? 'N/A'; ?> min
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-trophy me-1"></i>
                                                        Pass: <?php echo $quizData['quiz']['passing_score'] ?? 'N/A'; ?>%
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-redo me-1"></i>
                                                        Attempts: <?php echo $quizData['attempts_count']; ?>/3
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($quizData['best_attempt']): ?>
                                                <div class="attempt-info mb-2 p-2 bg-light rounded">
                                                    <small class="text-muted">
                                                        Best Score: <strong><?php echo round($quizData['best_attempt']['percentage']); ?>%</strong>
                                                        <?php if ($quizData['best_attempt']['passed']): ?>
                                                            <span class="text-success">✓ Passed</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <?php if ($quizData['can_retake']): ?>
                                                    <a href="quiz.php?quiz_id=<?php echo $quizData['quiz']['id']; ?>" 
                                                       class="btn btn-primary btn-sm flex-fill">
                                                        <i class="fas fa-play me-1"></i>
                                                        <?php echo $quizData['best_attempt'] ? 'Retake Quiz' : 'Start Quiz'; ?>
                                                    </a>
                                                <?php elseif ($quizData['best_attempt'] && $quizData['best_attempt']['passed']): ?>
                                                    <button class="btn btn-success btn-sm flex-fill" disabled>
                                                    <i class="fas fa-check me-1"></i>Completed
                                                </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm flex-fill" disabled>
                                                        <i class="fas fa-ban me-1"></i>No Attempts Left
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($quizData['best_attempt']): ?>
                                                    <a href="quiz-result.php?attempt_id=<?php echo $quizData['best_attempt']['id']; ?>" 
                                                       class="btn btn-outline-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function filterQuizzes(status) {
            $('.quiz-item').show();
            
            if (status !== 'all') {
                $('.quiz-item').each(function() {
                    if ($(this).data('status') !== status) {
                        $(this).hide();
                    }
                });
            }
        }
    </script>
</body>
</html>
