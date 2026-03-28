<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
requireStudent();

$course = new Course();
$studentId = $_SESSION['user_id'];
$courseId = $_GET['id'] ?? 0;

// Validate course ID
if (!$courseId || !is_numeric($courseId)) {
    $_SESSION['error_message'] = 'Invalid course ID';
    header('Location: courses.php');
    exit;
}

// Get course details
$courseDetails = $course->getCourseById($courseId);
if (!$courseDetails) {
    $_SESSION['error_message'] = 'Course not found';
    header('Location: courses.php');
    exit;
}

// Check if student is already enrolled
$enrolledCourses = $course->getEnrolledCourses($studentId);
$isEnrolled = false;
foreach ($enrolledCourses as $enrolled) {
    if ($enrolled['id'] == $courseId) {
        $isEnrolled = true;
        break;
    }
}

// Get course lessons count
$conn = connectDB();
$lessonStmt = $conn->prepare("SELECT COUNT(*) as lesson_count FROM lessons WHERE course_id = ?");
if ($lessonStmt === false) {
    error_log("Failed to prepare lesson count query: " . $conn->error);
    $lessonCount = 0;
} else {
    $lessonStmt->bind_param("i", $courseId);
    $lessonStmt->execute();
    $lessonCount = $lessonStmt->get_result()->fetch_assoc()['lesson_count'];
}

// Get instructor details with fallback
$instructorStmt = $conn->prepare("SELECT full_name, bio FROM users_new WHERE id = ?");
if ($instructorStmt === false) {
    error_log("Failed to prepare instructor query: " . $conn->error);
    $instructor = ['full_name' => 'Unknown Instructor', 'bio' => ''];
} else {
    $instructorStmt->bind_param("i", $courseDetails['instructor_id']);
    $instructorStmt->execute();
    $instructorResult = $instructorStmt->get_result();
    $instructor = $instructorResult->fetch_assoc() ?: ['full_name' => 'Unknown Instructor', 'bio' => ''];
}

// Get similar courses
$similarStmt = $conn->prepare("
    SELECT id, title, thumbnail, price, duration_hours 
    FROM courses 
    WHERE category_id = ? AND id != ? AND status = 'published' 
    LIMIT 4
");
$similarStmt->bind_param("ii", $courseDetails['category_id'], $courseId);
$similarStmt->execute();
$similarCourses = $similarStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseDetails['title']); ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            margin-left: 1rem;
        }
        
        .course-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .course-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,96C1248,75,1344,53,1392,42.7L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .course-thumbnail {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .info-card h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .instructor-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .instructor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .course-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6a6f73;
        }
        
        .btn-enroll {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-enroll:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-enroll:disabled {
            background: #6a6f73;
            cursor: not-allowed;
            transform: none;
        }
        
        .similar-course-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .similar-course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .similar-course-image {
            height: 120px;
            object-fit: cover;
            width: 100%;
        }
        
        .similar-course-content {
            padding: 1rem;
        }
        
        .course-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .badge-primary-custom {
            background: var(--gradient-primary);
            color: white;
        }
        
        .badge-info-custom {
            background: var(--gradient-info);
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1rem;
            }
            
            .course-header {
                padding: 2rem 1rem;
            }
            
            .course-thumbnail {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                            <li><a class="dropdown-item" href="my-courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="courses.php">Course Catalog</a></li>
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
                    <div class="list-group">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="my-courses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book-open me-2"></i> My Courses
                        </a>
                        <a href="courses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i> Course Catalog
                        </a>
                        <a href="certificates.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-certificate me-2"></i> Certificates
                        </a>
                        <a href="quiz-results.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> Quiz Results
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="main-content">
                        <!-- Course Header -->
                        <div class="course-header">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <span class="course-badge badge-primary-custom">
                                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($courseDetails['category_name'] ?? 'General'); ?>
                                        </span>
                                        <span class="course-badge badge-info-custom">
                                            <i class="fas fa-signal me-1"></i><?php echo ucfirst($courseDetails['difficulty_level'] ?? 'beginner'); ?>
                                        </span>
                                    </div>
                                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($courseDetails['title']); ?></h1>
                                    <p class="lead opacity-75"><?php echo htmlspecialchars($courseDetails['description'] ?? ''); ?></p>
                                    
                                    <div class="course-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $courseDetails['duration_hours'] ?? 0; ?> hours</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-book-open"></i>
                                            <span><?php echo $lessonCount; ?> lessons</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $courseDetails['enrollment_count'] ?? 0; ?> students</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-star"></i>
                                            <span>4.5 (<?php echo rand(50, 500); ?> reviews)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-center">
                                    <?php if ($courseDetails['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseDetails['thumbnail'])); ?>" 
                                             class="course-thumbnail mb-3" alt="<?php echo htmlspecialchars($courseDetails['title']); ?>">
                                    <?php else: ?>
                                        <div class="course-thumbnail d-flex align-items-center justify-content-center bg-light mb-3">
                                            <i class="fas fa-image fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Main Content -->
                            <div class="col-lg-8">
                                <!-- Course Description -->
                                <div class="info-card">
                                    <h4><i class="fas fa-info-circle me-2"></i>About This Course</h4>
                                    <p><?php echo nl2br(htmlspecialchars($courseDetails['description'] ?? '')); ?></p>
                                </div>

                                <!-- What You'll Learn -->
                                <div class="info-card">
                                    <h4><i class="fas fa-graduation-cap me-2"></i>What You'll Learn</h4>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Build modern web applications</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Master industry best practices</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Work on real-world projects</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Get hands-on experience</li>
                                    </ul>
                                </div>

                                <!-- Course Content -->
                                <div class="info-card">
                                    <h4><i class="fas fa-list me-2"></i>Course Content</h4>
                                    <div class="accordion" id="courseContent">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#module1">
                                                    <i class="fas fa-book me-2"></i>Module 1: Introduction
                                                </button>
                                            </h2>
                                            <div id="module1" class="accordion-collapse collapse show" data-bs-parent="#courseContent">
                                                <div class="accordion-body">
                                                    <ul class="list-unstyled">
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Course Overview</li>
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Getting Started</li>
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Setup Environment</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#module2">
                                                    <i class="fas fa-book me-2"></i>Module 2: Core Concepts
                                                </button>
                                            </h2>
                                            <div id="module2" class="accordion-collapse collapse" data-bs-parent="#courseContent">
                                                <div class="accordion-body">
                                                    <ul class="list-unstyled">
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Fundamentals</li>
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Advanced Topics</li>
                                                        <li class="mb-2"><i class="fas fa-play-circle me-2"></i>Best Practices</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <!-- Enrollment Card -->
                                <div class="info-card">
                                    <h4><i class="fas fa-shopping-cart me-2"></i>Enrollment</h4>
                                    
                                    <?php if ($isEnrolled): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>You are enrolled in this course
                                        </div>
                                        <a href="my-courses.php" class="btn btn-success w-100 mb-2">
                                            <i class="fas fa-play me-1"></i>Continue Learning
                                        </a>
                                    <?php else: ?>
                                        <div class="text-center mb-3">
                                            <h3 class="text-primary">FREE</h3>
                                            <p class="text-muted">Enroll now and start learning</p>
                                        </div>
                                        
                                        <a href="../billing.php?course_id=<?php echo $courseId; ?>" class="btn btn-enroll w-100">
                                            <i class="fas fa-plus-circle me-2"></i>Enroll Now
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Instructor Card -->
                                <div class="info-card">
                                    <h4><i class="fas fa-user-tie me-2"></i>Instructor</h4>
                                    <div class="instructor-card">
                                        <?php if (!empty($instructor['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($instructor['avatar'])); ?>" 
                                                 class="instructor-avatar" alt="Instructor">
                                        <?php else: ?>
                                            <div class="instructor-avatar d-flex align-items-center justify-content-center bg-light">
                                                <i class="fas fa-user fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($instructor['full_name']); ?></h6>
                                            <small class="text-muted">Expert Instructor</small>
                                        </div>
                                    </div>
                                    <p class="text-muted small"><?php echo htmlspecialchars($instructor['bio'] ?? 'Experienced professional with years of industry expertise.'); ?></p>
                                </div>

                                <!-- Course Stats -->
                                <div class="info-card">
                                    <h4><i class="fas fa-chart-bar me-2"></i>Course Stats</h4>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h5 class="text-primary"><?php echo $courseDetails['enrollment_count'] ?? 0; ?></h5>
                                            <small class="text-muted">Students</small>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="text-success">4.5</h5>
                                            <small class="text-muted">Rating</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Similar Courses -->
                        <?php if (!empty($similarCourses)): ?>
                            <div class="mt-5">
                                <h3 class="mb-4"><i class="fas fa-th-large me-2"></i>Similar Courses</h3>
                                <div class="row">
                                    <?php foreach ($similarCourses as $similar): ?>
                                        <div class="col-md-6 col-lg-3 mb-3">
                                            <div class="similar-course-card">
                                                <?php if ($similar['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($similar['thumbnail'])); ?>" 
                                                         class="similar-course-image" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                                                <?php else: ?>
                                                    <div class="similar-course-image d-flex align-items-center justify-content-center bg-light">
                                                        <i class="fas fa-image fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="similar-course-content">
                                                    <h6 class="fw-bold"><?php echo htmlspecialchars($similar['title']); ?></h6>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-primary fw-bold">FREE</span>
                                                        <small class="text-muted"><?php echo $similar['duration_hours'] ?? 0; ?>h</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Show success/error messages
        <?php if (isset($_SESSION['success_message'])): ?>
            alert('<?php echo $_SESSION['success_message']; ?>');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            alert('<?php echo $_SESSION['error_message']; ?>');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
