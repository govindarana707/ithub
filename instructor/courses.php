<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';
require_once '../models/User.php';

$instructor = new Instructor();
$course = new Course();
$user = new User();

$instructorId = $_SESSION['user_id'];

// Debug: Uncomment to see session and query state
/*
error_log('Session: ' . print_r($_SESSION, true));
error_log('Instructor ID: ' . $instructorId);
*/

// Handle form submissions
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_course':
            $data = [
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description']),
                'category_id' => intval($_POST['category_id']),
                'price' => floatval($_POST['price']),
                'duration_hours' => intval($_POST['duration_hours']),
                'difficulty_level' => sanitize($_POST['difficulty_level']),
                'status' => sanitize($_POST['status'] ?? 'draft'),
                'thumbnail' => sanitize($_POST['thumbnail'] ?? '')
            ];
            
            $result = $instructor->createInstructorCourse($instructorId, $data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course created successfully!';
                logActivity($_SESSION['user_id'], 'course_created', "Created course: {$data['title']}");
            } else {
                $_SESSION['error_message'] = 'Failed to create course: ' . $result['error'];
            }
            header('Location: courses.php');
            exit;
            
        case 'update_course':
            $courseId = intval($_POST['course_id']);
            $data = [
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description']),
                'category_id' => intval($_POST['category_id']),
                'price' => floatval($_POST['price']),
                'duration_hours' => intval($_POST['duration_hours']),
                'difficulty_level' => sanitize($_POST['difficulty_level']),
                'status' => sanitize($_POST['status']),
                'thumbnail' => sanitize($_POST['thumbnail'] ?? '')
            ];
            
            $result = $instructor->updateInstructorCourse($instructorId, $courseId, $data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course updated successfully!';
                logActivity($_SESSION['user_id'], 'course_updated', "Updated course ID: $courseId");
            } else {
                $_SESSION['error_message'] = 'Failed to update course: ' . $result['error'];
            }
            header('Location: courses.php');
            exit;
            
        case 'delete_course':
            $courseId = intval($_POST['course_id']);
            $result = $instructor->deleteInstructorCourse($instructorId, $courseId);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course deleted successfully!';
                logActivity($_SESSION['user_id'], 'course_deleted', "Deleted course ID: $courseId");
            } else {
                $_SESSION['error_message'] = 'Failed to delete course: ' . $result['error'];
            }
            header('Location: courses.php');
            exit;
    }
}

// Get courses with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$instructorCourses = $instructor->getInstructorCourses($instructorId, $status, $limit, $offset);

// Get total count for pagination
$conn = connectDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE instructor_id = ?");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$totalCourses = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalCourses / $limit);

// Get categories
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get instructor statistics
$instructorStats = $instructor->getInstructorAnalytics($instructorId);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .course-card-modern {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .course-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }
        .course-thumbnail-modern {
            height: 200px;
            object-fit: cover;
        }
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-img-top {
            border-top-left-radius: 0.375rem !important;
            border-top-right-radius: 0.375rem !important;
        }
        .badge.rounded-pill {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.775rem;
        }
        .card-title {
            line-height: 1.3;
        }
        .card-body {
            padding: 1.25rem;
        }
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
        }
        .border-0 {
            border: 0 !important;
        }
        .opacity-75 {
            opacity: 0.75 !important;
        }
        .opacity-50 {
            opacity: 0.5 !important;
        }
        .fw-semibold {
            font-weight: 600 !important;
        }
        .flex-fill {
            flex: 1 1 auto !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-chalkboard-teacher me-1"></i> My Courses
                </a>
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users me-1"></i> Students
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="fas fa-chart-line me-1"></i> Analytics
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="create-course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i> Quizzes
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
                
                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Quick Stats</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Courses:</span>
                            <strong><?php echo $instructorStats['overview']['total_courses'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Published:</span>
                            <strong><?php echo $instructorStats['overview']['published_courses'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Students:</span>
                            <strong><?php echo $instructorStats['overview']['total_students'] ?? 0; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">My Courses</h1>
                    <div>
                        <a href="create-course.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Course
                        </a>
                    </div>
                </div>

                <!-- Analytics Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-0">Total Courses</h6>
                                        <h3 class="mb-0"><?php echo $instructorStats['overview']['total_courses'] ?? 0; ?></h3>
                                    </div>
                                    <div class="ms-3">
                                        <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-0">Published</h6>
                                        <h3 class="mb-0"><?php echo $instructorStats['overview']['published_courses'] ?? 0; ?></h3>
                                    </div>
                                    <div class="ms-3">
                                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-0">Total Students</h6>
                                        <h3 class="mb-0"><?php echo $instructorStats['overview']['total_students'] ?? 0; ?></h3>
                                    </div>
                                    <div class="ms-3">
                                        <i class="fas fa-users fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-0">Avg Progress</h6>
                                        <h3 class="mb-0"><?php echo round($instructorStats['overview']['avg_progress'] ?? 0, 1); ?>%</h3>
                                    </div>
                                    <div class="ms-3">
                                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mini Chart -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Enrollment Trend</h6>
                        <canvas id="enrollmentChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Search Courses</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sort By</label>
                                <select name="sort" class="form-select">
                                    <option value="created_at">Created Date</option>
                                    <option value="title">Title</option>
                                    <option value="enrollment_count">Students</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="courses.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses Grid -->
                <div class="row g-4">
                    <?php if (empty($instructorCourses)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chalkboard-teacher fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted">No courses found</h4>
                            <p class="text-muted mb-4">Start creating your first course to share your knowledge with students.</p>
                            <a href="create-course.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Create Your First Course
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($instructorCourses as $course): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm course-card-modern">
                                    <?php if ($course['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" class="card-img-top course-thumbnail-modern" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top course-thumbnail-modern d-flex align-items-center justify-content-center bg-gradient">
                                            <i class="fas fa-book fa-3x text-white opacity-75"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?> rounded-pill">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <p class="text-muted small flex-grow-1"><?php echo substr(strip_tags($course['description']), 0, 100); ?>...</p>
                                        
                                        <div class="mb-3">
                                            <span class="badge bg-light text-dark me-1">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($course['category_name']); ?>
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <div class="text-center p-2 bg-light rounded">
                                                    <div class="fw-bold text-primary"><?php echo $course['enrollment_count'] ?? 0; ?></div>
                                                    <div class="small text-muted">Students</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-center p-2 bg-light rounded">
                                                    <div class="fw-bold text-success"><?php echo round($course['avg_progress'] ?? 0, 1); ?>%</div>
                                                    <div class="small text-muted">Progress</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-center p-2 bg-light rounded">
                                                    <div class="fw-bold text-info"><?php echo $course['lesson_count'] ?? 0; ?></div>
                                                    <div class="small text-muted">Lessons</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-1 mt-auto">
                                            <a href="../admin/course_builder.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary flex-fill" title="Course Builder">
                                                <i class="fas fa-screwdriver-wrench"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewCourseStats(<?php echo $course['id']; ?>)" title="Statistics">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="manageStudents(<?php echo $course['id']; ?>)" title="Students">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editCourse(<?php echo $course['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(<?php echo $course['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCourseForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_course">
                        <input type="hidden" name="course_id" id="edit_course_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course Title *</label>
                                    <input type="text" name="title" id="edit_title" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category_id" id="edit_category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (Rs) *</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Duration (hours) *</label>
                                    <input type="number" name="duration_hours" id="edit_duration_hours" class="form-control" min="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Difficulty Level *</label>
                                    <select name="difficulty_level" id="edit_difficulty_level" class="form-select" required>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select name="status" id="edit_status" class="form-select" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Course Thumbnail</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Upload from Computer</label>
                                    <input type="file" name="course_thumbnail_file" id="course_thumbnail_file" class="form-control" accept="image/*">
                                    <div class="form-text small">JPG, PNG, GIF, WebP (Max: 10MB)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Or enter Image URL</label>
                                    <input type="url" name="thumbnail" id="edit_thumbnail" class="form-control" placeholder="https://...">
                                    <div class="form-text small">External image URL</div>
                                </div>
                            </div>
                            <div id="thumbnail_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function editCourse(courseId) {
            // Load course data via AJAX and populate modal
            $.ajax({
                url: '../api/get_course.php',
                method: 'GET',
                data: { course_id: courseId },
                success: function(response) {
                    const course = response.course;
                    $('#edit_course_id').val(course.id);
                    $('#edit_title').val(course.title);
                    $('#edit_description').val(course.description);
                    $('#edit_category_id').val(course.category_id);
                    $('#edit_price').val(course.price);
                    $('#edit_duration_hours').val(course.duration_hours);
                    $('#edit_difficulty_level').val(course.difficulty_level);
                    $('#edit_status').val(course.status);
                    $('#edit_thumbnail').val(course.thumbnail);
                    
                    // Show current thumbnail preview
                    if (course.thumbnail) {
                        $('#thumbnail_preview').html(
                            '<div class="d-flex align-items-center gap-2">' +
                            '<img src="' + resolveUploadUrl(course.thumbnail) + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Current thumbnail">' +
                            '<span class="small text-muted">Current thumbnail</span>' +
                            '</div>'
                        );
                    } else {
                        $('#thumbnail_preview').html('');
                    }
                    
                    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
                },
                error: function() {
                    alert('Error loading course data');
                }
            });
        }
        
        function deleteCourse(courseId) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" value="${courseId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewCourseStats(courseId) {
            window.location.href = `course-stats.php?id=${courseId}`;
        }
        
        function manageStudents(courseId) {
            window.location.href = `course-students.php?id=${courseId}`;
        }
        
        // Handle file upload preview
        $(document).on('change', '#course_thumbnail_file', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size too large. Maximum size is 10MB');
                    $(this).val('');
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#thumbnail_preview').html(
                        '<div class="d-flex align-items-center gap-2">' +
                        '<img src="' + e.target.result + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview">' +
                        '<span class="small text-success">New image selected</span>' +
                        '</div>'
                    );
                };
                reader.readAsDataURL(file);
                
                // Clear URL field when file is selected
                $('#edit_thumbnail').val('');
            }
        });
        
        // Handle URL input change
        $(document).on('input', '#edit_thumbnail', function() {
            const url = $(this).val();
            if (url) {
                // Clear file input when URL is entered
                $('#course_thumbnail_file').val('');
                
                // Show preview if valid URL
                if (url.match(/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
                    $('#thumbnail_preview').html(
                        '<div class="d-flex align-items-center gap-2">' +
                        '<img src="' + url + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview" onerror="this.style.display=\'none\'">' +
                        '<span class="small text-info">URL image</span>' +
                        '</div>'
                    );
                }
            }
        });
        
        // Handle form submission with file upload
        $('#editCourseForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const courseId = $('#edit_course_id').val();
            
            // Check if file is uploaded
            const fileInput = $('#course_thumbnail_file')[0];
            if (fileInput.files.length > 0) {
                // Upload file first
                const fileFormData = new FormData();
                fileFormData.append('course_thumbnail', fileInput.files[0]);
                fileFormData.append('course_id', courseId);
                
                $.ajax({
                    url: '../api/upload_course_thumbnail.php',
                    method: 'POST',
                    data: fileFormData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Update thumbnail field with uploaded file path
                            formData.set('thumbnail', response.thumbnail_path);
                            submitCourseUpdate(formData);
                        } else {
                            alert('Failed to upload thumbnail: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error uploading thumbnail');
                    }
                });
            } else {
                // No file upload, submit form normally
                submitCourseUpdate(formData);
            }
        });
        
        function submitCourseUpdate(formData) {
            $.ajax({
                url: 'courses.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    // Reload page to show updated course
                    window.location.reload();
                },
                error: function() {
                    alert('Error updating course');
                }
            });
        }
        
        function resolveUploadUrl(path) {
            if (!path) return '';
            if (path.match(/^https?:\/\//i)) return path;
            return '<?php echo BASE_URL; ?>uploads/' + path;
        }
    </script>
</body>
</html>
