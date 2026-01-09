<?php
require_once '../config/config.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

if (!isset($_FILES['course_thumbnail'])) {
    sendJSON(['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['course_thumbnail'];
$courseId = intval($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid course ID']);
}

// Verify instructor access
$role = getUserRole();
if ($role !== 'admin') {
    if ($role === 'instructor') {
        $course = new Course();
        $c = $course->getCourseById($courseId);
        if (!$c || (int)$c['instructor_id'] !== (int)$_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Access denied']);
    }
}

// Validate file
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxSize = 10 * 1024 * 1024; // 10MB

$fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fileSize = $file['size'];

if (!in_array($fileType, $allowedTypes)) {
    sendJSON(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed']);
}

if ($fileSize > $maxSize) {
    sendJSON(['success' => false, 'message' => 'File size too large. Maximum size is 10MB']);
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJSON(['success' => false, 'message' => 'File upload error']);
}

// Upload file to course_thumbnails directory
$uploadResult = uploadFile($file, $allowedTypes, 'course_thumbnails');

if ($uploadResult['success']) {
    $course = new Course();
    
    // Update course thumbnail
    $result = $course->updateCourse($courseId, ['thumbnail' => $uploadResult['filename']]);
    
    if ($result) {
        // Log activity
        logActivity($_SESSION['user_id'], 'course_thumbnail_updated', "Updated thumbnail for course ID: $courseId");
        
        sendJSON([
            'success' => true, 
            'message' => 'Course thumbnail updated successfully',
            'thumbnail_url' => resolveUploadUrl($uploadResult['filename']),
            'thumbnail_path' => $uploadResult['filename']
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to update course thumbnail']);
    }
} else {
    sendJSON(['success' => false, 'message' => $uploadResult['message']]);
}
?>
