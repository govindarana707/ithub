<?php
/**
 * Comprehensive Lesson Content Model
 * Handles notes, assignments, resources, and progress tracking
 */

class LessonContent
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get comprehensive lesson content including all materials
     */
    public function getLessonContent($lessonId, $userId = null)
    {
        $lessonId = (int) $lessonId;

        // Get basic lesson info - use LEFT JOIN to handle missing data gracefully
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   COALESCE(c.instructor_id, 0) as instructor_id, 
                   COALESCE(u.full_name, 'Unknown') as instructor_name, 
                   COALESCE(u.email, '') as instructor_email
            FROM lessons l
            LEFT JOIN courses c ON l.course_id = c.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE l.id = ?
        ");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $lesson = $result->fetch_assoc();

        // Get instructor notes
        $lesson['notes'] = $this->getLessonNotes($lessonId);

        // Get assignments
        $lesson['assignments'] = $this->getLessonAssignments($lessonId, $userId);

        // Get resources
        $lesson['resources'] = $this->getLessonResources($lessonId);

        // Get student notes if user is provided
        if ($userId) {
            $lesson['student_notes'] = $this->getStudentNotes($lessonId, $userId);
            $lesson['progress'] = $this->getLessonProgress($lessonId, $userId);
            $lesson['is_completed'] = $lesson['progress']['completed'] ?? 0;
        } else {
            $lesson['is_completed'] = 0;
        }

        return $lesson;
    }

    /**
     * Get instructor notes for a lesson
     */
    public function getLessonNotes($lessonId)
    {
        $stmt = $this->conn->prepare("
            SELECT id, title, content, note_type, is_downloadable, file_path, file_size, created_at
            FROM lesson_notes 
            WHERE lesson_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get assignments for a lesson
     */
    public function getLessonAssignments($lessonId, $userId = null)
    {
        $assignments = [];

        $stmt = $this->conn->prepare("
            SELECT id, title, description, instructions, assignment_type, max_points, 
                   due_date, allow_late_submission, late_penalty_percent, max_attempts,
                   time_limit_minutes, is_published, created_at
            FROM lesson_assignments 
            WHERE lesson_id = ? AND is_published = 1
            ORDER BY due_date ASC
        ");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Add submission status for each assignment if user is provided
        if ($userId) {
            foreach ($assignments as &$assignment) {
                $assignment['submission'] = $this->getAssignmentSubmission($assignment['id'], $userId);
                $assignment['is_overdue'] = $assignment['due_date'] && strtotime($assignment['due_date']) < time() && !$assignment['submission'];
            }
        }

        return $assignments;
    }

    /**
     * Get resources for a lesson
     */
    public function getLessonResources($lessonId)
    {
        $stmt = $this->conn->prepare("
            SELECT id, title, description, resource_type, file_path, file_size, 
                   external_url, mime_type, is_downloadable, sort_order, created_at
            FROM lesson_resources 
            WHERE lesson_id = ? 
            ORDER BY sort_order ASC, created_at ASC
        ");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get student's personal notes for a lesson
     */
    public function getStudentNotes($lessonId, $userId)
    {
        $stmt = $this->conn->prepare("
            SELECT id, title, content, is_private, created_at, updated_at
            FROM student_notes 
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->bind_param('ii', $lessonId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get detailed progress for a lesson
     */
    public function getLessonProgress($lessonId, $userId)
    {
        $stmt = $this->conn->prepare("
            SELECT completed, video_watch_time_seconds, video_completion_percentage, notes_viewed,
                   assignments_completed, assignments_total, resources_viewed, resources_total,
                   time_spent_minutes, last_accessed_at, completed_at
            FROM lesson_progress 
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->bind_param('ii', $lessonId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create or update instructor note
     */
    public function saveInstructorNote($lessonId, $instructorId, $title, $content, $noteType = 'markdown', $filePath = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lesson_notes (lesson_id, instructor_id, title, content, note_type, file_path, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $fileSize = $filePath ? filesize($filePath) : null;
        $stmt->bind_param('isssssi', $lessonId, $instructorId, $title, $content, $noteType, $filePath, $fileSize);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Create assignment
     */
    public function createAssignment($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lesson_assignments (
                lesson_id, instructor_id, title, description, instructions, assignment_type,
                max_points, due_date, allow_late_submission, late_penalty_percent,
                max_attempts, time_limit_minutes, is_published
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'iissssdisiiii',
            $data['lesson_id'],
            $data['instructor_id'],
            $data['title'],
            $data['description'],
            $data['instructions'],
            $data['assignment_type'],
            $data['max_points'],
            $data['due_date'],
            $data['allow_late_submission'],
            $data['late_penalty_percent'],
            $data['max_attempts'],
            $data['time_limit_minutes'],
            $data['is_published']
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Submit assignment
     */
    public function submitAssignment($assignmentId, $studentId, $submissionData)
    {
        // Check if submission is late
        $stmt = $this->conn->prepare("
            SELECT due_date, allow_late_submission FROM lesson_assignments WHERE id = ?
        ");
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();

        $isLate = false;
        if ($assignment['due_date'] && strtotime($assignment['due_date']) < time()) {
            $isLate = true;
            if (!$assignment['allow_late_submission']) {
                return ['success' => false, 'message' => 'Late submissions are not allowed'];
            }
        }

        // Get attempt number
        $stmt = $this->conn->prepare("
            SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt
            FROM assignment_submissions 
            WHERE assignment_id = ? AND student_id = ?
        ");
        $stmt->bind_param('ii', $assignmentId, $studentId);
        $stmt->execute();
        $attemptNumber = $stmt->get_result()->fetch_assoc()['next_attempt'];

        // Insert submission
        $stmt = $this->conn->prepare("
            INSERT INTO assignment_submissions (
                assignment_id, student_id, submission_type, file_path, file_size,
                text_content, submission_data, is_late, attempt_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'iisssssii',
            $assignmentId,
            $studentId,
            $submissionData['submission_type'],
            $submissionData['file_path'] ?? null,
            $submissionData['file_size'] ?? null,
            $submissionData['text_content'] ?? null,
            $submissionData['submission_data'] ?? null,
            $isLate,
            $attemptNumber
        );

        if ($stmt->execute()) {
            $submissionId = $this->conn->insert_id;

            // Update lesson progress
            $this->updateLessonProgress($submissionData['lesson_id'], $studentId, [
                'assignments_completed' => 1
            ]);

            return ['success' => true, 'submission_id' => $submissionId];
        }

        return ['success' => false, 'message' => 'Failed to submit assignment'];
    }

    /**
     * Save student notes
     */
    public function saveStudentNotes($lessonId, $userId, $title, $content)
    {
        // Ensure student_notes table exists
        $this->conn->query("CREATE TABLE IF NOT EXISTS student_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL,
            student_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_note (lesson_id, student_id),
            INDEX idx_lesson_id (lesson_id),
            INDEX idx_student_id (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $this->conn->prepare("
            INSERT INTO student_notes (lesson_id, student_id, title, content)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), updated_at = CURRENT_TIMESTAMP
        ");

        if ($stmt === false) {
            error_log("Failed to prepare saveStudentNotes statement: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param('iiss', $lessonId, $userId, $title, $content);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Failed to execute saveStudentNotes: " . $stmt->error);
        return false;
    }

    /**
     * Update lesson progress
     */
    public function updateLessonProgress($lessonId, $userId, $updates)
    {
        if (empty($updates)) {
            return false;
        }

        $setClauses = [];
        $params = [];
        $types = '';

        foreach ($updates as $field => $value) {
            $setClauses[] = "$field = ?";
            $params[] = $value;
            // Determine type: i for int/bool, d for float, s for string
            if (is_int($value) || is_bool($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        // Add lesson_id and student_id
        $params[] = $lessonId;
        $params[] = $userId;
        $types .= 'ii';

        $setClause = implode(', ', $setClauses);

        $sql = "
            INSERT INTO lesson_progress (lesson_id, student_id, " . implode(', ', array_keys($updates)) . ")
            VALUES (?, ?, " . implode(', ', array_fill(0, count($updates), '?')) . ")
            ON DUPLICATE KEY UPDATE $setClause, last_accessed_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare SQL: " . $this->conn->error);
            error_log("SQL: " . $sql);
            return false;
        }

        // bind_param requires references, so we need to use call_user_func_array
        $bindParams = array_merge([$types], $params);
        $refs = [];
        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);

        if (!$stmt->execute()) {
            error_log("Failed to execute: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Get assignment submission
     */
    public function getAssignmentSubmission($assignmentId, $userId)
    {
        $stmt = $this->conn->prepare("
            SELECT id, submission_type, file_path, file_size, text_content, 
                   points_earned, points_possible, percentage_score, is_late,
                   submitted_at, graded_at, feedback, status, attempt_number
            FROM assignment_submissions 
            WHERE assignment_id = ? AND student_id = ?
            ORDER BY attempt_number DESC
            LIMIT 1
        ");
        $stmt->bind_param('ii', $assignmentId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Grade assignment submission
     */
    public function gradeSubmission($submissionId, $gradedBy, $pointsEarned, $feedback)
    {
        $stmt = $this->conn->prepare("
            UPDATE assignment_submissions 
            SET points_earned = ?, percentage_score = (points_earned / points_possible) * 100,
                graded_at = CURRENT_TIMESTAMP, graded_by = ?, feedback = ?, status = 'graded'
            WHERE id = ?
        ");

        $stmt->bind_param('disi', $pointsEarned, $gradedBy, $feedback, $submissionId);
        return $stmt->execute();
    }

    /**
     * Add lesson resource
     */
    public function addResource($lessonId, $instructorId, $data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lesson_resources (
                lesson_id, instructor_id, title, description, resource_type,
                file_path, file_size, external_url, mime_type, is_downloadable, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'iissssssisi',
            $lessonId,
            $instructorId,
            $data['title'],
            $data['description'],
            $data['resource_type'],
            $data['file_path'] ?? null,
            $data['file_size'] ?? null,
            $data['external_url'] ?? null,
            $data['mime_type'] ?? null,
            $data['is_downloadable'],
            $data['sort_order'] ?? 0
        );

        return $stmt->execute();
    }
}
?>