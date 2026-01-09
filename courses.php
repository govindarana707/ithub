<?php
require_once 'config/config.php';
require_once 'models/Course.php';

$course = new Course();

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Get courses
if ($search) {
    $courses = $course->searchCourses($search, $category, $difficulty, $limit);
} elseif ($category) {
    $courses = $course->getCoursesByCategory($category, $limit);
} else {
    $courses = $course->getAllCourses('published', $limit, $offset);
}

// Get categories for filter
$conn = connectDB();
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();
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
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Explore Courses</h1>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Courses</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
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
                                <label class="form-label">Difficulty</label>
                                <select name="difficulty" class="form-select">
                                    <option value="">All Levels</option>
                                    <option value="beginner" <?php echo $difficulty === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo $difficulty === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo $difficulty === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <a href="courses.php" class="btn btn-outline-secondary">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Grid -->
        <div class="row g-4">
            <?php if (empty($courses)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h3>No courses found</h3>
                        <p class="text-muted">Try adjusting your search criteria or browse all courses.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $c): ?>
                    <div class="col-md-4">
                        <div class="card course-card h-100">
                            <?php if ($c['thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($c['thumbnail'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($c['title']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="<?php echo htmlspecialchars($c['title']); ?>">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($c['category_name']); ?></span>
                                    <span class="badge bg-secondary"><?php echo ucfirst($c['difficulty_level']); ?></span>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($c['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($c['description']), 0, 100); ?>...</p>
                                <div class="course-meta mt-auto">
                                    <small><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($c['instructor_name']); ?></small>
                                    <small><i class="fas fa-clock me-1"></i><?php echo $c['duration_hours']; ?> hours</small>
                                    <small><i class="fas fa-tag me-1"></i>Rs<?php echo number_format($c['price'], 2); ?></small>
                                </div>
                                <div class="mt-3">
                                    <a href="course-details.php?id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
                                        <button class="btn btn-success btn-sm enroll-btn" data-course-id="<?php echo $c['id']; ?>">
                                            <i class="fas fa-plus me-1"></i>Enroll
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if (count($courses) === $limit && !$search): ?>
            <div class="text-center mt-4">
                <button class="btn btn-outline-primary load-more" data-page="<?php echo $page; ?>" data-url="courses.php" data-container=".row.g-4">
                    <i class="fas fa-plus me-2"></i>Load More Courses
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Handle enrollment
            $('.enroll-btn').click(function() {
                var btn = $(this);
                var courseId = btn.data('course-id');
                var originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enrolling...');
                
                $.ajax({
                    url: 'api/enroll_course.php',
                    type: 'POST',
                    data: { course_id: courseId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            btn.removeClass('btn-success').addClass('btn-secondary')
                               .html('<i class="fas fa-check me-1"></i>Enrolled')
                               .prop('disabled', true);
                        } else {
                            showAlert(response.message, 'danger');
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        showAlert('An error occurred. Please try again.', 'danger');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
</body>
</html>
