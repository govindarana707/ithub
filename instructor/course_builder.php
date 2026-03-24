<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';
require_once '../models/User.php';
require_once '../models/Database.php';

// Check instructor access manually to avoid redirect to main dashboard
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
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
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
    </style>
</head>
<body>
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
                                    <div>
                                        <h5 class="mb-0">Course Lessons</h5>
                                        <small class="text-muted">Manage and preview your course content</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="../student/view-course.php?id=<?php echo $courseId; ?>" class="btn btn-outline-info" target="_blank">
                                            <i class="fas fa-eye me-2"></i>Preview as Student
                                        </a>
                                        <button class="btn btn-primary" onclick="addNewLesson()">
                                            <i class="fas fa-plus me-2"></i>Add Lesson
                                        </button>
                                    </div>
                                </div>
                                <div id="lessonsContainer">
                                    <!-- Lessons will be loaded here via AJAX -->
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="text-muted mt-2">Loading lessons...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Details Tab -->
                            <div class="tab-pane fade" id="details" role="tabpanel">
                                <h5 class="mb-4">Course Details</h5>
                                
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
                                <h5 class="mb-4">Course Settings</h5>
                                
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const courseId = <?php echo $courseId; ?>;

        // Load lessons on page load
        $(document).ready(function() {
            loadLessons();
        });

        function loadLessons() {
            $.ajax({
                url: '../api/get_course_lessons.php',
                method: 'GET',
                data: { course_id: courseId },
                success: function(response) {
                    if (response.success) {
                        renderLessons(response.lessons);
                    } else {
                        $('#lessonsContainer').html('<p class="text-muted">No lessons found. Add your first lesson!</p>');
                    }
                },
                error: function() {
                    $('#lessonsContainer').html('<p class="text-danger">Error loading lessons.</p>');
                }
            });
        }

        function renderLessons(lessons) {
            let html = '';
            if (lessons.length === 0) {
                html = '<p class="text-muted text-center py-4">No lessons found. Add your first lesson!</p>';
            } else {
                lessons.forEach(function(lesson, index) {
                    html += `
                        <div class="lesson-row" data-lesson-id="${lesson.id}">
                            <div class="d-flex align-items-center">
                                <div class="drag-handle me-3">
                                    <i class="fas fa-grip-vertical text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${lesson.title}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>${lesson.duration || 'N/A'}
                                        <i class="fas fa-play-circle ms-3 me-1"></i>${lesson.content_type || 'Video'}
                                    </small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-info btn-sm me-1" onclick="manageResources(${lesson.id}, '${lesson.title}')">
                                            <i class="fas fa-file-alt me-1"></i>Resources
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning btn-sm me-1" onclick="manageAssignments(${lesson.id}, '${lesson.title}')">
                                            <i class="fas fa-tasks me-1"></i>Assignments
                                        </button>
                                        <button class="btn btn-sm btn-outline-success btn-sm" onclick="manageNotes(${lesson.id}, '${lesson.title}')">
                                            <i class="fas fa-sticky-note me-1"></i>Notes
                                        </button>
                                    </div>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editLesson(${lesson.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLesson(${lesson.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            $('#lessonsContainer').html(html);
        }

        function addNewLesson() {
            // Redirect to lesson creation page or open modal
            window.location.href = `lesson-editor.php?course_id=${courseId}&action=new`;
        }

        function editLesson(lessonId) {
            window.location.href = `lesson-editor.php?course_id=${courseId}&lesson_id=${lessonId}&action=edit`;
        }

        function deleteLesson(lessonId) {
            if (confirm('Are you sure you want to delete this lesson?')) {
                $.ajax({
                    url: '../api/delete_lesson.php',
                    method: 'POST',
                    data: { lesson_id: lessonId },
                    success: function(response) {
                        if (response.success) {
                            loadLessons();
                        } else {
                            alert('Error deleting lesson: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error deleting lesson');
                    }
                });
            }
        }

        function manageResources(lessonId, lessonTitle) {
            // Open a modal or redirect to resources management page
            // For now, we'll open a simple modal with resource management
            const modalHtml = `
                <div class="modal fade" id="resourcesModal" tabindex="-1" aria-labelledby="resourcesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="resourcesModalLabel">Manage Resources - ${lessonTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="resourcesList"></div>
                                <hr>
                                <div id="resourceFormSection">
                                    <h6 id="resourceFormTitle">Add New Resource</h6>
                                    <form id="resourceForm">
                                        <input type="hidden" name="lesson_id" value="${lessonId}">
                                        <input type="hidden" name="id" id="resourceId" value="">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="title" id="resourceTitle" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" id="resourceDescription" rows="2"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">File URL</label>
                                            <input type="text" class="form-control" name="file_url" id="resourceFileUrl" placeholder="https://... or /uploads/..." required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Resource Type</label>
                                            <select class="form-control" name="resource_type" id="resourceType">
                                                <option value="pdf">PDF</option>
                                                <option value="video">Video</option>
                                                <option value="link">Link</option>
                                                <option value="document">Document</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="resourceSubmitBtn">Add Resource</button>
                                        <button type="button" class="btn btn-secondary" id="resourceCancelEdit" style="display:none;" onclick="cancelResourceEdit()">Cancel Edit</button>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#resourcesModal').remove();
            $('body').append(modalHtml);
            
            const modal = new bootstrap.Modal(document.getElementById('resourcesModal'));
            modal.show();
            
            // Load existing resources
            loadResources(lessonId);
            
            // Handle form submission
            $('#resourceForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const resourceId = $('#resourceId').val();
                const method = resourceId ? 'PUT' : 'POST';
                const url = '../api/lesson_resources.php' + (resourceId ? `?id=${resourceId}` : '');
                
                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(resourceId ? 'Resource updated successfully!' : 'Resource added successfully!');
                            loadResources(lessonId);
                            resetResourceForm();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error saving resource');
                    }
                });
            });
        }

        function loadResources(lessonId) {
            $.ajax({
                url: '../api/lesson_resources.php',
                method: 'GET',
                data: { lesson_id: lessonId },
                success: function(response) {
                    let html = '';
                    if (response.success && response.resources && response.resources.length > 0) {
                        html = '<div class="list-group">';
                        response.resources.forEach(function(resource) {
                            html += `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${resource.title}</strong>
                                        <br>
                                        <small class="text-muted">${resource.resource_type} • ${resource.created_at}</small>
                                        ${resource.description ? `<br><small>${resource.description}</small>` : ''}
                                    </div>
                                    <div>
                                        <a href="${resource.file_url}" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-info me-1" onclick="editResource(${resource.id}, ${lessonId})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteResource(${resource.id}, ${lessonId})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html = '<p class="text-muted">No resources found.</p>';
                    }
                    $('#resourcesList').html(html);
                },
                error: function() {
                    $('#resourcesList').html('<p class="text-danger">Error loading resources.</p>');
                }
            });
        }

        function editResource(resourceId, lessonId) {
            $.ajax({
                url: '../api/lesson_resources.php',
                method: 'GET',
                data: { id: resourceId },
                success: function(response) {
                    if (response.success && response.resource) {
                        const resource = response.resource;
                        $('#resourceId').val(resource.id);
                        $('#resourceTitle').val(resource.title);
                        $('#resourceDescription').val(resource.description || '');
                        $('#resourceFileUrl').val(resource.file_url);
                        $('#resourceType').val(resource.resource_type);
                        
                        $('#resourceFormTitle').text('Edit Resource');
                        $('#resourceSubmitBtn').text('Update Resource');
                        $('#resourceCancelEdit').show();
                        
                        // Scroll to form
                        document.getElementById('resourceFormSection').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        alert('Error loading resource: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading resource');
                }
            });
        }

        function resetResourceForm() {
            $('#resourceId').val('');
            $('#resourceTitle').val('');
            $('#resourceDescription').val('');
            $('#resourceFileUrl').val('');
            $('#resourceType').val('pdf');
            
            $('#resourceFormTitle').text('Add New Resource');
            $('#resourceSubmitBtn').text('Add Resource');
            $('#resourceCancelEdit').hide();
        }

        function cancelResourceEdit() {
            resetResourceForm();
        }

        function deleteResource(resourceId, lessonId) {
            if (confirm('Are you sure you want to delete this resource?')) {
                $.ajax({
                    url: '../api/lesson_resources.php',
                    method: 'DELETE',
                    data: { id: resourceId },
                    success: function(response) {
                        if (response.success) {
                            alert('Resource deleted successfully!');
                            loadResources(lessonId);
                        } else {
                            alert('Error deleting resource: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error deleting resource');
                    }
                });
            }
        }

        function manageAssignments(lessonId, lessonTitle) {
            // Open assignments management modal
            const modalHtml = `
                <div class="modal fade" id="assignmentsModal" tabindex="-1" aria-labelledby="assignmentsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="assignmentsModalLabel">Manage Assignments - ${lessonTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="assignmentsList"></div>
                                <hr>
                                <div id="assignmentFormSection">
                                    <h6 id="assignmentFormTitle">Add New Assignment</h6>
                                    <form id="assignmentForm">
                                        <input type="hidden" name="lesson_id" value="${lessonId}">
                                        <input type="hidden" name="id" id="assignmentId" value="">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="title" id="assignmentTitle" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" id="assignmentDescription" rows="3" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Instructions</label>
                                            <textarea class="form-control" name="instructions" id="assignmentInstructions" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Due Date</label>
                                            <input type="datetime-local" class="form-control" name="due_date" id="assignmentDueDate">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Max Points</label>
                                            <input type="number" class="form-control" name="max_points" id="assignmentMaxPoints" min="0" step="1" value="100">
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="assignmentSubmitBtn">Add Assignment</button>
                                        <button type="button" class="btn btn-secondary" id="assignmentCancelEdit" style="display:none;" onclick="cancelAssignmentEdit()">Cancel Edit</button>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#assignmentsModal').remove();
            $('body').append(modalHtml);
            
            const modal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
            modal.show();
            
            loadAssignments(lessonId);
            
            $('#assignmentForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const assignmentId = $('#assignmentId').val();
                const method = assignmentId ? 'PUT' : 'POST';
                const url = '../api/lesson_assignments.php' + (assignmentId ? `?id=${assignmentId}` : '');
                
                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(assignmentId ? 'Assignment updated successfully!' : 'Assignment added successfully!');
                            loadAssignments(lessonId);
                            resetAssignmentForm();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error saving assignment');
                    }
                });
            });
        }

        function loadAssignments(lessonId) {
            $.ajax({
                url: '../api/lesson_assignments.php',
                method: 'GET',
                data: { lesson_id: lessonId },
                success: function(response) {
                    let html = '';
                    if (response.success && response.assignments && response.assignments.length > 0) {
                        html = '<div class="list-group">';
                        response.assignments.forEach(function(assignment) {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>${assignment.title}</strong>
                                            <br>
                                            <small class="text-muted">Due: ${assignment.due_date || 'No due date'} • Points: ${assignment.max_points || 'N/A'}</small>
                                            ${assignment.description ? `<br><small>${assignment.description}</small>` : ''}
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-info me-1" onclick="editAssignment(${assignment.id}, ${lessonId})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAssignment(${assignment.id}, ${lessonId})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html = '<p class="text-muted">No assignments found.</p>';
                    }
                    $('#assignmentsList').html(html);
                },
                error: function() {
                    $('#assignmentsList').html('<p class="text-danger">Error loading assignments.</p>');
                }
            });
        }

        function editAssignment(assignmentId, lessonId) {
            $.ajax({
                url: '../api/lesson_assignments.php',
                method: 'GET',
                data: { id: assignmentId },
                success: function(response) {
                    if (response.success && response.assignment) {
                        const assignment = response.assignment;
                        $('#assignmentId').val(assignment.id);
                        $('#assignmentTitle').val(assignment.title);
                        $('#assignmentDescription').val(assignment.description || '');
                        $('#assignmentInstructions').val(assignment.instructions || '');
                        $('#assignmentDueDate').val(assignment.due_date ? assignment.due_date.replace(' ', 'T').substring(0, 16) : '');
                        $('#assignmentMaxPoints').val(assignment.max_points || 100);
                        
                        $('#assignmentFormTitle').text('Edit Assignment');
                        $('#assignmentSubmitBtn').text('Update Assignment');
                        $('#assignmentCancelEdit').show();
                        
                        // Scroll to form
                        document.getElementById('assignmentFormSection').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        alert('Error loading assignment: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading assignment');
                }
            });
        }

        function resetAssignmentForm() {
            $('#assignmentId').val('');
            $('#assignmentTitle').val('');
            $('#assignmentDescription').val('');
            $('#assignmentInstructions').val('');
            $('#assignmentDueDate').val('');
            $('#assignmentMaxPoints').val('100');
            
            $('#assignmentFormTitle').text('Add New Assignment');
            $('#assignmentSubmitBtn').text('Add Assignment');
            $('#assignmentCancelEdit').hide();
        }

        function cancelAssignmentEdit() {
            resetAssignmentForm();
        }

        function deleteAssignment(assignmentId, lessonId) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                $.ajax({
                    url: '../api/lesson_assignments.php',
                    method: 'DELETE',
                    data: { id: assignmentId },
                    success: function(response) {
                        if (response.success) {
                            alert('Assignment deleted successfully!');
                            loadAssignments(lessonId);
                        } else {
                            alert('Error deleting assignment: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error deleting assignment');
                    }
                });
            }
        }

        function manageNotes(lessonId, lessonTitle) {
            // Open notes management modal
            const modalHtml = `
                <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="notesModalLabel">Manage Notes - ${lessonTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="notesList"></div>
                                <hr>
                                <div id="noteFormSection">
                                    <h6 id="noteFormTitle">Add New Note</h6>
                                    <form id="noteForm">
                                        <input type="hidden" name="lesson_id" value="${lessonId}">
                                        <input type="hidden" name="id" id="noteId" value="">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="title" id="noteTitle" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Content</label>
                                            <textarea class="form-control" name="content" id="noteContent" rows="5" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Visibility</label>
                                            <select class="form-control" name="visibility" id="noteVisibility">
                                                <option value="public">Public (All students)</option>
                                                <option value="private">Private (Only you)</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="noteSubmitBtn">Add Note</button>
                                        <button type="button" class="btn btn-secondary" id="noteCancelEdit" style="display:none;" onclick="cancelNoteEdit()">Cancel Edit</button>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#notesModal').remove();
            $('body').append(modalHtml);
            
            const modal = new bootstrap.Modal(document.getElementById('notesModal'));
            modal.show();
            
            loadNotes(lessonId);
            
            $('#noteForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const noteId = $('#noteId').val();
                const method = noteId ? 'PUT' : 'POST';
                const url = '../api/lesson_notes.php' + (noteId ? `?id=${noteId}` : '');
                
                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(noteId ? 'Note updated successfully!' : 'Note added successfully!');
                            loadNotes(lessonId);
                            resetNoteForm();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error saving note');
                    }
                });
            });
        }

        function loadNotes(lessonId) {
            $.ajax({
                url: '../api/lesson_notes.php',
                method: 'GET',
                data: { lesson_id: lessonId },
                success: function(response) {
                    let html = '';
                    if (response.success && response.notes && response.notes.length > 0) {
                        html = '<div class="list-group">';
                        response.notes.forEach(function(note) {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>${note.title}</strong>
                                            <span class="badge bg-${note.visibility === 'public' ? 'success' : 'secondary'} ms-2">${note.visibility}</span>
                                            <br>
                                            <small class="text-muted">${note.created_at}</small>
                                            <div class="mt-2">${note.content}</div>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-info me-1" onclick="editNote(${note.id}, ${lessonId})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(${note.id}, ${lessonId})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html = '<p class="text-muted">No notes found.</p>';
                    }
                    $('#notesList').html(html);
                },
                error: function() {
                    $('#notesList').html('<p class="text-danger">Error loading notes.</p>');
                }
            });
        }

        function editNote(noteId, lessonId) {
            $.ajax({
                url: '../api/lesson_notes.php',
                method: 'GET',
                data: { id: noteId },
                success: function(response) {
                    if (response.success && response.note) {
                        const note = response.note;
                        $('#noteId').val(note.id);
                        $('#noteTitle').val(note.title);
                        $('#noteContent').val(note.content || '');
                        $('#noteVisibility').val(note.visibility || 'public');
                        
                        $('#noteFormTitle').text('Edit Note');
                        $('#noteSubmitBtn').text('Update Note');
                        $('#noteCancelEdit').show();
                        
                        // Scroll to form
                        document.getElementById('noteFormSection').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        alert('Error loading note: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading note');
                }
            });
        }

        function resetNoteForm() {
            $('#noteId').val('');
            $('#noteTitle').val('');
            $('#noteContent').val('');
            $('#noteVisibility').val('public');
            
            $('#noteFormTitle').text('Add New Note');
            $('#noteSubmitBtn').text('Add Note');
            $('#noteCancelEdit').hide();
        }

        function cancelNoteEdit() {
            resetNoteForm();
        }

        function deleteNote(noteId, lessonId) {
            if (confirm('Are you sure you want to delete this note?')) {
                $.ajax({
                    url: '../api/lesson_notes.php',
                    method: 'DELETE',
                    data: { id: noteId },
                    success: function(response) {
                        if (response.success) {
                            alert('Note deleted successfully!');
                            loadNotes(lessonId);
                        } else {
                            alert('Error deleting note: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error deleting note');
                    }
                });
            }
        }

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

            $.ajax({
                url: '../api/save_course_details.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Course details saved successfully!');
                    } else {
                        alert('Error saving course details: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error saving course details');
                }
            });
        }

        // Handle settings form submission
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
                        alert('Settings saved successfully!');
                        location.reload();
                    } else {
                        alert('Error saving settings: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error saving settings');
                }
            });
        });
    </script>
</body>
</html>
