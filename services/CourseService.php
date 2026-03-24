<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Database.php';

class CourseService {
    private $course;
    private $cache = [];
    private $cacheExpiry = 3600; // 1 hour

    public function __construct() {
        $this->course = new Course();
    }

    /**
     * Unified course retrieval with dynamic filtering
     */
    public function getCourses(array $filters = [], int $limit = 20, int $offset = 0): array {
        // Default filters
        $defaultFilters = [
            'search' => null,
            'category' => null,
            'difficulty' => null,
            'status' => 'published',
            'visibility' => 'public',
            'deleted_at' => null,
            'approved' => true
        ];
        
        $filters = array_merge($defaultFilters, $filters);
        
        // Build cache key
        $cacheKey = 'courses_' . md5(serialize($filters) . $limit . $offset);
        
        // Check cache first
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        // Build dynamic query
        $courses = $this->buildDynamicQuery($filters, $limit, $offset);
        
        // Cache results
        $this->setCache($cacheKey, $courses);
        
        return $courses;
    }

    /**
     * Count courses with filters
     */
    public function countCourses(array $filters = []): int {
        $defaultFilters = [
            'search' => null,
            'category' => null,
            'difficulty' => null,
            'status' => 'published',
            'visibility' => 'public',
            'deleted_at' => null,
            'approved' => true
        ];
        
        $filters = array_merge($defaultFilters, $filters);
        
        // Use the model's count method
        return $this->course->countAdminCourses($filters);
    }

    /**
     * Get categories with caching
     */
    public function getCategories(): array {
        $cacheKey = 'categories_all';
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $conn = connectDB();
        $result = $conn->query("SELECT * FROM categories_new WHERE deleted_at IS NULL ORDER BY name");
        $categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $conn->close();
        
        $this->setCache($cacheKey, $categories);
        return $categories;
    }

    /**
     * Search courses with FULLTEXT index
     */
    public function searchCourses(string $query, array $filters = [], int $limit = 20, int $offset = 0): array {
        $filters['search'] = $query;
        return $this->getCourses($filters, $limit, $offset);
    }

    /**
     * Get course by ID with validation
     */
    public function getCourseById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        
        $cacheKey = "course_{$id}";
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $course = $this->course->getCourseById($id);
        
        if ($course) {
            // Validate course status
            if ($course['status'] !== 'published' || $course['deleted_at'] !== null) {
                return null;
            }
            
            $this->setCache($cacheKey, $course);
        }
        
        return $course;
    }

    /**
     * Check if user is already enrolled
     */
    public function isUserEnrolled(int $userId, int $courseId): bool {
        if ($userId <= 0 || $courseId <= 0) {
            return false;
        }
        
        $cacheKey = "enrollment_{$userId}_{$courseId}";
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $enrollment = $this->course->getEnrollment($userId, $courseId);
        $isEnrolled = !empty($enrollment);
        
        $this->setCache($cacheKey, $isEnrolled, 300); // 5 minutes cache
        return $isEnrolled;
    }

    /**
     * Enroll user with integrity checks
     */
    public function enrollUser(int $userId, int $courseId, string $paymentMethod = 'trial'): array {
        // Validate inputs
        if ($userId <= 0 || $courseId <= 0) {
            return ['success' => false, 'error' => 'Invalid user or course ID'];
        }
        
        // Check if course exists and is enrollable
        $course = $this->getCourseById($courseId);
        if (!$course) {
            return ['success' => false, 'error' => 'Course not found or not available'];
        }
        
        // Check if already enrolled
        if ($this->isUserEnrolled($userId, $courseId)) {
            return ['success' => false, 'error' => 'Already enrolled'];
        }
        
        // Check course capacity
        if ($course['max_students'] > 0) {
            $currentEnrollments = $this->course->getCourseStatistics($courseId)['total_enrollments'] ?? 0;
            if ($currentEnrollments >= $course['max_students']) {
                return ['success' => false, 'error' => 'Course is full'];
            }
        }
        
        // Perform enrollment
        $result = $this->course->enrollStudent($userId, $courseId);
        
        if ($result['success']) {
            // Clear relevant caches
            $this->clearCachePattern("enrollment_{$userId}_{$courseId}");
            $this->clearCachePattern("course_{$courseId}");
            $this->clearCachePattern("courses_");
        }
        
        return $result;
    }

    /**
     * Build dynamic SQL query
     */
    private function buildDynamicQuery(array $filters, int $limit, int $offset): array {
        // Use the model's admin courses method with proper filters
        return $this->course->getAdminCourses($filters, $limit, $offset);
    }

    /**
     * Cache management
     */
    private function isCached(string $key): bool {
        return isset($this->cache[$key]) && 
               (time() - $this->cache[$key]['timestamp']) < $this->cacheExpiry;
    }

    private function getCache(string $key) {
        return $this->cache[$key]['data'] ?? null;
    }

    private function setCache(string $key, $data, int $expiry = null): void {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time(),
            'expiry' => $expiry ?? $this->cacheExpiry
        ];
    }

    private function clearCachePattern(string $pattern): void {
        foreach ($this->cache as $key => $value) {
            if (strpos($key, $pattern) !== false) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Get popular courses with caching
     */
    public function getPopularCourses(int $limit = 10): array {
        $cacheKey = 'popular_courses_' . $limit;
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $courses = $this->course->getPopularCourses($limit);
        $this->setCache($cacheKey, $courses, 1800); // 30 minutes cache
        
        return $courses;
    }

    /**
     * Get recommended courses for user
     */
    public function getRecommendedCourses(int $userId, int $limit = 5): array {
        $cacheKey = "recommended_{$userId}_{$limit}";
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $courses = $this->course->getRecommendedCourses($userId, $limit);
        $this->setCache($cacheKey, $courses, 3600); // 1 hour cache
        
        return $courses;
    }

    /**
     * Validate enrollment prerequisites
     */
    public function validatePrerequisites(int $userId, int $courseId): array {
        $course = $this->getCourseById($courseId);
        if (!$course || empty($course['prerequisites'])) {
            return ['valid' => true, 'missing' => []];
        }
        
        $prerequisites = json_decode($course['prerequisites'], true) ?: [];
        $missing = [];
        
        foreach ($prerequisites as $prereqId) {
            if (!$this->isUserEnrolled($userId, $prereqId)) {
                $prereqCourse = $this->getCourseById($prereqId);
                $missing[] = $prereqCourse['title'] ?? "Course {$prereqId}";
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * Get course statistics
     */
    public function getCourseStats(int $courseId): array {
        $cacheKey = "stats_{$courseId}";
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $stats = $this->course->getCourseStatistics($courseId);
        $this->setCache($cacheKey, $stats, 600); // 10 minutes cache
        
        return $stats;
    }

    /**
     * Get enrolled courses for a student
     */
    public function getEnrolledCourses(int $studentId): array {
        $cacheKey = "enrolled_courses_{$studentId}";
        
        if ($this->isCached($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        
        $enrolledCourses = $this->course->getEnrolledCourses($studentId);
        $this->setCache($cacheKey, $enrolledCourses, 300); // 5 minutes cache
        
        return $enrolledCourses;
    }
}
?>
