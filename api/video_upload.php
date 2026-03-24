<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to upload (instructor or admin)
if (getUserRole() !== 'instructor' && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$userId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handleVideoUpload($conn, $userId);
        break;
    case 'GET':
        handleVideoList($conn, $userId);
        break;
    case 'DELETE':
        handleVideoDelete($conn, $userId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleVideoUpload($conn, $userId) {
    try {
        // Validate required fields
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $videoTitle = trim($_POST['video_title'] ?? '');
        $videoDescription = trim($_POST['video_description'] ?? '');
        $uploadMethod = $_POST['upload_method'] ?? 'direct'; // direct, youtube, vimeo
        
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Please select a valid course'];
        }
        
        if (empty($videoTitle)) {
            return ['success' => false, 'message' => 'Video title is required'];
        }
        
        // Verify that the course exists and belongs to the instructor
        $stmt = $conn->prepare("
            SELECT id, title FROM courses_new WHERE id = ? AND instructor_id = ?
        ");
        
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Database error occurred'];
        }
        
        $stmt->bind_param("ii", $courseId, $userId);
        $stmt->execute();
        $courseResult = $stmt->get_result();
        
        if ($courseResult->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid course or access denied'];
        }
        
        $course = $courseResult->fetch_assoc();
        $stmt->close();
        
        if (!isset($_FILES['video_file']) && $uploadMethod === 'direct') {
            return ['success' => false, 'message' => 'Video file is required for direct upload'];
        }
        
        // Create videos table if it doesn't exist
        createVideosTable($conn);
        
        switch ($uploadMethod) {
            case 'direct':
                $result = handleDirectUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription);
                break;
            case 'youtube':
                $result = handleYouTubeUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription);
                break;
            case 'vimeo':
                $result = handleVimeoUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription);
                break;
            default:
                return ['success' => false, 'message' => 'Invalid upload method'];
        }
        
        // Add course info to response for debugging
        if ($result['success']) {
            $result['course_title'] = $course['title'];
        }
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
    }
}

function handleDirectUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription) {
    $videoFile = $_FILES['video_file'];
    
    // Validate video file
    $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm', 'video/flv'];
    $maxFileSize = 500 * 1024 * 1024; // 500MB
    
    if (!in_array($videoFile['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid video format. Allowed: MP4, AVI, MOV, WMV, WebM, FLV'];
    }
    
    if ($videoFile['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'Video file too large. Maximum size: 500MB'];
    }
    
    // Create upload directory
    $uploadDir = '../uploads/videos/' . date('Y/m/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
    $fileName = 'video_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    // Get video metadata
    $videoMetadata = getVideoMetadata($targetPath);
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO videos (
            user_id, course_id, lesson_id, title, description, 
            filename, filepath, file_size, duration, 
            upload_method, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing', NOW())
    ");
    
    $relativePath = str_replace('../', '', $targetPath);
    $stmt->bind_param("iiissssids", 
        $userId, $courseId, $lessonId, $videoTitle, $videoDescription, 
        $fileName, $relativePath, $videoFile['size'], $videoMetadata['duration']
    );
    
    if ($stmt->execute()) {
        $videoId = $conn->insert_id();
        
        // Update status to ready
        updateVideoStatus($conn, $videoId, 'ready');
        
        return [
            'success' => true, 
            'message' => 'Video uploaded successfully',
            'video_id' => $videoId,
            'filename' => $fileName,
            'duration' => $videoMetadata['duration'],
            'file_size' => formatFileSize($videoFile['size'])
        ];
    } else {
        // Delete uploaded file if database insert fails
        unlink($targetPath);
        return ['success' => false, 'message' => 'Failed to save video to database'];
    }
}

function handleYouTubeUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription) {
    $videoUrl = trim($_POST['video_url'] ?? '');
    
    if (empty($videoUrl)) {
        return ['success' => false, 'message' => 'YouTube video URL is required'];
    }
    
    // Validate YouTube URL
    if (!isValidYouTubeUrl($videoUrl)) {
        return ['success' => false, 'message' => 'Invalid YouTube URL'];
    }
    
    // Extract video ID
    $videoId = extractYouTubeId($videoUrl);
    
    // Get video metadata from YouTube API (if API key is available)
    $videoMetadata = getYouTubeVideoMetadata($videoId);
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO videos (
            user_id, course_id, lesson_id, title, description, 
            video_url, video_id, upload_method, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())
    ");
    
    $stmt->bind_param("iiisssss", 
        $userId, $courseId, $lessonId, $videoTitle, $videoDescription, 
        $videoUrl, $videoId, 'youtube'
    );
    
    if ($stmt->execute()) {
        $videoId = $conn->insert_id();
        
        return [
            'success' => true, 
            'message' => 'YouTube video added successfully',
            'video_id' => $videoId,
            'video_url' => $videoUrl,
            'duration' => $videoMetadata['duration'] ?? 'Unknown'
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save YouTube video to database'];
    }
}

function handleVimeoUpload($conn, $userId, $courseId, $lessonId, $videoTitle, $videoDescription) {
    $videoUrl = trim($_POST['video_url'] ?? '');
    
    if (empty($videoUrl)) {
        return ['success' => false, 'message' => 'Vimeo video URL is required'];
    }
    
    // Validate Vimeo URL
    if (!isValidVimeoUrl($videoUrl)) {
        return ['success' => false, 'message' => 'Invalid Vimeo URL'];
    }
    
    // Extract video ID
    $videoId = extractVimeoId($videoUrl);
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO videos (
            user_id, course_id, lesson_id, title, description, 
            video_url, video_id, upload_method, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())
    ");
    
    $stmt->bind_param("iiisssss", 
        $userId, $courseId, $lessonId, $videoTitle, $videoDescription, 
        $videoUrl, $videoId, 'vimeo'
    );
    
    if ($stmt->execute()) {
        $videoId = $conn->insert_id();
        
        return [
            'success' => true, 
            'message' => 'Vimeo video added successfully',
            'video_id' => $videoId,
            'video_url' => $videoUrl
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save Vimeo video to database'];
    }
}

function handleVideoList($conn, $userId) {
    $courseId = (int)($_GET['course_id'] ?? 0);
    
    $whereClause = "WHERE v.user_id = ?";
    $params = [$userId];
    $types = "i";
    
    if ($courseId > 0) {
        $whereClause .= " AND v.course_id = ?";
        $params[] = $courseId;
        $types .= "i";
    }
    
    $stmt = $conn->prepare("
        SELECT v.*, c.title as course_title, l.title as lesson_title
        FROM videos v
        LEFT JOIN courses_new c ON v.course_id = c.id
        LEFT JOIN lessons l ON v.lesson_id = l.id
        $whereClause
        ORDER BY v.created_at DESC
    ");
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'course_title' => $row['course_title'],
            'lesson_title' => $row['lesson_title'],
            'filename' => $row['filename'],
            'filepath' => $row['filepath'],
            'video_url' => $row['video_url'],
            'upload_method' => $row['upload_method'],
            'duration' => $row['duration'],
            'file_size' => $row['file_size'] ? formatFileSize($row['file_size']) : null,
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos
    ]);
}

function handleVideoDelete($conn, $userId) {
    $videoId = (int)($_GET['video_id'] ?? 0);
    
    if ($videoId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
        return;
    }
    
    // Get video info
    $stmt = $conn->prepare("
        SELECT * FROM videos WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $videoId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        return;
    }
    
    $video = $result->fetch_assoc();
    
    // Delete file if it's a direct upload
    if ($video['upload_method'] === 'direct' && !empty($video['filepath'])) {
        $filePath = '../' . $video['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $videoId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete video']);
    }
}

// Helper functions
function createVideosTable($conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            lesson_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            filename VARCHAR(255),
            filepath VARCHAR(500),
            video_url VARCHAR(500),
            video_id VARCHAR(100),
            file_size BIGINT,
            duration VARCHAR(20),
            upload_method ENUM('direct', 'youtube', 'vimeo') DEFAULT 'direct',
            status ENUM('processing', 'ready', 'error') DEFAULT 'processing',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
        )
    ";
    $conn->query($sql);
}

function getVideoMetadata($filePath) {
    $metadata = ['duration' => 'Unknown'];
    
    // Try to get duration using ffmpeg if available
    if (function_exists('shell_exec')) {
        $command = "ffmpeg -i " . escapeshellarg($filePath) . " 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
        $duration = shell_exec($command);
        
        if ($duration && trim($duration)) {
            $metadata['duration'] = trim($duration);
        }
    }
    
    return $metadata;
}

function isValidYouTubeUrl($url) {
    return preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $url);
}

function extractYouTubeId($url) {
    $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/';
    preg_match($pattern, $url, $matches);
    return $matches[1] ?? '';
}

function isValidVimeoUrl($url) {
    return preg_match('/^(https?:\/\/)?(www\.)?vimeo\.com\/.+$/', $url);
}

function extractVimeoId($url) {
    $pattern = '/vimeo\.com\/(\d+)/';
    preg_match($pattern, $url, $matches);
    return $matches[1] ?? '';
}

function getYouTubeVideoMetadata($videoId) {
    // This would require YouTube API key
    // For now, return basic metadata
    return ['duration' => 'Unknown'];
}

function updateVideoStatus($conn, $videoId, $status) {
    $stmt = $conn->prepare("UPDATE videos SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $videoId);
    $stmt->execute();
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

$conn->close();
?>
