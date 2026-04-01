<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/session_helper.php';
require_once '../models/Discussion.php';
require_once '../models/Course.php';

// Initialize session
initializeSession();

if (!isUserLoggedIn()) {
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
            // Enhanced validation and security
            $courseId = intval($_POST['course_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            
            // Validate required fields
            if ($courseId <= 0 || empty($title) || empty($content)) {
                $error = 'Missing required fields';
                if ($courseId <= 0) $error = 'Please select a course';
                elseif (empty($title)) $error = 'Discussion title is required';
                elseif (empty($content)) $error = 'Discussion content is required';
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                } else {
                    $_SESSION['error_message'] = $error;
                }
                break;
            }
            
            // Validate content length
            if (strlen($title) > 255) {
                $error = 'Title is too long (maximum 255 characters)';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                } else {
                    $_SESSION['error_message'] = $error;
                }
                break;
            }
            
            if (strlen($content) > 5000) {
                $error = 'Content is too long (maximum 5000 characters)';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                } else {
                    $_SESSION['error_message'] = $error;
                }
                break;
            }
            
            // Check if student is enrolled in the course
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments_new WHERE user_id = ? AND course_id = ? AND status = 'active'");
            $stmt->bind_param("ii", $studentId, $courseId);
            $stmt->execute();
            $enrollment = $stmt->get_result()->fetch_assoc();
            
            if ($enrollment['enrolled'] == 0) {
                $error = 'You are not enrolled in this course';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                } else {
                    $_SESSION['error_message'] = $error;
                }
                break;
            }
            
            $data = [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'title' => $title,
                'content' => $content,
                'lesson_id' => null,
                'pinned' => 0,
                'locked' => 0
            ];
            
            $result = $discussion->createDiscussion($data);
            
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
            $content = sanitize($_POST['content'] ?? '');
            $parentId = intval($_POST['parent_id'] ?? 0);
            $courseId = intval($_POST['course_id'] ?? 0);
            
            // Validate required fields
            if ($courseId <= 0 || $parentId <= 0 || empty($content)) {
                $error = 'Missing required fields';
                if ($courseId <= 0) $error = 'Invalid course';
                elseif ($parentId <= 0) $error = 'Invalid discussion';
                elseif (empty($content)) $error = 'Reply content is required';
                
                $_SESSION['error_message'] = 'Failed to post reply: ' . $error;
                break;
            }
            
            // Validate content length
            if (strlen($content) > 2000) {
                $_SESSION['error_message'] = 'Reply is too long (maximum 2000 characters)';
                break;
            }
            
            // Check if student is enrolled in the course
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'");
            $stmt->bind_param("ii", $studentId, $courseId);
            $stmt->execute();
            $enrollment = $stmt->get_result()->fetch_assoc();
            
            if ($enrollment['enrolled'] == 0) {
                $_SESSION['error_message'] = 'You are not enrolled in this course';
                break;
            }
            
            // Check if parent discussion exists and is not locked
            $parentDiscussion = $discussion->getDiscussionById($parentId);
            if (!$parentDiscussion) {
                $_SESSION['error_message'] = 'Discussion not found';
                break;
            }
            
            if ($parentDiscussion['locked'] ?? 0) {
                $_SESSION['error_message'] = 'This discussion is locked and cannot be replied to';
                break;
            }
            
            $data = [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'title' => '', // Replies don't have titles
                'content' => $content,
                'parent_id' => $parentId
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussions - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        /* Enhanced Discussion Cards */
        .discussion-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid #667eea;
            border-radius: var(--border-radius-modern);
            overflow: hidden;
        }
        
        .discussion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .discussion-card.border-warning {
            border-left-color: #f59e0b;
        }
        
        .discussion-card.border-warning:hover {
            border-left-color: #d97706;
        }
        
        .discussion-content {
            line-height: 1.6;
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
        
        /* Modal Enhancements */
        .modal-header.bg-primary {
            background: var(--primary-gradient) !important;
        }
        
        .modal-content {
            border-radius: var(--border-radius-modern);
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
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
        
        /* Pagination Enhancements */
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
            color: #667eea;
            border: none;
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            color: white;
        }
        
        .pagination .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-1px);
        }
        
        /* Alert Enhancements */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        /* Badge Enhancements */
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        /* Staggered Animation for Discussion Cards */
        .discussion-card {
            animation: fadeInUp 0.6s ease both;
        }
        
        .discussion-card:nth-child(1) { animation-delay: 0.1s; }
        .discussion-card:nth-child(2) { animation-delay: 0.2s; }
        .discussion-card:nth-child(3) { animation-delay: 0.3s; }
        .discussion-card:nth-child(4) { animation-delay: 0.4s; }
        .discussion-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .modern-card .card-body {
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
                        <h1 class="mb-3">Course Discussions 💬</h1>
                        <p class="mb-0">Connect with peers and engage in meaningful conversations</p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                    </div>
                </div>

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
                
                <!-- Create Discussion Button -->
                <div class="mb-4">
                    <button class="btn btn-primary btn-modern btn-lg" data-bs-toggle="modal" data-bs-target="#createDiscussionModal">
                        <i class="fas fa-plus me-2"></i>Start New Discussion
                    </button>
                </div>

                <!-- Enhanced Filters -->
                <div class="modern-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2 text-primary"></i>
                            Filter Discussions
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Course</label>
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
                                <label class="form-label fw-semibold">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search discussions..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="discussions.php" class="btn btn-outline-secondary btn-modern">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Discussions List -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-comments me-2 text-primary"></i>
                                Discussions
                            </h5>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo count($discussions); ?> discussions
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                    
                    <?php if (empty($discussions)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-comments fa-4x text-muted" style="opacity: 0.4;"></i>
                            </div>
                            <h5 class="fw-bold text-muted mb-3">No discussions found</h5>
                            <p class="text-muted mb-4">Be the first to start a discussion in your courses!</p>
                            <button class="btn btn-primary-modern btn-modern btn-lg" data-bs-toggle="modal" data-bs-target="#createDiscussionModal">
                                <i class="fas fa-plus me-2"></i>Start Discussion
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="discussions-list">
                            <?php foreach ($discussions as $discussion): ?>
                                <div class="card mb-3 border-0 shadow-sm discussion-card <?php echo ($discussion['pinned'] ?? 0) ? 'border-warning border-2' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <?php if ($discussion['profile_image']): ?>
                                                    <img src="../uploads/<?php echo $discussion['profile_image']; ?>" class="rounded-circle me-3" width="48" height="48" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-weight: bold;">
                                                        <?php echo strtoupper(substr($discussion['full_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">
                                                        <?php echo htmlspecialchars($discussion['title']); ?>
                                                        <?php if ($discussion['pinned'] ?? 0): ?>
                                                            <span class="badge bg-warning text-dark ms-2">
                                                                <i class="fas fa-thumbtack me-1"></i>Pinned
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($discussion['locked'] ?? 0): ?>
                                                            <span class="badge bg-success ms-2">
                                                                <i class="fas fa-lock me-1"></i>Locked
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div class="text-muted small">
                                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($discussion['full_name']); ?> • 
                                                        <i class="fas fa-book me-1 ms-2"></i><?php echo htmlspecialchars($discussion['course_title'] ?? 'Unknown Course'); ?> • 
                                                        <i class="fas fa-clock me-1 ms-2"></i><?php echo date('M j, Y H:i', strtotime($discussion['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-eye me-1"></i><?php echo $discussion['views_count'] ?? 0; ?> views
                                                    <?php if ($discussion['replies_count'] > 0): ?>
                                                        <span class="ms-3"><i class="fas fa-comment me-1"></i><?php echo $discussion['replies_count']; ?> replies</span>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn btn-primary btn-modern" onclick="viewDiscussion(<?php echo $discussion['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View Discussion
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="discussion-content">
                                            <p class="mb-0 text-muted"><?php echo substr(htmlspecialchars($discussion['content']), 0, 300); ?>...</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Enhanced Pagination -->
                        <?php if (count($discussions) >= $limit): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav>
                                <ul class="pagination">
                                    <?php 
                                    $totalPages = ceil(count($discussions) / $limit);
                                    for ($i = 1; $i <= $totalPages; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&course=<?php echo urlencode($courseFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Discussion Modal -->
    <div class="modal fade" id="createDiscussionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Start New Discussion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="ajax-form">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_discussion">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Course *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select a course</option>
                                <?php foreach ($enrolledCourses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter discussion title" required maxlength="255">
                            <div class="form-text">Maximum 255 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Content *</label>
                            <textarea name="content" class="form-control" rows="6" placeholder="Describe your question or topic in detail..." required maxlength="5000"></textarea>
                            <div class="form-text">Maximum 5000 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
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
            // Enhanced animations for discussion cards
            $('.discussion-card').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(30px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100 * index);
            });
            
            // Hover effects for discussion cards
            $('.discussion-card').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-5px) scale(1.02)');
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
            
            // Handle AJAX form submissions
            $('.ajax-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();
                
                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();
                
                // Show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Posting...');
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Close modal and refresh page
                            $('#createDiscussionModal').modal('hide');
                            showSuccessMessage(response.message || 'Discussion posted successfully!');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            // Show error message
                            showErrorMessage(response.message || 'Failed to post discussion. Please try again.');
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        
                        // Try to parse error response
                        var errorMessage = 'Failed to post discussion. Please try again.';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || response.error || errorMessage;
                        } catch(e) {
                            if (xhr.responseText) {
                                errorMessage = 'Server error occurred. Please try again.';
                            }
                        }
                        
                        showErrorMessage(errorMessage);
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // View discussion function
            window.viewDiscussion = function(discussionId) {
                $('#discussionDetailsContent').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading discussion...</p>
                    </div>
                `);
                
                $('#discussionDetailsModal').modal('show');
                
                $.ajax({
                    url: '../api/get_discussion_details.php',
                    type: 'GET',
                    data: { discussion_id: discussionId },
                    success: function(data) {
                        $('#discussionDetailsContent').html(data);
                        
                        // Increment view count
                        $.post('../api/increment_views.php', { discussion_id: discussionId });
                    },
                    error: function() {
                        $('#discussionDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading discussion details. Please try again.
                            </div>
                        `);
                    }
                });
            };
            
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
                
                // Add new alert at the top of the container
                $('.container-fluid').prepend(alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $('.alert').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Form validation
            $('#createDiscussionModal form').on('submit', function() {
                var title = $(this).find('input[name="title"]').val().trim();
                var content = $(this).find('textarea[name="content"]').val().trim();
                var courseId = $(this).find('select[name="course_id"]').val();
                
                var isValid = true;
                
                // Reset validation states
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();
                
                if (!courseId) {
                    $(this).find('select[name="course_id"]').addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please select a course</div>');
                    isValid = false;
                }
                
                if (!title) {
                    $(this).find('input[name="title"]').addClass('is-invalid')
                        .after('<div class="invalid-feedback">Discussion title is required</div>');
                    isValid = false;
                } else if (title.length > 255) {
                    $(this).find('input[name="title"]').addClass('is-invalid')
                        .after('<div class="invalid-feedback">Title is too long (maximum 255 characters)</div>');
                    isValid = false;
                }
                
                if (!content) {
                    $(this).find('textarea[name="content"]').addClass('is-invalid')
                        .after('<div class="invalid-feedback">Discussion content is required</div>');
                    isValid = false;
                } else if (content.length > 5000) {
                    $(this).find('textarea[name="content"]').addClass('is-invalid')
                        .after('<div class="invalid-feedback">Content is too long (maximum 5000 characters)</div>');
                    isValid = false;
                }
                
                return isValid;
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
