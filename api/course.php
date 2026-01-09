<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// API endpoint for course operations (shared between admin and instructor)
header('Content-Type: application/json');

// Check if user is authenticated
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

require_once '../models/Course.php';
require_once '../models/User.php';

$course = new Course();
$user = new User();

$userId = $_SESSION['user_id'];
$userRole = getUserRole();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_course':
        $courseId = intval($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $courseData = $course->getCourseById($courseId);
        if (!$courseData) {
            echo json_encode(['success' => false, 'error' => 'Course not found']);
            exit;
        }
        
        // Check permissions
        if ($userRole === 'instructor' && $courseData['instructor_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $courseData]);
        break;
        
    case 'get_categories':
        $conn = connectDB();
        $categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        
        echo json_encode(['success' => true, 'data' => $categories]);
        break;
        
    case 'get_instructors':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $instructors = $user->getInstructors();
        echo json_encode(['success' => true, 'data' => $instructors]);
        break;
        
    case 'get_popular_courses':
        $limit = intval($_GET['limit'] ?? 10);
        $popularCourses = $course->getPopularCourses($limit);
        echo json_encode(['success' => true, 'data' => $popularCourses]);
        break;
        
    case 'get_course_stats':
        $courseId = intval($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $courseData = $course->getCourseById($courseId);
        if (!$courseData) {
            echo json_encode(['success' => false, 'error' => 'Course not found']);
            exit;
        }
        
        // Check permissions
        if ($userRole === 'instructor' && $courseData['instructor_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $stats = $course->getCourseStatistics($courseId);
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'get_enrolled_students':
        $courseId = intval($_GET['course_id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $courseData = $course->getCourseById($courseId);
        if (!$courseData) {
            echo json_encode(['success' => false, 'error' => 'Course not found']);
            exit;
        }
        
        // Check permissions
        if ($userRole === 'instructor' && $courseData['instructor_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $students = $course->getEnrolledStudents($courseId);
        echo json_encode(['success' => true, 'data' => $students]);
        break;
        
    case 'get_course_lessons':
        $courseId = intval($_GET['course_id'] ?? 0);
        $studentId = $_GET['student_id'] ?? null;
        
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $courseData = $course->getCourseById($courseId);
        if (!$courseData) {
            echo json_encode(['success' => false, 'error' => 'Course not found']);
            exit;
        }
        
        // Check permissions for instructors
        if ($userRole === 'instructor' && $courseData['instructor_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Students can only view their own enrolled courses
        if ($userRole === 'student') {
            $enrollment = $course->getEnrollment($userId, $courseId);
            if (!$enrollment) {
                echo json_encode(['success' => false, 'error' => 'Not enrolled in this course']);
                exit;
            }
            $studentId = $userId;
        }
        
        $lessons = $course->getCourseLessons($courseId, $studentId);
        echo json_encode(['success' => true, 'data' => $lessons]);
        break;
        
    case 'mark_lesson_complete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        if ($userRole !== 'student') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $lessonId = intval($input['lesson_id'] ?? 0);
        
        if ($lessonId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid lesson ID']);
            exit;
        }
        
        // Get lesson to verify course enrollment
        $lesson = $course->getLessonById($lessonId);
        if (!$lesson) {
            echo json_encode(['success' => false, 'error' => 'Lesson not found']);
            exit;
        }
        
        $enrollment = $course->getEnrollment($userId, $lesson['course_id']);
        if (!$enrollment) {
            echo json_encode(['success' => false, 'error' => 'Not enrolled in this course']);
            exit;
        }
        
        $result = $course->markLessonComplete($userId, $lessonId);
        if ($result) {
            // Update course progress
            $course->updateCourseProgress($userId, $lesson['course_id']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to mark lesson complete']);
        }
        break;
        
    case 'update_progress':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        if ($userRole !== 'student') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = intval($input['course_id'] ?? 0);
        $progress = floatval($input['progress'] ?? 0);
        
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $enrollment = $course->getEnrollment($userId, $courseId);
        if (!$enrollment) {
            echo json_encode(['success' => false, 'error' => 'Not enrolled in this course']);
            exit;
        }
        
        $result = $course->updateProgress($userId, $courseId, $progress);
        echo json_encode(['success' => $result]);
        break;
        
    case 'enroll_student':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        if ($userRole !== 'student') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = intval($input['course_id'] ?? 0);
        
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
            exit;
        }
        
        $result = $course->enrollStudent($userId, $courseId);
        echo json_encode($result);
        break;
        
    case 'get_enrolled_courses':
        if ($userRole !== 'student') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $courses = $course->getEnrolledCourses($userId);
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
        
    case 'search_courses':
        $query = $_GET['query'] ?? '';
        $category = intval($_GET['category'] ?? 0);
        $difficulty = $_GET['difficulty'] ?? '';
        $limit = intval($_GET['limit'] ?? 20);
        
        if (empty($query)) {
            echo json_encode(['success' => false, 'error' => 'Search query required']);
            exit;
        }
        
        $courses = $course->searchCourses($query, $category ?: null, $difficulty ?: null, $limit);
        echo json_encode(['success' => true, 'data' => $courses]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Action not found']);
        break;
}
?>
