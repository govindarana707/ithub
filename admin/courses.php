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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .course-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: white;
        }
        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        .course-thumbnail {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }
        .course-thumbnail .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .course-card:hover .course-thumbnail .overlay {
            opacity: 1;
        }
        .course-price {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, #28a745, #20c997);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .course-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
        }
        .stat-item {
            text-align: center;
            flex: 1;
        }
        .stat-item .number {
            font-size: 1.3rem;
            font-weight: bold;
            color: #495057;
            display: block;
        }
        .stat-item .label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-sm:hover {
            transform: translateY(-2px);
        }
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card p {
            margin: 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .view-toggle .btn {
            border-radius: 8px;
        }
        .dataTables_wrapper {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .course-progress-ring {
            width: 60px;
            height: 60px;
            position: relative;
        }
        .course-progress-ring svg {
            transform: rotate(-90deg);
        }
        .course-progress-ring circle {
            fill: none;
            stroke-width: 4;
        }
        .course-progress-ring .background {
            stroke: #e9ecef;
        }
        .course-progress-ring .progress {
            stroke: #28a745;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }
        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .bulk-actions-bar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }
        .course-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .course-filters-advanced {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
                    <div>
                        <h1 class="mb-2">Advanced Course Management</h1>
                        <p class="text-muted mb-0">Manage your courses with powerful tools and insights</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="view-toggle">
                            <button class="btn btn-outline-primary btn-sm active" onclick="toggleView('grid')" id="gridViewBtn">
                                <i class="fas fa-th"></i> Grid
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="toggleView('table')" id="tableViewBtn">
                                <i class="fas fa-list"></i> Table
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('csv')">Export as CSV</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('excel')">Export as Excel</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('pdf')">Export as PDF</a></li>
                            </ul>
                        </div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="course-filters-advanced">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Advanced Filters</h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="toggleFilters()">
                            <i class="fas fa-chevron-up" id="filterToggleIcon"></i>
                        </button>
                    </div>
                    
                    <div id="advancedFilters">
                        <form method="GET" id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select select2">
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
                                    <select name="status" class="form-select select2">
                                        <option value="">All Status</option>
                                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Instructor</label>
                                    <select name="instructor" class="form-select select2">
                                        <option value="">All Instructors</option>
                                        <?php foreach ($instructors as $inst): ?>
                                            <option value="<?php echo $inst['id']; ?>" <?php echo ((string)$inst['id'] === (string)$instructor) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($inst['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Price Range</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-2">
                                    <label class="form-label">Difficulty</label>
                                    <select name="difficulty" class="form-select select2">
                                        <option value="">All Levels</option>
                                        <option value="beginner" <?php echo ($_GET['difficulty'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="intermediate" <?php echo ($_GET['difficulty'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo ($_GET['difficulty'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sort By</label>
                                    <select name="sort" class="form-select select2">
                                        <option value="created_at">Date Created</option>
                                        <option value="title">Title</option>
                                        <option value="price">Price</option>
                                        <option value="enrollment_count">Popularity</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Order</label>
                                    <select name="order" class="form-select select2">
                                        <option value="DESC">Descending</option>
                                        <option value="ASC">Ascending</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Filter
                                        </button>
                                        <a href="courses.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-undo me-2"></i>Clear
                                        </a>
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                            <i class="fas fa-plus me-2"></i>Add Course
                                        </button>
                                        <button type="button" class="btn btn-info" onclick="refreshCourses()">
                                            <i class="fas fa-sync-alt me-2"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
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

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulkActionsBar">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="selectedCount">0</span> courses selected
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" onclick="bulkPublish()">
                                <i class="fas fa-check me-1"></i>Publish
                            </button>
                            <button class="btn btn-light btn-sm" onclick="bulkUnpublish()">
                                <i class="fas fa-times me-1"></i>Unpublish
                            </button>
                            <button class="btn btn-light btn-sm" onclick="bulkDelete()">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                            <button class="btn btn-light btn-sm" onclick="bulkExport()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <button class="btn btn-outline-light btn-sm" onclick="clearSelection()">
                                <i class="fas fa-times me-1"></i>Clear Selection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading courses...</p>
                </div>

                <!-- Courses Grid View -->
                <div class="course-grid" id="gridView">
                    <?php if (empty($courses)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-book fa-4x text-muted mb-3"></i>
                            <h4>No courses found</h4>
                            <p class="text-muted">Try adjusting your filters or create a new course.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                <i class="fas fa-plus me-2"></i>Create First Course
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $courseRow): ?>
                            <div class="course-card" data-course-id="<?php echo $courseRow['id']; ?>">
                                <?php if ($courseRow['thumbnail']): ?>
                                    <div class="course-thumbnail">
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseRow['thumbnail'])); ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo htmlspecialchars($courseRow['title']); ?>">
                                        <div class="overlay"></div>
                                    </div>
                                <?php else: ?>
                                    <div class="course-thumbnail d-flex align-items-center justify-content-center">
                                        <i class="fas fa-book fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="course-badge">
                                    <span class="badge bg-<?php echo $courseRow['status'] === 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($courseRow['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($courseRow['title']); ?></h5>
                                        <input type="checkbox" class="form-check-input course-checkbox" value="<?php echo $courseRow['id']; ?>" onchange="updateBulkSelection()">
                                    </div>
                                    <p class="text-muted mb-3"><?php echo substr(strip_tags($courseRow['description']), 0, 120); ?>...</p>
                                    
                                    <div class="course-meta">
                                        <span class="course-price">Rs<?php echo number_format($courseRow['price'], 2); ?></span>
                                        <span class="badge bg-info">
                                            <i class="fas fa-signal me-1"></i><?php echo ucfirst($courseRow['difficulty_level'] ?? 'Beginner'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="course-stats">
                                        <div class="stat-item">
                                            <div class="number"><?php echo $courseRow['enrollment_count'] ?? 0; ?></div>
                                            <div class="label">Students</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="number"><?php echo round($courseRow['avg_progress'] ?? 0); ?>%</div>
                                            <div class="label">Progress</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="course-progress-ring">
                                                <svg width="60" height="60">
                                                    <circle class="background" cx="30" cy="30" r="25"></circle>
                                                    <circle class="progress" cx="30" cy="30" r="25" 
                                                        stroke-dasharray="157" 
                                                        stroke-dashoffset="<?php echo 157 - (157 * ($courseRow['avg_progress'] ?? 0) / 100); ?>">
                                                    </circle>
                                                </svg>
                                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold;">
                                                    <?php echo round($courseRow['avg_progress'] ?? 0); ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-dark" onclick="editCourse(<?php echo $courseRow['id']; ?>)" title="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCourse(<?php echo $courseRow['id']; ?>)" title="View Course">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="courseBuilder(<?php echo $courseRow['id']; ?>)" title="Course Builder">
                                            <i class="fas fa-screwdriver-wrench"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="duplicateCourse(<?php echo $courseRow['id']; ?>)" title="Duplicate Course">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="toggleStatus(<?php echo $courseRow['id']; ?>, '<?php echo $courseRow['status']; ?>')" title="Toggle Status">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(<?php echo $courseRow['id']; ?>)" title="Delete Course">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Courses Table View -->
                <div class="table-responsive" id="tableView" style="display: none;">
                    <table class="table table-hover" id="coursesTable">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Instructor</th>
                                <th>Price</th>
                                <th>Students</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $courseRow): ?>
                                <tr data-course-id="<?php echo $courseRow['id']; ?>">
                                    <td><input type="checkbox" class="form-check-input course-checkbox" value="<?php echo $courseRow['id']; ?>" onchange="updateBulkSelection()"></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($courseRow['thumbnail']): ?>
                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseRow['thumbnail'])); ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="">
                                            <?php else: ?>
                                                <div class="rounded me-3 bg-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-book text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($courseRow['title']); ?></div>
                                                <small class="text-muted"><?php echo substr(strip_tags($courseRow['description']), 0, 50); ?>...</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($courseRow['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($courseRow['instructor_name'] ?? 'N/A'); ?></td>
                                    <td>Rs<?php echo number_format($courseRow['price'], 2); ?></td>
                                    <td><?php echo $courseRow['enrollment_count'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $courseRow['avg_progress'] ?? 0; ?>%; background: linear-gradient(90deg, #28a745, #20c997);" aria-valuenow="<?php echo $courseRow['avg_progress'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small><?php echo round($courseRow['avg_progress'] ?? 0); ?>%</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $courseRow['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($courseRow['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCourse(<?php echo $courseRow['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewCourse(<?php echo $courseRow['id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(<?php echo $courseRow['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            
            // Initialize DataTable for table view
            $('#coursesTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[1, 'asc']],
                language: {
                    search: 'Search courses:',
                    lengthMenu: 'Show _MENU_ courses per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ courses',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                }
            });
        });
        
        // View Toggle
        let currentView = 'grid';
        function toggleView(view) {
            currentView = view;
            const gridView = document.getElementById('gridView');
            const tableView = document.getElementById('tableView');
            const gridBtn = document.getElementById('gridViewBtn');
            const tableBtn = document.getElementById('tableViewBtn');
            
            if (view === 'grid') {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
                gridBtn.classList.add('active');
                tableBtn.classList.remove('active');
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
                gridBtn.classList.remove('active');
                tableBtn.classList.add('active');
            }
        }
        
        // Filter Toggle
        function toggleFilters() {
            const filters = document.getElementById('advancedFilters');
            const icon = document.getElementById('filterToggleIcon');
            
            if (filters.style.display === 'none') {
                filters.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            } else {
                filters.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            }
        }
        
        // Clear Search
        function clearSearch() {
            document.querySelector('input[name="search"]').value = '';
            document.getElementById('filterForm').submit();
        }
        
        // Refresh Courses
        function refreshCourses() {
            const spinner = document.getElementById('loadingSpinner');
            spinner.style.display = 'block';
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
        
        // Bulk Selection
        function updateBulkSelection() {
            const checkboxes = document.querySelectorAll('.course-checkbox:checked');
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');
            
            selectedCount.textContent = checkboxes.length;
            bulkActionsBar.style.display = checkboxes.length > 0 ? 'block' : 'none';
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.course-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkSelection();
        }
        
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.course-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAll.checked = false;
            
            updateBulkSelection();
        }
        
        // Bulk Actions
        function bulkPublish() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Publish Courses?',
                text: `This will publish ${selectedIds.length} course(s).`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Publish'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('publish', selectedIds);
                }
            });
        }
        
        function bulkUnpublish() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Unpublish Courses?',
                text: `This will unpublish ${selectedIds.length} course(s).`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Unpublish'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('unpublish', selectedIds);
                }
            });
        }
        
        function bulkDelete() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Delete Courses?',
                text: `This will permanently delete ${selectedIds.length} course(s). This action cannot be undone!`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('delete', selectedIds);
                }
            });
        }
        
        function bulkExport() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) return;
            
            window.location.href = `api/export_courses.php?ids=${selectedIds.join(',')}`;
        }
        
        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.course-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        function performBulkAction(action, ids) {
            $.ajax({
                url: '../api/bulk_course_actions.php',
                method: 'POST',
                data: {
                    action: action,
                    course_ids: ids
                },
                beforeSend: function() {
                    Swal.showLoading();
                },
                success: function(response) {
                    Swal.hideLoading();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.'
                    });
                }
            });
        }
        
        // Course Actions
        function editCourse(courseId) {
            Swal.showLoading();
            $.get('../api/get_course.php', {course_id: courseId}, function(data) {
                Swal.hideLoading();
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load course data: ' + (data.message || 'Unknown')
                    });
                }
            }).fail(function() {
                Swal.hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error fetching course data. Check console.'
                });
            });
        }
        
        function viewCourse(courseId) {
            window.open('../student/view-course.php?id=' + courseId, '_blank');
        }
        
        function courseBuilder(courseId) {
            window.location.href = 'course_builder.php?id=' + courseId;
        }
        
        function duplicateCourse(courseId) {
            $('#duplicate_course_id').val(courseId);
            const modal = new bootstrap.Modal(document.getElementById('duplicateCourseModal'));
            modal.show();
        }
        
        function toggleStatus(courseId, currentStatus) {
            const newStatus = currentStatus === 'published' ? 'draft' : 'published';
            const actionText = newStatus === 'published' ? 'publish' : 'unpublish';
            
            Swal.fire({
                title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Course?`,
                text: `This will ${actionText} the course.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'published' ? '#28a745' : '#ffc107',
                confirmButtonText: actionText.charAt(0).toUpperCase() + actionText.slice(1)
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../api/toggle_course_status.php',
                        method: 'POST',
                        data: {
                            course_id: courseId,
                            status: newStatus
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: `Course ${actionText}ed successfully!`,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong. Please try again.'
                            });
                        }
                    });
                }
            });
        }
        
        function deleteCourse(courseId) {
            Swal.fire({
                title: 'Delete Course?',
                text: 'This will permanently delete the course. This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_course">
                        <input type="hidden" name="course_id" value="${courseId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // Export Functions
        function exportCourses(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            
            if (format === 'pdf') {
                Swal.fire({
                    title: 'PDF Export',
                    html: 'The PDF will be downloaded as an HTML file.<br><br><strong>Instructions:</strong><br>1. Download the file<br>2. Open in your browser<br>3. Print → Save as PDF',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'Download HTML for PDF',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `../api/export_courses.php?${params.toString()}`;
                    }
                });
            } else {
                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we prepare your export.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                window.location.href = `../api/export_courses.php?${params.toString()}`;
                
                setTimeout(() => {
                    Swal.close();
                }, 2000);
            }
        }
        
        // Real-time Search with Debounce
        let searchTimeout;
        document.querySelector('input[name="search"]')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length >= 3 || e.target.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K for search focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
            
            // Ctrl/Cmd + N for new course
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('createCourseModal'));
                modal.show();
            }
            
            // Escape to clear selection
            if (e.key === 'Escape') {
                clearSelection();
            }
        });
        
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            $.get('../api/get_course_stats.php', function(data) {
                if (data.success) {
                    // Update stats with animation
                    $('.stat-card h3').each(function(index) {
                        const newValue = Object.values(data.stats)[index];
                        const currentValue = $(this).text();
                        if (currentValue !== newValue.toString()) {
                            $(this).fadeOut(200, function() {
                                $(this).text(newValue).fadeIn(200);
                            });
                        }
                    });
                }
            });
        }, 30000);
    </script>
</body>
</html>
