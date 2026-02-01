<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../includes/universal_header.php';

$discussion = new Discussion();
$course = new Course();

$studentId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    
    switch ($action) {
        case 'create_discussion':
            // Debug: Log the request
            error_log("Discussion creation request - User ID: " . $studentId);
            error_log("POST data: " . json_encode($_POST));
            
            $data = [
                'course_id' => intval($_POST['course_id']),
                'student_id' => $studentId,
                'title' => sanitize($_POST['title']),
                'content' => sanitize($_POST['content']),
                'lesson_id' => null,
                'pinned' => 0,
                'locked' => 0
            ];
            
            error_log("Processed data: " . json_encode($data));
            
            $result = $discussion->createDiscussion($data);
            error_log("Discussion result: " . json_encode($result));
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            } else {
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Discussion posted successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to post discussion: ' . $result['error'];
                }
            }
            break;
            
        case 'add_reply':
            $data = [
                'course_id' => intval($_POST['course_id']),
                'student_id' => $studentId,
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
    }
    
    header('Location: discussions.php');
    exit;
}

// Get enrolled courses for dropdown
$enrolledCourses = $course->getEnrolledCourses($studentId);

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
    // Get all discussions from enrolled courses
    $allDiscussions = [];
    foreach ($enrolledCourses as $enrolled) {
        $courseDiscussions = $discussion->getDiscussionsByCourse($enrolled['id'], 1, 100);
        $allDiscussions = array_merge($allDiscussions, $courseDiscussions);
    }
    
    // Sort by date
    usort($allDiscussions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Paginate
    $offset = ($page - 1) * $limit;
    $discussions = array_slice($allDiscussions, $offset, $limit);
}

// Get discussion stats
$stats = $discussion->getDiscussionStats();
?>

    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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
                    <a href="discussions.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="certificates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-certificate me-2"></i> Certificates
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
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <!-- Create Discussion Button -->
                <div class="mb-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDiscussionModal">
                        <i class="fas fa-plus me-2"></i>Start New Discussion
                    </button>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Course</label>
                                <select name="course" class="form-select">
                                    <option value="">All Courses</option>
                                    <?php foreach ($enrolledCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search discussions..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                                    <a href="discussions.php" class="btn btn-outline-secondary">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Discussions List -->
                <div class="dashboard-card">
                    <h3>Discussions</h3>
                    
                    <?php if (empty($discussions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5>No discussions found</h5>
                            <p class="text-muted">Be the first to start a discussion in your courses!</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDiscussionModal">
                                <i class="fas fa-plus me-2"></i>Start Discussion
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="discussions-list">
                            <?php foreach ($discussions as $discussion): ?>
                                <div class="card mb-3 <?php echo ($discussion['pinned'] ?? 0) ? 'border-warning' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <?php if ($discussion['profile_image']): ?>
                                                    <img src="../uploads/<?php echo $discussion['profile_image']; ?>" class="rounded-circle me-2" width="40" height="40">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($discussion['full_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0">
                                                        <?php echo htmlspecialchars($discussion['title']); ?>
                                                        <?php if ($discussion['pinned'] ?? 0): ?>
                                                            <i class="fas fa-thumbtack text-warning ms-2"></i>
                                                        <?php endif; ?>
                                                        <?php if ($discussion['locked'] ?? 0): ?>
                                                            <span class="badge bg-success ms-2">Locked</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        by <?php echo htmlspecialchars($discussion['full_name']); ?> • 
                                                        <?php echo date('M j, Y H:i', strtotime($discussion['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-eye me-1"></i><?php echo $discussion['views_count'] ?? 0; ?> views
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text"><?php echo substr(htmlspecialchars($discussion['content']), 0, 200); ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($discussion['course_title'] ?? 'Unknown Course'); ?>
                                            </small>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewDiscussion(<?php echo $discussion['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Discussion
                                            </button>
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

    <!-- Create Discussion Modal -->
    <div class="modal fade" id="createDiscussionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start New Discussion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="ajax-form">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_discussion">
                        
                        <div class="mb-3">
                            <label class="form-label">Course *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select a course</option>
                                <?php foreach ($enrolledCourses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter discussion title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content *</label>
                            <textarea name="content" class="form-control" rows="6" placeholder="Describe your question or topic in detail..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Post Discussion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Discussion Details Modal -->
    <div class="modal fade" id="discussionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Discussion Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="discussionDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Handle AJAX form submissions
            $('.ajax-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();
                
                console.log('Form submission started');
                console.log('Form data:', form.serialize());
                
                // Show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Posting...');
                
                $.ajax({
                    url: form.attr('action') || window.location.href,
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    beforeSend: function(xhr) {
                        console.log('Sending AJAX request...');
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        if (response.success) {
                            // Close modal and refresh page
                            $('#createDiscussionModal').modal('hide');
                            location.reload();
                        } else {
                            // Show error message
                            alert('Error: ' + (response.message || response.error || 'Failed to post discussion. Please try again.'));
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', status, error);
                        console.log('Response text:', xhr.responseText);
                        
                        // Try to parse error response
                        try {
                            var response = JSON.parse(xhr.responseText);
                            alert('Error: ' + (response.message || response.error || 'Failed to post discussion. Please try again.'));
                        } catch(e) {
                            alert('Error posting discussion. Please try again.\n\nDetails: ' + xhr.responseText);
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            window.viewDiscussion = function(discussionId) {
                $('#discussionDetailsContent').html(`
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
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
                        $('#discussionDetailsContent').html('<div class="alert alert-danger">Error loading discussion details</div>');
                    }
                });
            };
        });
    </script>
