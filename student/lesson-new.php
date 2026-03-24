<?php
/**
 * Course Content View - Enhanced with AJAX
 * No page reloads, fully dynamic learning experience
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

if (!$courseId) {
    header('Location: ' . BASE_URL . 'student/my-courses.php');
    exit();
}

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning - IT Hub</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/lesson-styles.css">
</head>
<body>
    <div class="lesson-container">
        <!-- Sidebar - Course Navigation -->
        <aside class="lesson-sidebar" id="lesson-sidebar">
            <div class="sidebar-header">
                <a href="my-courses.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h5 id="course-title">Loading...</h5>
                <button class="btn btn-link toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <!-- Course Progress -->
            <div class="course-progress px-3 py-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">Progress</small>
                    <small class="font-weight-bold" id="progress-text">0%</small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Lesson List -->
            <div class="lesson-list" id="lesson-list">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="lesson-main" id="lesson-main">
            <!-- Top Bar -->
            <header class="lesson-header">
                <div class="lesson-nav">
                    <button class="btn btn-outline-secondary btn-sm" id="prev-lesson-btn" disabled>
                        <i class="fas fa-chevron-left mr-1"></i> Previous
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="next-lesson-btn" disabled>
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
                <div class="lesson-actions">
                    <button class="btn btn-success btn-sm" id="complete-lesson-btn">
                        <i class="fas fa-check mr-1"></i> Mark Complete
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="lesson-content" id="lesson-content">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted">Loading lesson content...</p>
                </div>
            </div>

            <!-- Bottom Actions -->
            <footer class="lesson-footer" id="lesson-footer">
                <div class="footer-left">
                    <button class="btn btn-outline-primary btn-sm" id="resources-btn">
                        <i class="fas fa-file-alt mr-1"></i> Resources
                    </button>
                    <button class="btn btn-outline-primary btn-sm" id="assignments-btn">
                        <i class="fas fa-tasks mr-1"></i> Assignments
                    </button>
                </div>
                <div class="footer-right">
                    <button class="btn btn-success" id="mark-complete-btn">
                        <i class="fas fa-check-circle mr-1"></i> Complete & Next
                    </button>
                </div>
            </footer>
        </main>
    </div>

    <!-- Resources Modal -->
    <div class="modal fade" id="resources-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-alt mr-2"></i>Lesson Resources</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="resources-modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments Modal -->
    <div class="modal fade" id="assignments-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tasks mr-2"></i>Lesson Assignments</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="assignments-modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Submission Modal -->
    <div class="modal fade" id="submission-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload mr-2"></i>Submit Assignment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="submission-modal-body">
                    <!-- Dynamic content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global variables
        const courseId = <?php echo json_encode($courseId); ?>;
        const initialLessonId = <?php echo json_encode($lessonId); ?>;
        const apiUrl = 'api/student_api.php';
        
        let currentLesson = null;
        let courseData = null;

        $(document).ready(function() {
            // Initialize
            loadCourseContent();
            initEventHandlers();
        });

        /**
         * Load course content (lessons)
         */
        async function loadCourseContent() {
            try {
                const response = await $.get(apiUrl + '?action=get_course_content&course_id=' + courseId);
                
                if (response.status === 'success') {
                    courseData = response.data;
                    
                    // Update course title
                    $('#course-title').text(courseData.course.title);
                    
                    // Update progress
                    updateProgress(courseData.progress);
                    
                    // Render lesson list
                    renderLessonList(courseData.lessons);
                    
                    // Load first lesson or specified lesson
                    const lessonId = initialLessonId || (courseData.lessons.length > 0 ? courseData.lessons[0].id : null);
                    if (lessonId) {
                        loadLesson(lessonId);
                    }
                } else {
                    showError(response.message || 'Failed to load course content');
                }
            } catch (error) {
                showError('Failed to load course content. Please refresh the page.');
            }
        }

        /**
         * Update progress display
         */
        function updateProgress(progress) {
            $('#progress-bar').css('width', progress.percentage + '%');
            $('#progress-text').text(progress.percentage + '%');
        }

        /**
         * Render lesson list in sidebar
         */
        function renderLessonList(lessons) {
            const container = $('#lesson-list');
            
            if (lessons.length === 0) {
                container.html(`
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <p>No lessons available</p>
                    </div>
                `);
                return;
            }
            
            container.html(lessons.map((lesson, index) => `
                <div class="lesson-item ${lesson.is_completed ? 'completed' : ''} ${lesson.id === currentLesson?.id ? 'active' : ''}"
                     data-lesson-id="${lesson.id}">
                    <div class="lesson-status">
                        ${lesson.is_completed 
                            ? '<i class="fas fa-check-circle text-success"></i>' 
                            : '<span class="lesson-number">' + (index + 1) + '</span>'}
                    </div>
                    <div class="lesson-info">
                        <h6 class="lesson-title">${lesson.title}</h6>
                        <div class="lesson-meta">
                            <span><i class="fas fa-${lesson.lesson_type === 'video' ? 'play-circle' : 'file-alt'}"></i></span>
                            ${lesson.duration_minutes ? `<span>${lesson.duration_minutes} min</span>` : ''}
                        </div>
                    </div>
                </div>
            `).join(''));
            
            // Click handler for lesson items
            container.find('.lesson-item').on('click', function() {
                const lessonId = $(this).data('lesson-id');
                loadLesson(lessonId);
            });
        }

        /**
         * Load a specific lesson
         */
        async function loadLesson(lessonId) {
            try {
                // Show loading
                $('#lesson-content').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                        <p class="mt-3 text-muted">Loading lesson...</p>
                    </div>
                `);
                
                const response = await $.get(apiUrl + '?action=get_lesson_content&lesson_id=' + lessonId);
                
                if (response.status === 'success') {
                    currentLesson = response.data;
                    renderLessonContent(currentLesson);
                    updateLessonListActive(lessonId);
                    updateNavigationButtons(currentLesson);
                } else {
                    showError(response.message || 'Failed to load lesson');
                }
            } catch (error) {
                showError('Failed to load lesson. Please try again.');
            }
        }

        /**
         * Render lesson content
         */
        function renderLessonContent(lesson) {
            const container = $('#lesson-content');
            
            // Video content
            let videoSection = '';
            if (lesson.video_url) {
                videoSection = `
                    <div class="video-container mb-4">
                        <video controls class="w-100 rounded" id="lesson-video">
                            <source src="${lesson.video_url}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                `;
            }
            
            // Notes section
            let notesSection = '';
            if (lesson.notes && (lesson.notes.instructor_notes || lesson.notes.study_materials)) {
                notesSection = `
                    <div class="notes-section mt-4">
                        <h5><i class="fas fa-sticky-note mr-2"></i>Study Notes</h5>
                        <div class="card">
                            <div class="card-body">
                                ${lesson.notes.instructor_notes ? `<div class="mb-3">${lesson.notes.instructor_notes}</div>` : ''}
                                ${lesson.notes.study_materials ? `<div>${lesson.notes.study_materials}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Text content
            let textContent = '';
            if (lesson.content) {
                textContent = `
                    <div class="text-content mt-4">
                        <h5><i class="fas fa-align-left mr-2"></i>Lesson Content</h5>
                        <div class="content-body">
                            ${lesson.content}
                        </div>
                    </div>
                `;
            }
            
            container.html(`
                <div class="lesson-header-section mb-4">
                    <h2 class="lesson-title-display">${lesson.title}</h2>
                    <div class="lesson-badges">
                        <span class="badge badge-primary">
                            <i class="fas fa-${lesson.lesson_type === 'video' ? 'play-circle' : 'file-alt'} mr-1"></i>
                            ${lesson.lesson_type}
                        </span>
                        ${lesson.duration_minutes ? `
                            <span class="badge badge-secondary">
                                <i class="fas fa-clock mr-1"></i> ${lesson.duration_minutes} min
                            </span>
                        ` : ''}
                        ${currentLesson?.progress?.is_completed ? `
                            <span class="badge badge-success">
                                <i class="fas fa-check mr-1"></i> Completed
                            </span>
                        ` : ''}
                    </div>
                </div>
                
                ${videoSection}
                ${textContent}
                ${notesSection}
                
                ${!currentLesson?.progress?.is_completed ? `
                    <div class="mark-complete-section mt-4 text-center">
                        <button class="btn btn-success btn-lg" id="mark-complete-btn-inline">
                            <i class="fas fa-check-circle mr-2"></i> Mark This Lesson as Complete
                        </button>
                    </div>
                ` : ''}
            `);
            
            // Initialize inline mark complete button
            $('#mark-complete-btn-inline').on('click', markLessonComplete);
            
            // Track video progress
            if (lesson.video_url) {
                initVideoTracking();
            }
        }

        /**
         * Update lesson list active state
         */
        function updateLessonListActive(lessonId) {
            $('#lesson-list .lesson-item').removeClass('active');
            $(`#lesson-list .lesson-item[data-lesson-id="${lessonId}"]`).addClass('active');
        }

        /**
         * Update navigation buttons
         */
        function updateNavigationButtons(lesson) {
            // Previous button
            if (lesson.previous_lesson) {
                $('#prev-lesson-btn')
                    .prop('disabled', false)
                    .off('click')
                    .on('click', () => loadLesson(lesson.previous_lesson.id));
            } else {
                $('#prev-lesson-btn').prop('disabled', true);
            }
            
            // Next button
            if (lesson.next_lesson) {
                $('#next-lesson-btn')
                    .prop('disabled', false)
                    .off('click')
                    .on('click', () => loadLesson(lesson.next_lesson.id));
            } else {
                $('#next-lesson-btn').prop('disabled', true);
            }
        }

        /**
         * Mark lesson as complete
         */
        async function markLessonComplete() {
            if (!currentLesson) return;
            
            try {
                const response = await $.post(apiUrl, {
                    action: 'mark_lesson_complete',
                    lesson_id: currentLesson.id
                });
                
                if (response.status === 'success') {
                    // Update UI
                    currentLesson.progress = { is_completed: true };
                    
                    // Update lesson list item
                    const lessonItem = $(`#lesson-list .lesson-item[data-lesson-id="${currentLesson.id}"]`);
                    lessonItem.addClass('completed');
                    lessonItem.find('.lesson-status').html('<i class="fas fa-check-circle text-success"></i>');
                    
                    // Update progress
                    if (courseData) {
                        courseData.progress.completed_lessons++;
                        courseData.progress.percentage = Math.round(
                            (courseData.progress.completed_lessons / courseData.progress.total_lessons) * 100
                        );
                        updateProgress(courseData.progress);
                    }
                    
                    // Update content display
                    renderLessonContent(currentLesson);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Lesson Completed!',
                        text: 'Great job! Keep up the good work.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Enable next button if available
                    if (currentLesson.next_lesson) {
                        $('#next-lesson-btn').prop('disabled', false);
                    }
                } else {
                    showError(response.message || 'Failed to mark lesson complete');
                }
            } catch (error) {
                showError('Failed to mark lesson complete. Please try again.');
            }
        }

        /**
         * Initialize video tracking
         */
        function initVideoTracking() {
            const video = document.getElementById('lesson-video');
            if (!video) return;
            
            video.addEventListener('ended', function() {
                // Auto-mark as complete when video ends
                if (!currentLesson?.progress?.is_completed) {
                    markLessonComplete();
                }
            });
            
            // Track watch time
            video.addEventListener('timeupdate', function() {
                const progress = (video.currentTime / video.duration) * 100;
                if (progress > 90 && !currentLesson?.progress?.is_completed) {
                    markLessonComplete();
                }
            });
        }

        /**
         * Initialize event handlers
         */
        function initEventHandlers() {
            // Toggle sidebar
            $('#toggle-sidebar').on('click', function() {
                $('#lesson-sidebar').toggleClass('collapsed');
                $('#lesson-main').toggleClass('expanded');
            });
            
            // Mark complete buttons
            $('#complete-lesson-btn, #mark-complete-btn').on('click', markLessonComplete);
            
            // Resources modal
            $('#resources-btn').on('click', showResourcesModal);
            
            // Assignments modal
            $('#assignments-btn').on('click', showAssignmentsModal);
        }

        /**
         * Show resources modal
         */
        async function showResourcesModal() {
            if (!currentLesson) return;
            
            const modal = $('#resources-modal');
            const body = $('#resources-modal-body');
            
            body.html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            `);
            
            modal.modal('show');
            
            // Check if we need to load resources
            if (currentLesson.resources && currentLesson.resources.length > 0) {
                renderResources(currentLesson.resources);
            } else {
                // Load lesson content to get resources
                try {
                    const response = await $.get(apiUrl + '?action=get_lesson_content&lesson_id=' + currentLesson.id);
                    if (response.status === 'success') {
                        currentLesson = response.data;
                        renderResources(currentLesson.resources || []);
                    }
                } catch (error) {
                    body.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Failed to load resources.
                        </div>
                    `);
                }
            }
        }

        /**
         * Render resources list
         */
        function renderResources(resources) {
            const body = $('#resources-modal-body');
            
            if (resources.length === 0) {
                body.html(`
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>No resources available for this lesson.</p>
                    </div>
                `);
                return;
            }
            
            body.html(resources.map(r => `
                <div class="resource-item card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-${getResourceIcon(r.resource_type)} fa-2x text-primary mr-3"></i>
                                <div>
                                    <h6 class="mb-0">${r.title}</h6>
                                    <small class="text-muted">${r.description || ''}</small>
                                </div>
                            </div>
                            <a href="${r.file_path || r.external_url}" class="btn btn-primary btn-sm" 
                               target="_blank" download="${r.is_downloadable ? r.title : ''}">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `).join(''));
        }

        /**
         * Get resource icon
         */
        function getResourceIcon(type) {
            const icons = {
                'document': 'file-pdf',
                'presentation': 'presentation',
                'video': 'video',
                'audio': 'music',
                'image': 'image',
                'link': 'link'
            };
            return icons[type] || 'file';
        }

        /**
         * Show assignments modal
         */
        async function showAssignmentsModal() {
            if (!currentLesson) return;
            
            const modal = $('#assignments-modal');
            const body = $('#assignments-modal-body');
            
            body.html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            `);
            
            modal.modal('show');
            
            try {
                const response = await $.get(apiUrl + '?action=get_assignments&lesson_id=' + currentLesson.id);
                
                if (response.status === 'success') {
                    renderAssignments(response.data);
                } else {
                    body.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Failed to load assignments.
                        </div>
                    `);
                }
            } catch (error) {
                body.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Failed to load assignments.
                    </div>
                `);
            }
        }

        /**
         * Render assignments list
         */
        function renderAssignments(assignments) {
            const body = $('#assignments-modal-body');
            
            if (assignments.length === 0) {
                body.html(`
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-tasks fa-3x mb-3"></i>
                        <p>No assignments for this lesson.</p>
                    </div>
                `);
                return;
            }
            
            body.html(assignments.map(a => {
                const hasSubmission = a.submission && a.submission.length > 0;
                const submission = hasSubmission ? a.submission[0] : null;
                const isPastDue = a.due_date && new Date(a.due_date) < new Date();
                
                return `
                    <div class="assignment-card card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">${a.title}</h5>
                                    <p class="text-muted">${a.description || 'No description'}</p>
                                </div>
                                <div>
                                    ${submission ? `
                                        <span class="badge badge-${submission.status === 'graded' ? 'success' : 'info'}">
                                            ${submission.status === 'graded' 
                                                ? 'Graded: ' + submission.points_earned + '/' + submission.points_possible
                                                : 'Submitted'}
                                        </span>
                                    ` : isPastDue ? `
                                        <span class="badge badge-danger">Past Due</span>
                                    ` : a.due_date ? `
                                        <span class="badge badge-warning">Due: ${formatDate(a.due_date)}</span>
                                    ` : `
                                        <span class="badge badge-secondary">No Due Date</span>
                                    `}
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-star mr-1"></i> ${a.max_points || 100} points
                                </small>
                                ${a.attempt_count ? `
                                    <small class="text-muted ml-3">
                                        <i class="fas fa-redo mr-1"></i> ${a.attempt_count}/${a.max_attempts || 1} attempts
                                    </small>
                                ` : ''}
                            </div>
                            ${submission?.feedback ? `
                                <div class="feedback-box mt-3 p-3 bg-light rounded">
                                    <h6><i class="fas fa-comment mr-1"></i> Feedback:</h6>
                                    <p class="mb-0">${submission.feedback}</p>
                                </div>
                            ` : ''}
                            ${(a.attempt_count || 0) < (a.max_attempts || 1) ? `
                                <button class="btn btn-primary btn-sm mt-3 submit-assignment-btn" data-assignment-id="${a.id}">
                                    <i class="fas fa-upload mr-1"></i> ${hasSubmission ? 'Resubmit' : 'Submit'} Assignment
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join(''));
            
            // Initialize submit buttons
            initAssignmentSubmitButtons();
        }

        /**
         * Initialize assignment submit buttons
         */
        function initAssignmentSubmitButtons() {
            $('.submit-assignment-btn').on('click', function() {
                const assignmentId = $(this).data('assignment-id');
                showSubmissionModal(assignmentId);
            });
        }

        /**
         * Show submission modal
         */
        async function showSubmissionModal(assignmentId) {
            const modal = $('#submission-modal');
            const body = $('#submission-modal-body');
            
            // Close other modals
            $('#assignments-modal').modal('hide');
            
            body.html(`
                <form id="submission-form" enctype="multipart/form-data">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                    
                    <div class="form-group">
                        <label for="text-content">Your Answer</label>
                        <textarea class="form-control" id="text-content" name="text_content" rows="6" 
                                  placeholder="Enter your answer here..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="file-input">Upload File (Optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="file-input" name="file" 
                                   accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg">
                            <label class="custom-file-label" for="file-input">Choose file...</label>
                        </div>
                        <small class="text-muted">Max size: 10MB | Allowed: PDF, DOC, DOCX, TXT, PNG, JPG</small>
                    </div>
                    
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload mr-1"></i> Submit
                        </button>
                    </div>
                </form>
            `);
            
            modal.modal('show');
            
            // File input handler
            $('#file-input').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').text(fileName || 'Choose file...');
            });
            
            // Form submission
            $('#submission-form').on('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Show loading
                Swal.fire({
                    title: 'Submitting...',
                    html: '<div class="spinner-border text-primary"></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });
                
                try {
                    const response = await $.ajax({
                        url: apiUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    });
                    
                    if (response.status === 'success') {
                        Swal.close();
                        modal.modal('hide');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: response.message,
                            confirmButtonText: 'Continue'
                        });
                        
                        // Refresh assignments
                        showAssignmentsModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to submit assignment. Please try again.'
                    });
                }
            });
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
         * Show error message
         */
        function showError(message) {
            $('#lesson-content').html(`
                <div class="alert alert-danger m-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${message}
                    <button class="btn btn-sm btn-outline-danger ml-3" onclick="location.reload()">
                        <i class="fas fa-redo mr-1"></i> Refresh
                    </button>
                </div>
            `);
        }
    </script>
</body>
</html>
