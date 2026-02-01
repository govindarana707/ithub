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
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $file['size'] < 5000000) {
                    $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $dbPath = 'uploads/avatars/' . $filename;
                        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt->bind_param('si', $dbPath, $userId);
                        $stmt->execute();

                        echo json_encode(['success' => true, 'path' => BASE_URL . $dbPath]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Upload failed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid file']);
                }
            }
            exit;
    }
}

// Get user data
$userData = $user->getUserById($userId);
$enrolledCourses = $course->getEnrolledCourses($userId);

// Statistics
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));

// Quiz stats
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, AVG(score) as avg_score
    FROM quiz_attempts WHERE student_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$quizStats = $stmt->get_result()->fetch_assoc();

// Certificates
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE student_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_assoc()['total'];

// Study time
$stmt = $conn->prepare("SELECT COALESCE(SUM(time_spent_minutes), 0) as total FROM lesson_progress WHERE student_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$studyMinutes = $stmt->get_result()->fetch_assoc()['total'];
$studyHours = round($studyMinutes / 60, 1);

// Learning streak
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(last_accessed_at)) as days
    FROM lesson_progress
    WHERE student_id = ? AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$streak = $stmt->get_result()->fetch_assoc()['days'] ?? 0;

// Recent activity
$stmt = $conn->prepare("
    SELECT 'lesson' as type, l.title, lp.last_accessed_at as date, c.title as course
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE lp.student_id = ?
    ORDER BY lp.last_accessed_at DESC
    LIMIT 10
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Skills from courses
$skills = [];
foreach ($enrolledCourses as $enrolledCourse) {
    $title = strtolower($enrolledCourse['title']);
    if (strpos($title, 'web') !== false || strpos($title, 'html') !== false) {
        $skills['Web Development'] = ($skills['Web Development'] ?? 0) + ($enrolledCourse['progress_percentage'] ?? 0);
    }
    if (strpos($title, 'database') !== false || strpos($title, 'sql') !== false) {
        $skills['Database'] = ($skills['Database'] ?? 0) + ($enrolledCourse['progress_percentage'] ?? 0);
    }
    if (strpos($title, 'security') !== false || strpos($title, 'hacking') !== false) {
        $skills['Security'] = ($skills['Security'] ?? 0) + ($enrolledCourse['progress_percentage'] ?? 0);
    }
    if (strpos($title, 'programming') !== false || strpos($title, 'php') !== false || strpos($title, 'python') !== false) {
        $skills['Programming'] = ($skills['Programming'] ?? 0) + ($enrolledCourse['progress_percentage'] ?? 0);
    }
}

// Average skills
foreach ($skills as $skill => $total) {
    $skills[$skill] = min(100, $total / max(1, count($enrolledCourses)));
}

// Achievements
$achievements = [];
if ($completedCourses >= 1) {
    $achievements[] = ['title' => 'First Course', 'icon' => 'fa-graduation-cap', 'color' => 'primary'];
}
if (($quizStats['avg_score'] ?? 0) >= 90) {
    $achievements[] = ['title' => 'Quiz Master', 'icon' => 'fa-trophy', 'color' => 'warning'];
}
if ($certificates >= 3) {
    $achievements[] = ['title' => 'Certificate Pro', 'icon' => 'fa-certificate', 'color' => 'success'];
}
if ($streak >= 7) {
    $achievements[] = ['title' => 'Week Warrior', 'icon' => 'fa-fire', 'color' => 'danger'];
}

require_once dirname(__DIR__) . '/includes/universal_header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .profile-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 15px;
    }

    .profile-header-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .profile-cover {
        height: 250px;
        background: var(--primary-gradient);
        position: relative;
        display: flex;
        align-items: flex-end;
        padding: 30px;
    }

    .profile-avatar-container {
        position: relative;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        background: white;
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder {
        width: 100%;
        height: 100%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 4rem;
        font-weight: bold;
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #007bff;
        color: white;
        border: 3px solid white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .avatar-upload-btn:hover {
        background: #0056b3;
        transform: scale(1.1);
    }

    .profile-info {
        flex: 1;
        margin-left: 30px;
        color: white;
    }

    .profile-name {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .edit-name-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .edit-name-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .profile-meta {
        display: flex;
        gap: 30px;
        margin-top: 15px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0.9;
    }

    .profile-body {
        padding: 30px;
    }

    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--primary-gradient);
        color: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .stat-card.success {
        background: var(--success-gradient);
    }

    .stat-card.warning {
        background: var(--warning-gradient);
    }

    .stat-card.info {
        background: var(--info-gradient);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .stat-label {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .section-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .skill-item {
        margin-bottom: 20px;
    }

    .skill-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .skill-name {
        font-weight: 600;
        color: #333;
    }

    .skill-percentage {
        font-weight: bold;
        color: #667eea;
    }

    .skill-bar {
        height: 10px;
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }

    .skill-progress {
        height: 100%;
        background: var(--primary-gradient);
        border-radius: 10px;
        transition: width 1s ease;
    }

    .achievement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 20px;
    }

    .achievement-badge {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .achievement-badge:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .achievement-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
    }

    .achievement-icon.primary {
        background: var(--primary-gradient);
    }

    .achievement-icon.success {
        background: var(--success-gradient);
    }

    .achievement-icon.warning {
        background: var(--warning-gradient);
    }

    .achievement-icon.danger {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .activity-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }

    .activity-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .activity-content h6 {
        margin: 0 0 5px 0;
        font-weight: 600;
    }

    .activity-content small {
        color: #666;
    }

    .editable-field {
        position: relative;
    }

    .edit-overlay {
        position: absolute;
        top: 0;
        right: 0;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .editable-field:hover .edit-overlay {
        opacity: 1;
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    @media (max-width: 768px) {
        .profile-cover {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-info {
            margin-left: 0;
            margin-top: 20px;
        }

        .profile-meta {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header-card">
        <div class="profile-cover">
            <div class="profile-avatar-container">
                <?php if ($userData['profile_image']): ?>
                    <img src="../<?php echo htmlspecialchars($userData['profile_image']); ?>" alt="Profile"
                        class="profile-avatar" id="profileAvatar">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($userData['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <label for="avatarInput" class="avatar-upload-btn">
                    <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            </div>

            <div class="profile-info">
                <div class="profile-name">
                    <span id="displayName"><?php echo htmlspecialchars($userData['full_name']); ?></span>
                    <button class="edit-name-btn" onclick="editProfile()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($userData['email']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Joined <?php echo date('M Y', strtotime($userData['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-fire"></i>
                        <span><?php echo $streak; ?> Day Streak</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-body">
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalEnrolled; ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedCourses; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo round($quizStats['avg_score'] ?? 0); ?>%</div>
                    <div class="stat-label">Avg Quiz Score</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value"><?php echo $studyHours; ?>h</div>
                    <div class="stat-label">Study Time</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Skills Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-code text-primary"></i>
                    Skills & Expertise
                </div>
                <?php if (!empty($skills)): ?>
                    <?php foreach ($skills as $skillName => $skillLevel): ?>
                        <div class="skill-item">
                            <div class="skill-header">
                                <span class="skill-name"><?php echo htmlspecialchars($skillName); ?></span>
                                <span class="skill-percentage"><?php echo round($skillLevel); ?>%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: <?php echo round($skillLevel); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">Enroll in courses to build your skills!</p>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-history text-info"></i>
                    Recent Activity
                </div>
                <?php if (!empty($activities)): ?>
                    <?php foreach (array_slice($activities, 0, 5) as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="activity-content flex-grow-1">
                                <h6><?php echo htmlspecialchars($activity['title']); ?></h6>
                                <small><?php echo htmlspecialchars($activity['course']); ?> •
                                    <?php echo date('M d, Y', strtotime($activity['date'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Achievements -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-trophy text-warning"></i>
                    Achievements
                </div>
                <?php if (!empty($achievements)): ?>
                    <div class="achievement-grid">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="achievement-badge">
                                <div class="achievement-icon <?php echo $achievement['color']; ?>">
                                    <i class="fas <?php echo $achievement['icon']; ?>"></i>
                                </div>
                                <small class="fw-bold"><?php echo $achievement['title']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">Complete courses to earn achievements!</p>
                <?php endif; ?>
            </div>

            <!-- Progress Overview -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-chart-pie text-success"></i>
                    Progress Overview
                </div>
                <div class="text-center mb-3">
                    <div style="width: 150px; height: 150px; margin: 0 auto;">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="#e9ecef" stroke-width="3" />
                            <path class="circle"
                                stroke-dasharray="<?php echo $totalEnrolled > 0 ? round(($completedCourses / $totalEnrolled) * 100) : 0; ?>, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="#667eea" stroke-width="3" />
                            <text x="18" y="20.35" class="percentage" text-anchor="middle" font-size="8"
                                font-weight="bold" fill="#667eea">
                                <?php echo $totalEnrolled > 0 ? round(($completedCourses / $totalEnrolled) * 100) : 0; ?>%
                            </text>
                        </svg>
                    </div>
                    <p class="mt-3 mb-0 fw-bold">Course Completion Rate</p>
                </div>
                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <div class="text-center">
                        <div class="fw-bold text-primary"><?php echo $totalEnrolled; ?></div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-success"><?php echo $completedCourses; ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-warning"><?php echo $totalEnrolled - $completedCourses; ?></div>
                        <small class="text-muted">In Progress</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name"
                            value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio"
                            rows="3"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone"
                            value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Avatar upload
    document.getElementById('avatarInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('action', 'upload_avatar');

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileAvatar').src = data.path;
                        alert('Avatar updated successfully!');
                    } else {
                        alert(data.message || 'Upload failed');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Upload failed');
                });
        }
    });

    // Edit profile
    function editProfile() {
        new bootstrap.Modal(document.getElementById('editProfileModal')).show();
    }

    function saveProfile() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        formData.append('action', 'update_profile');

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Update failed');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Update failed');
            });
    }

    // Animate skill bars on load
    window.addEventListener('load', function () {
        document.querySelectorAll('.skill-progress').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>