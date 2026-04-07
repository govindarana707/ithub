<?php
// Debug API directly
require_once 'config/config.php';
require_once 'includes/auth.php';

// Simulate login as instructor if not logged in
if (!isLoggedIn()) {
    $_SESSION['user_id'] = 2;
    $_SESSION['user_role'] = 'instructor';
}

$userId = $_SESSION['user_id'];
echo "<h1>Debugging Instructor Stats API</h1>";
echo "<p>User ID: $userId</p>";
echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'none') . "</p>";

$conn = connectDB();

// Test 1: Direct query for courses
echo "<h2>Test 1: Courses Count</h2>";
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE instructor_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo "Courses: " . $result['count'] . "<br>";

// Show actual courses
echo "<h3>Actual courses for instructor $userId:</h3>";
$stmt = $conn->prepare("SELECT id, title, instructor_id FROM courses_new WHERE instructor_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo "<pre>";
print_r($courses);
echo "</pre>";

// Test 2: Students count
echo "<h2>Test 2: Students Count</h2>";
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT e.user_id) as count 
    FROM enrollments_new e 
    JOIN courses_new c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo "Students: " . $result['count'] . "<br>";

// Show actual enrollments
echo "<h3>Enrollments for instructor's courses:</h3>";
$stmt = $conn->prepare("
    SELECT e.*, c.title as course_title
    FROM enrollments_new e 
    JOIN courses_new c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo "<pre>";
print_r($enrollments);
echo "</pre>";

// Test 3: Check all instructors and their enrollments
echo "<h2>Test 3: All Instructors with Enrollments</h2>";
$result = $conn->query("
    SELECT 
        c.instructor_id,
        u.full_name,
        COUNT(e.user_id) as student_count
    FROM courses_new c
    LEFT JOIN users_new u ON c.instructor_id = u.id
    LEFT JOIN enrollments_new e ON c.id = e.course_id
    GROUP BY c.instructor_id
");
echo "<table border='1'><tr><th>Instructor ID</th><th>Name</th><th>Students</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['instructor_id']}</td><td>{$row['full_name']}</td><td>{$row['student_count']}</td></tr>";
}
echo "</table>";

// Test 4: API Response Simulation
echo "<h2>Test 4: Simulated API Response</h2>";
$stats = [];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE instructor_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats['courses'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT e.user_id) as count 
    FROM enrollments_new e 
    JOIN courses_new c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats['students'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM quizzes q 
    JOIN courses_new c ON q.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats['quizzes'] = $stmt->get_result()->fetch_assoc()['count'];

echo "<pre>";
echo json_encode([
    'success' => true,
    'courses' => $stats['courses'],
    'students' => $stats['students'],
    'quizzes' => $stats['quizzes']
], JSON_PRETTY_PRINT);
echo "</pre>";

$conn->close();
?>
