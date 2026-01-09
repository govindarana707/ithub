<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

$studentId = $_SESSION['user_id'];
$user = new User();

// Get student information
$studentInfo = $user->getUserById($studentId);

// Get student statistics
$conn = connectDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$enrolledCourses = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$quizAttempts = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$avgScore = $stmt->get_result()->fetch_assoc()['avg_score'];
$avgScore = $avgScore ? round($avgScore, 2) : 0;

// Get additional data for advanced features
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments WHERE student_id = ? AND status = 'completed'");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$completedCoursesCount = $stmt->get_result()->fetch_assoc()['total'];

// Get achievements (sample data for now)
$achievements = [
    [
        'title' => 'First Course',
        'description' => 'Completed your first course',
        'icon' => 'fas fa-graduation-cap',
        'icon_class' => 'badge-primary',
        'earned_at' => $studentInfo['created_at']
    ],
    [
        'title' => 'Quiz Master',
        'description' => 'Scored 100% on a quiz',
        'icon' => 'fas fa-trophy',
        'icon_class' => 'badge-warning',
        'earned_at' => date('Y-m-d', strtotime('-2 weeks'))
    ]
];

// Get skills (sample data)
$skills = [
    ['name' => 'HTML/CSS', 'level' => 85],
    ['name' => 'JavaScript', 'level' => 70],
    ['name' => 'PHP', 'level' => 60],
    ['name' => 'MySQL', 'level' => 75]
];

// Get goals (sample data)
$goals = [
    [
        'id' => 1,
        'title' => 'Complete Web Development Course',
        'description' => 'Finish all modules and get certificate',
        'target_date' => date('Y-m-d', strtotime('+1 month')),
        'completed' => false
    ],
    [
        'id' => 2,
        'title' => 'Learn Advanced JavaScript',
        'description' => 'Complete advanced JS topics',
        'target_date' => date('Y-m-d', strtotime('+2 months')),
        'completed' => false
    ]
];

// Calculate overall progress
$overallProgress = $enrolledCourses > 0 ? ($completedCoursesCount / $enrolledCourses) * 100 : 0;
$totalStudyTime = 45; // Sample data
$learningStreak = 7; // Sample data

$conn->close();
?>

    <style>
        /* Advanced Profile Styles */
        .profile-header {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .profile-cover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 300px;
            position: relative;
        }
        
        .profile-cover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
        }
        
        .profile-info {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            display: flex;
            align-items: flex-end;
            gap: 30px;
        }
        
        .profile-avatar {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-upload:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        
        .upload-btn {
            color: white;
            margin: 0;
            cursor: pointer;
        }
        
        .profile-details {
            flex: 1;
            color: white;
        }
        
        .profile-name {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .profile-email {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .profile-stats {
            display: flex;
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }
        
        .stat-value {
            display: block;
            font-size: 1.8rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .achievements-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .achievement-badge {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .achievement-badge:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .badge-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
        }
        
        .badge-info h6 {
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .badge-info p {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .badge-date {
            color: #999;
            font-size: 0.8rem;
        }
        
        .no-achievements {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .progress-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .progress-chart {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .progress-stats {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .progress-stats .stat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .progress-stats .stat-card:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            font-size: 1.2rem;
        }
        
        .stat-content h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .stat-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .skills-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .skills-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .skill-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .skill-info {
            flex: 1;
        }
        
        .skill-name {
            font-weight: bold;
            color: #333;
        }
        
        .skill-level {
            margin-top: 5px;
        }
        
        .skill-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .skill-progress {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        .skill-percentage {
            font-weight: bold;
            color: #007bff;
            min-width: 40px;
            text-align: right;
        }
        
        .goals-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .goal-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .goal-item:hover {
            background: #e9ecef;
        }
        
        .goal-checkbox {
            position: relative;
            margin-top: 2px;
        }
        
        .goal-checkbox input[type="checkbox"] {
            display: none;
        }
        
        .goal-checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .goal-checkbox input[type="checkbox"]:checked + .goal-checkmark {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .goal-content h6 {
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .goal-content p {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .actions-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .activity-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 0;
        }
        
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
        }
        
        .timeline-dot.primary {
            background: #007bff;
        }
        
        .timeline-dot.success {
            background: #28a745;
        }
        
        .timeline-line {
            width: 2px;
            height: 40px;
            background: #e9ecef;
            margin-left: 5px;
        }
        
        .timeline-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .timeline-content:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .timeline-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .timeline-icon.primary {
            background: #007bff;
        }
        
        .timeline-icon.success {
            background: #28a745;
        }
        
        .timeline-info h6 {
            margin: 0;
            font-weight: bold;
        }
        
        .timeline-body {
            color: #666;
            margin-top: 10px;
        }
        
        .no-activity {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .achievements-grid {
                grid-template-columns: 1fr;
            }
            
            .progress-stats {
                flex-direction: row;
                justify-content: space-between;
            }
        }
    </style>

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
                    <a href="certificates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Advanced Profile Header -->
                <div class="profile-header mb-4">
                    <div class="profile-cover">
                        <div class="profile-cover-overlay"></div>
                        <div class="profile-info">
                            <div class="profile-avatar">
                                <?php if ($studentInfo['profile_image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($studentInfo['profile_image']); ?>" alt="Profile" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="avatar-upload">
                                    <label for="profileImage" class="upload-btn">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" id="profileImage" name="profile_image" accept="image/*" style="display: none;">
                                    </label>
                                </div>
                            </div>
                            <div class="profile-details">
                                <h2 class="profile-name"><?php echo htmlspecialchars($studentInfo['full_name']); ?></h2>
                                <p class="profile-email"><?php echo htmlspecialchars($studentInfo['email']); ?></p>
                                <div class="profile-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span class="stat-value"><?php echo $enrolledCourses; ?></span>
                                        <span class="stat-label">Courses</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-trophy"></i>
                                        <span class="stat-value"><?php echo $completedCoursesCount; ?></span>
                                        <span class="stat-label">Completed</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        <span class="stat-value"><?php echo $avgScore; ?>%</span>
                                        <span class="stat-label">Avg Score</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Achievement Badges -->
                <div class="achievements-section mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-award me-2"></i>Achievements & Badges
                    </h3>
                    <div class="achievements-grid">
                        <?php if (!empty($achievements)): ?>
                            <?php foreach ($achievements as $achievement): ?>
                                <div class="achievement-badge">
                                    <div class="badge-icon <?php echo $achievement['icon_class']; ?>">
                                        <i class="<?php echo $achievement['icon']; ?>"></i>
                                    </div>
                                    <div class="badge-info">
                                        <h6><?php echo htmlspecialchars($achievement['title']); ?></h6>
                                        <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                                        <small class="badge-date"><?php echo date('M j, Y', strtotime($achievement['earned_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-achievements">
                                <div class="empty-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <h6>No achievements yet</h6>
                                <p>Complete courses and quizzes to earn badges!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Learning Progress Chart -->
                <div class="progress-section mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line me-2"></i>Learning Progress
                    </h3>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="progress-chart">
                                <canvas id="progressChart" height="300"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="progress-stats">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h4><?php echo round($overallProgress); ?>%</h4>
                                        <p>Overall Progress</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h4><?php echo $totalStudyTime; ?>h</h4>
                                        <p>Study Time</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h4><?php echo $learningStreak; ?></h4>
                                        <p>Day Streak</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Interests -->
                <div class="skills-section mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-cogs me-2"></i>Skills & Goals
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="dashboard-card">
                                <h4 class="card-title">Technical Skills</h4>
                                <div class="skills-container">
                                    <?php if (!empty($skills)): ?>
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="skill-item">
                                                <div class="skill-info">
                                                    <span class="skill-name"><?php echo htmlspecialchars($skill['name']); ?></span>
                                                    <div class="skill-level">
                                                        <div class="skill-bar">
                                                            <div class="skill-progress" style="width: <?php echo $skill['level']; ?>%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="skill-percentage"><?php echo $skill['level']; ?>%</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No skills added yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="dashboard-card">
                                <h4 class="card-title">Learning Goals</h4>
                                <div class="goals-container">
                                    <?php if (!empty($goals)): ?>
                                        <?php foreach ($goals as $goal): ?>
                                            <div class="goal-item">
                                                <div class="goal-checkbox">
                                                    <input type="checkbox" id="goal-<?php echo $goal['id']; ?>" <?php echo $goal['completed'] ? 'checked' : ''; ?>>
                                                    <label for="goal-<?php echo $goal['id']; ?>" class="goal-checkmark">
                                                        <i class="fas fa-check"></i>
                                                    </label>
                                                </div>
                                                <div class="goal-content">
                                                    <h6><?php echo htmlspecialchars($goal['title']); ?></h6>
                                                    <p><?php echo htmlspecialchars($goal['description']); ?></p>
                                                    <small class="text-muted">Target: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No goals set yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="actions-section mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h3>
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="my-courses.php" class="btn btn-info w-100">
                                <i class="fas fa-graduation-cap me-2"></i>My Courses
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="certificates.php" class="btn btn-success w-100">
                                <i class="fas fa-certificate me-2"></i>Certificates
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="activity-section">
                    <h3 class="section-title">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h3>
                    <div class="activity-timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <div class="timeline-dot primary"></div>
                                <div class="timeline-line"></div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-icon primary">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="timeline-info">
                                        <h6>Account Created</h6>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($studentInfo['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="timeline-body">
                                <p>Welcome to IT HUB! Your learning journey begins here.</p>
                            </div>
                        </div>
                        
                        <?php
                        // Get recent activity from logs
                        $conn = connectDB();
                        $stmt = $conn->prepare("
                            SELECT action, details, created_at 
                            FROM admin_logs 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $stmt->bind_param("i", $studentId);
                        $stmt->execute();
                        $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();
                        $conn->close();
                        
                        foreach ($activities as $activity):
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <div class="timeline-dot success"></div>
                                    <div class="timeline-line"></div>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-icon success">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="timeline-info">
                                            <h6><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-body">
                                    <p><?php echo htmlspecialchars($activity['details']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($activities)): ?>
                            <div class="no-activity">
                                <div class="empty-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h6>No recent activity</h6>
                                <p>Start learning to see your activity here!</p>
                            </div>
                        <?php endif; ?>
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
                <form id="editProfileForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($studentInfo['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($studentInfo['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($studentInfo['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($studentInfo['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Edit Profile Form
            $('#editProfileForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '../api/update_profile.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // Change Password Form
            $('#changePasswordForm').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                if ($('input[name="new_password"]').val() !== $('input[name="confirm_password"]').val()) {
                    alert('New passwords do not match');
                    return;
                }
                
                $.ajax({
                    url: '../api/change_password.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Password changed successfully');
                            $('#changePasswordModal').modal('hide');
                            $('#changePasswordForm')[0].reset();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // Profile Image Upload
            $('#profileImageUpload').change(function() {
                const file = this.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('profile_image', file);
                    
                    $.ajax({
                        url: '../api/upload_profile_image.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
    
    <!-- Chart.js for Progress Chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize Progress Chart
        const ctx = document.getElementById('progressChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7'],
                    datasets: [{
                        label: 'Learning Progress',
                        data: [10, 25, 35, 45, 55, 65, <?php echo round($overallProgress); ?>],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Animate Skills on Page Load
        $(document).ready(function() {
            // Animate skill bars
            setTimeout(() => {
                $('.skill-progress').each(function() {
                    const width = $(this).data('width') || $(this).css('width');
                    $(this).css('width', '0%');
                    setTimeout(() => {
                        $(this).css('width', width);
                    }, 100);
                });
            }, 500);
            
            // Animate achievement badges
            $('.achievement-badge').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, index * 100);
            });
            
            // Animate stat cards
            $('.progress-stats .stat-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateX(-20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateX(0)'
                    }, 600, 'easeOutCubic');
                }, index * 100);
            });
            
            // Profile image upload
            $('#profileImage').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.avatar-img').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Goal checkboxes
            $('.goal-checkbox input[type="checkbox"]').on('change', function() {
                const goalItem = $(this).closest('.goal-item');
                if ($(this).is(':checked')) {
                    goalItem.addClass('completed');
                } else {
                    goalItem.removeClass('completed');
                }
            });
            
            // Smooth scroll for timeline
            $('.timeline-item').each(function() {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100);
            });
        });
    </script>
