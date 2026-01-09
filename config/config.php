<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_hub_clean');
define('DB_PORT', 3307);

define('BASE_URL', 'http://localhost/store/');
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/store/');

define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kathmandu');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadFile($file, $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'], $subDir = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $newFileName = uniqid() . '.' . $fileType;

    $cleanSubDir = trim((string)$subDir);
    $cleanSubDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $cleanSubDir);
    $cleanSubDir = trim($cleanSubDir, DIRECTORY_SEPARATOR);

    $baseDir = UPLOAD_PATH;
    $targetDir = $baseDir . ($cleanSubDir !== '' ? ($cleanSubDir . DIRECTORY_SEPARATOR) : '');
    $uploadPath = $targetDir . $newFileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        $stored = $cleanSubDir !== '' ? ($cleanSubDir . '/' . $newFileName) : $newFileName;
        return ['success' => true, 'filename' => $stored];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

function resolveUploadUrl($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    return BASE_URL . 'uploads/' . ltrim($path, '/');
}

function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function logActivity($userId, $action, $details = '') {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
