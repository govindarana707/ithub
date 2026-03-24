<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Instructor\CourseService;
use App\Repositories\CourseRepository;
use Core\Cache\CacheManager;
use Core\Logging\Logger;

/**
 * Course Management Feature Tests
 * 
 * Comprehensive test coverage for course operations
 */
class CourseManagementTest extends TestCase
{
    private CourseService $courseService;
    private int $instructorId;
    private array $testData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->courseService = $this->app->make(CourseService::class);
        $this->instructorId = $this->createTestInstructor();
        
        $this->testData = [
            'title' => 'Test Course ' . uniqid(),
            'description' => 'This is a test course description',
            'category_id' => 1,
            'price' => 99.99,
            'duration_hours' => 10,
            'difficulty_level' => 'beginner',
            'status' => 'draft'
        ];
    }

    /**
     * Test course creation
     */
    public function testCreateCourse(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);

        $this->assertNotNull($course);
        $this->assertEquals($this->testData['title'], $course->getTitle());
        $this->assertEquals($this->instructorId, $course->getInstructorId());
        $this->assertEquals($this->testData['price'], $course->getPrice());
        
        // Verify course exists in database
        $this->assertDatabaseHas('courses', [
            'id' => $course->getId(),
            'title' => $this->testData['title'],
            'instructor_id' => $this->instructorId
        ]);
    }

    /**
     * Test course validation
     */
    public function testCourseValidation(): void
    {
        $invalidData = [
            'title' => '', // Empty title
            'description' => 'Test',
            'category_id' => 999, // Non-existent category
            'price' => -10, // Negative price
            'duration_hours' => 0 // Zero duration
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->courseService->createCourse($this->instructorId, $invalidData);
    }

    /**
     * Test course update
     */
    public function testUpdateCourse(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);
        
        $updateData = [
            'title' => 'Updated Course Title',
            'price' => 149.99,
            'status' => 'published'
        ];

        $updatedCourse = $this->courseService->updateCourse($course->getId(), $this->instructorId, $updateData);

        $this->assertEquals($updateData['title'], $updatedCourse->getTitle());
        $this->assertEquals($updateData['price'], $updatedCourse->getPrice());
        $this->assertEquals($updateData['status'], $updatedCourse->getStatus());
    }

    /**
     * Test unauthorized course update
     */
    public function testUnauthorizedCourseUpdate(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);
        $unauthorizedInstructorId = $this->createTestInstructor();

        $this->expectException(\InvalidArgumentException::class);
        $this->courseService->updateCourse($course->getId(), $unauthorizedInstructorId, ['title' => 'Hacked']);
    }

    /**
     * Test course deletion
     */
    public function testDeleteCourse(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);
        $courseId = $course->getId();

        $result = $this->courseService->deleteCourse($courseId, $this->instructorId);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('courses', ['id' => $courseId]);
    }

    /**
     * Test course deletion with enrollments
     */
    public function testDeleteCourseWithEnrollments(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);
        
        // Create enrollment
        $studentId = $this->createTestStudent();
        $this->createEnrollment($studentId, $course->getId());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete course with active enrollments');
        
        $this->courseService->deleteCourse($course->getId(), $this->instructorId);
    }

    /**
     * Test get instructor courses
     */
    public function testGetInstructorCourses(): void
    {
        // Create multiple courses
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $data = $this->testData;
            $data['title'] = "Test Course {$i}";
            $courses[] = $this->courseService->createCourse($this->instructorId, $data);
        }

        $result = $this->courseService->getInstructorCourses($this->instructorId);

        $this->assertCount(5, $result['courses']);
        $this->assertEquals(5, $result['pagination']['total']);
        
        // Verify course data
        foreach ($result['courses'] as $course) {
            $this->assertEquals($this->instructorId, $course['instructor_id']);
            $this->assertArrayHasKey('enrollment_count', $course);
            $this->assertArrayHasKey('avg_progress', $course);
            $this->assertArrayHasKey('lesson_count', $course);
        }
    }

    /**
     * Test course filtering
     */
    public function testCourseFiltering(): void
    {
        // Create courses with different statuses
        $draftCourse = $this->courseService->createCourse($this->instructorId, 
            array_merge($this->testData, ['status' => 'draft', 'title' => 'Draft Course']));
        
        $publishedCourse = $this->courseService->createCourse($this->instructorId, 
            array_merge($this->testData, ['status' => 'published', 'title' => 'Published Course']));

        // Filter by status
        $draftCourses = $this->courseService->getInstructorCourses($this->instructorId, ['status' => 'draft']);
        $publishedCourses = $this->courseService->getInstructorCourses($this->instructorId, ['status' => 'published']);

        $this->assertCount(1, $draftCourses['courses']);
        $this->assertCount(1, $publishedCourses['courses']);
        
        $this->assertEquals('Draft Course', $draftCourses['courses'][0]['title']);
        $this->assertEquals('Published Course', $publishedCourses['courses'][0]['title']);
    }

    /**
     * Test course search
     */
    public function testCourseSearch(): void
    {
        // Create courses with searchable content
        $this->courseService->createCourse($this->instructorId, 
            array_merge($this->testData, ['title' => 'PHP Programming Course']));
        
        $this->courseService->createCourse($this->instructorId, 
            array_merge($this->testData, ['title' => 'JavaScript Development Course']));

        $searchResults = $this->courseService->getInstructorCourses($this->instructorId, ['search' => 'PHP']);

        $this->assertCount(1, $searchResults['courses']);
        $this->assertStringContainsString('PHP', $searchResults['courses'][0]['title']);
    }

    /**
     * Test caching functionality
     */
    public function testCourseCaching(): void
    {
        $cacheManager = $this->app->make(CacheManager::class);
        
        // Clear cache
        $cacheManager->deletePattern("instructor_courses_{$this->instructorId}_*");

        // First call - should hit database
        $start = microtime(true);
        $result1 = $this->courseService->getInstructorCourses($this->instructorId);
        $firstCallTime = microtime(true) - $start;

        // Second call - should hit cache
        $start = microtime(true);
        $result2 = $this->courseService->getInstructorCourses($this->instructorId);
        $secondCallTime = microtime(true) - $start;

        // Results should be identical
        $this->assertEquals($result1, $result2);
        
        // Second call should be faster (cached)
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }

    /**
     * Test performance with large dataset
     */
    public function testPerformanceWithLargeDataset(): void
    {
        // Create 100 courses
        $courses = [];
        for ($i = 1; $i <= 100; $i++) {
            $data = $this->testData;
            $data['title'] = "Performance Test Course {$i}";
            $courses[] = $this->courseService->createCourse($this->instructorId, $data);
        }

        $start = microtime(true);
        $result = $this->courseService->getInstructorCourses($this->instructorId);
        $executionTime = microtime(true) - $start;

        // Should handle 100 courses efficiently
        $this->assertCount(100, $result['courses']);
        $this->assertLessThan(1.0, $executionTime); // Should complete in under 1 second
    }

    /**
     * Test concurrent course creation
     */
    public function testConcurrentCourseCreation(): void
    {
        $processes = [];
        $results = [];

        // Simulate concurrent course creation
        for ($i = 0; $i < 5; $i++) {
            $data = $this->testData;
            $data['title'] = "Concurrent Course {$i}";
            
            try {
                $course = $this->courseService->createCourse($this->instructorId, $data);
                $results[] = $course->getId();
            } catch (\Exception $e) {
                $this->fail("Concurrent course creation failed: " . $e->getMessage());
            }
        }

        // Verify all courses were created successfully
        $this->assertCount(5, $results);
        
        // Verify all IDs are unique
        $this->assertEquals(count($results), count(array_unique($results)));
    }

    /**
     * Test data integrity
     */
    public function testDataIntegrity(): void
    {
        $course = $this->courseService->createCourse($this->instructorId, $this->testData);

        // Verify foreign key constraints
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('foreign key constraint fails');
        
        // Try to insert course with non-existent instructor
        $invalidData = $this->testData;
        $invalidData['title'] = 'Invalid Course';
        
        $this->courseService->createCourse(99999, $invalidData);
    }

    // Helper methods
    private function createTestInstructor(): int
    {
        $instructorId = $this->createUser([
            'username' => 'test_instructor_' . uniqid(),
            'email' => 'instructor' . uniqid() . '@test.com',
            'role' => 'instructor',
            'full_name' => 'Test Instructor'
        ]);

        return $instructorId;
    }

    private function createTestStudent(): int
    {
        $studentId = $this->createUser([
            'username' => 'test_student_' . uniqid(),
            'email' => 'student' . uniqid() . '@test.com',
            'role' => 'student',
            'full_name' => 'Test Student'
        ]);

        return $studentId;
    }

    private function createUser(array $data): int
    {
        $this->db->insert('users', array_merge([
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'status' => 'active',
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ], $data));

        return $this->db->lastInsertId();
    }

    private function createEnrollment(int $studentId, int $courseId): void
    {
        $this->db->insert('enrollments', [
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => 'active',
            'enrolled_at' => date('Y-m-d H:i:s')
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->delete('courses', ['instructor_id' => $this->instructorId]);
        $this->db->delete('users', ['id' => $this->instructorId]);
        
        parent::tearDown();
    }
}
