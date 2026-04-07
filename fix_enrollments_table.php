<?php
require_once 'config/config.php';

$conn = connectDB();

// Step 1: Create enrollments_new table with correct structure
echo "<h1>Creating enrollments_new table...</h1>";

$sql = "CREATE TABLE IF NOT EXISTS enrollments_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    last_accessed_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_user_course (user_id, course_id)
)";

if ($conn->query($sql)) {
    echo "✅ Table created successfully<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
    exit;
}

// Step 2: Check if backup table has data
$result = $conn->query("SELECT COUNT(*) as total FROM enrollments_backup_20260329");
$row = $result->fetch_assoc();
$totalRecords = $row['total'];
echo "<p>Found $totalRecords records in backup table</p>";

// Step 3: Migrate data from backup to new table
if ($totalRecords > 0) {
    echo "<h2>Migrating data...</h2>";
    
    $sql = "
        INSERT INTO enrollments_new (id, user_id, course_id, enrolled_at, completed_at, progress_percentage, status)
        SELECT 
            id,
            student_id as user_id,
            course_id,
            enrolled_at,
            completed_at,
            COALESCE(progress_percentage, 0.00),
            COALESCE(status, 'active')
        FROM enrollments_backup_20260329
        ON DUPLICATE KEY UPDATE
            progress_percentage = VALUES(progress_percentage),
            status = VALUES(status)
    ";
    
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        echo "✅ Migrated $affected records successfully<br>";
    } else {
        echo "❌ Error migrating data: " . $conn->error . "<br>";
    }
}

// Step 4: Verify the migration
$result = $conn->query("SELECT COUNT(*) as total FROM enrollments_new");
$row = $result->fetch_assoc();
echo "<h2>Verification</h2>";
echo "<p>Total records in enrollments_new: <strong>" . $row['total'] . "</strong></p>";

// Step 5: Show sample data
echo "<h3>Sample Data</h3>";
$result = $conn->query("SELECT * FROM enrollments_new LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Course ID</th><th>Progress</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['course_id']}</td>";
        echo "<td>{$row['progress_percentage']}%</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
echo "<p><a href='check_instructor_students.php'>View Instructor-Student Report</a></p>";
?>
