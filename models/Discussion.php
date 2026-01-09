<?php
require_once 'Database.php';

class Discussion {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function createDiscussion($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO discussions (course_id, student_id, title, content, parent_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $data['course_id'], $data['student_id'], $data['title'], $data['content'], $data['parent_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'discussion_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
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
        
        $stmt = $conn->prepare("DELETE FROM discussions WHERE id = ? OR parent_id = ?");
        $stmt->bind_param("ii", $id, $id);
        
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
        
        // Get main discussions (no parent) with error handling
        $sql = "
            SELECT d.*, u.full_name, u.profile_image, c.title as course_title,
                   (SELECT COUNT(*) FROM discussions WHERE parent_id = d.id) as reply_count
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            LEFT JOIN courses c ON d.course_id = c.id
            WHERE d.course_id = ? AND d.parent_id IS NULL
            ORDER BY d.is_pinned DESC, d.created_at DESC
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
        
        // Get replies for each discussion
        foreach ($discussions as &$discussion) {
            $discussion['replies'] = $this->getReplies($discussion['id']);
        }
        
        return $discussions;
    }
    
    public function getReplies($parentId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT d.*, u.full_name, u.profile_image
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            WHERE d.parent_id = ?
            ORDER BY d.created_at ASC
        ");
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStudentDiscussions($studentId, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT d.*, c.title as course_title,
                   (SELECT COUNT(*) FROM discussions WHERE parent_id = d.id) as reply_count
            FROM discussions d
            JOIN courses c ON d.course_id = c.id
            WHERE d.student_id = ? AND d.parent_id IS NULL
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
            SELECT d.*, c.title as course_title, u.full_name as student_name,
                   (SELECT COUNT(*) FROM discussions WHERE parent_id = d.id) as reply_count
            FROM discussions d
            JOIN courses c ON d.course_id = c.id
            JOIN users u ON d.student_id = u.id
            WHERE c.instructor_id = ? AND d.parent_id IS NULL
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
        
        if ($courseId) {
            // Total discussions for course
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM discussions WHERE course_id = ? AND parent_id IS NULL");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $stats['total_discussions'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Unresolved discussions
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM discussions WHERE course_id = ? AND parent_id IS NULL AND is_resolved = FALSE");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $stats['unresolved'] = $stmt->get_result()->fetch_assoc()['total'];
        } else {
            // System-wide stats
            $result = $conn->query("SELECT COUNT(*) as total FROM discussions WHERE parent_id IS NULL");
            $stats['total_discussions'] = $result->fetch_assoc()['total'];
            
            $result = $conn->query("SELECT COUNT(*) as total FROM discussions WHERE parent_id IS NULL AND is_resolved = FALSE");
            $stats['unresolved'] = $result->fetch_assoc()['total'];
            
            $result = $conn->query("SELECT COUNT(*) as total FROM discussions WHERE parent_id IS NOT NULL");
            $stats['total_replies'] = $result->fetch_assoc()['total'];
        }
        
        return $stats;
    }
    
    public function searchDiscussions($courseId, $query, $page = 1, $limit = 20) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT d.*, u.full_name, u.profile_image, c.title as course_title,
                   (SELECT COUNT(*) FROM discussions WHERE parent_id = d.id) as reply_count
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            JOIN courses c ON d.course_id = c.id
            WHERE d.course_id = ? AND d.parent_id IS NULL 
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
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT d.*, u.full_name, u.profile_image
            FROM discussions d
            JOIN users u ON d.student_id = u.id
            WHERE d.parent_id = ?
            ORDER BY d.created_at ASC
        ");
        $stmt->bind_param("i", $discussionId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
