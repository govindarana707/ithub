<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/session_helper.php';
requireStudent();

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Course.php';

// Initialize session
initializeSession();

$user = new User();
$course = new Course();
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user data from same source as universal header
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, username, email, full_name, role, profile_image FROM users_new WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// Get user's enrolled courses for stats
$enrolledCourses = $course->getEnrolledCourses($userId);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
$totalStudyTime = 0;
foreach ($enrolledCourses as $course) {
    $totalStudyTime += ($course['time_spent'] ?? 0);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'update_profile':
            $fullName = trim($_POST['full_name'] ?? '');
            
            // Update the users table (same as universal header source)
            $stmt = $conn->prepare("UPDATE users_new SET full_name = ? WHERE id = ?");
            $stmt->bind_param('si', $fullName, $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
            exit;

        case 'upload_avatar':
            if (isset($_FILES['avatar'])) {
                $file = $_FILES['avatar'];
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $file['size'] < 5000000) {
                    $uploadDir = dirname(__DIR__) . '/../uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Update user avatar in database
                        $stmt = $conn->prepare("UPDATE users_new SET profile_image = ? WHERE id = ?");
                        $avatarPath = 'uploads/avatars/' . $filename;
                        $stmt->bind_param('si', $avatarPath, $userId);
                        
                        if ($stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Avatar uploaded successfully', 'avatar_path' => $avatarPath]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update avatar']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid file format or size']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
        
        /* Enhanced Profile Header */
        .profile-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius-modern);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .profile-header::before {
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
        
        .avatar-upload {
            position: relative;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .avatar-upload:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }
        
        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .avatar-upload:hover .avatar-upload-overlay {
            opacity: 1;
        }
        
        /* Enhanced Stats Cards */
        .stat-card-modern {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            border-radius: var(--border-radius-modern);
            height: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transition: all 0.4s ease;
        }
        
        .stat-card-modern.success::before {
            background: var(--success-gradient);
        }
        
        .stat-card-modern.info::before {
            background: var(--info-gradient);
        }
        
        .stat-card-modern.warning::before {
            background: var(--warning-gradient);
        }
        
        .stat-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card-modern:hover::before {
            height: 8px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
            color: white;
            transition: all 0.3s ease;
        }
        
        .stat-icon.primary {
            background: var(--primary-gradient);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .stat-icon.success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .stat-icon.info {
            background: var(--info-gradient);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .stat-icon.warning {
            background: var(--warning-gradient);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .stat-icon:hover {
            transform: scale(1.1);
        }
        
        /* Modern Form Styling */
        .form-modern {
            background: white;
            border-radius: var(--border-radius-modern);
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            border: none;
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
        
        .btn-outline-secondary {
            border-color: #6b7280 !important;
            color: #6b7280 !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-secondary:hover {
            background: #6b7280 !important;
            border-color: transparent !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }
        
        .btn-light {
            background: rgba(255, 255, 255, 0.9) !important;
            color: #667eea !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-light:hover {
            background: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3) !important;
        }
        
        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.8) !important;
            color: white !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-light:hover {
            background: white !important;
            color: #667eea !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3) !important;
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
        
        .form-label.fw-semibold {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }
        
        /* Activity List Enhancements */
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin-bottom: 0.5rem;
        }
        
        .activity-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: translateX(5px);
        }
        
        .activity-item:last-child {
            border-bottom: none;
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
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Staggered Animation for Stats */
        .stat-card-modern {
            animation: fadeInUp 0.6s ease both;
        }
        
        .stat-card-modern:nth-child(1) { animation-delay: 0.1s; }
        .stat-card-modern:nth-child(2) { animation-delay: 0.2s; }
        .stat-card-modern:nth-child(3) { animation-delay: 0.3s; }
        .stat-card-modern:nth-child(4) { animation-delay: 0.4s; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .profile-header {
                padding: 2rem;
            }
            
            .avatar-upload {
                width: 140px;
                height: 140px;
            }
            
            .modern-card .card-body {
                padding: 1.5rem;
            }
            
            .form-modern {
                padding: 1.5rem;
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
                        <h1 class="mb-3">My Profile 👤</h1>
                        <p class="mb-0">Manage your personal information and track your learning progress</p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                <!-- Profile Card -->
                <div class="profile-header text-center">
                    <div class="avatar-upload mb-3" onclick="document.getElementById('avatarInput').click()">
                        <img id="currentAvatar" src="<?php echo $userData['profile_image'] ? '../' . htmlspecialchars($userData['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($userData['full_name']) . '&background=random'; ?>" 
                             class="w-100 h-100 object-fit-cover" alt="Profile">
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera fa-2x text-white"></i>
                        </div>
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                    
                    <h3 class="mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h3>
                    <p class="mb-0 opacity-75">Student</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <a href="edit-profile.php" class="btn btn-light btn-modern">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                        <a href="settings.php" class="btn btn-outline-light btn-modern">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </div>
                </div>

                <!-- Enhanced Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="card-body text-center">
                                <div class="stat-icon primary">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h5 class="card-title fw-bold"><?php echo count($enrolledCourses); ?></h5>
                                <small class="text-muted">Enrolled Courses</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern success">
                            <div class="card-body text-center">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h5 class="card-title fw-bold"><?php echo $completedCourses; ?></h5>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern info">
                            <div class="card-body text-center">
                                <div class="stat-icon info">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h5 class="card-title fw-bold"><?php echo round($totalStudyTime / 60, 1); ?>h</h5>
                                <small class="text-muted">Study Time</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern warning">
                            <div class="card-body text-center">
                                <div class="stat-icon warning">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <h5 class="card-title fw-bold"><?php echo $userData['points'] ?? 0; ?></h5>
                                <small class="text-muted">Points</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Enhanced Profile Form -->
                <div class="modern-card form-modern">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2 text-primary"></i>
                            Profile Information
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                                        <div class="form-text">Email cannot be changed</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Join Date</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('M j, Y', strtotime($userData['created_at'] ?? '')); ?>" disabled>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Bio</label>
                                        <textarea class="form-control" name="bio" rows="4" 
                                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary btn-modern">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Recent Activity -->
                <div class="modern-card mt-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Recent Activity
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (!empty($enrolledCourses)): ?>
                                <?php foreach (array_slice($enrolledCourses, 0, 5) as $course): ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-primary text-white rounded-circle p-2" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-book-open small"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                <small class="text-muted">
                                                    Enrolled on <?php echo date('M j, Y', strtotime($course['enrolled_at'] ?? '')); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo ($course['progress_percentage'] ?? 0) >= 100 ? 'success' : 'primary'; ?> rounded-pill">
                                                    <?php echo ($course['progress_percentage'] ?? 0); ?>%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <div class="mb-4">
                                        <i class="fas fa-history fa-4x" style="opacity: 0.4;"></i>
                                    </div>
                                    <h5 class="fw-bold text-muted mb-3">No recent activity</h5>
                                    <p class="text-muted mb-4">Start exploring courses to see your learning progress here.</p>
                                    <a href="courses.php" class="btn btn-primary-modern btn-modern">
                                        <i class="fas fa-search me-2"></i>Browse Courses
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enhanced animations for stat cards
            $('.stat-card-modern').each(function(index) {
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
            $('.stat-card-modern').on('mouseenter', function() {
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
            
            // Enhanced avatar upload
            function uploadAvatar(input) {
                const file = input.files[0];
                if (file) {
                    // Show loading state on avatar
                    $('#currentAvatar').css('opacity', '0.5');
                    
                    const formData = new FormData();
                    formData.append('avatar', file);
                    formData.append('action', 'upload_avatar');

                    $.ajax({
                        url: 'profile.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#currentAvatar').attr('src', '../' + response.avatar_path);
                                showSuccessMessage('Avatar uploaded successfully!');
                            } else {
                                showErrorMessage('Error uploading avatar: ' + response.message);
                            }
                            $('#currentAvatar').css('opacity', '1');
                        },
                        error: function() {
                            showErrorMessage('Error uploading avatar');
                            $('#currentAvatar').css('opacity', '1');
                        }
                    });
                }
            }
            
            // Enhanced profile form submission
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
                
                const formData = new FormData(this);
                formData.append('action', 'update_profile');

                $.ajax({
                    url: 'profile.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showSuccessMessage('Profile updated successfully!');
                            // Update the name in the profile header
                            const newName = $('input[name="full_name"]').val();
                            $('.profile-header h3').text(newName);
                        } else {
                            showErrorMessage('Error updating profile: ' + response.message);
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    error: function() {
                        showErrorMessage('Error updating profile');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Helper functions for showing messages
            function showSuccessMessage(message) {
                var alertHtml = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                showAlert(alertHtml);
            }
            
            function showErrorMessage(message) {
                var alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                showAlert(alertHtml);
            }
            
            function showAlert(alertHtml) {
                // Remove existing alerts
                $('.container-fluid .alert').fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Add new alert at the top of the main content
                $('.col-md-9').prepend(alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $('.alert').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Add hover effect to stat icons
            $('.stat-icon').on('mouseenter', function() {
                $(this).css('transform', 'scale(1.1) rotate(5deg)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'scale(1) rotate(0deg)');
            });
            
            // Add hover effect to avatar
            $('.avatar-upload').on('mouseenter', function() {
                $(this).css('transform', 'scale(1.05)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'scale(1)');
            });
            
            // Add hover effect to activity items
            $('.activity-item').on('mouseenter', function() {
                $(this).css('transform', 'translateX(8px)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateX(0)');
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
