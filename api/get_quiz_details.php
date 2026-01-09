<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Quiz.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$attemptId = intval($_GET['attempt_id']);
$studentId = $_SESSION['user_id'];

// Verify this attempt belongs to the student
$conn = connectDB();
$stmt = $conn->prepare("SELECT student_id FROM quiz_attempts WHERE id = ?");
$stmt->bind_param("i", $attemptId);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();

if (!$attempt || $attempt['student_id'] != $studentId) {
    sendJSON(['success' => false, 'message' => 'Quiz attempt not found']);
}

$quiz = new Quiz();

// Get attempt details
$stmt = $conn->prepare("
    SELECT qa.*, q.title as quiz_title, c.title as course_title, q.passing_score
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE qa.id = ?
");
$stmt->bind_param("i", $attemptId);
$stmt->execute();
$attemptDetails = $stmt->get_result()->fetch_assoc();

// Get answers with questions
$stmt = $conn->prepare("
    SELECT qa.*, qq.question_text, qq.question_type, qq.points,
           qo.option_text, qo.is_correct as correct_option
    FROM quiz_answers qa
    JOIN quiz_questions qq ON qa.question_id = qq.id
    LEFT JOIN quiz_options qo ON qa.selected_option_id = qo.id
    WHERE qa.attempt_id = ?
    ORDER BY qq.question_order
");
$stmt->bind_param("i", $attemptId);
$stmt->execute();
$answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Generate HTML response
$html = '
<div class="quiz-details">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6>' . htmlspecialchars($attemptDetails['quiz_title']) . '</h6>
            <p class="text-muted mb-1">Course: ' . htmlspecialchars($attemptDetails['course_title']) . '</p>
            <p class="text-muted mb-1">Attempt #' . $attemptDetails['attempt_number'] . '</p>
            <p class="text-muted mb-1">Date: ' . date('M j, Y H:i', strtotime($attemptDetails['started_at'])) . '</p>
        </div>
        <div class="col-md-6 text-end">
            <h3 class="' . ($attemptDetails['passed'] ? 'text-success' : 'text-danger') . '">
                ' . round($attemptDetails['percentage']) . '%
            </h3>
            <p class="mb-1">Score: ' . $attemptDetails['score'] . '/' . $attemptDetails['total_points'] . '</p>
            <span class="badge ' . ($attemptDetails['passed'] ? 'bg-success' : 'bg-danger') . '">
                ' . ($attemptDetails['passed'] ? 'PASSED' : 'FAILED') . '
            </span>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Passing Score: ' . $attemptDetails['passing_score'] . '% | 
        Time Limit: ' . ($attemptDetails['time_limit_minutes'] ?? 'N/A') . ' minutes
    </div>
    
    <h5>Question Review</h5>
    <div class="questions-review">
';

foreach ($answers as $index => $answer) {
    $questionNumber = $index + 1;
    $isCorrect = $answer['is_correct'];
    
    $html .= '
        <div class="card mb-3 ' . ($isCorrect ? 'border-success' : 'border-danger') . '">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0">
                        Question ' . $questionNumber . '
                        <span class="badge ' . ($isCorrect ? 'bg-success' : 'bg-danger') . ' ms-2">
                            ' . ($isCorrect ? 'Correct' : 'Incorrect') . '
                        </span>
                    </h6>
                    <span class="badge bg-secondary">' . $answer['points'] . ' points</span>
                </div>
                
                <p class="mb-2">' . htmlspecialchars($answer['question_text']) . '</p>
                
                <div class="answer-details">
    ';
    
    if ($answer['question_type'] === 'multiple_choice') {
        $html .= '
            <p class="mb-1"><strong>Your Answer:</strong> ' . htmlspecialchars($answer['option_text'] ?? 'Not answered') . '</p>
        ';
        
        // Get correct answer
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT option_text FROM quiz_options WHERE question_id = ? AND is_correct = TRUE");
        $stmt->bind_param("i", $answer['question_id']);
        $stmt->execute();
        $correctAnswer = $stmt->get_result()->fetch_assoc();
        $conn->close();
        
        if ($correctAnswer && !$isCorrect) {
            $html .= '
                <p class="mb-0"><strong>Correct Answer:</strong> ' . htmlspecialchars($correctAnswer['option_text']) . '</p>
            ';
        }
    } else {
        $html .= '
            <p class="mb-0"><strong>Your Answer:</strong> ' . htmlspecialchars($answer['answer_text'] ?? 'Not answered') . '</p>
        ';
    }
    
    $html .= '
                </div>
                
                <div class="mt-2">
                    <small class="text-muted">
                        Points Earned: ' . $answer['points_earned'] . '/' . $answer['points'] . '
                    </small>
                </div>
            </div>
        </div>
    ';
}

$html .= '
    </div>
</div>
';

echo $html;
?>
