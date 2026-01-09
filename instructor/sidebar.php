<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

// Create sidebar include file
$instructorId = $_SESSION['user_id'];

// Get notification counts
$conn = connectDB();

// Get unread discussions count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM discussions d 
    JOIN courses c ON d.course_id = c.id 
    WHERE c.instructor_id = ? AND d.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$unreadCount = $stmt->get_result()->fetch_assoc()['count'];

// Get pending replies count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM discussions d 
    JOIN courses c ON d.course_id = c.id 
    WHERE c.instructor_id = ? AND d.is_resolved = FALSE AND d.parent_id IS NULL
");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];
?>

<div class="list-group sidebar-modern">
    <a href="dashboard.php" class="list-group-item list-group-item-action">
        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        <span class="badge bg-primary float-end">New</span>
    </a>
    <a href="courses.php" class="list-group-item list-group-item-action">
        <i class="fas fa-graduation-cap me-2"></i> My Courses
        <span class="badge bg-primary float-end" id="courseCount">0</span>
    </a>
    <a href="students.php" class="list-group-item list-group-item-action">
        <i class="fas fa-users me-2"></i> Students
        <span class="badge bg-info float-end" id="studentCount">0</span>
    </a>
    <a href="quizzes.php" class="list-group-item list-group-item-action">
        <i class="fas fa-brain me-2"></i> Quizzes
        <span class="badge bg-warning float-end" id="quizCount">0</span>
    </a>
    <a href="discussions.php" class="list-group-item list-group-item-action active">
        <i class="fas fa-comments me-2"></i> Discussions
        <?php if ($pendingCount > 0): ?>
            <span class="badge bg-danger float-end"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="earnings.php" class="list-group-item list-group-item-action">
        <i class="fas fa-rupee-sign me-2"></i> Earnings
    </a>
    <a href="analytics.php" class="list-group-item list-group-item-action">
        <i class="fas fa-chart-bar me-2"></i> Analytics
    </a>
    <a href="profile.php" class="list-group-item list-group-item-action">
        <i class="fas fa-user me-2"></i> Profile
    </a>
    <hr class="my-3">
    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
</div>

<style>
.sidebar-modern {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    overflow: hidden;
}

.sidebar-modern .list-group-item {
    border: none;
    padding: 15px 20px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}

.sidebar-modern .list-group-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateX(5px);
}

.sidebar-modern .list-group-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.sidebar-modern .list-group-item.active:hover {
    transform: translateX(0);
}

.sidebar-modern .badge {
    border-radius: 20px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<script>
$(document).ready(function() {
    // Load dynamic counts
    $.ajax({
        url: '../api/instructor_stats.php',
        type: 'GET',
        success: function(data) {
            if (data.courses) $('#courseCount').text(data.courses);
            if (data.students) $('#studentCount').text(data.students);
            if (data.quizzes) $('#quizCount').text(data.quizzes);
        }
    });
});
</script>
