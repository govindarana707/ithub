<?php
require_once __DIR__ . '/Database.php';

class Instructor {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get instructor profile information
     */
    public function getInstructorProfile($instructorId) {
        $conn = $this->db->getConnection();
        
        // Get basic user info
        $stmt = $conn->prepare("
            SELECT id, username, email, full_name, bio, profile_image, phone, status, created_at
            FROM users 
            WHERE id = ? AND role = 'instructor'
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        
        $profile = $stmt->get_result()->fetch_assoc();
        
        if ($profile) {
            // Get course statistics
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE instructor_id = ?");
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $profile['total_courses'] = $stmt->get_result()->fetch_assoc()['total'];
            
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT e.student_id) as total 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.instructor_id = ?
            ");
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $profile['total_students'] = $stmt->get_result()->fetch_assoc()['total'];
            
            $stmt = $conn->prepare("
                SELECT AVG(e.progress_percentage) as avg_progress 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.instructor_id = ?
            ");
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $avgProgress = $stmt->get_result()->fetch_assoc()['avg_progress'];
            $profile['avg_student_progress'] = $avgProgress ? round($avgProgress, 2) : 0;
            
            // Calculate total revenue
            $stmt = $conn->prepare("
                SELECT SUM(c.price) as total_revenue 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.instructor_id = ?
            ");
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $revenue = $stmt->get_result()->fetch_assoc()['total_revenue'];
            $profile['total_revenue'] = $revenue ? number_format($revenue, 2) : 0;
            
            // Get instructor specialties and qualifications
            $profile['specialties'] = $this->getInstructorSpecialties($instructorId);
            $profile['qualifications'] = $this->getInstructorQualifications($instructorId);
            $profile['social_links'] = $this->getInstructorSocialLinks($instructorId);
        }
        
        return $profile;
    }
    
    /**
     * Update instructor profile
     */
    public function updateInstructorProfile($instructorId, $data) {
        $conn = $this->db->getConnection();
        
        $allowedFields = ['full_name', 'bio', 'profile_image', 'phone'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        $sql = "UPDATE users SET ";
        $params = [];
        $types = "";
        
        foreach ($updateData as $key => $value) {
            $sql .= "$key = ?, ";
            $params[] = $value;
            $types .= "s";
        }
        
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE id = ? AND role = 'instructor'";
        $params[] = $instructorId;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Update specialties if provided
            if (isset($data['specialties'])) {
                $this->updateInstructorSpecialties($instructorId, $data['specialties']);
            }
            
            // Update qualifications if provided
            if (isset($data['qualifications'])) {
                $this->updateInstructorQualifications($instructorId, $data['qualifications']);
            }
            
            // Update social links if provided
            if (isset($data['social_links'])) {
                $this->updateInstructorSocialLinks($instructorId, $data['social_links']);
            }
            
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    /**
     * Get instructor's courses with detailed statistics
     */
    public function getInstructorCourses($instructorId, $status = null, $limit = 50, $offset = 0) {
        $conn = $this->db->getConnection();
        
        // Build basic query
        $sql = "
            SELECT c.*, cat.name as category_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.instructor_id = ?
        ";
        
        $params = [$instructorId];
        $types = "i";
        
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
        
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Add statistics for each course
        foreach ($courses as &$course) {
            // Get enrollment count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
            $stmt->bind_param("i", $course['id']);
            $stmt->execute();
            $course['enrollment_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Get average progress
            $stmt = $conn->prepare("SELECT AVG(progress_percentage) as avg FROM enrollments WHERE course_id = ?");
            $stmt->bind_param("i", $course['id']);
            $stmt->execute();
            $avg = $stmt->get_result()->fetch_assoc()['avg'];
            $course['avg_progress'] = $avg ? round($avg, 2) : 0;
            
            // Get completed count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ? AND progress_percentage = 100");
            $stmt->bind_param("i", $course['id']);
            $stmt->execute();
            $course['completed_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Get lesson count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lessons WHERE course_id = ?");
            $stmt->bind_param("i", $course['id']);
            $stmt->execute();
            $course['lesson_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Get rating
            $stmt = $conn->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM course_reviews WHERE course_id = ?");
            $stmt->bind_param("i", $course['id']);
            $stmt->execute();
            $ratingData = $stmt->get_result()->fetch_assoc();
            $course['avg_rating'] = $ratingData['avg'] ? round($ratingData['avg'], 1) : 0;
            $course['review_count'] = $ratingData['count'];
            
            // Calculate revenue
            $course['revenue'] = $course['price'] * $course['enrollment_count'];
        }
        
        return $courses;
    }
    
    /**
     * Get instructor's students with progress details
     */
    public function getInstructorStudents($instructorId, $courseId = null, $limit = 100, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT DISTINCT u.id, u.username, u.email, u.full_name, u.profile_image,
                   COUNT(e.course_id) as enrolled_courses,
                   AVG(e.progress_percentage) as avg_progress,
                   MAX(e.enrolled_at) as last_enrollment,
                   SUM(CASE WHEN e.progress_percentage = 100 THEN 1 ELSE 0 END) as completed_courses
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            JOIN courses c ON e.course_id = c.id
            WHERE c.instructor_id = ? AND u.role = 'student'
        ";
        
        $params = [$instructorId];
        $types = "i";
        
        if ($courseId) {
            $sql .= " AND c.id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.full_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get instructor analytics and statistics
     */
    public function getInstructorAnalytics($instructorId, $dateRange = '30days') {
        $conn = $this->db->getConnection();
        
        $analytics = [];
        
        // Date range calculation
        $dateCondition = $this->getDateCondition($dateRange);
        
        // Overall stats
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT CASE WHEN c.status = 'published' THEN c.id END) as published_courses,
                COUNT(DISTINCT e.student_id) as total_students,
                COUNT(DISTINCT e.id) as total_enrollments,
                AVG(e.progress_percentage) as avg_progress,
                SUM(c.price) as potential_revenue,
                COUNT(DISTINCT CASE WHEN e.progress_percentage = 100 THEN e.student_id END) as completed_students
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.instructor_id = ?
        ");
        
        if (!$stmt) {
            // Fallback: return default values if query fails
            $analytics['overview'] = [
                'total_courses' => 0,
                'published_courses' => 0,
                'total_students' => 0,
                'total_enrollments' => 0,
                'avg_progress' => 0,
                'potential_revenue' => 0,
                'completed_students' => 0
            ];
        } else {
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $analytics['overview'] = $stmt->get_result()->fetch_assoc();
        }
        
        // Recent enrollments
        $stmt = $conn->prepare("
            SELECT COUNT(*) as enrollments, DATE(e.enrolled_at) as date
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE c.instructor_id = ? AND $dateCondition
            GROUP BY DATE(e.enrolled_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        
        if (!$stmt) {
            // Fallback: return empty array if query fails
            $analytics['enrollment_trend'] = [];
        } else {
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $analytics['enrollment_trend'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Course performance
        $stmt = $conn->prepare("
            SELECT c.id, c.title, c.status,
                   COUNT(e.id) as enrollments,
                   AVG(e.progress_percentage) as avg_progress,
                   COUNT(DISTINCT CASE WHEN e.progress_percentage = 100 THEN e.student_id END) as completions,
                   (SELECT AVG(rating) FROM course_reviews WHERE course_id = c.id) as avg_rating
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.instructor_id = ?
            GROUP BY c.id
            ORDER BY enrollments DESC
            LIMIT 10
        ");
        
        if (!$stmt) {
            // Fallback query without course_reviews subquery
            $stmt = $conn->prepare("
                SELECT c.id, c.title, c.status,
                       COUNT(e.id) as enrollments,
                       AVG(e.progress_percentage) as avg_progress,
                       COUNT(DISTINCT CASE WHEN e.progress_percentage = 100 THEN e.student_id END) as completions,
                       0 as avg_rating
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE c.instructor_id = ?
                GROUP BY c.id
                ORDER BY enrollments DESC
                LIMIT 10
            ");
        }
        
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $analytics['course_performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Student engagement
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN e.progress_percentage > 0 THEN e.student_id END) as active_students,
                COUNT(DISTINCT CASE WHEN e.progress_percentage = 100 THEN e.student_id END) as completed_students,
                COUNT(DISTINCT CASE WHEN e.progress_percentage > 0 AND e.progress_percentage < 100 THEN e.student_id END) as in_progress_students,
                COUNT(DISTINCT CASE WHEN e.progress_percentage = 0 THEN e.student_id END) as not_started_students
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE c.instructor_id = ?
        ");
        
        if (!$stmt) {
            // Fallback: return default values if query fails
            $analytics['student_engagement'] = [
                'active_students' => 0,
                'completed_students' => 0,
                'in_progress_students' => 0,
                'not_started_students' => 0
            ];
        } else {
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $analytics['student_engagement'] = $stmt->get_result()->fetch_assoc();
        }
        
        return $analytics;
    }
    
    /**
     * Create course for instructor
     */
    public function createInstructorCourse($instructorId, $courseData) {
        $conn = $this->db->getConnection();
        
        // Validate instructor exists and is active
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'instructor' AND status = 'active'");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'error' => 'Invalid instructor account'];
        }
        
        // Set instructor_id and default status
        $courseData['instructor_id'] = $instructorId;
        $courseData['status'] = $courseData['status'] ?? 'draft';
        
        // Insert course
        $stmt = $conn->prepare("
            INSERT INTO courses (title, description, category_id, instructor_id, price, 
                               duration_hours, difficulty_level, status, thumbnail, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("ssiidisss", 
            $courseData['title'], 
            $courseData['description'], 
            $courseData['category_id'], 
            $courseData['instructor_id'], 
            $courseData['price'], 
            $courseData['duration_hours'], 
            $courseData['difficulty_level'], 
            $courseData['status'], 
            $courseData['thumbnail']
        );
        
        if ($stmt->execute()) {
            $courseId = $conn->insert_id;
            
            // Log activity
            $this->logInstructorActivity($instructorId, 'course_created', "Created course: {$courseData['title']}", $courseId);
            
            return ['success' => true, 'course_id' => $courseId];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    /**
     * Update instructor's course
     */
    public function updateInstructorCourse($instructorId, $courseId, $courseData) {
        $conn = $this->db->getConnection();
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $courseId, $instructorId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'error' => 'Course not found or access denied'];
        }
        
        // Build update query
        $sql = "UPDATE courses SET ";
        $params = [];
        $types = "";
        
        $allowedFields = ['title', 'description', 'category_id', 'price', 'duration_hours', 
                         'difficulty_level', 'status', 'thumbnail'];
        
        foreach ($allowedFields as $field) {
            if (isset($courseData[$field])) {
                $sql .= "$field = ?, ";
                $params[] = $courseData[$field];
                $types .= "s";
            }
        }
        
        $sql = rtrim($sql, ", ");
        $sql .= ", updated_at = NOW() WHERE id = ? AND instructor_id = ?";
        $params[] = $courseId;
        $params[] = $instructorId;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $this->logInstructorActivity($instructorId, 'course_updated', "Updated course ID: $courseId", $courseId);
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    /**
     * Delete instructor's course
     */
    public function deleteInstructorCourse($instructorId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Verify ownership and check enrollments
        $stmt = $conn->prepare("
            SELECT c.title, COUNT(e.id) as enrollment_count 
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id 
            WHERE c.id = ? AND c.instructor_id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("ii", $courseId, $instructorId);
        $stmt->execute();
        
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course) {
            return ['success' => false, 'error' => 'Course not found or access denied'];
        }
        
        if ($course['enrollment_count'] > 0) {
            return ['success' => false, 'error' => 'Cannot delete course with active enrollments'];
        }
        
        // Delete course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $courseId, $instructorId);
        
        if ($stmt->execute()) {
            $this->logInstructorActivity($instructorId, 'course_deleted', "Deleted course: {$course['title']}", $courseId);
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    /**
     * Get instructor's earnings and revenue data
     */
    public function getInstructorEarnings($instructorId, $dateRange = '30days') {
        $conn = $this->db->getConnection();
        
        $dateCondition = $this->getDateCondition($dateRange);
        
        $earnings = [];
        
        // Total revenue
        $stmt = $conn->prepare("
            SELECT 
                SUM(c.price) as total_revenue,
                COUNT(e.id) as total_enrollments,
                AVG(c.price) as avg_course_price
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE c.instructor_id = ? AND $dateCondition
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $earnings['summary'] = $stmt->get_result()->fetch_assoc();
        
        // Revenue by course
        $stmt = $conn->prepare("
            SELECT 
                c.id, c.title, c.price,
                COUNT(e.id) as enrollments,
                SUM(c.price) as revenue
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.instructor_id = ? AND $dateCondition
            GROUP BY c.id
            ORDER BY revenue DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $earnings['by_course'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Monthly revenue trend
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(e.enrolled_at, '%Y-%m') as month,
                SUM(c.price) as monthly_revenue,
                COUNT(e.id) as monthly_enrollments
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE c.instructor_id = ? AND $dateCondition
            GROUP BY DATE_FORMAT(e.enrolled_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $earnings['monthly_trend'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $earnings;
    }
    
    /**
     * Get instructor specialties
     */
    private function getInstructorSpecialties($instructorId) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("SELECT meta_value FROM instructor_meta WHERE instructor_id = ? AND meta_key = 'specialties'");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? json_decode($result['meta_value'], true) : [];
    }
    
    /**
     * Update instructor specialties
     */
    private function updateInstructorSpecialties($instructorId, $specialties) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("
            INSERT INTO instructor_meta (instructor_id, meta_key, meta_value, updated_at)
            VALUES (?, 'specialties', ?, NOW())
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()
        ");
        
        $specialtiesJson = json_encode($specialties);
        $stmt->bind_param("is", $instructorId, $specialtiesJson);
        
        return $stmt->execute();
    }
    
    /**
     * Get instructor qualifications
     */
    private function getInstructorQualifications($instructorId) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("SELECT meta_value FROM instructor_meta WHERE instructor_id = ? AND meta_key = 'qualifications'");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? json_decode($result['meta_value'], true) : [];
    }
    
    /**
     * Update instructor qualifications
     */
    private function updateInstructorQualifications($instructorId, $qualifications) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("
            INSERT INTO instructor_meta (instructor_id, meta_key, meta_value, updated_at)
            VALUES (?, 'qualifications', ?, NOW())
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()
        ");
        
        $qualificationsJson = json_encode($qualifications);
        $stmt->bind_param("is", $instructorId, $qualificationsJson);
        
        return $stmt->execute();
    }
    
    /**
     * Get instructor social links
     */
    private function getInstructorSocialLinks($instructorId) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("SELECT meta_value FROM instructor_meta WHERE instructor_id = ? AND meta_key = 'social_links'");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? json_decode($result['meta_value'], true) : [];
    }
    
    /**
     * Update instructor social links
     */
    private function updateInstructorSocialLinks($instructorId, $socialLinks) {
        $conn = $this->db->getConnection();
        
        $this->ensureInstructorMetaTable();
        
        $stmt = $conn->prepare("
            INSERT INTO instructor_meta (instructor_id, meta_key, meta_value, updated_at)
            VALUES (?, 'social_links', ?, NOW())
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()
        ");
        
        $socialLinksJson = json_encode($socialLinks);
        $stmt->bind_param("is", $instructorId, $socialLinksJson);
        
        return $stmt->execute();
    }
    
    /**
     * Ensure instructor_meta table exists
     */
    private function ensureInstructorMetaTable() {
        $conn = $this->db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS instructor_meta (
            instructor_id INT NOT NULL,
            meta_key VARCHAR(100) NOT NULL,
            meta_value LONGTEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (instructor_id, meta_key),
            CONSTRAINT fk_instructor_meta_user FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        return (bool)$conn->query($sql);
    }
    
    /**
     * Log instructor activity
     */
    private function logInstructorActivity($instructorId, $action, $details, $courseId = null) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO instructor_activity_log (instructor_id, action, details, course_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("issi", $instructorId, $action, $details, $courseId);
        return $stmt->execute();
    }
    
    /**
     * Get date condition for queries
     */
    private function getDateCondition($dateRange) {
        switch ($dateRange) {
            case '7days':
                return "e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1year':
                return "e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "1=1";
        }
    }
    
    /**
     * Get all instructors for admin management
     */
    public function getAllInstructors($status = 'active', $limit = 50, $offset = 0, $search = '') {
        $conn = $this->db->getConnection();
        
        $sql = "
            SELECT u.id, u.username, u.email, u.full_name, u.bio, u.profile_image, 
                   u.phone, u.status, u.created_at,
                   COUNT(DISTINCT c.id) as course_count,
                   COUNT(DISTINCT e.student_id) as student_count,
                   AVG(e.progress_percentage) as avg_progress
            FROM users u
            LEFT JOIN courses c ON u.id = c.instructor_id
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE u.role = 'instructor'
        ";
        
        $params = [];
        $types = "";
        
        if ($status && $status !== 'all') {
            $sql .= " AND u.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get instructor activity log
     */
    public function getInstructorActivityLog($instructorId, $limit = 50, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT ial.*, c.title as course_title
            FROM instructor_activity_log ial
            LEFT JOIN courses c ON ial.course_id = c.id
            WHERE ial.instructor_id = ?
            ORDER BY ial.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("iii", $instructorId, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
