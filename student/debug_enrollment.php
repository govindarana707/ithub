<?php
// Debug script to check enrollment data
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    die("Please login first");
}

$userId = $_SESSION['user_id'];
$conn = connectDB();

echo "<h2>Enrollment Debug for User ID: $userId</h2>";

// Check enrollments table
echo "<h3>1. Raw Enrollments Data:</h3>";
$stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red'>No enrollments found in database!</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Course ID</th><th>Progress %</th><th>Status</th><th>Enrolled At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['course_id'] . "</td>";
        echo "<td>" . ($row['progress_percentage'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['enrolled_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check enrolled courses with details
echo "<h3>2. Enrolled Courses (with status='active'):</h3>";
$stmt = $conn->prepare("
    SELECT c.id, c.title, e.progress_percentage, e.status
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND e.status = 'active'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red'>No active enrollments found!</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Course ID</th><th>Title</th><th>Progress %</th><th>Status</th><th>Is Completed?</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $isCompleted = ($row['progress_percentage'] ?? 0) >= 100 ? 'YES' : 'NO';
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . ($row['progress_percentage'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $isCompleted . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Calculate stats manually
echo "<h3>3. Manual Stats Calculation:</h3>";
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN progress_percentage >= 100 THEN 1 ELSE 0 END) as completed
    FROM enrollments 
    WHERE student_id = ? AND status = 'active'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo "Total enrolled: " . $row['total'] . "<br>";
echo "Completed: " . $row['completed'] . "<br>";
echo "Active (in progress): " . ($row['total'] - $row['completed']) . "<br>";

$conn->close();

echo "<hr><a href='dashboard.php'>Back to Dashboard</a>";
?>
