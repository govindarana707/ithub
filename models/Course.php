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
            INSERT INTO courses (title, description, category_id, instructor_id, price, duration_hours, difficulty_level, status, thumbnail, created_at, updated_at) 
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
        
        $sql = "UPDATE courses SET ";
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
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $enrollmentCount = ($stmt->get_result()->fetch_assoc()['count'] ?? 0);
        
        if ($enrollmentCount > 0) {
            return ['success' => false, 'error' => 'Cannot delete course with active enrollments'];
        }
        
        // Delete course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
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
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAllCourses($status = 'published', $limit = 50, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
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
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function searchCourses($query, $category = null, $difficulty = null, $limit = 20) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
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
        
        $stats = [];
        
        // Total courses
        $result = $conn->query("SELECT COUNT(*) as total FROM courses");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // Published courses
        $result = $conn->query("SELECT COUNT(*) as published FROM courses WHERE status = 'published'");
        $stats['published'] = $result->fetch_assoc()['published'];
        
        // Draft courses
        $result = $conn->query("SELECT COUNT(*) as draft FROM courses WHERE status = 'draft'");
        $stats['draft'] = $result->fetch_assoc()['draft'];
        
        // Total enrollments
        $result = $conn->query("SELECT COUNT(*) as enrollments FROM enrollments");
        $stats['enrollments'] = $result->fetch_assoc()['enrollments'];
        
        // Completed courses
        $result = $conn->query("SELECT COUNT(*) as completed FROM enrollments WHERE status = 'completed'");
        $stats['completed'] = $result->fetch_assoc()['completed'];
        
        // Total attempts
        $result = $conn->query("SELECT COUNT(*) as total_attempts FROM quiz_attempts");
        $stats['total_attempts'] = $result->fetch_assoc()['total_attempts'];
        
        return $stats;
    }
    
    public function getPopularCourses($limit = 10) {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            SELECT c.*, COALESCE(cat.name, 'Uncategorized') as category_name, COALESCE(u.full_name, 'Unknown Instructor') as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   COALESCE(AVG(e.progress_percentage), 0) as avg_progress
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN enrollments e ON c.id = e.course_id
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
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name
            FROM courses c
            JOIN categories cat ON c.category_id = cat.id
            JOIN users u ON c.instructor_id = u.id
            WHERE c.status = 'published' 
            AND c.id NOT IN (
                SELECT course_id FROM enrollments WHERE student_id = ?
            )
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("ii", $studentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCourseStatistics($courseId) {
        $conn = $this->db->getConnection();
        
        $stats = [];
        
        // Total enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['total_enrollments'] = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        
        // Active enrollments (progress > 0)
        $stmt = $conn->prepare("SELECT COUNT(*) as active FROM enrollments WHERE course_id = ? AND progress_percentage > 0");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['active_enrollments'] = ($stmt->get_result()->fetch_assoc()['active'] ?? 0);
        
        // Completed enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM enrollments WHERE course_id = ? AND progress_percentage = 100");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stats['completed_enrollments'] = ($stmt->get_result()->fetch_assoc()['completed'] ?? 0);
        
        // Average progress
        $stmt = $conn->prepare("SELECT AVG(progress_percentage) as avg_progress FROM enrollments WHERE course_id = ?");
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
            FROM courses c
            JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN enrollments e ON c.id = e.course_id
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
            INSERT INTO courses (title, description, category_id, instructor_id, price, duration_hours, difficulty_level, status, thumbnail, created_at, updated_at) 
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
                       CASE WHEN cl.student_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM lessons l
                LEFT JOIN completed_lessons cl ON l.id = cl.lesson_id AND cl.student_id = ?
                WHERE l.course_id = ?
                ORDER BY l.lesson_order
            ");
            $stmt->bind_param("ii", $studentId, $courseId);
        } else {
            $stmt = $conn->prepare("
                SELECT l.*, 0 as is_completed
                FROM lessons l
                WHERE l.course_id = ?
                ORDER BY l.lesson_order
            ");
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
        
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function markLessonComplete($studentId, $lessonId) {
        $conn = $this->db->getConnection();
        
        // Check if already completed
        $stmt = $conn->prepare("SELECT id FROM completed_lessons WHERE student_id = ? AND lesson_id = ?");
        $stmt->bind_param("ii", $studentId, $lessonId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return true; // Already completed
        }
        
        // Mark as completed
        $stmt = $conn->prepare("INSERT INTO completed_lessons (student_id, lesson_id, completed_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $studentId, $lessonId);
        
        return $stmt->execute();
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
            FROM completed_lessons cl
            JOIN lessons l ON cl.lesson_id = l.id
            WHERE cl.student_id = ? AND l.course_id = ?
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $completedLessons = (int)($stmt->get_result()->fetch_assoc()['completed'] ?? 0);
        
        // Calculate progress percentage
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
        
        // Update enrollment progress
        $stmt = $conn->prepare("UPDATE enrollments SET progress_percentage = ? WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("dii", $progress, $studentId, $courseId);
        
        return $stmt->execute();
    }
    
    public function getEnrolledCourses($studentId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, e.enrolled_at, e.progress_percentage, e.status as enrollment_status,
                   cat.name as category_name, u.full_name as instructor_name
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            JOIN categories cat ON c.category_id = cat.id
            JOIN users u ON c.instructor_id = u.id
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function enrollStudent($studentId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Check if already enrolled
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'error' => 'Already enrolled'];
        }
        
        // Enroll student
        error_log("Attempting to insert enrollment: student_id=$studentId, course_id=$courseId");
        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enrolled_at, progress_percentage, status) VALUES (?, ?, NOW(), 0, 'active')");
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
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
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
            UPDATE enrollments 
            SET progress_percentage = ?, updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ? AND course_id = ?
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
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN enrollments e ON c.id = e.course_id
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

        $sql = "SELECT COUNT(*) as total FROM courses c WHERE 1=1";
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
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['total'] ?? 0);
    }
}
?>
