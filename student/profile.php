<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireStudent();

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Course.php';

$user = new User();
$course = new Course();
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user data
$userData = $user->getUserById($userId);

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
            $bio = trim($_POST['bio'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ?, phone = ? WHERE id = ?");
            $stmt->bind_param('sssi', $fullName, $bio, $phone, $userId);

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
                        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
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
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .avatar-upload {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .avatar-upload:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .avatar-upload:hover .avatar-upload-overlay {
            opacity: 1;
        }
        .stat-card-modern {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            height: 100%;
        }
        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .form-modern {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .btn-modern {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-4">
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

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="card stat-card-modern">
                            <div class="card-body text-center">
                                <i class="fas fa-book-open fa-2x text-primary mb-2"></i>
                                <h5 class="card-title"><?php echo count($enrolledCourses); ?></h5>
                                <small class="text-muted">Enrolled Courses</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card stat-card-modern">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h5 class="card-title"><?php echo $completedCourses; ?></h5>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card stat-card-modern">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                <h5 class="card-title"><?php echo round($totalStudyTime / 60, 1); ?>h</h5>
                                <small class="text-muted">Study Time</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card stat-card-modern">
                            <div class="card-body text-center">
                                <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                <h5 class="card-title"><?php echo $userData['points'] ?? 0; ?></h5>
                                <small class="text-muted">Points</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Profile Form -->
                <div class="form-modern">
                    <h4 class="mb-4">
                        <i class="fas fa-user-edit me-2"></i>Profile Information
                    </h4>
                    
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

                <!-- Recent Activity -->
                <div class="form-modern mt-4">
                    <h4 class="mb-4">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h4>
                    
                    <div class="activity-list">
                        <?php if (!empty($enrolledCourses)): ?>
                            <?php foreach (array_slice($enrolledCourses, 0, 5) as $course): ?>
                                <div class="d-flex align-items-center p-3 border-bottom">
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
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-history fa-2x mb-3 opacity-50"></i>
                                <p>No recent activity</p>
                                <a href="courses.php" class="btn btn-primary btn-modern">
                                    <i class="fas fa-search me-2"></i>Browse Courses
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function uploadAvatar(input) {
            const file = input.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('action', 'upload_avatar');

                $.ajax({
                    url: 'profile-enhanced.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#currentAvatar').attr('src', '../' + response.avatar_path);
                            alert('Avatar uploaded successfully!');
                        } else {
                            alert('Error uploading avatar: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error uploading avatar');
                    }
                });
            }
        }

        // Handle profile form submission
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');

            $.ajax({
                url: 'profile-enhanced.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Profile updated successfully!');
                    } else {
                        alert('Error updating profile: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error updating profile');
                }
            });
        });

        // Add animations
        $(document).ready(function() {
            $('.stat-card-modern').each(function(index) {
                $(this).delay(index * 100).queue(function() {
                    $(this).addClass('animate__animated animate__fadeInUp');
                });
            });
        });
    </script>
</body>
</html>
