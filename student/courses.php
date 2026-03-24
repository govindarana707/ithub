<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../models/Course.php';
require_once '../models/User.php';

$course = new Course();
$user = new User();
$userId = $_SESSION['user_id'];
$userData = $user->getUserById($userId);

// Get filter parameters with proper sanitization
$search = sanitize($_GET['search'] ?? '');
$category = intval($_GET['category'] ?? 0);
$difficulty = sanitize($_GET['difficulty'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');
$price_range = sanitize($_GET['price_range'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build filters array
$filters = [
    'search' => $search,
    'category_id' => $category > 0 ? $category : null,
    'difficulty_level' => $difficulty ?: null,
    'price_range' => $price_range ?: null,
    'sort' => $sort
];

// Get courses with proper filtering and pagination
$courseResult = $course->getCoursesWithFilters($filters, $limit, $offset);
$courses = $courseResult['courses'] ?? [];
$totalCourses = $courseResult['total'] ?? 0;
$totalPages = ceil($totalCourses / $limit);

// Get categories for filter
$conn = connectDB();
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get enrolled courses and wishlist for current user
$enrolledCourses = [];
$wishlistCourses = [];

$enrolledResult = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enrolledResult->bind_param("i", $userId);
$enrolledResult->execute();
$enrolledData = $enrolledResult->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($enrolledData as $enrolled) {
    $enrolledCourses[] = $enrolled['course_id'];
}

// Get wishlist items
// Create wishlists table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS `wishlists` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wishlist` (`student_id`, `course_id`),
        KEY `idx_student_id` (`student_id`),
        KEY `idx_course_id` (`course_id`),
        CONSTRAINT `fk_wishlist_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_wishlist_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$wishlistResult = $conn->prepare("SELECT course_id FROM wishlists WHERE student_id = ?");
if ($wishlistResult === false) {
    // Table creation failed, handle gracefully
    $wishlistCourses = [];
} else {
    $wishlistResult->bind_param("i", $userId);
    $wishlistResult->execute();
    $wishlistData = $wishlistResult->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($wishlistData as $wishlist) {
        $wishlistCourses[] = $wishlist['course_id'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Catalog - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-color: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        /* Enhanced Sidebar */
        .sidebar-modern {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

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

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        /* Course Cards */
        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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

        .course-badge {
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
        }

        .course-badge.beginner { background: rgba(16, 185, 129, 0.9); color: white; }
        .course-badge.intermediate { background: rgba(245, 158, 11, 0.9); color: white; }
        .course-badge.advanced { background: rgba(239, 68, 68, 0.9); color: white; }

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
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            position: relative;
            z-index: 10;
            pointer-events: auto;
        }

        .course-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .course-action-btn.primary { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .course-action-btn.success { background: var(--success-color); color: white; border-color: var(--success-color); }
        .course-action-btn.warning { background: var(--warning-color); color: white; border-color: var(--warning-color); }
        .course-action-btn.danger { background: var(--danger-color); color: white; border-color: var(--danger-color); }

        /* Pagination */
        .pagination .page-link {
            border: none;
            margin: 0 0.25rem;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: var(--dark-color);
            transition: all 0.3s ease;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i> Student
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item active" href="courses.php">Course Catalog</a></li>
                        <li><a class="dropdown-item" href="my-courses.php">My Courses</a></li>
                        <li><a class="dropdown-item" href="certificates.php">Certificates</a></li>
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
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
                <div class="sidebar-modern list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-book me-2"></i> Browse Courses
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> My Courses
                    </a>
                    <a href="certificates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="notifications.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
                
                <!-- Quick Stats -->
                <div class="stat-card">
                    <h6 class="mb-3 fw-bold">Quick Overview</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Available Courses</span>
                        <span class="fw-bold"><?php echo $totalCourses; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Enrolled</span>
                        <span class="fw-bold text-success"><?php echo count($enrolledCourses); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Wishlist</span>
                        <span class="fw-bold text-info"><?php echo count($wishlistCourses); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="mb-2 fw-bold">Course Catalog</h1>
                        <p class="text-muted mb-0">Discover and enroll in amazing courses</p>
                    </div>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $totalCourses; ?></h3>
                            <p class="text-muted mb-0">Available Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo count($enrolledCourses); ?></h3>
                            <p class="text-muted mb-0">Enrolled</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-tags"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo count($categories); ?></h3>
                            <p class="text-muted mb-0">Categories</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo count($wishlistCourses); ?></h3>
                            <p class="text-muted mb-0">Wishlist</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Filter Courses</h5>
                        <a href="courses.php" class="btn btn-outline-secondary btn-sm">Clear All</a>
                    </div>
                    <form method="GET" class="mb-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0" 
                                           placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Difficulty</label>
                                <select name="difficulty" class="form-select">
                                    <option value="">All Levels</option>
                                    <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Price Range</label>
                                <select name="price_range" class="form-select">
                                    <option value="">All Prices</option>
                                    <option value="free" <?php echo $price_range === 'free' ? 'selected' : ''; ?>>Free</option>
                                    <option value="0-1000" <?php echo $price_range === '0-1000' ? 'selected' : ''; ?>>Rs 0-1000</option>
                                    <option value="1000-5000" <?php echo $price_range === '1000-5000' ? 'selected' : ''; ?>>Rs 1000-5000</option>
                                    <option value="5000+" <?php echo $price_range === '5000+' ? 'selected' : ''; ?>>Rs 5000+</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Sort By</label>
                                <select name="sort" class="form-select">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popular</option>
                                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Course Grid -->
                <div class="row g-4">
                    <?php if (empty($courses)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h2 class="fw-bold mb-3">No courses found</h2>
                                <p class="text-muted mb-4">Try adjusting your filters or browse all available courses.</p>
                                <a href="courses.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-book me-2"></i>Browse All Courses
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="course-card">
                                    <?php if ($course['thumbnail']): ?>
                                        <div class="course-thumbnail" style="position: relative;">
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" 
                                                 class="w-100 h-100" style="object-fit: cover;" 
                                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                                            <span class="course-badge <?php echo $course['difficulty_level']; ?>">
                                                <?php echo ucfirst($course['difficulty_level']); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="course-thumbnail-placeholder">
                                            <i class="fas fa-book"></i>
                                            <span class="course-badge <?php echo $course['difficulty_level']; ?>">
                                                <?php echo ucfirst($course['difficulty_level']); ?>
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
                                                <i class="fas fa-clock me-1"></i><?php echo $course['duration_hours'] ?? 0; ?>h
                                            </span>
                                        </div>
                                        
                                        <div class="course-stats">
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo $course['enrollment_count'] ?? 0; ?></span>
                                                <span class="course-stat-label">Students</span>
                                            </div>
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo $course['avg_rating'] ?? 0; ?></span>
                                                <span class="course-stat-label">Rating</span>
                                            </div>
                                            <div class="course-stat">
                                                <span class="course-stat-value"><?php echo $course['lesson_count'] ?? 0; ?></span>
                                                <span class="course-stat-label">Lessons</span>
                                            </div>
                                        </div>
                                        
                                        <div class="course-actions">
                                            <?php if (in_array($course['id'], $enrolledCourses)): ?>
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>" 
                                                   class="course-action-btn success">
                                                    <i class="fas fa-play me-1"></i>Continue
                                                </a>
                                            <?php else: ?>
                                                <a href="course-details.php?id=<?php echo $course['id']; ?>" 
                                                   class="course-action-btn primary">
                                                    <i class="fas fa-info-circle me-1"></i>Details
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button class="course-action-btn <?php echo in_array($course['id'], $wishlistCourses) ? 'warning' : 'danger'; ?>" 
                                                    onclick="toggleWishlist(<?php echo $course['id']; ?>, this)">
                                                <i class="<?php echo in_array($course['id'], $wishlistCourses) ? 'fas' : 'far'; ?> fa-heart"></i>
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
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&difficulty=<?php echo urlencode($difficulty); ?>&price_range=<?php echo urlencode($price_range); ?>&sort=<?php echo urlencode($sort); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&category=' . $category . '&difficulty=' . urlencode($difficulty) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">1</a></li>';
                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&difficulty=<?php echo urlencode($difficulty); ?>&price_range=<?php echo urlencode($price_range); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php 
                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&category=' . $category . '&difficulty=' . urlencode($difficulty) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&difficulty=<?php echo urlencode($difficulty); ?>&price_range=<?php echo urlencode($price_range); ?>&sort=<?php echo urlencode($sort); ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle wishlist functionality
        function toggleWishlist(courseId, button) {
            console.log('Toggle wishlist called for course:', courseId, 'button:', button);
            const csrfToken = '<?php echo generateCSRFToken(); ?>';
            
            $.ajax({
                url: '../api/toggle_wishlist.php',
                method: 'POST',
                data: { 
                    course_id: courseId,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                beforeSend: function() {
                    $(button).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                },
                success: function(response) {
                    if (response.success) {
                        if (response.in_wishlist) {
                            $(button).removeClass('danger').addClass('warning');
                            $(button).html('<i class="fas fa-heart"></i>');
                            showNotification('Added to wishlist!', 'success');
                        } else {
                            $(button).removeClass('warning').addClass('danger');
                            $(button).html('<i class="far fa-heart"></i>');
                            showNotification('Removed from wishlist', 'info');
                        }
                        
                        // Update wishlist count in sidebar
                        const currentCount = parseInt($('.stat-card').find('.text-info').text());
                        $('.stat-card').find('.text-info').text(response.in_wishlist ? currentCount + 1 : currentCount - 1);
                    } else {
                        showNotification(response.error || 'Error updating wishlist', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error, 'Response:', xhr.responseText);
                    showNotification('Network error. Please try again.', 'error');
                },
                complete: function() {
                    $(button).prop('disabled', false);
                }
            });
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                            type === 'error' ? 'alert-danger' : 'alert-info';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.alert('close');
            }, 3000);
        }
        
        // Auto-submit filters on change (optional)
        $('.form-select, .form-control').on('change', function() {
            if ($(this).attr('name') !== 'search') {
                $(this).closest('form').submit();
            }
        });
        
        // Search on Enter key
        $('input[name="search"]').on('keypress', function(e) {
            if (e.which === 13) {
                $(this).closest('form').submit();
            }
        });
    </script>
</body>
</html>
