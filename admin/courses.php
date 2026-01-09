<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';

requireAdmin();

$course = new Course();
$user = new User();

// Handle form submissions
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';

    $thumbnailUploadPath = null;
    if (isset($_FILES['thumbnail_file']) && is_array($_FILES['thumbnail_file']) && ($_FILES['thumbnail_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFile($_FILES['thumbnail_file'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'course_thumbnails');
        if (!($upload['success'] ?? false)) {
            $_SESSION['error_message'] = 'Thumbnail upload failed: ' . ($upload['message'] ?? 'Unknown error');
            header('Location: courses.php');
            exit;
        }
        $thumbnailUploadPath = $upload['filename'];
    }
    
    switch ($action) {
        case 'create_course':
            $data = [
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description']),
                'category_id' => intval($_POST['category_id']),
                'instructor_id' => intval($_POST['instructor_id']),
                'price' => floatval($_POST['price']),
                'duration_hours' => intval($_POST['duration_hours']),
                'difficulty_level' => sanitize($_POST['difficulty_level']),
                'status' => sanitize($_POST['status']),
                'thumbnail' => $thumbnailUploadPath ?? sanitize($_POST['thumbnail'] ?? '')
            ];
            
            $result = $course->createCourse($data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course created successfully!';
                logActivity($_SESSION['user_id'], 'course_created', "Created course: {$data['title']}");
            } else {
                $_SESSION['error_message'] = 'Failed to create course: ' . $result['error'];
            }
            break;
            
        case 'update_course':
            $courseId = intval($_POST['course_id']);
            $data = [
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description']),
                'category_id' => intval($_POST['category_id']),
                'instructor_id' => intval($_POST['instructor_id']),
                'price' => floatval($_POST['price']),
                'duration_hours' => intval($_POST['duration_hours']),
                'difficulty_level' => sanitize($_POST['difficulty_level']),
                'status' => sanitize($_POST['status']),
                'thumbnail' => $thumbnailUploadPath ?? sanitize($_POST['thumbnail'] ?? '')
            ];
            
            if ($course->updateCourse($courseId, $data)) {
                $_SESSION['success_message'] = 'Course updated successfully!';
                logActivity($_SESSION['user_id'], 'course_updated', "Updated course ID: $courseId");
            } else {
                $_SESSION['error_message'] = 'Failed to update course';
            }
            break;
            
        case 'delete_course':
            $courseId = intval($_POST['course_id']);
            $result = $course->deleteCourse($courseId);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course deleted successfully!';
                logActivity($_SESSION['user_id'], 'course_deleted', "Deleted course ID: $courseId");
            } else {
                $_SESSION['error_message'] = 'Failed to delete course: ' . $result['error'];
            }
            break;
            
        case 'duplicate_course':
            $courseId = intval($_POST['course_id']);
            $newTitle = sanitize($_POST['new_title']);
            
            $result = $course->duplicateCourse($courseId, $newTitle);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Course duplicated successfully!';
                logActivity($_SESSION['user_id'], 'course_duplicated', "Duplicated course ID: $courseId to new ID: {$result['new_course_id']}");
            } else {
                $_SESSION['error_message'] = 'Failed to duplicate course: ' . $result['error'];
            }
            break;
    }
    
    header('Location: courses.php');
    exit;
}

// Get courses with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$instructor = $_GET['instructor'] ?? '';

$filters = [
    'search' => $search,
    'category_id' => $category,
    'status' => $status,
    'instructor_id' => $instructor
];

$courses = $course->getAdminCourses($filters, $limit, $offset);

// Get total count for pagination
$db = new Database();
$conn = $db->getConnection();
$totalCourses = $course->countAdminCourses($filters);
$totalPages = ceil($totalCourses / $limit);

// Get categories and instructors
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$instructors = $user->getInstructors();

// Get popular courses for sidebar
$popularCourses = $course->getPopularCourses(5);

// Get course statistics
$courseStats = $course->getCourseStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Course Management - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .course-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .course-thumbnail {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .course-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .course-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #495057;
        }
        .stat-item .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
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
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="users.php">User Management</a></li>
                        <li><a class="dropdown-item" href="courses.php">Course Management</a></li>
                        <li><a class="dropdown-item" href="analytics.php">Analytics</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
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
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2"></i> User Management
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-book-open me-2"></i> Course Management
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags me-2"></i> Categories
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Reports
                    </a>
                    <a href="logs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
                
                <!-- Course Statistics Sidebar -->
                <div class="list-group mt-3">
                    <div class="list-group-item active">
                        <i class="fas fa-chart-pie me-2"></i> Statistics
                    </div>
                    <div class="list-group-item">
                        <i class="fas fa-trophy me-2"></i> Popular Courses
                    </div>
                </div>
                
                <!-- Popular Courses -->
                <div class="list-group mt-3">
                    <h6 class="list-group-item active">Popular Courses</h6>
                    <?php foreach ($popularCourses as $popularCourse): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($popularCourse['title']); ?></span>
                                <span class="badge bg-primary"><?php echo $popularCourse['enrollment_count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Advanced Course Management</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ((string)$cat['id'] === (string)$category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Instructor</label>
                            <select name="instructor" class="form-select">
                                <option value="">All Instructors</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?php echo $inst['id']; ?>" <?php echo ((string)$inst['id'] === (string)$instructor) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($inst['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="courses.php" class="btn btn-outline-secondary">Clear</a>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                    <i class="fas fa-plus me-2"></i>Add Course
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Course Statistics Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <h3><?php echo $courseStats['total']; ?></h3>
                            <p>Total Courses</p>
                            <small><i class="fas fa-book"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3><?php echo $courseStats['published']; ?></h3>
                            <p>Published</p>
                            <small><i class="fas fa-check-circle"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <h3><?php echo $courseStats['enrollments']; ?></h3>
                            <p>Total Enrollments</p>
                            <small><i class="fas fa-users"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <h3><?php echo $courseStats['total_attempts']; ?></h3>
                            <p>Quiz Attempts</p>
                            <small><i class="fas fa-question-circle"></i></small>
                        </div>
                    </div>
                </div>

                <!-- Courses Grid -->
                <div class="course-grid">
                    <?php if (empty($courses)): ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-2"></i>
                            <h5>No courses found</h5>
                            <p class="text-muted">Try adjusting your filters or create a new course.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                <i class="fas fa-plus me-2"></i>Create First Course
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $courseRow): ?>
                            <div class="course-card">
                                <?php if ($courseRow['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseRow['thumbnail'])); ?>" class="course-thumbnail" alt="<?php echo htmlspecialchars($courseRow['title']); ?>">
                                <?php else: ?>
                                    <div class="course-thumbnail d-flex align-items-center justify-content-center">
                                        <i class="fas fa-book fa-2x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($courseRow['title']); ?></h5>
                                    <p class="text-muted"><?php echo substr(strip_tags($courseRow['description']), 0, 100); ?>...</p>
                                    
                                    <div class="course-meta">
                                        <span class="course-price">Rs<?php echo number_format($courseRow['price'], 2); ?></span>
                                        <span class="badge bg-<?php echo $courseRow['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($courseRow['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="course-stats">
                                        <div class="stat-item">
                                            <div class="number"><?php echo $courseRow['enrollment_count'] ?? 0; ?></div>
                                            <div class="label">Students</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="number"><?php echo $courseRow['avg_progress'] ?? 0; ?>%</div>
                                            <div class="label">Avg Progress</div>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-dark" href="course_builder.php?id=<?php echo $courseRow['id']; ?>" title="Course Builder">
                                            <i class="fas fa-screwdriver-wrench"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCourse(<?php echo $courseRow['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="viewCourse(<?php echo $courseRow['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="duplicateCourse(<?php echo $courseRow['id']; ?>)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(<?php echo $courseRow['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&instructor=<?php echo $instructor; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&instructor=<?php echo $instructor; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&instructor=<?php echo $instructor; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div class="modal fade" id="createCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_course">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course Title *</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Instructor *</label>
                                    <select name="instructor_id" class="form-select" required>
                                        <option value="">Select Instructor</option>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (Rs) *</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Duration (hours) *</label>
                                    <input type="number" name="duration_hours" class="form-control" min="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Difficulty Level *</label>
                                    <select name="difficulty_level" class="form-select" required>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-select" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Thumbnail (upload)</label>
                            <input type="file" name="thumbnail_file" class="form-control" accept="image/*">
                            <div class="form-text">Supported: JPG, PNG, GIF, WEBP</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thumbnail URL (optional)</label>
                            <input type="url" name="thumbnail" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Course
                        </button>
                    </div>
                </form>
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
                                
                                <div class="mb-3">
                                    <label class="form-label">Instructor *</label>
                                    <select name="instructor_id" id="edit_instructor_id" class="form-select" required>
                                        <option value="">Select Instructor</option>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (Rs) *</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Duration (hours) *</label>
                                    <input type="number" name="duration_hours" id="edit_duration_hours" class="form-control" min="1">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Difficulty Level *</label>
                                    <select name="difficulty_level" id="edit_difficulty_level" class="form-select">
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select name="status" id="edit_status" class="form-select">
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Thumbnail (upload)</label>
                            <input type="file" name="thumbnail_file" class="form-control" accept="image/*">
                            <div class="form-text">Uploading a new file will replace the current thumbnail.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thumbnail URL (optional)</label>
                            <input type="url" name="thumbnail" id="edit_thumbnail" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Duplicate Course Modal -->
    <div class="modal fade" id="duplicateCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Duplicate Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="duplicate_course">
                        <input type="hidden" name="course_id" id="duplicate_course_id">
                        
                        <div class="mb-3">
                            <label class="form-label">New Course Title *</label>
                            <input type="text" name="new_title" class="form-control" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> This will create a copy of the course with all lessons and content.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-copy me-2"></i>Duplicate Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function editCourse(courseId) {
            console.log('editCourse called with ID:', courseId);
            $.get('../api/get_course.php', {course_id: courseId}, function(data) {
                console.log('API response:', data);
                if (data.success) {
                    $('#edit_course_id').val(data.course.id);
                    $('#edit_title').val(data.course.title);
                    $('#edit_description').val(data.course.description);
                    $('#edit_category_id').val(data.course.category_id);
                    $('#edit_instructor_id').val(data.course.instructor_id);
                    $('#edit_price').val(data.course.price);
                    $('#edit_duration_hours').val(data.course.duration_hours);
                    $('#edit_difficulty_level').val(data.course.difficulty_level);
                    $('#edit_status').val(data.course.status);
                    $('#edit_thumbnail').val(data.course.thumbnail);
                    
                    const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
                    modal.show();
                } else {
                    alert('Failed to load course data: ' + (data.message || 'Unknown'));
                }
            }).fail(function(xhr, status, err) {
                console.error('API call failed:', status, err, xhr.responseText);
                alert('Error fetching course data. Check console.');
            });
        }
        
        function viewCourse(courseId) {
            window.open('../student/my-courses.php?course_id=' + courseId, '_blank');
        }
        
        function duplicateCourse(courseId) {
            $('#duplicate_course_id').val(courseId);
            const modal = new bootstrap.Modal(document.getElementById('duplicateCourseModal'));
            modal.show();
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
        
        function exportCourses() {
            window.location.href = 'api/export_courses.php';
        }
        
        function bulkActions() {
            const selectedCourses = document.querySelectorAll('.course-checkbox:checked');
            if (selectedCourses.length === 0) {
                alert('Please select courses to perform bulk actions');
                return;
            }
            
            const courseIds = Array.from(selectedCourses).map(cb => cb.value);
            
            if (confirm(`Perform bulk actions on ${courseIds.length} courses?`)) {
                // Implement bulk actions here
                console.log('Bulk actions on courses:', courseIds);
            }
        }
    </script>
</body>
</html>
