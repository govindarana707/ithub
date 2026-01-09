<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../models/Quiz.php';

$quiz = new Quiz();
$userId = $_SESSION['user_id'];
$quizId = intval($_GET['quiz_id'] ?? 0);

if ($quizId <= 0) {
    $_SESSION['error_message'] = 'Invalid quiz ID';
    redirect('my-courses.php');
}

// Get quiz details
$quizData = $quiz->getQuizById($quizId);
if (!$quizData) {
    $_SESSION['error_message'] = 'Quiz not found';
    redirect('my-courses.php');
}

// Check if student is enrolled in the course
$conn = connectDB();
$stmt = $conn->prepare("SELECT course_id FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quizId);
$stmt->execute();
$quizCourse = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $userId, $quizCourse['course_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error_message'] = 'You must be enrolled in the course to take this quiz';
    redirect('my-courses.php');
}

// Check if there's an active attempt
$activeAttempt = $quiz->getActiveAttempt($userId, $quizId);

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    if (!$activeAttempt) {
        $_SESSION['error_message'] = 'No active quiz attempt found';
        redirect('my-courses.php');
    }
    
    $answers = $_POST['answers'] ?? [];
    $result = $quiz->submitQuizAttempt($activeAttempt['id'], $answers);
    
    if ($result['success']) {
        header("Location: quiz-result.php?attempt_id=" . $activeAttempt['id']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to submit quiz: ' . $result['error'];
    }
}

// Get quiz questions
$questions = $quiz->getQuizQuestions($quizId);

// Start new attempt if none exists
if (!$activeAttempt) {
    $result = $quiz->startQuizAttempt($userId, $quizId);
    if (!$result['success']) {
        $_SESSION['error_message'] = 'Failed to start quiz: ' . $result['error'];
        redirect('my-courses.php');
    }
    
    // Get the newly created attempt details
    $activeAttempt = $quiz->getActiveAttempt($userId, $quizId);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quizData['title']); ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .question-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .option-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-card:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .option-card.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        .progress-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #e9ecef;
            transition: all 0.3s ease;
        }
        .progress-dot.active {
            background-color: #007bff;
            transform: scale(1.2);
        }
        .progress-dot.answered {
            background-color: #28a745;
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
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="my-courses.php"><i class="fas fa-graduation-cap me-2"></i>My Courses</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="quiz-container">
            <!-- Quiz Header -->
            <div class="card question-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2"><?php echo htmlspecialchars($quizData['title']); ?></h3>
                            <p class="text-muted mb-0">
                                <i class="fas fa-question-circle me-1"></i><?php echo count($questions); ?> questions
                                <span class="mx-2">|</span>
                                <i class="fas fa-clock me-1"></i><?php echo $quizData['time_limit_minutes']; ?> minutes
                                <span class="mx-2">|</span>
                                <i class="fas fa-trophy me-1"></i>Passing: <?php echo $quizData['passing_score']; ?>%
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="timer" id="quizTimer">
                                <i class="fas fa-hourglass-half me-1"></i>
                                <span id="timeRemaining"><?php echo $quizData['time_limit_minutes'] * 60; ?></span>s
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <?php for ($i = 1; $i <= count($questions); $i++): ?>
                    <div class="progress-dot" data-question="<?php echo $i - 1; ?>"></div>
                <?php endfor; ?>
            </div>

            <!-- Quiz Form -->
            <form method="POST" id="quizForm">
                <input type="hidden" name="submit_quiz" value="1">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="card question-card question-container" data-question-index="<?php echo $index; ?>" <?php echo $index > 0 ? 'style="display: none;"' : ''; ?>>
                        <div class="card-header">
                            <h5 class="mb-0">
                                Question <?php echo $index + 1; ?> of <?php echo count($questions); ?>
                                <span class="badge bg-info float-end"><?php echo $question['points']; ?> point(s)</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="lead"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <?php
                                $conn = connectDB();
                                $stmt = $conn->prepare("SELECT * FROM quiz_options WHERE question_id = ? ORDER BY option_order");
                                $stmt->bind_param("i", $question['id']);
                                $stmt->execute();
                                $options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                $conn->close();
                                ?>
                                
                                <div class="options-container">
                                    <?php foreach ($options as $option): ?>
                                        <div class="option-card" onclick="selectOption(this, <?php echo $question['id']; ?>, <?php echo $option['id']; ?>)">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $option['id']; ?>" id="option_<?php echo $option['id']; ?>">
                                                </div>
                                                <label class="form-check-label flex-grow-1" for="option_<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <div class="options-container">
                                    <div class="option-card" onclick="selectOption(this, <?php echo $question['id']; ?>, 'true')">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" value="true" id="true_<?php echo $question['id']; ?>">
                                            </div>
                                            <label class="form-check-label flex-grow-1" for="true_<?php echo $question['id']; ?>">
                                                <i class="fas fa-check-circle text-success me-2"></i>True
                                            </label>
                                        </div>
                                    </div>
                                    <div class="option-card" onclick="selectOption(this, <?php echo $question['id']; ?>, 'false')">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" value="false" id="false_<?php echo $question['id']; ?>">
                                            </div>
                                            <label class="form-check-label flex-grow-1" for="false_<?php echo $question['id']; ?>">
                                                <i class="fas fa-times-circle text-danger me-2"></i>False
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                <div class="form-group">
                                    <textarea class="form-control" name="answers[<?php echo $question['id']; ?>]" rows="3" placeholder="Type your answer here..."></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="previousQuestion()" disabled>
                        <i class="fas fa-arrow-left me-1"></i>Previous
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary" id="nextBtn" onclick="nextQuestion()">
                        Next<i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                        <i class="fas fa-paper-plane me-1"></i>Submit Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentQuestion = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        let answers = {};
        let timeRemaining = <?php echo $quizData['time_limit_minutes'] * 60; ?>;
        
        // Timer functionality
        const timerInterval = setInterval(() => {
            timeRemaining--;
            document.getElementById('timeRemaining').textContent = timeRemaining;
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting your quiz...');
                document.getElementById('quizForm').submit();
            }
            
            // Change color when time is running out
            if (timeRemaining <= 300) { // 5 minutes
                document.getElementById('quizTimer').style.color = '#dc3545';
            }
        }, 1000);
        
        // Navigation functions
        function showQuestion(index) {
            // Hide all questions
            document.querySelectorAll('.question-container').forEach(q => {
                q.style.display = 'none';
            });
            
            // Show current question
            document.querySelector(`[data-question-index="${index}"]`).style.display = 'block';
            
            // Update progress dots
            document.querySelectorAll('.progress-dot').forEach((dot, i) => {
                dot.classList.remove('active');
                if (i === index) {
                    dot.classList.add('active');
                }
            });
            
            // Update navigation buttons
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').style.display = index === totalQuestions - 1 ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = index === totalQuestions - 1 ? 'inline-block' : 'none';
        }
        
        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                showQuestion(currentQuestion);
            }
        }
        
        function previousQuestion() {
            if (currentQuestion > 0) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
        }
        
        function selectOption(element, questionId, value) {
            // Remove selected class from siblings
            element.parentElement.querySelectorAll('.option-card').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class
            element.classList.add('selected');
            
            // Check the radio button
            const radio = element.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
            
            // Update progress dot
            const questionIndex = parseInt(element.closest('.question-container').dataset.questionIndex);
            document.querySelectorAll('.progress-dot')[questionIndex].classList.add('answered');
            
            // Store answer
            answers[questionId] = value;
        }
        
        // Handle textarea changes
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const questionId = this.name.match(/\d+/)[0];
                if (this.value.trim()) {
                    answers[questionId] = this.value.trim();
                    const questionIndex = parseInt(this.closest('.question-container').dataset.questionIndex);
                    document.querySelectorAll('.progress-dot')[questionIndex].classList.add('answered');
                }
            });
        });
        
        // Form submission
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
                return;
            }
            
            clearInterval(timerInterval);
            this.submit();
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' && currentQuestion < totalQuestions - 1) {
                nextQuestion();
            } else if (e.key === 'ArrowLeft' && currentQuestion > 0) {
                previousQuestion();
            }
        });
        
        // Initialize
        showQuestion(0);
    </script>
</body>
</html>
