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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_reply':
            $data = [
                'course_id' => intval($_POST['course_id']),
                'student_id' => $instructorId,
                'title' => '', // Replies don't have titles
                'content' => sanitize($_POST['content']),
                'parent_id' => intval($_POST['parent_id'])
            ];
            
            $result = $discussion->createDiscussion($data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Reply posted successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to post reply: ' . $result['error'];
            }
            break;
            
        case 'toggle_pin':
            $discussionId = intval($_POST['discussion_id']);
            $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
            $result = $discussion->togglePin($discussionId, $isPinned);
            if ($result) {
                $_SESSION['success_message'] = 'Discussion pin status updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update pin status';
            }
            break;
            
        case 'toggle_resolve':
            $discussionId = intval($_POST['discussion_id']);
            $isResolved = isset($_POST['is_resolved']) ? 1 : 0;
            $result = $discussion->toggleResolve($discussionId, $isResolved);
            if ($result) {
                $_SESSION['success_message'] = 'Discussion resolve status updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update resolve status';
            }
            break;
    }
    
    header('Location: discussions.php');
    exit;
}

// Get instructor's courses for dropdown
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ? AND status = 'published' ORDER BY title");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$instructorCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get discussions
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$courseFilter = $_GET['course'] ?? '';
$searchQuery = $_GET['search'] ?? '';

if ($courseFilter && $searchQuery) {
    $discussions = $discussion->searchDiscussions($courseFilter, $searchQuery, $page, $limit);
} elseif ($courseFilter) {
    $discussions = $discussion->getDiscussionsByCourse($courseFilter, $page, $limit);
} else {
    $discussions = $discussion->getInstructorDiscussions($instructorId, $page, $limit);
}

// Get discussion stats
$stats = $discussion->getDiscussionStats();
?>

    <div class="container-fluid py-4">
        <style>
            .stats-card {
                background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-dark, #0056b3) 100%);
                border: none;
                border-radius: 15px;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .stats-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            .stats-card.warning {
                background: linear-gradient(135deg, var(--bs-warning) 0%, #e08e0b 100%);
            }
            .stats-card.success {
                background: linear-gradient(135deg, var(--bs-success) 0%, #198754 100%);
            }
            .discussion-card {
                border: none;
                border-radius: 12px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .discussion-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }
            .discussion-card.pinned {
                border-left: 4px solid var(--bs-warning);
                background: linear-gradient(to right, rgba(255,193,7,0.05), transparent);
            }
            .user-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid var(--bs-primary);
            }
            .user-avatar-placeholder {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark, #0056b3));
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 18px;
                border: 2px solid var(--bs-primary);
            }
            .btn-group-sm .btn {
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .btn-group-sm .btn:hover {
                transform: translateY(-1px);
            }
            .filter-card {
                border: none;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .empty-state {
                padding: 60px 20px;
                text-align: center;
            }
            .empty-state i {
                font-size: 4rem;
                color: var(--bs-gray-300);
                margin-bottom: 1.5rem;
            }
            .badge-status {
                font-size: 0.75rem;
                padding: 0.35rem 0.65rem;
                border-radius: 20px;
            }
            .discussion-meta {
                font-size: 0.85rem;
                color: var(--bs-gray-600);
            }
            .discussion-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: var(--bs-gray-800);
                margin-bottom: 0.5rem;
            }
            .discussion-content {
                color: var(--bs-gray-600);
                line-height: 1.5;
            }
            .stats-number {
                font-size: 2.5rem;
                font-weight: bold;
                line-height: 1;
            }
            .stats-label {
                font-size: 0.9rem;
                opacity: 0.9;
            }
        </style>
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                        <span class="badge bg-primary float-end">0</span>
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                        <span class="badge bg-info float-end">0</span>
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-brain me-2"></i> Quizzes
                        <span class="badge bg-warning float-end">0</span>
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-comments me-2"></i> Discussions
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
                    <a href="../logout.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Course Discussions</h1>
                    <div>
                        <span class="badge bg-success">Instructor</span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?php echo count($discussions); ?></div>
                                        <div class="stats-label">Total Discussions</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-comments fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?php echo $stats['unresolved'] ?? 0; ?></div>
                                        <div class="stats-label">Unresolved</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?php echo $stats['total_replies'] ?? 0; ?></div>
                                        <div class="stats-label">Total Replies</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-reply fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card filter-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filter Discussions
                        </h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Course</label>
                                <select name="course" class="form-select form-select-lg">
                                    <option value="">All Courses</option>
                                    <?php foreach ($instructorCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Search</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control" placeholder="Search discussions..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="discussions.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Discussions List -->
                <div class="card border-0 bg-transparent">
                    <div class="card-header bg-transparent border-bottom-0">
                        <h3 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Discussions from Your Courses
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($discussions)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h4 class="text-muted mb-3">No discussions found</h4>
                                <p class="text-muted mb-4">Students haven't started any discussions in your courses yet.</p>
                                <div class="text-center">
                                    <i class="fas fa-lightbulb fa-2x text-warning mb-3"></i>
                                    <p class="text-muted">Encourage your students to ask questions and engage in discussions!</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="discussions-list">
                                <?php foreach ($discussions as $discussion): ?>
                                    <div class="card discussion-card mb-3 <?php echo $discussion['is_pinned'] ? 'pinned' : ''; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($discussion['profile_image'])): ?>
                                                        <img src="../uploads/<?php echo $discussion['profile_image']; ?>" class="user-avatar me-3" alt="Avatar">
                                                    <?php else: ?>
                                                        <div class="user-avatar-placeholder me-3">
                                                            <?php echo strtoupper(substr($discussion['student_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="discussion-title">
                                                            <?php echo htmlspecialchars($discussion['title']); ?>
                                                            <?php if ($discussion['is_pinned']): ?>
                                                                <i class="fas fa-thumbtack text-warning ms-2"></i>
                                                            <?php endif; ?>
                                                            <?php if ($discussion['is_resolved']): ?>
                                                                <span class="badge bg-success badge-status ms-2">
                                                                    <i class="fas fa-check-circle me-1"></i>Resolved
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="discussion-meta">
                                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($discussion['student_name']); ?>
                                                            <span class="mx-2">•</span>
                                                            <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($discussion['course_title'] ?? 'Unknown Course'); ?>
                                                            <span class="mx-2">•</span>
                                                            <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($discussion['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="badge bg-primary badge-status mb-2">
                                                        <i class="fas fa-comment me-1"></i><?php echo $discussion['reply_count']; ?> replies
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="discussion-content mb-3">
                                                <?php echo substr(htmlspecialchars($discussion['content']), 0, 200); ?>...
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="discussion-meta">
                                                    <small class="text-muted">
                                                        <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($discussion['course_title'] ?? 'Unknown Course'); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="viewDiscussion(<?php echo $discussion['id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>View & Reply
                                                    </button>
                                                    <button class="btn btn-outline-warning btn-sm" onclick="togglePin(<?php echo $discussion['id']; ?>, <?php echo $discussion['is_pinned'] ? 0 : 1; ?>)">
                                                        <i class="fas fa-thumbtack me-1"></i><?php echo $discussion['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                                    </button>
                                                    <button class="btn btn-outline-success btn-sm" onclick="toggleResolve(<?php echo $discussion['id']; ?>, <?php echo $discussion['is_resolved'] ? 0 : 1; ?>)">
                                                        <i class="fas fa-check me-1"></i><?php echo $discussion['is_resolved'] ? 'Reopen' : 'Resolve'; ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discussion Details Modal -->
    <div class="modal fade" id="discussionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-comments me-2"></i>Discussion Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="discussionDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading discussion details...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Form Template (hidden) -->
    <form id="replyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="add_reply">
        <input type="hidden" name="course_id" id="reply_course_id">
        <input type="hidden" name="parent_id" id="reply_parent_id">
        <div class="card border-0 bg-light">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-reply me-2"></i>Your Reply
                </h6>
                <div class="mb-3">
                    <textarea name="content" class="form-control" rows="4" placeholder="Write your reply..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i>Post Reply
                </button>
            </div>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            window.viewDiscussion = function(discussionId) {
                $('#discussionDetailsContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading discussion details...</p>
                    </div>
                `);
                
                $('#discussionDetailsModal').modal('show');
                
                $.ajax({
                    url: '../api/get_discussion_details.php',
                    type: 'GET',
                    data: { discussion_id: discussionId },
                    success: function(data) {
                        $('#discussionDetailsContent').html(data);
                    },
                    error: function() {
                        $('#discussionDetailsContent').html(`
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading discussion details
                            </div>
                        `);
                    }
                });
            };
            
            window.togglePin = function(discussionId, isPinned) {
                const action = isPinned ? 'pinning' : 'unpinning';
                if (!confirm(`Are you sure you want to ${action} this discussion?`)) {
                    return;
                }
                
                $.ajax({
                    url: 'discussions.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_pin',
                        discussion_id: discussionId,
                        is_pinned: isPinned
                    },
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        alert('Error updating pin status');
                    }
                });
            };
            
            window.toggleResolve = function(discussionId, isResolved) {
                const action = isResolved ? 'resolving' : 'reopening';
                if (!confirm(`Are you sure you want to ${action} this discussion?`)) {
                    return;
                }
                
                $.ajax({
                    url: 'discussions.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_resolve',
                        discussion_id: discussionId,
                        is_resolved: isResolved
                    },
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        alert('Error updating resolve status');
                    }
                });
            };
        });
    </script>
</body>
</html>
