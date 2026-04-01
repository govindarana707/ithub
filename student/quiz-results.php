<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/session_helper.php';
require_once '../models/Quiz.php';
require_once '../models/Course.php';

// Initialize session
initializeSession();

if (!isUserLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../includes/universal_header.php';

$quiz = new Quiz();
$course = new Course();

$studentId = $_SESSION['user_id'];
$quizAttempts = $quiz->getStudentQuizAttempts($studentId);

// Get quiz statistics
$conn = connectDB();

// Total quizzes taken
$stmt = $conn->prepare("SELECT COUNT(DISTINCT quiz_id) as total FROM quiz_attempts WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$totalQuizzes = $stmt->get_result()->fetch_assoc()['total'];

// Average score
$stmt = $conn->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$avgScore = $stmt->get_result()->fetch_assoc()['avg_score'];

// Passed quizzes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE student_id = ? AND status = 'completed' AND passed = TRUE");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$passedQuizzes = $stmt->get_result()->fetch_assoc()['total'];

// Best score
$stmt = $conn->prepare("SELECT MAX(percentage) as max_score FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$bestScore = $stmt->get_result()->fetch_assoc()['max_score'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        /* Modern Dashboard Color Scheme */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius-modern: 20px;
        }
        
        /* Modern Dashboard Header */
        .dashboard-header {
            background: var(--primary-gradient);
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(35deg);
            pointer-events: none;
        }
        
        .dashboard-header h1 {
            color: white !important;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header p {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Enhanced Stats Cards */
        .modern-stat-card {
            background: white;
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .modern-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transition: all 0.4s ease;
        }
        
        .modern-stat-card.success::before {
            background: var(--success-gradient);
        }
        
        .modern-stat-card.warning::before {
            background: var(--warning-gradient);
        }
        
        .modern-stat-card.info::before {
            background: var(--info-gradient);
        }
        
        .modern-stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modern-stat-card:hover::before {
            height: 8px;
        }
        
        .modern-stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin: 0 auto 1.5rem;
            position: relative;
            animation: pulse 2s infinite;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .modern-stat-icon.primary {
            background: var(--primary-gradient);
        }
        
        .modern-stat-icon.success {
            background: var(--success-gradient);
        }
        
        .modern-stat-icon.warning {
            background: var(--warning-gradient);
        }
        
        .modern-stat-icon.info {
            background: var(--info-gradient);
        }
        
        .modern-stat-card h3 {
            color: #2d3748;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            transition: transform 0.4s ease;
        }
        
        .modern-stat-card:hover h3 {
            transform: scale(1.05);
        }
        
        .modern-stat-card p {
            color: #718096;
            font-weight: 500;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        /* Modern Content Cards */
        .modern-card {
            background: white;
            border-radius: var(--border-radius-modern);
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modern-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
            border-radius: var(--border-radius-modern) var(--border-radius-modern) 0 0;
        }
        
        .modern-card .card-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .modern-card .card-body {
            padding: 2rem;
        }
        
        /* Enhanced Table Styles */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .modern-table th {
            color: #4a5568;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }
        
        .modern-table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f7fafc;
        }
        
        .modern-table tbody tr {
            transition: all 0.4s ease;
        }
        
        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }
        
        /* Modern Buttons */
        .btn-modern {
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Override buttons to use modern styling */
        .btn-outline-primary {
            border-color: #667eea !important;
            color: #667eea !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-gradient) !important;
            border-color: transparent !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }
        
        .btn-outline-info {
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-info:hover {
            background: var(--info-gradient) !important;
            border-color: transparent !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Staggered Animation for Stats */
        .stat-card-1 { animation: fadeInUp 0.6s ease 0.1s both; }
        .stat-card-2 { animation: fadeInUp 0.6s ease 0.2s both; }
        .stat-card-3 { animation: fadeInUp 0.6s ease 0.3s both; }
        .stat-card-4 { animation: fadeInUp 0.6s ease 0.4s both; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .modern-stat-card {
                padding: 1.5rem;
            }
            
            .modern-stat-icon {
                width: 60px;
                height: 60px;
                font-size: 1.4rem;
            }
            
            .modern-stat-card h3 {
                font-size: 1.8rem;
            }
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
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Modern Dashboard Header -->
                <div class="dashboard-header">
                    <div class="position-relative">
                        <h1 class="mb-3">Quiz Results 📊</h1>
                        <p class="mb-0">Track your quiz performance and learning progress</p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                    </div>
                </div>

                <!-- Enhanced Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="modern-stat-card stat-card-1">
                            <div class="modern-stat-icon primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $totalQuizzes; ?></h3>
                            <p class="mb-0">Quizzes Taken</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modern-stat-card success stat-card-2">
                            <div class="modern-stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $passedQuizzes; ?></h3>
                            <p class="mb-0">Passed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modern-stat-card info stat-card-3">
                            <div class="modern-stat-icon info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $avgScore ? round($avgScore, 1) : 0; ?>%</h3>
                            <p class="mb-0">Average Score</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modern-stat-card warning stat-card-4">
                            <div class="modern-stat-icon warning">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $bestScore ? round($bestScore, 1) : 0; ?>%</h3>
                            <p class="mb-0">Best Score</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Quiz Attempts Table -->
                <div class="modern-card dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Quiz History
                        </h3>
                    </div>
                    <div class="card-body">
                    
                    <?php if (empty($quizAttempts)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-question-circle fa-4x text-muted" style="opacity: 0.4;"></i>
                            </div>
                            <h5 class="fw-bold text-muted mb-3">No quiz attempts yet</h5>
                            <p class="text-muted mb-4">Take quizzes from your enrolled courses to see your results here.</p>
                            <a href="my-courses.php" class="btn btn-primary-modern btn-modern">
                                <i class="fas fa-graduation-cap me-2"></i>My Courses
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive modern-table">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quiz Title</th>
                                        <th>Course</th>
                                        <th>Attempt</th>
                                        <th>Score</th>
                                        <th>Result</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizAttempts as $attempt): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($attempt['quiz_title']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($attempt['course_title']); ?></td>
                                            <td><span class="badge bg-secondary">#<?php echo $attempt['attempt_number']; ?></span></td>
                                            <td>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <span class="fw-bold"><?php echo round($attempt['percentage']); ?>%</span>
                                                    <small class="text-muted">(<?php echo $attempt['score']; ?>/<?php echo $attempt['total_points']; ?>)</small>
                                                <?php else: ?>
                                                    <span class="text-muted">In Progress</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <?php if ($attempt['passed']): ?>
                                                        <span class="badge bg-success">Passed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">In Progress</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($attempt['started_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($attempt['status'] === 'completed'): ?>
                                                        <button class="btn btn-sm btn-outline-info btn-modern" onclick="viewQuizDetails(<?php echo $attempt['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-primary btn-modern" onclick="continueQuiz(<?php echo $attempt['id']; ?>)">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Details Modal -->
    <div class="modal fade" id="quizDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quiz Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="quizDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Enhanced animations for stat cards
            $('.modern-stat-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(30px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100 * index);
            });
            
            // Hover effects for stat cards
            $('.modern-stat-card').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-10px) scale(1.02)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0) scale(1)');
            });
            
            // Button ripple effect
            $('.btn-modern').on('click', function(e) {
                const button = $(this);
                const ripple = $('<span class="ripple"></span>');
                
                button.append(ripple);
                
                const x = e.pageX - button.offset().left;
                const y = e.pageY - button.offset().top;
                
                ripple.css({
                    left: x,
                    top: y
                });
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Number counting animation for stats
            $('.modern-stat-card h3').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text().replace(/[^\d.]/g, ''));
                
                if (!isNaN(countTo)) {
                    const originalText = $this.text();
                    $this.text('0');
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1500,
                        easing: 'easeOutCubic',
                        step: function() {
                            if (originalText.includes('%')) {
                                $this.text(Math.floor(this.countNum) + '%');
                            } else {
                                $this.text(Math.floor(this.countNum));
                            }
                        },
                        complete: function() {
                            $this.text(originalText);
                        }
                    });
                }
            });
            
            // Parallax effect for dashboard header
            $(window).on('scroll', function() {
                const scrolled = $(window).scrollTop();
                $('.dashboard-header').css('transform', `translateY(${scrolled * 0.3}px)`);
            });
            
            window.viewQuizDetails = function(attemptId) {
                $('#quizDetailsContent').html(`
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                
                $('#quizDetailsModal').modal('show');
                
                $.ajax({
                    url: '../api/get_quiz_details.php',
                    type: 'GET',
                    data: { attempt_id: attemptId },
                    success: function(data) {
                        $('#quizDetailsContent').html(data);
                    },
                    error: function() {
                        $('#quizDetailsContent').html('<div class="alert alert-danger">Error loading quiz details</div>');
                    }
                });
            };
            
            window.continueQuiz = function(attemptId) {
                window.location.href = 'take-quiz.php?attempt_id=' + attemptId;
            };
        });
    </script>
    
    <!-- Add CSS for ripple effect -->
    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>

</body>
</html>
