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

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$price = $_GET['price'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Get courses with filters
$courses = $course->getAllCourses('published', $limit, $offset);

// Apply additional filters
if (!empty($search) || !empty($category) || !empty($difficulty) || !empty($price)) {
    $courses = $course->searchCourses($search, $category, $difficulty, $limit);
}

// Get categories for filter
$conn = connectDB();
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get enrolled courses to show "Enrolled" button
$enrolledCourses = [];
$enrolledResult = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enrolledResult->bind_param("i", $userId);
$enrolledResult->execute();
$enrolledData = $enrolledResult->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($enrolledData as $enrolled) {
    $enrolledCourses[] = $enrolled['course_id'];
}
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
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
        }
        .course-card-modern {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
        }
        .course-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 32px rgba(0,0,0,0.15);
        }
        .course-thumbnail-modern {
            height: 200px;
            object-fit: cover;
        }
        .badge-soft {
            background: rgba(255,255,255,0.15);
            color: #fff;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
        }
        .btn-modern {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            font-weight: bold;
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link active" href="courses.php">
                    <i class="fas fa-book me-1"></i> Courses
                </a>
                <a class="nav-link" href="my-courses.php">
                    <i class="fas fa-book-open me-1"></i> My Courses
                </a>
                <a class="nav-link" href="certificates.php">
                    <i class="fas fa-certificate me-1"></i> Certificates
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user me-1"></i> Profile
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Hero Section -->
        <div class="hero-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">
                        <i class="fas fa-book-open me-3"></i>
                        <span class="text-gradient">Course Catalog</span>
                    </h1>
                    <p class="lead opacity-75 mb-0">Discover and enroll in courses that match your learning goals</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <small class="opacity-75">Available Courses</small>
                            <div class="h3 mb-0"><?php echo count($courses); ?></div>
                        </div>
                        <div>
                            <small class="opacity-75">Enrolled</small>
                            <div class="h3 mb-0"><?php echo count($enrolledCourses); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section mb-4">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search Courses</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Difficulty</label>
                    <select name="difficulty" class="form-select">
                        <option value="">All Levels</option>
                        <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="newest">Newest</option>
                        <option value="popular">Most Popular</option>
                        <option value="rating">Highest Rated</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-modern flex-fill">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="courses.php" class="btn btn-outline-secondary btn-modern">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <!-- Courses Grid -->
        <div class="row g-4">
            <?php if (empty($courses)): ?>
                <div class="col-12 text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted opacity-50"></i>
                    </div>
                    <h4 class="text-muted">No courses found</h4>
                    <p class="text-muted mb-4">Try adjusting your filters or browse all available courses.</p>
                    <a href="courses.php" class="btn btn-primary btn-modern btn-lg">
                        <i class="fas fa-compass me-2"></i>Browse All Courses
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 course-card-modern">
                            <?php if ($course['thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" 
                                     class="card-img-top course-thumbnail-modern" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <?php else: ?>
                                <div class="card-img-top course-thumbnail-modern d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-image fa-3x text-muted opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <span class="badge bg-<?php echo $course['difficulty_level'] === 'beginner' ? 'success' : ($course['difficulty_level'] === 'intermediate' ? 'warning' : 'danger'); ?> rounded-pill">
                                        <?php echo ucfirst($course['difficulty_level']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted small flex-grow-1"><?php echo substr(strip_tags($course['description']), 0, 120); ?>...</p>
                                
                                <div class="mb-3">
                                    <span class="badge badge-soft me-2">
                                        <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($course['category_name']); ?>
                                    </span>
                                    <span class="badge badge-soft">
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
                                            <div class="fw-bold text-success"><?php echo $course['duration_hours'] ?? 0; ?>h</div>
                                            <div class="small text-muted">Duration</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-center p-2 bg-light rounded">
                                            <div class="fw-bold text-info"><?php echo $course['lesson_count'] ?? 0; ?></div>
                                            <div class="small text-muted">Lessons</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-auto">
                                    <?php if (in_array($course['id'], $enrolledCourses)): ?>
                                        <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-modern flex-fill">
                                            <i class="fas fa-play me-1"></i>Continue Learning
                                        </a>
                                    <?php else: ?>
                                        <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-modern flex-fill">
                                            <i class="fas fa-info-circle me-1"></i>View Details
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline-warning btn-modern" onclick="toggleWishlist(<?php echo $course['id']; ?>)">
                                        <i class="fas fa-heart me-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (count($courses) > $limit): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&difficulty=<?php echo urlencode($difficulty); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item active">
                        <span class="page-link"><?php echo $page; ?></span>
                    </li>
                    
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&difficulty=<?php echo urlencode($difficulty); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleWishlist(courseId) {
            // Toggle wishlist functionality
            $.ajax({
                url: '../api/toggle_wishlist.php',
                method: 'POST',
                data: { course_id: courseId },
                success: function(response) {
                    if (response.success) {
                        const btn = event.target.closest('button');
                        if (response.in_wishlist) {
                            btn.classList.remove('btn-outline-warning');
                            btn.classList.add('btn-warning');
                            btn.innerHTML = '<i class="fas fa-heart me-1"></i>';
                        } else {
                            btn.classList.remove('btn-warning');
                            btn.classList.add('btn-outline-warning');
                            btn.innerHTML = '<i class="far fa-heart me-1"></i>';
                        }
                    }
                }
            });
        }

        // Add animations
        $(document).ready(function() {
            $('.course-card-modern').each(function(index) {
                $(this).delay(index * 100).queue(function() {
                    $(this).addClass('animate__animated animate__fadeInUp');
                });
            });
        });
    </script>
</body>
</html>
