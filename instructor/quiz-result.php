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

$attemptId = (int)($_GET['attempt_id'] ?? 0);

if (!$attemptId) {
    $_SESSION['error_message'] = 'Invalid attempt ID.';
    header('Location: quizzes.php');
    exit;
}

$attempt = $quizModel->getQuizAttempt($attemptId);
if (!$attempt) {
    $_SESSION['error_message'] = 'Attempt not found.';
    header('Location: quizzes.php');
    exit;
}

// Check if instructor owns this quiz
$instructorCourses = $instructor->getInstructorCourses($instructorId, null, 500, 0);
$courseMap = [];
foreach ($instructorCourses as $c) {
    $courseMap[(int)$c['id']] = $c;
}

$quiz = $quizModel->getQuizById($attempt['quiz_id']);
if (!$quiz || !isset($courseMap[(int)$quiz['course_id']])) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: quizzes.php');
    exit;
}

$results = $quizModel->getQuizAttemptResults($attemptId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result Details - Instructor Dashboard</title>
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
                        <h1 class="mb-1">Quiz Result Details</h1>
                        <div class="text-muted"><?php echo htmlspecialchars($attempt['quiz_title']); ?></div>
                    </div>
                    <a href="quiz-results.php?quiz_id=<?php echo (int)$attempt['quiz_id']; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Results
                    </a>
                </div>

                <div class="card-soft mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Attempt Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Student:</strong></td>
                                    <td><?php echo htmlspecialchars($results['attempt']['student_name'] ?? 'Unknown'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Attempt #:</strong></td>
                                    <td><?php echo (int)$attempt['attempt_number']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
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
                                </tr>
                                <tr>
                                    <td><strong>Score:</strong></td>
                                    <td><?php echo $attempt['status'] === 'completed' ? htmlspecialchars($attempt['score'] . '/' . $attempt['total_points']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Percentage:</strong></td>
                                    <td><?php echo $attempt['status'] === 'completed' ? htmlspecialchars($attempt['percentage'] . '%') : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Started:</strong></td>
                                    <td><?php echo $attempt['started_at'] ? date('M j, Y H:i:s', strtotime($attempt['started_at'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Completed:</strong></td>
                                    <td><?php echo $attempt['completed_at'] ? date('M j, Y H:i:s', strtotime($attempt['completed_at'])) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Summary</h5>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="h3 text-success"><?php echo (int)$results['correct_count']; ?></div>
                                    <div class="text-muted">Correct</div>
                                </div>
                                <div class="col-4">
                                    <div class="h3 text-danger"><?php echo (int)$results['incorrect_count']; ?></div>
                                    <div class="text-muted">Incorrect</div>
                                </div>
                                <div class="col-4">
                                    <div class="h3 text-warning"><?php echo (int)$results['unanswered_count']; ?></div>
                                    <div class="text-muted">Unanswered</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Question Details</h5>

                        <?php if (empty($results['questions'])): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-question-circle fa-3x mb-3"></i>
                                <h6>No questions found</h6>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="questionsAccordion">
                                <?php foreach ($results['questions'] as $index => $question): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#question<?php echo $index; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <span>Question <?php echo $index + 1; ?> (<?php echo (float)$question['points']; ?> points)</span>
                                                    <span class="badge bg-<?php echo $question['is_correct'] === 1 ? 'success' : ($question['is_correct'] === 0 ? 'danger' : 'warning'); ?>">
                                                        <?php echo $question['is_correct'] === 1 ? 'Correct' : ($question['is_correct'] === 0 ? 'Incorrect' : 'Unanswered'); ?>
                                                    </span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="question<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>">
                                            <div class="accordion-body">
                                                <p class="fw-semibold"><?php echo htmlspecialchars($question['question_text']); ?></p>

                                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                    <div class="mb-3">
                                                        <?php foreach ($question['options'] as $option): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                       <?php echo $option['selected'] ? 'checked' : ''; ?>
                                                                       disabled>
                                                                <label class="form-check-label <?php echo $option['is_correct'] ? 'text-success fw-semibold' : ($option['selected'] && !$option['is_correct'] ? 'text-danger' : ''); ?>">
                                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                                    <?php if ($option['is_correct']): ?>
                                                                        <i class="fas fa-check text-success ms-2"></i>
                                                                    <?php elseif ($option['selected'] && !$option['is_correct']): ?>
                                                                        <i class="fas fa-times text-danger ms-2"></i>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                                    <div class="mb-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                   value="true" <?php echo $question['user_answer'] === 'true' ? 'checked' : ''; ?> disabled>
                                                            <label class="form-check-label">
                                                                True
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                   value="false" <?php echo $question['user_answer'] === 'false' ? 'checked' : ''; ?> disabled>
                                                            <label class="form-check-label">
                                                                False
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">Student's Answer:</label>
                                                        <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($question['user_answer'] ?? ''); ?></textarea>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($question['correct_answer']): ?>
                                                    <div class="alert alert-info">
                                                        <strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['correct_answer']); ?>
                                                    </div>
                                                <?php endif; ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>