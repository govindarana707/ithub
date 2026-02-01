<?php
require_once 'Database.php';

class Discussion {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function createDiscussion($data) {
        $conn = $this->db->getConnection();
        
        // Check if discussions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'discussions'");
        if ($tableCheck->num_rows == 0) {
            return ['success' => false, 'error' => 'Discussions table does not exist'];
        }
        
        // Validate required data
        if (!isset($data['course_id']) || !isset($data['student_id']) || !isset($data['title']) || !isset($data['content'])) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        
        // Use the actual table structure
        $stmt = $conn->prepare("INSERT INTO discussions (course_id, student_id, title, content, lesson_id, pinned, locked) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            return ['success' => false, 'error' => 'SQL prepare failed: ' . $conn->error];
        }
        
        $lessonId = $data['lesson_id'] ?? null;
        $pinned = $data['pinned'] ?? 0;
        $locked = $data['locked'] ?? 0;
        
        $stmt->bind_param("iissiii", $data['course_id'], $data['student_id'], $data['title'], $data['content'], $lessonId, $pinned, $locked);
        
        if ($stmt->execute()) {
            return ['success' => true, 'discussion_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => 'SQL execute failed: ' . $stmt->error];
        }
    }
    
    public function updateDiscussion($id, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE discussions SET ";
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
    
    public function deleteDiscussion($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM discussions WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    public function getDiscussionById($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT d.*, u.full_name, u.profile_image, c.title as course_title
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            JOIN courses c ON d.course_id = c.id
            WHERE d.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getDiscussionsByCourse($courseId, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        // Validate inputs
        $courseId = intval($courseId);
        $limit = intval($limit);
        $offset = intval($offset);
        
        if ($courseId <= 0 || $limit <= 0 || $offset < 0) {
            return [];
        }
        
        // Get main discussions with error handling (updated for actual table structure)
        $sql = "
            SELECT d.*, u.full_name, u.profile_image, c.title as course_title
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            LEFT JOIN courses c ON d.course_id = c.id
            WHERE d.course_id = ?
            ORDER BY d.pinned DESC, d.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("SQL prepare failed in getDiscussionsByCourse: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("iii", $courseId, $limit, $offset);
        if (!$stmt->execute()) {
            error_log("SQL execute failed in getDiscussionsByCourse: " . $stmt->error);
            return [];
        }
        
        $discussions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $discussions;
    }
    
    public function getReplies($parentId) {
        // Current table structure doesn't support replies (no parent_id column)
        // Return empty array to maintain compatibility
        return [];
    }
    
    public function getStudentDiscussions($studentId, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT d.*, c.title as course_title
            FROM discussions d
            JOIN courses c ON d.course_id = c.id
            WHERE d.student_id = ?
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $studentId, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getInstructorDiscussions($instructorId, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT d.*, c.title as course_title, u.full_name as student_name
            FROM discussions d
            JOIN courses c ON d.course_id = c.id
            JOIN users u ON d.student_id = u.id
            WHERE c.instructor_id = ?
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $instructorId, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function togglePin($id, $isPinned) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("UPDATE discussions SET is_pinned = ? WHERE id = ?");
        $stmt->bind_param("ii", $isPinned, $id);
        
        return $stmt->execute();
    }
    
    public function toggleResolve($id, $isResolved) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("UPDATE discussions SET is_resolved = ? WHERE id = ?");
        $stmt->bind_param("ii", $isResolved, $id);
        
        return $stmt->execute();
    }
    
    public function getDiscussionStats($courseId = null) {
        $conn = $this->db->getConnection();
        $stats = [];
        
        // Check if discussions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'discussions'");
        if ($tableCheck->num_rows == 0) {
            // Return default stats if table doesn't exist
            return [
                'total_discussions' => 0,
                'unresolved' => 0,
                'total_replies' => 0
            ];
        }
        
        if ($courseId) {
            // Total discussions for course
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM discussions WHERE course_id = ?");
            if ($stmt === false) {
                error_log("SQL prepare failed in getDiscussionStats (course total): " . $conn->error);
                $stats['total_discussions'] = 0;
            } else {
                $stmt->bind_param("i", $courseId);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats['total_discussions'] = $result ? $result->fetch_assoc()['total'] : 0;
            }
            
            // Locked discussions (equivalent to resolved)
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM discussions WHERE course_id = ? AND locked = 1");
            if ($stmt === false) {
                error_log("SQL prepare failed in getDiscussionStats (course locked): " . $conn->error);
                $stats['unresolved'] = 0;
            } else {
                $stmt->bind_param("i", $courseId);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats['unresolved'] = $result ? $result->fetch_assoc()['total'] : 0;
            }
        } else {
            // System-wide stats
            $result = $conn->query("SELECT COUNT(*) as total FROM discussions");
            $stats['total_discussions'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            $result = $conn->query("SELECT COUNT(*) as total FROM discussions WHERE locked = 1");
            $stats['unresolved'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            $stats['total_replies'] = 0; // Not supported in current table structure
        }
        
        return $stats;
    }
    
    public function searchDiscussions($courseId, $query, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT d.*, u.full_name, u.profile_image, c.title as course_title
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            JOIN courses c ON d.course_id = c.id
            WHERE d.course_id = ? 
            AND (d.title LIKE ? OR d.content LIKE ?)
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        // Validate inputs
        $courseId = intval($courseId);
        $limit = intval($limit);
        $offset = intval($offset);
        $query = trim($query);
        
        if ($courseId <= 0 || $limit <= 0 || $offset < 0 || empty($query)) {
            return [];
        }
        
        // Sanitize search query
        $searchTerm = "%" . $conn->real_escape_string($query) . "%";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("SQL prepare failed in searchDiscussions: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("issii", $courseId, $searchTerm, $searchTerm, $limit, $offset);
        if (!$stmt->execute()) {
            error_log("SQL execute failed in searchDiscussions: " . $stmt->error);
            return [];
        }
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getDiscussionReplies($discussionId) {
        // Current table structure doesn't support replies (no parent_id column)
        // Return empty array to maintain compatibility
        return [];
    }
    
    /**
     * Alias for getDiscussionsByCourse to maintain compatibility
     */
    public function getCourseDiscussions($courseId, $page = 1, $limit = 20) {
        return $this->getDiscussionsByCourse($courseId, $page, $limit);
    }
    
    /**
     * Get discussion count for a course
     */
    public function getCourseDiscussionCount($courseId) {
        $conn = $this->db->getConnection();
        
        // Check if discussions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'discussions'");
        if ($tableCheck->num_rows == 0) {
            return 0; // Table doesn't exist, return 0
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM discussions 
            WHERE course_id = ?
        ");
        
        if ($stmt === false) {
            error_log("SQL prepare failed in getCourseDiscussionCount: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['count'] ?? 0;
    }
}
?>
