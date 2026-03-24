<?php

namespace App\Controllers\Instructor;

use App\Services\Instructor\CourseService;
use App\Services\Instructor\AnalyticsService;
use App\Validators\CourseValidator;
use Core\Request;
use Core\Response;
use Core\Container;

/**
 * Instructor Course Controller
 * 
 * Handles all course-related operations for instructors
 * following SOLID principles and clean architecture
 */
class CourseController
{
    private CourseService $courseService;
    private AnalyticsService $analyticsService;
    private CourseValidator $validator;

    public function __construct(
        CourseService $courseService,
        AnalyticsService $analyticsService,
        CourseValidator $validator
    ) {
        $this->courseService = $courseService;
        $this->analyticsService = $analyticsService;
        $this->validator = $validator;
    }

    /**
     * Display instructor's courses dashboard
     */
    public function index(Request $request): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        // Validate and sanitize input
        $filters = $this->validator->validateCourseFilters($request->all());
        
        // Get paginated courses with filters
        $courses = $this->courseService->getInstructorCourses(
            $instructorId,
            $filters,
            $request->get('page', 1),
            $request->get('limit', 12)
        );

        // Get analytics data
        $analytics = $this->analyticsService->getCourseAnalytics($instructorId);

        return Response::view('instructor.courses.index', [
            'courses' => $courses,
            'analytics' => $analytics,
            'filters' => $filters
        ]);
    }

    /**
     * Show course creation form
     */
    public function create(Request $request): Response
    {
        $categories = $this->courseService->getCategories();
        
        return Response::view('instructor.courses.create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store new course
     */
    public function store(Request $request): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        // Validate input
        $validatedData = $this->validator->validateCourseCreation($request->all());
        
        try {
            $course = $this->courseService->createCourse($instructorId, $validatedData);
            
            return Response::redirect('/instructor/courses')
                ->with('success', 'Course created successfully!');
                
        } catch (\Exception $e) {
            return Response::back()
                ->with('error', 'Failed to create course: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show course details
     */
    public function show(Request $request, int $courseId): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        try {
            $course = $this->courseService->getCourseWithDetails($courseId, $instructorId);
            $analytics = $this->analyticsService->getCoursePerformance($courseId);
            
            return Response::view('instructor.courses.show', [
                'course' => $course,
                'analytics' => $analytics
            ]);
            
        } catch (\Exception $e) {
            return Response::redirect('/instructor/courses')
                ->with('error', 'Course not found');
        }
    }

    /**
     * Update course
     */
    public function update(Request $request, int $courseId): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        // Validate input
        $validatedData = $this->validator->validateCourseUpdate($request->all());
        
        try {
            $course = $this->courseService->updateCourse(
                $courseId, 
                $instructorId, 
                $validatedData
            );
            
            return Response::redirect('/instructor/courses')
                ->with('success', 'Course updated successfully!');
                
        } catch (\Exception $e) {
            return Response::back()
                ->with('error', 'Failed to update course: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete course
     */
    public function destroy(Request $request, int $courseId): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        try {
            $this->courseService->deleteCourse($courseId, $instructorId);
            
            return Response::redirect('/instructor/courses')
                ->with('success', 'Course deleted successfully!');
                
        } catch (\Exception $e) {
            return Response::back()
                ->with('error', 'Failed to delete course: ' . $e->getMessage());
        }
    }

    /**
     * Get course statistics (AJAX endpoint)
     */
    public function stats(Request $request, int $courseId): Response
    {
        $instructorId = $request->session()->get('user_id');
        
        try {
            $stats = $this->analyticsService->getCourseStats($courseId, $instructorId);
            
            return Response::json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
