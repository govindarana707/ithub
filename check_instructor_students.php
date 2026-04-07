<?php
require_once 'config/config.php';

$conn = connectDB();

// Query to find all instructors with student counts
$sql = "
    SELECT 
        u.id as instructor_id,
        u.full_name,
        u.email,
        u.username,
        COUNT(DISTINCT c.id) as course_count,
        COUNT(DISTINCT e.user_id) as total_students,
        COUNT(DISTINCT e.id) as total_enrollments
    FROM users_new u
    LEFT JOIN courses_new c ON u.id = c.instructor_id
    LEFT JOIN enrollments_new e ON c.id = e.course_id
    WHERE u.role = 'instructor'
    GROUP BY u.id
    ORDER BY total_students DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo "<h2 style='color: red;'>SQL Error: " . $conn->error . "</h2>";
    echo "<pre>$sql</pre>";
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Instructor Students Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #ddd; }
        .has-students { background-color: #d4edda !important; }
        .no-students { background-color: #f8d7da !important; }
        h1 { color: #333; }
        .summary { margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Instructor - Student Enrollment Report</h1>
    <div class='summary'>
        <p><strong>Query:</strong> SELECT all instructors with their courses and student enrollment counts</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Instructor ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Courses</th>
                <th>Total Students</th>
                <th>Total Enrollments</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

$instructorsWithStudents = 0;
$totalStudents = 0;

while ($row = $result->fetch_assoc()) {
    $hasStudents = $row['total_students'] > 0;
    $class = $hasStudents ? 'has-students' : 'no-students';
    $status = $hasStudents ? '✅ Has Students' : '❌ No Students';
    
    if ($hasStudents) {
        $instructorsWithStudents++;
        $totalStudents += $row['total_students'];
    }
    
    echo "<tr class='$class'>
        <td>{$row['instructor_id']}</td>
        <td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>
        <td>" . htmlspecialchars($row['email']) . "</td>
        <td>" . htmlspecialchars($row['username']) . "</td>
        <td>{$row['course_count']}</td>
        <td><strong>{$row['total_students']}</strong></td>
        <td>{$row['total_enrollments']}</td>
        <td>$status</td>
    </tr>";
}

echo "</tbody></table>";

echo "<div class='summary' style='margin-top: 20px;'>
    <h3>Summary</h3>
    <p><strong>Total Instructors:</strong> " . $result->num_rows . "</p>
    <p><strong>Instructors with Students:</strong> $instructorsWithStudents</p>
    <p><strong>Total Unique Students:</strong> $totalStudents</p>
</div>";

// Also show detailed breakdown per course
echo "<h2 style='margin-top: 40px;'>Detailed Breakdown by Course</h2>
<table>
    <thead>
        <tr>
            <th>Instructor</th>
            <th>Course ID</th>
            <th>Course Title</th>
            <th>Enrollments</th>
        </tr>
    </thead>
    <tbody>";

$sql2 = "
    SELECT 
        u.full_name as instructor_name,
        c.id as course_id,
        c.title as course_title,
        COUNT(e.id) as enrollment_count
    FROM users_new u
    JOIN courses_new c ON u.id = c.instructor_id
    LEFT JOIN enrollments_new e ON c.id = e.course_id
    WHERE u.role = 'instructor'
    GROUP BY c.id
    ORDER BY u.full_name, enrollment_count DESC
";

$result2 = $conn->query($sql2);

if (!$result2) {
    echo "<h2 style='color: red;'>SQL Error in second query: " . $conn->error . "</h2>";
    echo "<pre>$sql2</pre>";
    exit;
}

while ($row = $result2->fetch_assoc()) {
    $class = $row['enrollment_count'] > 0 ? 'has-students' : '';
    echo "<tr class='$class'>
        <td>" . htmlspecialchars($row['instructor_name'] ?? 'N/A') . "</td>
        <td>{$row['course_id']}</td>
        <td>" . htmlspecialchars($row['course_title']) . "</td>
        <td><strong>{$row['enrollment_count']}</strong></td>
    </tr>";
}

echo "</tbody></table>";

$conn->close();
?>
</body>
</html>
