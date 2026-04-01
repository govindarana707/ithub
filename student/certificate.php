<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../models/Course.php';

$course = new Course();
$userId = $_SESSION['user_id'];
$courseId = intval($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course ID';
    redirect('my-courses.php');
}

// Ensure certificates table exists (with all required columns)
$conn = connectDB();
$conn->query("CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(100) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    pdf_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_certificate_code (certificate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add certificate_code column if missing (for existing tables)
$conn->query("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS certificate_code VARCHAR(100) UNIQUE AFTER course_id");
$conn->query("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER certificate_code");
$conn->query("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS status ENUM('active', 'revoked', 'expired') DEFAULT 'active' AFTER issued_at");

// Ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->close();

// Get student information from database to ensure correct name
$studentName = $_SESSION['full_name'] ?? 'Student';
error_log("Certificate Debug: Session user_id = $userId, Session full_name = " . ($_SESSION['full_name'] ?? 'not set'));

if (empty($studentName) || $studentName === 'Student') {
    // Fallback: fetch from database
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT full_name FROM users_new WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result && !empty($result['full_name'])) {
            $studentName = $result['full_name'];
            $_SESSION['full_name'] = $studentName; // Update session
            error_log("Certificate Debug: Fetched student name from database: $studentName");
        }
        $stmt->close();
    }
    $conn->close();
}

// Enhanced security: Double-check certificate belongs to logged-in user
$conn = connectDB();
$securityCheck = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE student_id = ? AND course_id = ?");
if ($securityCheck) {
    $securityCheck->bind_param("ii", $userId, $courseId);
    $securityCheck->execute();
    $certCount = $securityCheck->get_result()->fetch_assoc()['count'];
    $securityCheck->close();
    
    // If certificate exists but doesn't belong to user, deny access
    if ($certCount == 0) {
        // Check if any certificate exists for this course (to determine if we should show "not completed" vs "access denied")
        $anyCertCheck = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE course_id = ?");
        if ($anyCertCheck) {
            $anyCertCheck->bind_param("i", $courseId);
            $anyCertCheck->execute();
            $anyCerts = $anyCertCheck->get_result()->fetch_assoc()['count'];
            $anyCertCheck->close();
            
            if ($anyCerts > 0) {
                // Certificates exist for this course, but not for this user - access denied
                $_SESSION['error_message'] = 'Access denied. This certificate does not belong to you.';
                error_log("Certificate Security Alert: User $userId attempted to access certificate for course $courseId that belongs to another user");
                $conn->close();
                redirect('my-courses.php');
            }
        }
    }
}
$conn->close();

// Check if student is enrolled and has completed the course
$enrollment = $course->getEnrollment($userId, $courseId);
if (!$enrollment || $enrollment['progress_percentage'] < 100) {
    $_SESSION['error_message'] = 'You must complete the course to receive a certificate';
    redirect('lesson.php?course_id=' . $courseId);
}

// Get course details
$courseData = $course->getCourseById($courseId);
if (!$courseData) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('my-courses.php');
}

// Check if certificate already exists
$conn = connectDB();
$certificateId = null; // Initialize variable
$stmt = $conn->prepare("SELECT id, certificate_code, issued_at FROM certificates WHERE student_id = ? AND course_id = ?");
if ($stmt === false) {
    error_log("Certificate check prepare failed: " . $conn->error);
    $existingCertificate = null;
} else {
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    $existingCertificate = $stmt->get_result()->fetch_assoc();
    if ($existingCertificate) {
        $certificateId = $existingCertificate['id'];
    }
}

// Generate certificate if it doesn't exist
if (!$existingCertificate) {
    $certificateCode = 'ITHUB-' . strtoupper(uniqid()) . '-' . date('Y');
    
    $stmt = $conn->prepare("INSERT INTO certificates (student_id, course_id, certificate_code, issued_at) VALUES (?, ?, ?, NOW())");
    if ($stmt === false) {
        error_log("Certificate insert prepare failed: " . $conn->error);
        $_SESSION['error_message'] = 'Failed to generate certificate. Database error: ' . $conn->error;
        redirect('my-courses.php');
    }
    $stmt->bind_param("iis", $userId, $courseId, $certificateCode);
    
    if ($stmt->execute()) {
        $certificateId = $conn->insert_id;
        
        // Log activity
        logActivity($userId, 'certificate_issued', "Certificate issued for course: {$courseData['title']}");
        
        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, 'success')");
        if ($stmt !== false) {
            $title = "🎉 Certificate Earned!";
            $message = "Congratulations! You've earned a certificate for completing '{$courseData['title']}'.";
            $stmt->bind_param("iss", $userId, $title, $message);
            $stmt->execute();
        }
        
        $existingCertificate = [
            'id' => $certificateId,
            'certificate_code' => $certificateCode,
            'issued_at' => date('Y-m-d H:i:s')
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($courseData['title']); ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap');
        
        .certificate-container {
            background: linear-gradient(135deg, #4169E1 0%, #2563EB 100%);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .certificate {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23000" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="%23000" opacity="0.03"/><circle cx="50" cy="10" r="0.5" fill="%23000" opacity="0.02"/><circle cx="20" cy="60" r="0.5" fill="%23000" opacity="0.02"/><circle cx="80" cy="40" r="0.5" fill="%23000" opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .certificate-border {
            border: 3px solid #gold;
            border-image: linear-gradient(45deg, #ffd700, #ffed4e, #ffd700) 1;
            padding: 2rem;
            border-radius: 10px;
        }
        
        .certificate-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .certificate-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .student-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2c3e50;
            text-align: center;
            margin: 2rem 0;
            font-weight: 700;
        }
        
        .certificate-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            color: #34495e;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .course-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            color: #2c3e50;
            text-align: center;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .certificate-details {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #ecf0f1;
        }
        
        .certificate-signature {
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 2px solid #34495e;
            width: 200px;
            margin-bottom: 0.5rem;
        }
        
        .certificate-date {
            font-family: 'Montserrat', sans-serif;
            color: #7f8c8d;
            text-align: center;
            margin-top: 1rem;
        }
        
        .certificate-seal {
            position: absolute;
            bottom: 2rem;
            right: 2rem;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        .certificate-seal i {
            font-size: 3rem;
            color: #2c3e50;
        }
        
        /* Enhanced Print Styles */
        @media print {
            /* Hide non-printable elements */
            .no-print {
                display: none !important;
            }
            
            /* Remove universal header and navigation */
            .universal-header,
            header,
            nav,
            .container-fluid.py-4 > div:first-child,
            .card.mt-4 {
                display: none !important;
            }
            
            /* Full page certificate */
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .container-fluid {
                padding: 0 !important;
                max-width: none !important;
                width: 100% !important;
            }
            
            .certificate-container {
                background: white !important;
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                min-height: 100vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .certificate {
                box-shadow: none !important;
                border: 2px solid #4169E1 !important;
                page-break-inside: avoid;
                transform: scale(0.9);
                transform-origin: center;
            }
            
            /* Optimize text for print */
            .certificate-title {
                font-size: 28px !important;
                color: #4169E1 !important;
            }
            
            .student-name {
                font-size: 32px !important;
                color: #2c3e50 !important;
                font-weight: bold !important;
            }
            
            .course-title {
                font-size: 24px !important;
                color: #4169E1 !important;
            }
            
            .certificate-text {
                color: #2c3e50 !important;
            }
            
            /* Ensure proper spacing */
            .certificate-details {
                margin-top: 40px !important;
            }
            
            .certificate-signature {
                margin: 20px 0 !important;
            }
            
            .signature-line {
                border-bottom: 2px solid #2c3e50 !important;
                width: 200px !important;
                margin: 0 auto 10px !important;
            }
            
            .certificate-date {
                margin-top: 30px !important;
                color: #2c3e50 !important;
            }
            
            .certificate-seal {
                color: #4169E1 !important;
                font-size: 48px !important;
            }
            
            /* Page break handling */
            .certificate {
                page-break-after: always;
            }
            
            /* Print optimization */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        
        /* Print preview styles */
        @media screen {
            .print-preview {
                background: #f5f5f5;
                border: 1px dashed #ccc;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            }
            
            .print-preview .certificate {
                transform: scale(0.7);
                transform-origin: top center;
            }
        }
        
        /* Print background styles */
        .print-background .certificate {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        }
        
        @media print {
            .print-background .certificate {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            }
            
            .no-border .certificate {
                border: none !important;
            }
            
            .no-seal .certificate-seal {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/universal_header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Action Buttons -->
        <div class="text-center mb-4 no-print">
            <button onclick="printCertificate()" class="btn btn-primary me-2">
                <i class="fas fa-print me-1"></i>Print Certificate
            </button>
            <button onclick="printWithBackground()" class="btn btn-outline-primary me-2">
                <i class="fas fa-image me-1"></i>Print with Background
            </button>
            <button onclick="downloadPDF()" class="btn btn-success me-2">
                <i class="fas fa-file-pdf me-1"></i>Download PDF
            </button>
            <button onclick="shareCertificate()" class="btn btn-info me-2">
                <i class="fas fa-share-alt me-1"></i>Share
            </button>
            <div class="mt-3">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-cog me-1"></i>Print Settings
                </button>
                <a href="my-courses.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Courses
                </a>
            </div>
        </div>

        <!-- Certificate -->
        <div class="certificate-container">
            <div class="certificate">
                <div class="certificate-border">
                    <div class="text-center mb-4">
                        <i class="fas fa-graduation-cap fa-4x text-primary"></i>
                    </div>
                    
                    <h1 class="certificate-title">Certificate of Completion</h1>
                    <p class="certificate-subtitle">This is to certify that</p>
                    
                    <h2 class="student-name"><?php echo htmlspecialchars($studentName); ?></h2>
                    
                    <p class="certificate-text">
                        has successfully completed the course
                    </p>
                    
                    <h3 class="course-title"><?php echo htmlspecialchars($courseData['title']); ?></h3>
                    
                    <p class="certificate-text">
                        with a comprehensive understanding of the subject matter and demonstrated proficiency in all course requirements.
                    </p>
                    
                    <div class="certificate-details">
                        <div class="certificate-signature">
                            <div class="signature-line"></div>
                            <p class="mb-0"><strong><?php echo htmlspecialchars($courseData['instructor_name']); ?></strong></p>
                            <small class="text-muted">Course Instructor</small>
                        </div>
                        
                        <div class="certificate-signature">
                            <div class="signature-line"></div>
                            <p class="mb-0"><strong>IT HUB Administration</strong></p>
                            <small class="text-muted">Director</small>
                        </div>
                    </div>
                    
                    <div class="certificate-date">
                        <strong>Issued on:</strong> 
                        <?php 
                        if (!empty($existingCertificate['issued_at'])) {
                            echo date('F j, Y', strtotime($existingCertificate['issued_at']));
                        } else {
                            echo date('F j, Y');
                        }
                        ?>
                    </div>
                    
                    <div class="certificate-seal">
                        <i class="fas fa-award"></i>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Certificate Code: <?php echo htmlspecialchars($existingCertificate['certificate_code'] ?? $certificateId ?? 'N/A'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Section -->
        <div class="card mt-4 no-print">
            <div class="card-body">
                <h5><i class="fas fa-check-circle me-2"></i>Certificate Verification</h5>
                <p class="text-muted">This certificate can be verified using the certificate code above. Share this code with employers or educational institutions to verify your achievement.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($existingCertificate['certificate_code'] ?? $certificateId); ?>" readonly>
                            <button class="btn btn-outline-primary" onclick="copyCertificateCode()">
                                <i class="fas fa-copy me-1"></i>Copy
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo BASE_URL; ?>verify-certificate.php?code=<?php echo htmlspecialchars($existingCertificate['certificate_code'] ?? $certificateId); ?>" readonly>
                            <button class="btn btn-outline-primary" onclick="copyVerificationLink()">
                                <i class="fas fa-link me-1"></i>Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadCertificate() {
            const element = document.querySelector('.certificate-container');
            const opt = {
                margin: 10,
                filename: 'certificate-<?php echo $courseId; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
        
        function shareCertificate() {
            if (navigator.share) {
                navigator.share({
                    title: 'Certificate of Completion',
                    text: 'I earned a certificate for completing <?php echo htmlspecialchars($courseData["title"]); ?> at IT HUB!',
                    url: window.location.href
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                copyVerificationLink();
                alert('Verification link copied to clipboard! You can share it with others.');
            }
        }
        
        function copyCertificateCode() {
            const code = '<?php echo htmlspecialchars($existingCertificate["certificate_code"]); ?>';
            navigator.clipboard.writeText(code).then(() => {
                showAlert('Certificate code copied to clipboard!', 'success');
            });
        }
        
        function copyVerificationLink() {
            const link = '<?php echo BASE_URL; ?>verify-certificate.php?code=<?php echo htmlspecialchars($existingCertificate["certificate_code"]); ?>';
            navigator.clipboard.writeText(link).then(() => {
                showAlert('Verification link copied to clipboard!', 'success');
            });
        }
        
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('body').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 3000);
        }
        
        // Enhanced Print Functions
        function printCertificate() {
            // Remove print background class temporarily
            document.body.classList.remove('print-background');
            window.print();
        }
        
        function printWithBackground() {
            // Add print background class
            document.body.classList.add('print-background');
            window.print();
            // Remove class after print dialog closes
            setTimeout(() => {
                document.body.classList.remove('print-background');
            }, 1000);
        }
        
        function downloadPDF() {
            // Generate PDF using existing functionality
            const certificateId = '<?php echo htmlspecialchars($existingCertificate["certificate_code"] ?? $certificateId ?? "N/A"); ?>';
            window.open('../generate_real_pdf.php?id=' + certificateId, '_blank');
        }
        
        function shareCertificate() {
            const certificateId = '<?php echo htmlspecialchars($existingCertificate["certificate_code"] ?? $certificateId ?? "N/A"); ?>';
            const studentName = '<?php echo htmlspecialchars($studentName); ?>';
            const courseTitle = '<?php echo htmlspecialchars($courseData["title"]); ?>';
            const verificationUrl = '<?php echo BASE_URL; ?>verify-certificate.php?code=' + certificateId;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Certificate of Completion - ' + courseTitle,
                    text: `I have successfully completed the ${courseTitle} course at IT HUB!`,
                    url: verificationUrl
                }).then(() => {
                    showAlert('Certificate shared successfully!', 'success');
                }).catch((error) => {
                    console.log('Share failed:', error);
                    copyToClipboard(verificationUrl);
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                copyToClipboard(verificationUrl);
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('Certificate link copied to clipboard!', 'success');
            }).catch((error) => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showAlert('Certificate link copied to clipboard!', 'success');
            });
        }
        
        // Print settings dialog
        function showPrintSettings() {
            const settingsHtml = `
                <div class="modal fade" id="printSettingsModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Print Settings</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="printBackground" checked>
                                    <label class="form-check-label" for="printBackground">
                                        Print with background design
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="printBorder" checked>
                                    <label class="form-check-label" for="printBorder">
                                        Print with decorative border
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="printSeal" checked>
                                    <label class="form-check-label" for="printSeal">
                                        Print with official seal
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label for="printSize" class="form-label">Print Size</label>
                                    <select class="form-select" id="printSize">
                                        <option value="A4">A4 (Standard)</option>
                                        <option value="A3">A3 (Large)</option>
                                        <option value="Letter">Letter (US)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="applyPrintSettings()">Apply Settings</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(settingsHtml);
            $('#printSettingsModal').modal('show');
            
            // Remove modal from DOM after hidden
            $('#printSettingsModal').on('hidden.bs.modal', function () {
                $(this).remove();
            });
        }
        
        function applyPrintSettings() {
            const printBackground = document.getElementById('printBackground').checked;
            const printBorder = document.getElementById('printBorder').checked;
            const printSeal = document.getElementById('printSeal').checked;
            
            // Apply settings to print styles
            if (printBackground) {
                document.body.classList.add('print-background');
            } else {
                document.body.classList.remove('print-background');
            }
            
            if (!printBorder) {
                document.body.classList.add('no-border');
            } else {
                document.body.classList.remove('no-border');
            }
            
            if (!printSeal) {
                document.body.classList.add('no-seal');
            } else {
                document.body.classList.remove('no-seal');
            }
            
            $('#printSettingsModal').modal('hide');
            window.print();
            
            // Reset classes after printing
            setTimeout(() => {
                document.body.classList.remove('print-background', 'no-border', 'no-seal');
            }, 1000);
        }
        
        // Add print styles for settings
        const printStyles = document.createElement('style');
        printStyles.textContent = `
            @media print {
                .print-background .certificate {
                    background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%) !important;
                }
                
                .no-border .certificate {
                    border: none !important;
                }
                
                .no-seal .certificate-seal {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(printStyles);
    </script>
</body>
</html>
