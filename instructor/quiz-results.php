<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';

$instructor = new Instructor();
$courseModel = new Course();
$quizModel = new Quiz();

$instructorId = $_SESSION['user_id'];

$quizId = (int)($_GET['quiz_id'] ?? 0);

if (!$quizId) {
    $_SESSION['error_message'] = 'Invalid quiz ID.';
    header('Location: quizzes.php');
    exit;
}

$quiz = $quizModel->getQuizById($quizId);
if (!$quiz) {
    $_SESSION['error_message'] = 'Quiz not found.';
    header('Location: quizzes.php');
    exit;
}

// Check if instructor owns this quiz
$instructorCourses = $instructor->getInstructorCourses($instructorId, null, 500, 0);
$courseMap = [];
foreach ($instructorCourses as $c) {
    $courseMap[(int)$c['id']] = $c;
}

if (!isset($courseMap[(int)$quiz['course_id']])) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: quizzes.php');
    exit;
}

$attempts = $quizModel->getQuizAttemptsForInstructor($quizId);
$quizStats = $quizModel->getQuizStats($quizId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($quiz['title']); ?> - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                <a class="nav-link" href="courses.php"><i class="fas fa-chalkboard-teacher me-1"></i> My Courses</a>
                <a class="nav-link" href="students.php"><i class="fas fa-users me-1"></i> Students</a>
                <a class="nav-link active" href="quizzes.php"><i class="fas fa-question-circle me-1"></i> Quizzes</a>
                <a class="nav-link" href="analytics.php"><i class="fas fa-chart-line me-1"></i> Analytics</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="create-course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-question-circle me-2"></i> Quizzes
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="mb-1">Quiz Results</h1>
                        <div class="text-muted"><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo htmlspecialchars($quiz['course_title']); ?></div>
                    </div>
                    <a href="quizzes.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                    </a>
                </div>

                <div class="card-soft mb-3">
                    <h5>Quiz Statistics</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-primary"><?php echo (int)$quizStats['total_attempts']; ?></div>
                                <div class="text-muted">Total Attempts</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-success"><?php echo (int)$quizStats['completed_attempts']; ?></div>
                                <div class="text-muted">Completed</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-info"><?php echo htmlspecialchars($quizStats['average_score']); ?>%</div>
                                <div class="text-muted">Avg Score</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-warning"><?php echo htmlspecialchars($quizStats['pass_rate']); ?>%</div>
                                <div class="text-muted">Pass Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Student Attempts</h5>

                        <?php if (empty($attempts)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                <h6>No attempts found</h6>
                                <p class="mb-0">Students haven't taken this quiz yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Attempt #</th>
                                            <th>Score</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                            <th>Started</th>
                                            <th>Completed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attempts as $attempt): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($attempt['student_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($attempt['student_email']); ?></small>
                                                </td>
                                                <td><?php echo (int)$attempt['attempt_number']; ?></td>
                                                <td><?php echo $attempt['status'] === 'completed' ? htmlspecialchars($attempt['score'] . '/' . $attempt['total_points']) : '-'; ?></td>
                                                <td>
                                                    <?php if ($attempt['status'] === 'completed'): ?>
                                                        <span class="badge bg-<?php echo (float)$attempt['percentage'] >= (float)$quiz['passing_score'] ? 'success' : 'danger'; ?>">
                                                            <?php echo htmlspecialchars($attempt['percentage']); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($attempt['status'] === 'completed'): ?>
                                                        <span class="badge bg-<?php echo (int)$attempt['passed'] ? 'success' : 'danger'; ?>">
                                                            <?php echo (int)$attempt['passed'] ? 'Passed' : 'Failed'; ?>
                                                        </span>
                                                    <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                        <span class="badge bg-warning">In Progress</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not Started</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $attempt['started_at'] ? date('M j, Y H:i', strtotime($attempt['started_at'])) : '-'; ?></td>
                                                <td><?php echo $attempt['completed_at'] ? date('M j, Y H:i', strtotime($attempt['completed_at'])) : '-'; ?></td>
                                                <td>
                                                    <?php if ($attempt['status'] === 'completed'): ?>
                                                        <a href="quiz-result.php?attempt_id=<?php echo (int)$attempt['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>View Details
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>