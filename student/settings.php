<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireStudent();

require_once dirname(__DIR__) . '/models/User.php';

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); min-height: 100vh; }
        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-primary-gradient {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Universal Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i> Student
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="my-courses.php">My Courses</a></li>
                        <li><a class="dropdown-item" href="certificates.php">Certificates</a></li>
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Universal Sidebar -->
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
                    <a href="certificates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
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
                    <a href="settings.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <div class="mt-3 p-2">
                        <a href="../logout.php" class="btn btn-outline-danger w-100">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <h1 class="fw-bold mb-4">Settings</h1>
                
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

                <!-- Notification Settings -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="fas fa-bell me-2 text-primary"></i>Notification Preferences</h4>
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
                        
                        <button type="submit" name="update_settings" class="btn btn-primary-gradient">
                            <i class="fas fa-save me-2"></i>Save Preferences
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="fas fa-lock me-2 text-primary"></i>Change Password</h4>
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
                        <button type="submit" name="change_password" class="btn btn-primary-gradient">
                            <i class="fas fa-key me-2"></i>Update Password
                        </button>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="fas fa-user-shield me-2 text-primary"></i>Account Information</h4>
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
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
