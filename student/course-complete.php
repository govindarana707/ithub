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
$progress = $enrollment['progress_percentage'] ?? 0;
if ($progress < 100) {
    // If NOT 100%, check if all lessons are completed? Or just redirect back to lesson.
    // Assuming 100% is required for completion page.
    // If logic is loose, maybe allow 90%+?
    if ($progress < 90) {
        redirect("lesson.php?course_id=$courseId");
    }
}

require_once '../includes/universal_header.php';
?>

<style>
    .completion-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-card {
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: none;
        background: white;
        overflow: hidden;
    }

    .success-icon-wrapper {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 5px 15px rgba(56, 239, 125, 0.4);
    }

    .confetti-piece {
        position: absolute;
        width: 10px;
        height: 10px;
        background: #f2d74e;
        top: 0;
        opacity: 0;
    }

    .animate-pop {
        animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes popIn {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .btn-action {
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="container completion-container py-5">
    <div class="row justify-content-center w-100">
        <div class="col-lg-8 col-md-10 text-center">
            <div class="dashboard-card card animate-pop">
                <div class="card-header border-0 py-4 text-white"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0 text-uppercase letter-spacing-2">Course Completed</h5>
                </div>
                <div class="card-body p-5">
                    <div class="success-icon-wrapper mb-4">
                        <i class="fas fa-trophy fa-4x text-white"></i>
                    </div>

                    <h1 class="display-5 fw-bold text-dark mb-2">Congratulations!</h1>
                    <p class="text-muted lead mb-4">You've successfully mastered</p>

                    <div class="bg-light p-4 rounded-3 mb-5 border">
                        <h3 class="h4 text-primary fw-bold mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo htmlspecialchars($courseData['title']); ?>
                        </h3>
                    </div>

                    <div class="row g-3 justify-content-center">
                        <div class="col-sm-6 col-md-5">
                            <a href="../my-courses.php" class="btn btn-outline-secondary btn-action w-100">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-5">
                            <a href="certificate.php?course_id=<?php echo $courseId; ?>"
                                class="btn btn-success btn-action w-100 text-white"
                                style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none;">
                                <i class="fas fa-certificate me-2"></i>Get Certificate
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light py-3 border-0">
                    <small class="text-muted">Keep learning! Check out simple recommendations below.</small>
                    <div class="mt-2 text-center">
                        <a href="../courses.php" class="text-decoration-none fw-bold">Browse More Courses <i
                                class="fas fa-arrow-right small ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script>
    // Simple confetti effect
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof confetti !== 'undefined') {
            var duration = 3000;
            var end = Date.now() + duration;

            (function frame() {
                confetti({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: ['#667eea', '#764ba2', '#38ef7d', '#f2d74e']
                });
                confetti({
                    particleCount: 5,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: ['#667eea', '#764ba2', '#38ef7d', '#f2d74e']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());

            // Big burst
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }, 500);
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>