<?php
session_start();
session_destroy();

echo "<h1>Session Cleared</h1>";
echo "<div style='color: orange; padding: 10px; background: #fff3cd;'>";
echo "⚠ Test session has been cleared.<br>";
echo "You can now set up a new session or login normally.";
echo "</div>";

echo "<p><a href='setup_test_session.php'>Set Up Test Session</a></p>";
echo "<p><a href='courses.php'>Go to Courses Page</a></p>";
?>
