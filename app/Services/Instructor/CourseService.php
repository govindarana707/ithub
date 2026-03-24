<?php

namespace App\Services\Instructor;

use App\Repositories\CourseRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\UserRepository;
use App\Models\Course;
use App\Models\User;
use Core\Cache\CacheManager;
use Core\Events\EventDispatcher;
use Core\Logging\Logger;

/**
 * Course Service
 * 
 * Handles all course-related business logic
 * following Single Responsibility Principle
 */
class CourseService
{
    private CourseRepository $courseRepository;
    private EnrollmentRepository $enrollmentRepository;
    private UserRepository $userRepository;
    private CacheManager $cache;
    private EventDispatcher $events;
    private Logger $logger;

    public function __construct(
        CourseRepository $courseRepository,
        EnrollmentRepository $enrollmentRepository,
        UserRepository $userRepository,
        CacheManager $cache,
        EventDispatcher $events,
        Logger $logger
    ) {
        $this->courseRepository = $courseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->userRepository = $userRepository;
        $this->cache = $cache;
        $this->events = $events;
        $this->logger = $logger;
    }

    /**
     * Get instructor's courses with pagination and filtering
     */
    public function getInstructorCourses(
        int $instructorId,
        array $filters = [],
        int $page = 1,
        int $limit = 12
    ): array {
        $cacheKey = "instructor_courses_{$instructorId}_" . md5(serialize($filters) . "_{$page}_{$limit}");
        
        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $offset = ($page - 1) * $limit;
            
            // Get courses with optimized query
            $courses = $this->courseRepository->findInstructorCourses(
                $instructorId,
                $filters,
                $limit,
                $offset
            );

            // Get total count for pagination
            $total = $this->courseRepository->countInstructorCourses($instructorId, $filters);

            // Enrich with additional data
            foreach ($courses as &$course) {
                $course['enrollment_count'] = $this->enrollmentRepository->countByCourse($course['id']);
                $course['avg_progress'] = $this->enrollmentRepository->getAverageProgress($course['id']);
                $course['lesson_count'] = $this->courseRepository->countLessons($course['id']);
            }

            $result = [
                'courses' => $courses,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ];

            // Cache for 5 minutes
            $this->cache->set($cacheKey, $result, 300);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get instructor courses", [
                'instructor_id' => $instructorId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to retrieve courses");
        }
    }

    /**
     * Create new course
     */
    public function createCourse(int $instructorId, array $data): Course
    {
        try {
            // Validate instructor exists and is active
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor->getRole() !== 'instructor') {
                throw new \InvalidArgumentException("Invalid instructor");
            }

            // Create course
            $course = new Course();
            $course->setInstructorId($instructorId);
            $course->setTitle($data['title']);
            $course->setDescription($data['description']);
            $course->setCategoryId($data['category_id']);
            $course->setPrice($data['price']);
            $course->setDurationHours($data['duration_hours']);
            $course->setDifficultyLevel($data['difficulty_level']);
            $course->setStatus($data['status'] ?? 'draft');
            $course->setThumbnail($data['thumbnail'] ?? null);

            $course = $this->courseRepository->save($course);

            // Clear cache
            $this->cache->deletePattern("instructor_courses_{$instructorId}_*");

            // Dispatch event
            $this->events->dispatch('course.created', [
                'course_id' => $course->getId(),
                'instructor_id' => $instructorId
            ]);

            $this->logger->info("Course created successfully", [
                'course_id' => $course->getId(),
                'instructor_id' => $instructorId,
                'title' => $data['title']
            ]);

            return $course;

        } catch (\Exception $e) {
            $this->logger->error("Failed to create course", [
                'instructor_id' => $instructorId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to create course: " . $e->getMessage());
        }
    }

    /**
     * Update existing course
     */
    public function updateCourse(int $courseId, int $instructorId, array $data): Course
    {
        try {
            // Get and verify ownership
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->getInstructorId() !== $instructorId) {
                throw new \InvalidArgumentException("Course not found or access denied");
            }

            // Update fields
            if (isset($data['title'])) $course->setTitle($data['title']);
            if (isset($data['description'])) $course->setDescription($data['description']);
            if (isset($data['category_id'])) $course->setCategoryId($data['category_id']);
            if (isset($data['price'])) $course->setPrice($data['price']);
            if (isset($data['duration_hours'])) $course->setDurationHours($data['duration_hours']);
            if (isset($data['difficulty_level'])) $course->setDifficultyLevel($data['difficulty_level']);
            if (isset($data['status'])) $course->setStatus($data['status']);
            if (isset($data['thumbnail'])) $course->setThumbnail($data['thumbnail']);

            $course = $this->courseRepository->save($course);

            // Clear cache
            $this->cache->deletePattern("instructor_courses_{$instructorId}_*");
            $this->cache->delete("course_{$courseId}");

            // Dispatch event
            $this->events->dispatch('course.updated', [
                'course_id' => $courseId,
                'instructor_id' => $instructorId
            ]);

            $this->logger->info("Course updated successfully", [
                'course_id' => $courseId,
                'instructor_id' => $instructorId
            ]);

            return $course;

        } catch (\Exception $e) {
            $this->logger->error("Failed to update course", [
                'course_id' => $courseId,
                'instructor_id' => $instructorId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to update course: " . $e->getMessage());
        }
    }

    /**
     * Delete course
     */
    public function deleteCourse(int $courseId, int $instructorId): bool
    {
        try {
            // Get and verify ownership
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->getInstructorId() !== $instructorId) {
                throw new \InvalidArgumentException("Course not found or access denied");
            }

            // Check if course has enrollments
            $enrollmentCount = $this->enrollmentRepository->countByCourse($courseId);
            if ($enrollmentCount > 0) {
                throw new \RuntimeException("Cannot delete course with active enrollments");
            }

            // Delete course
            $this->courseRepository->delete($courseId);

            // Clear cache
            $this->cache->deletePattern("instructor_courses_{$instructorId}_*");
            $this->cache->delete("course_{$courseId}");

            // Dispatch event
            $this->events->dispatch('course.deleted', [
                'course_id' => $courseId,
                'instructor_id' => $instructorId
            ]);

            $this->logger->info("Course deleted successfully", [
                'course_id' => $courseId,
                'instructor_id' => $instructorId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error("Failed to delete course", [
                'course_id' => $courseId,
                'instructor_id' => $instructorId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to delete course: " . $e->getMessage());
        }
    }

    /**
     * Get course with full details
     */
    public function getCourseWithDetails(int $courseId, int $instructorId): array
    {
        $cacheKey = "course_details_{$courseId}_{$instructorId}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->getInstructorId() !== $instructorId) {
                throw new \InvalidArgumentException("Course not found or access denied");
            }

            $details = $course->toArray();
            $details['enrollments'] = $this->enrollmentRepository->findByCourse($courseId);
            $details['lessons'] = $this->courseRepository->getLessons($courseId);
            $details['analytics'] = $this->enrollmentRepository->getCourseAnalytics($courseId);

            // Cache for 10 minutes
            $this->cache->set($cacheKey, $details, 600);

            return $details;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get course details", [
                'course_id' => $courseId,
                'instructor_id' => $instructorId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to retrieve course details");
        }
    }

    /**
     * Get available categories
     */
    public function getCategories(): array
    {
        $cacheKey = 'course_categories';
        
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $categories = $this->courseRepository->getCategories();
        
        // Cache for 1 hour
        $this->cache->set($cacheKey, $categories, 3600);

        return $categories;
    }
}
