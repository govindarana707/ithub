<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../includes/universal_header.php';

$course = new Course();
$userId = $_SESSION['user_id'];

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

$conn->close();
?>

    <style>
        .course-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .course-thumbnail {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        .course-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .price-tag.paid {
            color: #007bff;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
         <!-- Enhanced Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="my-courses.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-graduation-cap me-2"></i> My Courses
                    <span class="badge bg-primary float-end"><?php echo $totalEnrolled; ?></span>
                </a>
                <a href="quizzes.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-brain me-2"></i> Quizzes
                    <span class="badge bg-info float-end"><?php echo $quizStats['total_attempts']; ?></span>
                </a>
                <a href="quiz-results.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Quiz Results
                </a>
                <a href="discussions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-comments me-2"></i> Discussions
                </a>
                <a href="certificates.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-certificate me-2"></i> Certificates
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
                <a href="../logout.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
            
           </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-book-open me-2"></i>Browse Courses</h1>
                    <div class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        <span id="courseCount"><?php echo count($courses); ?></span> courses found
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Search Courses</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
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
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Difficulty</label>
                                <select name="difficulty" class="form-select">
                                    <option value="">All Levels</option>
                                    <option value="beginner" <?php echo $difficulty == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo $difficulty == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo $difficulty == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Price</label>
                                <select name="price" class="form-select">
                                    <option value="">All Prices</option>
                                    <option value="free" <?php echo $price == 'free' ? 'selected' : ''; ?>>Free</option>
                                    <option value="paid" <?php echo $price == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i>Apply Filters
                                    </button>
                                    <a href="courses.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Course Grid -->
                <div class="row g-4">
                    <?php if (empty($courses)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No courses found</h4>
                            <p class="text-muted">Try adjusting your filters or search terms.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card course-card">
                                    <div class="position-relative">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="<?php echo resolveUploadUrl($course['thumbnail']); ?>" class="card-img-top course-thumbnail" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php else: ?>
                                            <div class="course-thumbnail">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span class="course-badge">
                                            <span class="badge bg-<?php echo $course['difficulty_level'] === 'beginner' ? 'success' : ($course['difficulty_level'] === 'intermediate' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($course['difficulty_level']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <p class="card-text text-muted small"><?php echo substr(strip_tags($course['description']), 0, 100); ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo $course['duration_hours']; ?>h
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($course['category_name']); ?>
                                            </span>
                                            <div class="price-tag <?php echo $course['price'] > 0 ? 'paid' : ''; ?>">
                                                <?php echo $course['price'] > 0 ? 'Rs' . number_format($course['price'], 2) : 'Free'; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <?php if (in_array($course['id'], $enrolledCourses)): ?>
                                                <button class="btn btn-success flex-fill" disabled>
                                                    <i class="fas fa-check me-1"></i>Enrolled
                                                </button>
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-play me-1"></i>Continue
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-primary flex-fill enroll-btn" data-course-id="<?php echo $course['id']; ?>">
                                                    <i class="fas fa-plus me-1"></i>Enroll Now
                                                </button>
                                                <a href="../course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-info me-1"></i>Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if (count($courses) >= $limit): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&difficulty=<?php echo $difficulty; ?>&price=<?php echo $price; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link"><?php echo $page; ?></span>
                            </li>
                            
                            <?php if (count($courses) >= $limit): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&difficulty=<?php echo $difficulty; ?>&price=<?php echo $price; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrf_token" value="<?php echo generateCSRFToken(); ?>">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle course enrollment
            $('.enroll-btn').click(function() {
                const btn = $(this);
                const courseId = btn.data('course-id');
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enrolling...');
                
                $.ajax({
                    url: '/store/api/enroll_course.php',
                    method: 'POST',
                    data: { course_id: courseId, csrf_token: $('#csrf_token').val() },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            btn.removeClass('btn-primary').addClass('btn-success')
                               .html('<i class="fas fa-check me-1"></i>Enrolled')
                               .prop('disabled', true);
                            
                            // Show success message
                            showAlert('Successfully enrolled in course!', 'success');
                            
                            // Update the continue button
                            btn.next().removeClass('btn-outline-secondary').addClass('btn-outline-primary')
                               .html('<i class="fas fa-play me-1"></i>Continue');
                        } else {
                            showAlert(response.message || 'Enrollment failed', 'danger');
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        showAlert('An error occurred. Please try again.', 'danger');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Show alert function
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('body').prepend(alertHtml);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    $('.alert').alert('close');
                }, 3000);
            }
        });
    </script>
