<?php
require_once 'config/config.php';

echo "<h1>Clear Test Payments</h1>";

try {
    $conn = connectDB();
    
    // Delete test payments
    $stmt = $conn->prepare("DELETE FROM payments WHERE user_id IN (1, 3) AND course_id = 4");
    $stmt->execute();
    $deletedCount = $stmt->affected_rows;
    
    echo "<div style='color: green; padding: 10px; background: #d4edda;'>";
    echo "✓ Cleared $deletedCount test payment records";
    echo "</div>";
    
    // Show remaining payments
    $result = $conn->query("SELECT id, user_id, course_id, status, amount FROM payments ORDER BY created_at DESC LIMIT 10");
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Remaining Payments:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Course ID</th><th>Status</th><th>Amount</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['course_id'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>Rs. " . $row['amount'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No payments found in database.</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='setup_test_session.php'>Set Up Test Session</a></li>";
echo "<li><a href='test_web_api.php'>Test eSewa API</a></li>";
echo "<li><a href='test_esewa_format.php'>Test eSewa Format</a></li>";
echo "<li><a href='courses.php'>Go to Courses</a></li>";
echo "</ul>";
?>
