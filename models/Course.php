<?php
require_once __DIR__ . '/Database.php';

class Course {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function createCourse($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO courses_new (title, description, category_id, instructor_id, price, duration_hours, difficulty_level, status, thumbnail, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("ssiidisss", 
            $data['title'], 
            $data['description'], 
            $data['category_id'], 
            $data['instructor_id'], 
            $data['price'], 
            $data['duration_hours'], 
            $data['difficulty_level'], 
            $data['status'], 
            $data['thumbnail']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'course_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function updateCourse($courseId, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE courses_new SET ";
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
        $params[] = $courseId;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function deleteCourse($courseId) {
        $conn = $this->db->getConnection();
        
        // Check if course has enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments_new WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $enrollmentCount = ($stmt->get_result()->fetch_assoc()['count'] ?? 0);
        
        if ($enrollmentCount > 0) {
            return ['success' => false, 'error' => 'Cannot delete course with active enrollments'];
        }
        
        // Delete course
        $stmt = $conn->prepare("DELETE FROM courses_new WHERE id = ?");
        $stmt->bind_param("i", $courseId);
        
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function getCourseById($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            WHERE c.id = ?
        ");
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAllCourses($status = 'published', $limit = 50, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare SQL query: " . $conn->error . " SQL: " . $sql);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCoursesWithFilters($filters = [], $limit = 12, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   0 as avg_rating,
                   0 as review_count,
                   COUNT(l.id) as lesson_count
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            LEFT JOIN enrollments_new e ON c.id = e.course_id
            LEFT JOIN lessons l ON c.id = l.course_id
            WHERE c.status = 'published'
        ";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND c.category_id = ?";
            $params[] = $filters['category_id'];
            $types .= "i";
        }
        
        if (!empty($filters['difficulty_level'])) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
            $types .= "s";
        }
        
        if (!empty($filters['price_range'])) {
            switch ($filters['price_range']) {
                case 'free':
                    $sql .= " AND c.price = 0";
                    break;
                case '0-1000':
                    $sql .= " AND c.price BETWEEN 0 AND 1000";
                    break;
                case '1000-5000':
                    $sql .= " AND c.price BETWEEN 1000 AND 5000";
                    break;
                case '5000+':
                    $sql .= " AND c.price > 5000";
                    break;
            }
        }
        
        $sql .= " GROUP BY c.id";
        
        // Apply sorting
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popular':
                    $sql .= " ORDER BY enrollment_count DESC, c.created_at DESC";
                    break;
                case 'rating':
                    $sql .= " ORDER BY avg_rating DESC, review_count DESC";
                    break;
                case 'price_low':
                    $sql .= " ORDER BY c.price ASC, c.created_at DESC";
                    break;
                case 'price_high':
                    $sql .= " ORDER BY c.price DESC, c.created_at DESC";
                    break;
                case 'newest':
                default:
                    $sql .= " ORDER BY c.created_at DESC";
                    break;
            }
        } else {
            $sql .= " ORDER BY c.created_at DESC";
        }
        
        // Get total count for pagination
        $countSql = str_replace("c.*, cat.name as category_name, u.full_name as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   0 as avg_rating,
                   0 as review_count,
                   COUNT(l.id) as lesson_count", "COUNT(DISTINCT c.id) as total", $sql);
        $countSql = preg_replace('/GROUP BY c\.id.*$/s', '', $countSql);
        
        $countStmt = $conn->prepare($countSql);
        if ($countStmt && !empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        if ($countStmt) {
            $countStmt->execute();
            $totalResult = $countStmt->get_result()->fetch_assoc();
            $totalCourses = $totalResult['total'] ?? 0;
        } else {
            $totalCourses = 0;
        }
        
        // Apply pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Failed to prepare courses query: " . $conn->error);
            return ['courses' => [], 'total' => 0];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Format data
        foreach ($courses as &$course) {
            $course['avg_rating'] = round((float)$course['avg_rating'], 1);
            $course['enrollment_count'] = (int)$course['enrollment_count'];
            $course['lesson_count'] = (int)$course['lesson_count'];
        }
        
        return [
            'courses' => $courses,
            'total' => $totalCourses
        ];
    }

    public function searchCourses($query, $category = null, $difficulty = null, $limit = 20) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            WHERE c.status = 'published' 
            AND (c.title LIKE ? OR c.description LIKE ?)
        ";
        
        $params = [];
        $types = "";
        
        if ($category) {
            $sql .= " AND c.category_id = ?";
            $params[] = $category;
            $types .= "i";
        }
        
        if ($difficulty) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $difficulty;
            $types .= "s";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $searchTerm = "%$query%";
        $stmt = $conn->prepare($sql);
        
        if ($category) {
            $stmt->bind_param("sssi", $searchTerm, $searchTerm, $category, $limit);
        } elseif ($difficulty) {
            $stmt->bind_param("sssi", $searchTerm, $searchTerm, $difficulty, $limit);
        } else {
            $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
        }
        
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCourseStats() {
        $conn = $this->db->getConnection();
        
        $stats = [
            'total' => 0,
            'published' => 0,
            'draft' => 0,
            'enrollments' => 0,
            'completed' => 0,
            'total_attempts' => 0
        ];
        
        // Total courses
        $result = $conn->query("SELECT COUNT(*) as total FROM courses_new");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total'] = $row['total'];
        }
        
        // Published courses
        $result = $conn->query("SELECT COUNT(*) as published FROM courses_new WHERE status = 'published'");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['published'] = $row['published'];
        }
        
        // Draft courses
        $result = $conn->query("SELECT COUNT(*) as draft FROM courses_new WHERE status = 'draft'");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['draft'] = $row['draft'];
        }
        
        // Total enrollments
        $result = $conn->query("SELECT COUNT(*) as enrollments FROM enrollments_new");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['enrollments'] = $row['enrollments'];
        }
        
        // Completed courses
        $result = $conn->query("SELECT COUNT(*) as completed FROM enrollments_new WHERE status = 'completed'");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['completed'] = $row['completed'];
        }
        
        // Total attempts
        $result = $conn->query("SELECT COUNT(*) as total_attempts FROM quiz_attempts");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_attempts'] = $row['total_attempts'];
        }
        
        return $stats;
    }
    
    public function getPopularCourses($limit = 10) {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            SELECT c.*, COALESCE(cat.name, 'Uncategorized') as category_name, COALESCE(u.full_name, 'Unknown Instructor') as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   COALESCE(AVG(e.progress_percentage), 0) as avg_progress
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            LEFT JOIN enrollments_new e ON c.id = e.course_id
            WHERE c.status = 'published'
            GROUP BY c.id
            ORDER BY enrollment_count DESC, avg_progress DESC
            LIMIT ?
        ");

        if (!$stmt) {
            // Prepare failed, return empty array
            return [];
        }

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getRecommendedCourses($studentId, $limit = 5) {
        // Try to get recommendations from the new recommendation system first
        require_once __DIR__ . '/RecommendationSystem.php';
        
        $recommendationSystem = new RecommendationSystem();
        
        // Check for cached recommendations
        $cachedRecommendations = $recommendationSystem->getCachedRecommendations($studentId, 'knn');
        
        if (!empty($cachedRecommendations)) {
            return array_slice($cachedRecommendations, 0, $limit);
        }
        
        // Generate new KNN recommendations
        $knnRecommendations = $recommendationSystem->getKNNRecommendations($studentId, $limit);
        
        if (!empty($knnRecommendations)) {
            return $knnRecommendations;
        }
        
        // Fallback to original random recommendations if KNN fails
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses_new c
            JOIN categories_new cat ON c.category_id = cat.id
            JOIN users_new u ON c.instructor_id = u.id
            WHERE c.status = 'published' 
            AND c.id NOT IN (
                SELECT course_id FROM enrollments_new WHERE user_id = ?
            )
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $studentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $fallbackRecommendations = $result->fetch_all(MYSQLI_ASSOC);
        
        // Add basic recommendation scores to fallback
        foreach ($fallbackRecommendations as &$rec) {
            $rec['recommendation_score'] = 0.5; // Default score
            $rec['recommendation_reason'] = 'Popular course';
        }
        
        return $fallbackRecommendations;
    }
    
    public function getCourseStatistics($courseId) {
        $conn = $this->db->getConnection();
        
        $stats = [];
        
        // Total enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments_new WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['total_enrollments'] = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        
        // Active enrollments (progress > 0)
        $stmt = $conn->prepare("SELECT COUNT(*) as active FROM enrollments_new WHERE course_id = ? AND progress_percentage > 0");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['active_enrollments'] = ($stmt->get_result()->fetch_assoc()['active'] ?? 0);
        
        // Completed enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM enrollments_new WHERE course_id = ? AND progress_percentage = 100");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['completed_enrollments'] = ($stmt->get_result()->fetch_assoc()['completed'] ?? 0);
        
        // Average progress
        $stmt = $conn->prepare("SELECT AVG(progress_percentage) as avg_progress FROM enrollments_new WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['avg_progress'] = round((float)($stmt->get_result()->fetch_assoc()['avg_progress'] ?? 0), 2);
        
        // Total lessons
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['total_lessons'] = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        
        return $stats;
    }
    
    public function getCoursesByInstructor($instructorId, $limit = 50) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name,
                   COUNT(e.id) as enrollment_count,
                   AVG(e.progress_percentage) as avg_progress
            FROM courses_new c
            JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN enrollments_new e ON c.id = e.course_id
            WHERE c.instructor_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $instructorId, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function duplicateCourse($courseId, $newTitle) {
        $conn = $this->db->getConnection();
        
        // Get original course
        $original = $this->getCourseById($courseId);
        if (!$original) {
            return ['success' => false, 'error' => 'Original course not found'];
        }
        
        // Create duplicate
        $stmt = $conn->prepare("
            INSERT INTO courses_new (title, description, category_id, instructor_id, price, duration_hours, difficulty_level, status, thumbnail, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("ssiidisss", 
            $newTitle,
            $original['description'],
            $original['category_id'],
            $original['instructor_id'],
            $original['price'],
            $original['duration_hours'],
            $original['difficulty_level'],
            'draft',
            $original['thumbnail']
        );
        
        if ($stmt->execute()) {
            $newCourseId = $conn->insert_id;
            
            return ['success' => true, 'new_course_id' => $newCourseId];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function getCourseLessons($courseId, $studentId = null) {
        $conn = $this->db->getConnection();
        
        if ($studentId === null) {
            $studentId = $_SESSION['user_id'] ?? null;
        }
        
        if ($studentId) {
            $stmt = $conn->prepare("
                SELECT l.*, 
                       CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END as is_completed
                FROM lessons l
                LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ?
                WHERE l.course_id = ?
                ORDER BY l.lesson_order
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare query: " . $conn->error);
            }
            $stmt->bind_param("ii", $studentId, $courseId);
        } else {
            $stmt = $conn->prepare("
                SELECT l.*, 0 as is_completed
                FROM lessons l
                WHERE l.course_id = ?
                ORDER BY l.lesson_order
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare query: " . $conn->error);
            }
            $stmt->bind_param("i", $courseId);
        }
        
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getLessonById($lessonId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getEnrollment($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM enrollments_new WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function markLessonComplete($studentId, $lessonId) {
        $conn = $this->db->getConnection();
        
        error_log("markLessonComplete: student_id=$studentId, lesson_id=$lessonId");
        
        // Check if already exists in lesson_progress
        $stmt = $conn->prepare("SELECT id, completed FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
        if ($stmt === false) {
            error_log("Failed to prepare select statement: " . $conn->error);
            return false;
        }
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            // Record exists
            $isAlreadyCompleted = $result['completed'] == 1;
            error_log("Progress record exists: id={$result['id']}, completed=$isAlreadyCompleted");
            
            if ($isAlreadyCompleted) {
                // Already completed - return success
                error_log("Lesson already marked as complete");
                return true;
            } else {
                // Update existing record
                $stmt = $conn->prepare("UPDATE lesson_progress SET completed = 1, last_accessed_at = NOW(), completed_at = NOW() WHERE student_id = ? AND lesson_id = ?");
                if ($stmt === false) {
                    error_log("Failed to prepare update statement: " . $conn->error);
                    return false;
                }
                $stmt->bind_param("ii", $studentId, $lessonId);
                $success = $stmt->execute();
                error_log("Update result: " . ($success ? 'success' : 'failed'));
                $stmt->close();
                return $success;
            }
        } else {
            // Insert new record
            error_log("No existing record, inserting new one");
            $stmt = $conn->prepare("INSERT INTO lesson_progress (student_id, lesson_id, completed, last_accessed_at, completed_at) VALUES (?, ?, 1, NOW(), NOW())");
            if ($stmt === false) {
                error_log("Failed to prepare insert statement: " . $conn->error);
                return false;
            }
            $stmt->bind_param("ii", $studentId, $lessonId);
            $success = $stmt->execute();
            error_log("Insert result: " . ($success ? 'success' : 'failed'));
            $stmt->close();
            return $success;
        }
    }
    
    public function updateCourseProgress($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Get total lessons and completed lessons
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $totalLessons = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as completed 
            FROM lesson_progress lp
            JOIN lessons l ON lp.lesson_id = l.id
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $completedLessons = (int)($stmt->get_result()->fetch_assoc()['completed'] ?? 0);
        
        // Calculate progress percentage
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
        
        // Update enrollment progress
        $stmt = $conn->prepare("UPDATE enrollments_new SET progress_percentage = ? WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("dii", $progress, $studentId, $courseId);
        
        return $stmt->execute();
    }
    
    public function getEnrolledCourses($studentId) {
        $conn = $this->db->getConnection();
        
        // First check if enrollments_new table has any data for this student
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments_new WHERE user_id = ?");
        if ($checkStmt === false) {
            error_log("Failed to prepare enrollment check query: " . $conn->error);
            return [];
        }
        $checkStmt->bind_param("i", $studentId);
        $checkStmt->execute();
        $count = $checkStmt->get_result()->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($count == 0) {
            return []; // No enrollments found
        }
        
        // Use courses_new table (the correct table) with optimized query
        $stmt = $conn->prepare("
            SELECT c.*, e.enrolled_at, e.updated_at, e.status as enrollment_status,
                   cat.name as category_name, u.full_name as instructor_name
            FROM courses_new c
            JOIN enrollments_new e ON c.id = e.course_id
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            WHERE e.user_id = ? AND e.status = 'active'
            ORDER BY e.updated_at DESC, e.enrolled_at DESC
        ");
        
        if ($stmt === false) {
            error_log("Failed to prepare enrolled courses query: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        
        // Batch calculate progress for better performance
        $courseIds = array_column($courses, 'id');
        if (!empty($courseIds)) {
            $batchProgress = $this->batchCalculateProgress($studentId, $courseIds);
            
            foreach ($courses as &$course) {
                $course['progress_percentage'] = $batchProgress[$course['id']] ?? 0;
                
                // Get next lesson for resume functionality
                $nextLesson = $this->getNextLesson($studentId, $course['id']);
                $course['next_lesson'] = $nextLesson;
                $course['total_lessons'] = $this->getTotalLessons($course['id']);
                $course['completed_lessons'] = $this->getCompletedLessons($studentId, $course['id']);
            }
        }
        
        return $courses;
    }
    
    public function getTotalLessons($courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ? AND is_published = 1");
        if ($stmt === false) {
            return 0;
        }
        
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }
    
    public function getCompletedLessons($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as completed
            FROM lesson_progress lp
            JOIN lessons l ON lp.lesson_id = l.id
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1 AND l.is_published = 1
        ");
        
        if ($stmt === false) {
            return 0;
        }
        
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['completed'] ?? 0;
    }
    
    public function calculateCourseProgress($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Get total lessons for the course
        $lessonStmt = $conn->prepare("SELECT COUNT(*) as total_lessons FROM lessons WHERE course_id = ? AND is_published = 1");
        if ($lessonStmt === false) {
            error_log("Failed to prepare lesson count query: " . $conn->error);
            return 0;
        }
        $lessonStmt->bind_param("i", $courseId);
        $lessonStmt->execute();
        $totalLessons = $lessonStmt->get_result()->fetch_assoc()['total_lessons'];
        
        if ($totalLessons == 0) {
            return 0;
        }
        
        // Check if lesson_progress table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'lesson_progress'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, return 0 progress
            return 0;
        }
        
        // Get completed lessons for the student using the correct field
        $completedStmt = $conn->prepare("
            SELECT COUNT(*) as completed_lessons 
            FROM lesson_progress lp 
            JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1 AND l.is_published = 1
        ");
        if ($completedStmt === false) {
            error_log("Failed to prepare completed lessons query: " . $conn->error);
            return 0;
        }
        $completedStmt->bind_param("ii", $studentId, $courseId);
        $completedStmt->execute();
        $completedLessons = $completedStmt->get_result()->fetch_assoc()['completed_lessons'];
        
        $progress = ($completedLessons / $totalLessons) * 100;
        return round($progress, 2);
    }
    
    public function updateEnrollmentProgress($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        $progress = $this->calculateCourseProgress($studentId, $courseId);
        
        // Update enrollment progress
        $stmt = $conn->prepare("
            UPDATE enrollments_new 
            SET progress_percentage = ?, 
                status = CASE 
                    WHEN progress_percentage >= 100 THEN 'completed'
                    ELSE 'active'
                END,
                completed_at = CASE 
                    WHEN progress_percentage >= 100 AND completed_at IS NULL THEN NOW()
                    ELSE completed_at
                END
            WHERE student_id = ? AND course_id = ?
        ");
        
        $stmt->bind_param("dii", $progress, $studentId, $courseId);
        return $stmt->execute();
    }
    
    public function hasCertificate($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Check if certificates table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'certificates'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, return false
            return false;
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM certificates 
            WHERE student_id = ? AND course_id = ? AND status = 'active'
        ");
        if ($stmt === false) {
            error_log("Failed to prepare certificate check query: " . $conn->error);
            return false;
        }
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['count'] > 0;
    }
    
    public function getStudyTime($studentId, $courseId = null) {
        $conn = $this->db->getConnection();
        
        // Check if study_sessions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'study_sessions'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, return 0
            return 0;
        }
        
        if ($courseId) {
            // Get study time for specific course
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(study_time), 0) as total_time 
                FROM study_sessions 
                WHERE student_id = ? AND course_id = ?
            ");
            if ($stmt === false) {
                error_log("Failed to prepare study time query for course: " . $conn->error);
                return 0;
            }
            $stmt->bind_param("ii", $studentId, $courseId);
        } else {
            // Get total study time for all courses
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(study_time), 0) as total_time 
                FROM study_sessions 
                WHERE student_id = ?
            ");
            if ($stmt === false) {
                error_log("Failed to prepare total study time query: " . $conn->error);
                return 0;
            }
            $stmt->bind_param("i", $studentId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Convert minutes to hours
        return round(($result['total_time'] ?? 0) / 60, 1);
    }
    
    public function getEnrollmentStats($studentId) {
        $conn = $this->db->getConnection();
        
        $stats = [];
        
        // Total enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments_new WHERE user_id = ? AND status = 'active'");
        if ($stmt === false) {
            error_log("Failed to prepare total enrollments query: " . $conn->error);
            return ['total_enrollments' => 0, 'completed_courses' => 0, 'in_progress' => 0, 'completion_rate' => 0, 'total_study_hours' => 0];
        }
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $stats['total_enrollments'] = $stmt->get_result()->fetch_assoc()['total'];
        
        // Calculate completed courses based on actual progress
        $enrolledCourses = $this->getEnrolledCourses($studentId);
        $courseIds = array_column($enrolledCourses, 'id');
        
        if (!empty($courseIds)) {
            $batchProgress = $this->batchCalculateProgress($studentId, $courseIds);
            $completedCount = 0;
            
            foreach ($batchProgress as $progress) {
                if ($progress >= 100) {
                    $completedCount++;
                }
            }
            
            $stats['completed_courses'] = $completedCount;
        } else {
            $stats['completed_courses'] = 0;
        }
        
        // In progress courses
        $stats['in_progress'] = $stats['total_enrollments'] - $stats['completed_courses'];
        
        // Completion rate
        $stats['completion_rate'] = $stats['total_enrollments'] > 0 
            ? round(($stats['completed_courses'] / $stats['total_enrollments']) * 100, 1)
            : 0;
        
        // Total study time
        $stats['total_study_hours'] = $this->getStudyTime($studentId);
        
        return $stats;
    }
    
    public function enrollStudent($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Check if already enrolled
        $stmt = $conn->prepare("SELECT id FROM enrollments_new WHERE user_id = ? AND course_id = ? AND status = 'active'");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'error' => 'Already enrolled'];
        }
        
        // Enroll student
        error_log("Attempting to insert enrollment: user_id=$studentId, course_id=$courseId");
        $stmt = $conn->prepare("INSERT INTO enrollments_new (user_id, course_id, enrolled_at, progress_percentage, status, enrollment_type) VALUES (?, ?, NOW(), 0, 'active', 'paid')");
        $stmt->bind_param("ii", $studentId, $courseId);

        if ($stmt->execute()) {
            return ['success' => true, 'enrollment_id' => $conn->insert_id];
        } else {
            error_log("enrollStudent INSERT failed: " . $stmt->error);
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function getEnrolledStudents($courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.email, u.full_name, u.profile_image,
                   e.enrolled_at, e.progress_percentage, e.status as enrollment_status
            FROM users_new u
            JOIN enrollments_new e ON u.id = e.user_id
            WHERE e.course_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function updateProgress($studentId, $courseId, $progress) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE enrollments_new 
            SET progress_percentage = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->bind_param("dii", $progress, $studentId, $courseId);
        
        return $stmt->execute();
    }

    private function ensureCourseMetaTable() {
        $conn = $this->db->getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS course_meta (
            course_id INT NOT NULL,
            meta_key VARCHAR(100) NOT NULL,
            meta_value LONGTEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (course_id, meta_key),
            CONSTRAINT fk_course_meta_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )";

        return (bool)$conn->query($sql);
    }

    public function getCourseMeta($courseId) {
        $this->ensureCourseMetaTable();
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("SELECT meta_key, meta_value FROM course_meta WHERE course_id = ?");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $meta = [];
        foreach ($rows as $row) {
            $key = $row['meta_key'];
            $value = $row['meta_value'];
            $decoded = json_decode($value, true);
            $meta[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }

        return $meta;
    }

    public function setCourseMeta($courseId, $meta) {
        $this->ensureCourseMetaTable();
        $conn = $this->db->getConnection();

        if (!is_array($meta)) {
            return false;
        }

        $stmt = $conn->prepare(
            "INSERT INTO course_meta (course_id, meta_key, meta_value, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()"
        );

        foreach ($meta as $key => $value) {
            $metaKey = trim((string)$key);
            if ($metaKey === '') {
                continue;
            }

            $payload = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            $stmt->bind_param('iss', $courseId, $metaKey, $payload);
            if (!$stmt->execute()) {
                return false;
            }
        }

        return true;
    }

    public function getAdminCourses($filters = [], $limit = 20, $offset = 0) {
        $conn = $this->db->getConnection();

        $search = trim((string)($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $status = $filters['status'] ?? null;
        $instructorId = $filters['instructor_id'] ?? null;

        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   ROUND(AVG(e.progress_percentage), 2) as avg_progress
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            LEFT JOIN enrollments_new e ON c.id = e.course_id
            WHERE 1=1
        ";

        $params = [];
        $types = '';

        if ($search !== '') {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }

        if ($categoryId !== null && $categoryId !== '') {
            $sql .= " AND c.category_id = ?";
            $params[] = (int)$categoryId;
            $types .= 'i';
        }

        if ($status !== null && $status !== '') {
            $sql .= " AND c.status = ?";
            $params[] = (string)$status;
            $types .= 's';
        }

        if ($instructorId !== null && $instructorId !== '') {
            $sql .= " AND c.instructor_id = ?";
            $params[] = (int)$instructorId;
            $types .= 'i';
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $types .= 'ii';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countAdminCourses($filters = []) {
        $conn = $this->db->getConnection();

        $search = trim((string)($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $status = $filters['status'] ?? null;
        $instructorId = $filters['instructor_id'] ?? null;

        $sql = "SELECT COUNT(*) as total FROM courses_new c WHERE 1=1";
        $params = [];
        $types = '';

        if ($search !== '') {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }

        if ($categoryId !== null && $categoryId !== '') {
            $sql .= " AND c.category_id = ?";
            $params[] = (int)$categoryId;
            $types .= 'i';
        }

        if ($status !== null && $status !== '') {
            $sql .= " AND c.status = ?";
            $params[] = (string)$status;
            $types .= 's';
        }

        if ($instructorId !== null && $instructorId !== '') {
            $sql .= " AND c.instructor_id = ?";
            $params[] = (int)$instructorId;
            $types .= 'i';
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Get the next lesson for a student in a course
     */
    public function getNextLesson($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Check if required tables exist
        $lessonsCheck = $conn->query("SHOW TABLES LIKE 'lessons'");
        $lessonProgressCheck = $conn->query("SHOW TABLES LIKE 'lesson_progress'");
        
        if ($lessonsCheck->num_rows == 0 || $lessonProgressCheck->num_rows == 0) {
            return null; // Tables don't exist, return null
        }
        
        // Get the next lesson that is not yet completed by the student
        $stmt = $conn->prepare("
            SELECT l.*
            FROM lessons l
            LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.student_id = ? AND lp.completed = 1
            WHERE l.course_id = ? AND l.is_published = 1 AND lp.lesson_id IS NULL
            ORDER BY l.lesson_order ASC
            LIMIT 1
        ");
        
        if ($stmt === false) {
            error_log("SQL prepare failed in getNextLesson: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }
    
    /**
     * Batch calculate progress for multiple courses (reduces N+1 queries)
     * @param int $studentId
     * @param array $courseIds
     * @return array [courseId => progressPercentage]
     */
    public function batchCalculateProgress($studentId, array $courseIds) {
        if (empty($courseIds)) {
            return [];
        }
        
        $conn = $this->db->getConnection();
        $result = [];
        
        // Initialize all course IDs with 0 progress
        foreach ($courseIds as $cid) {
            $result[$cid] = 0;
        }
        
        // Get lesson counts for all courses
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $types = str_repeat('i', count($courseIds));
        
        $lessonStmt = $conn->prepare("
            SELECT course_id, COUNT(*) as total_lessons 
            FROM lessons 
            WHERE course_id IN ($placeholders)
            GROUP BY course_id
        ");
        
        if ($lessonStmt === false) {
            error_log("Failed to prepare lesson count query: " . $conn->error);
            return $result;
        }
        
        $lessonStmt->bind_param($types, ...$courseIds);
        $lessonStmt->execute();
        $lessonResult = $lessonStmt->get_result();
        $lessonCounts = [];
        if ($lessonResult) {
            while ($row = $lessonResult->fetch_assoc()) {
                $lessonCounts[$row['course_id']] = (int)$row['total_lessons'];
            }
        }
        $lessonStmt->close();
        
        // Get completed lesson counts for all courses (using 'completed' column)
        $completedStmt = $conn->prepare("
            SELECT l.course_id, COUNT(*) as completed_lessons 
            FROM lesson_progress lp 
            JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.student_id = ? AND lp.completed = 1 AND l.course_id IN ($placeholders)
            GROUP BY l.course_id
        ");
        
        if ($completedStmt === false) {
            error_log("Failed to prepare completed lessons query: " . $conn->error);
            return $result;
        }
        
        $allTypes = 'i' . $types;
        $allParams = array_merge([$studentId], $courseIds);
        $completedStmt->bind_param($allTypes, ...$allParams);
        $completedStmt->execute();
        $completedResult = $completedStmt->get_result();
        $completedCounts = [];
        if ($completedResult) {
            while ($row = $completedResult->fetch_assoc()) {
                $completedCounts[$row['course_id']] = (int)$row['completed_lessons'];
            }
        }
        $completedStmt->close();
        
        // Calculate progress for each course
        foreach ($courseIds as $courseId) {
            $total = $lessonCounts[$courseId] ?? 0;
            $completed = $completedCounts[$courseId] ?? 0;
            
            // Handle edge case: if course has no lessons, consider it as 100% if enrolled
            if ($total == 0) {
                // Check if course has any lessons at all
                $lessonCheckStmt = $conn->prepare("SELECT COUNT(*) as lesson_count FROM lessons WHERE course_id = ?");
                $lessonCheckStmt->bind_param("i", $courseId);
                $lessonCheckStmt->execute();
                $lessonCheckResult = $lessonCheckStmt->get_result()->fetch_assoc();
                $lessonCheckStmt->close();
                
                if ($lessonCheckResult && $lessonCheckResult['lesson_count'] > 0) {
                    // Course has lessons but batch calculation failed, fallback to individual calculation
                    $total = $lessonCheckResult['lesson_count'];
                    $result[$courseId] = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
                } else {
                    // Course has no lessons, consider it as 100% (course is completed without lessons)
                    $result[$courseId] = 100;
                }
            } else {
                // Normal calculation
                $result[$courseId] = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
            }
        }
        
        return $result;
    }
    
    /**
     * Batch check certificates for multiple courses
     * @param int $studentId
     * @param array $courseIds
     * @return array [courseId => hasCertificate]
     */
    public function batchHasCertificates($studentId, array $courseIds) {
        if (empty($courseIds)) {
            return [];
        }
        
        $conn = $this->db->getConnection();
        
        // Check if certificates table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'certificates'");
        if ($tableCheck->num_rows == 0) {
            return array_fill_keys($courseIds, false);
        }
        
        $placeholders = str_repeat('?,', count($courseIds) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT course_id 
            FROM certificates 
            WHERE student_id = ? AND status = 'active' AND course_id IN ($placeholders)
        ");
        $types = 'i' . str_repeat('i', count($courseIds));
        $params = array_merge([$studentId], $courseIds);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = array_fill_keys($courseIds, false);
        while ($row = $stmt->get_result()->fetch_assoc()) {
            $result[$row['course_id']] = true;
        }
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Batch get study time for multiple courses
     * @param int $studentId
     * @param array $courseIds
     * @return array [courseId => studyHours]
     */
    public function batchGetStudyTime($studentId, array $courseIds) {
        if (empty($courseIds)) {
            return [];
        }
        
        $conn = $this->db->getConnection();
        
        // Check if study_sessions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'study_sessions'");
        if ($tableCheck->num_rows == 0) {
            return array_fill_keys($courseIds, 0);
        }
        
        $placeholders = str_repeat('?,', count($courseIds) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT course_id, COALESCE(SUM(study_time), 0) as total_time 
            FROM study_sessions 
            WHERE student_id = ? AND course_id IN ($placeholders)
            GROUP BY course_id
        ");
        $types = 'i' . str_repeat('i', count($courseIds));
        $params = array_merge([$studentId], $courseIds);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = array_fill_keys($courseIds, 0);
        while ($row = $stmt->get_result()->fetch_assoc()) {
            $result[$row['course_id']] = round(($row['total_time'] ?? 0) / 60, 1);
        }
        $stmt->close();
        
        return $result;
    }
}
?>
