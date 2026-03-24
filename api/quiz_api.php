<?php
/**
 * Quiz Management API
 * Handles all AJAX operations for quiz management
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Set JSON response headers
header('Content-Type: application/json');

// Helper function to send JSON responses
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper function to sanitize input
function sanitizeInput($value) {
    return sanitize($value);
}

// Verify request method
$requestMethod = $_SERVER['REQUEST_METHOD'];
if ($requestMethod !== 'POST' && $requestMethod !== 'GET') {
    sendResponse(false, 'Invalid request method', null, 405);
}

// Check authentication - use user_role from session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    sendResponse(false, 'Unauthorized access', null, 401);
}

$userId = $_SESSION['user_id'];

// Verify CSRF token for POST requests
if ($requestMethod === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        sendResponse(false, 'Invalid CSRF token. Please refresh the page and try again.', null, 403);
    }
}

// Database connection
$conn = connectDB();

// Get the action
$action = '';
if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

// Route the request
switch ($action) {
    // ========== QUIZ OPERATIONS ==========
    
    case 'create_quiz':
        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $timeLimit = (int)($_POST['time_limit_minutes'] ?? 30);
        $passingScore = (float)($_POST['passing_score'] ?? 70);
        $maxAttempts = (int)($_POST['max_attempts'] ?? 3);
        $status = sanitizeInput($_POST['status'] ?? 'draft');
        
        // Validation
        if ($courseId <= 0) {
            sendResponse(false, 'Please select a course.', null, 400);
        }
        
        if (empty($title) || strlen($title) < 3) {
            sendResponse(false, 'Quiz title must be at least 3 characters.', null, 400);
        }
        
        // Verify course belongs to instructor
        $stmt = $conn->prepare("SELECT id FROM courses_new WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $courseId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Invalid course selection.', null, 400);
        }
        $stmt->close();
        
        // Validate status
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }
        
        // Create quiz
        $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, description, time_limit_minutes, passing_score, max_attempts, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdis", $courseId, $title, $description, $timeLimit, $passingScore, $maxAttempts, $status);
        
        if ($stmt->execute()) {
            $quizId = $conn->insert_id;
            logActivity($userId, 'quiz_created', "Created quiz: {$title} (ID: {$quizId})");
            sendResponse(true, 'Quiz created successfully!', ['quiz_id' => $quizId]);
        } else {
            sendResponse(false, 'Failed to create quiz: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'update_quiz':
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $timeLimit = (int)($_POST['time_limit_minutes'] ?? 30);
        $passingScore = (float)($_POST['passing_score'] ?? 70);
        $maxAttempts = (int)($_POST['max_attempts'] ?? 3);
        $status = sanitizeInput($_POST['status'] ?? 'draft');
        
        // Validation
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        if (empty($title) || strlen($title) < 3) {
            sendResponse(false, 'Quiz title must be at least 3 characters.', null, 400);
        }
        
        // Verify quiz ownership
        $stmt = $conn->prepare("
            SELECT q.id FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Quiz not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Validate status
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }
        
        // Update quiz
        $stmt = $conn->prepare("UPDATE quizzes SET title = ?, description = ?, time_limit_minutes = ?, passing_score = ?, max_attempts = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssdisi", $title, $description, $timeLimit, $passingScore, $maxAttempts, $status, $quizId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'quiz_updated', "Updated quiz ID: {$quizId}");
            sendResponse(true, 'Quiz updated successfully!', ['quiz_id' => $quizId]);
        } else {
            sendResponse(false, 'Failed to update quiz: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'delete_quiz':
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        // Verify quiz ownership
        $stmt = $conn->prepare("
            SELECT q.id FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Quiz not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Delete quiz (cascade will delete questions and options)
        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'quiz_deleted', "Deleted quiz ID: {$quizId}");
            sendResponse(true, 'Quiz deleted successfully!');
        } else {
            sendResponse(false, 'Failed to delete quiz: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'get_quizzes':
        // Get all quizzes for instructor's courses
        $courseId = (int)($_GET['course_id'] ?? 0);
        $status = sanitizeInput($_GET['status'] ?? '');
        
        $sql = "
            SELECT q.*, c.title as course_title,
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count
            FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE c.instructor_id = ?
        ";
        
        $params = [$userId];
        $types = "i";
        
        if ($courseId > 0) {
            $sql .= " AND q.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        
        if (!empty($status) && in_array($status, ['draft', 'published'], true)) {
            $sql .= " AND q.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY q.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get stats for each quiz
        foreach ($quizzes as &$quiz) {
            $qid = (int)$quiz['id'];
            
            // Get attempts stats
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total, 
                       AVG(percentage) as avg_score,
                       SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100 as pass_rate
                FROM quiz_attempts 
                WHERE quiz_id = ? AND status = 'completed'
            ");
            $stmt->bind_param("i", $qid);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $quiz['stats'] = [
                'total_attempts' => (int)($stats['total'] ?? 0),
                'average_score' => round($stats['avg_score'] ?? 0, 1),
                'pass_rate' => round($stats['pass_rate'] ?? 0, 1)
            ];
        }
        
        sendResponse(true, 'Quizzes retrieved successfully', $quizzes);
        break;
    
    case 'get_quiz':
        $quizId = (int)($_GET['quiz_id'] ?? 0);
        
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        // Get quiz details
        $stmt = $conn->prepare("
            SELECT q.*, c.title as course_title
            FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$quiz) {
            sendResponse(false, 'Quiz not found or access denied.', null, 404);
        }
        
        sendResponse(true, 'Quiz retrieved successfully', $quiz);
        break;
    
    // ========== QUESTION OPERATIONS ==========
    
    case 'create_question':
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $questionText = sanitizeInput($_POST['question_text'] ?? '');
        $questionType = sanitizeInput($_POST['question_type'] ?? 'multiple_choice');
        $points = (float)($_POST['points'] ?? 1);
        
        // Validation
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        if (empty($questionText) || strlen($questionText) < 5) {
            sendResponse(false, 'Question text must be at least 5 characters.', null, 400);
        }
        
        if (!in_array($questionType, ['multiple_choice', 'true_false', 'short_answer'], true)) {
            $questionType = 'multiple_choice';
        }
        
        if ($points <= 0) {
            $points = 1;
        }
        
        // Verify quiz ownership
        $stmt = $conn->prepare("
            SELECT q.id FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Quiz not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Get next question order
        $stmt = $conn->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $nextOrder = (int)($stmt->get_result()->fetch_assoc()['next_order'] ?? 1);
        $stmt->close();
        
        // Insert question
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, question_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $quizId, $questionText, $questionType, $points, $nextOrder);
        
        if ($stmt->execute()) {
            $questionId = $conn->insert_id;
            
            // Handle options based on question type
            if ($questionType === 'multiple_choice') {
                $optionsRaw = $_POST['options'] ?? '[]';
                $options = is_array($optionsRaw) ? $optionsRaw : json_decode($optionsRaw, true);
                if (!is_array($options)) $options = [];
                
                // Filter out empty options
                $options = array_filter($options, function($opt) {
                    return trim($opt) !== '';
                });
                $options = array_values($options); // Re-index
                
                $correctIndex = (int)($_POST['correct_index'] ?? 0);
                
                if (count($options) < 2) {
                    // Rollback question
                    $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ?");
                    $stmt->bind_param("i", $questionId);
                    $stmt->execute();
                    sendResponse(false, 'Multiple choice questions require at least 2 options.', null, 400);
                }
                
                foreach ($options as $index => $optText) {
                    $optText = sanitizeInput(trim($optText));
                    if (empty($optText)) continue;
                    
                    $isCorrect = ($index === $correctIndex) ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isii", $questionId, $optText, $isCorrect, $index);
                    $stmt->execute();
                    $stmt->close();
                }
            } elseif ($questionType === 'true_false') {
                $correctAnswer = $_POST['tf_correct'] ?? 'true';
                $isTrueCorrect = ($correctAnswer === 'true') ? 1 : 0;
                
                // Create True option
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, 'True', ?, 1)");
                $stmt->bind_param("ii", $questionId, $isTrueCorrect);
                $stmt->execute();
                $stmt->close();
                
                // Create False option
                $isFalseCorrect = 1 - $isTrueCorrect;
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, 'False', ?, 2)");
                $stmt->bind_param("ii", $questionId, $isFalseCorrect);
                $stmt->execute();
                $stmt->close();
            }
            
            logActivity($userId, 'quiz_question_created', "Added question to quiz {$quizId}");
            sendResponse(true, 'Question added successfully!', ['question_id' => $questionId]);
        } else {
            sendResponse(false, 'Failed to create question: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'update_question':
        $questionId = (int)($_POST['question_id'] ?? 0);
        $questionText = sanitizeInput($_POST['question_text'] ?? '');
        $questionType = sanitizeInput($_POST['question_type'] ?? 'multiple_choice');
        $points = (float)($_POST['points'] ?? 1);
        
        // Validation
        if ($questionId <= 0) {
            sendResponse(false, 'Invalid question ID.', null, 400);
        }
        
        if (empty($questionText) || strlen($questionText) < 5) {
            sendResponse(false, 'Question text must be at least 5 characters.', null, 400);
        }
        
        if (!in_array($questionType, ['multiple_choice', 'true_false', 'short_answer'], true)) {
            $questionType = 'multiple_choice';
        }
        
        if ($points <= 0) {
            $points = 1;
        }
        
        // Verify question ownership through quiz
        $stmt = $conn->prepare("
            SELECT qq.id FROM quiz_questions qq
            JOIN quizzes q ON qq.quiz_id = q.id
            JOIN courses_new c ON q.course_id = c.id
            WHERE qq.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $questionId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Question not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Update question
        $stmt = $conn->prepare("UPDATE quiz_questions SET question_text = ?, question_type = ?, points = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $questionText, $questionType, $points, $questionId);
        
        if ($stmt->execute()) {
            // Update options if multiple choice
            if ($questionType === 'multiple_choice' && isset($_POST['options'])) {
                // Delete existing options
                $stmt = $conn->prepare("DELETE FROM quiz_options WHERE question_id = ?");
                $stmt->bind_param("i", $questionId);
                $stmt->execute();
                $stmt->close();
                
                // Insert new options
                $options = $_POST['options'];
                $correctIndex = (int)($_POST['correct_index'] ?? 0);
                
                foreach ($options as $index => $optText) {
                    $optText = sanitizeInput(trim($optText));
                    if (empty($optText)) continue;
                    
                    $isCorrect = ($index === $correctIndex) ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isii", $questionId, $optText, $isCorrect, $index);
                    $stmt->execute();
                    $stmt->close();
                }
            } elseif ($questionType === 'true_false' && isset($_POST['tf_correct'])) {
                // Delete existing options
                $stmt = $conn->prepare("DELETE FROM quiz_options WHERE question_id = ?");
                $stmt->bind_param("i", $questionId);
                $stmt->execute();
                $stmt->close();
                
                // Recreate true/false options
                $correctAnswer = $_POST['tf_correct'];
                $isTrueCorrect = ($correctAnswer === 'true') ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, 'True', ?, 1)");
                $stmt->bind_param("ii", $questionId, $isTrueCorrect);
                $stmt->execute();
                $stmt->close();
                
                $isFalseCorrect = 1 - $isTrueCorrect;
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, 'False', ?, 2)");
                $stmt->bind_param("ii", $questionId, $isFalseCorrect);
                $stmt->execute();
                $stmt->close();
            }
            
            sendResponse(true, 'Question updated successfully!', ['question_id' => $questionId]);
        } else {
            sendResponse(false, 'Failed to update question: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'delete_question':
        $questionId = (int)($_POST['question_id'] ?? 0);
        
        if ($questionId <= 0) {
            sendResponse(false, 'Invalid question ID.', null, 400);
        }
        
        // Verify question ownership through quiz
        $stmt = $conn->prepare("
            SELECT qq.id FROM quiz_questions qq
            JOIN quizzes q ON qq.quiz_id = q.id
            JOIN courses_new c ON q.course_id = c.id
            WHERE qq.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $questionId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Question not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Delete question (cascade will delete options)
        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Question deleted successfully!');
        } else {
            sendResponse(false, 'Failed to delete question: ' . $stmt->error, null, 500);
        }
        break;
    
    case 'get_quiz_questions':
        $quizId = (int)($_GET['quiz_id'] ?? 0);
        
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        // Verify quiz ownership
        $stmt = $conn->prepare("
            SELECT q.id FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Quiz not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Get questions
        $stmt = $conn->prepare("
            SELECT id, quiz_id, question_text, question_type, points, question_order, created_at
            FROM quiz_questions
            WHERE quiz_id = ?
            ORDER BY question_order ASC, id ASC
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get options for each question
        foreach ($questions as &$question) {
            $qid = (int)$question['id'];
            $stmt = $conn->prepare("
                SELECT id, option_text, is_correct, option_order
                FROM quiz_options
                WHERE question_id = ?
                ORDER BY option_order ASC, id ASC
            ");
            $stmt->bind_param("i", $qid);
            $stmt->execute();
            $question['options'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        
        sendResponse(true, 'Questions retrieved successfully', $questions);
        break;
    
    case 'get_quiz_attempts':
        $quizId = (int)($_GET['quiz_id'] ?? 0);
        
        if ($quizId <= 0) {
            sendResponse(false, 'Invalid quiz ID.', null, 400);
        }
        
        // Verify quiz ownership
        $stmt = $conn->prepare("
            SELECT q.id FROM quizzes q
            JOIN courses_new c ON q.course_id = c.id
            WHERE q.id = ? AND c.instructor_id = ?
        ");
        $stmt->bind_param("ii", $quizId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(false, 'Quiz not found or access denied.', null, 403);
        }
        $stmt->close();
        
        // Get attempts
        $stmt = $conn->prepare("
            SELECT qa.*, u.full_name as student_name, u.email as student_email
            FROM quiz_attempts qa
            JOIN users_new u ON qa.student_id = u.id
            WHERE qa.quiz_id = ?
            ORDER BY qa.completed_at DESC, qa.started_at DESC
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        sendResponse(true, 'Attempts retrieved successfully', $attempts);
        break;
    
    default:
        sendResponse(false, 'Unknown action: ' . $action, null, 400);
        break;
}

$conn->close();
