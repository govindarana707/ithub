<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/session_helper.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Progress.php';

// Initialize session
initializeSession();

if (!isUserLoggedIn()) {
    redirect('../login.php');
}

// Allow students, admins, and instructors to view certificates
if (!in_array(getUserRole(), ['student', 'admin', 'instructor'])) {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

$studentId = getCurrentUserId();

// Get current student's name for display
$studentName = getUserDisplayName() ?? $_SESSION['full_name'] ?? 'Student';

// Security: Verify user session is valid
if (!isValidSession($studentId)) {
    error_log("SECURITY ALERT: Invalid session detected - student_id is empty or invalid");
    $_SESSION['error_message'] = 'Invalid session. Please log in again.';
    redirect('../login.php');
}

// Additional security: Verify user exists in database and is active
$conn = connectDB();
$userVerifyStmt = $conn->prepare('SELECT id, full_name, email, role FROM users_new WHERE id = ? AND status = "active"');
$userVerifyStmt->bind_param("i", $studentId);
$userVerifyStmt->execute();
$userExists = $userVerifyStmt->get_result()->fetch_assoc();
$userVerifyStmt->close();

if (!$userExists) {
    error_log("SECURITY ALERT: User ID $studentId not found or inactive in database");
    $_SESSION['error_message'] = 'Account not found or inactive. Please contact support.';
    redirect('../login.php');
}

// Verify session name matches database name (additional security layer)
if ($userExists['full_name'] !== $studentName && $studentName !== 'Student') {
    error_log("SECURITY ALERT: Session name mismatch for user $studentId - session: '$studentName', database: '{$userExists['full_name']}'");
    // Update session with correct name from database
    $_SESSION['full_name'] = $userExists['full_name'];
    $studentName = $userExists['full_name'];
}

$conn->close();

// Debug: Log current session information
error_log("Certificate Debug: Current session - User ID: $studentId, User Name: " . ($_SESSION['full_name'] ?? 'Not set') . ", Role: " . ($_SESSION['role'] ?? 'Not set'));

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

// Get student certificates with enhanced data and validation
$stmt = $conn->prepare("
    SELECT c.*, 
           COALESCE(co.title, 'Course Completed') as course_title, 
           COALESCE(co.description, 'Certificate of completion for successfully finishing course requirements') as course_description,
           co.thumbnail, 
           COALESCE(co.duration_hours, 0) as duration_hours, 
           COALESCE(co.difficulty_level, 'beginner') as difficulty_level,
           COALESCE(instructor.full_name, 'Instructor') as instructor_name,
           COALESCE(student.full_name, 'Unknown Student') as student_name,
           e.completed_at as course_completed_at,
           COALESCE(e.progress_percentage, 100) as final_progress
    FROM certificates c
    LEFT JOIN courses_new co ON c.course_id = co.id
    LEFT JOIN users_new instructor ON co.instructor_id = instructor.id
    LEFT JOIN users_new student ON c.student_id = student.id
    LEFT JOIN enrollments_new e ON e.course_id = c.course_id AND e.user_id = c.student_id
    WHERE c.student_id = ? AND c.status = 'issued'
    ORDER BY c.issued_date DESC
");

$stmt->bind_param("i", $studentId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Security: Verify all certificates belong to the current user
$validCertificates = [];
$securityViolations = 0;
foreach ($certificates as $cert) {
    // Double-check that this certificate actually belongs to the current user
    if ($cert['student_id'] == $studentId) {
        // Additional verification: ensure the student name matches
        if ($cert['student_name'] === $studentName || $cert['student_name'] === 'Unknown Student') {
            $validCertificates[] = $cert;
        } else {
            error_log("SECURITY ALERT: Certificate ID {$cert['id']} name mismatch - expected '$studentName', found '{$cert['student_name']}'");
            $securityViolations++;
        }
    } else {
        // Log security violation
        error_log("SECURITY ALERT: Certificate ID {$cert['id']} belongs to student {$cert['student_id']} but user $studentId tried to access it");
        $securityViolations++;
    }
}

// Log security summary
if ($securityViolations > 0) {
    error_log("SECURITY SUMMARY: $securityViolations certificate access violations blocked for user $studentId");
}

$certificates = $validCertificates;

// Debug: Log certificate data
error_log("Certificates Debug: Found " . count($certificates) . " certificates for student $studentId");

// Get student progress for potential certificates
$overallProgress = $progress->getStudentOverallProgress($studentId);
$eligibleCourses = [];

foreach ($overallProgress as $course) {
    if ($course['progress_percentage'] >= 100 && !in_array($course['id'], array_column($certificates, 'course_id'))) {
        $eligibleCourses[] = $course;
    }
}

// Get certificate statistics
$completedCourses = 0;
foreach ($overallProgress as $course) {
    if ($course['progress_percentage'] >= 100) {
        $completedCourses++;
    }
}

$stats = [
    'total_certificates' => count($certificates),
    'total_courses' => count($overallProgress),
    'completion_rate' => count($overallProgress) > 0 ? round(($completedCourses / count($overallProgress)) * 100, 1) : 0,
    'eligible_for_certificate' => count($eligibleCourses)
];

$conn->close();

error_log("Certificates Debug: Current student ID = $studentId, Student name = $studentName");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <link href="../assets/css/certificates.css" rel="stylesheet">
    <style>
        /* Modern Dashboard Color Scheme */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius-modern: 20px;
        }
        
        /* Modern Header Styling */
        .dashboard-header {
            background: var(--primary-gradient);
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--card-shadow);
        }
        
        /* Modern Card Styling */
        .modern-card {
            background: white;
            border-radius: var(--border-radius-modern);
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Modern Button Styling */
        .btn-modern {
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Override buttons to use modern styling */
        .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }
        
        .btn-outline-primary {
            border-color: #667eea !important;
            color: #667eea !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-gradient) !important;
            border-color: transparent !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }
        
        /* Modern stat cards */
        .stat-card {
            background: white !important;
            border: none !important;
            border-radius: var(--border-radius-modern) !important;
            padding: 2rem !important;
            box-shadow: var(--card-shadow) !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
            text-align: center;
        }
        
        .stat-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 4px !important;
            background: var(--primary-gradient) !important;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
        }
        
        .stat-card:hover::before {
            height: 8px !important;
        }
        
        .stat-card h3 {
            color: #2d3748 !important;
            font-size: 2.2rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.5rem !important;
            transition: transform 0.4s ease !important;
        }
        
        .stat-card:hover h3 {
            transform: scale(1.05) !important;
        }
        
        .stat-card p {
            color: #718096 !important;
            font-weight: 500 !important;
            margin: 0 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            font-size: 0.9rem !important;
        }
        
        /* Certificate grid modernization */
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .certificate-card {
            background: white;
            border-radius: var(--border-radius-modern);
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            border: none;
        }
        
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Student identity styling */
        .student-identity {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 12px 16px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
        }
        
        .student-identity small {
            margin: 0;
            font-size: 13px;
            color: #475569;
        }
        
        /* Dashboard card styling */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .certificates-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stat-card {
                padding: 1.5rem !important;
            }
            
            .stat-card h3 {
                font-size: 1.8rem !important;
            }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/includes/universal_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php require_once 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Modern Dashboard Header -->
                <div class="dashboard-header">
                    <div class="position-relative">
                        <h1 class="mb-3">My Certificates 🎓</h1>
                        <p class="mb-2">Track your achievements and download certificates</p>
                        <p class="mb-0" style="opacity: 0.9;">
                            <i class="fas fa-user me-1"></i>Showing certificates for: <strong><?php echo htmlspecialchars($studentName); ?></strong>
                            <span class="ms-3"><i class="fas fa-id-badge me-1"></i>User ID: <strong><?php echo htmlspecialchars($studentId); ?></strong></span>
                        </p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                        <button class="btn btn-light btn-modern ms-2" onclick="generateMissingCertificates()">
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

                <!-- Achievement Certificates Section -->
                <div class="modern-card dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">
                            <i class="fas fa-trophy me-2 text-warning"></i>
                            Achievement Certificates
                        </h3>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-modern btn-sm active" onclick="filterCertificates('all', event)">
                                All (<?php echo count($certificates); ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-modern btn-sm" onclick="filterCertificates('recent', event)">
                                Recent
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-modern btn-sm" onclick="filterCertificates('advanced', event)">
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
                            <div class="mb-4">
                                <i class="fas fa-certificate fa-4x text-muted" style="opacity: 0.4;"></i>
                            </div>
                            <h4 class="fw-bold text-muted mb-3">No certificates earned yet</h4>
                            <p class="text-muted mb-4">Complete courses to earn certificates and showcase your achievements!</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="my-courses.php" class="btn btn-primary-modern btn-modern">
                                    <i class="fas fa-graduation-cap me-2"></i>View My Courses
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-primary btn-modern">
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
                                            <div class="student-identity mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-user-graduate me-1"></i>
                                                    Awarded to: <strong><?php echo htmlspecialchars($certificate['student_name']); ?></strong>
                                                </small>
                                            </div>
                                            <h5 class="certificate-title"><?php echo htmlspecialchars($certificate['course_title']); ?></h5>
                                            <p class="certificate-description"><?php echo htmlspecialchars(mb_substr($certificate['course_description'] ?? '', 0, 120)); ?>...</p>
                                            
                                            <div class="certificate-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-user-tie"></i>
                                                    <span>Instructor: <?php echo htmlspecialchars($certificate['instructor_name']); ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo intval($certificate['duration_hours']); ?> hours</span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-trophy"></i>
                                                    <span><?php echo intval($certificate['final_progress']); ?>% completed</span>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <link rel="stylesheet" href="../assets/css/theme-colors.css">
    <link rel="stylesheet" href="../assets/css/certificates.css">
    <script>
        $(document).ready(function() {
            // Enhanced animations for stat cards
            $('.stat-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(30px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100 * index);
            });
            
            // Hover effects for stat cards
            $('.stat-card').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-10px) scale(1.02)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0) scale(1)');
            });
            
            // Certificate card animations
            $('.certificate-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 500, 'easeOutCubic');
                }, 200 * index);
            });
            
            // Button ripple effect
            $('.btn-modern').on('click', function(e) {
                const button = $(this);
                const ripple = $('<span class="ripple"></span>');
                
                button.append(ripple);
                
                const x = e.pageX - button.offset().left;
                const y = e.pageY - button.offset().top;
                
                ripple.css({
                    left: x,
                    top: y
                });
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Number counting animation for stats
            $('.stat-card h3').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text().replace(/[^\d.]/g, ''));
                
                if (!isNaN(countTo)) {
                    const originalText = $this.text();
                    $this.text('0');
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1500,
                        easing: 'easeOutCubic',
                        step: function() {
                            if (originalText.includes('%')) {
                                $this.text(Math.floor(this.countNum) + '%');
                            } else {
                                $this.text(Math.floor(this.countNum));
                            }
                        },
                        complete: function() {
                            $this.text(originalText);
                        }
                    });
                }
            });
            
            // Parallax effect for dashboard header
            $(window).on('scroll', function() {
                const scrolled = $(window).scrollTop();
                $('.dashboard-header').css('transform', `translateY(${scrolled * 0.3}px)`);
            });
        });
        
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
                $.get('../api/verify_certificate.php', { id: certificateId })
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
                
                $.post('../api/generate_certificates.php', { action: 'generate_missing' })
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
                
                $.post('../api/generate_certificates.php', { action: 'generate_eligible' })
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
    </script>
    
    <!-- Add CSS for ripple effect -->
    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
