<?php
/**
 * Course API - Instructor Course Management
 * Handles all AJAX requests for course CRUD operations
 * 
 * JSON Response Format:
 * {
 *     status: "success" | "error",
 *     message: "Proper message",
 *     data: optional
 * }
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Instructor.php';
require_once '../models/Course.php';

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Response helper function
function jsonResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF Token validation
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    jsonResponse('error', 'Unauthorized access. Please login first.');
}

$instructorId = $_SESSION['user_id'];
$instructor = new Instructor();
$course = new Course();

// Get request method
$requestMethod = $_SERVER['REQUEST_METHOD'];
$action = '';

// Handle different request methods
if ($requestMethod === 'GET') {
    $action = $_GET['action'] ?? '';
} elseif ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';
}

// Route the request
switch ($action) {
    case 'create':
        handleCreateCourse();
        break;
        
    case 'update':
        handleUpdateCourse();
        break;
        
    case 'delete':
        handleDeleteCourse();
        break;
        
    case 'toggle_status':
        handleToggleStatus();
        break;
        
    case 'get_course':
        handleGetCourse();
        break;
        
    case 'get_courses':
        handleGetCourses();
        break;
        
    case 'get_categories':
        handleGetCategories();
        break;
        
    case 'get_stats':
        handleGetStats();
        break;
        
    default:
        jsonResponse('error', 'Invalid action specified');
}

/**
 * Handle Create Course
 */
function handleCreateCourse() {
    global $instructor, $instructorId;
    
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        jsonResponse('error', 'Invalid security token. Please refresh the page.');
    }
    
    // Validate required fields
    $requiredFields = ['title', 'description', 'category_id', 'price', 'duration_hours', 'difficulty_level'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    // Sanitize and validate input
    $data = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'category_id' => intval($_POST['category_id']),
        'price' => floatval($_POST['price']),
        'duration_hours' => intval($_POST['duration_hours']),
        'difficulty_level' => sanitize($_POST['difficulty_level']),
        'status' => sanitize($_POST['status'] ?? 'draft'),
        'thumbnail' => sanitize($_POST['thumbnail'] ?? '')
    ];
    
    // Additional validation
    if (strlen($data['title']) < 3) {
        jsonResponse('error', 'Course title must be at least 3 characters');
    }
    
    if (strlen($data['description']) < 10) {
        jsonResponse('error', 'Course description must be at least 10 characters');
    }
    
    if ($data['price'] < 0) {
        jsonResponse('error', 'Price cannot be negative');
    }
    
    if ($data['duration_hours'] < 1) {
        jsonResponse('error', 'Duration must be at least 1 hour');
    }
    
    // Create the course
    $result = $instructor->createInstructorCourse($instructorId, $data);
    
    if ($result['success']) {
        // Log activity
        logActivity($_SESSION['user_id'], 'course_created', "Created course: {$data['title']}");
        
        jsonResponse('success', 'Course created successfully!', [
            'course_id' => $result['course_id'],
            'title' => $data['title']
        ]);
    } else {
        jsonResponse('error', 'Failed to create course: ' . $result['error']);
    }
}

/**
 * Handle Update Course
 */
function handleUpdateCourse() {
    global $instructor, $instructorId;
    
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        jsonResponse('error', 'Invalid security token. Please refresh the page.');
    }
    
    // Validate course ID
    $courseId = intval($_POST['course_id'] ?? 0);
    if ($courseId <= 0) {
        jsonResponse('error', 'Invalid course ID');
    }
    
    // Validate required fields
    $requiredFields = ['title', 'description', 'category_id', 'price', 'duration_hours', 'difficulty_level', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    // Sanitize and validate input
    $data = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'category_id' => intval($_POST['category_id']),
        'price' => floatval($_POST['price']),
        'duration_hours' => intval($_POST['duration_hours']),
        'difficulty_level' => sanitize($_POST['difficulty_level']),
        'status' => sanitize($_POST['status']),
        'thumbnail' => sanitize($_POST['thumbnail'] ?? '')
    ];
    
    // Additional validation
    if (strlen($data['title']) < 3) {
        jsonResponse('error', 'Course title must be at least 3 characters');
    }
    
    if (strlen($data['description']) < 10) {
        jsonResponse('error', 'Course description must be at least 10 characters');
    }
    
    if ($data['price'] < 0) {
        jsonResponse('error', 'Price cannot be negative');
    }
    
    if ($data['duration_hours'] < 1) {
        jsonResponse('error', 'Duration must be at least 1 hour');
    }
    
    // Verify course belongs to instructor
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, instructor_id FROM courses_new WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows === 0) {
        $conn->close();
        jsonResponse('error', 'Course not found');
    }
    
    $course = $result_check->fetch_assoc();
    if ($course['instructor_id'] != $instructorId) {
        $conn->close();
        jsonResponse('error', 'You do not have permission to update this course');
    }
    $conn->close();
    
    // Update the course
    $result = $instructor->updateInstructorCourse($instructorId, $courseId, $data);
    
    if ($result['success']) {
        // Log activity
        logActivity($_SESSION['user_id'], 'course_updated', "Updated course ID: $courseId");
        
        jsonResponse('success', 'Course updated successfully!', [
            'course_id' => $courseId,
            'title' => $data['title']
        ]);
    } else {
        jsonResponse('error', 'Failed to update course: ' . $result['error']);
    }
}

/**
 * Handle Delete Course
 */
function handleDeleteCourse() {
    global $instructor, $instructorId;
    
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        jsonResponse('error', 'Invalid security token. Please refresh the page.');
    }
    
    // Validate course ID
    $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
    if ($courseId <= 0) {
        jsonResponse('error', 'Invalid course ID');
    }
    
    // Verify course belongs to instructor
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, instructor_id, title FROM courses_new WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows === 0) {
        $conn->close();
        jsonResponse('error', 'Course not found');
    }
    
    $course = $result_check->fetch_assoc();
    if ($course['instructor_id'] != $instructorId) {
        $conn->close();
        jsonResponse('error', 'You do not have permission to delete this course');
    }
    $conn->close();
    
    // Delete the course
    $result = $instructor->deleteInstructorCourse($instructorId, $courseId);
    
    if ($result['success']) {
        // Log activity
        logActivity($_SESSION['user_id'], 'course_deleted', "Deleted course: {$course['title']}");
        
        jsonResponse('success', 'Course deleted successfully!', [
            'course_id' => $courseId
        ]);
    } else {
        jsonResponse('error', 'Failed to delete course: ' . $result['error']);
    }
}

/**
 * Handle Toggle Publish/Unpublish Status
 */
function handleToggleStatus() {
    global $instructor, $instructorId;
    
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        jsonResponse('error', 'Invalid security token. Please refresh the page.');
    }
    
    // Validate course ID
    $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
    if ($courseId <= 0) {
        jsonResponse('error', 'Invalid course ID');
    }
    
    // Get new status
    $newStatus = sanitize($_POST['status'] ?? $_GET['status'] ?? '');
    if (!in_array($newStatus, ['published', 'draft', 'archived'])) {
        jsonResponse('error', 'Invalid status');
    }
    
    // Verify course belongs to instructor
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, instructor_id, title, status FROM courses_new WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows === 0) {
        $conn->close();
        jsonResponse('error', 'Course not found');
    }
    
    $course = $result_check->fetch_assoc();
    if ($course['instructor_id'] != $instructorId) {
        $conn->close();
        jsonResponse('error', 'You do not have permission to update this course');
    }
    $conn->close();
    
    // Update status
    $data = [
        'status' => $newStatus
    ];
    
    $result = $instructor->updateInstructorCourse($instructorId, $courseId, $data);
    
    if ($result['success']) {
        $action = $newStatus === 'published' ? 'published' : 'unpublished';
        logActivity($_SESSION['user_id'], "course_$action", "Course {$action}: {$course['title']}");
        
        jsonResponse('success', "Course {$action} successfully!", [
            'course_id' => $courseId,
            'status' => $newStatus
        ]);
    } else {
        jsonResponse('error', 'Failed to update course status: ' . $result['error']);
    }
}

/**
 * Handle Get Single Course
 */
function handleGetCourse() {
    global $instructorId;
    
    $courseId = intval($_GET['course_id'] ?? 0);
    if ($courseId <= 0) {
        jsonResponse('error', 'Invalid course ID');
    }
    
    $conn = connectDB();
    
    // Get course with category name
    $stmt = $conn->prepare("
        SELECT c.*, cat.name as category_name,
               (SELECT COUNT(*) FROM enrollments_new WHERE course_id = c.id) as enrollment_count,
               (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count
        FROM courses_new c
        LEFT JOIN categories_new cat ON c.category_id = cat.id
        WHERE c.id = ? AND c.instructor_id = ?
    ");
    $stmt->bind_param("ii", $courseId, $instructorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        jsonResponse('error', 'Course not found');
    }
    
    $course = $result->fetch_assoc();
    $conn->close();
    
    jsonResponse('success', 'Course retrieved successfully', ['course' => $course]);
}

/**
 * Handle Get Multiple Courses
 */
function handleGetCourses() {
    global $instructor, $instructorId;
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(20, intval($_GET['limit'] ?? 20));
    $offset = ($page - 1) * $limit;
    
    $search = sanitize($_GET['search'] ?? '');
    $status = sanitize($_GET['status'] ?? '');
    
    $courses = $instructor->getInstructorCourses($instructorId, $status, $limit, $offset);
    
    // Filter by search if provided
    if ($search) {
        $courses = array_filter($courses, function($c) use ($search) {
            return stripos($c['title'], $search) !== false;
        });
    }
    
    // Get total count
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses_new WHERE instructor_id = ?");
    $stmt->bind_param("i", $instructorId);
    $stmt->execute();
    $totalCourses = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalCourses / $limit);
    $conn->close();
    
    jsonResponse('success', 'Courses retrieved successfully', [
        'courses' => $courses,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCourses,
            'total_pages' => $totalPages
        ]
    ]);
}

/**
 * Handle Get Categories
 */
function handleGetCategories() {
    $conn = connectDB();
    $result = $conn->query("SELECT id, name FROM categories_new ORDER BY name");
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    
    jsonResponse('success', 'Categories retrieved successfully', ['categories' => $categories]);
}

/**
 * Handle Get Instructor Stats
 */
function handleGetStats() {
    global $instructor, $instructorId;
    
    $stats = $instructor->getInstructorAnalytics($instructorId);
    
    jsonResponse('success', 'Stats retrieved successfully', ['stats' => $stats]);
}
