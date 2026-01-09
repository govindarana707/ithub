<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';
require_once '../models/Course.php';

requireInstructor();

require_once '../includes/universal_header.php';

$discussion = new Discussion();
$course = new Course();
$instructorId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    switch ($action) {
        case 'add_reply':
            $data = [
                'course_id' => intval($_POST['course_id'] ?? 0),
                'student_id' => $instructorId,
                'title' => '',
                'content' => sanitize($_POST['content'] ?? ''),
                'parent_id' => intval($_POST['parent_id'] ?? 0)
            ];
            
            if (!empty($data['content'])) {
                $result = $discussion->createDiscussion($data);
                $_SESSION['success_message'] = $result['success'] ? 'Reply posted successfully!' : 'Failed to post reply';
            } else {
                $_SESSION['error_message'] = 'Reply content cannot be empty';
            }
            break;
            
        case 'toggle_pin':
            $discussionId = intval($_POST['discussion_id'] ?? 0);
            $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
            if ($discussionId > 0) {
                $result = $discussion->togglePin($discussionId, $isPinned);
                $_SESSION['success_message'] = $result ? 'Discussion pin status updated!' : 'Failed to update pin status';
            }
            break;
            
        case 'toggle_resolve':
            $discussionId = intval($_POST['discussion_id'] ?? 0);
            $isResolved = isset($_POST['is_resolved']) ? 1 : 0;
            if ($discussionId > 0) {
                $result = $discussion->toggleResolve($discussionId, $isResolved);
                $_SESSION['success_message'] = $result ? 'Discussion resolve status updated!' : 'Failed to update resolve status';
            }
            break;
    }
    
    header('Location: discussions.php');
    exit;
}

// Get data
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$instructorCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page = max(1, intval($_GET['page'] ?? 1));
$courseFilter = intval($_GET['course'] ?? 0);
$searchQuery = sanitize($_GET['search'] ?? '');

if ($courseFilter && $searchQuery) {
    $discussions = $discussion->searchDiscussions($courseFilter, $searchQuery, $page, 20);
} elseif ($courseFilter) {
    $discussions = $discussion->getDiscussionsByCourse($courseFilter, $page, 20);
} else {
    $discussions = $discussion->getInstructorDiscussions($instructorId, $page, 20);
}

$stats = $discussion->getDiscussionStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussions Management - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
        }

        .main-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            min-height: 100vh;
        }

        .main-content {
            padding: 30px;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .header-subtitle {
            color: var(--gray-500);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            color: white;
        }

        .stat-icon.success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-500);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .filters-section {
            padding: 30px;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-control-modern {
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-modern {
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--gray-200);
            color: var(--gray-700);
        }

        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        .discussions-list {
            padding: 30px;
        }

        .discussion-item {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
        }

        .discussion-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-left-color: var(--primary);
        }

        .discussion-item.pinned {
            border-left-color: var(--warning);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%);
        }

        .discussion-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid var(--gray-200);
        }

        .user-avatar-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            margin-right: 15px;
            border: 3px solid var(--gray-200);
        }

        .discussion-meta {
            flex: 1;
        }

        .discussion-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .discussion-info {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .discussion-content {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .discussion-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-primary {
            background: var(--primary);
            color: white;
        }

        .badge-success {
            background: var(--success);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray-500);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 20px;
        }

        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(180deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3">
                    <div class="list-group">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="courses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                        </a>
                        <a href="create-course.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus me-2"></i> Create Course
                        </a>
                        <a href="students.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Students
                        </a>
                        <a href="discussions.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-comments me-2"></i> Discussions
                        </a>
                        <a href="analytics.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-line me-2"></i> Analytics
                        </a>
                        <a href="earnings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-rupee-sign me-2"></i> Earnings
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="../logout.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9">
                    <div class="main-content">
                        <!-- Page Header -->
                        <div class="page-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1 class="header-title">Discussions Management</h1>
                                    <p class="header-subtitle">Manage discussions from your courses</p>
                                </div>
                                <div>
                                    <span class="badge badge-primary">Instructor</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon primary">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="stat-value"><?php echo count($discussions); ?></div>
                                <div class="stat-label">Total Discussions</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-value"><?php echo $stats['unresolved'] ?? 0; ?></div>
                                <div class="stat-label">Unresolved</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-value"><?php echo $stats['total_replies'] ?? 0; ?></div>
                                <div class="stat-label">Total Replies</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-value"><?php echo round(($stats['resolved'] ?? 0) / max(1, count($discussions)) * 100, 1); ?>%</div>
                                <div class="stat-label">Resolution Rate</div>
                            </div>
                        </div>

                        <!-- Main Content Card -->
                        <div class="content-card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-comments me-2"></i>
                                    Recent Discussions
                                </h2>
                            </div>

                            <!-- Filters -->
                            <div class="filters-section">
                                <form method="GET" class="filter-row">
                                    <div>
                                        <label class="form-label d-block mb-2">Course</label>
                                        <select name="course" class="form-control-modern w-100">
                                            <option value="">All Courses</option>
                                            <?php foreach ($instructorCourses as $course): ?>
                                                <option value="<?php echo $course['id']; ?>" <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label d-block mb-2">Search</label>
                                        <input type="text" name="search" class="form-control-modern w-100" 
                                               placeholder="Search discussions..." 
                                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn-modern btn-primary">
                                            <i class="fas fa-search me-2"></i>Filter
                                        </button>
                                        <a href="discussions.php" class="btn-modern btn-outline">
                                            <i class="fas fa-times me-2"></i>Clear
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- Discussions List -->
                            <div class="discussions-list">
                                <?php if (empty($discussions)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-comments empty-icon"></i>
                                        <h3>No discussions found</h3>
                                        <p>Students haven't started any discussions in your courses yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($discussions as $discussion): ?>
                                        <div class="discussion-item <?php echo $discussion['is_pinned'] ? 'pinned' : ''; ?>">
                                            <div class="discussion-header">
                                                <?php if (!empty($discussion['profile_image'])): ?>
                                                    <img src="../uploads/<?php echo $discussion['profile_image']; ?>" 
                                                         class="user-avatar" alt="User Avatar">
                                                <?php else: ?>
                                                    <div class="user-avatar-placeholder">
                                                        <?php echo strtoupper(substr($discussion['student_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="discussion-meta">
                                                    <div class="discussion-title">
                                                        <?php echo htmlspecialchars($discussion['title']); ?>
                                                        <?php if ($discussion['is_pinned']): ?>
                                                            <i class="fas fa-thumbtack text-warning ms-2"></i>
                                                        <?php endif; ?>
                                                        <?php if ($discussion['is_resolved']): ?>
                                                            <span class="badge badge-success ms-2">Resolved</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="discussion-info">
                                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($discussion['student_name']); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($discussion['course_title'] ?? 'Unknown Course'); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($discussion['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="discussion-content">
                                                <?php echo substr(htmlspecialchars($discussion['content']), 0, 200); ?>...
                                            </div>
                                            
                                            <div class="discussion-actions">
                                                <span class="badge badge-primary me-2">
                                                    <i class="fas fa-comment me-1"></i><?php echo $discussion['reply_count']; ?> replies
                                                </span>
                                                <button class="btn-modern btn-outline" onclick="viewDiscussion(<?php echo $discussion['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                                <button class="btn-modern btn-outline" onclick="togglePin(<?php echo $discussion['id']; ?>, <?php echo $discussion['is_pinned'] ? 0 : 1; ?>)">
                                                    <i class="fas fa-thumbtack me-1"></i><?php echo $discussion['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                                </button>
                                                <button class="btn-modern btn-outline" onclick="toggleResolve(<?php echo $discussion['id']; ?>, <?php echo $discussion['is_resolved'] ? 0 : 1; ?>)">
                                                    <i class="fas fa-check me-1"></i><?php echo $discussion['is_resolved'] ? 'Reopen' : 'Resolve'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="discussionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Discussion Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
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
            <?php if (isset($_SESSION['success_message'])): ?>
                showNotification('<?php echo $_SESSION['success_message']; ?>', 'success');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                showNotification('<?php echo $_SESSION['error_message']; ?>', 'error');
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            window.viewDiscussion = function(discussionId) {
                $('#modalContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                
                $('#discussionModal').modal('show');
                
                $.ajax({
                    url: '../api/get_discussion_details.php',
                    type: 'GET',
                    data: { discussion_id: discussionId },
                    success: function(data) {
                        $('#modalContent').html(data);
                    },
                    error: function() {
                        $('#modalContent').html(`
                            <div class="alert alert-danger">
                                Error loading discussion details
                            </div>
                        `);
                    }
                });
            };
            
            window.togglePin = function(discussionId, isPinned) {
                $.ajax({
                    url: 'discussions.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_pin',
                        discussion_id: discussionId,
                        is_pinned: isPinned
                    },
                    success: function() {
                        showNotification('Pin status updated successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function() {
                        showNotification('Error updating pin status', 'error');
                    }
                });
            };
            
            window.toggleResolve = function(discussionId, isResolved) {
                $.ajax({
                    url: 'discussions.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_resolve',
                        discussion_id: discussionId,
                        is_resolved: isResolved
                    },
                    success: function() {
                        showNotification('Resolve status updated successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function() {
                        showNotification('Error updating resolve status', 'error');
                    }
                });
            };
            
            window.showNotification = function(message, type = 'info') {
                const alertClass = type === 'success' ? 'alert-success' : 
                                 type === 'error' ? 'alert-danger' : 'alert-info';
                
                const notification = $(`
                    <div class="alert ${alertClass} position-fixed top-0 end-0 m-3" style="z-index: 9999;">
                        ${message}
                    </div>
                `);
                
                $('body').append(notification);
                
                setTimeout(() => {
                    notification.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 3000);
            };
        });
    </script>
</body>
</html>
