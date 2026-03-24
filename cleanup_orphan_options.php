<?php
require_once 'config/config.php';
$conn = connectDB();

// Clean up orphan options (options without questions)
$conn->query("DELETE FROM quiz_options WHERE question_id NOT IN (SELECT id FROM quiz_questions)");

// Also clean any options with question_id = 0
$conn->query("DELETE FROM quiz_options WHERE question_id = 0");

echo "Orphan options cleaned!\n";

// Verify
$result = $conn->query("SELECT COUNT(*) as count FROM quiz_options");
$count = $result->fetch_assoc()['count'];
echo "quiz_options now has: $count rows\n";

$conn->close();
