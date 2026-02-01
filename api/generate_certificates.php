<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../models/Database.php';
require_once '../models/Progress.php';

$database = new Database();
$conn = $database->getConnection();
$progress = new Progress();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action, $progress);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function handleGetRequest($conn, $action) {
    switch ($action) {
        case 'verify':
            $certificateId = $_GET['id'] ?? null;
            if (!$certificateId) {
                http_response_code(400);
                echo json_encode(['error' => 'Certificate ID required']);
                return;
            }
            
            verifyCertificate($conn, $certificateId);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function handlePostRequest($conn, $action, $progress) {
    switch ($action) {
        case 'generate_missing':
            generateMissingCertificates($conn, $progress);
            break;
            
        case 'generate_eligible':
            generateEligibleCertificates($conn, $progress);
            break;
            
        case 'generate_single':
            $courseId = $_POST['course_id'] ?? null;
            $studentId = $_POST['student_id'] ?? null;
            if (!$courseId || !$studentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID and Student ID required']);
                return;
            }
            generateSingleCertificate($conn, $courseId, $studentId);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function verifyCertificate($conn, $certificateId) {
    $stmt = $conn->prepare("
        SELECT c.*, co.title as course_title, u.full_name as student_name, u.email as student_email,
               u.username, co.description as course_description, co.duration_hours,
               ins.full_name as instructor_name
        FROM certificates c
        JOIN courses_new co ON c.course_id = co.id
        JOIN users_new u ON c.student_id = u.id
        JOIN users_new ins ON co.instructor_id = ins.id
        WHERE c.certificate_id = ? AND c.status = 'issued'
    ");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $stmt->bind_param("s", $certificateId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $certificate = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Certificate verified successfully',
            'data' => [
                'certificate_id' => $certificate['certificate_id'],
                'course_title' => $certificate['course_title'],
                'course_description' => $certificate['course_description'],
                'student_name' => $certificate['student_name'],
                'student_email' => $certificate['student_email'],
                'student_username' => $certificate['username'],
                'instructor_name' => $certificate['instructor_name'],
                'issued_date' => date('F j, Y', strtotime($certificate['issued_date'])),
                'duration_hours' => $certificate['duration_hours'],
                'verification_date' => date('F j, Y'),
                'verification_time' => date('g:i A')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Certificate not found or invalid'
        ]);
    }
    
    $stmt->close();
}

function generateMissingCertificates($conn, $progress) {
    $generated = 0;
    $errors = [];
    
    // Get all students with 100% course completion but no certificate
    $stmt = $conn->prepare("
        SELECT DISTINCT e.student_id, e.course_id
        FROM enrollments e
        LEFT JOIN certificates c ON e.student_id = c.student_id AND e.course_id = c.course_id
        WHERE e.progress_percentage >= 100 
        AND c.id IS NULL
        AND e.status = 'completed'
    ");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        try {
            $certificateId = generateCertificate($conn, $row['student_id'], $row['course_id']);
            if ($certificateId) {
                $generated++;
            } else {
                $errors[] = "Failed to generate certificate for student {$row['student_id']}, course {$row['course_id']}";
            }
        } catch (Exception $e) {
            $errors[] = "Error for student {$row['student_id']}, course {$row['course_id']}: " . $e->getMessage();
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'errors' => $errors,
        'message' => "Generated {$generated} certificates successfully"
    ]);
}

function generateEligibleCertificates($conn, $progress) {
    $generated = 0;
    $errors = [];
    
    // Get current user's eligible courses
    $studentId = $_SESSION['user_id'] ?? 0;
    
    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        return;
    }
    
    $overallProgress = $progress->getStudentOverallProgress($studentId);
    
    foreach ($overallProgress as $course) {
        if ($course['progress_percentage'] >= 100) {
            // Check if certificate already exists
            $stmt = $conn->prepare("
                SELECT id FROM certificates 
                WHERE student_id = ? AND course_id = ? AND status = 'issued'
            ");
            $stmt->bind_param("ii", $studentId, $course['id']);
            $stmt->execute();
            $existing = $stmt->get_result()->num_rows;
            $stmt->close();
            
            if ($existing == 0) {
                try {
                    $certificateId = generateCertificate($conn, $studentId, $course['id']);
                    if ($certificateId) {
                        $generated++;
                    } else {
                        $errors[] = "Failed to generate certificate for course {$course['id']}";
                    }
                } catch (Exception $e) {
                    $errors[] = "Error for course {$course['id']}: " . $e->getMessage();
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'errors' => $errors,
        'message' => "Generated {$generated} certificates successfully"
    ]);
}

function generateSingleCertificate($conn, $courseId, $studentId) {
    try {
        $certificateId = generateCertificate($conn, $studentId, $courseId);
        
        if ($certificateId) {
            echo json_encode([
                'success' => true,
                'certificate_id' => $certificateId,
                'message' => 'Certificate generated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate certificate'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating certificate: ' . $e->getMessage()
        ]);
    }
}

function generateCertificate($conn, $studentId, $courseId) {
    // Get course and student information
    $stmt = $conn->prepare("
        SELECT co.title, co.description, u.full_name as student_name, u.email as student_email,
               ins.full_name as instructor_name, co.duration_hours
        FROM courses_new co
        JOIN users_new u ON u.id = ?
        JOIN users_new ins ON co.instructor_id = ins.id
        WHERE co.id = ?
    ");
    
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $courseInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$courseInfo) {
        return false;
    }
    
    // Generate unique certificate ID
    $certificateId = 'CERT_' . strtoupper(uniqid()) . '_' . date('Y');
    
    // Create certificate HTML
    $certificateHtml = generateCertificateHTML($certificateId, $courseInfo, $studentId, $courseId);
    
    // Save certificate file
    $fileName = $certificateId . '.html';
    $filePath = 'certificates/' . $fileName;
    $fullPath = dirname(__DIR__) . '/uploads/' . $filePath;
    
    // Create directory if it doesn't exist
    $certDir = dirname($fullPath);
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
    }
    
    if (file_put_contents($fullPath, $certificateHtml) === false) {
        return false;
    }
    
    // Insert certificate record
    $stmt = $conn->prepare("
        INSERT INTO certificates (student_id, course_id, certificate_id, file_path, issued_date, status)
        VALUES (?, ?, ?, ?, CURDATE(), 'issued')
    ");
    
    $stmt->bind_param("iiss", $studentId, $courseId, $certificateId, $filePath);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result ? $certificateId : false;
}

function generateCertificateHTML($certificateId, $courseInfo, $studentId, $courseId) {
    $currentDate = date('F j, Y');
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Certificate of Completion - {$courseInfo['title']}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .certificate {
            background: white;
            width: 800px;
            max-width: 100%;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            border: 8px solid #gold;
            background-image: 
                linear-gradient(45deg, transparent 40%, rgba(255, 215, 0, 0.1) 50%, transparent 60%),
                linear-gradient(-45deg, transparent 40%, rgba(255, 215, 0, 0.1) 50%, transparent 60%);
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #gold;
            border-radius: 12px;
            pointer-events: none;
        }
        
        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .certificate-title {
            font-size: 48px;
            color: #2c3e50;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .certificate-subtitle {
            font-size: 24px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .certificate-body {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .recipient-name {
            font-size: 36px;
            color: #2c3e50;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            border-bottom: 3px solid #gold;
            display: inline-block;
        }
        
        .certificate-text {
            font-size: 18px;
            color: #34495e;
            line-height: 1.6;
            margin: 30px 0;
        }
        
        .course-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
        }
        
        .signature-section {
            text-align: center;
        }
        
        .signature-line {
            border-top: 2px solid #34495e;
            width: 200px;
            margin: 10px 0;
        }
        
        .signature-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .certificate-seal {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 100px;
            height: 100px;
            background: gold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #2c3e50;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .certificate-id {
            position: absolute;
            bottom: 20px;
            left: 40px;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .verification-info {
            position: absolute;
            bottom: 20px;
            right: 40px;
            font-size: 10px;
            color: #7f8c8d;
            text-align: right;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .certificate {
                box-shadow: none;
                border: 8px solid #gold;
            }
        }
    </style>
</head>
<body>
    <div class=\"certificate\">
        <div class=\"certificate-seal\">🏆</div>
        
        <div class=\"certificate-header\">
            <div class=\"certificate-title\">Certificate of Completion</div>
            <div class=\"certificate-subtitle\">This is to certify that</div>
        </div>
        
        <div class=\"certificate-body\">
            <div class=\"recipient-name\">{$courseInfo['student_name']}</div>
            
            <div class=\"certificate-text\">
                has successfully completed the course
            </div>
            
            <div class=\"course-title\">{$courseInfo['title']}</div>
            
            <div class=\"certificate-text\">
                with a total duration of {$courseInfo['duration_hours']} hours.<br>
                This certificate acknowledges their dedication and achievement.
            </div>
        </div>
        
        <div class=\"certificate-footer\">
            <div class=\"signature-section\">
                <div class=\"signature-line\"></div>
                <div class=\"signature-label\">Instructor</div>
                <div style=\"margin-top: 5px; font-weight: bold;\">{$courseInfo['instructor_name']}</div>
            </div>
            
            <div class=\"signature-section\">
                <div class=\"signature-line\"></div>
                <div class=\"signature-label\">Date</div>
                <div style=\"margin-top: 5px; font-weight: bold;\">{$currentDate}</div>
            </div>
        </div>
        
        <div class=\"certificate-id\">Certificate ID: {$certificateId}</div>
        
        <div class=\"verification-info\">
            Verify online at:<br>
            {$baseUrl}/store/verify-certificate.php?id={$certificateId}
        </div>
    </div>
</body>
</html>";
}
?>
