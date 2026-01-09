<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];

$csrfToken = generateCSRFToken();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($postedToken)) {
        $_SESSION['error_message'] = 'Invalid request token. Please refresh and try again.';
        header('Location: create-course.php');
        exit;
    }

    $thumbnailUploadPath = null;
    if (isset($_FILES['course_thumbnail_file']) && is_array($_FILES['course_thumbnail_file']) && ($_FILES['course_thumbnail_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFile($_FILES['course_thumbnail_file'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'course_thumbnails');
        if (!($upload['success'] ?? false)) {
            $_SESSION['error_message'] = 'Thumbnail upload failed: ' . ($upload['message'] ?? 'Unknown error');
            header('Location: create-course.php');
            exit;
        }
        $thumbnailUploadPath = $upload['filename'];
    }

    $status = sanitize($_POST['submit_status'] ?? ($_POST['status'] ?? 'draft'));
    if (!in_array($status, ['draft', 'published'], true)) {
        $status = 'draft';
    }

    $data = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'category_id' => intval($_POST['category_id']),
        'price' => floatval($_POST['price']),
        'duration_hours' => intval($_POST['duration_hours']),
        'difficulty_level' => sanitize($_POST['difficulty_level']),
        'status' => $status,
        'thumbnail' => $thumbnailUploadPath ?? sanitize($_POST['course_thumbnail'] ?? '')
    ];

    if ($data['title'] === '' || strlen($data['title']) < 5) {
        $_SESSION['error_message'] = 'Course title must be at least 5 characters.';
        header('Location: create-course.php');
        exit;
    }

    if ($data['description'] === '' || strlen($data['description']) < 50) {
        $_SESSION['error_message'] = 'Course description must be at least 50 characters.';
        header('Location: create-course.php');
        exit;
    }

    if ($data['category_id'] <= 0) {
        $_SESSION['error_message'] = 'Please select a valid category.';
        header('Location: create-course.php');
        exit;
    }

    if (!in_array($data['difficulty_level'], ['beginner', 'intermediate', 'advanced'], true)) {
        $_SESSION['error_message'] = 'Please select a valid difficulty level.';
        header('Location: create-course.php');
        exit;
    }

    if ($data['duration_hours'] <= 0) {
        $_SESSION['error_message'] = 'Duration must be at least 1 hour.';
        header('Location: create-course.php');
        exit;
    }

    if ($data['price'] < 0) {
        $_SESSION['error_message'] = 'Price cannot be negative.';
        header('Location: create-course.php');
        exit;
    }
    
    $result = $instructor->createInstructorCourse($instructorId, $data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = 'Course created successfully!';
        logActivity($_SESSION['user_id'], 'course_created', "Created course: {$data['title']}");

        $redirectToBuilder = ($_POST['open_builder'] ?? '') === '1';
        if ($redirectToBuilder && !empty($result['course_id'])) {
            header('Location: ../admin/course_builder.php?id=' . intval($result['course_id']));
        } else {
            header('Location: courses.php');
        }
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to create course: ' . $result['error'];
    }
}

// Get categories
$conn = connectDB();
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .preview-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            min-height: 200px;
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-chalkboard-teacher me-1"></i> My Courses
                </a>
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users me-1"></i> Students
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
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
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="create-course.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Create New Course</h1>
                    <a href="courses.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Courses
                    </a>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle me-2"></i>Basic Information</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course Thumbnail</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Upload from Computer</label>
                                            <input type="file" name="course_thumbnail_file" class="form-control" accept="image/*">
                                            <div class="form-text small">JPG, PNG, GIF, WebP (Max: 10MB)</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Or enter Image URL</label>
                                            <input type="url" name="course_thumbnail" class="form-control" 
                                                   placeholder="https://example.com/image.jpg">
                                            <div class="form-text small">External image URL</div>
                                        </div>
                                    </div>
                                    <div id="thumbnail_preview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course Title *</label>
                                    <input type="text" name="title" class="form-control" required 
                                           placeholder="Enter an engaging course title">
                                    <div class="form-text">Choose a title that clearly describes what students will learn</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Course Description *</label>
                                    <textarea name="description" class="form-control" rows="6" required 
                                              placeholder="Describe your course in detail... What will students learn? What are the prerequisites?"></textarea>
                                    <div class="form-text">Provide a comprehensive description of your course content and learning objectives</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="preview-section">
                                    <h6>Live Preview</h6>
                                    <div id="titlePreview" class="h5 text-muted">Course Title</div>
                                    <div id="descriptionPreview" class="small text-muted">Course description will appear here...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-cog me-2"></i>Course Details</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
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
                                    <label class="form-label">Difficulty Level *</label>
                                    <select name="difficulty_level" class="form-select" required>
                                        <option value="">Select Level</option>
                                        <option value="beginner">Beginner - No prior experience needed</option>
                                        <option value="intermediate">Intermediate - Some experience recommended</option>
                                        <option value="advanced">Advanced - Comprehensive knowledge required</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (Rs) *</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" required 
                                           placeholder="0.00">
                                    <div class="form-text">Set a competitive price for your course</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Estimated Duration (hours) *</label>
                                    <input type="number" name="duration_hours" class="form-control" min="1" required 
                                           placeholder="10">
                                    <div class="form-text">How long will it take to complete this course?</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Publishing Options -->
                    <div class="form-section">
                        <h3><i class="fas fa-globe me-2"></i>Publishing Options</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course Status *</label>
                                    <select name="status" class="form-select" required>
                                        <option value="draft">Draft - Save but don't publish</option>
                                        <option value="published">Published - Make available to students</option>
                                    </select>
                                    <div class="form-text">You can always change this later</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" value="1" id="openBuilder" name="open_builder" checked>
                                    <label class="form-check-label" for="openBuilder">
                                        Open Course Builder after creating
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Guidelines -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-lightbulb me-2"></i>Course Creation Tips</h5>
                        <ul class="mb-0">
                            <li>Use clear, descriptive titles that include relevant keywords</li>
                            <li>Break down complex topics into manageable lessons</li>
                            <li>Include practical examples and real-world applications</li>
                            <li>Set realistic expectations about course duration and difficulty</li>
                            <li>Research similar courses to determine competitive pricing</li>
                        </ul>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="courses.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <div>
                            <button type="submit" name="submit_status" value="draft" class="btn btn-outline-primary me-2">
                                <i class="fas fa-save me-2"></i>Save as Draft
                            </button>
                            <button type="submit" name="submit_status" value="published" class="btn btn-success">
                                <i class="fas fa-rocket me-2"></i>Publish Course
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Live preview
        $('input[name="title"], textarea[name="description"]').on('input', function() {
            const title = $('input[name="title"]').val() || 'Course Title';
            const description = $('textarea[name="description"]').val() || 'Course description will appear here...';
            
            $('#titlePreview').text(title);
            $('#descriptionPreview').text(description.substring(0, 150) + (description.length > 150 ? '...' : ''));
        });
        
        // Handle file upload preview
        $(document).on('change', 'input[name="course_thumbnail_file"]', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size too large. Maximum size is 10MB');
                    $(this).val('');
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#thumbnail_preview').html(
                        '<div class="d-flex align-items-center gap-2">' +
                        '<img src="' + e.target.result + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview">' +
                        '<span class="small text-success">New image selected</span>' +
                        '</div>'
                    );
                };
                reader.readAsDataURL(file);
                
                // Clear URL field when file is selected
                $('input[name="course_thumbnail"]').val('');
            }
        });
        
        // Handle URL input change
        $(document).on('input', 'input[name="course_thumbnail"]', function() {
            const url = $(this).val();
            if (url) {
                // Clear file input when URL is entered
                $('input[name="course_thumbnail_file"]').val('');
                
                // Show preview if valid URL
                if (url.match(/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
                    $('#thumbnail_preview').html(
                        '<div class="d-flex align-items-center gap-2">' +
                        '<img src="' + url + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" alt="Preview" onerror="this.style.display=\'none\'">' +
                        '<span class="small text-info">URL image</span>' +
                        '</div>'
                    );
                }
            } else {
                $('#thumbnail_preview').html('');
            }
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            const title = $('input[name="title"]').val().trim();
            const description = $('textarea[name="description"]').val().trim();
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Course title must be at least 5 characters long');
                return false;
            }
            
            if (description.length < 50) {
                e.preventDefault();
                alert('Course description must be at least 50 characters long');
                return false;
            }
        });
    </script>
</body>
</html>
