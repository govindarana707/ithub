<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Quiz.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
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

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                        <span class="badge bg-primary float-end">0</span>
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-brain me-2"></i> Quizzes
                        <span class="badge bg-info float-end">0</span>
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
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
                    <a href="../logout.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Quiz Results</h1>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <h3><?php echo $totalQuizzes; ?></h3>
                            <p>Quizzes Taken</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3><?php echo $passedQuizzes; ?></h3>
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
                            <h3><?php echo $bestScore ? round($bestScore, 1) : 0; ?>%</h3>
                            <p>Best Score</p>
                        </div>
                    </div>
                </div>

                <!-- Quiz Attempts Table -->
                <div class="dashboard-card">
                    <h3>Quiz History</h3>
                    
                    <?php if (empty($quizAttempts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h5>No quiz attempts yet</h5>
                            <p class="text-muted">Take quizzes from your enrolled courses to see your results here.</p>
                            <a href="my-courses.php" class="btn btn-primary">My Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
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
                                            <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                            <td><?php echo htmlspecialchars($attempt['course_title']); ?></td>
                                            <td>#<?php echo $attempt['attempt_number']; ?></td>
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
                                                        <button class="btn btn-sm btn-outline-info" onclick="viewQuizDetails(<?php echo $attempt['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="continueQuiz(<?php echo $attempt['id']; ?>)">
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
