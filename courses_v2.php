<?php
require_once 'config/config.php';
require_once 'controllers/CourseController.php';

// Initialize controller
$controller = new CourseController();

// Handle the request
$result = $controller->index();

// Check if this is an API request
$isApiRequest = isset($_GET['api']) || isset($_POST['api']);

if ($isApiRequest) {
    // Return JSON response for API requests
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// For web requests, continue with normal page rendering
if (!$result['success']) {
    // Handle error gracefully
    $errorMessage = $result['message'] ?? 'An error occurred';
    $errors = $controller->getErrors();
    
    // You might want to show an error page or redirect
    // For now, we'll continue with empty data
    $data = [
        'courses' => [],
        'categories' => [],
        'popularCourses' => [],
        'userCourses' => [],
        'filters' => [],
        'pagination' => [
            'current' => 1,
            'total' => 0,
            'totalPages' => 0
        ],
        'params' => [
            'search' => '',
            'category' => 0,
            'difficulty' => '',
            'page' => 1
        ]
    ];
} else {
    $data = $result['data'];
}

// Extract data for easier access in template
$courses = $data['courses'];
$categories = $data['categories'];
$popularCourses = $data['popularCourses'];
$userCourses = $data['userCourses'];
$filters = $data['filters'];
$pagination = $data['pagination'];
$params = $data['params'];

// Generate query string for preserving filters
$queryString = $controller->generateQueryString(['page']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .pagination-container {
            margin-top: 30px;
        }
        .enrolled-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .course-image {
            height: 200px;
            object-fit: cover;
        }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .search-container h1 {
            color: white;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Search Section -->
    <div class="search-container">
        <div class="container">
            <h1 class="text-center">Discover Your Learning Journey</h1>
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search for courses..." 
                           value="<?php echo htmlspecialchars($params['search']); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-warning btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Search Courses
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-section">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                    
                    <form method="GET" id="filterForm">
                        <!-- Preserve search -->
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($params['search']); ?>">
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($params['category'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Difficulty Filter -->
                        <div class="mb-3">
                            <label class="form-label">Difficulty</label>
                            <select name="difficulty" class="form-select">
                                <option value="">All Levels</option>
                                <option value="beginner" <?php echo ($params['difficulty'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo ($params['difficulty'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo ($params['difficulty'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>

                        <!-- Sort Filter -->
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="latest" <?php echo ($params['sort'] == 'latest') ? 'selected' : ''; ?>>Latest</option>
                                <option value="popular" <?php echo ($params['sort'] == 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="price_low" <?php echo ($params['sort'] == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($params['sort'] == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check me-2"></i>Apply Filters
                        </button>
                        
                        <?php if (!empty($params['search']) || $params['category'] > 0 || !empty($params['difficulty'])): ?>
                            <a href="courses_v2.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Popular Courses -->
                <?php if (!empty($popularCourses)): ?>
                    <div class="filter-section mt-4">
                        <h5 class="mb-3"><i class="fas fa-fire me-2"></i>Popular Courses</h5>
                        <?php foreach ($popularCourses as $course): ?>
                            <div class="mb-3">
                                <div class="d-flex">
                                    <img src="<?php echo resolveUploadUrl($course['thumbnail']); ?>" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                         class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo $course['enrollment_count'] ?? 0; ?> students
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Courses Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4>
                            <?php if (!empty($params['search'])): ?>
                                Search Results for "<?php echo htmlspecialchars($params['search']); ?>"
                            <?php else: ?>
                                Available Courses
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-0">
                            <?php echo $pagination['total']; ?> courses found
                        </p>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <!-- Courses Grid -->
                <?php if (!empty($courses)): ?>
                    <div class="row">
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card course-card">
                                    <?php 
                                    // Check if user is enrolled
                                    $isEnrolled = false;
                                    if (isLoggedIn() && getUserRole() === 'student') {
                                        $isEnrolled = in_array($course['id'], array_column($userCourses, 'id'));
                                    }
                                    ?>
                                    
                                    <?php if ($isEnrolled): ?>
                                        <div class="enrolled-badge">
                                            <span class="badge bg-success">Enrolled</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <img src="<?php echo resolveUploadUrl($course['thumbnail']); ?>" 
                                         class="card-img-top course-image" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <div class="flex-grow-1">
                                            <div class="mb-2">
                                                <span class="badge bg-primary me-1"><?php echo htmlspecialchars($course['category_name'] ?? ''); ?></span>
                                                <span class="badge bg-secondary"><?php echo ucfirst($course['difficulty_level'] ?? ''); ?></span>
                                            </div>
                                            
                                            <h5 class="card-title">
                                                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($course['title']); ?>
                                                </a>
                                            </h5>
                                            
                                            <p class="card-text text-muted">
                                                <?php echo substr(htmlspecialchars($course['description'] ?? ''), 0, 100) . '...'; ?>
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name'] ?? 'Instructor'); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i><?php echo $course['duration_hours'] ?? 0; ?>h
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-primary">Rs<?php echo number_format($course['price'] ?? 0, 2); ?></strong>
                                            </div>
                                            
                                            <?php if ($isEnrolled): ?>
                                                <a href="student/view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-play me-1"></i>Continue
                                                </a>
                                            <?php else: ?>
                                                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-info-circle me-1"></i>View Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <div class="pagination-container">
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['hasPrev']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $pagination['prevPage']; ?>&<?php echo $queryString; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    $start = max(1, $pagination['current'] - 2);
                                    $end = min($pagination['totalPages'], $pagination['current'] + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <li class="page-item <?php echo ($i == $pagination['current']) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $queryString; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($pagination['hasNext']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $pagination['nextPage']; ?>&<?php echo $queryString; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Courses Found -->
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No courses found</h4>
                        <p class="text-muted">
                            <?php if (!empty($params['search']) || $params['category'] > 0 || !empty($params['difficulty'])): ?>
                                Try adjusting your filters or search terms
                            <?php else: ?>
                                Check back later for new courses
                            <?php endif; ?>
                        </p>
                        <a href="courses_v2.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Auto-submit filters on change
            $('#filterForm select').on('change', function() {
                $('#filterForm').submit();
            });
            
            // Search suggestions (if needed)
            $('input[name="search"]').on('input', function() {
                const query = $(this).val();
                if (query.length >= 3) {
                    // Implement search suggestions here
                }
            });
        });
    </script>
</body>
</html>
