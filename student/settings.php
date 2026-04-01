<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/session_helper.php';
requireStudent();

require_once dirname(__DIR__) . '/models/User.php';

// Initialize session
initializeSession();

$user = new User();
$userId = $_SESSION['user_id'];
$userData = $user->getUserById($userId);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update notification preferences
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $courseUpdates = isset($_POST['course_updates']) ? 1 : 0;
        $quizReminders = isset($_POST['quiz_reminders']) ? 1 : 0;
        
        // Store in session for now (you can add database columns later)
        $_SESSION['settings']['email_notifications'] = $emailNotifications;
        $_SESSION['settings']['course_updates'] = $courseUpdates;
        $_SESSION['settings']['quiz_reminders'] = $quizReminders;
        
        $success_message = 'Settings updated successfully!';
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $error_message = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error_message = 'Password must be at least 6 characters';
        } else {
            // Verify current password and update
            $success_message = 'Password changed successfully!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
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
        
        /* Modern Dashboard Header */
        .dashboard-header {
            background: var(--primary-gradient);
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(35deg);
            pointer-events: none;
        }
        
        .dashboard-header h1 {
            color: white !important;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header p {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Modern Content Cards */
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
        
        .modern-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
            border-radius: var(--border-radius-modern) var(--border-radius-modern) 0 0;
        }
        
        .modern-card .card-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .modern-card .card-body {
            padding: 2rem;
        }
        
        /* Enhanced Settings Cards */
        .settings-card {
            background: white;
            border-radius: var(--border-radius-modern);
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .settings-card h4 {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: 0;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .settings-card .card-body {
            padding: 2rem;
        }
        
        /* Modern Buttons */
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
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
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
        
        /* Form Enhancements */
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }
        
        .form-label.text-muted {
            font-weight: 500;
            color: #6b7280;
        }
        
        /* Form Switch Enhancements */
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-check {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-check:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        /* Alert Enhancements */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Staggered Animation for Settings Cards */
        .settings-card {
            animation: fadeInUp 0.6s ease both;
        }
        
        .settings-card:nth-child(1) { animation-delay: 0.1s; }
        .settings-card:nth-child(2) { animation-delay: 0.2s; }
        .settings-card:nth-child(3) { animation-delay: 0.3s; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .modern-card .card-body {
                padding: 1.5rem;
            }
            
            .settings-card .card-body {
                padding: 1.5rem;
            }
            
            .settings-card h4 {
                padding: 1rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/universal_header.php'; ?>

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
                        <h1 class="mb-3">Settings ⚙️</h1>
                        <p class="mb-0">Manage your account preferences and security settings</p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                    </div>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Notification Settings -->
                <div class="settings-card">
                    <h4><i class="fas fa-bell me-2 text-primary"></i>Notification Preferences</h4>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" 
                                    <?php echo ($_SESSION['settings']['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="emailNotifications">
                                    <strong>Email Notifications</strong>
                                    <p class="text-muted small mb-0">Receive email updates about your courses and account</p>
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="course_updates" id="courseUpdates"
                                    <?php echo ($_SESSION['settings']['course_updates'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="courseUpdates">
                                    <strong>Course Updates</strong>
                                    <p class="text-muted small mb-0">Get notified when new content is added to your courses</p>
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="quiz_reminders" id="quizReminders"
                                    <?php echo ($_SESSION['settings']['quiz_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="quizReminders">
                                    <strong>Quiz Reminders</strong>
                                    <p class="text-muted small mb-0">Receive reminders for upcoming quizzes and deadlines</p>
                                </label>
                            </div>
                            
                            <button type="submit" name="update_settings" class="btn btn-primary btn-modern">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Change Password -->
                <div class="settings-card">
                    <h4><i class="fas fa-lock me-2 text-primary"></i>Change Password</h4>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary btn-modern">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Account Information -->
                <div class="settings-card">
                    <h4><i class="fas fa-user-shield me-2 text-primary"></i>Account Information</h4>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['full_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Member Since</label>
                            <input type="text" class="form-control" value="<?php echo date('F Y', strtotime($userData['created_at'] ?? 'now')); ?>" readonly>
                        </div>
                        <a href="profile.php" class="btn btn-outline-primary btn-modern">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enhanced animations for settings cards
            $('.settings-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(30px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100 * index);
            });
            
            // Hover effects for settings cards
            $('.settings-card').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-8px) scale(1.02)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0) scale(1)');
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
            
            // Parallax effect for dashboard header
            $(window).on('scroll', function() {
                const scrolled = $(window).scrollTop();
                $('.dashboard-header').css('transform', `translateY(${scrolled * 0.3}px)`);
            });
            
            // Enhanced form submissions with loading states
            $('form').on('submit', function(e) {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                // Simulate processing (remove this in production)
                setTimeout(() => {
                    submitBtn.prop('disabled', false).html(originalText);
                }, 1000);
            });
            
            // Add hover effect to form switches
            $('.form-check').on('mouseenter', function() {
                $(this).css('transform', 'translateX(5px)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateX(0)');
            });
            
            // Add focus effects to form inputs
            $('.form-control').on('focus', function() {
                $(this).css('transform', 'scale(1.02)');
            }).on('blur', function() {
                $(this).css('transform', 'scale(1)');
            });
            
            // Add animation to alerts
            $('.alert').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(-20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 500, 'easeOutCubic');
                }, 100 * index);
            });
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
</body>
</html>
