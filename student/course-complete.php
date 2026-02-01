<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$course = new Course();
$userId = $_SESSION['user_id'];
$courseId = intval($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    redirect('../courses.php');
}

$courseData = $course->getCourseById($courseId);
if (!$courseData) {
    redirect('../courses.php');
}

// Verify enrollment and completion
$enrollment = $course->getEnrollment($userId, $courseId);
if (!$enrollment) {
    redirect('../courses.php');
}

// Check if course is completed (you can adjust this logic based on your enrollment table structure)
$progress = $enrollment['progress'] ?? 0;
if ($progress < 100) {
    redirect("lesson.php?course_id=$courseId");
}

require_once '../includes/universal_header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-trophy fa-5x text-warning mb-3"></i>
                    </div>

                    <h1 class="display-4 fw-bold text-success mb-3">Congratulations!</h1>
                    <h2 class="h4 text-muted mb-4">You have completed</h2>
                    <h3 class="mb-4 text-primary">
                        <?php echo htmlspecialchars($courseData['title']); ?>
                    </h3>

                    <p class="lead mb-5">
                        You have successfully finished all lessons in this course.
                        We hope you learned a lot and enjoyed the journey!
                    </p>

                    <div class="d-grid gap-2 d-sm-flex justify-content-center">
                        <a href="../my-courses.php" class="btn btn-outline-primary btn-lg px-4 gap-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to My Courses
                        </a>
                        <a href="certificate.php?course_id=<?php echo $courseId; ?>"
                            class="btn btn-success btn-lg px-4 gap-3">
                            <i class="fas fa-certificate me-2"></i>View Certificate
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <h4>What would you like to learn next?</h4>
                <div class="row mt-4">
                    <!-- Recommended courses could go here -->
                    <div class="col-12">
                        <a href="../courses.php" class="btn btn-link">Browse all courses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>