<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

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
$categories = $conn->query("SELECT id, name FROM categories_new ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get enrolled courses and wishlist for current user
$enrolledCourses = [];
$wishlistCourses = [];

$enrolledResult = $conn->prepare("SELECT course_id FROM enrollments_new WHERE user_id = ? AND status = 'active'");
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
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        /* Consistent Sidebar Styling */
        .sidebar-nav {
            background: var(--gradient-primary);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .sidebar-nav .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
            background: transparent;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            border-left: 4px solid transparent;
        }

        .sidebar-nav .list-group-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(8px);
            border-left-color: white;
            color: white;
        }

        .sidebar-nav .list-group-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            gap: 0.5rem;
            font-weight: 600;
        }

        .course-price {
            font-size: 1.5rem;
            font-weight: var(--font-weight-bold);
            color: var(--primary-color);
        }

        .course-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-stat {
            text-align: center;
            padding: 0.5rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
        }

        .filter-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
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
        
        /* Enhanced Course Page Styles */
        .page-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin: -1rem -1rem 2rem -1rem;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: var(--font-weight-bold);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Enhanced Filter Card */
        .filter-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .filter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .filter-title {
            font-size: 1.3rem;
            font-weight: var(--font-weight-bold);
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-title i {
            color: var(--primary-color);
        }
        
        /* Enhanced Course Cards */
        .course-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: var(--transition-bounce);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: var(--transition);
        }
        
        .course-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .course-card:hover::before {
            opacity: 1;
        }
        
        .course-thumbnail {
            height: 200px;
            position: relative;
            overflow: hidden;
            background: var(--gradient-primary);
        }
        
        .course-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }
        
        .course-card:hover .course-thumbnail img {
            transform: scale(1.1);
        }
        
        .course-thumbnail-placeholder {
            height: 200px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }
        
        .course-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: var(--font-weight-semibold);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .course-badge.beginner {
            background: rgba(16, 185, 129, 0.9);
            color: white;
        }
        
        .course-badge.intermediate {
            background: rgba(245, 158, 11, 0.9);
            color: white;
        }
        
        .course-badge.advanced {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }
        
        .course-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .course-title {
            font-size: 1.25rem;
            font-weight: var(--font-weight-bold);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            color: var(--dark-color);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .course-description {
            color: var(--gray-color);
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
        }
        
        .course-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--gray-color);
            font-size: 0.875rem;
        }
        
        .course-meta-item i {
            color: var(--primary-color);
        }
        
        .course-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
        }
        
        .course-stat {
            text-align: center;
        }
        
        .course-stat-value {
            display: block;
            font-size: 1.25rem;
            font-weight: var(--font-weight-bold);
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .course-stat-label {
            display: block;
            font-size: 0.75rem;
            color: var(--gray-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .course-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }
        
        .course-action-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: var(--radius);
            font-weight: var(--font-weight-medium);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .course-action-btn.primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .course-action-btn.primary:hover {
            background: var(--gradient-primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
            color: white;
        }
        
        .course-action-btn.success {
            background: var(--gradient-success);
            color: white;
        }
        
        .course-action-btn.success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: var(--shadow-success);
            color: white;
        }
        
        .course-action-btn.warning,
        .course-action-btn.danger {
            width: auto;
            min-width: 50px;
            padding: 0.75rem;
        }
        
        .course-action-btn.warning {
            background: var(--gradient-warning);
            color: white;
        }
        
        .course-action-btn.danger {
            background: var(--bg-secondary);
            color: var(--danger-color);
            border: 2px solid var(--danger-color);
        }
        
        .course-action-btn.warning:hover,
        .course-action-btn.danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin: 2rem 0;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
        
        .empty-state h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: var(--gray-color);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        /* Enhanced Pagination */
        .pagination {
            margin-top: 3rem;
        }
        
        .pagination .page-link {
            border: 2px solid var(--border-color);
            color: var(--primary-color);
            font-weight: var(--font-weight-medium);
            border-radius: var(--radius);
            margin: 0 0.25rem;
            transition: var(--transition);
            padding: 0.75rem 1rem;
        }
        
        .pagination .page-link:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-primary);
        }
        
        /* Stats Cards Enhancement */
        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card h6 {
            color: var(--dark-color);
            font-weight: var(--font-weight-bold);
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.primary {
            background: var(--gradient-primary);
        }
        
        .stat-icon.success {
            background: var(--gradient-success);
        }
        
        .stat-icon.warning {
            background: var(--gradient-warning);
        }
        
        .stat-icon.info {
            background: var(--gradient-info);
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .stat-row:last-child {
            margin-bottom: 0;
        }
        
        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .stat-value {
            color: var(--dark-color);
            font-weight: var(--font-weight-bold);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                margin: -1rem -1rem 1.5rem -1rem;
                padding: 1.5rem 0;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .course-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            
            .course-stat-value {
                font-size: 1rem;
            }
            
            .course-actions {
                flex-direction: column;
            }
            
            .course-action-btn {
                width: 100%;
            }
            
            .filter-card {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .course-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .course-stats {
                grid-template-columns: 1fr;
                text-align: left;
                display: block;
            }
            
            .course-stat {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/universal_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <?php require_once 'includes/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="page-title">Course Catalog</h1>
                        <p class="page-subtitle">Discover and enroll in courses that match your learning goals</p>
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
                <div class="filter-card">
                    <h3 class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filter Courses
                    </h3>
                    <form method="GET" action="courses.php">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search courses..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
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
                        
                        <!-- Active Filters Display -->
                        <?php if ($search || $category || $difficulty || $price_range || $sort !== 'newest'): ?>
                            <div class="mt-3">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="text-muted fw-bold">Active Filters:</span>
                                    <?php if ($search): ?>
                                        <span class="badge bg-primary">
                                            Search: <?php echo htmlspecialchars($search); ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="text-white ms-1">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($category): ?>
                                        <span class="badge bg-primary">
                                            Category: <?php echo htmlspecialchars(array_filter($categories, fn($c) => $c['id'] == $category)[0]['name'] ?? ''); ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" class="text-white ms-1">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($difficulty): ?>
                                        <span class="badge bg-warning">
                                            Level: <?php echo ucfirst($difficulty); ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['difficulty' => ''])); ?>" class="text-white ms-1">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($price_range): ?>
                                        <span class="badge bg-success">
                                            Price: <?php echo $price_range === 'free' ? 'Free' : 'Rs ' . str_replace('+', '+', $price_range); ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['price_range' => ''])); ?>" class="text-white ms-1">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($sort !== 'newest'): ?>
                                        <span class="badge bg-info">
                                            Sort: <?php echo ['popular' => 'Popular', 'rating' => 'Highest Rated', 'price_low' => 'Price Low to High', 'price_high' => 'Price High to Low'][$sort] ?? $sort; ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" class="text-white ms-1">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <a href="courses.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i>Clear All
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
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
        $(document).ready(function() {
            // Enhanced wishlist functionality
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
                                $(button).removeClass('btn-outline-danger').addClass('btn-warning');
                                $(button).html('<i class="fas fa-heart"></i> Added');
                                showNotification('Added to wishlist!', 'success');
                            } else {
                                $(button).removeClass('btn-warning').addClass('btn-outline-danger');
                                $(button).html('<i class="far fa-heart"></i> Add to Wishlist');
                                showNotification('Removed from wishlist', 'info');
                            }
                            
                            // Update wishlist count
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
            
            // Enhanced notification system
            function showNotification(message, type = 'info') {
                const alertClass = type === 'success' ? 'alert-success' : 
                                type === 'error' ? 'alert-danger' : 'alert-info';
                
                const notification = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed shadow-lg" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px;">
                        <div class="d-flex align-items-center">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
                            <div class="flex-grow-1">${message}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                `);
                
                $('body').append(notification);
                
                // Auto remove after 4 seconds
                setTimeout(() => {
                    notification.alert('close');
                }, 4000);
            }
            
            // Course card hover effects
            $('.course-card').hover(
                function() {
                    $(this).find('.course-thumbnail img').css('transform', 'scale(1.1)');
                },
                function() {
                    $(this).find('.course-thumbnail img').css('transform', 'scale(1)');
                }
            );
            
            // Smooth scroll for pagination
            $('.pagination a').on('click', function(e) {
                e.preventDefault();
                const targetUrl = $(this).attr('href');
                $('html, body').animate({ scrollTop: 0 }, 300);
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 300);
            });
            
            // Filter form enhancement
            $('form[method="GET"]').on('submit', function() {
                // Show loading state
                $('.course-grid').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Loading courses...</p></div>');
            });
            
            // Auto-clear search on escape key
            $('input[name="search"]').on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html>
