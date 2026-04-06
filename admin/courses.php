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

// Use categories_new table
$categories = [];
try {
    $result = $conn->query("SELECT id, name FROM categories_new ORDER BY name");
    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $categories = [];
}

$totalCourses = $course->countAdminCourses($filters);
$totalPages = ceil($totalCourses / $limit);

$instructors = $user->getInstructors();

// Get popular courses for sidebar
$popularCourses = $course->getPopularCourses(5);

// Get course statistics
$courseStats = $course->getCourseStats();
require_once dirname(__DIR__) . '/includes/universal_header.php';
?>

<link rel="stylesheet" href="../assets/css/admin-theme.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<style>
/* Course-specific styles */
.course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.course-card {
    background: var(--admin-bg-primary);
    border-radius: var(--admin-radius-xl);
    border: 1px solid var(--admin-border-light);
    box-shadow: var(--admin-shadow-sm);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.course-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--admin-shadow-xl);
    border-color: var(--admin-primary-light);
}

.course-thumbnail {
    height: 160px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.course-card:hover .course-thumbnail img {
    transform: scale(1.05);
}

.course-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
}

.course-badge .badge {
    font-size: 0.75rem;
    padding: 0.5em 0.8em;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.course-card .card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.course-card .card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.course-card .course-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1rem 0;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--admin-border-light);
}

.course-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--admin-primary);
}

.course-card .course-stats-bar {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: linear-gradient(135deg, var(--admin-bg-secondary) 0%, #f1f5f9 100%);
    border-radius: var(--admin-radius-lg);
    margin-top: auto;
    margin-bottom: 1rem;
}

.course-card .stat-item {
    text-align: center;
    flex: 1;
}

.course-card .stat-item .number {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--admin-text-primary);
    display: block;
    line-height: 1;
}

.course-card .stat-item .label {
    font-size: 0.7rem;
    color: var(--admin-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.course-card .action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: center;
}

.course-card .action-buttons .btn {
    padding: 0.35rem 0.6rem;
    font-size: 0.8rem;
    border-radius: var(--admin-radius);
    transition: all 0.2s ease;
}

.course-card .action-buttons .btn:hover {
    transform: translateY(-2px);
}

.filter-section {
    background: var(--admin-bg-secondary);
    border-radius: var(--admin-radius-xl);
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid var(--admin-border);
}

.view-toggle .btn {
    border-radius: var(--admin-radius);
}

.bulk-actions-bar {
    background: var(--admin-gradient);
    color: white;
    padding: 15px 20px;
    border-radius: var(--admin-radius-lg);
    margin-bottom: 20px;
    display: none;
}

#tableView {
    background: var(--admin-bg-primary);
    border-radius: var(--admin-radius-xl);
    padding: 20px;
    border: 1px solid var(--admin-border);
}

#tableView .table {
    margin-bottom: 0;
}

#tableView .table thead th {
    background: var(--admin-bg-secondary);
    border-bottom: 2px solid var(--admin-border);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

#tableView .table tbody tr:hover {
    background: var(--admin-bg-secondary);
}

.progress {
    height: 6px;
    background: var(--admin-bg-tertiary);
    border-radius: 3px;
}

.progress-bar {
    background: var(--admin-success);
    border-radius: 3px;
}

.pagination .page-link {
    border: 1px solid var(--admin-border);
    color: var(--admin-text-primary);
    border-radius: var(--admin-radius);
    margin: 0 2px;
}

.pagination .page-item.active .page-link {
    background: var(--admin-gradient);
    border-color: var(--admin-primary);
    color: white;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Admin Dashboard Header -->
            <div class="admin-dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">📚 Course Management</h2>
                        <p class="mb-0 opacity-75">Manage your courses with powerful tools and insights</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="view-toggle">
                            <button class="btn btn-sm btn-outline-light active" onclick="toggleView('grid')" id="gridViewBtn">
                                <i class="fas fa-th me-1"></i> Grid
                            </button>
                            <button class="btn btn-sm btn-outline-light" onclick="toggleView('table')" id="tableViewBtn">
                                <i class="fas fa-list me-1"></i> Table
                            </button>
                        </div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="filter-section">
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
                                <select name="status" class="form-select">
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
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn-modern btn-primary-modern">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                    <a href="courses.php" class="btn-modern btn-secondary-modern">
                                        <i class="fas fa-undo me-2"></i>Clear
                                    </a>
                                    <button type="button" class="btn-modern btn-success-modern" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                        <i class="fas fa-plus me-2"></i>Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="admin-stat-card primary">
                        <div class="admin-stat-icon"><i class="fas fa-book"></i></div>
                        <div class="admin-stat-value"><?php echo $courseStats['total']; ?></div>
                        <div class="admin-stat-label">Total Courses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-stat-card success">
                        <div class="admin-stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="admin-stat-value"><?php echo $courseStats['published']; ?></div>
                        <div class="admin-stat-label">Published</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-stat-card info">
                        <div class="admin-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="admin-stat-value"><?php echo $courseStats['enrollments']; ?></div>
                        <div class="admin-stat-label">Total Enrollments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-stat-card warning">
                        <div class="admin-stat-icon"><i class="fas fa-question-circle"></i></div>
                        <div class="admin-stat-value"><?php echo $courseStats['total_attempts']; ?></div>
                        <div class="admin-stat-label">Quiz Attempts</div>
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

            <!-- Course Content Card -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-graduation-cap me-2"></i>
                            All Courses (<?php echo $totalCourses; ?>)
                        </div>
                        <div class="dropdown">
                            <button class="btn-modern btn-secondary-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('csv')">Export as CSV</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('excel')">Export as Excel</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportCourses('pdf')">Export as PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Courses Grid View -->
                    <div class="p-4">
                        <div class="course-grid" id="gridView">
                            <?php if (empty($courses)): ?>
                                <div class="col-12 text-center py-5">
                                    <div class="admin-empty-state">
                                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                        <h5>No courses found</h5>
                                        <p class="text-muted">Try adjusting your filters or create a new course.</p>
                                        <button type="button" class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                            <i class="fas fa-plus me-2"></i>Create First Course
                                        </button>
                                    </div>
                                </div>
                    <?php else: ?>
                        <?php foreach ($courses as $courseRow): ?>
                            <div class="course-card" data-course-id="<?php echo $courseRow['id']; ?>">
                                <div class="course-thumbnail">
                                    <?php if ($courseRow['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseRow['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($courseRow['title']); ?>">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <i class="fas fa-book fa-3x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="course-badge">
                                        <span class="badge bg-<?php echo $courseRow['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($courseRow['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($courseRow['title']); ?></h5>
                                        <input type="checkbox" class="form-check-input course-checkbox" value="<?php echo $courseRow['id']; ?>" onchange="updateBulkSelection()">
                                    </div>
                                    <p class="text-muted small mb-0"><?php echo substr(strip_tags($courseRow['description']), 0, 100); ?>...</p>
                                    
                                    <div class="course-meta">
                                        <span class="course-price">Rs<?php echo number_format($courseRow['price'], 2); ?></span>
                                        <span class="badge bg-info">
                                            <i class="fas fa-signal me-1"></i><?php echo ucfirst($courseRow['difficulty_level'] ?? 'Beginner'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="course-stats-bar">
                                        <div class="stat-item">
                                            <span class="number"><?php echo $courseRow['enrollment_count'] ?? 0; ?></span>
                                            <span class="label">Students</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="number"><?php echo round($courseRow['avg_progress'] ?? 0); ?>%</span>
                                            <span class="label">Progress</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="number"><?php echo $courseRow['lesson_count'] ?? 0; ?></span>
                                            <span class="label">Lessons</span>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-outline-primary" onclick="editCourse(<?php echo $courseRow['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-info" onclick="viewCourse(<?php echo $courseRow['id']; ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="courseBuilder(<?php echo $courseRow['id']; ?>)" title="Builder">
                                            <i class="fas fa-tools"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="duplicateCourse(<?php echo $courseRow['id']; ?>)" title="Duplicate">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $courseRow['id']; ?>, '<?php echo $courseRow['status']; ?>')" title="Toggle">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteCourse(<?php echo $courseRow['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div id="tableView" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="coursesTable">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()"></th>
                                        <th>Course</th>
                                        <th>Category</th>
                                        <th>Instructor</th>
                                        <th>Price</th>
                                        <th>Students</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th width="150">Actions</th>
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
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $courseRow['avg_progress'] ?? 0; ?>%" aria-valuenow="<?php echo $courseRow['avg_progress'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted"><?php echo round($courseRow['avg_progress'] ?? 0); ?>%</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $courseRow['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($courseRow['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editCourse(<?php echo $courseRow['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" onclick="viewCourse(<?php echo $courseRow['id']; ?>)" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteCourse(<?php echo $courseRow['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="p-4 pt-0">
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
                        <button type="submit" class="btn-modern btn-primary-modern">
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
                        <button type="submit" class="btn-modern btn-primary-modern">
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
                        <button type="submit" class="btn-modern btn-primary-modern">
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
                confirmButtonColor: '#4169E1',
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
                confirmButtonColor: '#4169E1',
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
