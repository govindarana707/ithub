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
$stmt = $conn->prepare("SELECT id, certificate_code, issued_at FROM certificates WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $userId, $courseId);
$stmt->execute();
$existingCertificate = $stmt->get_result()->fetch_assoc();

// Generate certificate if it doesn't exist
if (!$existingCertificate) {
    $certificateCode = 'ITHUB-' . strtoupper(uniqid()) . '-' . date('Y');
    
    $stmt = $conn->prepare("INSERT INTO certificates (student_id, course_id, certificate_code, issued_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $userId, $courseId, $certificateCode);
    
    if ($stmt->execute()) {
        $certificateId = $conn->insert_id;
        
        // Log activity
        logActivity($userId, 'certificate_issued', "Certificate issued for course: {$courseData['title']}");
        
        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, 'success')");
        $title = "🎉 Certificate Earned!";
        $message = "Congratulations! You've earned a certificate for completing '{$courseData['title']}'.";
        $stmt->bind_param("iss", $userId, $title, $message);
        $stmt->execute();
        
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
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap');
        
        .certificate-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .certificate-container {
                background: white;
                padding: 0;
                box-shadow: none;
            }
            
            .certificate {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="my-courses.php"><i class="fas fa-graduation-cap me-2"></i>My Courses</a></li>
                        <li><a class="dropdown-item" href="certificates.php"><i class="fas fa-certificate me-2"></i>My Certificates</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Action Buttons -->
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print me-1"></i>Print Certificate
            </button>
            <button onclick="downloadCertificate()" class="btn btn-success me-2">
                <i class="fas fa-download me-1"></i>Download PDF
            </button>
            <button onclick="shareCertificate()" class="btn btn-info me-2">
                <i class="fas fa-share-alt me-1"></i>Share
            </button>
            <a href="my-courses.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Courses
            </a>
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
                    
                    <h2 class="student-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                    
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
                        <strong>Issued on:</strong> <?php echo date('F j, Y', strtotime($existingCertificate['issued_at'])); ?>
                    </div>
                    
                    <div class="certificate-seal">
                        <i class="fas fa-award"></i>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Certificate Code: <?php echo htmlspecialchars($existingCertificate['certificate_code']); ?>
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
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($existingCertificate['certificate_code']); ?>" readonly>
                            <button class="btn btn-outline-primary" onclick="copyCertificateCode()">
                                <i class="fas fa-copy me-1"></i>Copy
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo BASE_URL; ?>verify-certificate.php?code=<?php echo htmlspecialchars($existingCertificate['certificate_code']); ?>" readonly>
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
    </script>
</body>
</html>
