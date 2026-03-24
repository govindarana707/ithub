<?php
// Minimal course builder - no auth, no database, just basic output
echo "<h1>Minimal Course Builder Test</h1>";
echo "<p>If you can see this, the admin directory is accessible.</p>";
echo "<p>Course ID: " . ($_GET['id'] ?? 'not set') . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// Test basic PHP functionality
echo "<h2>PHP Test:</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";

// Test if we can include config
try {
    require_once dirname(__DIR__) . '/config/config.php';
    echo "<p>✓ Config loaded successfully</p>";
    echo "<p>BASE_URL: " . BASE_URL . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Config loading failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<p>If this page loads, try: <a href='course_builder.php?id=1'>Full Course Builder</a></p>";
?>
