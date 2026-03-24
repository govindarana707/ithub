<?php
require_once '../config/config.php';
require_once '../services/TrialService.php';
require_once '../services/NotificationService.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || getUserRole() !== 'student') {
    redirect('login.php');
}

// Initialize services
$trialService = new TrialService();
$notificationService = new NotificationService();

// Get user's active trials
$activeTrials = $trialService->getUserActiveTrials($_SESSION['user_id']);

// Get unread notifications count
$unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Free Trials - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .trial-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .trial-card.expiring-soon {
            border-left-color: #ffc107;
        }
        .trial-card.expiring-critical {
            border-left-color: #dc3545;
        }
        .trial-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .days-remaining {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .days-remaining.plenty {
            color: #28a745;
        }
        .days-remaining.warning {
            color: #ffc107;
        }
        .days-remaining.danger {
            color: #dc3545;
        }
        .progress-ring {
            width: 60px;
            height: 60px;
        }
        .progress-ring circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        .trial-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .upgrade-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            transition: all 0.3s ease;
        }
        .upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-clock me-2"></i>My Free Trials</h2>
                        <p class="text-muted">Manage your active free trials and track your learning progress</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trial Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card trial-stats">
                    <div class="card-body text-center">
                        <h3 class="mb-2"><?php echo count($activeTrials); ?></h3>
                        <p class="mb-0">Active Trials</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-2"><?php echo $unreadCount; ?></h3>
                        <p class="mb-0">Unread Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-2">30</h3>
                        <p class="mb-0">Trial Duration (Days)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Trials -->
        <?php if (!empty($activeTrials)): ?>
            <div class="row">
                <?php foreach ($activeTrials as $trial): ?>
                    <?php
                    $daysRemaining = $trial['days_remaining'];
                    $statusClass = $daysRemaining <= 3 ? 'expiring-critical' : ($daysRemaining <= 7 ? 'expiring-soon' : '');
                    $daysClass = $daysRemaining <= 3 ? 'danger' : ($daysRemaining <= 7 ? 'warning' : 'plenty');
                    $progressPercentage = floatval($trial['progress_percentage']);
                    ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card trial-card <?php echo $statusClass; ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-start mb-3">
                                            <img src="<?php echo resolveUploadUrl($trial['course_thumbnail']); ?>" 
                                                 alt="<?php echo htmlspecialchars($trial['course_title']); ?>" 
                                                 class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title mb-1">
                                                    <?php echo htmlspecialchars($trial['course_title']); ?>
                                                </h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($trial['instructor_name']); ?>
                                                </p>
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-primary me-2">Free Trial</span>
                                                    <span class="days-remaining <?php echo $daysClass; ?>">
                                                        <?php echo $daysRemaining; ?> days left
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Course Progress</small>
                                                <small><?php echo round($progressPercentage); ?>%</small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo $progressPercentage; ?>%"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2">
                                            <a href="view-course.php?id=<?php echo $trial['course_id']; ?>" 
                                               class="btn btn-primary btn-sm flex-grow-1">
                                                <i class="fas fa-play me-1"></i>Continue Learning
                                            </a>
                                            <button class="btn btn-success btn-sm upgrade-btn" 
                                                    onclick="upgradeToPaid(<?php echo $trial['course_id']; ?>, '<?php echo htmlspecialchars($trial['course_title']); ?>', <?php echo $trial['course_price']; ?>)">
                                                <i class="fas fa-crown me-1"></i>Upgrade
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-center">
                                        <!-- Circular Progress -->
                                        <svg class="progress-ring mx-auto mb-3">
                                            <circle cx="30" cy="30" r="25" 
                                                   stroke="#e9ecef" 
                                                   stroke-width="5" 
                                                   fill="transparent"/>
                                            <circle cx="30" cy="30" r="25" 
                                                   stroke="#28a745" 
                                                   stroke-width="5" 
                                                   fill="transparent"
                                                   stroke-dasharray="<?php echo (2 * 3.14159 * 25); ?>"
                                                   stroke-dashoffset="<?php echo (2 * 3.14159 * 25) * (1 - $progressPercentage / 100); ?>"/>
                                        </svg>
                                        <div class="small text-muted">
                                            <strong><?php echo round($progressPercentage); ?>%</strong><br>
                                            Completed
                                        </div>
                                        
                                        <!-- Trial Info -->
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                Started: <?php echo date('M j, Y', strtotime($trial['enrolled_at'])); ?><br>
                                                Expires: <?php echo date('M j, Y', strtotime($trial['expires_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Active Trials -->
            <div class="text-center py-5">
                <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                <h3>No Active Trials</h3>
                <p class="text-muted mb-4">
                    You don't have any active free trials. Start exploring courses and begin your learning journey!
                </p>
                <a href="../courses.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Courses
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upgrade Modal -->
    <div class="modal fade" id="upgradeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-crown me-2"></i>Upgrade to Full Access
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="upgradeCourseId">
                    <input type="hidden" id="upgradeCourseTitle">
                    <input type="hidden" id="upgradeCoursePrice">
                    
                    <div class="text-center mb-4">
                        <h6 id="modalCourseTitle">Course Title</h6>
                        <p class="text-muted">Keep your progress and get unlimited access</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your trial progress will be transferred to your paid enrollment.
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="proceedWithPayment('esewa')">
                            🟢 Pay with eSewa
                        </button>
                        <button class="btn btn-primary" onclick="proceedWithPayment('khalti')">
                            🟣 Pay with Khalti
                        </button>
                        <button class="btn btn-dark" onclick="proceedWithPayment('other')">
                            💳 Other Payment Methods
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let upgradeModal;
        
        $(document).ready(function() {
            upgradeModal = new bootstrap.Modal(document.getElementById('upgradeModal'));
        });
        
        function upgradeToPaid(courseId, courseTitle, coursePrice) {
            $('#upgradeCourseId').val(courseId);
            $('#upgradeCourseTitle').val(courseTitle);
            $('#upgradeCoursePrice').val(coursePrice);
            $('#modalCourseTitle').text(courseTitle);
            
            upgradeModal.show();
        }
        
        function proceedWithPayment(method) {
            const courseId = $('#upgradeCourseId').val();
            const courseTitle = $('#upgradeCourseTitle').val();
            const coursePrice = $('#upgradeCoursePrice').val();
            
            upgradeModal.hide();
            
            // Show processing message
            showAlert('Processing payment...', 'info');
            
            // Route to appropriate payment handler
            if(method === 'esewa'){
                initiateEsewaPayment(courseId, courseTitle, coursePrice, true); // true for trial conversion
            }
            else if(method === 'khalti'){
                showAlert('Khalti payment integration coming soon!', 'info');
            }
            else{
                showAlert('Other payment methods coming soon!', 'info');
            }
        }
        
        function initiateEsewaPayment(courseId, courseTitle, coursePrice, isTrialConversion) {
            $.ajax({
                url: '../api/esewa_payment.php',
                type: 'POST',
                data: {
                    course_id: courseId,
                    payment_method: 'esewa',
                    trial_conversion: isTrialConversion
                },
                xhrFields: {
                    withCredentials: true
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Create and submit eSewa form
                        const form = $('<form>', {
                            method: 'POST',
                            action: response.payment_form.form_action,
                            target: '_blank'
                        });
                        
                        $.each(response.payment_form.form_data, function(key, value) {
                            form.append($('<input>', {
                                type: 'hidden',
                                name: key,
                                value: value
                            }));
                        });
                        
                        form.appendTo('body').submit();
                        showAlert('Redirecting to eSewa...', 'info');
                    } else {
                        showAlert('Payment initiation failed. Please try again.', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    showAlert('Payment service unavailable. Please try again.', 'danger');
                }
            });
        }
        
        function showAlert(message, type) {
            $('.alert').remove();
            
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.container').first().prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
</body>
</html>
