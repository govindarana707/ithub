<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Progress.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Allow students, admins, and instructors to view certificates
if (!in_array(getUserRole(), ['student', 'admin', 'instructor'])) {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

$studentId = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception('Failed to connect to database');
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Unable to load certificates. Please try again later.';
    redirect('../dashboard.php');
}

$progress = new Progress();

// Get student certificates with enhanced data
$stmt = $conn->prepare("
    SELECT c.*, 
           COALESCE(co.title, 'Course') as course_title, 
           COALESCE(co.description, 'No description available') as course_description,
           co.thumbnail, 
           COALESCE(co.duration_hours, 0) as duration_hours, 
           COALESCE(co.difficulty_level, 'beginner') as difficulty_level,
           COALESCE(u.full_name, 'Instructor') as instructor_name,
           e.completed_at as course_completed_at,
           COALESCE(e.progress_percentage, 100) as final_progress
    FROM certificates c
    LEFT JOIN courses_new co ON c.course_id = co.id
    LEFT JOIN users_new u ON co.instructor_id = u.id
    LEFT JOIN enrollments e ON e.course_id = c.course_id AND e.student_id = c.student_id
    WHERE c.student_id = ? AND c.status = 'issued'
    ORDER BY c.issued_date DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get student progress for potential certificates
$overallProgress = $progress->getStudentOverallProgress($studentId);
$eligibleCourses = [];

foreach ($overallProgress as $course) {
    if ($course['progress_percentage'] >= 100 && !in_array($course['id'], array_column($certificates, 'course_id'))) {
        $eligibleCourses[] = $course;
    }
}

// Get certificate statistics
$stats = [
    'total_certificates' => count($certificates),
    'total_courses' => count($overallProgress),
    'completion_rate' => count($overallProgress) > 0 ? round((count($certificates) / count($overallProgress)) * 100, 1) : 0,
    'eligible_for_certificate' => count($eligibleCourses)
];

$conn->close();
?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book me-2"></i> Browse Courses
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> My Courses
                    </a>
                    <a href="certificates.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-brain me-2"></i> Quizzes
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="notifications.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <div class="mt-3 p-2">
                        <a href="../logout.php" class="btn btn-outline-danger w-100">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="mb-1">My Certificates</h1>
                        <p class="text-muted mb-0">Track your achievements and download certificates</p>
                    </div>
                    <div>
                        <span class="badge bg-success me-2">Student</span>
                        <button class="btn btn-outline-primary btn-sm" onclick="generateMissingCertificates()">
                            <i class="fas fa-magic me-1"></i>Generate Missing
                        </button>
                    </div>
                </div>

                <!-- Certificate Statistics Dashboard -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['total_certificates']; ?></h3>
                            <p>Certificates Earned</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['completion_rate']; ?>%</h3>
                            <p>Completion Rate</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['total_courses']; ?></h3>
                            <p>Total Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['eligible_for_certificate']; ?></h3>
                            <p>Pending Certificates</p>
                        </div>
                    </div>
                </div>

                <!-- Eligible for Certificate Alert -->
                <?php if (!empty($eligibleCourses)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Congratulations!</strong> You have <?php echo count($eligibleCourses); ?> completed course(s) eligible for certificates.
                        <button class="btn btn-sm btn-primary ms-2" onclick="generateAllEligibleCertificates()">
                            <i class="fas fa-download me-1"></i>Generate All
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Achievement Certificates</h3>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm active" onclick="filterCertificates('all', event)">
                                All (<?php echo count($certificates); ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterCertificates('recent', event)">
                                Recent
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterCertificates('advanced', event)">
                                Advanced
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($certificates)): ?>
                        <!-- Show eligible courses if any -->
                        <?php if (!empty($eligibleCourses)): ?>
                            <div class="alert alert-success mb-4">
                                <h5><i class="fas fa-trophy me-2"></i>Course Completed!</h5>
                                <p>You have completed the following course(s) and can now generate your certificate(s):</p>
                                <div class="list-group">
                                    <?php foreach ($eligibleCourses as $course): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <small class="text-success"><i class="fas fa-check-circle"></i> 100% Complete</small>
                                            </div>
                                            <a href="course-complete.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-certificate me-1"></i>Get Certificate
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center py-5">
                            <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                            <h4>No certificates earned yet</h4>
                            <p class="text-muted mb-4">Complete courses to earn certificates and showcase your achievements!</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="my-courses.php" class="btn btn-primary">
                                    <i class="fas fa-graduation-cap me-2"></i>View My Courses
                                </a>
                                <a href="progress-tracking.php" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-line me-2"></i>Track Progress
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="certificates-grid" id="certificatesContainer">
                            <?php foreach ($certificates as $certificate): ?>
                                <div class="certificate-item" data-difficulty="<?php echo htmlspecialchars($certificate['difficulty_level'] ?? 'beginner'); ?>" data-date="<?php echo !empty($certificate['issued_date']) ? htmlspecialchars($certificate['issued_date']) : date('Y-m-d'); ?>">
                                    <div class="certificate-card">
                                        <div class="certificate-header">
                                            <?php if ($certificate['thumbnail']): ?>
                                                <img src="../uploads/course_thumbnails/<?php echo htmlspecialchars($certificate['thumbnail']); ?>" 
                                                     alt="<?php echo htmlspecialchars($certificate['course_title']); ?>" 
                                                     class="certificate-thumbnail">
                                            <?php else: ?>
                                                <div class="certificate-thumbnail-placeholder">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="certificate-badge">
                                                <span class="badge bg-<?php echo $certificate['difficulty_level'] === 'advanced' ? 'danger' : ($certificate['difficulty_level'] === 'intermediate' ? 'warning' : 'success'); ?>">
                                                    <?php echo ucfirst($certificate['difficulty_level']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="certificate-body">
                                            <h5 class="certificate-title"><?php echo !empty($certificate['course_title']) ? htmlspecialchars($certificate['course_title']) : 'Course Completed'; ?></h5>
                                            <p class="certificate-description"><?php echo htmlspecialchars(mb_substr($certificate['course_description'] ?? '', 0, 120)); ?>...</p>
                                            
                                            <div class="certificate-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-user-tie"></i>
                                                    <span><?php echo htmlspecialchars($certificate['instructor_name'] ?? 'Instructor'); ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo intval($certificate['duration_hours'] ?? 0); ?> hours</span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-trophy"></i>
                                                    <span><?php echo intval($certificate['final_progress'] ?? 0); ?>% completed</span>
                                                </div>
                                            </div>
                                            
                                            <div class="certificate-footer">
                                                <div class="certificate-info">
                                                    <small class="text-muted">
                                                        <i class="fas fa-certificate me-1"></i>
                                                        ID: <?php echo !empty($certificate['certificate_id']) ? htmlspecialchars($certificate['certificate_id']) : 'N/A'; ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo !empty($certificate['issued_date']) ? date('M j, Y', strtotime($certificate['issued_date'])) : 'N/A'; ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="certificate-actions">
                                                    <?php 
                                                    $certificatePath = !empty($certificate['file_path']) ? '../uploads/' . $certificate['file_path'] : '';
                                                    $fullPath = !empty($certificate['file_path']) ? dirname(__DIR__) . '/uploads/' . $certificate['file_path'] : '';
                                                    $fileExists = !empty($certificate['file_path']) && file_exists($fullPath);
                                                    $certId = !empty($certificate['certificate_id']) ? urlencode($certificate['certificate_id']) : '';
                                                    $hasValidCertId = !empty($certificate['certificate_id']);
                                                    ?>
                                                    
                                                    <?php if ($fileExists): ?>
                                                        <button type="button" onclick="viewCertificate('<?php echo htmlspecialchars($certificatePath); ?>', '<?php echo $certId; ?>')" 
                                                                class="btn btn-sm btn-outline-primary" title="View Certificate">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Certificate file not available">
                                                            <i class="fas fa-eye-slash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasValidCertId): ?>
                                                        <a href="../generate_real_pdf.php?id=<?php echo $certId; ?>" 
                                                           class="btn btn-sm btn-primary" title="Download PDF" target="_blank">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Certificate ID not available">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasValidCertId): ?>
                                                        <button type="button" onclick="shareCertificate('<?php echo $certId; ?>', '<?php echo urlencode($certificate['course_title'] ?? 'Course'); ?>')" 
                                                                class="btn btn-sm btn-outline-success" title="Share Certificate">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Certificate ID not available">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasValidCertId): ?>
                                                        <button type="button" onclick="verifyCertificate('<?php echo $certId; ?>')" 
                                                                class="btn btn-sm btn-outline-info" title="Verify Certificate">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Certificate ID not available">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Certificate Preview Modal -->
                <div class="modal fade" id="certificateModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-certificate me-2"></i>Certificate Preview
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="certificate-preview-container">
                                    <iframe id="certificateFrame" style="width: 100%; height: 600px; border: none;"></iframe>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="printCertificate()">
                                    <i class="fas fa-print me-1"></i>Print
                                </button>
                                <a href="#" id="downloadCertificate" class="btn btn-primary">
                                    <i class="fas fa-file-pdf me-1"></i>Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Share Certificate Modal -->
                <div class="modal fade" id="shareModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-share-alt me-2"></i>Share Certificate
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Share Link</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="shareLink" readonly>
                                        <button class="btn btn-outline-primary" onclick="copyShareLink()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Share via</label>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary" onclick="shareOnLinkedIn()">
                                            <i class="fab fa-linkedin me-1"></i>LinkedIn
                                        </button>
                                        <button class="btn btn-info" onclick="shareOnTwitter()">
                                            <i class="fab fa-twitter me-1"></i>Twitter
                                        </button>
                                        <button class="btn btn-success" onclick="shareOnWhatsApp()">
                                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verify Certificate Modal -->
                <div class="modal fade" id="verifyModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle me-2"></i>Verify Certificate
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="verificationResult">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                        <p>Verifying certificate...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <link rel="stylesheet" href="../assets/css/certificates.css">
    <script>
        let currentCertificateId = null;
        let currentCertificateTitle = '';
        
        // View certificate in modal
        function viewCertificate(url, certificateId) {
            currentCertificateId = certificateId;
            $('#certificateFrame').attr('src', url);
            $('#certificateModal').modal('show');
            // Set download button to use PDF
            $('#downloadCertificate').attr('href', '../generate_real_pdf.php?id=' + certificateId);
        }
        
        // Print certificate
        function printCertificate() {
            const iframe = document.getElementById('certificateFrame');
            iframe.contentWindow.print();
        }
        
        // Share certificate
        function shareCertificate(certificateId, courseTitle) {
            currentCertificateId = certificateId;
            currentCertificateTitle = decodeURIComponent(courseTitle);
            
            const shareUrl = `${window.location.origin}/store/verify-certificate.php?id=${certificateId}`;
            $('#shareLink').val(shareUrl);
            $('#shareModal').modal('show');
        }
        
        // Copy share link
        async function copyShareLink() {
            const shareLink = document.getElementById('shareLink');
            
            try {
                await navigator.clipboard.writeText(shareLink.value);
                
                // Show success feedback
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            } catch (err) {
                // Fallback for older browsers
                shareLink.select();
                document.execCommand('copy');
            }
        }
        
        // Share on LinkedIn
        function shareOnLinkedIn() {
            const url = `${window.location.origin}/store/verify-certificate.php?id=${currentCertificateId}`;
            const text = `I've successfully completed the ${currentCertificateTitle} course!`;
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}&summary=${encodeURIComponent(text)}`, '_blank');
        }
        
        // Share on Twitter
        function shareOnTwitter() {
            const url = `${window.location.origin}/store/verify-certificate.php?id=${currentCertificateId}`;
            const text = `I've successfully completed the ${currentCertificateTitle} course! 🎓`;
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
        }
        
        // Share on WhatsApp
        function shareOnWhatsApp() {
            const url = `${window.location.origin}/store/verify-certificate.php?id=${currentCertificateId}`;
            const text = `I've successfully completed the ${currentCertificateTitle} course! 🎓 ${url}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }
        
        // Verify certificate
        function verifyCertificate(certificateId) {
            currentCertificateId = certificateId;
            $('#verifyModal').modal('show');
            
            // Simulate verification process
            setTimeout(() => {
                $.get('<?php echo BASE_URL; ?>api/verify_certificate.php', { id: certificateId })
                    .done(function(response) {
                        if (response.success) {
                            $('#verificationResult').html(`
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Valid Certificate</strong><br>
                                    <small>This certificate is authentic and verified.</small>
                                </div>
                                <div class="certificate-details">
                                    <p><strong>Course:</strong> ${response.data.course_title}</p>
                                    <p><strong>Student:</strong> ${response.data.student_name}</p>
                                    <p><strong>Issue Date:</strong> ${response.data.issued_date}</p>
                                    <p><strong>Certificate ID:</strong> ${response.data.certificate_id}</p>
                                </div>
                            `);
                        } else {
                            $('#verificationResult').html(`
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Invalid Certificate</strong><br>
                                    <small>This certificate could not be verified.</small>
                                </div>
                            `);
                        }
                    })
                    .fail(function() {
                        $('#verificationResult').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Verification Failed</strong><br>
                                <small>Unable to verify certificate at this time.</small>
                            </div>
                        `);
                    });
            }, 1500);
        }
        
        // Filter certificates
        function filterCertificates(filter, event) {
            const certificates = document.querySelectorAll('.certificate-item');
            const buttons = document.querySelectorAll('.btn-group .btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                event.target.classList.add('active');
            }
            
            certificates.forEach(cert => {
                let show = true;
                
                if (filter === 'recent') {
                    const certDate = new Date(cert.dataset.date);
                    const thirtyDaysAgo = new Date();
                    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                    show = certDate >= thirtyDaysAgo;
                } else if (filter === 'advanced') {
                    show = cert.dataset.difficulty === 'advanced';
                }
                
                cert.style.display = show ? 'block' : 'none';
            });
        }
        
        // Generate missing certificates
        function generateMissingCertificates() {
            if (confirm('This will generate certificates for all completed courses. Continue?')) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
                
                $.post('<?php echo BASE_URL; ?>api/generate_certificates.php', { action: 'generate_missing' })
                    .done(function(response) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (response.success) {
                            alert(`Successfully generated ${response.generated} certificates!`);
                            location.reload();
                        } else {
                            alert('Error generating certificates: ' + response.message);
                        }
                    })
                    .fail(function() {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        alert('Error generating certificates. Please try again.');
                    });
            }
        }
        
        // Generate all eligible certificates
        function generateAllEligibleCertificates() {
            if (confirm('Generate certificates for all eligible completed courses?')) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
                
                $.post('<?php echo BASE_URL; ?>api/generate_certificates.php', { action: 'generate_eligible' })
                    .done(function(response) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (response.success) {
                            alert(`Successfully generated ${response.generated} certificates!`);
                            location.reload();
                        } else {
                            alert('Error generating certificates: ' + response.message);
                        }
                    })
                    .fail(function() {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        alert('Error generating certificates. Please try again.');
                    });
            }
        }
        
        // Handle modal close to clear iframe
        $('#certificateModal').on('hidden.bs.modal', function () {
            $('#certificateFrame').attr('src', '');
            currentCertificateId = null;
        });
        
        // Handle share modal close
        $('#shareModal').on('hidden.bs.modal', function () {
            currentCertificateId = null;
            currentCertificateTitle = '';
        });
        
        // Handle verify modal close
        $('#verifyModal').on('hidden.bs.modal', function () {
            currentCertificateId = null;
        });
    </script>
