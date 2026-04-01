<?php
session_start();
require_once 'config/config.php';
require_once 'includes/session_helper.php';
require_once 'includes/certificate_pdf_generator.php';

// Critical security check - ensure user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized access - Please login first');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'student';

// Handle download parameter
if (isset($_GET['download']) && $_GET['download'] == '1') {
    // Get certificate ID from URL
    $certificateId = $_GET['id'] ?? '';
    if (empty($certificateId)) {
        die('Certificate ID required');
    }
    
    // Get certificate details from database with ownership verification
    $conn = connectDB();
    
    // Check connection
    if (!$conn) {
        die('Database connection failed');
    }
    
    // SECURE QUERY: Verify ownership and fetch certificate data
    $stmt = $conn->prepare("
        SELECT c.*, u.full_name, u.email, co.title as course_title
        FROM certificates c
        JOIN users_new u ON c.student_id = u.id
        JOIN courses_new co ON c.course_id = co.id
        WHERE c.certificate_id = ? AND c.status = 'issued' AND c.student_id = ?
    ");
    
    if (!$stmt) {
        error_log('Certificate query preparation failed: ' . $conn->error);
        die('Query preparation failed');
    }
    
    $stmt->bind_param("si", $certificateId, $userId);
    if (!$stmt->execute()) {
        error_log('Certificate query execution failed: ' . $stmt->error);
        die('Query execution failed');
    }
    
    $certificate = $stmt->get_result()->fetch_assoc();
    
    if (!$certificate) {
        error_log("Certificate not found or access denied for certificate_id: $certificateId, user_id: $userId");
        die('Certificate not found or access denied');
    }
    
    // Additional security: Double-check ownership
    if ($certificate['student_id'] != $userId && $userRole !== 'admin') {
        error_log("SECURITY BREACH: User $userId attempted to access certificate belonging to {$certificate['student_id']}");
        die('Access denied - This certificate does not belong to you');
    }
    
    // Generate PDF using our custom generator
    try {
        $pdfGenerator = new CertificatePDFGenerator($certificate);
        $filename = 'Certificate_' . $certificateId . '.pdf';
        
        if (isset($_GET['print']) && $_GET['print'] == '1') {
            // For print view, set inline display
            $pdfGenerator->view($filename);
        } else {
            // For download, set attachment
            $pdfGenerator->download($filename);
        }
        
    } catch (Exception $e) {
        error_log('PDF generation failed: ' . $e->getMessage());
        die('PDF generation failed. Please contact support.');
    }
    
    $conn->close();
    exit;
}

// If no download parameter, show preview page
$certificateId = $_GET['id'] ?? '';
if (empty($certificateId)) {
    die('Certificate ID required');
}

// Get certificate details from database with ownership verification
$conn = connectDB();

// Check connection
if (!$conn) {
    die('Database connection failed');
}

// SECURE QUERY: Verify ownership and fetch certificate data
$stmt = $conn->prepare("
    SELECT c.*, u.full_name, u.email, co.title as course_title
    FROM certificates c
    JOIN users_new u ON c.student_id = u.id
    JOIN courses_new co ON c.course_id = co.id
    WHERE c.certificate_id = ? AND c.status = 'issued' AND c.student_id = ?
");

if (!$stmt) {
    error_log('Certificate query preparation failed: ' . $conn->error);
    die('Query preparation failed');
}

$stmt->bind_param("si", $certificateId, $userId);
if (!$stmt->execute()) {
    error_log('Certificate query execution failed: ' . $stmt->error);
    die('Query execution failed');
}

$certificate = $stmt->get_result()->fetch_assoc();

if (!$certificate) {
    error_log("Certificate not found or access denied for certificate_id: $certificateId, user_id: $userId");
    die('Certificate not found or access denied');
}

// Additional security: Double-check ownership
if ($certificate['student_id'] != $userId && $userRole !== 'admin') {
    error_log("SECURITY BREACH: User $userId attempted to access certificate belonging to {$certificate['student_id']}");
    die('Access denied - This certificate does not belong to you');
}

// Generate preview using our custom generator
try {
    $pdfGenerator = new CertificatePDFGenerator($certificate);
    $pdfBase64 = $pdfGenerator->getBase64();
    
} catch (Exception $e) {
    error_log('PDF preview generation failed: ' . $e->getMessage());
    $pdfBase64 = '';
}
echo "<!DOCTYPE html>
<html>
<head>
    <title>Certificate - " . htmlspecialchars($certificate['certificate_id']) . "</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .preview { border: 2px solid #667eea; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fff; }
        .actions { text-align: center; margin-top: 20px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn:hover { background: #5a67d8; }
        .embed-container { border: 1px solid #ddd; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏆 Certificate of Completion</h1>
            <p>Certificate ID: " . htmlspecialchars($certificate['certificate_id']) . "</p>
            <p>Student: " . htmlspecialchars($certificate['full_name']) . "</p>
            <p>Course: " . htmlspecialchars($certificate['course_title']) . "</p>
            <p>Issued: " . htmlspecialchars(date('F j, Y', strtotime($certificate['issued_date']))) . "</p>
        </div>
        
        <div class='preview'>
            <h3>Certificate Preview</h3>
            <embed src='data:text/html;base64,$pdfBase64' width='100%' height='600px' style='border: 1px solid #ddd;'>
        </div>
        
        <div class='actions'>
            <h3>Download Options</h3>
            <a href='generate_real_pdf.php?id=" . htmlspecialchars($certificate['certificate_id']) . "&download=1' class='btn'>📥 Download PDF</a>
            <a href='generate_real_pdf.php?id=" . htmlspecialchars($certificate['certificate_id']) . "&download=1&print=1' class='btn'>🖨️ Download & Print</a>
        </div>
    </div>
</body>
</html>";

$conn->close();
?>
