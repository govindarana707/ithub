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
$stmt = $conn->prepare("\n    SELECT q.id, q.title, q.description, q.status, q.course_id, c.title as course_title\n    FROM quizzes q\n    JOIN courses c ON c.id = q.course_id\n    WHERE q.id = ? AND c.instructor_id = ?\n");
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

// Handle actions
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($postedToken)) {
        $_SESSION['error_message'] = 'Invalid request token. Please refresh and try again.';
        header('Location: quiz-questions.php?quiz_id=' . $quizId);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_question') {
        $questionText = trim((string)($_POST['question_text'] ?? ''));
        $questionType = (string)($_POST['question_type'] ?? 'multiple_choice');
        $points = (float)($_POST['points'] ?? 1);

        if ($questionText === '' || strlen($questionText) < 5) {
            $_SESSION['error_message'] = 'Question text must be at least 5 characters.';
            header('Location: quiz-questions.php?quiz_id=' . $quizId);
            exit;
        }

        if (!in_array($questionType, ['multiple_choice', 'true_false', 'short_answer'], true)) {
            $questionType = 'multiple_choice';
        }

        if ($points <= 0) {
            $points = 1;
        }

        // Next order
        $stmt = $conn->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $nextOrder = (int)($stmt->get_result()->fetch_assoc()['next_order'] ?? 1);
        $stmt->close();

        $result = $quizModel->createQuestion([
            'quiz_id' => $quizId,
            'question_text' => $questionText,
            'question_type' => $questionType,
            'points' => $points,
            'question_order' => $nextOrder
        ]);

        if (!($result['success'] ?? false)) {
            $_SESSION['error_message'] = 'Failed to create question: ' . ($result['error'] ?? 'Unknown error');
            header('Location: quiz-questions.php?quiz_id=' . $quizId);
            exit;
        }

        $questionId = (int)($result['question_id'] ?? 0);

        if ($questionType === 'true_false') {
            $correct = (string)($_POST['tf_correct'] ?? 'true');
            $isTrueCorrect = $correct === 'true';

            $quizModel->createOption([
                'question_id' => $questionId,
                'option_text' => 'True',
                'is_correct' => $isTrueCorrect ? 1 : 0,
                'option_order' => 1
            ]);
            $quizModel->createOption([
                'question_id' => $questionId,
                'option_text' => 'False',
                'is_correct' => $isTrueCorrect ? 0 : 1,
                'option_order' => 2
            ]);
        }

        if ($questionType === 'multiple_choice') {
            $options = $_POST['mc_option'] ?? [];
            if (!is_array($options)) {
                $options = [];
            }
            $options = array_values(array_filter(array_map(function ($v) {
                return trim((string)$v);
            }, $options), function ($v) {
                return $v !== '';
            }));

            $correctIndex = (int)($_POST['mc_correct'] ?? -1);

            if (count($options) < 2) {
                // Rollback question if no valid options
                $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
                $stmt->bind_param('ii', $questionId, $quizId);
                $stmt->execute();
                $stmt->close();

                $_SESSION['error_message'] = 'Multiple choice questions require at least 2 options.';
                header('Location: quiz-questions.php?quiz_id=' . $quizId);
                exit;
            }

            if ($correctIndex < 0 || $correctIndex >= count($options)) {
                $correctIndex = 0;
            }

            foreach ($options as $i => $optText) {
                $quizModel->createOption([
                    'question_id' => $questionId,
                    'option_text' => $optText,
                    'is_correct' => $i === $correctIndex ? 1 : 0,
                    'option_order' => $i + 1
                ]);
            }
        }

        $_SESSION['success_message'] = 'Question added successfully!';
        logActivity($_SESSION['user_id'], 'quiz_question_created', "Quiz {$quizId} question added");
        header('Location: quiz-questions.php?quiz_id=' . $quizId);
        exit;
    }

    if ($action === 'delete_question') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        if ($questionId <= 0) {
            $_SESSION['error_message'] = 'Invalid question.';
            header('Location: quiz-questions.php?quiz_id=' . $quizId);
            exit;
        }

        // Ensure question belongs to this quiz
        $stmt = $conn->prepare("SELECT id FROM quiz_questions WHERE id = ? AND quiz_id = ?");
        $stmt->bind_param('ii', $questionId, $quizId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            $_SESSION['error_message'] = 'Question not found.';
            header('Location: quiz-questions.php?quiz_id=' . $quizId);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
        $stmt->bind_param('ii', $questionId, $quizId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $_SESSION['success_message'] = 'Question deleted successfully!';
            logActivity($_SESSION['user_id'], 'quiz_question_deleted', "Quiz {$quizId} question deleted: {$questionId}");
        } else {
            $_SESSION['error_message'] = 'Failed to delete question.';
        }

        header('Location: quiz-questions.php?quiz_id=' . $quizId);
        exit;
    }

    $_SESSION['error_message'] = 'Unknown action.';
    header('Location: quiz-questions.php?quiz_id=' . $quizId);
    exit;
}

// Load questions + options
$stmt = $conn->prepare("\n    SELECT id, quiz_id, question_text, question_type, points, question_order, created_at\n    FROM quiz_questions\n    WHERE quiz_id = ?\n    ORDER BY question_order ASC, id ASC\n");
$stmt->bind_param('i', $quizId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$optionsByQuestion = [];
if (!empty($questions)) {
    $stmt = $conn->prepare("SELECT id, question_id, option_text, is_correct, option_order FROM quiz_options WHERE question_id = ? ORDER BY option_order ASC, id ASC");

    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $stmt->bind_param('i', $qid);
        $stmt->execute();
        $optionsByQuestion[$qid] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $stmt->close();
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
                    </div>
                    <div class="d-flex gap-2">
                        <a href="quizzes.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
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

                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="card-soft">
                            <h5 class="mb-3">Add Question</h5>

                            <form method="POST" id="questionForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="create_question">

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
                                    <textarea class="form-control" name="question_text" rows="4" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Points</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="points" value="1">
                                </div>

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

                                <div id="saSection" class="d-none">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Short answer questions are stored, but automatic grading is not enabled in the current schema.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus me-2"></i>Add Question
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Questions</h5>

                                <?php if (empty($questions)): ?>
                                    <div class="text-center py-4 text-muted">No questions yet.</div>
                                <?php else: ?>
                                    <?php foreach ($questions as $q): ?>
                                        <?php $qid = (int)$q['id']; ?>
                                        <div class="mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-semibold">
                                                        <?php echo (int)$q['question_order']; ?>.
                                                        <?php echo htmlspecialchars($q['question_text']); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        Type: <?php echo htmlspecialchars($q['question_type']); ?>
                                                        | Points: <?php echo htmlspecialchars((string)$q['points']); ?>
                                                    </div>
                                                </div>
                                                <form method="POST" class="m-0" onsubmit="return confirm('Delete this question?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="question_id" value="<?php echo $qid; ?>">
                                                    <button class="btn btn-outline-danger btn-sm" type="submit">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>

                                            <?php if (($q['question_type'] ?? '') !== 'short_answer'): ?>
                                                <div class="mt-2">
                                                    <?php foreach (($optionsByQuestion[$qid] ?? []) as $o): ?>
                                                        <div class="option-item">
                                                            <div>
                                                                <?php echo htmlspecialchars($o['option_text']); ?>
                                                            </div>
                                                            <div>
                                                                <?php if ((int)$o['is_correct'] === 1): ?>
                                                                    <span class="badge bg-success">Correct</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Wrong</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2 text-muted small">Short answer: no options</div>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                if (count >= 8) return;

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

            // attach remove handlers for dynamically added options only
        })();
    </script>
</body>
</html>
