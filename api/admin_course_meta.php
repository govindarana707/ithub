<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once dirname(__DIR__) . '/config/config.php';
    require_once dirname(__DIR__) . '/includes/auth.php';
    require_once dirname(__DIR__) . '/models/Course.php';

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Always read course_id from JSON body for API calls (most reliable)
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $courseId = 0;
    if (is_array($payload) && isset($payload['course_id'])) {
        $courseId = (int)$payload['course_id'];
    } elseif (isset($_GET['course_id'])) {
        $courseId = (int)$_GET['course_id'];
    } elseif (isset($_POST['course_id'])) {
        $courseId = (int)$_POST['course_id'];
    } elseif (isset($_REQUEST['course_id'])) {
        $courseId = (int)$_REQUEST['course_id'];
    }

    if ($courseId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid course_id: ' . $courseId]);
        exit;
    }

    // Check access: admin can edit any course, instructor can only edit their own
    $userRole = getUserRole();
    $userId = $_SESSION['user_id'] ?? 0;

    if ($userRole !== 'admin') {
        require_once dirname(__DIR__) . '/models/Instructor.php';
        $instructorModel = new Instructor();
        $courses = $instructorModel->getInstructorCourses($userId);
        $canEdit = false;
        foreach ($courses as $c) {
            if ($c['id'] == $courseId) {
                $canEdit = true;
                break;
            }
        }
        if (!$canEdit) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }

    $course = new Course();

    if ($method === 'GET') {
        $meta = $course->getCourseMeta($courseId);
        echo json_encode(['success' => true, 'meta' => $meta]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // $payload is already defined above, just ensure it's valid
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $meta = $payload['meta'] ?? null;
    if (!is_array($meta)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid meta payload']);
        exit;
    }

    $ok = $course->setCourseMeta($courseId, $meta);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save meta']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
