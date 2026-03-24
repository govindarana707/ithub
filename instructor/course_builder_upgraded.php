<?php
/**
 * Course Builder - Upgraded with AJAX + SweetAlert2
 * Full dynamic system with Lessons, Resources, Notes, Assignments CRUD
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Database.php';

// Check instructor access
if (!isLoggedIn() || !in_array(getUserRole(), ['instructor', 'admin'], true)) {
    $_SESSION['error_message'] = 'Access denied. Instructor privileges required.';
    header('Location: dashboard.php');
    exit;
}

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course';
    header('Location: courses.php');
    exit;
}

$courseModel = new Course();
$userModel = new User();

$course = $courseModel->getCourseById($courseId);
if (!$course) {
    $_SESSION['error_message'] = 'Course not found';
    header('Location: courses.php');
    exit;
}

// Instructors can only edit their own courses
if (getUserRole() === 'instructor' && (int)$course['instructor_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
    $_SESSION['error_message'] = 'Access denied. You can only edit your own courses.';
    header('Location: courses.php');
    exit;
}

$meta = $courseModel->getCourseMeta($courseId);

$whatYouLearn = $meta['what_you_learn'] ?? [];
$requirements = $meta['requirements'] ?? [];
$targetAudience = $meta['target_audience'] ?? [];
$faqs = $meta['faqs'] ?? [];

if (!is_array($whatYouLearn)) $whatYouLearn = [];
if (!is_array($requirements)) $requirements = [];
if (!is_array($targetAudience)) $targetAudience = [];
if (!is_array($faqs)) $faqs = [];

$courseStats = $courseModel->getCourseStatistics($courseId);
$db = new Database();
$conn = $db->getConnection();
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$instructors = $userModel->getInstructors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Builder - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .builder-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            border-radius: 12px;
            padding: 20px;
        }
        .builder-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .list-editor-item {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }
        .list-editor-item input {
            flex: 1;
        }
        .lesson-row {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .lesson-row:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #2a5298;
        }
        .lesson-row.dragging {
            opacity: 0.6;
        }
        .drag-handle {
            cursor: grab;
        }
        .badge-soft {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .btn-action-group .btn {
            padding: 6px 12px;
            font-size: 13px;
        }
        .content-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
        .modal-content {
            border-radius: 12px;
        }
        .swal2-popup {
            border-radius: 12px !important;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner-large {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2a5298;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast-success {
            background: #28a745 !important;
        }
        .toast-error {
            background: #dc3545 !important;
        }
        .toast-warning {
            background: #ffc107 !important;
        }
        .content-section {
            border-left: 3px solid #2a5298;
            padding-left: 15px;
            margin: 10px 0;
        }
        .section-title {
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .inline-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .inline-form h6 {
            color: #1e3c72;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="text-center">
            <div class="spinner-large"></div>
            <p class="mt-3 text-muted">Processing...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB Instructor
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-chalkboard-teacher me-1"></i> My Courses
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="builder-header mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">
                                <i class="fas fa-screwdriver-wrench me-2"></i>Course Builder
                            </h2>
                            <p class="mb-0 opacity-75">
                                Editing: <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                            </p>
                        </div>
                        <div>
                            <a href="courses.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Courses
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card builder-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h5><?php echo $courseStats['total_students'] ?? 0; ?></h5>
                                <small class="text-muted">Total Students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card builder-card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                                <h5><?php echo round($courseStats['avg_progress'] ?? 0, 1); ?>%</h5>
                                <small class="text-muted">Avg Progress</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card builder-card">
                            <div class="card-body text-center">
                                <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                <h5><?php echo round($courseStats['avg_rating'] ?? 0, 1); ?></h5>
                                <small class="text-muted">Avg Rating</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card builder-card">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-2x text-info mb-2"></i>
                                <h5><?php echo $courseStats['total_lessons'] ?? 0; ?></h5>
                                <small class="text-muted">Total Lessons</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Content Tabs -->
                <div class="card builder-card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="builderTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="lessons-tab" data-bs-toggle="tab" data-bs-target="#lessons" type="button" role="tab">
                                    <i class="fas fa-book-open me-2"></i>Lessons & Content
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                    <i class="fas fa-info-circle me-2"></i>Course Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="builderTabContent">
                            <!-- Lessons Tab -->
                            <div class="tab-pane fade show active" id="lessons" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="fas fa-list me-2"></i>Course Lessons</h5>
                                    <button class="btn btn-primary" onclick="showAddLessonModal()">
                                        <i class="fas fa-plus me-2"></i>Add Lesson
                                    </button>
                                </div>
                                <div id="lessonsContainer">
                                    <!-- Lessons will be loaded here via AJAX -->
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-3">Loading lessons...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Details Tab -->
                            <div class="tab-pane fade" id="details" role="tabpanel">
                                <h5 class="mb-4"><i class="fas fa-edit me-2"></i>Course Details</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">What You'll Learn</label>
                                            <div id="whatYouLearnContainer">
                                                <?php foreach ($whatYouLearn as $index => $item): ?>
                                                    <div class="list-editor-item">
                                                        <input type="text" class="form-control" name="what_you_learn[]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Learning outcome...">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addListItem('whatYouLearnContainer')">
                                                <i class="fas fa-plus me-1"></i>Add Item
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Requirements</label>
                                            <div id="requirementsContainer">
                                                <?php foreach ($requirements as $index => $item): ?>
                                                    <div class="list-editor-item">
                                                        <input type="text" class="form-control" name="requirements[]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Course requirement...">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addListItem('requirementsContainer')">
                                                <i class="fas fa-plus me-1"></i>Add Item
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Target Audience</label>
                                            <div id="targetAudienceContainer">
                                                <?php foreach ($targetAudience as $index => $item): ?>
                                                    <div class="list-editor-item">
                                                        <input type="text" class="form-control" name="target_audience[]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Target audience...">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addListItem('targetAudienceContainer')">
                                                <i class="fas fa-plus me-1"></i>Add Item
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">FAQs</label>
                                            <div id="faqsContainer">
                                                <?php foreach ($faqs as $index => $faq): ?>
                                                    <div class="list-editor-item">
                                                        <input type="text" class="form-control mb-2" name="faq_question[]" value="<?php echo htmlspecialchars($faq['question'] ?? ''); ?>" placeholder="Question...">
                                                        <input type="text" class="form-control" name="faq_answer[]" value="<?php echo htmlspecialchars($faq['answer'] ?? ''); ?>" placeholder="Answer...">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addFAQItem()">
                                                <i class="fas fa-plus me-1"></i>Add FAQ
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button class="btn btn-success" onclick="saveCourseDetails()">
                                        <i class="fas fa-save me-2"></i>Save Details
                                    </button>
                                </div>
                            </div>

                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settings" role="tabpanel">
                                <h5 class="mb-4"><i class="fas fa-cog me-2"></i>Course Settings</h5>
                                
                                <form id="settingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Course Title</label>
                                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($course['title']); ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="category_id">
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $course['category_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Price (Rs)</label>
                                                <input type="number" class="form-control" name="price" value="<?php echo $course['price']; ?>" step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Duration (hours)</label>
                                                <input type="number" class="form-control" name="duration_hours" value="<?php echo $course['duration_hours']; ?>" min="1">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Difficulty Level</label>
                                                <select class="form-select" name="difficulty_level">
                                                    <option value="beginner" <?php echo $course['difficulty_level'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                                    <option value="intermediate" <?php echo $course['difficulty_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="advanced" <?php echo $course['difficulty_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="draft" <?php echo $course['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="published" <?php echo $course['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                                    <option value="archived" <?php echo $course['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($course['description']); ?></textarea>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- Add/Edit Lesson Modal -->
    <div class="modal fade" id="lessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonModalTitle">
                        <i class="fas fa-plus-circle me-2"></i>Add New Lesson
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="lessonForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_lesson">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                        <input type="hidden" name="lesson_id" id="editLessonId" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Lesson Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="lessonTitle" required placeholder="Enter lesson title...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="lessonDescription" rows="3" placeholder="Brief description of the lesson..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Content Type</label>
                                    <select class="form-select" name="content_type" id="lessonContentType">
                                        <option value="video">Video</option>
                                        <option value="text">Text/Article</option>
                                        <option value="quiz">Quiz</option>
                                        <option value="live">Live Session</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (e.g., 10:30)</label>
                                    <input type="text" class="form-control" name="duration" id="lessonDuration" placeholder="10:30">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Video URL (YouTube, Vimeo, etc.)</label>
                            <input type="url" class="form-control" name="video_url" id="lessonVideoUrl" placeholder="https://...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Video File</label>
                            <input type="file" class="form-control" name="video_file" id="lessonVideoFile" accept="video/*">
                            <small class="text-muted">Max size: 500MB. Supported: mp4, webm, mov</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_free" id="lessonIsFree" value="1">
                            <label class="form-check-label" for="lessonIsFree">
                                <i class="fas fa-unlock me-1"></i>Free Preview Lesson
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="lessonSubmitBtn">
                            <i class="fas fa-save me-1"></i>Save Lesson
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resources Modal -->
    <div class="modal fade" id="resourcesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>Manage Resources
                        <small id="resourcesLessonTitle" class="text-muted ms-2"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="resourcesLessonId" value="">
                    
                    <!-- Resources List -->
                    <div id="resourcesListContainer" class="mb-4">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted">Loading resources...</span>
                        </div>
                    </div>
                    
                    <!-- Add Resource Form -->
                    <div class="inline-form">
                        <h6><i class="fas fa-plus me-2"></i>Add New Resource</h6>
                        <form id="resourceForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_resource">
                            <input type="hidden" name="lesson_id" id="resourceLessonId" value="">
                            <input type="hidden" name="resource_id" id="editResourceId" value="">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" id="resourceTitle" required placeholder="Resource title...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="resource_type" id="resourceType">
                                            <option value="document">Document</option>
                                            <option value="pdf">PDF</option>
                                            <option value="presentation">Presentation</option>
                                            <option value="video">Video</option>
                                            <option value="link">External Link</option>
                                            <option value="image">Image</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="resourceDescription" rows="2" placeholder="Brief description..."></textarea>
                            </div>
                            
                            <div class="mb-3" id="externalUrlField">
                                <label class="form-label">External URL</label>
                                <input type="url" class="form-control" name="external_url" id="resourceExternalUrl" placeholder="https://...">
                            </div>
                            
                            <div class="mb-3" id="fileUploadField">
                                <label class="form-label">Upload File</label>
                                <input type="file" class="form-control" name="resource_file" id="resourceFile">
                                <small class="text-muted">Max size: 50MB. Supported: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, txt</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_downloadable" id="resourceDownloadable" value="1" checked>
                                <label class="form-check-label" for="resourceDownloadable">
                                    <i class="fas fa-download me-1"></i>Downloadable by students
                                </label>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm" id="resourceSubmitBtn">
                                    <i class="fas fa-plus me-1"></i>Add Resource
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="cancelResourceEdit" style="display:none;" onclick="cancelResourceEdit()">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sticky-note me-2"></i>Manage Notes
                        <small id="notesLessonTitle" class="text-muted ms-2"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="notesLessonId" value="">
                    
                    <!-- Notes List -->
                    <div id="notesListContainer" class="mb-4">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted">Loading notes...</span>
                        </div>
                    </div>
                    
                    <!-- Add Note Form -->
                    <div class="inline-form">
                        <h6><i class="fas fa-plus me-2"></i>Add New Note</h6>
                        <form id="noteForm">
                            <input type="hidden" name="action" value="add_note">
                            <input type="hidden" name="lesson_id" id="noteLessonId" value="">
                            <input type="hidden" name="note_id" id="editNoteId" value="">
                            
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="noteTitle" required placeholder="Note title...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" id="noteContent" rows="6" required placeholder="Write your note here... (Markdown supported)"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm" id="noteSubmitBtn">
                                    <i class="fas fa-plus me-1"></i>Add Note
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="cancelNoteEdit" style="display:none;" onclick="cancelNoteEdit()">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments Modal -->
    <div class="modal fade" id="assignmentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks me-2"></i>Manage Assignments
                        <small id="assignmentsLessonTitle" class="text-muted ms-2"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignmentsLessonId" value="">
                    
                    <!-- Assignments List -->
                    <div id="assignmentsListContainer" class="mb-4">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted">Loading assignments...</span>
                        </div>
                    </div>
                    
                    <!-- Add Assignment Form -->
                    <div class="inline-form">
                        <h6><i class="fas fa-plus me-2"></i>Add New Assignment</h6>
                        <form id="assignmentForm">
                            <input type="hidden" name="action" value="add_assignment">
                            <input type="hidden" name="lesson_id" id="assignmentLessonId" value="">
                            <input type="hidden" name="assignment_id" id="editAssignmentId" value="">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" id="assignmentTitle" required placeholder="Assignment title...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="assignment_type" id="assignmentType">
                                            <option value="file_upload">File Upload</option>
                                            <option value="text_submission">Text Submission</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="project">Project</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" id="assignmentDescription" rows="3" required placeholder="Assignment description..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Instructions</label>
                                <textarea class="form-control" name="instructions" id="assignmentInstructions" rows="2" placeholder="Step-by-step instructions..."></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="datetime-local" class="form-control" name="due_date" id="assignmentDueDate">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Max Points</label>
                                        <input type="number" class="form-control" name="max_points" id="assignmentMaxPoints" min="0" value="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_published" id="assignmentPublished" value="1">
                                            <label class="form-check-label" for="assignmentPublished">
                                                Published
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm" id="assignmentSubmitBtn">
                                    <i class="fas fa-plus me-1"></i>Add Assignment
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="cancelAssignmentEdit" style="display:none;" onclick="cancelAssignmentEdit()">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Course Builder API Configuration
        const API_URL = '../api/course_builder_api.php';
        let courseId = <?php echo $courseId; ?>;
        let lessonModal, resourcesModal, notesModal, assignmentsModal;
        
        // Initialize modals
        document.addEventListener('DOMContentLoaded', function() {
            lessonModal = new bootstrap.Modal(document.getElementById('lessonModal'));
            resourcesModal = new bootstrap.Modal(document.getElementById('resourcesModal'));
            notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
            assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
            
            // Load lessons on page load
            loadLessons();
            
            // Initialize forms
            initLessonForm();
            initResourceForm();
            initNoteForm();
            initAssignmentForm();
            initSettingsForm();
        });

        // ==================== SWEETALERT2 HELPERS ====================
        
        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({ icon, title });
        }

        function showLoading() {
            $('#loadingOverlay').fadeIn();
        }

        function hideLoading() {
            $('#loadingOverlay').fadeOut();
        }

        async function confirmDelete(title, text) {
            return await Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Delete',
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
                reverseButtons: true
            });
        }

        // ==================== AJAX HELPERS ====================
        
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { status: 'error', message: 'Network error. Please try again.' };
            }
        }

        async function apiCallWithFiles(action, formElement) {
            const formData = new FormData(formElement);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { status: 'error', message: 'Network error. Please try again.' };
            }
        }

        // ==================== LESSONS CRUD ====================
        
        function loadLessons() {
            $.ajax({
                url: API_URL,
                method: 'GET',
                data: { action: 'get_lessons', course_id: courseId },
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        renderLessons(response.data.lessons);
                    } else {
                        renderEmptyLessons();
                    }
                },
                error: function() {
                    $('#lessonsContainer').html(`
                        <div class="empty-state text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error loading lessons. Please refresh the page.</p>
                        </div>
                    `);
                }
            });
        }

        function renderLessons(lessons) {
            if (!lessons || lessons.length === 0) {
                renderEmptyLessons();
                return;
            }
            
            let html = '';
            lessons.forEach(function(lesson, index) {
                const isFreeBadge = lesson.is_free == 1 ? '<span class="badge bg-success content-badge ms-2">Free</span>' : '';
                const publishedBadge = lesson.is_published == 1 ? '<span class="badge bg-primary content-badge ms-1">Published</span>' : '<span class="badge bg-secondary content-badge ms-1">Draft</span>';
                
                html += `
                    <div class="lesson-row" data-lesson-id="${lesson.id}">
                        <div class="d-flex align-items-start">
                            <div class="drag-handle me-3 mt-2">
                                <i class="fas fa-grip-vertical text-muted"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="lesson-number">${index + 1}.</span>
                                            ${escapeHtml(lesson.title)}
                                            ${isFreeBadge}
                                            ${publishedBadge}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>${lesson.duration || 'N/A'}
                                            <i class="fas fa-${getContentTypeIcon(lesson.content_type)} ms-3 me-1"></i>${lesson.content_type || 'Video'}
                                            ${lesson.resources_count > 0 ? `<span class="badge bg-info content-badge ms-2"><i class="fas fa-paperclip me-1"></i>${lesson.resources_count}</span>` : ''}
                                            ${lesson.notes_count > 0 ? `<span class="badge bg-warning content-badge ms-1"><i class="fas fa-sticky-note me-1"></i>${lesson.notes_count}</span>` : ''}
                                            ${lesson.assignments_count > 0 ? `<span class="badge bg-success content-badge ms-1"><i class="fas fa-tasks me-1"></i>${lesson.assignments_count}</span>` : ''}
                                        </small>
                                        ${lesson.description ? `<p class="text-muted small mb-0 mt-2">${escapeHtml(lesson.description.substring(0, 100))}${lesson.description.length > 100 ? '...' : ''}</p>` : ''}
                                    </div>
                                    <div class="btn-group btn-action-group">
                                        <button class="btn btn-outline-primary btn-sm" onclick="editLesson(${lesson.id})" title="Edit Lesson">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" onclick="duplicateLesson(${lesson.id})" title="Duplicate">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteLesson(${lesson.id})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="mt-3 pt-2 border-top">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="openResourcesModal(${lesson.id}, '${escapeHtml(lesson.title)}')">
                                        <i class="fas fa-file-alt me-1"></i>Resources
                                        ${lesson.resources_count > 0 ? `<span class="badge bg-primary ms-1">${lesson.resources_count}</span>` : ''}
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning me-1" onclick="openNotesModal(${lesson.id}, '${escapeHtml(lesson.title)}')">
                                        <i class="fas fa-sticky-note me-1"></i>Notes
                                        ${lesson.notes_count > 0 ? `<span class="badge bg-warning ms-1">${lesson.notes_count}</span>` : ''}
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="openAssignmentsModal(${lesson.id}, '${escapeHtml(lesson.title)}')">
                                        <i class="fas fa-tasks me-1"></i>Assignments
                                        ${lesson.assignments_count > 0 ? `<span class="badge bg-success ms-1">${lesson.assignments_count}</span>` : ''}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#lessonsContainer').html(html);
        }

        function renderEmptyLessons() {
            $('#lessonsContainer').html(`
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h5>No Lessons Yet</h5>
                    <p>Start building your course by adding the first lesson!</p>
                    <button class="btn btn-primary" onclick="showAddLessonModal()">
                        <i class="fas fa-plus me-2"></i>Add First Lesson
                    </button>
                </div>
            `);
        }

        function showAddLessonModal() {
            $('#lessonModalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Lesson');
            $('#lessonForm')[0].reset();
            $('#editLessonId').val('');
            $('#lessonSubmitBtn').html('<i class="fas fa-save me-1"></i>Save Lesson');
            lessonModal.show();
        }

        function initLessonForm() {
            $('#lessonForm').on('submit', async function(e) {
                e.preventDefault();
                const editId = $('#editLessonId').val();
                const action = editId ? 'update_lesson' : 'add_lesson';
                
                showLoading();
                const response = await apiCallWithFiles(action, this);
                hideLoading();
                
                if (response.status === 'success') {
                    lessonModal.hide();
                    showToast('success', response.message);
                    loadLessons();
                    updateCourseStats();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            });
        }

        // Redirect to lesson-editor.php for editing (shows full form with video thumbnail, additional notes, etc.)
        function editLesson(lessonId) {
            window.location.href = `lesson-editor.php?course_id=${courseId}&lesson_id=${lessonId}&action=edit`;
        }

        async function deleteLesson(lessonId) {
            const result = await confirmDelete(
                'Delete Lesson?',
                'This will permanently delete the lesson and all its resources, notes, and assignments. This action cannot be undone.'
            );
            
            if (result.isConfirmed) {
                showLoading();
                const response = await apiCall('delete_lesson', { lesson_id: lessonId });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    loadLessons();
                    updateCourseStats();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            }
        }

        async function duplicateLesson(lessonId) {
            const result = await Swal.fire({
                title: 'Duplicate Lesson?',
                text: 'This will create a copy of the lesson including all resources, notes, and assignments.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-copy me-1"></i>Duplicate',
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel'
            });
            
            if (result.isConfirmed) {
                showLoading();
                const response = await apiCall('duplicate_lesson', { lesson_id: lessonId });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', 'Lesson duplicated successfully!');
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            }
        }

        // ==================== RESOURCES CRUD ====================
        
        function openResourcesModal(lessonId, lessonTitle) {
            $('#resourcesLessonId').val(lessonId);
            $('#resourceLessonId').val(lessonId);
            $('#resourcesLessonTitle').text(`- ${lessonTitle}`);
            $('#resourceForm')[0].reset();
            $('#editResourceId').val('');
            $('#cancelResourceEdit').hide();
            $('#resourceSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Resource');
            
            loadResources(lessonId);
            resourcesModal.show();
        }

        async function loadResources(lessonId) {
            $('#resourcesListContainer').html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted">Loading resources...</span>
                </div>
            `);
            
            const response = await apiCall('get_resources', { lesson_id: lessonId });
            
            if (response.status === 'success' && response.data.resources.length > 0) {
                let html = '<div class="list-group">';
                response.data.resources.forEach(function(resource) {
                    const icon = getResourceIcon(resource.resource_type);
                    const fileSize = resource.file_size ? formatFileSize(resource.file_size) : '';
                    
                    html += `
                        <div class="list-group-item" data-resource-id="${resource.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-start">
                                    <i class="${icon} text-primary me-3 mt-1" style="font-size: 24px;"></i>
                                    <div>
                                        <strong>${escapeHtml(resource.title)}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <span class="badge bg-secondary">${resource.resource_type}</span>
                                            ${fileSize ? `<span class="ms-2">${fileSize}</span>` : ''}
                                            ${resource.external_url ? '<span class="badge bg-info ms-2">External Link</span>' : ''}
                                        </small>
                                        ${resource.description ? `<br><small class="text-muted">${escapeHtml(resource.description)}</small>` : ''}
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editResource(${resource.id}, ${lessonId})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteResource(${resource.id}, ${lessonId})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#resourcesListContainer').html(html);
            } else {
                $('#resourcesListContainer').html(`
                    <div class="empty-state py-3">
                        <i class="fas fa-file-alt" style="font-size: 32px;"></i>
                        <p class="text-muted mb-0">No resources added yet.</p>
                    </div>
                `);
            }
        }

        function initResourceForm() {
            $('#resourceForm').on('submit', async function(e) {
                e.preventDefault();
                const editId = $('#editResourceId').val();
                const action = editId ? 'update_resource' : 'add_resource';
                $('#resourceForm input[name="action"]').val(action);
                
                showLoading();
                const response = await apiCallWithFiles(action, this);
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    const lessonId = $('#resourceLessonId').val();
                    loadResources(lessonId);
                    resetResourceForm();
                    loadLessons(); // Refresh to update counts
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            });
        }

        async function editResource(resourceId, lessonId) {
            const response = await apiCall('get_resources', { lesson_id: lessonId });
            
            if (response.status === 'success') {
                const resource = response.data.resources.find(r => r.id == resourceId);
                if (resource) {
                    $('#editResourceId').val(resource.id);
                    $('#resourceTitle').val(resource.title);
                    $('#resourceDescription').val(resource.description || '');
                    $('#resourceType').val(resource.resource_type || 'document');
                    $('#resourceExternalUrl').val(resource.external_url || '');
                    $('#resourceDownloadable').prop('checked', resource.is_downloadable == 1);
                    
                    $('#resourceSubmitBtn').html('<i class="fas fa-save me-1"></i>Update Resource');
                    $('#cancelResourceEdit').show();
                    
                    // Scroll to form
                    document.getElementById('resourceForm').scrollIntoView({ behavior: 'smooth' });
                }
            }
        }

        function cancelResourceEdit() {
            resetResourceForm();
        }

        function resetResourceForm() {
            $('#resourceForm')[0].reset();
            $('#editResourceId').val('');
            $('#resourceDownloadable').prop('checked', true);
            $('#resourceSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Resource');
            $('#cancelResourceEdit').hide();
        }

        async function deleteResource(resourceId, lessonId) {
            const result = await confirmDelete('Delete Resource?', 'This action cannot be undone.');
            
            if (result.isConfirmed) {
                showLoading();
                const response = await apiCall('delete_resource', { resource_id: resourceId });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    loadResources(lessonId);
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            }
        }

        // ==================== NOTES CRUD ====================
        
        function openNotesModal(lessonId, lessonTitle) {
            $('#notesLessonId').val(lessonId);
            $('#noteLessonId').val(lessonId);
            $('#notesLessonTitle').text(`- ${lessonTitle}`);
            $('#noteForm')[0].reset();
            $('#editNoteId').val('');
            $('#cancelNoteEdit').hide();
            $('#noteSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Note');
            
            loadNotes(lessonId);
            notesModal.show();
        }

        async function loadNotes(lessonId) {
            $('#notesListContainer').html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted">Loading notes...</span>
                </div>
            `);
            
            const response = await apiCall('get_notes', { lesson_id: lessonId });
            
            if (response.status === 'success' && response.data.notes.length > 0) {
                let html = '<div class="list-group">';
                response.data.notes.forEach(function(note) {
                    const date = new Date(note.created_at).toLocaleDateString();
                    const content = note.content.length > 200 ? note.content.substring(0, 200) + '...' : note.content;
                    
                    html += `
                        <div class="list-group-item" data-note-id="${note.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1 me-3">
                                    <strong><i class="fas fa-sticky-note text-warning me-2"></i>${escapeHtml(note.title)}</strong>
                                    <br>
                                    <small class="text-muted">Created: ${date}</small>
                                    <div class="mt-2 p-2 bg-light rounded">${note.content}</div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editNote(${note.id}, ${lessonId})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteNote(${note.id}, ${lessonId})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#notesListContainer').html(html);
            } else {
                $('#notesListContainer').html(`
                    <div class="empty-state py-3">
                        <i class="fas fa-sticky-note" style="font-size: 32px;"></i>
                        <p class="text-muted mb-0">No notes added yet.</p>
                    </div>
                `);
            }
        }

        function initNoteForm() {
            $('#noteForm').on('submit', async function(e) {
                e.preventDefault();
                const editId = $('#editNoteId').val();
                const action = editId ? 'update_note' : 'add_note';
                $('#noteForm input[name="action"]').val(action);
                
                showLoading();
                const response = await apiCall('add_note', {
                    action: action,
                    note_id: editId,
                    lesson_id: $('#noteLessonId').val(),
                    title: $('#noteTitle').val(),
                    content: $('#noteContent').val(),
                    note_type: 'markdown'
                });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    const lessonId = $('#noteLessonId').val();
                    loadNotes(lessonId);
                    resetNoteForm();
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            });
        }

        async function editNote(noteId, lessonId) {
            const response = await apiCall('get_notes', { lesson_id: lessonId });
            
            if (response.status === 'success') {
                const note = response.data.notes.find(n => n.id == noteId);
                if (note) {
                    $('#editNoteId').val(note.id);
                    $('#noteTitle').val(note.title);
                    $('#noteContent').val(note.content);
                    
                    $('#noteSubmitBtn').html('<i class="fas fa-save me-1"></i>Update Note');
                    $('#cancelNoteEdit').show();
                    
                    document.getElementById('noteForm').scrollIntoView({ behavior: 'smooth' });
                }
            }
        }

        function cancelNoteEdit() {
            resetNoteForm();
        }

        function resetNoteForm() {
            $('#noteForm')[0].reset();
            $('#editNoteId').val('');
            $('#noteSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Note');
            $('#cancelNoteEdit').hide();
        }

        async function deleteNote(noteId, lessonId) {
            const result = await confirmDelete('Delete Note?', 'This action cannot be undone.');
            
            if (result.isConfirmed) {
                showLoading();
                const response = await apiCall('delete_note', { note_id: noteId });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    loadNotes(lessonId);
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            }
        }

        // ==================== ASSIGNMENTS CRUD ====================
        
        function openAssignmentsModal(lessonId, lessonTitle) {
            $('#assignmentsLessonId').val(lessonId);
            $('#assignmentLessonId').val(lessonId);
            $('#assignmentsLessonTitle').text(`- ${lessonTitle}`);
            $('#assignmentForm')[0].reset();
            $('#editAssignmentId').val('');
            $('#cancelAssignmentEdit').hide();
            $('#assignmentSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Assignment');
            
            loadAssignments(lessonId);
            assignmentsModal.show();
        }

        async function loadAssignments(lessonId) {
            $('#assignmentsListContainer').html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted">Loading assignments...</span>
                </div>
            `);
            
            const response = await apiCall('get_assignments', { lesson_id: lessonId });
            
            if (response.status === 'success' && response.data.assignments.length > 0) {
                let html = '<div class="list-group">';
                response.data.assignments.forEach(function(assignment) {
                    const dueDate = assignment.due_date ? new Date(assignment.due_date).toLocaleString() : 'No deadline';
                    const isPastDue = assignment.due_date && new Date(assignment.due_date) < new Date();
                    const statusBadge = assignment.is_published == 1 ? '<span class="badge bg-success ms-2">Published</span>' : '<span class="badge bg-secondary ms-2">Draft</span>';
                    const pastDueBadge = isPastDue ? '<span class="badge bg-danger ms-2">Past Due</span>' : '';
                    
                    html += `
                        <div class="list-group-item" data-assignment-id="${assignment.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1 me-3">
                                    <strong><i class="fas fa-tasks text-success me-2"></i>${escapeHtml(assignment.title)}</strong>
                                    ${statusBadge}
                                    ${pastDueBadge}
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Due: ${dueDate}
                                        <i class="fas fa-star ms-3 me-1"></i>Max Points: ${assignment.max_points || 100}
                                        <i class="fas fa-list ms-3 me-1"></i>Type: ${assignment.assignment_type || 'file_upload'}
                                    </small>
                                    ${assignment.description ? `<p class="mb-0 mt-2 small">${escapeHtml(assignment.description.substring(0, 150))}${assignment.description.length > 150 ? '...' : ''}</p>` : ''}
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editAssignment(${assignment.id}, ${lessonId})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteAssignment(${assignment.id}, ${lessonId})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#assignmentsListContainer').html(html);
            } else {
                $('#assignmentsListContainer').html(`
                    <div class="empty-state py-3">
                        <i class="fas fa-tasks" style="font-size: 32px;"></i>
                        <p class="text-muted mb-0">No assignments added yet.</p>
                    </div>
                `);
            }
        }

        function initAssignmentForm() {
            $('#assignmentForm').on('submit', async function(e) {
                e.preventDefault();
                const editId = $('#editAssignmentId').val();
                const action = editId ? 'update_assignment' : 'add_assignment';
                $('#assignmentForm input[name="action"]').val(action);
                
                showLoading();
                const response = await apiCall('add_assignment', {
                    action: action,
                    assignment_id: editId,
                    lesson_id: $('#assignmentLessonId').val(),
                    title: $('#assignmentTitle').val(),
                    description: $('#assignmentDescription').val(),
                    instructions: $('#assignmentInstructions').val(),
                    assignment_type: $('#assignmentType').val(),
                    max_points: $('#assignmentMaxPoints').val(),
                    due_date: $('#assignmentDueDate').val(),
                    is_published: $('#assignmentPublished').is(':checked') ? 1 : 0
                });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    const lessonId = $('#assignmentLessonId').val();
                    loadAssignments(lessonId);
                    resetAssignmentForm();
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            });
        }

        async function editAssignment(assignmentId, lessonId) {
            const response = await apiCall('get_assignments', { lesson_id: lessonId });
            
            if (response.status === 'success') {
                const assignment = response.data.assignments.find(a => a.id == assignmentId);
                if (assignment) {
                    $('#editAssignmentId').val(assignment.id);
                    $('#assignmentTitle').val(assignment.title);
                    $('#assignmentDescription').val(assignment.description || '');
                    $('#assignmentInstructions').val(assignment.instructions || '');
                    $('#assignmentType').val(assignment.assignment_type || 'file_upload');
                    $('#assignmentMaxPoints').val(assignment.max_points || 100);
                    $('#assignmentDueDate').val(assignment.due_date ? assignment.due_date.replace(' ', 'T').substring(0, 16) : '');
                    $('#assignmentPublished').prop('checked', assignment.is_published == 1);
                    
                    $('#assignmentSubmitBtn').html('<i class="fas fa-save me-1"></i>Update Assignment');
                    $('#cancelAssignmentEdit').show();
                    
                    document.getElementById('assignmentForm').scrollIntoView({ behavior: 'smooth' });
                }
            }
        }

        function cancelAssignmentEdit() {
            resetAssignmentForm();
        }

        function resetAssignmentForm() {
            $('#assignmentForm')[0].reset();
            $('#editAssignmentId').val('');
            $('#assignmentMaxPoints').val(100);
            $('#assignmentSubmitBtn').html('<i class="fas fa-plus me-1"></i>Add Assignment');
            $('#cancelAssignmentEdit').hide();
        }

        async function deleteAssignment(assignmentId, lessonId) {
            const result = await confirmDelete('Delete Assignment?', 'This action cannot be undone.');
            
            if (result.isConfirmed) {
                showLoading();
                const response = await apiCall('delete_assignment', { assignment_id: assignmentId });
                hideLoading();
                
                if (response.status === 'success') {
                    showToast('success', response.message);
                    loadAssignments(lessonId);
                    loadLessons();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
            }
        }

        // ==================== SETTINGS & DETAILS ====================
        
        function initSettingsForm() {
            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('course_id', courseId);

                $.ajax({
                    url: '../api/update_course_settings.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Settings saved successfully!',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save settings' });
                    }
                });
            });
        }

        function saveCourseDetails() {
            const formData = new FormData();
            
            // Collect what you learn items
            const whatYouLearn = [];
            $('input[name="what_you_learn[]"]').each(function() {
                if ($(this).val().trim()) whatYouLearn.push($(this).val().trim());
            });
            formData.append('what_you_learn', JSON.stringify(whatYouLearn));
            
            // Collect requirements
            const requirements = [];
            $('input[name="requirements[]"]').each(function() {
                if ($(this).val().trim()) requirements.push($(this).val().trim());
            });
            formData.append('requirements', JSON.stringify(requirements));
            
            // Collect target audience
            const targetAudience = [];
            $('input[name="target_audience[]"]').each(function() {
                if ($(this).val().trim()) targetAudience.push($(this).val().trim());
            });
            formData.append('target_audience', JSON.stringify(targetAudience));
            
            // Collect FAQs
            const faqs = [];
            $('input[name="faq_question[]"]').each(function(index) {
                const question = $(this).val().trim();
                const answer = $('input[name="faq_answer[]"]').eq(index).val().trim();
                if (question && answer) {
                    faqs.push({ question, answer });
                }
            });
            formData.append('faqs', JSON.stringify(faqs));
            
            formData.append('course_id', courseId);

            showLoading();
            $.ajax({
                url: '../api/save_course_details.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Course details saved successfully!');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save course details' });
                }
            });
        }

        // ==================== HELPER FUNCTIONS ====================
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getContentTypeIcon(type) {
            const icons = {
                'video': 'play-circle',
                'text': 'file-alt',
                'quiz': 'question-circle',
                'live': 'video'
            };
            return icons[type] || 'play-circle';
        }

        function getResourceIcon(type) {
            const icons = {
                'pdf': 'fas fa-file-pdf text-danger',
                'document': 'fas fa-file-word text-primary',
                'presentation': 'fas fa-file-powerpoint text-warning',
                'video': 'fas fa-file-video text-info',
                'link': 'fas fa-link text-secondary',
                'image': 'fas fa-file-image text-success'
            };
            return icons[type] || 'fas fa-file text-secondary';
        }

        function formatFileSize(bytes) {
            if (!bytes) return '';
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
        }

        function updateCourseStats() {
            // Refresh the page to update stats, or implement AJAX stats update
            // For now, we'll just reload
        }

        // ==================== LIST EDITOR FUNCTIONS ====================
        
        function addListItem(containerId) {
            const container = document.getElementById(containerId);
            const newItem = document.createElement('div');
            newItem.className = 'list-editor-item';
            newItem.innerHTML = `
                <input type="text" class="form-control" placeholder="Enter item..." name="${containerId.replace('Container', '[]')}">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newItem);
        }

        function addFAQItem() {
            const container = document.getElementById('faqsContainer');
            const newFAQ = document.createElement('div');
            newFAQ.className = 'list-editor-item';
            newFAQ.innerHTML = `
                <input type="text" class="form-control mb-2" placeholder="Question..." name="faq_question[]">
                <input type="text" class="form-control" placeholder="Answer..." name="faq_answer[]">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeListItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newFAQ);
        }

        function removeListItem(button) {
            button.parentElement.remove();
        }
    </script>
</body>
</html>
