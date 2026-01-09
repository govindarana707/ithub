<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'update_progress') {
    $lessonId = (int)($_POST['lesson_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    $watchTimeSeconds = (int)($_POST['watch_time_seconds'] ?? 0);
    $totalVideoDuration = (int)($_POST['total_video_duration'] ?? 0);
    $completionPercentage = (float)($_POST['completion_percentage'] ?? 0);
    $lastWatchedPosition = (int)($_POST['last_watched_position'] ?? 0);
    
    if ($lessonId <= 0 || $userId <= 0 || $userId !== $_SESSION['user_id']) {
        sendJSON(['success' => false, 'message' => 'Invalid parameters']);
    }
    
    $conn = connectDB();
    
    // Check if analytics record exists
    $stmt = $conn->prepare("SELECT id, watch_count FROM video_analytics WHERE lesson_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $lessonId, $userId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing record
        $watchCount = $existing['watch_count'] + 1;
        $completedWatching = $completionPercentage >= 90 ? 1 : 0;
        
        $stmt = $conn->prepare("
            UPDATE video_analytics 
            SET watch_time_seconds = GREATEST(watch_time_seconds, ?), 
                total_video_duration = ?, 
                completion_percentage = GREATEST(completion_percentage, ?), 
                last_watched_position = ?, 
                watch_count = ?, 
                completed_watching = ?, 
                last_watched_at = NOW() 
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->bind_param('iidiiiii', $watchTimeSeconds, $totalVideoDuration, $completionPercentage, $lastWatchedPosition, $watchCount, $completedWatching, $lessonId, $userId);
    } else {
        // Insert new record
        $firstWatchedAt = date('Y-m-d H:i:s');
        $completedWatching = $completionPercentage >= 90 ? 1 : 0;
        
        $stmt = $conn->prepare("
            INSERT INTO video_analytics 
            (lesson_id, student_id, watch_time_seconds, total_video_duration, completion_percentage, last_watched_position, watch_count, completed_watching, first_watched_at, last_watched_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())
        ");
        $stmt->bind_param('iiidiiis', $lessonId, $userId, $watchTimeSeconds, $totalVideoDuration, $completionPercentage, $lastWatchedPosition, $completedWatching, $firstWatchedAt);
    }
    
    if ($stmt->execute()) {
        sendJSON(['success' => true]);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to update analytics']);
    }
}

if ($method === 'GET' && $action === 'get_stats') {
    $lessonId = (int)($_GET['lesson_id'] ?? 0);
    $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id']);
    
    if ($lessonId <= 0 || $userId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid parameters']);
    }
    
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT * FROM video_analytics WHERE lesson_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $lessonId, $userId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    sendJSON(['success' => true, 'stats' => $stats]);
}

sendJSON(['success' => false, 'message' => 'Invalid action']);
?>
