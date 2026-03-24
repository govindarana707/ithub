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
        }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .quiz-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .quiz-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createQuizModal">
                        <i class="fas fa-plus me-2"></i>New Quiz
                    </button>
                </div>

                <!-- Filter Section -->
                <div class="card-soft mb-3">
                    <form class="row g-2" method="GET" id="filterForm">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course_id" id="filterCourse">
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
                            <select class="form-select" name="status" id="filterStatus">
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

                <!-- Quizzes Grid -->
                <div class="row g-3 mb-3" id="quizzesContainer">
                    <?php if (empty($quizzes)): ?>
                        <div class="col-12">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-4x mb-3"></i>
                                <h4>No quizzes found</h4>
                                <p>Create your first quiz to get started!</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($quizzes as $q): ?>
                            <?php $s = $quizStats[(int)$q['id']] ?? ['total_attempts' => 0, 'average_score' => 0, 'pass_rate' => 0]; ?>
                            <div class="col-md-6 col-lg-4" id="quiz-card-<?php echo (int)$q['id']; ?>">
                                <div class="card quiz-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-<?php echo ($q['status'] ?? '') === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst((string)($q['status'] ?? 'draft')); ?>
                                            </span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="quiz-questions.php?quiz_id=<?php echo (int)$q['id']; ?>">
                                                        <i class="fas fa-list-check me-2"></i>Questions
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="editQuiz(<?php echo (int)$q['id']; ?>); return false;">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteQuiz(<?php echo (int)$q['id']; ?>, '<?php echo htmlspecialchars($q['title'] ?? '', ENT_QUOTES); ?>'); return false;">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($q['title']); ?></h5>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($q['course_title'] ?? ''); ?></p>
                                        <div class="quiz-meta">
                                            <span><i class="fas fa-stopwatch me-1"></i><?php echo (int)($q['time_limit_minutes'] ?? 0); ?> min</span>
                                            <span><i class="fas fa-bullseye me-1"></i><?php echo (float)($q['passing_score'] ?? 0); ?>%</span>
                                            <span><i class="fas fa-repeat me-1"></i><?php echo (int)($q['max_attempts'] ?? 0); ?></span>
                                        </div>
                                        <hr>
                                        <div class="row text-center small">
                                            <div class="col-4">
                                                <div class="fw-bold"><?php echo (int)($s['total_attempts'] ?? 0); ?></div>
                                                <div class="text-muted">Attempts</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="fw-bold"><?php echo (float)($s['average_score'] ?? 0); ?>%</div>
                                                <div class="text-muted">Avg Score</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="fw-bold"><?php echo (float)($s['pass_rate'] ?? 0); ?>%</div>
                                                <div class="text-muted">Pass Rate</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="quiz-questions.php?quiz_id=<?php echo (int)$q['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-list-check me-1"></i>Manage Questions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Create Quiz Modal -->
    <div class="modal fade" id="createQuizModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createQuizForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="create_quiz">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course *</label>
                                <select name="course_id" class="form-select" required id="createCourseId">
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
                                <input type="text" name="title" class="form-control" required minlength="3" id="createTitle">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" id="createDescription"></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Time Limit (minutes)</label>
                                <input type="number" name="time_limit_minutes" class="form-control" min="0" value="30">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Passing Score (%)</label>
                                <input type="number" name="passing_score" class="form-control" min="0" max="100" value="70">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Attempts</label>
                                <input type="number" name="max_attempts" class="form-control" min="1" value="3">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="createQuizBtn">
                            <i class="fas fa-plus me-2"></i>Create Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Quiz Modal -->
    <div class="modal fade" id="editQuizModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editQuizForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="update_quiz">
                        <input type="hidden" name="quiz_id" value="" id="editQuizId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <input type="text" class="form-control" id="editCourseName" disabled>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required id="editStatus">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Quiz Title *</label>
                                <input type="text" name="title" class="form-control" required minlength="3" id="editTitle">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" id="editDescription"></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Time Limit (minutes)</label>
                                <input type="number" name="time_limit_minutes" class="form-control" min="0" id="editTimeLimit">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Passing Score (%)</label>
                                <input type="number" name="passing_score" class="form-control" min="0" max="100" id="editPassingScore">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Attempts</label>
                                <input type="number" name="max_attempts" class="form-control" min="1" id="editMaxAttempts">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="updateQuizBtn">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // CSRF Token
    const csrfToken = '<?php echo htmlspecialchars($csrfToken); ?>';

    // Create Quiz
    document.getElementById('createQuizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('createQuizBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Creating...';
        
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>api/quiz_api.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Edit Quiz - Fetch quiz data
    function editQuiz(quizId) {
        fetch('<?php echo BASE_URL; ?>api/quiz_api.php?action=get_quiz&quiz_id=' + quizId, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const quiz = data.data;
                document.getElementById('editQuizId').value = quiz.id;
                document.getElementById('editCourseName').value = quiz.course_title;
                document.getElementById('editTitle').value = quiz.title;
                document.getElementById('editDescription').value = quiz.description || '';
                document.getElementById('editStatus').value = quiz.status;
                document.getElementById('editTimeLimit').value = quiz.time_limit_minutes;
                document.getElementById('editPassingScore').value = quiz.passing_score;
                document.getElementById('editMaxAttempts').value = quiz.max_attempts;
                
                var editModal = new bootstrap.Modal(document.getElementById('editQuizModal'));
                editModal.show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load quiz data.'
            });
        });
    }

    // Update Quiz
    document.getElementById('editQuizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('updateQuizBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Saving...';
        
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>api/quiz_api.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Delete Quiz with confirmation
    function deleteQuiz(quizId, quizTitle) {
        Swal.fire({
            title: 'Delete Quiz?',
            text: 'Are you sure you want to delete "' + quizTitle + '"? This will also delete all questions and attempts.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_quiz');
                formData.append('quiz_id', quizId);
                formData.append('csrf_token', csrfToken);
                
                fetch('<?php echo BASE_URL; ?>api/quiz_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Remove the card from DOM
                            const card = document.getElementById('quiz-card-' + quizId);
                            if (card) {
                                card.remove();
                            }
                            
                            // Check if no more quizzes
                            const container = document.getElementById('quizzesContainer');
                            if (container.children.length === 0) {
                                container.innerHTML = `
                                    <div class="col-12">
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-list fa-4x mb-3"></i>
                                            <h4>No quizzes found</h4>
                                            <p>Create your first quiz to get started!</p>
                                        </div>
                                    </div>
                                `;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                });
            }
        });
    }

    // Helper function to add quiz card dynamically
    function addQuizCard(quiz, stats) {
        const container = document.getElementById('quizzesContainer');
        
        // Remove empty state if exists
        const emptyMsg = container.querySelector('.text-center');
        if (emptyMsg) {
            container.innerHTML = '';
        }
        
        const card = `
            <div class="col-md-6 col-lg-4" id="quiz-card-${quiz.id}">
                <div class="card quiz-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-${quiz.status === 'published' ? 'success' : 'warning'}">
                                ${quiz.status}
                            </span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="quiz-questions.php?quiz_id=${quiz.id}">
                                        <i class="fas fa-list-check me-2"></i>Questions
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editQuiz(${quiz.id}); return false;">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteQuiz(${quiz.id}, '${quiz.title}'); return false;">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <h5 class="card-title">${quiz.title}</h5>
                        <p class="text-muted small mb-2">${quiz.course_title}</p>
                        <div class="quiz-meta">
                            <span><i class="fas fa-stopwatch me-1"></i>${quiz.time_limit_minutes} min</span>
                            <span><i class="fas fa-bullseye me-1"></i>${quiz.passing_score}%</span>
                            <span><i class="fas fa-repeat me-1"></i>${quiz.max_attempts}</span>
                        </div>
                        <hr>
                        <div class="row text-center small">
                            <div class="col-4">
                                <div class="fw-bold">${stats ? stats.total_attempts : 0}</div>
                                <div class="text-muted">Attempts</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">${stats ? stats.average_score : 0}%</div>
                                <div class="text-muted">Avg Score</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">${stats ? stats.pass_rate : 0}%</div>
                                <div class="text-muted">Pass Rate</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="quiz-questions.php?quiz_id=${quiz.id}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-list-check me-1"></i>Manage Questions
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', card);
    }
    
    // Show messages from PHP sessions
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo htmlspecialchars($_SESSION['error_message']); ?>'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo htmlspecialchars($_SESSION['success_message']); ?>'
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    </script>
</body>
</html>
