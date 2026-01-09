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
$attemptId = intval($_GET['attempt_id'] ?? 0);

if ($attemptId <= 0) {
    $_SESSION['error_message'] = 'Invalid attempt ID';
    redirect('my-courses.php');
}

// Get attempt details
$attempt = $quiz->getQuizAttempt($attemptId);
if (!$attempt || $attempt['student_id'] != $userId) {
    $_SESSION['error_message'] = 'Quiz attempt not found';
    redirect('my-courses.php');
}

// Get quiz details
$quizData = $quiz->getQuizById($attempt['quiz_id']);

// Get attempt results with answers
$results = $quiz->getQuizAttemptResults($attemptId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .result-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            margin: 0 auto;
        }
        .score-excellent { background: linear-gradient(135deg, #28a745, #20c997); }
        .score-good { background: linear-gradient(135deg, #007bff, #6610f2); }
        .score-average { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .score-fail { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        
        .question-review {
            border-left: 4px solid #e9ecef;
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }
        .question-review.correct {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        .question-review.incorrect {
            border-left-color: #dc3545;
            background-color: #fff8f8;
        }
        
        .option-review {
            padding: 0.5rem;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        .option-review.selected {
            background-color: #e7f3ff;
            border: 1px solid #007bff;
        }
        .option-review.correct {
            background-color: #d4edda;
            border: 1px solid #28a745;
        }
        .option-review.incorrect {
            background-color: #f8d7da;
            border: 1px solid #dc3545;
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
                        <li><a class="dropdown-item" href="quiz-results.php"><i class="fas fa-chart-bar me-2"></i>Quiz Results</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- Result Header -->
                <div class="card result-card">
                    <div class="card-body text-center">
                        <h2 class="mb-4">Quiz Results</h2>
                        <h4 class="text-muted mb-3"><?php echo htmlspecialchars($quizData['title']); ?></h4>
                        
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="score-circle <?php 
                                    echo $attempt['percentage'] >= 90 ? 'score-excellent' : 
                                        ($attempt['percentage'] >= 70 ? 'score-good' : 
                                        ($attempt['percentage'] >= 50 ? 'score-average' : 'score-fail')); 
                                ?>">
                                    <?php echo round($attempt['percentage']); ?>%
                                </div>
                                <p class="mt-2 mb-0">
                                    <?php echo $attempt['passed'] ? 
                                        '<span class="badge bg-success"><i class="fas fa-check me-1"></i>PASSED</span>' : 
                                        '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>FAILED</span>'; 
                                    ?>
                                </p>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <h4 class="text-primary"><?php echo $attempt['score']; ?></h4>
                                        <p class="text-muted mb-0">Score</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h4 class="text-success"><?php echo $attempt['total_points']; ?></h4>
                                        <p class="text-muted mb-0">Total Points</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h4 class="text-info"><?php echo $attempt['time_taken']; ?> min</h4>
                                        <p class="text-muted mb-0">Time Taken</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h4 class="text-warning"><?php echo $attempt['attempt_number']; ?>/<?php echo $quizData['max_attempts']; ?></h4>
                                        <p class="text-muted mb-0">Attempt</p>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Passing Score: <?php echo $quizData['passing_score']; ?>% | 
                                        Completed: <?php echo date('M j, Y H:i', strtotime($attempt['completed_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Analysis -->
                <div class="card result-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Performance Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Question Breakdown</h6>
                                <div class="mb-2">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Correct: <?php echo $results['correct_count']; ?> questions
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-times-circle text-danger me-1"></i>
                                    Incorrect: <?php echo $results['incorrect_count']; ?> questions
                                </div>
                                <div>
                                    <i class="fas fa-minus-circle text-warning me-1"></i>
                                    Unanswered: <?php echo $results['unanswered_count']; ?> questions
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Recommendations</h6>
                                <?php if ($attempt['passed']): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-trophy me-1"></i>
                                        <strong>Excellent work!</strong> You've passed this quiz. Keep up the great performance!
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Keep practicing!</strong> Review the incorrect answers below and try again.
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($attempt['attempt_number'] < $quizData['max_attempts'] && !$attempt['passed']): ?>
                                    <div class="mt-2">
                                        <a href="quiz.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-redo me-1"></i>Retake Quiz
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Review -->
                <div class="card result-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Question Review</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results['questions'] as $index => $question): ?>
                            <div class="question-review <?php echo $question['is_correct'] ? 'correct' : 'incorrect'; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6>Question <?php echo $index + 1; ?></h6>
                                    <span class="badge bg-<?php echo $question['is_correct'] ? 'success' : 'danger'; ?>">
                                        <?php echo $question['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                    </span>
                                </div>
                                
                                <p class="mb-2"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <div class="options-review">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <div class="option-review <?php 
                                                echo $option['is_correct'] ? 'correct' : ''; 
                                                echo ($option['selected'] && !$option['is_correct']) ? ' incorrect' : ''; 
                                                echo $option['selected'] ? ' selected' : ''; 
                                            ?>">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($option['is_correct']): ?>
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                    <?php elseif ($option['selected']): ?>
                                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-circle text-muted me-2"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <div class="options-review">
                                        <div class="option-review <?php echo $question['correct_answer'] === 'true' ? 'correct' : ''; ?>">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            True
                                        </div>
                                        <div class="option-review <?php echo $question['correct_answer'] === 'false' ? 'correct' : ''; ?>">
                                            <i class="fas fa-times-circle text-success me-2"></i>
                                            False
                                        </div>
                                        <div class="mt-2">
                                            <strong>Your answer:</strong> 
                                            <span class="badge bg-<?php echo $question['user_answer'] === $question['correct_answer'] ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($question['user_answer']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <div class="mt-2">
                                        <strong>Your answer:</strong>
                                        <div class="alert alert-light">
                                            <?php echo htmlspecialchars($question['user_answer'] ?? 'Not answered'); ?>
                                        </div>
                                        <?php if (!$question['is_correct']): ?>
                                            <strong>Expected answer:</strong>
                                            <div class="alert alert-info">
                                                <?php echo htmlspecialchars($question['correct_answer'] ?? 'Will be evaluated by instructor'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$question['is_correct'] && isset($question['explanation'])): ?>
                                    <div class="mt-2">
                                        <strong>Explanation:</strong>
                                        <div class="alert alert-info">
                                            <?php echo htmlspecialchars($question['explanation']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="my-courses.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Back to My Courses
                    </a>
                    <?php if ($attempt['passed']): ?>
                        <a href="quiz-results.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-chart-bar me-1"></i>View All Results
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
