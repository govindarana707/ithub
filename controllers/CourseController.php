<?php
require_once __DIR__ . '/../services/CourseService.php';
require_once __DIR__ . '/../config/config.php';

class CourseController {
    private $courseService;
    private $errors = [];

    public function __construct() {
        $this->courseService = new CourseService();
    }

    /**
     * Main courses page handler
     */
    public function index(): array {
        try {
            // Parse and validate input parameters
            $params = $this->parseRequestParams();
            
            // Build filters
            $filters = $this->buildFilters($params);
            
            // Get pagination info
            $pagination = $this->getPaginationInfo($filters, $params['page'], $params['limit']);
            
            // Get courses
            $courses = $this->courseService->getCourses($filters, $params['limit'], $pagination['offset']);
            
            // Get categories for filter
            $categories = $this->courseService->getCategories();
            
            // Get popular courses for sidebar
            $popularCourses = $this->courseService->getPopularCourses(5);
            
            // Get user-specific data if logged in
            $userCourses = [];
            if (isLoggedIn() && getUserRole() === 'student') {
                $userCourses = $this->courseService->getEnrolledCourses($_SESSION['user_id']);
            }
            
            return [
                'success' => true,
                'data' => [
                    'courses' => $courses,
                    'categories' => $categories,
                    'popularCourses' => $popularCourses,
                    'userCourses' => $userCourses,
                    'filters' => $filters,
                    'pagination' => $pagination,
                    'params' => $params
                ]
            ];
            
        } catch (Exception $e) {
            $this->logError('Course index error', $e);
            return [
                'success' => false,
                'error' => 'Failed to load courses',
                'message' => 'Please try again later'
            ];
        }
    }

    /**
     * Course details handler
     */
    public function show(int $courseId): array {
        try {
            if ($courseId <= 0) {
                return ['success' => false, 'error' => 'Invalid course ID'];
            }

            $course = $this->courseService->getCourseById($courseId);
            if (!$course) {
                return ['success' => false, 'error' => 'Course not found'];
            }

            // Get additional course data
            $stats = $this->courseService->getCourseStats($courseId);
            $isEnrolled = false;
            $canEnroll = true;
            $enrollmentRequirements = [];

            // Check enrollment status if user is logged in
            if (isLoggedIn() && getUserRole() === 'student') {
                $userId = $_SESSION['user_id'];
                $isEnrolled = $this->courseService->isUserEnrolled($userId, $courseId);
                
                // Validate prerequisites
                $prereqCheck = $this->courseService->validatePrerequisites($userId, $courseId);
                $canEnroll = $prereqCheck['valid'];
                $enrollmentRequirements = $prereqCheck['missing'];
            }

            return [
                'success' => true,
                'data' => [
                    'course' => $course,
                    'stats' => $stats,
                    'isEnrolled' => $isEnrolled,
                    'canEnroll' => $canEnroll,
                    'enrollmentRequirements' => $enrollmentRequirements
                ]
            ];

        } catch (Exception $e) {
            $this->logError('Course show error', $e);
            return [
                'success' => false,
                'error' => 'Failed to load course details',
                'message' => 'Please try again later'
            ];
        }
    }

    /**
     * Enrollment handler
     */
    public function enroll(int $courseId, string $paymentMethod = 'trial'): array {
        try {
            // Authorization check
            if (!isLoggedIn() || getUserRole() !== 'student') {
                return [
                    'success' => false,
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Only students can enroll in courses'
                ];
            }

            if ($courseId <= 0) {
                return [
                    'success' => false,
                    'error' => 'INVALID_COURSE',
                    'message' => 'Invalid course ID'
                ];
            }

            $userId = $_SESSION['user_id'];
            
            // Perform enrollment with integrity checks
            $result = $this->courseService->enrollUser($userId, $courseId, $paymentMethod);
            
            if ($result['success']) {
                // Log activity
                logActivity($userId, 'enroll_course', "Enrolled in course ID: {$courseId} via {$paymentMethod}");
                
                return [
                    'success' => true,
                    'message' => 'Enrollment successful!',
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'ENROLLMENT_FAILED',
                    'message' => $result['error'] ?? 'Enrollment failed'
                ];
            }

        } catch (Exception $e) {
            $this->logError('Enrollment error', $e);
            return [
                'success' => false,
                'error' => 'SYSTEM_ERROR',
                'message' => 'An unexpected error occurred'
            ];
        }
    }

    /**
     * Search handler
     */
    public function search(string $query, array $filters = []): array {
        try {
            if (empty(trim($query))) {
                return ['success' => false, 'error' => 'Search query is required'];
            }

            $params = $this->parseRequestParams();
            $searchFilters = array_merge($filters, ['search' => $query]);
            
            $pagination = $this->getPaginationInfo($searchFilters, $params['page'], $params['limit']);
            $courses = $this->courseService->searchCourses($query, $searchFilters, $params['limit'], $pagination['offset']);

            return [
                'success' => true,
                'data' => [
                    'courses' => $courses,
                    'query' => $query,
                    'filters' => $searchFilters,
                    'pagination' => $pagination,
                    'total' => $this->courseService->countCourses($searchFilters)
                ]
            ];

        } catch (Exception $e) {
            $this->logError('Search error', $e);
            return [
                'success' => false,
                'error' => 'Search failed',
                'message' => 'Please try again'
            ];
        }
    }

    /**
     * Parse and validate request parameters
     */
    private function parseRequestParams(): array {
        return [
            'search' => sanitize($_GET['search'] ?? ''),
            'category' => intval($_GET['category'] ?? 0),
            'difficulty' => sanitize($_GET['difficulty'] ?? ''),
            'page' => max(1, intval($_GET['page'] ?? 1)),
            'limit' => 12, // Configurable limit
            'sort' => sanitize($_GET['sort'] ?? 'latest'),
            'price_min' => floatval($_GET['price_min'] ?? 0),
            'price_max' => floatval($_GET['price_max'] ?? 0)
        ];
    }

    /**
     * Build filters array
     */
    private function buildFilters(array $params): array {
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

        if ($params['price_min'] > 0) {
            $filters['price_min'] = $params['price_min'];
        }

        if ($params['price_max'] > 0) {
            $filters['price_max'] = $params['price_max'];
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
            case 'rating':
                $filters['sort'] = 'rating DESC';
                break;
            default:
                $filters['sort'] = 'created_at DESC';
        }

        return $filters;
    }

    /**
     * Get pagination information
     */
    private function getPaginationInfo(array $filters, int $page, int $limit): array {
        $total = $this->courseService->countCourses($filters);
        $offset = ($page - 1) * $limit;
        $totalPages = ceil($total / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return [
            'current' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'totalPages' => $totalPages,
            'hasNext' => $hasNext,
            'hasPrev' => $hasPrev,
            'nextPage' => $hasNext ? $page + 1 : null,
            'prevPage' => $hasPrev ? $page - 1 : null
        ];
    }

    /**
     * Generate query string for URL preservation
     */
    public function generateQueryString(array $exclude = []): string {
        $params = $_GET;
        
        // Exclude specified parameters
        foreach ($exclude as $param) {
            unset($params[$param]);
        }
        
        return http_build_query($params);
    }

    /**
     * Error logging
     */
    private function logError(string $message, Exception $e): void {
        error_log("$message: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        
        // Store in errors array for debugging
        $this->errors[] = [
            'message' => $message,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }

    /**
     * Get errors for debugging
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * API endpoint handler
     */
    public function api(string $action, array $data = []): array {
        switch ($action) {
            case 'list':
                return $this->index();
            
            case 'search':
                $query = $data['query'] ?? '';
                return $this->search($query, $data);
            
            case 'details':
                $courseId = intval($data['course_id'] ?? 0);
                return $this->show($courseId);
            
            case 'enroll':
                $courseId = intval($data['course_id'] ?? 0);
                $paymentMethod = $data['payment_method'] ?? 'trial';
                return $this->enroll($courseId, $paymentMethod);
            
            default:
                return [
                    'success' => false,
                    'error' => 'INVALID_ACTION',
                    'message' => 'Unknown API action'
                ];
        }
    }
}
?>
