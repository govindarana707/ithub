/**
 * Student AJAX API Helper
 * Unified JavaScript module for all student portal AJAX operations
 * Uses SweetAlert2 for user feedback and notifications
 */

const StudentAPI = (function() {
    'use strict';

    // API Base URL
    const API_URL = 'api/student_api.php';

    // Current user data cache
    let userData = null;

    /**
     * Generic AJAX request helper
     */
    async function request(action, data = {}, method = 'GET') {
        return new Promise((resolve, reject) => {
            // Show loading indicator
            Swal.fire({
                title: 'Loading...',
                html: '<div class="spinner-border text-primary" role="status"></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            const options = {
                url: API_URL + '?action=' + action,
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        resolve(response);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                        reject(response);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    let errorMsg = 'An error occurred. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch (e) {}
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: errorMsg
                    });
                    reject({ status: 'error', message: errorMsg });
                }
            };

            if (method === 'POST' || method === 'PUT') {
                options.data = data;
                options.contentType = 'application/x-www-form-urlencoded';
                
                // Handle file uploads
                if (data instanceof FormData) {
                    options.processData = false;
                    options.contentType = false;
                    options.data = data;
                }
            }

            $.ajax(options);
        });
    }

    /**
     * Get all available courses
     */
    async function getCourses(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = API_URL + '?action=get_courses' + (queryString ? '&' + queryString : '');
        
        return new Promise((resolve, reject) => {
            $.get(url)
                .done(function(response) {
                    if (response.status === 'success') {
                        resolve(response.data);
                    } else {
                        reject(response);
                    }
                })
                .fail(reject);
        });
    }

    /**
     * Get single course details
     */
    async function getCourseDetails(courseId) {
        return request('get_course_details', { course_id: courseId });
    }

    /**
     * Search courses
     */
    async function searchCourses(query) {
        return request('search_courses', { query: query });
    }

    /**
     * Get categories
     */
    async function getCategories() {
        return request('get_categories');
    }

    /**
     * Enroll in a course
     */
    async function enrollCourse(courseId, paymentMethod = 'free') {
        // Show confirmation dialog
        const result = await Swal.fire({
            title: 'Confirm Enrollment',
            text: 'Are you sure you want to enroll in this course?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Enroll Now!'
        });

        if (!result.isConfirmed) {
            throw { status: 'cancelled', message: 'Enrollment cancelled' };
        }

        // Process enrollment
        return new Promise((resolve, reject) => {
            $.post(API_URL, {
                action: 'enroll_course',
                course_id: courseId,
                payment_method: paymentMethod
            })
            .done(function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enrollment Successful!',
                        text: response.message,
                        confirmButtonText: 'Go to Course'
                    }).then(() => {
                        // Redirect to course or refresh
                        window.location.href = 'my-courses.php';
                    });
                    resolve(response);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Enrollment Failed',
                        text: response.message
                    });
                    reject(response);
                }
            })
            .fail(function(xhr) {
                let errorMsg = 'Enrollment failed. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                reject({ status: 'error', message: errorMsg });
            });
        });
    }

    /**
     * Get enrolled courses (My Courses)
     */
    async function getMyCourses(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = API_URL + '?action=get_my_courses' + (queryString ? '&' + queryString : '');
        
        return new Promise((resolve, reject) => {
            $.get(url)
                .done(function(response) {
                    if (response.status === 'success') {
                        resolve(response.data);
                    } else {
                        reject(response);
                    }
                })
                .fail(reject);
        });
    }

    /**
     * Get course content (lessons)
     */
    async function getCourseContent(courseId) {
        return request('get_course_content', { course_id: courseId });
    }

    /**
     * Get single lesson content
     */
    async function getLessonContent(lessonId) {
        return request('get_lesson_content', { lesson_id: lessonId });
    }

    /**
     * Mark lesson as complete
     */
    async function markLessonComplete(lessonId) {
        // Show confirmation
        const result = await Swal.fire({
            title: 'Complete Lesson?',
            text: 'Mark this lesson as completed?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Complete!'
        });

        if (!result.isConfirmed) {
            throw { status: 'cancelled' };
        }

        return new Promise((resolve, reject) => {
            $.post(API_URL, { action: 'mark_lesson_complete', lesson_id: lessonId })
                .done(function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Lesson Completed!',
                            text: 'Great job! Keep going!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        resolve(response);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                        reject(response);
                    }
                })
                .fail(reject);
        });
    }

    /**
     * Get assignments for a lesson
     */
    async function getAssignments(lessonId) {
        return request('get_assignments', { lesson_id: lessonId });
    }

    /**
     * Submit assignment
     */
    async function submitAssignment(assignmentId, textContent = '', file = null) {
        // Show confirmation
        const result = await Swal.fire({
            title: 'Submit Assignment?',
            text: 'Once submitted, you may not be able to modify your submission.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Submit Now!'
        });

        if (!result.isConfirmed) {
            throw { status: 'cancelled' };
        }

        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'submit_assignment');
        formData.append('assignment_id', assignmentId);
        formData.append('text_content', textContent);
        
        if (file) {
            formData.append('file', file);
        }

        return new Promise((resolve, reject) => {
            $.ajax({
                url: API_URL,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Assignment Submitted!',
                            text: response.message,
                            confirmButtonText: 'Continue Learning'
                        });
                        resolve(response);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: response.message
                        });
                        reject(response);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Submission failed. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch (e) {}
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                    reject({ status: 'error', message: errorMsg });
                }
            });
        });
    }

    /**
     * Get all submissions
     */
    async function getSubmissions(courseId = null) {
        const params = courseId ? { course_id: courseId } : {};
        return request('get_submissions', params);
    }

    /**
     * Get progress
     */
    async function getProgress(courseId = null) {
        const params = courseId ? { course_id: courseId } : {};
        return request('get_progress', params);
    }

    /**
     * Get dashboard stats
     */
    async function getDashboardStats() {
        return request('get_dashboard_stats');
    }

    /**
     * Get notifications
     */
    async function getNotifications(unreadOnly = false) {
        return request('get_notifications', { unread_only: unreadOnly });
    }

    /**
     * Mark notification as read
     */
    async function markNotificationRead(notificationId) {
        return new Promise((resolve, reject) => {
            $.post(API_URL, {
                action: 'mark_notification_read',
                notification_id: notificationId
            })
            .done(resolve)
            .fail(reject);
        });
    }

    /**
     * Get quizzes for a course
     */
    async function getQuizzes(courseId) {
        return request('get_quizzes', { course_id: courseId });
    }

    /**
     * Show loading spinner in container
     */
    function showLoading(container) {
        $(container).html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading...</p>
            </div>
        `);
    }

    /**
     * Show empty state
     */
    function showEmpty(container, message, icon = 'folder-open') {
        $(container).html(`
            <div class="text-center py-5">
                <i class="fas fa-${icon} fa-4x text-muted mb-3"></i>
                <p class="text-muted">${message}</p>
            </div>
        `);
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Format duration (minutes to hours/minutes)
     */
    function formatDuration(minutes) {
        if (!minutes) return '0 min';
        if (minutes < 60) return minutes + ' min';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours + 'h ' + mins + 'm';
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Create course card HTML
     */
    function createCourseCard(course, options = {}) {
        const {
            showProgress = false,
            showEnrollButton = true,
            isEnrolled = false,
            onEnroll = null
        } = options;

        const progressBar = showProgress && isEnrolled ? `
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: ${course.progress_percentage || 0}%"></div>
            </div>
            <small class="text-muted">${course.progress_percentage || 0}% complete</small>
        ` : '';

        const actionButton = showEnrollButton ? `
            ${isEnrolled ? `
                <a href="lesson.php?course_id=${course.id}" class="btn btn-success btn-sm btn-block">
                    <i class="fas fa-play mr-1"></i> Continue
                </a>
            ` : `
                <button class="btn btn-primary btn-sm btn-block enroll-btn" data-course-id="${course.id}">
                    <i class="fas fa-plus mr-1"></i> Enroll Now
                </button>
            `}
        ` : '';

        return `
            <div class="card course-card h-100 shadow-sm">
                <div class="card-img-wrapper">
                    <img src="${course.thumbnail || 'https://via.placeholder.com/300x200?text=Course'}" 
                         class="card-img-top" alt="${course.title}">
                    <span class="badge badge-primary position-absolute" style="top: 10px; right: 10px;">
                        ${course.difficulty_level || 'Beginner'}
                    </span>
                    ${course.category_name ? `
                        <span class="badge badge-secondary position-absolute" style="top: 10px; left: 10px;">
                            ${course.category_name}
                        </span>
                    ` : ''}
                </div>
                <div class="card-body">
                    <h5 class="card-title text-truncate">${course.title}</h5>
                    <p class="card-text small text-muted text-truncate-2">
                        ${course.description || 'No description available'}
                    </p>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span><i class="fas fa-user mr-1"></i> ${course.instructor_name || 'Unknown'}</span>
                        <span><i class="fas fa-users mr-1"></i> ${course.enrollment_count || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-info">${course.duration_hours || 0} hours</span>
                        <span class="text-primary font-weight-bold">$${course.price || 0}</span>
                    </div>
                    ${progressBar}
                </div>
                <div class="card-footer bg-white">
                    ${actionButton}
                </div>
            </div>
        `;
    }

    /**
     * Create lesson item HTML
     */
    function createLessonItem(lesson, index) {
        const completedClass = lesson.is_completed ? 'completed' : '';
        const checkIcon = lesson.is_completed ? 'fa-check-circle text-success' : 'fa-circle text-muted';
        const duration = lesson.duration_minutes ? lesson.duration_minutes + ' min' : '';
        
        return `
            <div class="lesson-item ${completedClass}" data-lesson-id="${lesson.id}">
                <div class="lesson-number">${index + 1}</div>
                <div class="lesson-info">
                    <h6 class="lesson-title">${lesson.title}</h6>
                    <div class="lesson-meta">
                        <span><i class="fas fa-${lesson.lesson_type === 'video' ? 'play-circle' : 'file-alt'} mr-1"></i> ${lesson.lesson_type}</span>
                        ${duration ? `<span><i class="fas fa-clock mr-1"></i> ${duration}</span>` : ''}
                        ${lesson.is_completed ? `<span class="text-success"><i class="fas fa-check mr-1"></i> Completed</span>` : ''}
                    </div>
                </div>
                <div class="lesson-actions">
                    <i class="fas ${checkIcon}"></i>
                </div>
            </div>
        `;
    }

    /**
     * Create assignment card HTML
     */
    function createAssignmentCard(assignment) {
        const isPastDue = assignment.due_date && new Date(assignment.due_date) < new Date();
        const hasSubmission = assignment.submission && assignment.submission.length > 0;
        const submission = hasSubmission ? assignment.submission[0] : null;
        
        let statusBadge = '';
        if (submission) {
            if (submission.status === 'graded') {
                statusBadge = `<span class="badge badge-success">Graded: ${submission.points_earned}/${submission.points_possible}</span>`;
            } else if (submission.status === 'submitted') {
                statusBadge = '<span class="badge badge-info">Submitted</span>';
            }
        } else if (isPastDue) {
            statusBadge = '<span class="badge badge-danger">Past Due</span>';
        } else if (assignment.due_date) {
            const daysUntilDue = Math.ceil((new Date(assignment.due_date) - new Date()) / (1000 * 60 * 60 * 24));
            if (daysUntilDue <= 3) {
                statusBadge = `<span class="badge badge-warning">Due Soon</span>`;
            } else {
                statusBadge = '<span class="badge badge-secondary">Pending</span>';
            }
        }

        return `
            <div class="assignment-card card mb-3" data-assignment-id="${assignment.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">${assignment.title}</h5>
                            <p class="card-text text-muted small">${assignment.description || ''}</p>
                        </div>
                        ${statusBadge}
                    </div>
                    <div class="assignment-meta mt-3">
                        <span class="mr-3"><i class="fas fa-star mr-1"></i> ${assignment.max_points || 100} points</span>
                        ${assignment.due_date ? `<span><i class="fas fa-calendar mr-1"></i> Due: ${formatDate(assignment.due_date)}</span>` : ''}
                        ${assignment.attempt_count ? `<span class="ml-3"><i class="fas fa-redo mr-1"></i> Attempts: ${assignment.attempt_count}/${assignment.max_attempts || 1}</span>` : ''}
                    </div>
                    ${submission ? `
                        <div class="submission-info mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                Submitted: ${formatDate(submission.submitted_at)}
                                ${submission.is_late ? '<span class="badge badge-warning ml-2">Late</span>' : ''}
                            </small>
                        </div>
                    ` : ''}
                    <button class="btn btn-primary btn-sm mt-3 submit-assignment-btn" data-assignment-id="${assignment.id}">
                        <i class="fas fa-upload mr-1"></i> ${hasSubmission ? 'Resubmit' : 'Submit'} Assignment
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Initialize enroll button click handlers
     */
    function initEnrollButtons() {
        $(document).on('click', '.enroll-btn', function(e) {
            e.preventDefault();
            const courseId = $(this).data('course-id');
            enrollCourse(courseId)
                .then(() => {
                    // Refresh the page or update UI
                    location.reload();
                })
                .catch(() => {});
        });
    }

    // Public API
    return {
        // Course operations
        getCourses,
        getCourseDetails,
        searchCourses,
        getCategories,
        enrollCourse,
        
        // My Courses
        getMyCourses,
        getCourseContent,
        getLessonContent,
        markLessonComplete,
        
        // Assignments
        getAssignments,
        submitAssignment,
        getSubmissions,
        
        // Progress & Stats
        getProgress,
        getDashboardStats,
        
        // Notifications
        getNotifications,
        markNotificationRead,
        
        // Quizzes
        getQuizzes,
        
        // UI Helpers
        showLoading,
        showEmpty,
        formatDate,
        formatDuration,
        formatFileSize,
        createCourseCard,
        createLessonItem,
        createAssignmentCard,
        initEnrollButtons
    };
})();

// Initialize on document ready
$(document).ready(function() {
    // Initialize enroll buttons
    StudentAPI.initEnrollButtons();
});
