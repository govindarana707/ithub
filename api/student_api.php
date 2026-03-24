<?php
/**
 * Student API - Unified AJAX API for Student Portal
 * Handles all student-related operations with JSON responses
 * 
 * Actions:
 * - get_courses (list all available courses)
 * - get_course_details (get single course info)
 * - enroll_course (enroll in a course)
 * - get_my_courses (get enrolled courses)
 * - get_course_content (get lessons for a course)
 * - get_lesson_content (get single lesson details)
 * - mark_lesson_complete (mark lesson as completed)
 * - get_assignments (get assignments for a lesson)
 * - submit_assignment (submit assignment work)
 * - get_submissions (get student submissions)
 * - get_progress (get course progress)
 * - search_courses (search courses)
 * - get_categories (get course categories)
 * - get_notifications (get student notifications)
 * - mark_notification_read (mark notification as read)
 * - get_dashboard_stats (get dashboard statistics)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/functions.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Response helper function
function sendResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Validate authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    sendResponse('error', 'Authentication required. Please login.', null, 401);
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'student';

// Only allow students to access this API
if ($userRole !== 'student') {
    sendResponse('error', 'Access denied. Students only.', null, 403);
}

$database = new Database();
$conn = $database->getConnection();
$courseModel = new Course();
$userModel = new User();

// Route actions
switch ($action) {
    // ==================== COURSE BROWSING ====================
    
    case 'get_courses':
        /**
         * Get all available courses with optional filtering
         * GET params: category_id, difficulty, search, page, limit
         */
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
        $difficulty = isset($_GET['difficulty']) ? sanitize($_GET['difficulty']) : null;
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 12;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT c.*, u.full_name as instructor_name, u.profile_image as instructor_image,
                       cat.name as category_name,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.status = 'published'";
        $countSql = "SELECT COUNT(*) as total FROM courses c WHERE c.status = 'published'";
        $params = [];
        $types = '';
        
        if ($categoryId) {
            $sql .= " AND c.category_id = ?";
            $countSql .= " AND c.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        if ($difficulty) {
            $sql .= " AND c.difficulty_level = ?";
            $countSql .= " AND c.difficulty_level = ?";
            $params[] = $difficulty;
            $types .= 's';
        }
        
        if ($search) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $countSql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'ss';
        }
        
        // Get total count
        $stmt = $conn->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $totalCourses = $totalResult['total'];
        
        // Add pagination
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Check enrollment status for each course
        foreach ($courses as &$course) {
            $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->bind_param("ii", $userId, $course['id']);
            $stmt->execute();
            $course['is_enrolled'] = $stmt->get_result()->num_rows > 0;
            $course['thumbnail'] = $course['thumbnail'] ?: 'https://via.placeholder.com/300x200?text=Course';
        }
        
        sendResponse('success', 'Courses retrieved successfully', [
            'courses' => $courses,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCourses / $limit),
                'total_items' => $totalCourses,
                'items_per_page' => $limit
            ]
        ]);
        break;
    
    case 'get_course_details':
        /**
         * Get detailed information about a single course
         * GET params: course_id
         */
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        
        if (!$courseId) {
            sendResponse('error', 'Course ID is required', null, 400);
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name as instructor_name, u.profile_image as instructor_image, u.bio as instructor_bio,
                   cat.name as category_name
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ? AND c.status = 'published'
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course) {
            sendResponse('error', 'Course not found', null, 404);
        }
        
        // Get enrollment status
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $enrollment = $stmt->get_result()->fetch_assoc();
        $course['is_enrolled'] = !empty($enrollment);
        $course['enrollment_data'] = $enrollment;
        
        // Get lesson count
        $stmt = $conn->prepare("SELECT COUNT(*) as lesson_count FROM lessons WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course['lesson_count'] = $stmt->get_result()->fetch_assoc()['lesson_count'];
        
        // Get enrollment count
        $stmt = $conn->prepare("SELECT COUNT(*) as enrollment_count FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course['enrollment_count'] = $stmt->get_result()->fetch_assoc()['enrollment_count'];
        
        // Get average rating
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM feedback WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course['avg_rating'] = round($stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0, 1);
        
        $course['thumbnail'] = $course['thumbnail'] ?: 'https://via.placeholder.com/800x400?text=Course';
        
        sendResponse('success', 'Course details retrieved successfully', $course);
        break;
    
    case 'search_courses':
        /**
         * Search courses by title or description
         * GET params: query
         */
        $query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
        
        if (strlen($query) < 2) {
            sendResponse('error', 'Search query must be at least 2 characters', null, 400);
        }
        
        $searchParam = "%{$query}%";
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name as instructor_name, cat.name as category_name,
                   (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.status = 'published' AND (c.title LIKE ? OR c.description LIKE ?)
            ORDER BY c.title ASC
            LIMIT 20
        ");
        $stmt->bind_param("ss", $searchParam, $searchParam);
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($courses as &$course) {
            $course['thumbnail'] = $course['thumbnail'] ?: 'https://via.placeholder.com/300x200?text=Course';
        }
        
        sendResponse('success', 'Search completed', ['courses' => $courses, 'query' => $query]);
        break;
    
    case 'get_categories':
        /**
         * Get all course categories
         */
        $stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $stmt->fetch_all(MYSQLI_ASSOC);
        sendResponse('success', 'Categories retrieved successfully', $categories);
        break;
    
    // ==================== COURSE ENROLLMENT ====================
    
    case 'enroll_course':
        /**
         * Enroll in a course (AJAX)
         * POST params: course_id, payment_method (optional)
         */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse('error', 'Method not allowed', null, 405);
        }
        
        $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $paymentMethod = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : 'free';
        
        if (!$courseId) {
            sendResponse('error', 'Course ID is required', null, 400);
        }
        
        // Verify course exists and is published
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        if (!$course) {
            sendResponse('error', 'Course not found or not available', null, 404);
        }
        
        // Check if already enrolled
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse('error', 'You are already enrolled in this course', null, 409);
        }
        
        // Handle different payment methods
        if ($course['price'] > 0 && $paymentMethod === 'free') {
            sendResponse('error', 'This course requires payment', null, 400);
        }
        
        // Create enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, course_id, status, progress_percentage, enrolled_at)
            VALUES (?, ?, 'active', 0, NOW())
        ");
        $stmt->bind_param("ii", $userId, $courseId);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity($userId, 'course_enrolled', "Enrolled in course: {$course['title']} (ID: $courseId)");
            
            // Create notification
            $notificationTitle = 'Course Enrolled';
            $notificationMessage = "You have successfully enrolled in '{$course['title']}'. Start learning now!";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type)
                VALUES (?, ?, ?, 'success')
            ");
            $stmt->bind_param("iss", $userId, $notificationTitle, $notificationMessage);
            $stmt->execute();
            
            sendResponse('success', 'Successfully enrolled in the course!', [
                'course_id' => $courseId,
                'course_title' => $course['title'],
                'enrolled_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            sendResponse('error', 'Enrollment failed. Please try again.', null, 500);
        }
        break;
    
    // ==================== MY COURSES ====================
    
    case 'get_my_courses':
        /**
         * Get all enrolled courses for the current student
         * GET params: status (active/completed), page, limit
         */
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 12;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT c.*, u.full_name as instructor_name, e.enrolled_at, e.progress_percentage,
                       e.status as enrollment_status, e.completed_at,
                       cat.name as category_name
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN users u ON c.instructor_id = u.id
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE e.student_id = ?";
        $countSql = "SELECT COUNT(*) as total FROM enrollments WHERE student_id = ?";
        $params = [$userId];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND e.status = ?";
            $countSql .= " AND status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        // Get total count
        $stmt = $conn->prepare($countSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $totalCourses = $totalResult['total'];
        
        // Add pagination
        $sql .= " ORDER BY e.enrolled_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get additional info for each course
        foreach ($courses as &$course) {
            // Get lesson count and completed lessons
            $stmt = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM lessons WHERE course_id = ?) as total_lessons,
                    (SELECT COUNT(*) FROM lesson_progress lp 
                     JOIN lessons l ON lp.lesson_id = l.id 
                     WHERE l.course_id = ? AND lp.student_id = ? AND lp.completed = 1) as completed_lessons
            ");
            $stmt->bind_param("iii", $course['id'], $course['id'], $userId);
            $stmt->execute();
            $lessonInfo = $stmt->get_result()->fetch_assoc();
            $course['total_lessons'] = $lessonInfo['total_lessons'];
            $course['completed_lessons'] = $lessonInfo['completed_lessons'];
            
            // Get last accessed lesson
            $stmt = $conn->prepare("
                SELECT l.title, lp.last_accessed_at
                FROM lesson_progress lp
                JOIN lessons l ON lp.lesson_id = l.id
                WHERE l.course_id = ? AND lp.student_id = ?
                ORDER BY lp.last_accessed_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("ii", $course['id'], $userId);
            $stmt->execute();
            $lastLesson = $stmt->get_result()->fetch_assoc();
            $course['last_lesson'] = $lastLesson['title'] ?? null;
            $course['last_accessed'] = $lastLesson['last_accessed_at'] ?? $course['enrolled_at'];
            
            $course['thumbnail'] = $course['thumbnail'] ?: 'https://via.placeholder.com/300x200?text=Course';
            
            // Calculate estimated time remaining
            $remainingLessons = $course['total_lessons'] - $course['completed_lessons'];
            $avgTimePerLesson = 15; // minutes
            $course['estimated_minutes_remaining'] = $remainingLessons * $avgTimePerLesson;
        }
        
        sendResponse('success', 'Enrolled courses retrieved successfully', [
            'courses' => $courses,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCourses / $limit),
                'total_items' => $totalCourses,
                'items_per_page' => $limit
            ]
        ]);
        break;
    
    // ==================== COURSE CONTENT ====================
    
    case 'get_course_content':
        /**
         * Get all lessons and content for a course
         * GET params: course_id
         */
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        
        if (!$courseId) {
            sendResponse('error', 'Course ID is required', null, 400);
        }
        
        // Verify enrollment
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Get course info
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name as instructor_name
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        
        // Get lessons with progress
        $stmt = $conn->prepare("
            SELECT l.*,
                   (SELECT is_completed FROM lesson_progress WHERE lesson_id = l.id AND student_id = ? LIMIT 1) as is_completed,
                   (SELECT completion_time FROM lesson_progress WHERE lesson_id = l.id AND student_id = ? LIMIT 1) as completed_at,
                   (SELECT time_spent_minutes FROM lesson_progress WHERE lesson_id = l.id AND student_id = ? LIMIT 1) as time_spent
            FROM lessons l
            WHERE l.course_id = ?
            ORDER BY l.lesson_order ASC, l.id ASC
        ");
        $stmt->bind_param("iiii", $userId, $userId, $userId, $courseId);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate progress
        $totalLessons = count($lessons);
        $completedLessons = count(array_filter($lessons, function($l) { return $l['is_completed']; }));
        $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;
        
        sendResponse('success', 'Course content retrieved successfully', [
            'course' => $course,
            'lessons' => $lessons,
            'progress' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'percentage' => $progressPercentage
            ]
        ]);
        break;
    
    case 'get_lesson_content':
        /**
         * Get detailed content for a single lesson
         * GET params: lesson_id
         */
        $lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
        
        if (!$lessonId) {
            sendResponse('error', 'Lesson ID is required', null, 400);
        }
        
        // Get lesson info and verify enrollment
        $stmt = $conn->prepare("
            SELECT l.*, c.id as course_id, c.title as course_title
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            WHERE l.id = ?
        ");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson) {
            sendResponse('error', 'Lesson not found', null, 404);
        }
        
        // Verify enrollment
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $lesson['course_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Get lesson progress
        $stmt = $conn->prepare("SELECT * FROM lesson_progress WHERE lesson_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $lessonId, $userId);
        $stmt->execute();
        $progress = $stmt->get_result()->fetch_assoc();
        $lesson['progress'] = $progress;
        
        // Get lesson resources
        $stmt = $conn->prepare("SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY sort_order ASC");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $lesson['resources'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get lesson notes
        $stmt = $conn->prepare("SELECT * FROM lesson_notes WHERE lesson_id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $lesson['notes'] = $stmt->get_result()->fetch_assoc();
        
        // Get assignments for this lesson
        $stmt = $conn->prepare("SELECT * FROM lesson_assignments WHERE lesson_id = ? AND is_published = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $lesson['assignments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get submissions for these assignments
        foreach ($lesson['assignments'] as &$assignment) {
            $stmt = $conn->prepare("
                SELECT * FROM assignment_submissions 
                WHERE assignment_id = ? AND student_id = ? 
                ORDER BY submitted_at DESC LIMIT 1
            ");
            $stmt->bind_param("ii", $assignment['id'], $userId);
            $stmt->execute();
            $assignment['submission'] = $stmt->get_result()->fetch_assoc();
        }
        
        // Get next and previous lessons
        $stmt = $conn->prepare("
            SELECT id, title FROM lessons 
            WHERE course_id = ? AND lesson_order < ? 
            ORDER BY lesson_order DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $lesson['course_id'], $lesson['lesson_order']);
        $stmt->execute();
        $prevLesson = $stmt->get_result()->fetch_assoc();
        $lesson['previous_lesson'] = $prevLesson;
        
        $stmt = $conn->prepare("
            SELECT id, title FROM lessons 
            WHERE course_id = ? AND lesson_order > ? 
            ORDER BY lesson_order ASC LIMIT 1
        ");
        $stmt->bind_param("ii", $lesson['course_id'], $lesson['lesson_order']);
        $stmt->execute();
        $nextLesson = $stmt->get_result()->fetch_assoc();
        $lesson['next_lesson'] = $nextLesson;
        
        // Update last accessed time
        $stmt = $conn->prepare("
            INSERT INTO lesson_progress (lesson_id, student_id, last_accessed_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_accessed_at = NOW()
        ");
        $stmt->bind_param("ii", $lessonId, $userId);
        $stmt->execute();
        
        sendResponse('success', 'Lesson content retrieved successfully', $lesson);
        break;
    
    case 'mark_lesson_complete':
        /**
         * Mark a lesson as completed
         * POST params: lesson_id
         */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse('error', 'Method not allowed', null, 405);
        }
        
        $lessonId = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lessonId) {
            sendResponse('error', 'Lesson ID is required', null, 400);
        }
        
        // Get lesson and verify enrollment
        $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson) {
            sendResponse('error', 'Lesson not found', null, 404);
        }
        
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $lesson['course_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Update or insert progress
        $stmt = $conn->prepare("
            INSERT INTO lesson_progress (lesson_id, student_id, is_completed, completion_time)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE is_completed = 1, completion_time = NOW()
        ");
        $stmt->bind_param("ii", $lessonId, $userId);
        $stmt->execute();
        
        // Update course progress
        updateCourseProgress($conn, $userId, $lesson['course_id']);
        
        sendResponse('success', 'Lesson marked as completed!', [
            'lesson_id' => $lessonId,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
        break;
    
    // ==================== ASSIGNMENTS ====================
    
    case 'get_assignments':
        /**
         * Get assignments for a lesson
         * GET params: lesson_id
         */
        $lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
        
        if (!$lessonId) {
            sendResponse('error', 'Lesson ID is required', null, 400);
        }
        
        // Verify enrollment
        $stmt = $conn->prepare("
            SELECT e.* FROM enrollments e
            JOIN lessons l ON e.course_id = l.course_id
            WHERE e.student_id = ? AND l.id = ?
        ");
        $stmt->bind_param("ii", $userId, $lessonId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Get assignments
        $stmt = $conn->prepare("
            SELECT la.*, 
                   (SELECT MAX(submitted_at) FROM assignment_submissions WHERE assignment_id = la.id AND student_id = ?) as last_submitted,
                   (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = la.id AND student_id = ?) as attempt_count
            FROM lesson_assignments la
            WHERE la.lesson_id = ? AND la.is_published = 1
            ORDER BY la.sort_order ASC
        ");
        $stmt->bind_param("iii", $userId, $userId, $lessonId);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get submissions for each assignment
        foreach ($assignments as &$assignment) {
            $stmt = $conn->prepare("
                SELECT * FROM assignment_submissions 
                WHERE assignment_id = ? AND student_id = ?
                ORDER BY submitted_at DESC
            ");
            $stmt->bind_param("ii", $assignment['id'], $userId);
            $stmt->execute();
            $assignment['submissions'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        sendResponse('success', 'Assignments retrieved successfully', $assignments);
        break;
    
    case 'submit_assignment':
        /**
         * Submit an assignment
         * POST params: assignment_id, text_content, file (optional)
         */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse('error', 'Method not allowed', null, 405);
        }
        
        $assignmentId = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $textContent = isset($_POST['text_content']) ? sanitize($_POST['text_content']) : '';
        
        if (!$assignmentId) {
            sendResponse('error', 'Assignment ID is required', null, 400);
        }
        
        // Get assignment and verify enrollment
        $stmt = $conn->prepare("
            SELECT la.*, l.course_id 
            FROM lesson_assignments la
            JOIN lessons l ON la.lesson_id = l.id
            WHERE la.id = ?
        ");
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        
        if (!$assignment) {
            sendResponse('error', 'Assignment not found', null, 404);
        }
        
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $assignment['course_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Check attempt limit
        $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $assignmentId, $userId);
        $stmt->execute();
        $attempts = $stmt->get_result()->fetch_assoc()['attempts'];
        
        if ($assignment['max_attempts'] > 0 && $attempts >= $assignment['max_attempts']) {
            sendResponse('error', 'Maximum attempts reached for this assignment', null, 400);
        }
        
        // Check due date
        $isLate = false;
        if ($assignment['due_date'] && strtotime($assignment['due_date']) < time()) {
            if (!$assignment['allow_late_submission']) {
                sendResponse('error', 'Submission deadline has passed', null, 400);
            }
            $isLate = true;
        }
        
        // Handle file upload if present
        $filePath = null;
        $fileSize = null;
        
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
            $filePath = 'uploads/assignments/' . $fileName;
            $targetPath = __DIR__ . '/../' . $filePath;
            
            // Validate file
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'image/png', 'image/jpeg'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($_FILES['file']['type'], $allowedTypes)) {
                sendResponse('error', 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, PNG, JPG', null, 400);
            }
            
            if ($_FILES['file']['size'] > $maxSize) {
                sendResponse('error', 'File size exceeds 10MB limit', null, 400);
            }
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                sendResponse('error', 'File upload failed', null, 500);
            }
            
            $fileSize = $_FILES['file']['size'];
        }
        
        // Determine submission type
        $submissionType = 'text_submission';
        if ($filePath) {
            $submissionType = 'file_upload';
        }
        
        // Insert submission
        $stmt = $conn->prepare("
            INSERT INTO assignment_submissions 
            (assignment_id, student_id, submission_type, file_path, file_size, text_content, is_late, attempt_number, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
        ");
        $nextAttempt = $attempts + 1;
        $isLateInt = $isLate ? 1 : 0;
        $stmt->bind_param("iissisi", $assignmentId, $userId, $submissionType, $filePath, $fileSize, $textContent, $isLateInt, $nextAttempt);
        
        if ($stmt->execute()) {
            $submissionId = $conn->insert_id;
            
            // Log activity
            logActivity($userId, 'assignment_submitted', "Submitted assignment: {$assignment['title']}");
            
            // Create notification
            $notificationMessage = "Your submission for '{$assignment['title']}' has been received.";
            if ($isLate) {
                $notificationMessage .= " (Submitted late)";
            }
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type)
                VALUES (?, 'Assignment Submitted', ?, 'success')
            ");
            $stmt->bind_param("is", $userId, $notificationMessage);
            $stmt->execute();
            
            sendResponse('success', 'Assignment submitted successfully!', [
                'submission_id' => $submissionId,
                'is_late' => $isLate,
                'attempt_number' => $nextAttempt,
                'submitted_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            sendResponse('error', 'Submission failed. Please try again.', null, 500);
        }
        break;
    
    case 'get_submissions':
        /**
         * Get all submissions for a student
         * GET params: course_id (optional)
         */
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
        
        $sql = "
            SELECT s.*, la.title as assignment_title, la.max_points, la.due_date,
                   l.title as lesson_title, c.title as course_title
            FROM assignment_submissions s
            JOIN lesson_assignments la ON s.assignment_id = la.id
            JOIN lessons l ON la.lesson_id = l.id
            JOIN courses c ON l.course_id = c.id
            WHERE s.student_id = ?
        ";
        $params = [$userId];
        $types = 'i';
        
        if ($courseId) {
            $sql .= " AND c.id = ?";
            $params[] = $courseId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY s.submitted_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        sendResponse('success', 'Submissions retrieved successfully', $submissions);
        break;
    
    // ==================== PROGRESS ====================
    
    case 'get_progress':
        /**
         * Get overall learning progress
         * GET params: course_id (optional)
         */
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
        
        $response = [];
        
        // Overall stats
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM enrollments WHERE student_id = ?) as total_enrolled,
                (SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'completed') as total_completed,
                (SELECT AVG(progress_percentage) FROM enrollments WHERE student_id = ? AND progress_percentage > 0) as avg_progress
        ");
        $stmt->bind_param("iii", $userId, $userId, $userId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $response['stats'] = $stats;
        
        // Course-specific progress
        if ($courseId) {
            $stmt = $conn->prepare("
                SELECT e.progress_percentage, e.status, e.enrolled_at, e.completed_at,
                       (SELECT COUNT(*) FROM lessons WHERE course_id = e.course_id) as total_lessons,
                       (SELECT COUNT(*) FROM lesson_progress lp 
                        JOIN lessons l ON lp.lesson_id = l.id 
                        WHERE l.course_id = e.course_id AND lp.student_id = ? AND lp.completed = 1) as completed_lessons
                FROM enrollments e
                WHERE e.student_id = ? AND e.course_id = ?
            ");
            $stmt->bind_param("iii", $userId, $userId, $courseId);
            $stmt->execute();
            $response['course_progress'] = $stmt->get_result()->fetch_assoc();
        }
        
        // Recent activity
        $stmt = $conn->prepare("
            SELECT lp.*, l.title as lesson_title, c.title as course_title
            FROM lesson_progress lp
            JOIN lessons l ON lp.lesson_id = l.id
            JOIN courses c ON l.course_id = c.id
            WHERE lp.student_id = ?
            ORDER BY lp.updated_at DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $response['recent_activity'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Weekly study time
        $stmt = $conn->prepare("
            SELECT SUM(time_spent_minutes) as total_minutes
            FROM lesson_progress
            WHERE student_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $response['weekly_study_minutes'] = $stmt->get_result()->fetch_assoc()['total_minutes'] ?? 0;
        
        sendResponse('success', 'Progress retrieved successfully', $response);
        break;
    
    // ==================== NOTIFICATIONS ====================
    
    case 'get_notifications':
        /**
         * Get student notifications
         * GET params: unread_only, limit
         */
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] == 'true';
        $limit = isset($_GET['limit']) ? min(50, intval($_GET['limit'])) : 20;
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get unread count
        $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $unreadCount = $stmt->get_result()->fetch_assoc()['unread_count'];
        
        sendResponse('success', 'Notifications retrieved successfully', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        break;
    
    case 'mark_notification_read':
        /**
         * Mark notification(s) as read
         * POST params: notification_id (or 'all' for all)
         */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse('error', 'Method not allowed', null, 405);
        }
        
        $notificationId = isset($_POST['notification_id']) ? sanitize($_POST['notification_id']) : '';
        
        if ($notificationId === 'all') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            sendResponse('success', 'All notifications marked as read');
        } elseif (is_numeric($notificationId)) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notificationId, $userId);
            $stmt->execute();
            sendResponse('success', 'Notification marked as read');
        } else {
            sendResponse('error', 'Invalid notification ID', null, 400);
        }
        break;
    
    // ==================== DASHBOARD ====================
    
    case 'get_dashboard_stats':
        /**
         * Get dashboard statistics
         */
        // Get enrolled courses count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['enrolled_courses'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get completed courses count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['completed_courses'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get in-progress courses count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'active'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['in_progress_courses'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get average progress
        $stmt = $conn->prepare("SELECT AVG(progress_percentage) as avg_progress FROM enrollments WHERE student_id = ? AND progress_percentage > 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['average_progress'] = round($stmt->get_result()->fetch_assoc()['avg_progress'] ?? 0, 1);
        
        // Get total study time (in minutes)
        $stmt = $conn->prepare("SELECT SUM(time_spent_minutes) as total_time FROM lesson_progress WHERE student_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['total_study_minutes'] = $stmt->get_result()->fetch_assoc()['total_time'] ?? 0;
        
        // Get completed lessons count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lesson_progress WHERE student_id = ? AND is_completed = 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['completed_lessons'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get pending assignments
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM lesson_assignments la
            JOIN lessons l ON la.lesson_id = l.id
            JOIN enrollments e ON l.course_id = e.course_id
            LEFT JOIN assignment_submissions s ON la.id = s.assignment_id AND s.student_id = ?
            WHERE e.student_id = ? AND la.due_date >= CURDATE() AND s.id IS NULL
        ");
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $stats['pending_assignments'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get certificates count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE student_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['certificates'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get unread notifications count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['unread_notifications'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get recent enrollments
        $stmt = $conn->prepare("
            SELECT c.title, e.enrolled_at, c.thumbnail
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = ?
            ORDER BY e.enrolled_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['recent_enrollments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        sendResponse('success', 'Dashboard stats retrieved successfully', $stats);
        break;
    
    // ==================== QUIZZES ====================
    
    case 'get_quizzes':
        /**
         * Get quizzes for a course
         * GET params: course_id
         */
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        
        if (!$courseId) {
            sendResponse('error', 'Course ID is required', null, 400);
        }
        
        // Verify enrollment
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse('error', 'You are not enrolled in this course', null, 403);
        }
        
        // Get quizzes with attempts
        $stmt = $conn->prepare("
            SELECT q.*,
                   (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ?) as attempt_count,
                   (SELECT MAX(score) FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ?) as best_score,
                   (SELECT MAX(percentage) FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ?) as best_percentage
            FROM quizzes q
            WHERE q.course_id = ? AND q.status = 'published'
            ORDER BY q.created_at DESC
        ");
        $stmt->bind_param("iiii", $userId, $userId, $userId, $courseId);
        $stmt->execute();
        $quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        sendResponse('success', 'Quizzes retrieved successfully', $quizzes);
        break;
    
    // ==================== DEFAULT ====================
    
    default:
        sendResponse('error', 'Invalid action. Please provide a valid action parameter.', null, 400);
}

// Helper function to update course progress
function updateCourseProgress($conn, $studentId, $courseId) {
    // Get total lessons
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $totalLessons = $stmt->get_result()->fetch_assoc()['total'];
    
    if ($totalLessons === 0) return;
    
    // Get completed lessons
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed 
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE l.course_id = ? AND lp.student_id = ? AND lp.is_completed = 1
    ");
    $stmt->bind_param("ii", $courseId, $studentId);
    $stmt->execute();
    $completedLessons = $stmt->get_result()->fetch_assoc()['completed'];
    
    $progressPercentage = round(($completedLessons / $totalLessons) * 100, 1);
    
    // Determine status
    $status = 'active';
    $completedAt = null;
    
    if ($progressPercentage >= 100) {
        $status = 'completed';
        $completedAt = date('Y-m-d H:i:s');
    }
    
    // Update enrollment
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET progress_percentage = ?, status = ?, completed_at = ?
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->bind_param("dssii", $progressPercentage, $status, $completedAt, $studentId, $courseId);
    $stmt->execute();
}
