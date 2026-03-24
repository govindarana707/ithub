<?php
/**
 * Course Builder API - Unified API for Lessons, Resources, Notes, Assignments
 * 
 * Actions:
 * - add_lesson, update_lesson, delete_lesson, get_lessons, reorder_lessons
 * - add_resource, update_resource, delete_resource, get_resources
 * - add_note, update_note, delete_note, get_notes
 * - add_assignment, update_assignment, delete_assignment, get_assignments
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Database.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    exit;
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'] ?? 0;

if (!in_array($userRole, ['instructor', 'admin'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied. Instructor privileges required.'
    ]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to verify course ownership
function verifyCourseOwnership($conn, $courseId, $userId, $userRole) {
    if ($userRole === 'admin') return true;
    
    $stmt = $conn->prepare("SELECT instructor_id FROM courses WHERE id = ?");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
    
    return $course && (int)$course['instructor_id'] === (int)$userId;
}

// Helper function to verify lesson ownership
function verifyLessonOwnership($conn, $lessonId, $userId, $userRole) {
    if ($userRole === 'admin') return true;
    
    $stmt = $conn->prepare("SELECT l.id, l.course_id, c.instructor_id 
                           FROM lessons l 
                           JOIN courses c ON l.course_id = c.id 
                           WHERE l.id = ?");
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();
    
    return $lesson && (int)$lesson['instructor_id'] === (int)$userId;
}

// Response helper
function jsonResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Validate required fields
function validateRequired($fields, $post) {
    foreach ($fields as $field) {
        if (empty($post[$field])) {
            jsonResponse('error', "Missing required field: {$field}");
        }
    }
    return true;
}

// Handle file uploads
function handleFileUpload($fileKey, $allowedTypes = [], $maxSize = 10485760) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    $file = $_FILES[$fileKey];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse('error', 'File upload error: ' . $file['error']);
    }
    
    if ($file['size'] > $maxSize) {
        jsonResponse('error', 'File size exceeds maximum allowed (' . ($maxSize / 1048576) . 'MB)');
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($ext, $allowedTypes)) {
        jsonResponse('error', 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes));
    }
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return '/uploads/' . $filename;
    }
    
    jsonResponse('error', 'Failed to move uploaded file');
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Route action
switch ($action) {
    // ==================== LESSONS ====================
    
    case 'add_lesson':
        validateRequired(['course_id', 'title'], $_POST);
        
        $courseId = (int)$_POST['course_id'];
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $contentType = sanitize($_POST['content_type'] ?? 'video');
        $duration = sanitize($_POST['duration'] ?? '');
        $videoUrl = sanitize($_POST['video_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        if (!verifyCourseOwnership($conn, $courseId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this course');
        }
        
        // Get next sort order if not provided
        if ($sortOrder === 0) {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(lesson_order), 0) + 1 as next_order FROM lessons WHERE course_id = ?");
            $stmt->bind_param('i', $courseId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sortOrder = $result->fetch_assoc()['next_order'];
        }
        
        // Handle video upload
        $videoPath = handleFileUpload('video_file', ['mp4', 'webm', 'mov', 'avi', 'mkv'], 524288000); // 500MB max
        
        $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, description, content_type, video_url, video_path, duration, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('issssssi', $courseId, $title, $description, $contentType, $videoUrl, $videoPath, $duration, $sortOrder);
        
        if ($stmt->execute()) {
            $lessonId = $conn->insert_id;
            jsonResponse('success', 'Lesson added successfully', ['lesson_id' => $lessonId]);
        } else {
            jsonResponse('error', 'Failed to add lesson: ' . $conn->error);
        }
        break;
    
    case 'update_lesson':
        validateRequired(['lesson_id'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $contentType = sanitize($_POST['content_type'] ?? 'video');
        $duration = sanitize($_POST['duration'] ?? '');
        $videoUrl = sanitize($_POST['video_url'] ?? '');
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        // Handle new video upload if provided
        $videoPath = handleFileUpload('video_file', ['mp4', 'webm', 'mov', 'avi', 'mkv'], 524288000);
        
        if ($videoPath) {
            $stmt = $conn->prepare("UPDATE lessons SET title=?, description=?, content_type=?, video_url=?, video_path=?, duration=?, is_free=?, is_published=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ssssssiii', $title, $description, $contentType, $videoUrl, $videoPath, $duration, $isFree, $isPublished, $lessonId);
        } else {
            $stmt = $conn->prepare("UPDATE lessons SET title=?, description=?, content_type=?, video_url=?, duration=?, is_free=?, is_published=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sssssiii', $title, $description, $contentType, $videoUrl, $duration, $isFree, $isPublished, $lessonId);
        }
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Lesson updated successfully');
        } else {
            jsonResponse('error', 'Failed to update lesson: ' . $conn->error);
        }
        break;
    
    case 'delete_lesson':
        validateRequired(['lesson_id'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        // Get lesson to delete associated files
        $stmt = $conn->prepare("SELECT video_path FROM lessons WHERE id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        // Delete video file if exists
        if ($lesson && $lesson['video_path']) {
            $filePath = __DIR__ . '/..' . $lesson['video_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->bind_param('i', $lessonId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Lesson deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete lesson: ' . $conn->error);
        }
        break;
    
    case 'get_lessons':
        validateRequired(['course_id'], $_GET);
        
        $courseId = (int)$_GET['course_id'];
        
        if (!verifyCourseOwnership($conn, $courseId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this course');
        }
        
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC, id ASC");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get counts for each lesson
        foreach ($lessons as &$lesson) {
            // Resource count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lesson_resources WHERE lesson_id = ?");
            $stmt->bind_param('i', $lesson['id']);
            $stmt->execute();
            $lesson['resources_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Notes count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lesson_notes WHERE lesson_id = ?");
            $stmt->bind_param('i', $lesson['id']);
            $stmt->execute();
            $lesson['notes_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Assignments count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lesson_assignments WHERE lesson_id = ?");
            $stmt->bind_param('i', $lesson['id']);
            $stmt->execute();
            $lesson['assignments_count'] = $stmt->get_result()->fetch_assoc()['count'];
        }
        
        jsonResponse('success', 'Lessons retrieved', ['lessons' => $lessons]);
        break;
    
    case 'reorder_lessons':
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lessonIds = $_POST['lesson_ids'] ?? [];
        
        if (!verifyCourseOwnership($conn, $courseId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this course');
        }
        
        if (!is_array($lessonIds) || empty($lessonIds)) {
            jsonResponse('error', 'No lesson IDs provided');
        }
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE lessons SET sort_order = ? WHERE id = ? AND course_id = ?");
            foreach ($lessonIds as $index => $lessonId) {
                $sortOrder = $index + 1;
                $stmt->bind_param('iii', $sortOrder, $lessonId, $courseId);
                $stmt->execute();
            }
            $conn->commit();
            jsonResponse('success', 'Lessons reordered successfully');
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', 'Failed to reorder lessons: ' . $e->getMessage());
        }
        break;
    
    // ==================== RESOURCES ====================
    
    case 'add_resource':
        validateRequired(['lesson_id', 'title'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $resourceType = sanitize($_POST['resource_type'] ?? 'document');
        $externalUrl = sanitize($_POST['external_url'] ?? '');
        $isDownloadable = isset($_POST['is_downloadable']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        // Handle file upload
        $filePath = handleFileUpload('resource_file', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'png', 'jpg', 'jpeg', 'gif'], 52428800); // 50MB max
        
        // Get file info
        $fileSize = null;
        $mimeType = null;
        if ($filePath && isset($_FILES['resource_file'])) {
            $fileSize = $_FILES['resource_file']['size'];
            $mimeType = $_FILES['resource_file']['type'];
        }
        
        // Get next sort order if not provided
        if ($sortOrder === 0) {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM lesson_resources WHERE lesson_id = ?");
            $stmt->bind_param('i', $lessonId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sortOrder = $result->fetch_assoc()['next_order'];
        }
        
        $stmt = $conn->prepare("INSERT INTO lesson_resources (lesson_id, instructor_id, title, description, resource_type, file_path, file_size, external_url, mime_type, is_downloadable, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('iissssissii', $lessonId, $userId, $title, $description, $resourceType, $filePath, $fileSize, $externalUrl, $mimeType, $isDownloadable, $sortOrder);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Resource added successfully', ['resource_id' => $conn->insert_id]);
        } else {
            jsonResponse('error', 'Failed to add resource: ' . $conn->error);
        }
        break;
    
    case 'update_resource':
        validateRequired(['resource_id'], $_POST);
        
        $resourceId = (int)$_POST['resource_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT lr.*, c.instructor_id FROM lesson_resources lr JOIN lessons l ON lr.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE lr.id = ?");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $resource = $stmt->get_result()->fetch_assoc();
        
        if (!$resource) {
            jsonResponse('error', 'Resource not found');
        }
        
        if ($userRole !== 'admin' && (int)$resource['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this resource');
        }
        
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $externalUrl = sanitize($_POST['external_url'] ?? '');
        $isDownloadable = isset($_POST['is_downloadable']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? $resource['sort_order']);
        
        // Handle file upload if provided
        $filePath = $resource['file_path'];
        $fileSize = $resource['file_size'];
        $mimeType = $resource['mime_type'];
        
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Delete old file
            if ($filePath) {
                $oldFilePath = __DIR__ . '/..' . $filePath;
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            $filePath = handleFileUpload('resource_file', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'png', 'jpg', 'jpeg', 'gif'], 52428800);
            if ($filePath && isset($_FILES['resource_file'])) {
                $fileSize = $_FILES['resource_file']['size'];
                $mimeType = $_FILES['resource_file']['type'];
            }
        }
        
        $stmt = $conn->prepare("UPDATE lesson_resources SET title=?, description=?, external_url=?, file_path=?, file_size=?, mime_type=?, is_downloadable=?, sort_order=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('sssssiiii', $title, $description, $externalUrl, $filePath, $fileSize, $mimeType, $isDownloadable, $sortOrder, $resourceId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Resource updated successfully');
        } else {
            jsonResponse('error', 'Failed to update resource: ' . $conn->error);
        }
        break;
    
    case 'delete_resource':
        validateRequired(['resource_id'], $_POST);
        
        $resourceId = (int)$_POST['resource_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT lr.file_path, c.instructor_id FROM lesson_resources lr JOIN lessons l ON lr.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE lr.id = ?");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $resource = $stmt->get_result()->fetch_assoc();
        
        if (!$resource) {
            jsonResponse('error', 'Resource not found');
        }
        
        if ($userRole !== 'admin' && (int)$resource['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this resource');
        }
        
        // Delete file if exists
        if ($resource['file_path']) {
            $filePath = __DIR__ . '/..' . $resource['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM lesson_resources WHERE id = ?");
        $stmt->bind_param('i', $resourceId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Resource deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete resource: ' . $conn->error);
        }
        break;
    
    case 'get_resources':
        validateRequired(['lesson_id'], $_GET);
        
        $lessonId = (int)$_GET['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $stmt = $conn->prepare("SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        jsonResponse('success', 'Resources retrieved', ['resources' => $resources]);
        break;
    
    // ==================== NOTES ====================
    
    case 'add_note':
        validateRequired(['lesson_id', 'title', 'content'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; // Don't sanitize HTML content
        $noteType = sanitize($_POST['note_type'] ?? 'markdown');
        $isDownloadable = isset($_POST['is_downloadable']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO lesson_notes (lesson_id, instructor_id, title, content, note_type, is_downloadable, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('iisssi', $lessonId, $userId, $title, $content, $noteType, $isDownloadable);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Note added successfully', ['note_id' => $conn->insert_id]);
        } else {
            jsonResponse('error', 'Failed to add note: ' . $conn->error);
        }
        break;
    
    case 'update_note':
        validateRequired(['note_id'], $_POST);
        
        $noteId = (int)$_POST['note_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT ln.*, c.instructor_id FROM lesson_notes ln JOIN lessons l ON ln.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE ln.id = ?");
        $stmt->bind_param('i', $noteId);
        $stmt->execute();
        $note = $stmt->get_result()->fetch_assoc();
        
        if (!$note) {
            jsonResponse('error', 'Note not found');
        }
        
        if ($userRole !== 'admin' && (int)$note['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this note');
        }
        
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; // Don't sanitize HTML content
        $noteType = sanitize($_POST['note_type'] ?? $note['note_type']);
        $isDownloadable = isset($_POST['is_downloadable']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE lesson_notes SET title=?, content=?, note_type=?, is_downloadable=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('sssii', $title, $content, $noteType, $isDownloadable, $noteId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Note updated successfully');
        } else {
            jsonResponse('error', 'Failed to update note: ' . $conn->error);
        }
        break;
    
    case 'delete_note':
        validateRequired(['note_id'], $_POST);
        
        $noteId = (int)$_POST['note_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT ln.file_path, c.instructor_id FROM lesson_notes ln JOIN lessons l ON ln.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE ln.id = ?");
        $stmt->bind_param('i', $noteId);
        $stmt->execute();
        $note = $stmt->get_result()->fetch_assoc();
        
        if (!$note) {
            jsonResponse('error', 'Note not found');
        }
        
        if ($userRole !== 'admin' && (int)$note['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this note');
        }
        
        // Delete file if exists
        if ($note['file_path']) {
            $filePath = __DIR__ . '/..' . $note['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM lesson_notes WHERE id = ?");
        $stmt->bind_param('i', $noteId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Note deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete note: ' . $conn->error);
        }
        break;
    
    case 'get_notes':
        validateRequired(['lesson_id'], $_GET);
        
        $lessonId = (int)$_GET['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $stmt = $conn->prepare("SELECT * FROM lesson_notes WHERE lesson_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        jsonResponse('success', 'Notes retrieved', ['notes' => $notes]);
        break;
    
    // ==================== ASSIGNMENTS ====================
    
    case 'add_assignment':
        validateRequired(['lesson_id', 'title', 'description'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $title = sanitize($_POST['title']);
        $description = $_POST['description'];
        $instructions = sanitize($_POST['instructions'] ?? '');
        $assignmentType = sanitize($_POST['assignment_type'] ?? 'file_upload');
        $maxPoints = (float)($_POST['max_points'] ?? 100);
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $allowLate = isset($_POST['allow_late_submission']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO lesson_assignments (lesson_id, instructor_id, title, description, instructions, assignment_type, max_points, due_date, allow_late_submission, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if ($dueDate) {
            $stmt->bind_param('iissssdiss', $lessonId, $userId, $title, $description, $instructions, $assignmentType, $maxPoints, $dueDate, $allowLate, $isPublished);
        } else {
            $nullDate = null;
            $stmt->bind_param('iissssdiss', $lessonId, $userId, $title, $description, $instructions, $assignmentType, $maxPoints, $nullDate, $allowLate, $isPublished);
        }
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Assignment added successfully', ['assignment_id' => $conn->insert_id]);
        } else {
            jsonResponse('error', 'Failed to add assignment: ' . $conn->error);
        }
        break;
    
    case 'update_assignment':
        validateRequired(['assignment_id'], $_POST);
        
        $assignmentId = (int)$_POST['assignment_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT la.*, c.instructor_id FROM lesson_assignments la JOIN lessons l ON la.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE la.id = ?");
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        
        if (!$assignment) {
            jsonResponse('error', 'Assignment not found');
        }
        
        if ($userRole !== 'admin' && (int)$assignment['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this assignment');
        }
        
        $title = sanitize($_POST['title']);
        $description = $_POST['description'];
        $instructions = sanitize($_POST['instructions'] ?? '');
        $assignmentType = sanitize($_POST['assignment_type'] ?? $assignment['assignment_type']);
        $maxPoints = (float)($_POST['max_points'] ?? $assignment['max_points']);
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $allowLate = isset($_POST['allow_late_submission']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        if ($dueDate) {
            $stmt = $conn->prepare("UPDATE lesson_assignments SET title=?, description=?, instructions=?, assignment_type=?, max_points=?, due_date=?, allow_late_submission=?, is_published=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('issssdiss', $title, $description, $instructions, $assignmentType, $maxPoints, $dueDate, $allowLate, $isPublished, $assignmentId);
        } else {
            $nullDate = null;
            $stmt = $conn->prepare("UPDATE lesson_assignments SET title=?, description=?, instructions=?, assignment_type=?, max_points=?, due_date=?, allow_late_submission=?, is_published=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('issssdiss', $title, $description, $instructions, $assignmentType, $maxPoints, $nullDate, $allowLate, $isPublished, $assignmentId);
        }
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Assignment updated successfully');
        } else {
            jsonResponse('error', 'Failed to update assignment: ' . $conn->error);
        }
        break;
    
    case 'delete_assignment':
        validateRequired(['assignment_id'], $_POST);
        
        $assignmentId = (int)$_POST['assignment_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT la.*, c.instructor_id FROM lesson_assignments la JOIN lessons l ON la.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE la.id = ?");
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        
        if (!$assignment) {
            jsonResponse('error', 'Assignment not found');
        }
        
        if ($userRole !== 'admin' && (int)$assignment['instructor_id'] !== (int)$userId) {
            jsonResponse('error', 'Access denied to this assignment');
        }
        
        $stmt = $conn->prepare("DELETE FROM lesson_assignments WHERE id = ?");
        $stmt->bind_param('i', $assignmentId);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Assignment deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete assignment: ' . $conn->error);
        }
        break;
    
    case 'get_assignments':
        validateRequired(['lesson_id'], $_GET);
        
        $lessonId = (int)$_GET['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        $stmt = $conn->prepare("SELECT * FROM lesson_assignments WHERE lesson_id = ? ORDER BY id ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        jsonResponse('success', 'Assignments retrieved', ['assignments' => $assignments]);
        break;
    
    // ==================== BULK OPERATIONS ====================
    
    case 'get_lesson_content':
        validateRequired(['lesson_id'], $_GET);
        
        $lessonId = (int)$_GET['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        // Get lesson - handle both description and content columns
        $stmt = $conn->prepare("SELECT id, course_id, title, COALESCE(description, content) as description, content_type, video_url, video_path, duration, duration_minutes, is_free, is_published, lesson_type, lesson_order, sort_order FROM lessons WHERE id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson) {
            jsonResponse('error', 'Lesson not found');
        }
        
        // Get resources
        $stmt = $conn->prepare("SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY sort_order ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get notes
        $stmt = $conn->prepare("SELECT * FROM lesson_notes WHERE lesson_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get assignments
        $stmt = $conn->prepare("SELECT * FROM lesson_assignments WHERE lesson_id = ? ORDER BY id ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        jsonResponse('success', 'Content retrieved', [
            'lesson' => $lesson,
            'resources' => $resources,
            'notes' => $notes,
            'assignments' => $assignments
        ]);
        break;
    
    case 'duplicate_lesson':
        validateRequired(['lesson_id'], $_POST);
        
        $lessonId = (int)$_POST['lesson_id'];
        
        if (!verifyLessonOwnership($conn, $lessonId, $userId, $userRole)) {
            jsonResponse('error', 'Access denied to this lesson');
        }
        
        // Get original lesson
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson) {
            jsonResponse('error', 'Lesson not found');
        }
        
        $conn->begin_transaction();
        try {
            // Get next sort order
            $stmt = $conn->prepare("SELECT COALESCE(MAX(lesson_order), 0) + 1 as next_order FROM lessons WHERE course_id = ?");
            $stmt->bind_param('i', $lesson['course_id']);
            $stmt->execute();
            $sortOrder = $stmt->get_result()->fetch_assoc()['next_order'];
            
            // Insert duplicate lesson
            $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, description, content_type, video_url, video_path, duration, is_free, is_preview, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $newTitle = $lesson['title'] . ' (Copy)';
            $stmt->bind_param('isssssssii', $lesson['course_id'], $newTitle, $lesson['description'], $lesson['content_type'], $lesson['video_url'], $lesson['video_path'], $lesson['duration'], $lesson['is_free'], $lesson['is_preview'], $sortOrder);
            $stmt->execute();
            $newLessonId = $conn->insert_id;
            
            // Duplicate resources
            $stmt = $conn->prepare("INSERT INTO lesson_resources (lesson_id, instructor_id, title, description, resource_type, file_path, file_size, external_url, mime_type, is_downloadable, sort_order) SELECT ?, instructor_id, title, description, resource_type, file_path, file_size, external_url, mime_type, is_downloadable, sort_order FROM lesson_resources WHERE lesson_id = ?");
            $stmt->bind_param('ii', $newLessonId, $lessonId);
            $stmt->execute();
            
            // Duplicate notes
            $stmt = $conn->prepare("INSERT INTO lesson_notes (lesson_id, instructor_id, title, content, note_type, is_downloadable) SELECT ?, instructor_id, title, content, note_type, is_downloadable FROM lesson_notes WHERE lesson_id = ?");
            $stmt->bind_param('ii', $newLessonId, $lessonId);
            $stmt->execute();
            
            // Duplicate assignments
            $stmt = $conn->prepare("INSERT INTO lesson_assignments (lesson_id, instructor_id, title, description, instructions, assignment_type, max_points, allow_late_submission, is_published) SELECT ?, instructor_id, title, description, instructions, assignment_type, max_points, allow_late_submission, 0 FROM lesson_assignments WHERE lesson_id = ?");
            $stmt->bind_param('ii', $newLessonId, $lessonId);
            $stmt->execute();
            
            $conn->commit();
            jsonResponse('success', 'Lesson duplicated successfully', ['new_lesson_id' => $newLessonId]);
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', 'Failed to duplicate lesson: ' . $e->getMessage());
        }
        break;
    
    default:
        jsonResponse('error', 'Invalid action specified');
}
?>
