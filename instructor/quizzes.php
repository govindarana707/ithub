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
$csrfToken = generateCSRFToken();

$instructorCourses = $instructor->getInstructorCourses($instructorId, null, 500, 0);
$courseMap = [];
foreach ($instructorCourses as $c) {
    $courseMap[(int)$c['id']] = $c;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($postedToken)) {
        $_SESSION['error_message'] = 'Invalid request token. Please refresh and try again.';
        header('Location: quizzes.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_quiz') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $timeLimit = (int)($_POST['time_limit_minutes'] ?? 30);
        $passingScore = (float)($_POST['passing_score'] ?? 70);
        $maxAttempts = (int)($_POST['max_attempts'] ?? 3);
        $status = sanitize($_POST['status'] ?? 'draft');
        $lessonId = (int)($_POST['lesson_id'] ?? 0);

        if (!isset($courseMap[$courseId])) {
            $_SESSION['error_message'] = 'Invalid course selection.';
            header('Location: quizzes.php');
            exit;
        }

        if ($title === '' || strlen($title) < 3) {
            $_SESSION['error_message'] = 'Quiz title must be at least 3 characters.';
            header('Location: quizzes.php');
            exit;
        }

        if ($timeLimit < 0) {
            $timeLimit = 0;
        }

        if ($passingScore < 0) {
            $passingScore = 0;
        }
        if ($passingScore > 100) {
            $passingScore = 100;
        }

        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }

        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        $result = $quizModel->createQuiz([
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'title' => $title,
            'description' => $description,
            'time_limit_minutes' => $timeLimit,
            'passing_score' => $passingScore,
            'max_attempts' => $maxAttempts,
            'status' => $status,
        ]);

        if (($result['success'] ?? false)) {
            $_SESSION['success_message'] = 'Quiz created successfully!';
            logActivity($_SESSION['user_id'], 'quiz_created', "Created quiz: {$title}");
        } else {
            $_SESSION['error_message'] = 'Failed to create quiz: ' . ($result['error'] ?? 'Unknown error');
        }

        header('Location: quizzes.php');
        exit;
    }

    if ($action === 'update_quiz') {
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $quiz = $quizModel->getQuizById($quizId);

        if (!$quiz) {
            $_SESSION['error_message'] = 'Quiz not found.';
            header('Location: quizzes.php');
            exit;
        }

        $courseId = (int)($quiz['course_id'] ?? 0);
        if (!isset($courseMap[$courseId])) {
            $_SESSION['error_message'] = 'Access denied.';
            header('Location: quizzes.php');
            exit;
        }

        $data = [
            'title' => sanitize($_POST['title'] ?? $quiz['title']),
            'description' => sanitize($_POST['description'] ?? $quiz['description']),
            'time_limit_minutes' => (int)($_POST['time_limit_minutes'] ?? $quiz['time_limit_minutes']),
            'passing_score' => (float)($_POST['passing_score'] ?? $quiz['passing_score']),
            'max_attempts' => (int)($_POST['max_attempts'] ?? $quiz['max_attempts']),
            'status' => sanitize($_POST['status'] ?? $quiz['status']),
        ];

        if ($data['title'] === '' || strlen($data['title']) < 3) {
            $_SESSION['error_message'] = 'Quiz title must be at least 3 characters.';
            header('Location: quizzes.php?edit=' . $quizId);
            exit;
        }

        if (!in_array($data['status'], ['draft', 'published'], true)) {
            $data['status'] = 'draft';
        }

        if ($data['max_attempts'] < 1) {
            $data['max_attempts'] = 1;
        }

        if ($data['passing_score'] < 0) {
            $data['passing_score'] = 0;
        }
        if ($data['passing_score'] > 100) {
            $data['passing_score'] = 100;
        }

        $ok = $quizModel->updateQuiz($quizId, $data);
        if ($ok) {
            $_SESSION['success_message'] = 'Quiz updated successfully!';
            logActivity($_SESSION['user_id'], 'quiz_updated', "Updated quiz ID: {$quizId}");
            header('Location: quizzes.php');
        } else {
            $_SESSION['error_message'] = 'Failed to update quiz.';
            header('Location: quizzes.php?edit=' . $quizId);
        }
        exit;
    }

    if ($action === 'delete_quiz') {
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $quiz = $quizModel->getQuizById($quizId);

        if (!$quiz) {
            $_SESSION['error_message'] = 'Quiz not found.';
            header('Location: quizzes.php');
            exit;
        }

        $courseId = (int)($quiz['course_id'] ?? 0);
        if (!isset($courseMap[$courseId])) {
            $_SESSION['error_message'] = 'Access denied.';
            header('Location: quizzes.php');
            exit;
        }

        $ok = $quizModel->deleteQuiz($quizId);
        if ($ok) {
            $_SESSION['success_message'] = 'Quiz deleted successfully!';
            logActivity($_SESSION['user_id'], 'quiz_deleted', "Deleted quiz ID: {$quizId}");
        } else {
            $_SESSION['error_message'] = 'Failed to delete quiz.';
        }

        header('Location: quizzes.php');
        exit;
    }

    $_SESSION['error_message'] = 'Unknown action.';
    header('Location: quizzes.php');
    exit;
}

$filterCourseId = (int)($_GET['course_id'] ?? 0);
$filterStatus = trim((string)($_GET['status'] ?? ''));
$editId = (int)($_GET['edit'] ?? 0);

$quizzes = $quizModel->getQuizzesByInstructor($instructorId);

if ($filterCourseId > 0) {
    $quizzes = array_values(array_filter($quizzes, function ($q) use ($filterCourseId) {
        return (int)($q['course_id'] ?? 0) === $filterCourseId;
    }));
}

if ($filterStatus !== '' && in_array($filterStatus, ['draft', 'published'], true)) {
    $quizzes = array_values(array_filter($quizzes, function ($q) use ($filterStatus) {
        return (string)($q['status'] ?? '') === $filterStatus;
    }));
}

$quizStats = [];
foreach ($quizzes as $q) {
    $qid = (int)$q['id'];
    $quizStats[$qid] = $quizModel->getQuizStats($qid);
}

$editQuiz = null;
if ($editId > 0) {
    $candidate = $quizModel->getQuizById($editId);
    if ($candidate) {
        $cid = (int)($candidate['course_id'] ?? 0);
        if (isset($courseMap[$cid])) {
            $editQuiz = $candidate;
        } else {
            $_SESSION['error_message'] = 'Access denied.';
            header('Location: quizzes.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Quiz not found.';
        header('Location: quizzes.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .card-soft {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        .quiz-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
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
                        <h1 class="mb-1">Quizzes</h1>
                        <div class="text-muted">Create and manage quizzes for your courses</div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="card-soft mb-3">
                    <form class="row g-2" method="GET">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course_id">
                                <option value="0">All Courses</option>
                                <?php foreach ($instructorCourses as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$filterCourseId === (int)$c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $filterStatus === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button class="btn btn-primary w-100" type="submit">Filter</button>
                                <a class="btn btn-outline-secondary" href="quizzes.php">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($editQuiz): ?>
                    <div class="card-soft mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Edit Quiz</h5>
                            <a class="btn btn-outline-secondary btn-sm" href="quizzes.php">Close</a>
                        </div>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="update_quiz">
                            <input type="hidden" name="quiz_id" value="<?php echo (int)$editQuiz['id']; ?>">

                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($editQuiz['course_title'] ?? ''); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?php echo ($editQuiz['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($editQuiz['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editQuiz['title'] ?? ''); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editQuiz['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Time Limit (minutes)</label>
                                <input type="number" name="time_limit_minutes" class="form-control" min="0" value="<?php echo (int)($editQuiz['time_limit_minutes'] ?? 0); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Passing Score (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="passing_score" class="form-control" value="<?php echo htmlspecialchars((string)($editQuiz['passing_score'] ?? '70')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Attempts</label>
                                <input type="number" name="max_attempts" class="form-control" min="1" value="<?php echo (int)($editQuiz['max_attempts'] ?? 3); ?>">
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a class="btn btn-outline-secondary" href="quizzes.php">Cancel</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="card-soft mb-3">
                    <h5 class="mb-3">Create Quiz</h5>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="create_quiz">

                        <div class="col-md-6">
                            <label class="form-label">Course *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach ($instructorCourses as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Quiz Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Time Limit (minutes)</label>
                            <input type="number" name="time_limit_minutes" class="form-control" min="0" value="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passing Score (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="passing_score" class="form-control" value="70">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Attempts</label>
                            <input type="number" name="max_attempts" class="form-control" min="1" value="3">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lesson ID (optional)</label>
                            <input type="number" name="lesson_id" class="form-control" min="0" value="0">
                            <div class="form-text">Set to 0 for course-level quizzes</div>
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Create Quiz
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Quizzes</h5>

                        <?php if (empty($quizzes)): ?>
                            <div class="text-center py-4 text-muted">No quizzes found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Quiz</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                            <th class="text-center">Attempts</th>
                                            <th class="text-center">Avg</th>
                                            <th class="text-center">Pass</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quizzes as $q): ?>
                                            <?php $s = $quizStats[(int)$q['id']] ?? ['total_attempts' => 0, 'average_score' => 0, 'pass_rate' => 0]; ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($q['title']); ?></div>
                                                    <div class="quiz-meta">
                                                        <span><i class="fas fa-stopwatch me-1"></i><?php echo (int)($q['time_limit_minutes'] ?? 0); ?> min</span>
                                                        <span><i class="fas fa-bullseye me-1"></i><?php echo (float)($q['passing_score'] ?? 0); ?>%</span>
                                                        <span><i class="fas fa-repeat me-1"></i><?php echo (int)($q['max_attempts'] ?? 0); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($q['course_title'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($q['status'] ?? '') === 'published' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst((string)($q['status'] ?? 'draft')); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?php echo (int)($s['total_attempts'] ?? 0); ?></td>
                                                <td class="text-center"><?php echo (float)($s['average_score'] ?? 0); ?>%</td>
                                                <td class="text-center"><?php echo (float)($s['pass_rate'] ?? 0); ?>%</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a class="btn btn-outline-secondary btn-sm" href="quiz-questions.php?quiz_id=<?php echo (int)$q['id']; ?>">
                                                            <i class="fas fa-list-check me-1"></i>Questions
                                                        </a>
                                                        <a class="btn btn-outline-primary btn-sm" href="quizzes.php?edit=<?php echo (int)$q['id']; ?>">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </a>
                                                        <form method="POST" class="m-0" onsubmit="return confirm('Delete this quiz?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="delete_quiz">
                                                            <input type="hidden" name="quiz_id" value="<?php echo (int)$q['id']; ?>">
                                                            <button class="btn btn-outline-danger btn-sm" type="submit">
                                                                <i class="fas fa-trash me-1"></i>Delete
                                                            </button>
                                                        </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
