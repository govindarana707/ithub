<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// API endpoint for instructor operations
header('Content-Type: application/json');

// Check if user is authenticated and has instructor privileges
if (!isLoggedIn() || !in_array(getUserRole(), ['instructor', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_profile':
        $profile = $instructor->getInstructorProfile($instructorId);
        echo json_encode(['success' => true, 'data' => $profile]);
        break;
        
    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $instructor->updateInstructorProfile($instructorId, $data);
        echo json_encode($result);
        break;
        
    case 'get_courses':
        $status = $_GET['status'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $courses = $instructor->getInstructorCourses($instructorId, $status, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
        
    case 'get_course':
        $courseId = intval($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $courseData = $course->getCourseById($courseId);
        if (!$courseData || $courseData['instructor_id'] != $instructorId) {
            echo json_encode(['success' => false, 'error' => 'Course not found or access denied']);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $courseData]);
        break;
        
    case 'create_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $instructor->createInstructorCourse($instructorId, $data);
        echo json_encode($result);
        break;
        
    case 'update_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = intval($input['course_id'] ?? 0);
        unset($input['course_id']);
        
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $result = $instructor->updateInstructorCourse($instructorId, $courseId, $input);
        echo json_encode($result);
        break;
        
    case 'delete_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = intval($input['course_id'] ?? 0);
        
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $result = $instructor->deleteInstructorCourse($instructorId, $courseId);
        echo json_encode($result);
        break;
        
    case 'get_students':
        $courseId = intval($_GET['course_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 100);
        $offset = intval($_GET['offset'] ?? 0);
        
        $students = $instructor->getInstructorStudents($instructorId, $courseId, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $students]);
        break;
        
    case 'get_analytics':
        $dateRange = $_GET['date_range'] ?? '30days';
        $analytics = $instructor->getInstructorAnalytics($instructorId, $dateRange);
        echo json_encode(['success' => true, 'data' => $analytics]);
        break;
        
    case 'get_earnings':
        $dateRange = $_GET['date_range'] ?? '30days';
        $earnings = $instructor->getInstructorEarnings($instructorId, $dateRange);
        echo json_encode(['success' => true, 'data' => $earnings]);
        break;
        
    case 'get_activity_log':
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $activityLog = $instructor->getInstructorActivityLog($instructorId, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $activityLog]);
        break;
        
    case 'get_course_stats':
        $courseId = intval($_GET['course_id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        // Verify course ownership
        $courseData = $course->getCourseById($courseId);
        if (!$courseData || $courseData['instructor_id'] != $instructorId) {
            echo json_encode(['success' => false, 'error' => 'Course not found or access denied']);
            exit;
        }
        
        $stats = $course->getCourseStatistics($courseId);
        $enrolledStudents = $course->getEnrolledStudents($courseId);
        $lessons = $course->getCourseLessons($courseId);
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'statistics' => $stats,
                'enrolled_students' => $enrolledStudents,
                'lessons' => $lessons
            ]
        ]);
        break;
        
    case 'duplicate_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = intval($input['course_id'] ?? 0);
        $newTitle = $input['new_title'] ?? '';
        
        if ($courseId <= 0 || empty($newTitle)) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID or title']);
            exit;
        }
        
        // Verify course ownership
        $courseData = $course->getCourseById($courseId);
        if (!$courseData || $courseData['instructor_id'] != $instructorId) {
            echo json_encode(['success' => false, 'error' => 'Course not found or access denied']);
            exit;
        }
        
        $result = $course->duplicateCourse($courseId, $newTitle);
        echo json_encode($result);
        break;
        
    case 'get_categories':
        $conn = connectDB();
        $categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        
        echo json_encode(['success' => true, 'data' => $categories]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Action not found']);
        break;
}
?>
