<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Instructor.php';

$instructorId = $_SESSION['user_id'] ?? 2; // fallback to 2 based on error log

// Check if instructor has courses
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, title, instructor_id FROM courses_new WHERE instructor_id = ?");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>Instructor ID: $instructorId</h2>";
echo "<h3>Courses:</h3><pre>";
print_r($courses);
echo "</pre>";

// Check enrollments for these courses
if (!empty($courses)) {
    $courseIds = array_column($courses, 'id');
    $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
    
    $types = str_repeat('i', count($courseIds));
    $stmt = $conn->prepare("SELECT * FROM enrollments_new WHERE course_id IN ($placeholders)");
    $stmt->bind_param($types, ...$courseIds);
    $stmt->execute();
    $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<h3>Enrollments for these courses:</h3><pre>";
    print_r($enrollments);
    echo "</pre>";
    echo "<p>Total enrollments: " . count($enrollments) . "</p>";
} else {
    echo "<p>No courses found for this instructor.</p>";
}

// Also check all enrollments with instructor info
$stmt = $conn->prepare("
    SELECT e.*, c.title as course_title, c.instructor_id 
    FROM enrollments_new e 
    JOIN courses_new c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>All enrollments via JOIN:</h3><pre>";
print_r($enrollments);
echo "</pre>";
echo "<p>Total: " . count($enrollments) . "</p>";

$conn->close();
?>
