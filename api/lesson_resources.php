<?php
// Clean output buffer to prevent HTML in JSON response
if (ob_get_level()) {
    ob_end_clean();
}

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

if ($method === 'GET') {
    $resourceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
    
    // Fetch single resource by ID
    if ($resourceId > 0) {
        // Check permissions and get resource
        if (getUserRole() === 'instructor') {
            // For instructors, check if they own the course that contains the lesson that contains the resource
            $stmt = $conn->prepare("
                SELECT lr.*, c.instructor_id
                FROM lesson_resources lr
                JOIN lessons l ON lr.lesson_id = l.id
                JOIN courses_new c ON l.course_id = c.id
                WHERE lr.id = ?
            ");
            $stmt->bind_param('i', $resourceId);
            $stmt->execute();
            $resource = $stmt->get_result()->fetch_assoc();
            
            if (!$resource) {
                sendJSON(['success' => false, 'message' => 'Resource not found']);
            }
            
            if ($resource['instructor_id'] != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
            
            // Remove instructor_id from response
            unset($resource['instructor_id']);
        } else {
            // For admin, just fetch the resource
            $stmt = $conn->prepare("SELECT * FROM lesson_resources WHERE id = ?");
            $stmt->bind_param('i', $resourceId);
            $stmt->execute();
            $resource = $stmt->get_result()->fetch_assoc();
            
            if (!$resource) {
                sendJSON(['success' => false, 'message' => 'Resource not found']);
            }
        }
        
        // Clean HTML entities
        array_walk_recursive($resource, function(&$item) {
            if (is_string($item)) {
                $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML401);
                $item = strip_tags($item);
            }
        });
        
        sendJSON(['success' => true, 'resource' => $resource]);
    }
    // Fetch all resources for a lesson
    else if ($lessonId > 0) {
        // Check instructor permissions
        if (getUserRole() === 'instructor') {
            $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
            $stmt->bind_param('i', $lessonId);
            $stmt->execute();
            $lesson = $stmt->get_result()->fetch_assoc();
            
            if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
        }

        $stmt = $conn->prepare("SELECT id, lesson_id, title, description, resource_type, file_path, file_size, external_url, mime_type, is_downloadable, sort_order, created_at FROM lesson_resources WHERE lesson_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Clean any HTML entities from the data
        array_walk_recursive($resources, function(&$item) {
            if (is_array($item)) {
                array_walk_recursive($item, function(&$value) {
                    if (is_string($value)) {
                        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML401);
                        $value = strip_tags($value);
                    }
                });
            } elseif (is_string($item)) {
                $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML401);
                $item = strip_tags($item);
            }
        });

        sendJSON(['success' => true, 'resources' => $resources]);
    }
    else {
        sendJSON(['success' => false, 'message' => 'Please provide either id or lesson_id parameter']);
    }
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
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    $title = trim((string)($payload['title'] ?? ''));
    $description = trim((string)($payload['description'] ?? ''));
    $resourceType = (string)($payload['resource_type'] ?? 'document');
    $externalUrl = trim((string)($payload['external_url'] ?? ''));
    $isDownloadable = (int)($payload['is_downloadable'] ?? 1);
    $sortOrder = (int)($payload['sort_order'] ?? 0);

    if ($lessonId <= 0 || $title === '') {
        sendJSON(['success' => false, 'message' => 'Missing required fields']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    // Handle file upload
    $filePath = null;
    $fileSize = null;
    $mimeType = null;

    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resource_file'];
        
        // Validate file type based on resource type
        $allowedTypes = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'presentation' => ['ppt', 'pptx', 'key'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']
        ];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($allowedTypes[$resourceType]) || !in_array($fileExtension, $allowedTypes[$resourceType])) {
            sendJSON(['success' => false, 'message' => 'Invalid file type for this resource type']);
        }

        // Create upload directory if it doesn't exist
        $uploadDir = dirname(__DIR__) . '/uploads/resources/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $fileName = 'resource_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = 'uploads/resources/' . $fileName;
        $fullPath = $uploadDir . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            sendJSON(['success' => false, 'message' => 'Failed to upload resource file']);
        }

        $fileSize = $file['size'];
        $mimeType = $file['type'];
    }

    // Get next sort order if not specified
    if ($sortOrder === 0) {
        $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM lesson_resources WHERE lesson_id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $sortOrder = (int)($stmt->get_result()->fetch_assoc()['next_order'] ?? 1);
    }

    $instructorId = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO lesson_resources (lesson_id, instructor_id, title, description, resource_type, file_path, file_size, external_url, mime_type, is_downloadable, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('iissssissii', $lessonId, $instructorId, $title, $description, $resourceType, $filePath, $fileSize, $externalUrl, $mimeType, $isDownloadable, $sortOrder);

    if (!$stmt->execute()) {
        // Clean up uploaded file if database insert fails
        if ($filePath && file_exists(dirname(__DIR__) . '/' . $filePath)) {
            unlink(dirname(__DIR__) . '/' . $filePath);
        }
        sendJSON(['success' => false, 'message' => 'Failed to create resource']);
    }

    $resourceId = $conn->insert_id;
    sendJSON(['success' => true, 'resource_id' => $resourceId]);
}

if ($action === 'update') {
    $resourceId = (int)($payload['resource_id'] ?? 0);
    if ($resourceId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid resource_id']);
    }

    // Check permissions
    if (getUserRole() === 'instructor') {
        // For instructors, check if they own the course that contains the lesson that contains the resource
        $stmt = $conn->prepare("
            SELECT c.instructor_id
            FROM lesson_resources lr
            JOIN lessons l ON lr.lesson_id = l.id
            JOIN courses_new c ON l.course_id = c.id
            WHERE lr.id = ?
        ");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result || $result['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    } else {
        // For admin, just check if resource exists
        $stmt = $conn->prepare("SELECT id FROM lesson_resources WHERE id = ?");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $resource = $stmt->get_result()->fetch_assoc();
        
        if (!$resource) {
            sendJSON(['success' => false, 'message' => 'Resource not found']);
        }
    }

    $title = trim((string)($payload['title'] ?? ''));
    $description = trim((string)($payload['description'] ?? ''));
    $externalUrl = trim((string)($payload['external_url'] ?? ''));
    $isDownloadable = (int)($payload['is_downloadable'] ?? 1);
    $sortOrder = (int)($payload['sort_order'] ?? 0);

    if ($title === '') {
        sendJSON(['success' => false, 'message' => 'Title is required']);
    }

    $stmt = $conn->prepare("UPDATE lesson_resources SET title = ?, description = ?, external_url = ?, is_downloadable = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('sssiii', $title, $description, $externalUrl, $isDownloadable, $sortOrder, $resourceId);

    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to update resource']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'delete') {
    $resourceId = (int)($payload['resource_id'] ?? 0);
    if ($resourceId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid resource_id']);
    }

    // Check permissions and get file path
    if (getUserRole() === 'instructor') {
        // For instructors, check if they own the course that contains the lesson that contains the resource
        $stmt = $conn->prepare("
            SELECT lr.file_path, c.instructor_id
            FROM lesson_resources lr
            JOIN lessons l ON lr.lesson_id = l.id
            JOIN courses_new c ON l.course_id = c.id
            WHERE lr.id = ?
        ");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $resource = $stmt->get_result()->fetch_assoc();
        
        if (!$resource) {
            sendJSON(['success' => false, 'message' => 'Resource not found']);
        }
        
        if ($resource['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    } else {
        // For admin, just check if resource exists and get file path
        $stmt = $conn->prepare("SELECT file_path FROM lesson_resources WHERE id = ?");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $resource = $stmt->get_result()->fetch_assoc();
        
        if (!$resource) {
            sendJSON(['success' => false, 'message' => 'Resource not found']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM lesson_resources WHERE id = ?");
    $stmt->bind_param('i', $resourceId);
    
    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to delete resource']);
    }

    // Delete file if exists
    if ($resource['file_path'] && file_exists(dirname(__DIR__) . '/' . $resource['file_path'])) {
        unlink(dirname(__DIR__) . '/' . $resource['file_path']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'reorder') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    $order = $payload['order'] ?? [];

    if ($lessonId <= 0 || !is_array($order)) {
        sendJSON(['success' => false, 'message' => 'Invalid payload']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    $stmt = $conn->prepare("UPDATE lesson_resources SET sort_order = ? WHERE id = ? AND lesson_id = ?");

    $pos = 1;
    foreach ($order as $resourceId) {
        $resourceId = (int)$resourceId;
        if ($resourceId <= 0) {
            continue;
        }
        $stmt->bind_param('iii', $pos, $resourceId, $lessonId);
        $stmt->execute();
        $pos++;
    }

    sendJSON(['success' => true]);
}

sendJSON(['success' => false, 'message' => 'Unknown action']);
