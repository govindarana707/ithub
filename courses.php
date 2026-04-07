<?php
require_once 'includes/session_helper.php';
require_once 'config/config.php';
require_once 'services/CourseService.php';

// Initialize services
$courseService = new CourseService();

// Parse and validate parameters
$params = [
    'search' => sanitize(isset($_GET['search']) ? $_GET['search'] : ''),
    'category' => intval(isset($_GET['category']) ? $_GET['category'] : 0),
    'difficulty' => sanitize(isset($_GET['difficulty']) ? $_GET['difficulty'] : ''),
    'page' => max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1)),
    'limit' => 12,
    'sort' => sanitize(isset($_GET['sort']) ? $_GET['sort'] : 'latest')
];

// Build filters
$filters = [
    'status' => 'published',
    'visibility' => 'public',
    'deleted_at' => null,
    'approved' => true
];

if (!empty($params['search'])) {
    $filters['search'] = $params['search'];
}

if ($params['category'] > 0) {
    $filters['category_id'] = $params['category'];
}

if (!empty($params['difficulty'])) {
    $filters['difficulty_level'] = $params['difficulty'];
}

// Add sorting
switch ($params['sort']) {
    case 'popular':
        $filters['sort'] = 'enrollment_count DESC';
        break;
    case 'price_low':
        $filters['sort'] = 'price ASC';
        break;
    case 'price_high':
        $filters['sort'] = 'price DESC';
        break;
    default:
        $filters['sort'] = 'created_at DESC';
}

// Calculate pagination
$offset = ($params['page'] - 1) * $params['limit'];
$total = $courseService->countCourses($filters);
$totalPages = ceil($total / $params['limit']);
$hasNext = $params['page'] < $totalPages;

// Get courses
$courses = $courseService->getCourses($filters, $params['limit'], $offset);

// Get categories
$categories = $courseService->getCategories();

// Get popular courses for sidebar
$popularCourses = $courseService->getPopularCourses(5);

// Get user's enrollments if logged in
$userEnrollments = [];
if (isLoggedIn() && getUserRole() === 'student') {
    $userEnrollments = $courseService->getEnrolledCourses($_SESSION['user_id']);
}
$userEnrolledCourseIds = array_column($userEnrollments, 'id');

// Generate query string for preserving filters
$queryString = http_build_query(array_filter($_GET, function($k) {
    return $k !== 'page';
}, ARRAY_FILTER_USE_KEY));
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
    <link href="assets/css/esewa.css" rel="stylesheet">
    <style>
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            position: relative;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .search-container h1 {
            color: white;
            margin-bottom: 20px;
        }
        .course-meta {
            font-size: 0.85rem;
        }
        .course-meta small {
            display: block;
            margin-bottom: 5px;
        }
        .pagination-container {
            margin-top: 30px;
        }
        .price-tag {
            font-size: 1.1rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container">
        <div class="search-container">
            <div class="row">
                <div class="col-12">
                    <h1 class="text-center">Discover Your Learning Journey</h1>
                    <p class="text-center text-white-50 mb-4">Explore our comprehensive course catalog</p>
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search for courses..." 
                                       value="<?php echo htmlspecialchars($params['search']); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-select form-select-lg">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($params['category'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="difficulty" class="form-select form-select-lg">
                                <option value="">All Levels</option>
                                <option value="beginner" <?php echo ($params['difficulty'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo ($params['difficulty'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo ($params['difficulty'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-section">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                    
                    <form method="GET">
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
                            <a href="courses.php" class="btn btn-outline-secondary w-100 mt-2">
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
                                            <?php echo isset($course['enrollment_count']) ? $course['enrollment_count'] : 0; ?> students
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
                            <?php echo $total; ?> courses found
                        </p>
                    </div>
                </div>

                <!-- Course Grid -->
                <?php if (!empty($courses)): ?>
                    <div class="row g-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card course-card">
                                    <?php 
                                    $isEnrolled = in_array($course['id'], $userEnrolledCourseIds);
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
                                        <div class="mb-2">
                                            <span class="badge bg-primary me-1"><?php echo htmlspecialchars(isset($course['category_name']) ? $course['category_name'] : ''); ?></span>
                                            <span class="badge bg-secondary"><?php echo ucfirst(isset($course['difficulty_level']) ? $course['difficulty_level'] : ''); ?></span>
                                        </div>
                                        
                                        <h5 class="card-title">
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </a>
                                        </h5>
                                        
                                        <p class="card-text text-muted">
                                            <?php echo substr(htmlspecialchars(isset($course['description']) ? $course['description'] : ''), 0, 80) . '...'; ?>
                                        </p>
                                        
                                        <div class="course-meta mt-auto mb-3">
                                            <small><i class="fas fa-user me-1"></i><?php echo htmlspecialchars(isset($course['instructor_name']) ? $course['instructor_name'] : 'Instructor'); ?></small>
                                            <small><i class="fas fa-clock me-1"></i><?php echo isset($course['duration_hours']) ? $course['duration_hours'] : 0; ?>h</small>
                                            <small class="price-tag"><i class="fas fa-tag me-1"></i>Rs<?php echo number_format(isset($course['price']) ? $course['price'] : 0, 2); ?></small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php if ($isEnrolled): ?>
                                                <a href="student/view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-play me-1"></i>Continue
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm enroll-course-btn" 
                                                        data-course-id="<?php echo $course['id']; ?>"
                                                        data-course-title="<?php echo htmlspecialchars($course['title']); ?>"
                                                        data-course-price="<?php echo $course['price']; ?>">
                                                    <i class="fas fa-plus me-1"></i>Enroll Now
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-info-circle me-1"></i>Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php if ($params['page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $params['page'] - 1; ?>&<?php echo $queryString; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    $start = max(1, $params['page'] - 2);
                                    $end = min($totalPages, $params['page'] + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <li class="page-item <?php echo ($i == $params['page']) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $queryString; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($params['page'] < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $params['page'] + 1; ?>&<?php echo $queryString; ?>">
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
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-2"></i>Select Payment Method
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="selectedCourseId">
                    <input type="hidden" id="selectedCourseTitle">
                    <input type="hidden" id="selectedCoursePrice">
                    
                    <div class="course-summary mb-4 p-3 bg-light rounded">
                        <h6 class="mb-2">Course Details</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-start">
                                <strong id="modalCourseTitle">Course Title</strong>
                                <br><small class="text-muted" id="modalCourseDuration">Duration: 0 hours</small>
                            </div>
                            <div class="text-end">
                                <div class="price-tag" id="modalCoursePrice">Rs0.00</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <button class="btn btn-success pay-btn" data-method="esewa">
                            🟢 Pay with eSewa
                        </button>
                        <button class="btn btn-primary pay-btn" data-method="khalti">
                            🟣 Pay with Khalti
                        </button>
                        <button class="btn btn-warning pay-btn" data-method="free">
                            🆓 Start Free Trial
                        </button>
                        <button class="btn btn-dark pay-btn" data-method="other">
                            💳 Other Payment Methods
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            let selectedCourseId = null;

            // Click Enroll → Open Modal
            $('.enroll-course-btn').click(function() {
                console.log('Enroll button clicked');
                selectedCourseId = $(this).data('course-id');
                console.log('Selected course ID:', selectedCourseId);
                
                // Get course details from data attributes
                const courseTitle = $(this).data('course-title');
                const coursePrice = $(this).data('course-price');
                const courseCard = $(this).closest('.course-card');
                const courseDuration = courseCard.find('.course-meta small:contains("hours")').text().trim();
                
                console.log('Course details:', { title: courseTitle, price: coursePrice, duration: courseDuration });
                
                // Set modal data
                $('#selectedCourseId').val(selectedCourseId);
                $('#selectedCourseTitle').val(courseTitle);
                $('#selectedCoursePrice').val(coursePrice);
                $('#modalCourseTitle').text(courseTitle);
                $('#modalCoursePrice').text(coursePrice);
                $('#modalCourseDuration').text(courseDuration);
                
                console.log('Opening modal...');
                $('#paymentModal').modal('show');
            });

            // Handle payment selection
            $('.pay-btn').click(function() {
                console.log('Payment button clicked');
                const method = $(this).data('method');
                const courseId = $('#selectedCourseId').val();
                const courseTitle = $('#selectedCourseTitle').val();
                const coursePrice = $('#selectedCoursePrice').val();
                
                console.log('Payment method:', method);
                console.log('Course ID:', courseId);
                console.log('Course title:', courseTitle);

                $('#paymentModal').modal('hide');

                // Show processing message
                showAlert('Processing ' + method + ' for ' + courseTitle + '...', 'info');

                // Route to appropriate payment handler
                if(method === 'free'){
                    console.log('Starting free trial enrollment...');
                    enrollCourse(courseId, 'free_trial');
                }
                else if(method === 'esewa'){
                    initiateEsewaPayment(courseId, courseTitle, coursePrice);
                }
                else if(method === 'khalti'){
                    initiateKhaltiPayment(courseId, courseTitle, coursePrice);
                }
                else{
                    initiateOtherPayment(courseId, courseTitle, coursePrice);
                }
            });

            // Free trial enrollment
            function enrollCourse(courseId, type){
                console.log('enrollCourse called with:', { courseId, type });
                
                $.ajax({
                    url: 'api/enroll_course.php',
                    type: 'POST',
                    data: { 
                        course_id: courseId,
                        payment_method: type
                    },
                    xhrFields: {
                        withCredentials: true
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('Sending AJAX request...');
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        
                        if (response.success) {
                            // Show detailed success message for trials
                            let message = response.message;
                            if (response.enrollment_type === 'free_trial') {
                                message += `<br><small>Trial expires: ${new Date(response.expires_at).toLocaleDateString()}</small>`;
                            }
                            
                            showAlert(message, 'success');
                            
                            // Update button state
                            const btn = $('.enroll-course-btn[data-course-id="' + courseId + '"]');
                            btn.removeClass('btn-primary')
                              .addClass('btn-success')
                              .html('<i class="fas fa-check me-1"></i>Enrolled')
                              .prop('disabled', true);
                              
                            // Add enrolled badge
                            const card = btn.closest('.course-card');
                            if (!card.find('.enrolled-badge').length) {
                                card.find('.card-img-top').after(`
                                    <div class="enrolled-badge">
                                        <span class="badge bg-success">Enrolled</span>
                                    </div>
                                `);
                            }
                        } else {
                            console.log('Enrollment failed:', response.message);
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Enrollment Error:', xhr.responseText);
                        console.error('Status:', status);
                        console.error('Error:', error);
                        showAlert('Enrollment failed. Please try again.', 'danger');
                    }
                });
            }

            // eSewa payment initiation
            function initiateEsewaPayment(courseId, courseTitle, coursePrice) {
                $.ajax({
                    url: 'api/esewa_payment.php',
                    type: 'POST',
                    data: {
                        course_id: courseId,
                        payment_method: 'esewa'
                    },
                    xhrFields: {
                        withCredentials: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Create and submit eSewa form
                            const form = $('<form>', {
                                method: 'POST',
                                action: response.payment_form.form_action,
                                target: '_blank'
                            });
                            
                            $.each(response.payment_form.form_data, function(key, value) {
                                form.append($('<input>', {
                                    type: 'hidden',
                                    name: key,
                                    value: value
                                }));
                            });
                            
                            form.appendTo('body').submit();
                            showAlert('Redirecting to eSewa...', 'info');
                        } else {
                            showAlert('Payment initiation failed. Please try again.', 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        showAlert('Payment service unavailable. Please try again.', 'danger');
                    }
                });
            }

            // Khalti payment initiation (placeholder)
            function initiateKhaltiPayment(courseId, courseTitle, coursePrice) {
                showAlert('Khalti payment integration coming soon!', 'info');
                // TODO: Implement Khalti integration
            }

            // Other payment methods (placeholder)
            function initiateOtherPayment(courseId, courseTitle, coursePrice) {
                showAlert('Other payment methods coming soon!', 'info');
                // TODO: Implement other payment gateways
            }

            // Alert function
            function showAlert(message, type) {
                // Remove existing alerts
                $('.alert').remove();
                
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Insert at the top of the container
                $('.container').first().prepend(alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 5000);
            }
        });
    </script>
</body>
</html>
