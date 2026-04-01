<?php
require_once 'config/config.php';

echo "<h2>Assign Courses to Your Instructor Account</h2>\n";

$conn = connectDB();

// Current instructor ID (from your session)
$current_instructor_id = 13;

// Assign existing courses to your instructor ID
echo "<h3>Assigning existing courses to Instructor ID: $current_instructor_id</h3>\n";

// Get courses that belong to instructor1 (ID: 2)
$courses_to_assign = $conn->query("SELECT id, title, instructor_id FROM courses_new WHERE instructor_id = 2");

if ($courses_to_assign && $courses_to_assign->num_rows > 0) {
    echo "<p>Found {$courses_to_assign->num_rows} courses to reassign:</p>\n";
    
    while ($course = $courses_to_assign->fetch_assoc()) {
        echo "<p>Reassigning course '{$course['title']}' (ID: {$course['id']}) from instructor {$course['instructor_id']} to instructor $current_instructor_id</p>\n";
        
        // Update the course to assign to current instructor
        $update_sql = "UPDATE courses_new SET instructor_id = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $current_instructor_id, $course['id']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Successfully assigned course '{$course['title']}' to you!</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to assign course '{$course['title']}': " . $stmt->error . "</p>\n";
        }
        $stmt->close();
    }
    
    // Verify the assignment
    echo "<h3>Verification:</h3>\n";
    $your_courses = $conn->query("SELECT id, title, status FROM courses_new WHERE instructor_id = $current_instructor_id");
    
    if ($your_courses && $your_courses->num_rows > 0) {
        echo "<p style='color: green;'>✅ You now have {$your_courses->num_rows} courses assigned!</p>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Course ID</th><th>Title</th><th>Status</th></tr>\n";
        
        while ($course = $your_courses->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$course['id']}</td>";
            echo "<td>{$course['title']}</td>";
            echo "<td>{$course['status']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<h3>🎉 Ready to Test!</h3>\n";
        echo "<p>Now go to <a href='/store/instructor/courses.php' target='_blank'>http://localhost/store/instructor/courses.php</a> to see your courses!</p>\n";
        
    } else {
        echo "<p style='color: red;'>❌ Something went wrong - no courses found after assignment</p>\n";
    }
    
} else {
    echo "<p style='color: orange;'>⚠️ No courses found to reassign (instructor1 may have no courses)</p>\n";
    
    // Let's create a sample course instead
    echo "<h3>Creating a sample course for you instead...</h3>\n";
    
    $insert_sql = "INSERT INTO courses_new (title, description, instructor_id, status, price, created_at, updated_at) VALUES (?, ?, ?, 'published', 0.00, NOW(), NOW())";
    $stmt = $conn->prepare($insert_sql);
    
    $title = "Sample Course for Testing";
    $description = "This is a sample course created to test the instructor courses page functionality.";
    
    $stmt->bind_param("ssi", $title, $description, $current_instructor_id);
    
    if ($stmt->execute()) {
        $new_course_id = $conn->insert_id;
        echo "<p style='color: green;'>✅ Created sample course '$title' (ID: $new_course_id) for you!</p>\n";
        echo "<p>Now go to <a href='/store/instructor/courses.php' target='_blank'>http://localhost/store/instructor/courses.php</a> to see your course!</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create sample course: " . $stmt->error . "</p>\n";
    }
    $stmt->close();
}

$conn->close();

echo "<hr>\n";
echo "<p><strong>Note:</strong> This script assigns existing courses to your instructor account for testing purposes. In production, you would create your own courses through the course creation interface.</p>\n";

?>
