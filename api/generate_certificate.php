<?php
require_once '../config/config.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$studentId = $_SESSION['user_id'];
$courseId = intval($_POST['course_id']);

if (!$courseId) {
    sendJSON(['success' => false, 'message' => 'Invalid course ID']);
}

$course = new Course();

// Verify student is enrolled and has completed the course
$enrollment = $course->getEnrollment($studentId, $courseId);
if (!$enrollment) {
    sendJSON(['success' => false, 'message' => 'You are not enrolled in this course']);
}

if ($enrollment['progress_percentage'] < 100) {
    sendJSON(['success' => false, 'message' => 'You must complete the course before generating a certificate']);
}

// Check if certificate already exists
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, certificate_url FROM certificates WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $studentId, $courseId);
$stmt->execute();
$existingCertificate = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingCertificate) {
    sendJSON([
        'success' => true, 
        'message' => 'Certificate already generated',
        'certificate_url' => BASE_URL . $existingCertificate['certificate_url']
    ]);
}

// Generate new certificate
$certificateCode = 'CERT_' . strtoupper(uniqid()) . '_' . date('Y');
$certificateUrl = 'certificates/' . $certificateCode . '.pdf';

$stmt = $conn->prepare("INSERT INTO certificates (student_id, course_id, certificate_code, certificate_url, issued_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiss", $studentId, $courseId, $certificateCode, $certificateUrl);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    // Create the certificate PDF (simplified version)
    createCertificatePDF($studentId, $courseId, $certificateCode);
    
    // Log activity
    logActivity($studentId, 'certificate_generated', "Generated certificate for course ID: $courseId");
    
    sendJSON([
        'success' => true, 
        'message' => 'Certificate generated successfully',
        'certificate_url' => BASE_URL . $certificateUrl
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendJSON(['success' => false, 'message' => 'Failed to generate certificate']);
}

function createCertificatePDF($studentId, $courseId, $certificateCode) {
    // This is a simplified version - in production, you'd use a proper PDF library
    $studentName = $_SESSION['full_name'];
    
    // Get course details
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    $courseTitle = $course['title'];
    $date = date('F j, Y');
    
    // Create a simple HTML certificate (in production, convert to PDF)
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin: 50px; }
            .certificate { border: 5px solid #007bff; padding: 50px; max-width: 800px; margin: 0 auto; }
            .title { font-size: 48px; color: #007bff; margin-bottom: 30px; }
            .name { font-size: 36px; margin: 20px 0; font-weight: bold; }
            .course { font-size: 24px; margin: 20px 0; }
            .date { font-size: 18px; margin-top: 40px; }
            .signature { margin-top: 60px; }
        </style>
    </head>
    <body>
        <div class='certificate'>
            <div class='title'>Certificate of Completion</div>
            <div>This is to certify that</div>
            <div class='name'>$studentName</div>
            <div>has successfully completed the course</div>
            <div class='course'>$courseTitle</div>
            <div class='date'>Issued on $date</div>
            <div class='signature'>
                <div>_________________________</div>
                <div>Authorized Signature</div>
            </div>
        </div>
    </body>
    </html>";
    
    // Save certificate (in production, convert to PDF)
    $certDir = '../uploads/certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0777, true);
    }
    
    file_put_contents("$certDir/$certificateCode.html", $html);
    
    // For demo purposes, we'll save as HTML. In production, use TCPDF or similar library
    $pdfUrl = "uploads/certificates/$certificateId.html";
    
    // Update database with actual file path
    $conn = connectDB();
    $stmt = $conn->prepare("UPDATE certificates SET certificate_url = ? WHERE certificate_code = ?");
    $stmt->bind_param("ss", $pdfUrl, $certificateCode);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
