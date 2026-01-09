<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

$studentId = $_SESSION['user_id'];

// Get student certificates
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT c.*, co.title as course_title, co.description as course_description
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    WHERE c.student_id = ?
    ORDER BY c.issued_at DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                        <span class="badge bg-primary float-end">0</span>
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-brain me-2"></i> Quizzes
                        <span class="badge bg-info float-end">0</span>
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="certificates.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>My Certificates</h1>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <div class="dashboard-card">
                    <h3>Achievement Certificates</h3>
                    
                    <?php if (empty($certificates)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-certificate fa-3x text-muted mb-3"></i>
                            <h5>No certificates earned yet</h5>
                            <p class="text-muted">Complete courses to earn certificates and showcase your achievements!</p>
                            <a href="my-courses.php" class="btn btn-primary">
                                <i class="fas fa-graduation-cap me-2"></i>View My Courses
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($certificates as $certificate): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card certificate-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="certificate-icon me-3">
                                                    <i class="fas fa-certificate fa-2x text-warning"></i>
                                                </div>
                                                <div>
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($certificate['course_title']); ?></h5>
                                                    <small class="text-muted">Certificate ID: <?php echo htmlspecialchars($certificate['certificate_code']); ?></small>
                                                </div>
                                            </div>
                                            
                                            <p class="card-text"><?php echo substr(htmlspecialchars($certificate['course_description']), 0, 100); ?>...</p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Issued: <?php echo date('M j, Y', strtotime($certificate['issued_at'])); ?>
                                                </small>
                                                <div>
                                                    <a href="<?php echo BASE_URL . $certificate['certificate_url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                    <a href="<?php echo BASE_URL . $certificate['certificate_url']; ?>" download class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download me-1"></i> Download
                                                    </a>
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
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Certificate Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <iframe id="certificateFrame" style="width: 100%; height: 500px; border: none;"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="downloadCertificate">
                                    <i class="fas fa-download me-1"></i> Download Certificate
                                </button>
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
    <script>
        function viewCertificate(url) {
            $('#certificateFrame').attr('src', url);
            $('#certificateModal').modal('show');
            
            $('#downloadCertificate').off('click').on('click', function() {
                window.open(url, '_blank');
            });
        }
    </script>
