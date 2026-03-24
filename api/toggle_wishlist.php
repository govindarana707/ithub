<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Only allow logged-in students
if (!isLoggedIn() || getUserRole() !== 'student') {
    sendJSON(['success' => false, 'error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJSON(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$courseId = intval($_POST['course_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($courseId <= 0) {
    sendJSON(['success' => false, 'error' => 'Invalid course ID'], 400);
}

$conn = connectDB();

// Check if course exists and is published
$stmt = $conn->prepare("SELECT id FROM courses_new WHERE id = ? AND status = 'published'");
$stmt->bind_param("i", $courseId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    sendJSON(['success' => false, 'error' => 'Course not found'], 404);
}

// Create wishlist table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS wishlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )
");

// Check if already in wishlist
$stmt = $conn->prepare("SELECT id FROM wishlists WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $userId, $courseId);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;

if ($exists) {
    // Remove from wishlist
    $stmt = $conn->prepare("DELETE FROM wishlists WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    
    sendJSON([
        'success' => true,
        'in_wishlist' => false,
        'message' => 'Removed from wishlist'
    ]);
} else {
    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlists (student_id, course_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    
    sendJSON([
        'success' => true,
        'in_wishlist' => true,
        'message' => 'Added to wishlist'
    ]);
}
?>
