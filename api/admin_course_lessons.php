<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/includes/VideoProcessor.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin' && getUserRole() !== 'instructor') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = new Database();
$conn = $db->getConnection();
$videoProcessor = new VideoProcessor();

if ($method === 'GET') {
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    if ($courseId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid course_id']);
    }

    $stmt = $conn->prepare("SELECT id, course_id, title, content, video_url, video_file_path, google_drive_url, video_source, lesson_order, lesson_type, duration_minutes, is_free, video_file_size, video_duration, video_thumbnail, video_processing_status, video_mime_type, video_quality, is_downloadable, created_at, updated_at FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC, id ASC");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    sendJSON(['success' => true, 'lessons' => $lessons]);
}

if ($method !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = $payload['action'] ?? '';

if ($action === 'create') {
    $courseId = (int)($payload['course_id'] ?? 0);
    $title = trim((string)($payload['title'] ?? ''));
    $lessonType = (string)($payload['lesson_type'] ?? 'text');
    $durationMinutes = (int)($payload['duration_minutes'] ?? 0);
    $isFree = (int)($payload['is_free'] ?? 0);
    $content = (string)($payload['content'] ?? '');
    $videoUrl = (string)($payload['video_url'] ?? '');
    $googleDriveUrl = (string)($payload['google_drive_url'] ?? '');
    $videoSource = (string)($payload['video_source'] ?? 'none');
    $videoQuality = (string)($payload['video_quality'] ?? '720p');
    $isDownloadable = (int)($payload['is_downloadable'] ?? 0);

    if ($courseId <= 0 || $title === '') {
        sendJSON(['success' => false, 'message' => 'Missing required fields']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT instructor_id FROM courses WHERE id = ?");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    $stmt = $conn->prepare("SELECT COALESCE(MAX(lesson_order), 0) + 1 as next_order FROM lessons WHERE course_id = ?");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $nextOrder = (int)($stmt->get_result()->fetch_assoc()['next_order'] ?? 1);

    // Handle video upload
    $videoFilePath = null;
    $videoFileSize = null;
    $videoMimeType = null;
    $videoProcessingStatus = 'none';

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $videoFile = $_FILES['video_file'];
        
        // Validate video file
        $validation = $videoProcessor->validateVideo($videoFile);
        if (!$validation['success']) {
            sendJSON(['success' => false, 'message' => $validation['message']]);
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = dirname(__DIR__) . '/uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
        $fileName = 'video_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $videoFilePath = 'uploads/videos/' . $fileName;
        $fullPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($videoFile['tmp_name'], $fullPath)) {
            sendJSON(['success' => false, 'message' => 'Failed to upload video file']);
        }
        
        $videoFileSize = $videoFile['size'];
        $videoMimeType = $videoFile['type'];
        $videoProcessingStatus = 'pending';
    }

    $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, video_url, video_file_path, google_drive_url, video_source, lesson_order, lesson_type, duration_minutes, is_free, video_file_size, video_mime_type, video_processing_status, video_quality, is_downloadable, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param('issssssisisssssi', $courseId, $title, $content, $videoUrl, $videoFilePath, $googleDriveUrl, $videoSource, $nextOrder, $lessonType, $durationMinutes, $isFree, $videoFileSize, $videoMimeType, $videoProcessingStatus, $videoQuality, $isDownloadable);

    if (!$stmt->execute()) {
        // Clean up uploaded file if database insert fails
        if ($videoFilePath && file_exists(dirname(__DIR__) . '/' . $videoFilePath)) {
            unlink(dirname(__DIR__) . '/' . $videoFilePath);
        }
        sendJSON(['success' => false, 'message' => 'Failed to create lesson']);
    }

    $lessonId = $conn->insert_id;
    
    // Process video asynchronously if uploaded
    if ($videoFilePath) {
        $stmt = $conn->prepare("INSERT INTO video_processing_queue (lesson_id, video_file_path, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param('is', $lessonId, $videoFilePath);
        $stmt->execute();
    }

    sendJSON(['success' => true, 'lesson_id' => $lessonId]);
}

if ($action === 'update') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid lesson_id']);
    }

    $title = trim((string)($payload['title'] ?? ''));
    $lessonType = (string)($payload['lesson_type'] ?? 'text');
    $durationMinutes = (int)($payload['duration_minutes'] ?? 0);
    $isFree = (int)($payload['is_free'] ?? 0);
    $content = (string)($payload['content'] ?? '');
    $videoUrl = (string)($payload['video_url'] ?? '');

    if ($title === '') {
        sendJSON(['success' => false, 'message' => 'Title is required']);
    }

    $stmt = $conn->prepare("UPDATE lessons SET title = ?, content = ?, video_url = ?, lesson_type = ?, duration_minutes = ?, is_free = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssssiii', $title, $content, $videoUrl, $lessonType, $durationMinutes, $isFree, $lessonId);

    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to update lesson']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'delete') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid lesson_id']);
    }

    $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->bind_param('i', $lessonId);
    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to delete lesson']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'reorder') {
    $courseId = (int)($payload['course_id'] ?? 0);
    $order = $payload['order'] ?? [];

    if ($courseId <= 0 || !is_array($order)) {
        sendJSON(['success' => false, 'message' => 'Invalid payload']);
    }

    $stmt = $conn->prepare("UPDATE lessons SET lesson_order = ?, updated_at = NOW() WHERE id = ? AND course_id = ?");

    $pos = 1;
    foreach ($order as $lessonId) {
        $lessonId = (int)$lessonId;
        if ($lessonId <= 0) {
            continue;
        }
        $stmt->bind_param('iii', $pos, $lessonId, $courseId);
        $stmt->execute();
        $pos++;
    }

    sendJSON(['success' => true]);
}

sendJSON(['success' => false, 'message' => 'Unknown action']);
