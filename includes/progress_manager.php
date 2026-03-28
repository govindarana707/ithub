<?php
/**
 * Enhanced Progress Management System
 * Handles comprehensive progress tracking for courses and lessons
 */

require_once __DIR__ . '/../config/config.php';

class ProgressManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    /**
     * Initialize progress tracking for a student in a course
     */
    public function initializeCourseProgress($studentId, $courseId) {
        // Ensure lesson_progress table exists
        $this->ensureLessonProgressTable();
        
        // Get all lessons for the course
        $stmt = $this->conn->prepare("SELECT id FROM lessons WHERE course_id = ? AND is_published = 1");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Create progress entries for each lesson if they don't exist
        foreach ($lessons as $lesson) {
            $this->initializeLessonProgress($studentId, $lesson['id']);
        }
        
        // Update overall course progress
        $this->updateCourseProgress($studentId, $courseId);
    }
    
    /**
     * Initialize progress for a specific lesson
     */
    public function initializeLessonProgress($studentId, $lessonId) {
        // Check if progress already exists
        $stmt = $this->conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            // Create new progress entry
            $stmt = $this->conn->prepare("
                INSERT INTO lesson_progress 
                (student_id, lesson_id, completed, video_watch_time_seconds, video_completion_percentage, 
                 notes_viewed, assignments_completed, assignments_total, resources_viewed, resources_total, 
                 time_spent_minutes, last_accessed_at)
                VALUES (?, ?, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, NOW())
            ");
            $stmt->bind_param("ii", $studentId, $lessonId);
            $stmt->execute();
        }
    }
    
    /**
     * Update lesson progress when user interacts with lesson content
     */
    public function updateLessonProgress($studentId, $lessonId, $progressData = []) {
        // Ensure progress entry exists
        $this->initializeLessonProgress($studentId, $lessonId);
        
        // Build update query dynamically based on provided data
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($progressData['video_watch_time_seconds'])) {
            $updateFields[] = "video_watch_time_seconds = ?";
            $params[] = $progressData['video_watch_time_seconds'];
            $types .= "i";
        }
        
        if (isset($progressData['video_completion_percentage'])) {
            $updateFields[] = "video_completion_percentage = ?";
            $params[] = $progressData['video_completion_percentage'];
            $types .= "d";
        }
        
        if (isset($progressData['notes_viewed'])) {
            $updateFields[] = "notes_viewed = ?";
            $params[] = $progressData['notes_viewed'];
            $types .= "i";
        }
        
        if (isset($progressData['assignments_completed'])) {
            $updateFields[] = "assignments_completed = ?";
            $params[] = $progressData['assignments_completed'];
            $types .= "i";
        }
        
        if (isset($progressData['assignments_total'])) {
            $updateFields[] = "assignments_total = ?";
            $params[] = $progressData['assignments_total'];
            $types .= "i";
        }
        
        if (isset($progressData['resources_viewed'])) {
            $updateFields[] = "resources_viewed = ?";
            $params[] = $progressData['resources_viewed'];
            $types .= "i";
        }
        
        if (isset($progressData['resources_total'])) {
            $updateFields[] = "resources_total = ?";
            $params[] = $progressData['resources_total'];
            $types .= "i";
        }
        
        if (isset($progressData['time_spent_minutes'])) {
            $updateFields[] = "time_spent_minutes = ?";
            $params[] = $progressData['time_spent_minutes'];
            $types .= "i";
        }
        
        // Always update last_accessed_at
        $updateFields[] = "last_accessed_at = NOW()";
        
        // Calculate completion status
        $completionStatus = $this->calculateLessonCompletionStatus($studentId, $lessonId, $progressData);
        $updateFields[] = "completed = ?";
        $params[] = $completionStatus['completed'];
        $types .= "i";
        
        if ($completionStatus['completed'] && !isset($progressData['completed_at'])) {
            $updateFields[] = "completed_at = NOW()";
        }
        
        if (!empty($updateFields)) {
            $sql = "UPDATE lesson_progress SET " . implode(", ", $updateFields) . " WHERE student_id = ? AND lesson_id = ?";
            $params[] = $studentId;
            $params[] = $lessonId;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }
        
        // Update course progress
        $courseId = $this->getCourseIdByLesson($lessonId);
        if ($courseId) {
            $this->updateCourseProgress($studentId, $courseId);
        }
    }
    
    /**
     * Calculate lesson completion status based on various factors
     */
    private function calculateLessonCompletionStatus($studentId, $lessonId, $progressData = []) {
        // Get current progress data
        $stmt = $this->conn->prepare("SELECT * FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        $currentProgress = $stmt->get_result()->fetch_assoc();
        
        if (!$currentProgress) {
            return ['completed' => 0, 'percentage' => 0];
        }
        
        // Merge with new progress data
        $progress = array_merge($currentProgress, $progressData);
        
        // Calculate completion percentage based on multiple factors
        $completionFactors = [];
        
        // Video completion (40% weight)
        if ($progress['video_completion_percentage'] > 0) {
            $completionFactors[] = $progress['video_completion_percentage'] * 0.4;
        }
        
        // Notes viewed (20% weight)
        if (isset($progress['notes_viewed']) && $progress['notes_viewed'] > 0) {
            $completionFactors[] = 20;
        }
        
        // Assignments completion (30% weight)
        if (isset($progress['assignments_total']) && $progress['assignments_total'] > 0) {
            $assignmentProgress = ($progress['assignments_completed'] / $progress['assignments_total']) * 30;
            $completionFactors[] = $assignmentProgress;
        }
        
        // Resources viewed (10% weight)
        if (isset($progress['resources_total']) && $progress['resources_total'] > 0) {
            $resourceProgress = ($progress['resources_viewed'] / $progress['resources_total']) * 10;
            $completionFactors[] = $resourceProgress;
        }
        
        // Calculate overall completion percentage
        $totalPercentage = !empty($completionFactors) ? array_sum($completionFactors) : 0;
        
        // Consider lesson completed if 80% or more
        $isCompleted = $totalPercentage >= 80;
        
        return [
            'completed' => $isCompleted ? 1 : 0,
            'percentage' => round($totalPercentage, 2)
        ];
    }
    
    /**
     * Update overall course progress
     */
    public function updateCourseProgress($studentId, $courseId) {
        // Get total lessons for the course
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total_lessons FROM lessons WHERE course_id = ? AND is_published = 1");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $totalLessons = $stmt->get_result()->fetch_assoc()['total_lessons'];
        
        if ($totalLessons == 0) {
            return 0;
        }
        
        // Get completed lessons count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as completed_lessons 
            FROM lesson_progress lp 
            JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1 AND l.is_published = 1
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $completedLessons = $stmt->get_result()->fetch_assoc()['completed_lessons'];
        
        // Calculate progress percentage
        $progressPercentage = ($completedLessons / $totalLessons) * 100;
        
        // Update enrollment progress
        $stmt = $this->conn->prepare("
            UPDATE enrollments_new 
            SET progress_percentage = ?, 
                status = CASE 
                    WHEN progress_percentage >= 100 THEN 'completed'
                    ELSE 'active'
                END,
                completed_at = CASE 
                    WHEN progress_percentage >= 100 AND completed_at IS NULL THEN NOW()
                    ELSE completed_at
                END,
                updated_at = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->bind_param("dii", $progressPercentage, $studentId, $courseId);
        $stmt->execute();
        
        return round($progressPercentage, 2);
    }
    
    /**
     * Get detailed progress for a course
     */
    public function getCourseProgressDetails($studentId, $courseId) {
        $stmt = $this->conn->prepare("
            SELECT 
                l.id as lesson_id,
                l.title as lesson_title,
                l.lesson_order,
                l.duration_minutes,
                l.lesson_type,
                lp.completed,
                lp.video_completion_percentage,
                lp.time_spent_minutes,
                lp.last_accessed_at,
                lp.completed_at,
                CASE 
                    WHEN lp.completed = 1 THEN 'completed'
                    WHEN lp.video_completion_percentage > 0 OR lp.time_spent_minutes > 0 THEN 'in_progress'
                    ELSE 'not_started'
                END as status
            FROM lessons l
            LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
            WHERE l.course_id = ? AND l.is_published = 1
            ORDER BY l.lesson_order
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get progress statistics for a student
     */
    public function getStudentProgressStats($studentId) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT e.course_id) as enrolled_courses,
                COUNT(DISTINCT CASE WHEN e.progress_percentage >= 100 THEN e.course_id END) as completed_courses,
                AVG(e.progress_percentage) as average_progress,
                SUM(lp.time_spent_minutes) as total_study_time,
                COUNT(DISTINCT CASE WHEN lp.completed = 1 THEN lp.lesson_id END) as completed_lessons
            FROM enrollments_new e
            LEFT JOIN lesson_progress lp ON e.user_id = lp.student_id
            WHERE e.user_id = ? AND e.status = 'active'
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Mark lesson as complete
     */
    public function markLessonComplete($studentId, $lessonId) {
        $this->updateLessonProgress($studentId, $lessonId, [
            'completed' => 1,
            'video_completion_percentage' => 100,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update video progress
     */
    public function updateVideoProgress($studentId, $lessonId, $watchTimeSeconds, $completionPercentage) {
        $this->updateLessonProgress($studentId, $lessonId, [
            'video_watch_time_seconds' => $watchTimeSeconds,
            'video_completion_percentage' => $completionPercentage
        ]);
    }
    
    /**
     * Update study time
     */
    public function updateStudyTime($studentId, $lessonId, $additionalMinutes) {
        // Get current time spent
        $stmt = $this->conn->prepare("SELECT time_spent_minutes FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        
        $newTimeSpent = ($current['time_spent_minutes'] ?? 0) + $additionalMinutes;
        
        $this->updateLessonProgress($studentId, $lessonId, [
            'time_spent_minutes' => $newTimeSpent
        ]);
    }
    
    /**
     * Get course ID by lesson ID
     */
    private function getCourseIdByLesson($lessonId) {
        $stmt = $this->conn->prepare("SELECT course_id FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['course_id'] : null;
    }
    
    /**
     * Ensure lesson_progress table exists with proper structure
     */
    private function ensureLessonProgressTable() {
        // Table already exists, but we can add any missing columns if needed
        $this->conn->query("ALTER TABLE lesson_progress ADD COLUMN IF NOT EXISTS status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started'");
    }
    
    /**
     * Get learning streak for a student
     */
    public function getLearningStreak($studentId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT DATE(last_accessed_at)) as streak_days
            FROM lesson_progress
            WHERE student_id = ? 
            AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['streak_days'] ?? 0;
    }
    
    /**
     * Get next lesson to continue
     */
    public function getNextLesson($studentId, $courseId) {
        $stmt = $this->conn->prepare("
            SELECT l.id, l.title, l.lesson_order
            FROM lessons l
            LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
            WHERE l.course_id = ? AND l.is_published = 1
            ORDER BY 
                CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END,
                l.lesson_order
            LIMIT 1
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
