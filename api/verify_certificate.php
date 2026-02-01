<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../models/Database.php';

$database = new Database();
$conn = $database->getConnection();

$certificateId = $_GET['id'] ?? null;

if (!$certificateId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Certificate ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.*, co.title as course_title, co.description as course_description, 
               co.duration_hours, co.difficulty_level,
               u.full_name as student_name, u.email as student_email, u.username,
               ins.full_name as instructor_name, ins.email as instructor_email
        FROM certificates c
        JOIN courses_new co ON c.course_id = co.id
        JOIN users_new u ON c.student_id = u.id
        JOIN users_new ins ON co.instructor_id = ins.id
        WHERE c.certificate_id = ? AND c.status = 'issued'
    ");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("s", $certificateId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $certificate = $result->fetch_assoc();
        
        // Additional verification checks
        $isValid = true;
        $warnings = [];
        
        // Check if certificate is not revoked
        if ($certificate['status'] !== 'issued') {
            $isValid = false;
            $warnings[] = 'Certificate status is not active';
        }
        
        // Check if course completion is valid
        $completionCheck = $conn->prepare("
            SELECT progress_percentage, status 
            FROM enrollments 
            WHERE student_id = ? AND course_id = ?
        ");
        $completionCheck->bind_param("ii", $certificate['student_id'], $certificate['course_id']);
        $completionCheck->execute();
        $completionData = $completionCheck->get_result()->fetch_assoc();
        
        if (!$completionData || $completionData['progress_percentage'] < 100) {
            $isValid = false;
            $warnings[] = 'Course completion requirements not met';
        }
        
        echo json_encode([
            'success' => true,
            'is_valid' => $isValid,
            'warnings' => $warnings,
            'message' => $isValid ? 'Certificate is authentic and valid' : 'Certificate validation failed',
            'data' => [
                'certificate_id' => $certificate['certificate_id'],
                'course_title' => $certificate['course_title'],
                'course_description' => $certificate['course_description'],
                'course_duration' => $certificate['duration_hours'],
                'difficulty_level' => $certificate['difficulty_level'],
                'student_name' => $certificate['student_name'],
                'student_email' => $certificate['student_email'],
                'student_username' => $certificate['username'],
                'instructor_name' => $certificate['instructor_name'],
                'instructor_email' => $certificate['instructor_email'],
                'issued_date' => date('F j, Y', strtotime($certificate['issued_date'])),
                'completion_percentage' => $completionData['progress_percentage'] ?? 0,
                'enrollment_status' => $completionData['status'] ?? 'unknown',
                'verification_date' => date('F j, Y'),
                'verification_time' => date('g:i A'),
                'verification_timestamp' => time()
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Certificate not found or invalid',
            'error_code' => 'CERT_NOT_FOUND'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Verification service temporarily unavailable',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
