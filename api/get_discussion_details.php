<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';

// Authentication check
if (!isLoggedIn()) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'];

// Role-based access control
if ($userRole !== 'student' && $userRole !== 'instructor') {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

// Get discussion ID
$discussionId = intval($_GET['discussion_id'] ?? 0);
if ($discussionId <= 0) {
    http_response_code(400);
    echo 'Invalid discussion ID';
    exit;
}

$discussion = new Discussion();

try {
    // Get discussion details
    $discussionDetails = $discussion->getDiscussionById($discussionId);
    
    if (!$discussionDetails) {
        echo '<div class="alert alert-warning">Discussion not found</div>';
        exit;
    }
    
    // Check access based on user role
    $conn = connectDB();
    $hasAccess = false;
    
    if ($userRole === 'student') {
        // Check if student is enrolled in the course
        $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $discussionDetails['course_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $hasAccess = $result['enrolled'] > 0;
    } elseif ($userRole === 'instructor') {
        // Check if instructor owns the course
        $stmt = $conn->prepare("SELECT COUNT(*) as owns FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $discussionDetails['course_id'], $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $hasAccess = $result['owns'] > 0;
    }
    
    if (!$hasAccess) {
        echo '<div class="alert alert-danger">You do not have access to this discussion</div>';
        exit;
    }
    
    // Get replies
    $replies = $discussion->getDiscussionReplies($discussionId);
    
    // Format discussion details for display
    ?>
    <div class="discussion-details">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="mb-2"><?php echo htmlspecialchars($discussionDetails['title']); ?></h4>
                <div class="text-muted small">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($discussionDetails['full_name']); ?>
                    <i class="fas fa-book me-1 ms-2"></i><?php echo htmlspecialchars($discussionDetails['course_title'] ?? 'Unknown Course'); ?>
                    <i class="fas fa-clock me-1 ms-2"></i><?php echo date('M j, Y H:i', strtotime($discussionDetails['created_at'])); ?>
                    <?php if ($discussionDetails['is_pinned']): ?>
                        <span class="badge bg-warning ms-2"><i class="fas fa-thumbtack me-1"></i>Pinned</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <?php if ($discussionDetails['student_id'] == $userId): ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="editDiscussion(<?php echo $discussionDetails['id']; ?>)">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDiscussion(<?php echo $discussionDetails['id']; ?>)">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                <?php elseif ($userRole === 'instructor'): ?>
                    <button class="btn btn-sm btn-outline-warning" onclick="togglePin(<?php echo $discussionDetails['id']; ?>, <?php echo $discussionDetails['is_pinned'] ? 0 : 1; ?>)">
                        <i class="fas fa-thumbtack me-1"></i><?php echo $discussionDetails['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="toggleResolve(<?php echo $discussionDetails['id']; ?>, <?php echo $discussionDetails['is_resolved'] ? 0 : 1; ?>)">
                        <i class="fas fa-check me-1"></i><?php echo $discussionDetails['is_resolved'] ? 'Reopen' : 'Resolve'; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="discussion-content mb-4">
            <p><?php echo nl2br(htmlspecialchars($discussionDetails['content'])); ?></p>
        </div>
        
        <?php if (!empty($replies)): ?>
            <div class="replies-section">
                <h5 class="mb-3">
                    <i class="fas fa-comments me-2"></i>Replies (<?php echo count($replies); ?>)
                </h5>
                <?php foreach ($replies as $reply): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($reply['full_name']); ?></strong>
                                    <small class="text-muted ms-2"><?php echo date('M j, Y H:i', strtotime($reply['created_at'])); ?></small>
                                </div>
                                <?php if ($reply['student_id'] == $userId || $userRole === 'instructor'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editReply(<?php echo $reply['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Reply Form -->
        <div class="reply-form mt-4">
            <h5 class="mb-3"><i class="fas fa-reply me-2"></i>Add Reply</h5>
            <form method="POST" class="ajax-form" action="../api/discussion_reply.php">
                <input type="hidden" name="discussion_id" value="<?php echo $discussionId; ?>">
                <input type="hidden" name="action" value="create_reply">
                <div class="mb-3">
                    <textarea name="content" class="form-control" rows="3" placeholder="Write your reply..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i>Post Reply
                </button>
            </form>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading discussion details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
