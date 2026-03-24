<?php

namespace App\Repositories;

use App\Models\Course;
use Core\Database\Connection;
use Core\Database\QueryBuilder;

/**
 * Course Repository
 * 
 * Handles all database operations for courses
 * following Repository pattern
 */
class CourseRepository
{
    private Connection $db;
    private QueryBuilder $queryBuilder;

    public function __construct(Connection $db, QueryBuilder $queryBuilder)
    {
        $this->db = $db;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Find course by ID
     */
    public function findById(int $id): ?Course
    {
        $query = $this->queryBuilder->select()
            ->from('courses')
            ->where('id', '=', $id)
            ->limit(1);

        $result = $this->db->fetchOne($query->getSQL(), [$id]);
        
        return $result ? new Course($result) : null;
    }

    /**
     * Find instructor's courses with pagination and filtering
     */
    public function findInstructorCourses(
        int $instructorId,
        array $filters = [],
        int $limit = 12,
        int $offset = 0
    ): array {
        $query = $this->queryBuilder->select([
            'c.id', 'c.title', 'c.description', 'c.category_id',
            'c.price', 'c.duration_hours', 'c.difficulty_level',
            'c.status', 'c.thumbnail', 'c.created_at', 'c.updated_at',
            'cat.name as category_name'
        ])
        ->from('courses', 'c')
        ->leftJoin('categories', 'cat', 'c.category_id = cat.id')
        ->where('c.instructor_id', '=', $instructorId);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where('c.title', 'LIKE', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('c.status', '=', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('c.category_id', '=', $filters['category_id']);
        }

        // Apply ordering
        $orderBy = $filters['sort'] ?? 'created_at';
        $orderDir = $filters['order'] ?? 'DESC';
        $query->orderBy("c.{$orderBy}", $orderDir);

        // Apply pagination
        $query->limit($limit)->offset($offset);

        $params = [$instructorId];
        if (!empty($filters['search'])) $params[] = '%' . $filters['search'] . '%';
        if (!empty($filters['status'])) $params[] = $filters['status'];
        if (!empty($filters['category_id'])) $params[] = $filters['category_id'];

        return $this->db->fetchAll($query->getSQL(), $params);
    }

    /**
     * Count instructor's courses
     */
    public function countInstructorCourses(int $instructorId, array $filters = []): int
    {
        $query = $this->queryBuilder->select(['COUNT(*) as total'])
            ->from('courses')
            ->where('instructor_id', '=', $instructorId);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where('title', 'LIKE', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', '=', $filters['category_id']);
        }

        $params = [$instructorId];
        if (!empty($filters['search'])) $params[] = '%' . $filters['search'] . '%';
        if (!empty($filters['status'])) $params[] = $filters['status'];
        if (!empty($filters['category_id'])) $params[] = $filters['category_id'];

        $result = $this->db->fetchOne($query->getSQL(), $params);
        
        return (int) $result['total'];
    }

    /**
     * Save course (create or update)
     */
    public function save(Course $course): Course
    {
        if ($course->getId()) {
            // Update existing course
            $data = [
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'category_id' => $course->getCategoryId(),
                'price' => $course->getPrice(),
                'duration_hours' => $course->getDurationHours(),
                'difficulty_level' => $course->getDifficultyLevel(),
                'status' => $course->getStatus(),
                'thumbnail' => $course->getThumbnail(),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->update('courses', $data, ['id' => $course->getId()]);
            
        } else {
            // Create new course
            $data = [
                'instructor_id' => $course->getInstructorId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'category_id' => $course->getCategoryId(),
                'price' => $course->getPrice(),
                'duration_hours' => $course->getDurationHours(),
                'difficulty_level' => $course->getDifficultyLevel(),
                'status' => $course->getStatus(),
                'thumbnail' => $course->getThumbnail(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $id = $this->db->insert('courses', $data);
            $course->setId($id);
        }

        return $course;
    }

    /**
     * Delete course
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('courses', ['id' => $id]) > 0;
    }

    /**
     * Get course lessons
     */
    public function getLessons(int $courseId): array
    {
        $query = $this->queryBuilder->select()
            ->from('lessons')
            ->where('course_id', '=', $courseId)
            ->orderBy('lesson_order', 'ASC');

        return $this->db->fetchAll($query->getSQL(), [$courseId]);
    }

    /**
     * Count lessons in course
     */
    public function countLessons(int $courseId): int
    {
        $query = $this->queryBuilder->select(['COUNT(*) as total'])
            ->from('lessons')
            ->where('course_id', '=', $courseId);

        $result = $this->db->fetchOne($query->getSQL(), [$courseId]);
        
        return (int) $result['total'];
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $query = $this->queryBuilder->select()
            ->from('categories')
            ->where('status', '=', 'active')
            ->orderBy('name', 'ASC');

        return $this->db->fetchAll($query->getSQL());
    }

    /**
     * Get instructor's course statistics
     */
    public function getInstructorStats(int $instructorId): array
    {
        $query = $this->queryBuilder->select([
            'COUNT(*) as total_courses',
            'COUNT(CASE WHEN status = "published" THEN 1 END) as published_courses',
            'COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_courses',
            'AVG(price) as avg_price',
            'SUM(price) as total_revenue'
        ])
        ->from('courses')
        ->where('instructor_id', '=', $instructorId);

        $result = $this->db->fetchOne($query->getSQL(), [$instructorId]);
        
        return [
            'total_courses' => (int) $result['total_courses'],
            'published_courses' => (int) $result['published_courses'],
            'draft_courses' => (int) $result['draft_courses'],
            'avg_price' => (float) $result['avg_price'],
            'total_revenue' => (float) $result['total_revenue']
        ];
    }

    /**
     * Search courses
     */
    public function search(string $term, int $limit = 20): array
    {
        $query = $this->queryBuilder->select([
            'c.id', 'c.title', 'c.description', 'c.thumbnail',
            'cat.name as category_name', 'u.full_name as instructor_name'
        ])
        ->from('courses', 'c')
        ->leftJoin('categories', 'cat', 'c.category_id = cat.id')
        ->leftJoin('users', 'u', 'c.instructor_id = u.id')
        ->where('c.status', '=', 'published')
        ->where('c.title', 'LIKE', '%' . $term . '%')
        ->orWhere('c.description', 'LIKE', '%' . $term . '%')
        ->orderBy('c.created_at', 'DESC')
        ->limit($limit);

        return $this->db->fetchAll($query->getSQL(), ['%' . $term . '%', '%' . $term . '%']);
    }
}
