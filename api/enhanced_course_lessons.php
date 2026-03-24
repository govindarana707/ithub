<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin' && getUserRole() !== 'instructor') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = new Database();
$conn = $db->getConnection();

// Enhanced JSON parsing with error handling
function parseJSONInput() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    
    // Check if it's already an array (form data)
    if (is_array($raw)) {
        return $raw;
    }
    
    // Try to decode JSON
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Parse Error: " . json_last_error_msg() . " - Raw input: " . $raw);
        return [];
    }
    
    return $decoded ?: [];
}

// Enhanced JSON response with error handling
function safeSendJSON($data, $statusCode = 200) {
    // Clean data to ensure it can be JSON encoded
    array_walk_recursive($data, function(&$value) {
        if (is_string($value)) {
            // Remove invalid UTF-8 characters
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            // Remove control characters except newlines and tabs
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        }
    });
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Encode Error: " . json_last_error_msg());
        // Send a simple error response
        echo json_encode([
            'success' => false, 
            'message' => 'JSON encoding error occurred'
        ]);
    } else {
        echo $json;
    }
    exit;
}

if ($method === 'GET') {
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    if ($courseId <= 0) {
        safeSendJSON(['success' => false, 'message' => 'Invalid course_id']);
    }

    // Check if instructor owns the course
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT instructor_id FROM courses_new WHERE id = ?");
        if ($stmt === false) {
            safeSendJSON(['success' => false, 'message' => 'Database error']);
        }
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
            safeSendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    $stmt = $conn->prepare("SELECT id, course_id, title, content, video_url, video_file_path, google_drive_url, video_source, lesson_order, lesson_type, duration_minutes, is_free, video_file_size, video_duration, video_thumbnail, video_processing_status, video_mime_type, video_quality, is_downloadable, created_at, updated_at FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC, id ASC");
    if ($stmt === false) {
        safeSendJSON(['success' => false, 'message' => 'Database error']);
    }
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    safeSendJSON(['success' => true, 'lessons' => $lessons]);
}

if ($method !== 'POST') {
    safeSendJSON(['success' => false, 'message' => 'Invalid request method']);
}

// Handle both JSON and form data with enhanced error handling
$payload = [];
if (isset($_POST['action'])) {
    $payload = $_POST;
} else {
    $payload = parseJSONInput();
}

if (empty($payload)) {
    safeSendJSON(['success' => false, 'message' => 'No valid data received']);
}

$action = $payload['action'] ?? 'create';

// Handle file upload
$videoFilePath = null;
$videoFileSize = null;
$videoMimeType = null;
$videoProcessingStatus = 'none';

if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
    $videoFile = $_FILES['video_file'];
    
    // Validate file type
    $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    if (!in_array($videoFile['type'], $allowedTypes)) {
        safeSendJSON(['success' => false, 'message' => 'Invalid video file type. Allowed: MP4, WebM, OGG']);
    }
    
    // Validate file size (500MB max)
    $maxSize = 500 * 1024 * 1024;
    if ($videoFile['size'] > $maxSize) {
        safeSendJSON(['success' => false, 'message' => 'Video file size must be less than 500MB']);
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
        safeSendJSON(['success' => false, 'message' => 'Failed to upload video file']);
    }
    
    $videoFileSize = $videoFile['size'];
    $videoMimeType = $videoFile['type'];
    $videoProcessingStatus = 'pending';
    
    // Add to processing queue
    $stmt = $conn->prepare("INSERT INTO video_processing_queue (lesson_id, video_file_path, status) VALUES (?, ?, 'pending')");
    $lessonId = $payload['lesson_id'] ?? 0;
    if ($lessonId > 0) {
        $stmt->bind_param('is', $lessonId, $videoFilePath);
        $stmt->execute();
    }
}

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
    $autoGenerateThumbnail = (int)($payload['auto_generate_thumbnail'] ?? 1);

    if ($courseId <= 0 || $title === '') {
        safeSendJSON(['success' => false, 'message' => 'Missing required fields']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT instructor_id FROM courses_new WHERE id = ?");
        if ($stmt === false) {
            safeSendJSON(['success' => false, 'message' => 'Database error']);
        }
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
            safeSendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    $stmt = $conn->prepare("SELECT COALESCE(MAX(lesson_order), 0) + 1 as next_order FROM lessons WHERE course_id = ?");
    if ($stmt === false) {
        safeSendJSON(['success' => false, 'message' => 'Database error']);
    }
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $nextOrder = (int)($stmt->get_result()->fetch_assoc()['next_order'] ?? 1);

    $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, video_url, video_file_path, google_drive_url, video_source, lesson_order, lesson_type, duration_minutes, is_free, video_file_size, video_mime_type, video_processing_status, video_quality, is_downloadable, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    if ($stmt === false) {
        safeSendJSON(['success' => false, 'message' => 'Database error']);
    }
    $stmt->bind_param('issssssisisssssi', $courseId, $title, $content, $videoUrl, $videoFilePath, $googleDriveUrl, $videoSource, $nextOrder, $lessonType, $durationMinutes, $isFree, $videoFileSize, $videoMimeType, $videoProcessingStatus, $videoQuality, $isDownloadable);

    if (!$stmt->execute()) {
        // Clean up uploaded file if database insert fails
        if ($videoFilePath && file_exists(dirname(__DIR__) . '/' . $videoFilePath)) {
            unlink(dirname(__DIR__) . '/' . $videoFilePath);
        }
        safeSendJSON(['success' => false, 'message' => 'Failed to create lesson: ' . $conn->error]);
    }

    $lessonId = $conn->insert_id;
    
    // Add to processing queue if video was uploaded
    if ($videoFilePath) {
        $stmt = $conn->prepare("INSERT INTO video_processing_queue (lesson_id, video_file_path, status) VALUES (?, ?, 'pending')");
        if ($stmt !== false) {
            $stmt->bind_param('is', $lessonId, $videoFilePath);
            $stmt->execute();
        }
    }

    safeSendJSON(['success' => true, 'lesson_id' => $lessonId]);
}

if ($action === 'update') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        safeSendJSON(['success' => false, 'message' => 'Invalid lesson_id']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        if ($stmt === false) {
            safeSendJSON(['success' => false, 'message' => 'Database error']);
        }
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            safeSendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

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

    if ($title === '') {
        safeSendJSON(['success' => false, 'message' => 'Title is required']);
    }

    $stmt = $conn->prepare("UPDATE lessons SET title = ?, lesson_type = ?, duration_minutes = ?, is_free = ?, content = ?, video_url = ?, google_drive_url = ?, video_source = ?, video_quality = ?, is_downloadable = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt === false) {
        safeSendJSON(['success' => false, 'message' => 'Database error']);
    }
    $stmt->bind_param('siissssssi', $title, $lessonType, $durationMinutes, $isFree, $content, $videoUrl, $googleDriveUrl, $videoSource, $videoQuality, $isDownloadable, $lessonId);

    if ($stmt->execute()) {
        safeSendJSON(['success' => true, 'message' => 'Lesson updated successfully']);
    } else {
        safeSendJSON(['success' => false, 'message' => 'Failed to update lesson']);
    }
}

if ($action === 'delete') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        safeSendJSON(['success' => false, 'message' => 'Invalid lesson_id']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT c.instructor_id, l.video_file_path FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        if ($stmt === false) {
            safeSendJSON(['success' => false, 'message' => 'Database error']);
        }
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            safeSendJSON(['success' => false, 'message' => 'Access denied']);
        }
        
        // Delete video file if exists
        if ($lesson['video_file_path'] && file_exists(dirname(__DIR__) . '/' . $lesson['video_file_path'])) {
            unlink(dirname(__DIR__) . '/' . $lesson['video_file_path']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
    if ($stmt === false) {
        safeSendJSON(['success' => false, 'message' => 'Database error']);
    }
    $stmt->bind_param('i', $lessonId);

    if ($stmt->execute()) {
        safeSendJSON(['success' => true, 'message' => 'Lesson deleted successfully']);
    } else {
        safeSendJSON(['success' => false, 'message' => 'Failed to delete lesson']);
    }
}

if ($action === 'reorder') {
    $lessonOrders = $payload['lesson_orders'] ?? [];
    if (empty($lessonOrders) || !is_array($lessonOrders)) {
        safeSendJSON(['success' => false, 'message' => 'Invalid lesson orders data']);
    }

    // Check instructor permissions for first lesson
    $firstLessonId = (int)key($lessonOrders);
    if (getUserRole() === 'instructor' && $firstLessonId > 0) {
        $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        if ($stmt === false) {
            safeSendJSON(['success' => false, 'message' => 'Database error']);
        }
        $stmt->bind_param('i', $firstLessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            safeSendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    $conn->begin_transaction();
    try {
        foreach ($lessonOrders as $lessonId => $order) {
            $stmt = $conn->prepare("UPDATE lessons SET lesson_order = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception('Database error');
            }
            $stmt->bind_param('ii', $order, $lessonId);
            $stmt->execute();
        }
        $conn->commit();
        safeSendJSON(['success' => true, 'message' => 'Lessons reordered successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        safeSendJSON(['success' => false, 'message' => 'Failed to reorder lessons']);
    }
}

if ($action === 'get_google_drive_embed') {
    $googleDriveUrl = trim($payload['google_drive_url'] ?? '');
    
    if (empty($googleDriveUrl) || !strpos($googleDriveUrl, 'drive.google.com')) {
        safeSendJSON(['success' => false, 'message' => 'Invalid Google Drive URL']);
    }
    
    // Extract file ID from Google Drive URL
    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $googleDriveUrl, $matches)) {
        $fileId = $matches[1];
        $embedUrl = "https://drive.google.com/file/d/{$fileId}/preview";
        
        safeSendJSON([
            'success' => true, 
            'embed_url' => $embedUrl,
            'file_id' => $fileId
        ]);
    } else {
        safeSendJSON(['success' => false, 'message' => 'Could not extract file ID from Google Drive URL']);
    }
}

safeSendJSON(['success' => false, 'message' => 'Unknown action']);
?>
