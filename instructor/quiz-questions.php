<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Quiz.php';

$instructor = new Instructor();
$quizModel = new Quiz();

$instructorId = $_SESSION['user_id'];
$csrfToken = generateCSRFToken();

$quizId = (int)($_GET['quiz_id'] ?? 0);
if ($quizId <= 0) {
    $_SESSION['error_message'] = 'Invalid quiz.';
    header('Location: quizzes.php');
    exit;
}

$conn = connectDB();

// Verify quiz ownership (must belong to instructor's course)
$stmt = $conn->prepare("
    SELECT q.id, q.title, q.description, q.status, q.course_id, q.time_limit_minutes, q.passing_score, q.max_attempts, c.title as course_title
    FROM quizzes q
    JOIN courses_new c ON c.id = q.course_id
    WHERE q.id = ? AND c.instructor_id = ?
");
$stmt->bind_param('ii', $quizId, $instructorId);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quiz) {
    $conn->close();
    $_SESSION['error_message'] = 'Access denied or quiz not found.';
    header('Location: quizzes.php');
    exit;
}

// Load questions + options
$stmt = $conn->prepare("
    SELECT id, quiz_id, question_text, question_type, points, question_order, created_at
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_order ASC, id ASC
");
$stmt->bind_param('i', $quizId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$optionsByQuestion = [];
if (!empty($questions)) {
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $stmt = $conn->prepare("SELECT id, question_id, option_text, is_correct, option_order FROM quiz_options WHERE question_id = ? ORDER BY option_order ASC, id ASC");
        $stmt->bind_param('i', $qid);
        $stmt->execute();
        $optionsByQuestion[$qid] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Questions - Instructor</title>
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
        .option-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0.4rem 0.6rem;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .question-card {
            transition: all 0.3s ease;
        }
        .question-card:hover {
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
                <a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle me-1"></i> Quizzes</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="mb-1">Quiz Questions</h1>
                        <div class="text-muted">
                            <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                            <span class="text-muted">(<?php echo htmlspecialchars($quiz['course_title']); ?>)</span>
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-<?php echo ($quiz['status'] ?? '') === 'published' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($quiz['status'] ?? 'draft'); ?>
                            </span>
                            <span class="text-muted small">
                                <i class="fas fa-stopwatch me-1"></i><?php echo (int)($quiz['time_limit_minutes'] ?? 0); ?> min
                                <i class="fas fa-bullseye ms-2 me-1"></i><?php echo (float)($quiz['passing_score'] ?? 0); ?>%
                                <i class="fas fa-repeat ms-2 me-1"></i><?php echo (int)($quiz['max_attempts'] ?? 0); ?> attempts
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="quizzes.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="card-soft">
                            <h5 class="mb-3">
                                <i class="fas fa-plus-circle me-2"></i>Add Question
                            </h5>

                            <form id="questionForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="create_question">
                                <input type="hidden" name="quiz_id" value="<?php echo (int)$quizId; ?>">

                                <div class="mb-3">
                                    <label class="form-label">Question Type</label>
                                    <select class="form-select" name="question_type" id="questionType">
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="true_false">True / False</option>
                                        <option value="short_answer">Short Answer (manual review)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Question Text</label>
                                    <textarea class="form-control" name="question_text" id="questionText" rows="4" required placeholder="Enter your question here..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Points</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="points" value="1">
                                </div>

                                <!-- Multiple Choice Options -->
                                <div id="mcSection">
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <label class="form-label mb-0">Options</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addOptionBtn">
                                            <i class="fas fa-plus me-1"></i>Add Option
                                        </button>
                                    </div>
                                    <div id="optionsWrap">
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">1</span>
                                            <input type="text" class="form-control" name="mc_option[]" placeholder="Option text" required>
                                            <span class="input-group-text">Correct</span>
                                            <span class="input-group-text">
                                                <input class="form-check-input mt-0" type="radio" name="mc_correct" value="0" checked>
                                            </span>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">2</span>
                                            <input type="text" class="form-control" name="mc_option[]" placeholder="Option text" required>
                                            <span class="input-group-text">Correct</span>
                                            <span class="input-group-text">
                                                <input class="form-check-input mt-0" type="radio" name="mc_correct" value="1">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-text">Choose which option is correct.</div>
                                </div>

                                <!-- True/False Section -->
                                <div id="tfSection" class="d-none">
                                    <label class="form-label">Correct Answer</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tf_correct" value="true" id="tfTrue" checked>
                                        <label class="form-check-label" for="tfTrue">True</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tf_correct" value="false" id="tfFalse">
                                        <label class="form-check-label" for="tfFalse">False</label>
                                    </div>
                                    <div class="form-text">Options will be created automatically.</div>
                                </div>

                                <!-- Short Answer Section -->
                                <div id="saSection" class="d-none">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Short answer questions are stored, but automatic grading is not enabled in the current schema.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-success" id="addQuestionBtn">
                                        <i class="fas fa-plus me-2"></i>Add Question
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-list-ol me-2"></i>Questions 
                                    <span class="badge bg-primary" id="questionCount"><?php echo count($questions); ?></span>
                                </h5>

                                <div id="questionsContainer">
                                    <?php if (empty($questions)): ?>
                                        <div class="text-center py-5 text-muted" id="noQuestionsMsg">
                                            <i class="fas fa-clipboard-question fa-4x mb-3"></i>
                                            <h4>No questions yet</h4>
                                            <p>Add your first question using the form on the left!</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($questions as $q): ?>
                                            <?php $qid = (int)$q['id']; ?>
                                            <div class="card mb-3 question-card" id="question-<?php echo $qid; ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold mb-2">
                                                                <span class="badge bg-secondary me-2"><?php echo (int)$q['question_order']; ?></span>
                                                                <?php echo htmlspecialchars($q['question_text']); ?>
                                                            </div>
                                                            <div class="text-muted small">
                                                                <span class="badge bg-info me-1"><?php echo htmlspecialchars($q['question_type']); ?></span>
                                                                <span><i class="fas fa-star me-1"></i><?php echo htmlspecialchars((string)$q['points']); ?> points</span>
                                                            </div>
                                                        </div>
                                                        <div class="ms-3">
                                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteQuestion(<?php echo $qid; ?>)" title="Delete Question">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <?php if (($q['question_type'] ?? '') !== 'short_answer'): ?>
                                                        <div class="mt-3">
                                                            <?php foreach (($optionsByQuestion[$qid] ?? []) as $o): ?>
                                                                <div class="option-item">
                                                                    <div>
                                                                        <?php echo htmlspecialchars($o['option_text']); ?>
                                                                    </div>
                                                                    <div>
                                                                        <?php if ((int)$o['is_correct'] === 1): ?>
                                                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Correct</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">Wrong</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mt-3 text-muted small">
                                                            <i class="fas fa-pen me-1"></i>Short answer: no options (manual review)
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const csrfToken = '<?php echo htmlspecialchars($csrfToken); ?>';
    const quizId = <?php echo (int)$quizId; ?>;

    // Question type toggle
    (function() {
        var typeEl = document.getElementById('questionType');
        var mc = document.getElementById('mcSection');
        var tf = document.getElementById('tfSection');
        var sa = document.getElementById('saSection');

        function syncSections() {
            var v = typeEl.value;
            mc.classList.toggle('d-none', v !== 'multiple_choice');
            tf.classList.toggle('d-none', v !== 'true_false');
            sa.classList.toggle('d-none', v !== 'short_answer');
        }

        typeEl.addEventListener('change', syncSections);
        syncSections();

        // Add option button
        var addBtn = document.getElementById('addOptionBtn');
        var wrap = document.getElementById('optionsWrap');

        function renumber() {
            var groups = wrap.querySelectorAll('.input-group');
            groups.forEach(function(g, idx) {
                var num = g.querySelector('.input-group-text');
                if (num) num.textContent = String(idx + 1);

                var radio = g.querySelector('input[type="radio"]');
                if (radio) radio.value = String(idx);
            });
        }

        addBtn.addEventListener('click', function() {
            var count = wrap.querySelectorAll('.input-group').length;
            if (count >= 8) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Reached',
                    text: 'Maximum 8 options allowed.'
                });
                return;
            }

            var div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML =
                '<span class="input-group-text">' + (count + 1) + '</span>' +
                '<input type="text" class="form-control" name="mc_option[]" placeholder="Option text" required>' +
                '<span class="input-group-text">Correct</span>' +
                '<span class="input-group-text">' +
                '<input class="form-check-input mt-0" type="radio" name="mc_correct" value="' + count + '">' +
                '</span>' +
                '<button type="button" class="btn btn-outline-danger" data-remove="1"><i class="fas fa-times"></i></button>';

            wrap.appendChild(div);

            div.querySelector('[data-remove="1"]').addEventListener('click', function() {
                div.remove();
                renumber();
            });

            renumber();
        });
    })();

    // Add question via AJAX
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('addQuestionBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Adding...';
        
        const formData = new FormData(this);
        
        // Handle options for multiple choice
        if (document.getElementById('questionType').value === 'multiple_choice') {
            const options = [];
            document.querySelectorAll('input[name="mc_option[]"]').forEach(input => {
                options.push(input.value);
            });
            formData.append('options', JSON.stringify(options));
            
            const correctRadio = document.querySelector('input[name="mc_correct"]:checked');
            formData.append('correct_index', correctRadio ? correctRadio.value : 0);
        } else if (document.getElementById('questionType').value === 'true_false') {
            const tfCorrect = document.querySelector('input[name="tf_correct"]:checked');
            formData.append('tf_correct', tfCorrect ? tfCorrect.value : 'true');
        }
        
        formData.set('question_text', document.getElementById('questionText').value);
        
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

    // Delete question with confirmation
    function deleteQuestion(questionId) {
        Swal.fire({
            title: 'Delete Question?',
            text: 'Are you sure you want to delete this question? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_question');
                formData.append('question_id', questionId);
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
                            // Remove the question card
                            const card = document.getElementById('question-' + questionId);
                            if (card) {
                                card.remove();
                            }
                            
                            // Update question count
                            const countBadge = document.getElementById('questionCount');
                            let currentCount = parseInt(countBadge.textContent);
                            countBadge.textContent = currentCount - 1;
                            
                            // Show "no questions" message if empty
                            const container = document.getElementById('questionsContainer');
                            const remainingCards = container.querySelectorAll('.question-card');
                            if (remainingCards.length === 0) {
                                container.innerHTML = `
                                    <div class="text-center py-5 text-muted" id="noQuestionsMsg">
                                        <i class="fas fa-clipboard-question fa-4x mb-3"></i>
                                        <h4>No questions yet</h4>
                                        <p>Add your first question using the form on the left!</p>
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                });
            }
        });
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
