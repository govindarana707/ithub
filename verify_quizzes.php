<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

$conn = connectDB();

echo "=== All Quizzes in Database ===\n\n";

$result = $conn->query("SELECT q.id, q.title, q.status, q.time_limit_minutes, q.passing_score, c.title as course_title 
    FROM quizzes q 
    JOIN courses_new c ON q.course_id = c.id 
    ORDER BY q.id DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Quiz #{$row['id']}: {$row['title']}\n";
        echo "  Status: {$row['status']} | Time: {$row['time_limit_minutes']} min | Passing: {$row['passing_score']}%\n";
        echo "  Course: {$row['course_title']}\n";
        
        // Count questions
        $qResult = $conn->query("SELECT COUNT(*) as cnt FROM quiz_questions WHERE quiz_id = {$row['id']}");
        $qCount = $qResult->fetch_assoc();
        echo "  Questions: {$qCount['cnt']}\n";
        echo "\n";
    }
} else {
    echo "No quizzes found.\n";
}

$conn->close();
