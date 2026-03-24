<?php
/**
 * Test script to verify quiz submission fix
 * This simulates a quiz submission and verifies all answers are recorded
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'models/Quiz.php';

echo "=== Testing Quiz Submission Fix ===\n\n";

$quiz = new Quiz();
$conn = connectDB();

// Use quiz #7 instead of #8 (which has reached max attempts)
$quizId = 7;
$studentId = 6; // Same student

// Get questions for this quiz
$questions = $quiz->getQuizQuestions($quizId);
echo "Quiz $quizId has " . count($questions) . " questions:\n";
foreach ($questions as $i => $q) {
    echo "  Q" . ($i+1) . ": ID={$q['id']} - {$q['question_text']} ({$q['question_type']})\n";
}

// Simulate answers - answer all questions
$simulatedAnswers = [];
foreach ($questions as $q) {
    if ($q['question_type'] === 'multiple_choice') {
        // Pick the first option (correct one based on earlier output)
        if (!empty($q['options'])) {
            $simulatedAnswers[$q['id']] = $q['options'][0]['id'];
        }
    } elseif ($q['question_type'] === 'true_false') {
        $simulatedAnswers[$q['id']] = 'false'; // Answer false for the HTML case-sensitive question
    } else {
        $simulatedAnswers[$q['id']] = 'Test answer';
    }
}

echo "\nSimulated answers:\n";
print_r($simulatedAnswers);

// Start a new attempt
echo "\n--- Starting new attempt ---\n";
$startResult = $quiz->startQuizAttempt($studentId, $quizId);
if (!$startResult['success']) {
    echo "Failed to start attempt: " . $startResult['error'] . "\n";
    exit;
}
$attemptId = $startResult['attempt_id'];
echo "Created new attempt ID: $attemptId\n";

// Submit the quiz
echo "\n--- Submitting quiz ---\n";
$submitResult = $quiz->submitQuizAttempt($attemptId, $simulatedAnswers);
if ($submitResult['success']) {
    echo "Quiz submitted successfully!\n";
    echo "Score: {$submitResult['score']}/{$submitResult['total_points']} ({$submitResult['percentage']}%)\n";
    echo "Passed: " . ($submitResult['passed'] ? 'Yes' : 'No') . "\n";
} else {
    echo "Failed to submit: " . $submitResult['error'] . "\n";
}

// Check answers in database
echo "\n--- Verifying answers in database ---\n";
$result = $conn->query("SELECT * FROM quiz_answers WHERE attempt_id = $attemptId");
$answerCount = 0;
$correctCount = 0;
while ($row = $result->fetch_assoc()) {
    $answerCount++;
    if ($row['is_correct']) {
        $correctCount++;
    }
    echo "Answer $answerCount: Question ID={$row['question_id']}, is_correct={$row['is_correct']}, points={$row['points_earned']}\n";
}

echo "\n=== RESULT ===\n";
echo "Questions submitted: " . count($simulatedAnswers) . "\n";
echo "Answers in database: $answerCount\n";
echo "Correct answers: $correctCount\n";

if ($answerCount == count($questions) && $answerCount > 0) {
    echo "\n✅ SUCCESS: All answers were recorded!\n";
} else {
    echo "\n❌ FAILURE: Not all answers were recorded!\n";
}

$conn->close();
