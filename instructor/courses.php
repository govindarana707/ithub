<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];

// Generate CSRF token for AJAX requests
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get courses with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$instructorCourses = $instructor->getInstructorCourses($instructorId, $status, $limit, $offset);

// Get total count for pagination
$conn = connectDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses_new WHERE instructor_id = ?");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$totalCourses = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalCourses / $limit);

// Get categories
$categoriesResult = $conn->query("SELECT id, name FROM categories_new ORDER BY name");
if ($categoriesResult === false) {
    $categories = [];
} else {
    $categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);
}

// Get instructor statistics
$instructorStats = $instructor->getInstructorAnalytics($instructorId);
$conn->close();
?>

<?php require_once '../includes/universal_header.php'; ?>

<!-- Enhanced Courses Page Styles -->
<style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        .navbar-nav .nav-link {
            color: var(--dark-color) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .navbar-nav .nav-link:hover {
            background: var(--primary-color);
            color: white !important;
            transform: translateY(-1px);
        }

        /* Enhanced Sidebar */
        .sidebar-modern {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .sidebar-modern .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            color: var(--dark-color);
            border-left: 4px solid transparent;
        }

        .sidebar-modern .list-group-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateX(8px);
            border-left-color: var(--primary-color);
        }

        .sidebar-modern .list-group-item.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 600;
            border-left-color: white;
        }

        .sidebar-modern .list-group-item.active:hover {
            transform: translateX(0);
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card.primary::before { background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); }
        .stat-card.success::before { background: linear-gradient(90deg, var(--success-color), #059669); }
        .stat-card.info::before { background: linear-gradient(90deg, var(--info-color), #0891b2); }
        .stat-card.warning::before { background: linear-gradient(90deg, var(--warning-color), #d97706); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, var(--success-color), #059669); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, var(--info-color), #1d4ed8); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning-color), #d97706); color: white; }

        /* Course Cards */
        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .course-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .course-card .course-actions {
            position: relative;
            z-index: 10;
        }

        .course-thumbnail {
            height: 200px;
            object-fit: cover;
            position: relative;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .course-thumbnail-placeholder {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .course-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 5;
        }

        .course-status-badge.published {
            background: rgba(16, 185, 129, 0.9);
            color: white;
        }

        .course-status-badge.draft {
            background: rgba(245, 158, 11, 0.9);
            color: white;
        }

        .course-status-badge.archived {
            background: rgba(107, 114, 128, 0.9);
            color: white;
        }

        .course-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .course-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-description {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-meta {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .course-meta-item {
            padding: 0.25rem 0.75rem;
            background: var(--light-color);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--dark-color);
            border: 1px solid var(--border-color);
        }

        .course-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .course-stat {
            text-align: center;
            padding: 0.75rem;
            background: var(--light-color);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .course-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .course-stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .course-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .course-action-btn {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark-color);
            transition: all 0.3s ease;
            cursor: pointer !important;
            text-align: center;
            text-decoration: none;
            pointer-events: auto !important;
            position: relative !important;
            z-index: 100 !important;
        }

        .course-action-btn:disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }

        .course-action-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .course-action-btn.primary { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .course-action-btn.info { background: var(--info-color); color: white; border-color: var(--info-color); }
        .course-action-btn.success { background: var(--success-color); color: white; border-color: var(--success-color); }
        .course-action-btn.warning { background: var(--warning-color); color: white; border-color: var(--warning-color); }
        .course-action-btn.danger { background: var(--danger-color); color: white; border-color: var(--danger-color); }

        /* Enhanced Filters */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline-secondary {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
        }

        .empty-state-description {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }

        /* Pagination */
        .pagination .page-link {
            border: none;
            margin: 0 0.25rem;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: var(--dark-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        /* Modal Enhancements */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 0 0 16px 16px;
        }

        /* Enhanced animations */
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

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Dashboard Header Styles (matching dashboard.php) */
        .dashboard-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .header-subtitle {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.75rem;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .course-stats {
                grid-template-columns: 1fr;
            }
            
            .course-actions {
                flex-wrap: wrap;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php require_once '../includes/instructor_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Header -->
                <div class="dashboard-header mb-4">
                    <div class="header-content">
                        <div class="header-left">
                            <h1 class="header-title">My Courses</h1>
                            <p class="header-subtitle">Manage and monitor your educational content</p>
                        </div>
                        <div class="header-right">
                            <div class="header-actions">
                                <button type="button" class="btn btn-primary btn-lg" id="btnCreateCourse">
                                    <i class="fas fa-plus me-2"></i>Create New Course
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary animate-fade-in-up" style="animation-delay: 0.1s;">
                            <div class="stat-icon primary">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="statsTotalCourses"><?php echo $instructorStats['overview']['total_courses'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success animate-fade-in-up" style="animation-delay: 0.2s;">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="statsPublishedCourses"><?php echo $instructorStats['overview']['published_courses'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Published</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info animate-fade-in-up" style="animation-delay: 0.3s;">
                            <div class="stat-icon info">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="statsTotalStudents"><?php echo $instructorStats['overview']['total_students'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning animate-fade-in-up" style="animation-delay: 0.4s;">
                            <div class="stat-icon warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="statsAvgProgress"><?php echo round($instructorStats['overview']['avg_progress'] ?? 0, 1); ?>%</h3>
                            <p class="text-muted mb-0">Avg Progress</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card animate-fade-in-up" style="animation-delay: 0.5s;">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Search Courses</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search by title..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select id="sortBy" class="form-select">
                                <option value="created_at">Created Date</option>
                                <option value="title">Title</option>
                                <option value="enrollment_count">Students</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary flex-fill" id="btnFilter">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnClearFilter">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Grid -->
                <div class="row g-4" id="coursesContainer">
                    <?php if (empty($instructorCourses)): ?>
                        <div class="col-12" id="emptyState">
                            <div class="empty-state animate-fade-in-up" style="animation-delay: 0.6s;">
                                <div class="empty-state-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h2 class="empty-state-title">No courses found</h2>
                                <p class="empty-state-description">Start creating your first course to share your knowledge with students.</p>
                                <button type="button" class="btn btn-primary btn-lg" id="btnCreateCourseEmpty">
                                    <i class="fas fa-plus me-2"></i>Create Your First Course
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($instructorCourses as $index => $course): ?>
                            <div class="col-md-6 col-lg-4 course-item" data-course-id="<?php echo $course['id']; ?>">
                                <div class="course-card animate-fade-in-up" style="animation-delay: <?php echo 0.6 + ($index * 0.1); ?>s;">
                                    <?php if ($course['thumbnail']): ?>
                                        <div class="course-thumbnail" style="position: relative;">
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                            <span class="course-status-badge <?php echo $course['status']; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="course-thumbnail-placeholder">
                                            <i class="fas fa-book"></i>
                                            <span class="course-status-badge <?php echo $course['status']; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="course-body">
                                        <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p class="course-description"><?php echo strip_tags($course['description']); ?></p>
                                        
                                        <div class="course-meta">
                                            <span class="course-meta-item">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($course['category_name']); ?>
                                            </span>
                                            <span class="course-meta-item">
                                                <i class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="course-stats">
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo $course['enrollment_count'] ?? 0; ?></span>
                                                <span class="course-stat-label">Students</span>
                                            </div>
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo round($course['avg_progress'] ?? 0, 1); ?>%</span>
                                                <span class="course-stat-label">Progress</span>
                                            </div>
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo $course['lesson_count'] ?? 0; ?></span>
                                                <span class="course-stat-label">Lessons</span>
                                            </div>
                                        </div>
                                        
                                        <div class="course-actions">
                                            <a href="course_builder.php?id=<?php echo $course['id']; ?>" class="course-action-btn primary" title="Course Builder">
                                                <i class="fas fa-screwdriver-wrench"></i>
                                            </a>
                                            <button type="button" class="course-action-btn info" onclick="viewCourseStats(<?php echo $course['id']; ?>)" title="Statistics">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button type="button" class="course-action-btn success" onclick="manageStudents(<?php echo $course['id']; ?>)" title="Students">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button type="button" class="course-action-btn warning" onclick="editCourse(<?php echo $course['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="course-action-btn danger" onclick="deleteCourse(<?php echo $course['id']; ?>)" title="Delete">
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
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center" id="paginationContainer">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $page - 1; ?>)">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadPage(1)">1</a></li>';
                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $i; ?>)"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php 
                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadPage(' . $totalPages . ')">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(<?php echo $page + 1; ?>)">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div class="modal fade" id="createCourseModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createCourseForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Course Title *</label>
                                    <input type="text" name="title" id="create_title" class="form-control" required minlength="3" placeholder="Enter course title">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Category *</label>
                                    <select name="category_id" id="create_category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Price (Rs) *</label>
                                    <input type="number" name="price" id="create_price" class="form-control" step="0.01" min="0" value="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Duration (hours) *</label>
                                    <input type="number" name="duration_hours" id="create_duration_hours" class="form-control" min="1" value="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Difficulty Level *</label>
                                    <select name="difficulty_level" id="create_difficulty_level" class="form-select" required>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status *</label>
                                    <select name="status" id="create_status" class="form-select" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description *</label>
                            <textarea name="description" id="create_description" class="form-control" rows="4" required minlength="10" placeholder="Enter course description (min 10 characters)"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Thumbnail</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Upload from Computer</label>
                                    <input type="file" name="course_thumbnail_file" id="create_thumbnail_file" class="form-control" accept="image/*">
                                    <div class="form-text small">JPG, PNG, GIF, WebP (Max: 10MB)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Or enter Image URL</label>
                                    <input type="url" name="thumbnail" id="create_thumbnail" class="form-control" placeholder="https://...">
                                    <div class="form-text small">External image URL</div>
                                </div>
                            </div>
                            <div id="create_thumbnail_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnCreateSubmit">
                            <i class="fas fa-plus me-2"></i>Create Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCourseForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="course_id" id="edit_course_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Course Title *</label>
                                    <input type="text" name="title" id="edit_title" class="form-control" required minlength="3" placeholder="Enter course title">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Category *</label>
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
                                    <label class="form-label fw-bold">Price (Rs) *</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Duration (hours) *</label>
                                    <input type="number" name="duration_hours" id="edit_duration_hours" class="form-control" min="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Difficulty Level *</label>
                                    <select name="difficulty_level" id="edit_difficulty_level" class="form-select" required>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status *</label>
                                    <select name="status" id="edit_status" class="form-select" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description *</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4" required minlength="10" placeholder="Enter course description (min 10 characters)"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Thumbnail</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Upload from Computer</label>
                                    <input type="file" name="course_thumbnail_file" id="edit_thumbnail_file" class="form-control" accept="image/*">
                                    <div class="form-text small">JPG, PNG, GIF, WebP (Max: 10MB)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Or enter Image URL</label>
                                    <input type="url" name="thumbnail" id="edit_thumbnail" class="form-control" placeholder="https://...">
                                    <div class="form-text small">External image URL</div>
                                </div>
                            </div>
                            <div id="edit_thumbnail_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnUpdateSubmit">
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
        // Global variables
        let currentPage = <?php echo $page; ?>;
        let currentSearch = '<?php echo htmlspecialchars($search); ?>';
        let currentStatus = '<?php echo htmlspecialchars($status); ?>';
        let createModal, editModal;
        
        // CSRF Token
        const csrfToken = '<?php echo $csrfToken; ?>';

        // Initialize on document ready
        $(document).ready(function() {
            // Initialize modals
            createModal = new bootstrap.Modal(document.getElementById('createCourseModal'));
            editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
            
            // Setup event listeners
            setupEventListeners();
        });

        function setupEventListeners() {
            // Create course buttons
            $('#btnCreateCourse, #btnCreateCourseEmpty').on('click', function() {
                resetCreateForm();
                createModal.show();
            });
            
            // Filter buttons
            $('#btnFilter').on('click', function() {
                currentSearch = $('#searchInput').val();
                currentStatus = $('#statusFilter').val();
                currentPage = 1;
                loadCourses();
            });
            
            $('#btnClearFilter').on('click', function() {
                $('#searchInput').val('');
                $('#statusFilter').val('');
                currentSearch = '';
                currentStatus = '';
                currentPage = 1;
                loadCourses();
            });
            
            // Enter key for search
            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#btnFilter').click();
                }
            });
            
            // Create course form submit
            $('#createCourseForm').on('submit', function(e) {
                e.preventDefault();
                createCourse();
            });
            
            // Edit course form submit
            $('#editCourseForm').on('submit', function(e) {
                e.preventDefault();
                updateCourse();
            });
            
            // Thumbnail preview for create form
            $('#create_thumbnail_file').on('change', function() {
                handleFilePreview(this, '#create_thumbnail_preview');
            });
            
            $('#create_thumbnail').on('input', function() {
                handleUrlPreview(this, '#create_thumbnail_preview');
            });
            
            // Thumbnail preview for edit form
            $('#edit_thumbnail_file').on('change', function() {
                handleFilePreview(this, '#edit_thumbnail_preview');
            });
            
            $('#edit_thumbnail').on('input', function() {
                handleUrlPreview(this, '#edit_thumbnail_preview');
            });
        }

        // Loading functions
        function showLoading() {
            $('#loadingOverlay').addClass('active');
        }

        function hideLoading() {
            $('#loadingOverlay').removeClass('active');
        }

        // Show SweetAlert messages
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                confirmButtonColor: '#6366f1'
            });
        }

        function showConfirm(title, text, confirmText = 'Yes, delete it!') {
            return Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmText,
                reverseButtons: true
            });
        }

        // Load courses via AJAX
        function loadCourses() {
            showLoading();
            
            $.ajax({
                url: '../api/course_api.php',
                type: 'GET',
                data: {
                    action: 'get_courses',
                    page: currentPage,
                    search: currentSearch,
                    status: currentStatus
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success') {
                        renderCourses(response.data.courses);
                        renderPagination(response.data.pagination);
                        updateStats();
                    } else {
                        showError(response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showError('Failed to load courses. Please try again.');
                }
            });
        }

        // Render courses to DOM
        function renderCourses(courses) {
            const container = $('#coursesContainer');
            
            if (!courses || courses.length === 0) {
                container.html(`
                    <div class="col-12" id="emptyState">
                        <div class="empty-state animate-fade-in-up">
                            <div class="empty-state-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h2 class="empty-state-title">No courses found</h2>
                            <p class="empty-state-description">Start creating your first course to share your knowledge with students.</p>
                            <button type="button" class="btn btn-primary btn-lg" id="btnCreateCourseEmpty">
                                <i class="fas fa-plus me-2"></i>Create Your First Course
                            </button>
                        </div>
                    </div>
                `);
                
                $('#btnCreateCourseEmpty').on('click', function() {
                    resetCreateForm();
                    createModal.show();
                });
                return;
            }
            
            let html = '';
            courses.forEach(function(course, index) {
                const thumbnail = course.thumbnail ? 
                    `<img src="${resolveUploadUrl(course.thumbnail)}" class="w-100 h-100" style="object-fit: cover;" alt="${escapeHtml(course.title)}">` :
                    `<i class="fas fa-book"></i>`;
                
                const thumbnailClass = course.thumbnail ? 'course-thumbnail' : 'course-thumbnail-placeholder';
                
                html += `
                    <div class="col-md-6 col-lg-4 course-item" data-course-id="${course.id}">
                        <div class="course-card animate-fade-in-up" style="animation-delay: ${0.1 * index}s;">
                            <div class="${thumbnailClass}" style="position: relative;">
                                ${thumbnail}
                                <span class="course-status-badge ${course.status}">
                                    ${course.status.charAt(0).toUpperCase() + course.status.slice(1)}
                                </span>
                            </div>
                            
                            <div class="course-body">
                                <h3 class="course-title">${escapeHtml(course.title)}</h3>
                                <p class="course-description">${stripTags(course.description)}</p>
                                
                                <div class="course-meta">
                                    <span class="course-meta-item">
                                        <i class="fas fa-folder me-1"></i>${escapeHtml(course.category_name || 'Uncategorized')}
                                    </span>
                                    <span class="course-meta-item">
                                        <i class="fas fa-signal me-1"></i>${course.difficulty_level.charAt(0).toUpperCase() + course.difficulty_level.slice(1)}
                                    </span>
                                </div>
                                
                                <div class="course-stats">
                                    <div class="course-stat">
                                        <span class="course-stat-value">${course.enrollment_count || 0}</span>
                                        <span class="course-stat-label">Students</span>
                                    </div>
                                    <div class="course-stat">
                                        <span class="course-stat-value">${parseFloat(course.avg_progress || 0).toFixed(1)}%</span>
                                        <span class="course-stat-label">Progress</span>
                                    </div>
                                    <div class="course-stat">
                                        <span class="course-stat-value">${course.lesson_count || 0}</span>
                                        <span class="course-stat-label">Lessons</span>
                                    </div>
                                </div>
                                
                                <div class="course-actions">
                                    <a href="course_builder.php?id=${course.id}" class="course-action-btn primary" title="Course Builder">
                                        <i class="fas fa-screwdriver-wrench"></i>
                                    </a>
                                    <button type="button" class="course-action-btn info" onclick="viewCourseStats(${course.id})" title="Statistics">
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                    <button type="button" class="course-action-btn success" onclick="manageStudents(${course.id})" title="Students">
                                        <i class="fas fa-users"></i>
                                    </button>
                                    <button type="button" class="course-action-btn warning" onclick="editCourse(${course.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="course-action-btn danger" onclick="deleteCourse(${course.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }

        // Render pagination
        function renderPagination(pagination) {
            if (pagination.total_pages <= 1) {
                $('#paginationContainer').hide();
                return;
            }
            
            $('#paginationContainer').show();
            
            let html = '';
            const { page, total_pages } = pagination;
            
            if (page > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(${page - 1})">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                </li>`;
            }
            
            const start = Math.max(1, page - 2);
            const end = Math.min(total_pages, page + 2);
            
            if (start > 1) {
                html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadPage(1)">1</a></li>`;
                if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            
            for (let i = start; i <= end; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(${i})">${i}</a>
                </li>`;
            }
            
            if (end < total_pages) {
                if (end < total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadPage(${total_pages})">${total_pages}</a></li>`;
            }
            
            if (page < total_pages) {
                html += `<li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadPage(${page + 1})">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </li>`;
            }
            
            $('#paginationContainer').html(html);
        }

        // Load page
        function loadPage(page) {
            currentPage = page;
            loadCourses();
        }

        // Update stats
        function updateStats() {
            $.ajax({
                url: '../api/course_api.php',
                type: 'GET',
                data: { action: 'get_stats' },
                success: function(response) {
                    if (response.status === 'success') {
                        const stats = response.data.stats.overview;
                        $('#statTotalCourses, #statsTotalCourses').text(stats.total_courses || 0);
                        $('#statPublishedCourses, #statsPublishedCourses').text(stats.published_courses || 0);
                        $('#statTotalStudents, #statsTotalStudents').text(stats.total_students || 0);
                        $('#statsAvgProgress').text((stats.avg_progress || 0).toFixed(1) + '%');
                    }
                }
            });
        }

        // Create course
        function createCourse() {
            const form = $('#createCourseForm');
            const submitBtn = $('#btnCreateSubmit');
            
            // Validate
            const title = $('#create_title').val().trim();
            const description = $('#create_description').val().trim();
            const categoryId = $('#create_category_id').val();
            
            if (title.length < 3) {
                showError('Course title must be at least 3 characters');
                return;
            }
            
            if (description.length < 10) {
                showError('Course description must be at least 10 characters');
                return;
            }
            
            if (!categoryId) {
                showError('Please select a category');
                return;
            }
            
            submitBtn.prop('disabled', true).addClass('btn-loading');
            
            const formData = new FormData(form[0]);
            
            $.ajax({
                url: '../api/course_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    submitBtn.prop('disabled', false).removeClass('btn-loading');
                    
                    if (response.status === 'success') {
                        createModal.hide();
                        showSuccess(response.message);
                        resetCreateForm();
                        loadCourses();
                        
                        // Update stats
                        updateStats();
                    } else {
                        showError(response.message);
                    }
                },
                error: function() {
                    submitBtn.prop('disabled', false).removeClass('btn-loading');
                    showError('Failed to create course. Please try again.');
                }
            });
        }

        // Edit course - load data
        function editCourse(courseId) {
            showLoading();
            
            $.ajax({
                url: '../api/course_api.php',
                type: 'GET',
                data: {
                    action: 'get_course',
                    course_id: courseId
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success') {
                        const course = response.data.course;
                        
                        $('#edit_course_id').val(course.id);
                        $('#edit_title').val(course.title);
                        $('#edit_description').val(course.description);
                        $('#edit_category_id').val(course.category_id);
                        $('#edit_price').val(course.price);
                        $('#edit_duration_hours').val(course.duration_hours);
                        $('#edit_difficulty_level').val(course.difficulty_level);
                        $('#edit_status').val(course.status);
                        $('#edit_thumbnail').val(course.thumbnail || '');
                        
                        // Show current thumbnail
                        if (course.thumbnail) {
                            $('#edit_thumbnail_preview').html(`
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${resolveUploadUrl(course.thumbnail)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Current thumbnail">
                                    <span class="small text-muted">Current thumbnail</span>
                                </div>
                            `);
                        } else {
                            $('#edit_thumbnail_preview').html('');
                        }
                        
                        editModal.show();
                    } else {
                        showError(response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showError('Failed to load course data');
                }
            });
        }

        // Update course
        function updateCourse() {
            const form = $('#editCourseForm');
            const submitBtn = $('#btnUpdateSubmit');
            
            // Validate
            const title = $('#edit_title').val().trim();
            const description = $('#edit_description').val().trim();
            const categoryId = $('#edit_category_id').val();
            
            if (title.length < 3) {
                showError('Course title must be at least 3 characters');
                return;
            }
            
            if (description.length < 10) {
                showError('Course description must be at least 10 characters');
                return;
            }
            
            if (!categoryId) {
                showError('Please select a category');
                return;
            }
            
            submitBtn.prop('disabled', true).addClass('btn-loading');
            
            const formData = new FormData(form[0]);
            
            $.ajax({
                url: '../api/course_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    submitBtn.prop('disabled', false).removeClass('btn-loading');
                    
                    if (response.status === 'success') {
                        editModal.hide();
                        showSuccess(response.message);
                        loadCourses();
                        
                        // Update stats
                        updateStats();
                    } else {
                        showError(response.message);
                    }
                },
                error: function() {
                    submitBtn.prop('disabled', false).removeClass('btn-loading');
                    showError('Failed to update course. Please try again.');
                }
            });
        }

        // Delete course
        function deleteCourse(courseId) {
            showConfirm(
                'Delete Course?',
                'Are you sure you want to delete this course? This action cannot be undone and all related data will be lost.',
                'Yes, delete it!'
            ).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: '../api/course_api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            course_id: courseId,
                            csrf_token: csrfToken
                        },
                        success: function(response) {
                            hideLoading();
                            
                            if (response.status === 'success') {
                                // Remove course from DOM with animation
                                $(`.course-item[data-course-id="${courseId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Check if empty
                                    if ($('.course-item').length === 0) {
                                        loadCourses();
                                    }
                                });
                                
                                showSuccess(response.message);
                                updateStats();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function() {
                            hideLoading();
                            showError('Failed to delete course. Please try again.');
                        }
                    });
                }
            });
        }

        // Toggle publish/unpublish
        function toggleCourseStatus(courseId, currentStatus) {
            const newStatus = currentStatus === 'published' ? 'draft' : 'published';
            
            showConfirm(
                newStatus === 'published' ? 'Publish Course?' : 'Unpublish Course?',
                newStatus === 'published' ? 
                    'Are you sure you want to publish this course? Students will be able to enroll.' :
                    'Are you sure you want to unpublish this course? Students will not be able to enroll.',
                newStatus === 'published' ? 'Yes, publish it!' : 'Yes, unpublish it!'
            ).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: '../api/course_api.php',
                        type: 'POST',
                        data: {
                            action: 'toggle_status',
                            course_id: courseId,
                            status: newStatus,
                            csrf_token: csrfToken
                        },
                        success: function(response) {
                            hideLoading();
                            
                            if (response.status === 'success') {
                                showSuccess(response.message);
                                loadCourses();
                                updateStats();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function() {
                            hideLoading();
                            showError('Failed to update course status. Please try again.');
                        }
                    });
                }
            });
        }

        // View course stats
        function viewCourseStats(courseId) {
            window.location.href = `course-stats.php?id=${courseId}`;
        }

        // Manage students
        function manageStudents(courseId) {
            window.location.href = `course-students.php?id=${courseId}`;
        }

        // Reset create form
        function resetCreateForm() {
            $('#createCourseForm')[0].reset();
            $('#create_thumbnail_preview').html('');
        }

        // Handle file preview
        function handleFilePreview(input, previewId) {
            const file = input.files[0];
            if (file) {
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    showError('File size too large. Maximum size is 10MB');
                    $(input).val('');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(previewId).html(`
                        <div class="d-flex align-items-center gap-2">
                            <img src="${e.target.result}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview">
                            <span class="small text-success">New image selected</span>
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
                
                // Clear URL field
                $(input).closest('.mb-3').find('input[type="url"]').val('');
            }
        }

        // Handle URL preview
        function handleUrlPreview(input, previewId) {
            const url = $(input).val();
            if (url) {
                // Clear file input
                $(input).closest('.mb-3').find('input[type="file"]').val('');
                
                // Show preview if valid URL
                if (url.match(/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
                    $(previewId).html(`
                        <div class="d-flex align-items-center gap-2">
                            <img src="${url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview" onerror="this.style.display='none'; $(this).next('span').text('Invalid image URL').addClass('text-danger');">
                            <span class="small text-info">URL image</span>
                        </div>
                    `);
                }
            } else {
                $(previewId).html('');
            }
        }

        // Utility functions
        function resolveUploadUrl(path) {
            if (!path) return '';
            if (path.match(/^https?:\/\//i)) return path;
            return '<?php echo BASE_URL; ?>uploads/' + path;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function stripTags(html) {
            if (!html) return '';
            return html.replace(/<[^>]*>/g, '');
        }
    </script>
</body>
</html>
