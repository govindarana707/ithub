<?php
/**
 * API endpoint to save student notes for a lesson
 */
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/LessonContent.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Try form data
    $input = $_POST;
}

$lessonId = isset($input['lesson_id']) ? intval($input['lesson_id']) : 0;
$content = isset($input['content']) ? trim($input['content']) : '';
$title = isset($input['title']) ? trim($input['title']) : 'My Notes';

// Validate inputs
if ($lessonId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
    exit;
}

$userId = $_SESSION['user_id'];
$lessonContent = new LessonContent();

try {
    $result = $lessonContent->saveStudentNotes($lessonId, $userId, $title, $content);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Notes saved successfully',
            'data' => [
                'lesson_id' => $lessonId,
                'content_length' => strlen($content)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save notes']);
    }
} catch (Exception $e) {
    error_log("Error saving notes: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
