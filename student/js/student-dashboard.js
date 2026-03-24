/**
 * Student Dashboard JavaScript
 * Handles all AJAX interactions, UI updates, and SweetAlert2 dialogs
 */

$(document).ready(function() {
    'use strict';

    // Initialize dashboard
    initDashboard();

    // Navigation handling
    handleNavigation();

    // Initialize components
    initLogoutConfirm();
    initGlobalSearch();
    initFilters();
    initAssignmentModal();

    // Load initial data
    loadDashboardStats();
    loadNotifications();
    loadContinueLearning();
});

/**
 * Initialize dashboard
 */
function initDashboard() {
    // Toggle sidebar on mobile
    $('#toggle-sidebar').on('click', function() {
        $('#sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('expanded');
    });

    // Set active nav item from URL hash
    const hash = window.location.hash.replace('#', '') || 'dashboard';
    showSection(hash);
}

/**
 * Handle navigation
 */
function handleNavigation() {
    // Sidebar navigation
    $('[data-section]').on('click', function(e) {
        e.preventDefault();
        const section = $(this).data('section');
        showSection(section);
        
        // Update URL hash
        window.location.hash = section;
    });

    // Handle hash changes
    $(window).on('hashchange', function() {
        const hash = window.location.hash.replace('#', '') || 'dashboard';
        showSection(hash);
    });
}

/**
 * Show a specific section
 */
function showSection(sectionName) {
    // Update nav active state
    $('.nav-links li').removeClass('active');
    $(`.nav-links li a[data-section="${sectionName}"]`).closest('li').addClass('active');

    // Show/hide sections
    $('.content-section').removeClass('active');
    $(`#section-${sectionName}`).addClass('active');

    // Load section data
    switch (sectionName) {
        case 'dashboard':
            loadDashboardStats();
            loadContinueLearning();
            break;
        case 'courses':
            loadCourses();
            loadCategories();
            break;
        case 'my-courses':
            loadMyCourses();
            break;
        case 'progress':
            loadProgress();
            break;
        case 'assignments':
            loadAssignments();
            break;
        case 'notifications':
            loadAllNotifications();
            break;
    }
}

/**
 * Initialize logout confirmation
 */
function initLogoutConfirm() {
    $(document).on('click', '#logout-btn', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Logout Confirmation',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = $(this).attr('href');
            }
        });
    });
}

/**
 * Initialize global search
 */
function initGlobalSearch() {
    let searchTimeout;
    
    $('#global-search').on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 500);
    });
}

/**
 * Perform course search
 */
async function performSearch(query) {
    try {
        const response = await StudentAPI.searchCourses(query);
        
        if (response.status === 'success' && response.data.courses.length > 0) {
            showSection('courses');
            renderCourses(response.data.courses);
        } else {
            Swal.fire({
                icon: 'info',
                title: 'No Results',
                text: 'No courses found matching your search.',
                showConfirmButton: false,
                timer: 2000
            });
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

/**
 * Initialize filters
 */
function initFilters() {
    // Category filter
    $('#category-filter').on('change', function() {
        loadCourses();
    });

    // Difficulty filter
    $('#difficulty-filter').on('change', function() {
        loadCourses();
    });

    // Status filter
    $('#status-filter').on('change', function() {
        loadMyCourses();
    });
}

/**
 * Load dashboard statistics
 */
async function loadDashboardStats() {
    try {
        const stats = await StudentAPI.getDashboardStats();
        
        // Update stat cards with animation
        animateCounter('#stat-enrolled', stats.enrolled_courses || 0);
        animateCounter('#stat-completed', stats.completed_courses || 0);
        animateCounter('#stat-progress', stats.in_progress_courses || 0);
        
        const hours = Math.floor((stats.total_study_minutes || 0) / 60);
        animateCounter('#stat-hours', hours);
        
        // Update notification badge
        if (stats.unread_notifications > 0) {
            $('#notification-count, #header-notification-count')
                .text(stats.unread_notifications)
                .show();
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

/**
 * Animate counter
 */
function animateCounter(element, targetValue) {
    $(element).prop('Counter', 0).animate({
        Counter: targetValue
    }, {
        duration: 1000,
        step: function (now) {
            $(this).text(Math.ceil(now));
        }
    });
}

/**
 * Load notifications (header dropdown)
 */
async function loadNotifications() {
    try {
        const response = await StudentAPI.getNotifications();
        
        if (response.status === 'success') {
            const { notifications, unread_count } = response.data;
            
            // Update badge
            if (unread_count > 0) {
                $('#notification-count, #header-notification-count')
                    .text(unread_count)
                    .show();
            }
            
            // Render notification list
            renderNotificationList(notifications.slice(0, 5));
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

/**
 * Render notification list
 */
function renderNotificationList(notifications) {
    const container = $('#notification-list');
    
    if (notifications.length === 0) {
        container.html('<div class="dropdown-item text-muted">No notifications</div>');
        return;
    }
    
    container.html(notifications.map(n => `
        <a class="dropdown-item ${n.is_read ? '' : 'bg-light'}" href="#" data-notification-id="${n.id}">
            <div class="d-flex align-items-center">
                <div class="notification-icon mr-2">
                    <i class="fas fa-${getNotificationIcon(n.notification_type)} text-${getNotificationColor(n.notification_type)}"></i>
                </div>
                <div class="notification-content">
                    <small class="font-weight-bold">${n.title}</small>
                    <small class="d-block text-muted">${truncate(n.message, 50)}</small>
                </div>
            </div>
        </a>
    `).join(''));
    
    // Mark as read on click
    container.find('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('notification-id');
        markNotificationAsRead(id);
    });
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'bell';
}

/**
 * Get notification color based on type
 */
function getNotificationColor(type) {
    const colors = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'primary'
    };
    return colors[type] || 'secondary';
}

/**
 * Mark notification as read
 */
async function markNotificationAsRead(notificationId) {
    try {
        await StudentAPI.markNotificationRead(notificationId);
        loadNotifications();
    } catch (error) {
        console.error('Error marking notification:', error);
    }
}

/**
 * Load continue learning section
 */
async function loadContinueLearning() {
    const container = $('#continue-learning');
    
    try {
        const courses = await StudentAPI.getMyCourses({ status: 'active' });
        
        if (courses.courses && courses.courses.length > 0) {
            container.html(courses.courses.slice(0, 3).map(course => createCourseCardHTML(course, true)).join(''));
            
            // Initialize enroll button handlers
            StudentAPI.initEnrollButtons();
        } else {
            container.html(`
                <div class="empty-state text-center py-4">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No courses in progress. Start learning today!</p>
                    <a href="#courses" class="btn btn-primary" data-section="courses">
                        <i class="fas fa-search mr-1"></i> Browse Courses
                    </a>
                </div>
            `);
        }
    } catch (error) {
        container.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading courses. Please refresh the page.
            </div>
        `);
    }
}

/**
 * Create course card HTML
 */
function createCourseCardHTML(course, showProgress = false) {
    return `
        <div class="continue-card card mb-3">
            <div class="row no-gutters">
                <div class="col-md-4">
                    <img src="${course.thumbnail || 'https://via.placeholder.com/300x200?text=Course'}" 
                         class="card-img" alt="${course.title}">
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title">${course.title}</h5>
                        <p class="card-text text-muted small">
                            <i class="fas fa-user mr-1"></i> ${course.instructor_name}
                            <span class="mx-2">|</span>
                            <i class="fas fa-book mr-1"></i> ${course.completed_lessons || 0}/${course.total_lessons || 0} lessons
                        </p>
                        ${showProgress ? `
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: ${course.progress_percentage || 0}%"></div>
                            </div>
                            <small class="text-muted">${course.progress_percentage || 0}% complete</small>
                        ` : ''}
                        <div class="mt-3">
                            <a href="lesson.php?course_id=${course.id}" class="btn btn-success btn-sm">
                                <i class="fas fa-play mr-1"></i> Continue
                            </a>
                            <a href="view-course.php?course_id=${course.id}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-info-circle mr-1"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Load categories for filter
 */
async function loadCategories() {
    try {
        const response = await StudentAPI.getCategories();
        
        if (response.status === 'success') {
            const options = response.data.map(cat => 
                `<option value="${cat.id}">${cat.name}</option>`
            ).join('');
            
            $('#category-filter').append(options);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Load courses
 */
async function loadCourses(page = 1) {
    const container = $('#course-grid');
    container.html(`
        <div class="text-center py-5 col-12">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading courses...</p>
        </div>
    `);
    
    try {
        const params = {
            page: page,
            limit: 12
        };
        
        const categoryId = $('#category-filter').val();
        const difficulty = $('#difficulty-filter').val();
        
        if (categoryId) params.category_id = categoryId;
        if (difficulty) params.difficulty = difficulty;
        
        const courses = await StudentAPI.getCourses(params);
        
        if (courses.courses && courses.courses.length > 0) {
            renderCourses(courses.courses);
            renderPagination(courses.pagination, 'course-pagination', loadCourses);
        } else {
            container.html(`
                <div class="empty-state text-center py-5 col-12">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No courses found. Try adjusting your filters.</p>
                </div>
            `);
        }
    } catch (error) {
        container.html(`
            <div class="alert alert-danger col-12">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading courses. Please try again.
            </div>
        `);
    }
}

/**
 * Render courses grid
 */
function renderCourses(courses) {
    const container = $('#course-grid');
    
    container.html(courses.map(course => `
        <div class="course-card-wrapper">
            <div class="card course-card h-100 shadow-sm">
                <div class="card-img-wrapper">
                    <img src="${course.thumbnail || 'https://via.placeholder.com/300x200?text=Course'}" 
                         class="card-img-top" alt="${course.title}"
                         style="height: 160px; object-fit: cover;">
                    <span class="badge badge-primary position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                        ${course.difficulty_level || 'Beginner'}
                    </span>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-truncate" title="${course.title}">${course.title}</h5>
                    <p class="card-text small text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${course.description || 'No description available'}
                    </p>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span><i class="fas fa-user mr-1"></i> ${course.instructor_name || 'Unknown'}</span>
                        <span><i class="fas fa-users mr-1"></i> ${course.enrollment_count || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge badge-info">${course.duration_hours || 0}h</span>
                        <span class="text-primary font-weight-bold">$${course.price || 0}</span>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary btn-sm btn-block view-course-btn" data-course-id="${course.id}">
                        <i class="fas fa-eye mr-1"></i> View Course
                    </button>
                </div>
            </div>
        </div>
    `).join(''));
    
    // Initialize course view button
    initCourseViewButtons();
}

/**
 * Initialize course view buttons
 */
function initCourseViewButtons() {
    $(document).on('click', '.view-course-btn', function() {
        const courseId = $(this).data('course-id');
        showCourseModal(courseId);
    });
}

/**
 * Show course details modal
 */
async function showCourseModal(courseId) {
    const modal = $('#course-modal');
    const body = $('#course-modal-body');
    
    body.html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading course details...</p>
        </div>
    `);
    
    modal.modal('show');
    
    try {
        const response = await StudentAPI.getCourseDetails(courseId);
        const course = response.data;
        
        body.html(`
            <div class="course-detail-content">
                <img src="${course.thumbnail || 'https://via.placeholder.com/800x300?text=Course'}" 
                     class="img-fluid rounded mb-4 w-100" style="max-height: 300px; object-fit: cover;">
                
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4>${course.title}</h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-user mr-1"></i> ${course.instructor_name}
                            <span class="mx-2">|</span>
                            <i class="fas fa-folder mr-1"></i> ${course.category_name}
                        </p>
                    </div>
                    <span class="badge badge-primary">${course.difficulty_level}</span>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-book fa-2x text-primary mb-2"></i>
                            <p class="mb-0 font-weight-bold">${course.lesson_count}</p>
                            <small class="text-muted">Lessons</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-clock fa-2x text-info mb-2"></i>
                            <p class="mb-0 font-weight-bold">${course.duration_hours}h</p>
                            <small class="text-muted">Duration</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-users fa-2x text-success mb-2"></i>
                            <p class="mb-0 font-weight-bold">${course.enrollment_count}</p>
                            <small class="text-muted">Students</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <p class="mb-0 font-weight-bold">${course.avg_rating || 0}</p>
                            <small class="text-muted">Rating</small>
                        </div>
                    </div>
                </div>
                
                <h5>Description</h5>
                <p>${course.description || 'No description available.'}</p>
                
                <div class="mt-4">
                    ${course.is_enrolled ? `
                        <a href="view-course.php?course_id=${course.id}" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-play mr-2"></i> Continue Learning
                        </a>
                    ` : `
                        <button class="btn btn-primary btn-lg btn-block enroll-btn" data-course-id="${course.id}">
                            <i class="fas fa-plus mr-2"></i> Enroll Now - $${course.price || 0}
                        </button>
                    `}
                </div>
            </div>
        `);
        
        // Initialize enroll button
        StudentAPI.initEnrollButtons();
        
    } catch (error) {
        body.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading course details.
            </div>
        `);
    }
}

/**
 * Load my courses
 */
async function loadMyCourses(page = 1) {
    const container = $('#my-courses-grid');
    container.html(`
        <div class="text-center py-5 col-12">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading your courses...</p>
        </div>
    `);
    
    try {
        const params = {
            page: page,
            limit: 12
        };
        
        const status = $('#status-filter').val();
        if (status) params.status = status;
        
        const courses = await StudentAPI.getMyCourses(params);
        
        if (courses.courses && courses.courses.length > 0) {
            container.html(courses.courses.map(course => createMyCourseCard(course)).join(''));
            renderPagination(courses.pagination, 'my-courses-pagination', loadMyCourses);
        } else {
            container.html(`
                <div class="empty-state text-center py-5 col-12">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <p class="text-muted">You haven't enrolled in any courses yet.</p>
                    <a href="#courses" class="btn btn-primary" data-section="courses">
                        <i class="fas fa-search mr-1"></i> Browse Courses
                    </a>
                </div>
            `);
        }
    } catch (error) {
        container.html(`
            <div class="alert alert-danger col-12">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading courses. Please try again.
            </div>
        `);
    }
}

/**
 * Create my course card
 */
function createMyCourseCard(course) {
    const isCompleted = course.enrollment_status === 'completed';
    const progressColor = isCompleted ? 'bg-success' : 'bg-primary';
    
    return `
        <div class="course-card-wrapper">
            <div class="card course-card h-100 shadow-sm">
                <div class="card-img-wrapper">
                    <img src="${course.thumbnail || 'https://via.placeholder.com/300x200?text=Course'}" 
                         class="card-img-top" alt="${course.title}"
                         style="height: 160px; object-fit: cover;">
                    ${isCompleted ? `
                        <span class="badge badge-success position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                            <i class="fas fa-check mr-1"></i> Completed
                        </span>
                    ` : ''}
                </div>
                <div class="card-body">
                    <h5 class="card-title text-truncate" title="${course.title}">${course.title}</h5>
                    <p class="card-text small text-muted mb-2">
                        <i class="fas fa-user mr-1"></i> ${course.instructor_name}
                    </p>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar ${progressColor}" role="progressbar" 
                             style="width: ${course.progress_percentage || 0}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">${course.completed_lessons || 0}/${course.total_lessons || 0} lessons</span>
                        <span class="font-weight-bold">${course.progress_percentage || 0}%</span>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="lesson.php?course_id=${course.id}" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-play mr-1"></i> ${isCompleted ? 'Review' : 'Continue'}
                    </a>
                </div>
            </div>
        </div>
    `;
}

/**
 * Load progress section
 */
async function loadProgress() {
    const container = $('#progress-overview');
    
    try {
        const progress = await StudentAPI.getProgress();
        
        container.html(`
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Overall Progress</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-trophy mr-2"></i>Achievements</h5>
                        </div>
                        <div class="card-body">
                            <div class="achievement-list">
                                <div class="achievement-item">
                                    <i class="fas fa-graduation-cap text-primary"></i>
                                    <span>Enrolled in ${progress.stats.total_enrolled || 0} courses</span>
                                </div>
                                <div class="achievement-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Completed ${progress.stats.total_completed || 0} courses</span>
                                </div>
                                <div class="achievement-item">
                                    <i class="fas fa-book-open text-info"></i>
                                    <span>${progress.stats.avg_progress || 0}% average progress</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
    } catch (error) {
        container.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading progress. Please try again.
            </div>
        `);
    }
}

/**
 * Load assignments
 */
async function loadAssignments() {
    const container = $('#assignments-container');
    container.html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading assignments...</p>
        </div>
    `);
    
    try {
        const response = await StudentAPI.getSubmissions();
        
        if (response.status === 'success' && response.data.length > 0) {
            container.html(response.data.map(sub => createAssignmentItem(sub)).join(''));
        } else {
            container.html(`
                <div class="empty-state text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No assignments yet. Complete lessons to see assignments here.</p>
                </div>
            `);
        }
    } catch (error) {
        container.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading assignments. Please try again.
            </div>
        `);
    }
}

/**
 * Create assignment item
 */
function createAssignmentItem(submission) {
    let statusClass = 'secondary';
    let statusIcon = 'clock';
    
    if (submission.status === 'graded') {
        statusClass = 'success';
        statusIcon = 'check-circle';
    } else if (submission.status === 'submitted') {
        statusClass = 'info';
        statusIcon = 'inbox';
    } else if (submission.is_late) {
        statusClass = 'warning';
        statusIcon = 'exclamation-triangle';
    }
    
    return `
        <div class="submission-item card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">${submission.assignment_title}</h5>
                        <p class="text-muted small mb-1">
                            <i class="fas fa-book mr-1"></i> ${submission.course_title}
                        </p>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-calendar mr-1"></i> Due: ${StudentAPI.formatDate(submission.due_date)}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-${statusClass}">
                            <i class="fas fa-${statusIcon} mr-1"></i>
                            ${submission.status === 'graded' ? `Score: ${submission.points_earned}/${submission.points_possible}` : submission.status}
                        </span>
                        ${submission.is_late ? '<br><span class="badge badge-warning mt-1">Late</span>' : ''}
                    </div>
                </div>
                ${submission.feedback ? `
                    <div class="feedback-box mt-3 p-3 bg-light rounded">
                        <h6><i class="fas fa-comment mr-1"></i> Instructor Feedback:</h6>
                        <p class="mb-0">${submission.feedback}</p>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Load all notifications
 */
async function loadAllNotifications() {
    const container = $('#notifications-container');
    container.html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading notifications...</p>
        </div>
    `);
    
    try {
        const response = await StudentAPI.getNotifications();
        
        if (response.status === 'success' && response.data.notifications.length > 0) {
            container.html(`
                <button class="btn btn-outline-primary mb-3" id="mark-all-read">
                    <i class="fas fa-check-double mr-1"></i> Mark All as Read
                </button>
                <div class="notification-list">
                    ${response.data.notifications.map(n => createNotificationItem(n)).join('')}
                </div>
            `);
            
            // Mark all as read
            $('#mark-all-read').on('click', async function() {
                try {
                    await StudentAPI.markNotificationRead('all');
                    loadAllNotifications();
                    loadDashboardStats();
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        } else {
            container.html(`
                <div class="empty-state text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No notifications to show.</p>
                </div>
            `);
        }
    } catch (error) {
        container.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading notifications. Please try again.
            </div>
        `);
    }
}

/**
 * Create notification item
 */
function createNotificationItem(notification) {
    return `
        <div class="notification-item card mb-2 ${notification.is_read ? '' : 'border-left-primary'}">
            <div class="card-body py-2">
                <div class="d-flex align-items-center">
                    <div class="notification-icon mr-3">
                        <i class="fas fa-${getNotificationIcon(notification.notification_type)} fa-lg text-${getNotificationColor(notification.notification_type)}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${notification.title}</h6>
                        <p class="mb-1 text-muted small">${notification.message}</p>
                        <small class="text-muted">${StudentAPI.formatDate(notification.created_at)}</small>
                    </div>
                    ${!notification.is_read ? `
                        <button class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="${notification.id}">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

/**
 * Initialize assignment modal
 */
function initAssignmentModal() {
    $(document).on('click', '.submit-assignment-btn', function() {
        const assignmentId = $(this).data('assignment-id');
        showAssignmentModal(assignmentId);
    });
}

/**
 * Show assignment submission modal
 */
async function showAssignmentModal(assignmentId) {
    const modal = $('#assignment-modal');
    const body = $('#assignment-modal-body');
    
    body.html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading assignment...</p>
        </div>
    `);
    
    modal.modal('show');
    
    // For now, show a simple form
    body.html(`
        <form id="assignment-form">
            <input type="hidden" name="assignment_id" value="${assignmentId}">
            
            <div class="form-group">
                <label for="text-content">Your Answer</label>
                <textarea class="form-control" id="text-content" name="text_content" rows="8" 
                          placeholder="Enter your answer here..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="file-upload">Upload File (Optional)</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="file-upload" name="file">
                    <label class="custom-file-label" for="file-upload">Choose file</label>
                </div>
                <small class="text-muted">Allowed: PDF, DOC, DOCX, TXT, PNG, JPG (Max 10MB)</small>
            </div>
            
            <div class="text-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload mr-1"></i> Submit Assignment
                </button>
            </div>
        </form>
    `);
    
    // Initialize file input
    $('.custom-file-input').on('change', function() {
        $(this).next('.custom-file-label').text($(this).val());
    });
    
    // Handle form submission
    $('#assignment-form').on('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            await StudentAPI.submitAssignment(
                formData.get('assignment_id'),
                formData.get('text_content'),
                formData.get('file') || null
            );
            
            modal.modal('hide');
            loadAssignments();
        } catch (error) {
            if (error.status !== 'cancelled') {
                console.error('Submission error:', error);
            }
        }
    });
}

/**
 * Render pagination
 */
function renderPagination(pagination, containerId, callback) {
    const container = $(`#${containerId}`);
    
    if (pagination.total_pages <= 1) {
        container.empty();
        return;
    }
    
    let html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    html += `
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    html += `
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
        </li>
    `;
    
    html += '</ul></nav>';
    container.html(html);
    
    // Handle click
    container.find('.page-link').on('click', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) callback(page);
    });
}

/**
 * Truncate text
 */
function truncate(str, length) {
    if (str.length <= length) return str;
    return str.substring(0, length) + '...';
}
