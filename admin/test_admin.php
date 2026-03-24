<?php
echo "<h1>Admin Directory Test</h1>";
echo "<p>This file is in the admin directory.</p>";
echo "<p>Current working directory: " . getcwd() . "</p>";
echo "<p>Script path: " . __FILE__ . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Test if we can access course_builder.php
if (file_exists('course_builder.php')) {
    echo "<p>✓ course_builder.php exists in this directory</p>";
} else {
    echo "<p>❌ course_builder.php does NOT exist in this directory</p>";
}

// List files in this directory
echo "<h2>Files in admin directory:</h2>";
$files = glob('*.php');
foreach ($files as $file) {
    echo "<p>" . htmlspecialchars($file) . "</p>";
}
?>
