<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$studentId = $_SESSION['user_id'];

if (!isset($_FILES['profile_image'])) {
    sendJSON(['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['profile_image'];

// Validate file with enhanced security
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
$allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

$fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fileSize = $file['size'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

// Validate file extension
if (!in_array($fileType, $allowedTypes)) {
    sendJSON(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
}

// Validate MIME type
if (!in_array($mime, $allowedMimeTypes)) {
    sendJSON(['success' => false, 'message' => 'Invalid file format']);
}

// Validate file size
if ($fileSize > $maxSize) {
    sendJSON(['success' => false, 'message' => 'File size too large. Maximum size is 5MB']);
}

// Validate upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJSON(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
}

// Additional security: Check if file is actually an image
if (!getimagesize($file['tmp_name'])) {
    sendJSON(['success' => false, 'message' => 'Invalid image file']);
}

// Upload file
$uploadResult = uploadFile($file, ['jpg', 'jpeg', 'png', 'gif']);

if ($uploadResult['success']) {
    $user = new User();
    
    // Update user profile image
    $result = $user->updateUser($studentId, ['profile_image' => $uploadResult['filename']]);
    
    if ($result) {
        // Log activity
        logActivity($studentId, 'profile_image_updated', 'Updated profile picture');
        
        sendJSON([
            'success' => true, 
            'message' => 'Profile image updated successfully',
            'image_url' => BASE_URL . 'uploads/' . $uploadResult['filename']
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to update profile image']);
    }
} else {
    sendJSON(['success' => false, 'message' => $uploadResult['message']]);
}
?>
