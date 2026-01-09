<?php
require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function register($data) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $data['username'], $data['email'], $data['password'], $data['full_name'], $data['role'], $data['phone']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }
    
    public function login($email, $password) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'blocked') {
                return ['success' => false, 'error' => 'Account is blocked'];
            }
            
            if (password_verify($password, $user['password'])) {
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'error' => 'Invalid password'];
            }
        } else {
            return ['success' => false, 'error' => 'User not found'];
        }
    }
    
    public function getUserById($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, role, profile_image, bio, phone, status, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getAllUsers($role = null, $status = null, $limit = 50, $offset = 0) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, username, email, full_name, role, profile_image, phone, status, created_at FROM users WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
            $types .= "s";
        }
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function updateUser($id, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE users SET ";
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
    
    public function deleteUser($id) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    public function updatePassword($id, $newPassword) {
        $conn = $this->db->getConnection();
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        
        return $stmt->execute();
    }
    
    public function emailExists($email) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    public function usernameExists($username) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    public function getInstructors() {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_image FROM users WHERE role = 'instructor' AND status = 'active' ORDER BY full_name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUserByEmail($email) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getStudents($courseId = null) {
        $conn = $this->db->getConnection();
        
        if ($courseId) {
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.profile_image, e.enrolled_at, e.progress_percentage
                FROM users u
                JOIN enrollments e ON u.id = e.student_id
                WHERE e.course_id = ? AND u.role = 'student'
                ORDER BY e.enrolled_at DESC
            ");
            $stmt->bind_param("i", $courseId);
        } else {
            $stmt = $conn->prepare("
                SELECT id, username, email, full_name, profile_image, created_at
                FROM users
                WHERE role = 'student'
                ORDER BY created_at DESC
            ");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUserStats() {
        $conn = $this->db->getConnection();
        
        $stats = [];
        
        // Total users
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // Students
        $result = $conn->query("SELECT COUNT(*) as students FROM users WHERE role = 'student'");
        $stats['students'] = $result->fetch_assoc()['students'];
        
        // Instructors
        $result = $conn->query("SELECT COUNT(*) as instructors FROM users WHERE role = 'instructor'");
        $stats['instructors'] = $result->fetch_assoc()['instructors'];
        
        // Admins
        $result = $conn->query("SELECT COUNT(*) as admins FROM users WHERE role = 'admin'");
        $stats['admins'] = $result->fetch_assoc()['admins'];
        
        // Active users
        $result = $conn->query("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
        $stats['active'] = $result->fetch_assoc()['active'];
        
        // Blocked users
        $result = $conn->query("SELECT COUNT(*) as blocked FROM users WHERE status = 'blocked'");
        $stats['blocked'] = $result->fetch_assoc()['blocked'];
        
        return $stats;
    }
}
?>
