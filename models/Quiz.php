<?php
require_once 'Database.php';

class Quiz {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function createQuiz($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO quizzes (course_id, lesson_id, title, description, time_limit_minutes, passing_score, max_attempts, status) VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?)");
        $lessonId = isset($data['lesson_id']) ? (int)$data['lesson_id'] : 0;
        $stmt->bind_param(
            "iissidis",
            $data['course_id'],
            $lessonId,
            $data['title'],
            $data['description'],
            $data['time_limit_minutes'],
            $data['passing_score'],
            $data['max_attempts'],
            $data['status']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'quiz_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function updateQuiz($id, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE quizzes SET ";
        $params = [];
        $types = "";
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $sql .= "$key = ?, ";
                $params[] = $value;
                $types .= "s";
            }
        }
        
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function deleteQuiz($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    public function getQuizById($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT q.*, c.title as course_title, l.title as lesson_title
            FROM quizzes q
            LEFT JOIN courses c ON q.course_id = c.id
            LEFT JOIN lessons l ON q.lesson_id = l.id
            WHERE q.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getQuizzesByCourse($courseId, $status = null) {
        $conn = $this->db->getConnection();
        
        if ($status) {
            $stmt = $conn->prepare("
                SELECT q.*, c.title as course_title
                FROM quizzes q
                JOIN courses c ON q.course_id = c.id
                WHERE q.course_id = ? AND q.status = ?
                ORDER BY q.created_at DESC
            ");
            $stmt->bind_param("is", $courseId, $status);
        } else {
            $stmt = $conn->prepare("
                SELECT q.*, c.title as course_title
                FROM quizzes q
                JOIN courses c ON q.course_id = c.id
                WHERE q.course_id = ?
                ORDER BY q.created_at DESC
            ");
            $stmt->bind_param("i", $courseId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCourseQuizzes($courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT q.*, 
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count
            FROM quizzes q
            WHERE q.course_id = ? AND q.status = 'published'
            ORDER BY q.created_at DESC
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStudentQuizAttemptsForQuiz($studentId, $quizId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT qa.*, q.title as quiz_title, c.title as course_title
            FROM quiz_attempts qa
            JOIN quizzes q ON qa.quiz_id = q.id
            JOIN courses c ON q.course_id = c.id
            WHERE qa.student_id = ? AND qa.quiz_id = ?
            ORDER BY qa.percentage DESC, qa.started_at DESC
        ");
        $stmt->bind_param("ii", $studentId, $quizId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getQuizzesByInstructor($instructorId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT q.*, c.title as course_title
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE c.instructor_id = ?
            ORDER BY q.created_at DESC
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function createQuestion($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, question_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $data['quiz_id'], $data['question_text'], $data['question_type'], $data['points'], $data['question_order']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'question_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function createOption($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $data['question_id'], $data['option_text'], $data['is_correct'], $data['option_order']);
        
        return $stmt->execute();
    }
    
    public function getQuizQuestions($quizId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT q.*, 
                   (SELECT GROUP_CONCAT(CONCAT(o.id, ':', o.option_text, ':', o.is_correct) ORDER BY o.option_order SEPARATOR '|') 
                    FROM quiz_options o WHERE o.question_id = q.id) as options
            FROM quiz_questions q
            WHERE q.quiz_id = ?
            ORDER BY q.question_order
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $options = [];
            if ($row['options']) {
                $optionData = explode('|', $row['options']);
                foreach ($optionData as $option) {
                    $parts = explode(':', $option);
                    if (count($parts) === 3) {
                        $options[] = [
                            'id' => $parts[0],
                            'text' => $parts[1],
                            'is_correct' => $parts[2] == '1'
                        ];
                    }
                }
            }
            $row['options'] = $options;
            $questions[] = $row;
        }
        
        return $questions;
    }
    
    public function startQuizAttempt($studentId, $quizId) {
        $conn = $this->db->getConnection();
        
        // Check if student has attempts remaining
        $stmt = $conn->prepare("SELECT max_attempts FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();
        
        if (!$quiz) {
            return ['success' => false, 'error' => 'Quiz not found'];
        }
        
        // Count existing attempts
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE student_id = ? AND quiz_id = ?");
        $stmt->bind_param("ii", $studentId, $quizId);
        $stmt->execute();
        $attemptCount = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($attemptCount >= $quiz['max_attempts']) {
            return ['success' => false, 'error' => 'Maximum attempts reached'];
        }
        
        // Create new attempt
        $attemptNumber = $attemptCount + 1;
        $stmt = $conn->prepare("INSERT INTO quiz_attempts (student_id, quiz_id, attempt_number) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $studentId, $quizId, $attemptNumber);
        
        if ($stmt->execute()) {
            return ['success' => true, 'attempt_id' => $conn->insert_id, 'attempt_number' => $attemptNumber];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function submitAnswer($attemptId, $questionId, $selectedOptionId = null, $answerText = null) {
        $conn = $this->db->getConnection();
        
        // Get question details
        $stmt = $conn->prepare("
            SELECT q.points, q.question_type
            FROM quiz_questions q
            WHERE q.id = ?
        ");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $question = $stmt->get_result()->fetch_assoc();
        
        if (!$question) {
            return ['success' => false, 'error' => 'Question not found'];
        }
        
        $isCorrect = false;
        $pointsEarned = 0;
        
        // Check if selected option is correct (for multiple choice)
        if ($selectedOptionId && $question['question_type'] === 'multiple_choice') {
            $stmt = $conn->prepare("SELECT is_correct FROM quiz_options WHERE id = ?");
            $stmt->bind_param("i", $selectedOptionId);
            $stmt->execute();
            $optionResult = $stmt->get_result()->fetch_assoc();
            $isCorrect = $optionResult && $optionResult['is_correct'] == 1;
            $pointsEarned = $isCorrect ? $question['points'] : 0;
        }
        
        // Check if answer already exists
        $stmt = $conn->prepare("SELECT id FROM quiz_answers WHERE attempt_id = ? AND question_id = ?");
        $stmt->bind_param("ii", $attemptId, $questionId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Update existing answer
            $stmt = $conn->prepare("
                UPDATE quiz_answers 
                SET selected_option_id = ?, answer_text = ?, is_correct = ?, points_earned = ?
                WHERE attempt_id = ? AND question_id = ?
            ");
            $stmt->bind_param("isidii", $selectedOptionId, $answerText, $isCorrect, $pointsEarned, $attemptId, $questionId);
        } else {
            // Insert new answer
            $stmt = $conn->prepare("
                INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id, answer_text, is_correct, points_earned)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisidd", $attemptId, $questionId, $selectedOptionId, $answerText, $isCorrect, $pointsEarned);
        }
        
        return $stmt->execute();
    }
    
    public function completeQuizAttempt($attemptId) {
        $conn = $this->db->getConnection();
        
        // Calculate total score
        $stmt = $conn->prepare("
            SELECT SUM(points_earned) as earned, SUM(q.points) as total
            FROM quiz_answers qa
            JOIN quiz_questions q ON qa.question_id = q.id
            WHERE qa.attempt_id = ?
        ");
        $stmt->bind_param("i", $attemptId);
        $stmt->execute();
        $score = $stmt->get_result()->fetch_assoc();
        
        $earnedPoints = $score['earned'] ?? 0;
        $totalPoints = $score['total'] ?? 0;
        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        
        // Get quiz passing score
        $stmt = $conn->prepare("
            SELECT q.passing_score
            FROM quiz_attempts qa
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE qa.id = ?
        ");
        $stmt->bind_param("i", $attemptId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();
        
        $passed = $percentage >= ($quiz['passing_score'] ?? 70);
        
        // Update attempt
        $stmt = $conn->prepare("
            UPDATE quiz_attempts 
            SET score = ?, total_points = ?, percentage = ?, passed = ?, status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ddidi", $earnedPoints, $totalPoints, $percentage, $passed, $attemptId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'score' => $earnedPoints,
                'total_points' => $totalPoints,
                'percentage' => round($percentage, 2),
                'passed' => $passed
            ];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function getStudentQuizAttempts($studentId, $quizId = null) {
        $conn = $this->db->getConnection();
        
        if ($quizId) {
            $stmt = $conn->prepare("
                SELECT qa.*, q.title as quiz_title, c.title as course_title
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN courses c ON q.course_id = c.id
                WHERE qa.student_id = ? AND qa.quiz_id = ?
                ORDER BY qa.started_at DESC
            ");
            $stmt->bind_param("ii", $studentId, $quizId);
        } else {
            $stmt = $conn->prepare("
                SELECT qa.*, q.title as quiz_title, c.title as course_title
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN courses c ON q.course_id = c.id
                WHERE qa.student_id = ?
                ORDER BY qa.started_at DESC
            ");
            $stmt->bind_param("i", $studentId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getQuizStats($quizId) {
        $conn = $this->db->getConnection();

        $stats = [];

        // Total attempts
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $stats['total_attempts'] = $stmt->get_result()->fetch_assoc()['total'];

        // Completed attempts
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE quiz_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $stats['completed_attempts'] = $stmt->get_result()->fetch_assoc()['total'];

        // Average score
        $stmt = $conn->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE quiz_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $avgScore = $stmt->get_result()->fetch_assoc()['avg_score'];
        $stats['average_score'] = $avgScore ? round($avgScore, 2) : 0;

        // Pass rate
        $stmt = $conn->prepare("SELECT AVG(passed) * 100 as pass_rate FROM quiz_attempts WHERE quiz_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $passRate = $stmt->get_result()->fetch_assoc()['pass_rate'];
        $stats['pass_rate'] = $passRate ? round($passRate, 2) : 0;

        return $stats;
    }

    public function getQuizAttemptsForInstructor($quizId) {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            SELECT qa.*, u.full_name as student_name, u.email as student_email,
                   q.title as quiz_title, q.passing_score
            FROM quiz_attempts qa
            JOIN users u ON qa.student_id = u.id
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE qa.quiz_id = ?
            ORDER BY qa.completed_at DESC, qa.started_at DESC
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getActiveAttempt($studentId, $quizId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT * FROM quiz_attempts 
            WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
            ORDER BY started_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("ii", $studentId, $quizId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getQuizAttempt($attemptId) {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            SELECT qa.*, q.title as quiz_title, q.passing_score, q.time_limit_minutes,
                   TIMESTAMPDIFF(MINUTE, qa.started_at, NOW()) as time_taken,
                   u.full_name as student_name, u.email as student_email
            FROM quiz_attempts qa
            JOIN quizzes q ON qa.quiz_id = q.id
            JOIN users u ON qa.student_id = u.id
            WHERE qa.id = ?
        ");
        $stmt->bind_param("i", $attemptId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
    public function submitQuizAttempt($attemptId, $answers) {
        $conn = $this->db->getConnection();
        
        try {
            mysqli_begin_transaction($conn);
            
            // Submit all answers
            foreach ($answers as $questionId => $answer) {
                if (is_array($answer)) {
                    // Multiple choice
                    $selectedOptionId = $answer[0] ?? null;
                    $this->submitAnswer($attemptId, $questionId, $selectedOptionId);
                } else {
                    // Text answer
                    $this->submitAnswer($attemptId, $questionId, null, $answer);
                }
            }
            
            // Complete attempt
            $result = $this->completeQuizAttempt($attemptId);
            
            if ($result['success']) {
                mysqli_commit($conn);
                return $result;
            } else {
                mysqli_rollback($conn);
                return $result;
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getQuizAttemptResults($attemptId) {
        $conn = $this->db->getConnection();
        
        // Get attempt details
        $attempt = $this->getQuizAttempt($attemptId);
        
        // Get questions with answers
        $sql = "
            SELECT 
                qq.id,
                qq.question_text,
                qq.question_type,
                qq.points,
                qa.selected_option_id,
                qa.answer_text,
                qa.is_correct,
                qo.option_text,
                qo.is_correct as option_is_correct
            FROM quiz_questions qq
            LEFT JOIN quiz_answers qa ON qq.id = qa.question_id AND qa.attempt_id = ?
            LEFT JOIN quiz_options qo ON qa.selected_option_id = qo.id
            WHERE qq.quiz_id = ?
            ORDER BY qq.question_order
        ";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            return [
                'attempt' => $attempt,
                'questions' => [],
                'correct_count' => 0,
                'incorrect_count' => 0,
                'unanswered_count' => 0,
                'error' => 'SQL preparation failed: ' . $conn->error
            ];
        }
        
        $stmt->bind_param("ii", $attemptId, $attempt['quiz_id']);
        $executeResult = $stmt->execute();
        
        if (!$executeResult) {
            return [
                'attempt' => $attempt,
                'questions' => [],
                'correct_count' => 0,
                'incorrect_count' => 0,
                'unanswered_count' => 0,
                'error' => 'SQL execution failed: ' . $stmt->error
            ];
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            return [
                'attempt' => $attempt,
                'questions' => [],
                'correct_count' => 0,
                'incorrect_count' => 0,
                'unanswered_count' => 0,
                'error' => 'SQL get_result failed: ' . $conn->error
            ];
        }
        
        $questions = [];
        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $question = [
                'id' => $row['id'],
                'question_text' => $row['question_text'],
                'question_type' => $row['question_type'],
                'points' => $row['points'],
                'is_correct' => $row['is_correct'],
                'user_answer' => null,
                'correct_answer' => null
            ];
            
            if ($row['question_type'] === 'multiple_choice') {
                // Get all options for this question
                $optStmt = $conn->prepare("
                    SELECT id, option_text, is_correct 
                    FROM quiz_options 
                    WHERE question_id = ? 
                    ORDER BY option_order
                ");
                $optStmt->bind_param("i", $row['id']);
                $optStmt->execute();
                $options = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Mark the selected option
                foreach ($options as &$option) {
                    $option['selected'] = ($option['id'] == $row['selected_option_id']);
                }
                
                $question['options'] = $options;
                $question['user_answer'] = $row['selected_option_id'];
                
                // Find correct answer
                foreach ($options as $option) {
                    if ($option['is_correct']) {
                        $question['correct_answer'] = $option['option_text'];
                        break;
                    }
                }
            } elseif ($row['question_type'] === 'true_false') {
                $question['user_answer'] = $row['answer_text'];
                $question['correct_answer'] = $row['answer_text']; // For T/F, the answer text contains the correct answer
            } else {
                // Short answer
                $question['user_answer'] = $row['answer_text'];
                $question['correct_answer'] = 'Will be evaluated by instructor';
            }
            
            // Count correct/incorrect
            if ($row['is_correct'] === 1) {
                $correctCount++;
            } elseif ($row['is_correct'] === 0) {
                $incorrectCount++;
            } else {
                $unansweredCount++;
            }
            
            $questions[] = $question;
        }
        
        return [
            'attempt' => $attempt,
            'questions' => $questions,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'unanswered_count' => $unansweredCount
        ];
    }
}

?>
