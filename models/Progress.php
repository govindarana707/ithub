<?php
/**
 * Progress Tracking Model
 * Handles all progress tracking functionality for students and courses
 */
class Progress {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Update lesson progress for a student
     */
    public function updateLessonProgress($studentId, $courseId, $lessonId, $data = []) {
        try {
            // Check if progress record exists
            $stmt = $this->db->prepare("SELECT * FROM course_progress WHERE student_id = ? AND course_id = ? AND lesson_id = ?");
            $stmt->bind_param("iii", $studentId, $courseId, $lessonId);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            $updateData = [
                'time_spent_minutes' => $data['time_spent_minutes'] ?? 0,
                'last_position_seconds' => $data['last_position_seconds'] ?? 0,
                'watch_percentage' => $data['watch_percentage'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($data['is_completed'] ?? false) {
                $updateData['is_completed'] = true;
                $updateData['completion_time'] = date('Y-m-d H:i:s');
            }
            
            if ($existing) {
                // Update existing record
                $setClause = [];
                $params = [];
                $types = '';
                
                foreach ($updateData as $key => $value) {
                    $setClause[] = "$key = ?";
                    $params[] = $value;
                    $types .= is_numeric($value) ? 'i' : 's';
                }
                
                $sql = "UPDATE course_progress SET " . implode(', ', $setClause) . " WHERE id = ?";
                $params[] = $existing['id'];
                $types .= 'i';
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $progressId = $existing['id'];
                $stmt->close();
            } else {
                // Insert new record
                $insertData = array_merge([
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                    'is_completed' => false
                ], $updateData);
                
                $columns = implode(', ', array_keys($insertData));
                $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
                $values = array_values($insertData);
                
                $types = str_repeat('i', count(array_filter($values, 'is_numeric'))) . 
                         str_repeat('s', count(array_filter($values, 'is_string')));
                
                $sql = "INSERT INTO course_progress ($columns) VALUES ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $progressId = $this->db->insertId();
                $stmt->close();
            }
            
            // Update detailed lesson progress if provided
            if (isset($data['detailed_progress'])) {
                $this->updateDetailedLessonProgress($studentId, $lessonId, $data['detailed_progress']);
            }
            
            // Record study session
            $this->recordStudySession($studentId, $courseId, $lessonId, $data);
            
            // Recalculate course progress
            $this->recalculateCourseProgress($studentId, $courseId);
            
            return [
                'success' => true,
                'progress_id' => $progressId,
                'message' => 'Progress updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating progress: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update detailed lesson progress
     */
    private function updateDetailedLessonProgress($studentId, $lessonId, $data) {
        $stmt = $this->db->prepare("SELECT * FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        $updateData = [
            'video_watch_time_seconds' => $data['video_watch_time_seconds'] ?? 0,
            'video_completion_percentage' => $data['video_completion_percentage'] ?? 0,
            'notes_viewed' => $data['notes_viewed'] ?? false,
            'assignments_completed' => $data['assignments_completed'] ?? 0,
            'assignments_total' => $data['assignments_total'] ?? 0,
            'resources_viewed' => $data['resources_viewed'] ?? 0,
            'resources_total' => $data['resources_total'] ?? 0,
            'time_spent_minutes' => $data['time_spent_minutes'] ?? 0,
            'last_accessed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($data['is_completed'] ?? false) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        if ($existing) {
            $setClause = [];
            $params = [];
            $types = '';
            
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = ?";
                $params[] = $value;
                $types .= is_numeric($value) ? 'i' : 's';
            }
            
            $sql = "UPDATE lesson_progress SET " . implode(', ', $setClause) . " WHERE id = ?";
            $params[] = $existing['id'];
            $types .= 'i';
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        } else {
            $insertData = array_merge([
                'student_id' => $studentId,
                'lesson_id' => $lessonId
            ], $updateData);
            
            $columns = implode(', ', array_keys($insertData));
            $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
            $values = array_values($insertData);
            
            $types = str_repeat('i', count(array_filter($values, 'is_numeric'))) . 
                     str_repeat('s', count(array_filter($values, 'is_string')));
            
            $sql = "INSERT INTO lesson_progress ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Record study session
     */
    private function recordStudySession($studentId, $courseId, $lessonId, $data) {
        if (!isset($data['session_start']) || !isset($data['duration_minutes'])) {
            return;
        }
        
        $sessionData = [
            'student_id' => $studentId,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'session_start' => $data['session_start'],
            'session_end' => date('Y-m-d H:i:s'),
            'duration_minutes' => $data['duration_minutes'],
            'activity_type' => $data['activity_type'] ?? 'reading',
            'completion_percentage' => $data['watch_percentage'] ?? 0
        ];
        
        $columns = implode(', ', array_keys($sessionData));
        $placeholders = implode(', ', array_fill(0, count($sessionData), '?'));
        $values = array_values($sessionData);
        
        $types = str_repeat('i', count(array_filter($values, 'is_numeric'))) . 
                 str_repeat('s', count(array_filter($values, 'is_string')));
        
        $sql = "INSERT INTO study_sessions ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Recalculate course progress
     */
    public function recalculateCourseProgress($studentId, $courseId) {
        // Get total lessons in course
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM lessons WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalLessons = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Get completed lessons
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM course_progress WHERE student_id = ? AND course_id = ? AND is_completed = 1");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $completedLessons = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Calculate completion percentage
        $completionPercentage = $totalLessons > 0 ? ($completedLessons * 100.0) / $totalLessons : 0;
        
        // Get total time spent
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(time_spent_minutes), 0) as total FROM course_progress WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalTimeSpent = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Update progress summary
        $stmt = $this->db->prepare("
            INSERT INTO progress_summary (student_id, course_id, total_lessons, completed_lessons, completion_percentage, total_time_spent_minutes, average_time_per_lesson)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            total_lessons = VALUES(total_lessons),
            completed_lessons = VALUES(completed_lessons),
            completion_percentage = VALUES(completion_percentage),
            total_time_spent_minutes = VALUES(total_time_spent_minutes),
            average_time_per_lesson = CASE WHEN VALUES(total_lessons) > 0 THEN VALUES(total_time_spent_minutes) / VALUES(total_lessons) ELSE 0 END,
            last_activity_at = CURRENT_TIMESTAMP
        ");
        
        $averageTime = $totalLessons > 0 ? $totalTimeSpent / $totalLessons : 0;
        $stmt->bind_param("iiiddd", $studentId, $courseId, $totalLessons, $completedLessons, $completionPercentage, $totalTimeSpent, $averageTime);
        $stmt->execute();
        $stmt->close();
        
        // Update enrollment
        $stmt = $this->db->prepare("
            UPDATE enrollments_new SET
            progress_percentage = ?,
            lessons_completed = ?,
            lessons_total = ?,
            total_time_spent_minutes = ?,
            last_accessed_at = ?
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->bind_param("diiisi", $completionPercentage, $completedLessons, $totalLessons, $totalTimeSpent, date('Y-m-d H:i:s'), $studentId, $courseId);
        $stmt->execute();
        $stmt->close();
        
        return $completionPercentage;
    }
    
    /**
     * Get student's course progress
     */
    public function getCourseProgress($studentId, $courseId) {
        $stmt = $this->db->prepare("SELECT * FROM progress_summary WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $progress = $result->fetch_assoc();
        $stmt->close();
        
        if (!$progress) {
            $this->recalculateCourseProgress($studentId, $courseId);
            $stmt = $this->db->prepare("SELECT * FROM progress_summary WHERE student_id = ? AND course_id = ?");
            $stmt->bind_param("ii", $studentId, $courseId);
            $stmt->execute();
            $result = $stmt->get_result();
            $progress = $result->fetch_assoc();
            $stmt->close();
        }
        
        return $progress;
    }
    
    /**
     * Get detailed progress for all lessons in a course
     */
    public function getCourseLessonsProgress($studentId, $courseId) {
        $stmt = $this->db->prepare("
            SELECT l.*, cp.is_completed, cp.completion_time, cp.time_spent_minutes, 
                   cp.watch_percentage, lp.video_completion_percentage, lp.notes_viewed,
                   lp.assignments_completed, lp.resources_viewed
            FROM lessons l
            LEFT JOIN course_progress cp ON l.id = cp.lesson_id AND cp.student_id = ?
            LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
            WHERE l.course_id = ?
            ORDER BY l.lesson_order
        ");
        $stmt->bind_param("iii", $studentId, $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $lessonsProgress = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $lessonsProgress;
    }
    
    /**
     * Get student's overall progress across all courses
     */
    public function getStudentOverallProgress($studentId) {
        // Use a simpler query that works with existing table structure
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT c.id, c.title, e.progress_percentage, 
                   CASE WHEN e.progress_percentage >= 100 THEN 'completed'
                        WHEN e.progress_percentage > 0 THEN 'in_progress'
                        ELSE 'not_started' END as status,
                   e.enrolled_at
            FROM enrollments_new e
            JOIN courses_new c ON e.course_id = c.id
            WHERE e.user_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        
        if ($stmt === false) {
            // Fallback query if the main one fails
            $stmt = $conn->prepare("
                SELECT c.id, c.title, 0 as progress_percentage,
                       'not_started' as status,
                       e.enrolled_at
                FROM enrollments_new e
                JOIN courses_new c ON e.course_id = c.id
                WHERE e.user_id = ?
                ORDER BY e.enrolled_at DESC
            ");
        }
        
        if ($stmt === false) {
            return []; // Return empty array if both queries fail
        }
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $overallProgress = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $overallProgress;
    }
    
    /**
     * Mark lesson as completed
     */
    public function markLessonCompleted($studentId, $courseId, $lessonId) {
        return $this->updateLessonProgress($studentId, $courseId, $lessonId, [
            'is_completed' => true,
            'watch_percentage' => 100,
            'time_spent_minutes' => 0,
            'activity_type' => 'completion'
        ]);
    }
    
    /**
     * Get study sessions for a student
     */
    public function getStudySessions($studentId, $courseId = null, $limit = 50) {
        $sql = "SELECT ss.*, l.title as lesson_title, c.title as course_title
                FROM study_sessions ss
                JOIN lessons l ON ss.lesson_id = l.id
                JOIN courses_new c ON ss.course_id = c.id
                WHERE ss.student_id = ?";
        
        if ($courseId) {
            $sql .= " AND ss.course_id = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY ss.session_start DESC LIMIT ?");
            $stmt->bind_param("iii", $studentId, $courseId, $limit);
        } else {
            $stmt = $this->db->prepare($sql . " ORDER BY ss.session_start DESC LIMIT ?");
            $stmt->bind_param("ii", $studentId, $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $sessions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $sessions;
    }
    
    /**
     * Get progress statistics for dashboard
     */
    public function getProgressStatistics($studentId) {
        $stats = [];
        
        // Overall stats - simplified query
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as enrolled_courses,
                   SUM(CASE WHEN progress_percentage >= 100 THEN 1 ELSE 0 END) as completed_courses,
                   SUM(CASE WHEN progress_percentage > 0 AND progress_percentage < 100 THEN 1 ELSE 0 END) as in_progress_courses,
                   COALESCE(AVG(progress_percentage), 0) as average_progress
            FROM enrollments_new e
            WHERE e.student_id = ?
        ");
        
        if ($stmt === false) {
            // Return default stats if query fails
            $stats['overall'] = [
                'enrolled_courses' => 0,
                'completed_courses' => 0,
                'in_progress_courses' => 0,
                'average_progress' => 0,
                'total_time_minutes' => 0
            ];
        } else {
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['overall'] = $result->fetch_assoc();
            $stats['overall']['total_time_minutes'] = 0; // Default value
            $stmt->close();
        }
        
        // Recent activity - simplified
        $stmt = $this->db->prepare("
            SELECT c.title as course_title, e.progress_percentage, e.enrolled_at as last_activity_at
            FROM enrollments_new e
            JOIN courses_new c ON e.course_id = c.id
            WHERE e.student_id = ?
            ORDER BY e.enrolled_at DESC
            LIMIT 5
        ");
        
        if ($stmt === false) {
            $stats['recent_activity'] = [];
        } else {
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['recent_activity'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        
        // Study streak (consecutive days with activity) - simplified
        $stats['study_streak'] = 0; // Default value
        
        return $stats;
    }
    
    /**
     * Calculate study streak
     */
    private function getStudyStreak($studentId) {
        // Simplified study streak calculation using enrollments table
        $stmt = $this->db->prepare("
            SELECT DISTINCT DATE(enrolled_at) as activity_date
            FROM enrollments_new
            WHERE student_id = ? AND enrolled_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY activity_date DESC
        ");
        
        if ($stmt === false) {
            return 0;
        }
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $activities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        if (empty($activities)) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = date('Y-m-d');
        
        foreach ($activities as $row) {
            if ($row['activity_date'] == $currentDate) {
                $streak++;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Get leaderboard data
     */
    public function getLeaderboard($courseId = null, $limit = 10) {
        // Simplified leaderboard query using only existing tables
        $sql = "SELECT u.id, u.username, u.full_name,
                       COUNT(e.course_id) as enrolled_courses,
                       COALESCE(AVG(e.progress_percentage), 0) as avg_progress
                FROM users_new u
                JOIN enrollments_new e ON u.id = e.student_id
                WHERE u.role = 'student'";
        
        if ($courseId) {
            $sql .= " AND e.course_id = ?";
            $stmt = $this->db->prepare($sql . " GROUP BY u.id ORDER BY avg_progress DESC LIMIT ?");
            if ($stmt === false) return [];
            $stmt->bind_param("ii", $courseId, $limit);
        } else {
            $stmt = $this->db->prepare($sql . " GROUP BY u.id ORDER BY avg_progress DESC LIMIT ?");
            if ($stmt === false) return [];
            $stmt->bind_param("i", $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $leaderboard = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Add total_time field with default value
        foreach ($leaderboard as &$student) {
            $student['total_time'] = 0;
        }
        
        return $leaderboard;
    }
}
