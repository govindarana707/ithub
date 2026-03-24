<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Course Builder Debug</h1>";

try {
    echo "<p>Starting course builder...</p>";
    
    // Test basic file inclusion
    echo "<p>Step 1: Including config...</p>";
    require_once dirname(__DIR__) . '/config/config.php';
    echo "<p>✓ Config included</p>";
    
    echo "<p>Step 2: Including auth...</p>";
    require_once dirname(__DIR__) . '/includes/auth.php';
    echo "<p>✓ Auth included</p>";
    
    echo "<p>Step 3: Including models...</p>";
    require_once dirname(__DIR__) . '/models/Course.php';
    require_once dirname(__DIR__) . '/models/User.php';
    require_once dirname(__DIR__) . '/models/Database.php';
    echo "<p>✓ Models included</p>";
    
    echo "<p>Step 4: Checking session...</p>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✓ Session started</p>";
    
    echo "<p>Step 5: Checking authentication...</p>";
    if (!isLoggedIn()) {
        echo "<p>❌ Not logged in</p>";
        exit;
    }
    echo "<p>✓ Logged in</p>";
    
    $role = getUserRole();
    echo "<p>✓ Role: $role</p>";
    
    if (!in_array($role, ['instructor', 'admin'])) {
        echo "<p>❌ Not instructor/admin</p>";
        exit;
    }
    echo "<p>✓ Has instructor/admin access</p>";
    
    echo "<p>Step 6: Getting course ID...</p>";
    $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    echo "<p>✓ Course ID: $courseId</p>";
    
    if ($courseId <= 0) {
        echo "<p>❌ Invalid course ID</p>";
        exit;
    }
    
    echo "<p>Step 7: Loading course data...</p>";
    $courseModel = new Course();
    $course = $courseModel->getCourseById($courseId);
    
    if (!$course) {
        echo "<p>❌ Course not found</p>";
        exit;
    }
    
    echo "<p>✓ Course found: " . htmlspecialchars($course['title']) . "</p>";
    
    echo "<h2>✓ SUCCESS: All checks passed!</h2>";
    echo "<p>The course builder should work. Try the <a href='course_builder.php?id=$courseId'>actual course builder</a></p>";
    
} catch (Error $e) {
    echo "<h2>❌ FATAL ERROR:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<h2>❌ EXCEPTION:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
