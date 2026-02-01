/**
 * Enhanced Dashboard JavaScript
 * Interactive components for recommendation system and progress tracking
 */

class DashboardEnhanced {
    constructor() {
        this.init();
        this.setupEventListeners();
        this.startRealTimeUpdates();
        this.initializeTooltips();
        this.setupProgressAnimations();
    }

    init() {
        console.log('Enhanced Dashboard Initialized');
        this.userId = this.getUserId();
        this.updateInterval = null;
        this.progressData = new Map();
    }

    getUserId() {
        // Get user ID from session or meta tag
        return document.querySelector('meta[name="user-id"]')?.getAttribute('content') || null;
    }

    setupEventListeners() {
        // Course interaction logging
        document.addEventListener('click', (e) => {
            this.handleCourseInteraction(e);
        });

        // Recommendation refresh
        const refreshBtn = document.querySelector('.refresh-recommendations');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshRecommendations();
            });
        }

        // Progress detail modal
        document.addEventListener('click', (e) => {
            if (e.target.closest('.progress-details-btn')) {
                this.showProgressDetails(e.target.closest('.progress-details-btn'));
            }
        });

        // Learning path suggestions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.learning-path-btn')) {
                this.generateLearningPath(e.target.closest('.learning-path-btn'));
            }
        });

        // Skill gap analysis
        document.addEventListener('click', (e) => {
            if (e.target.closest('.skill-gap-btn')) {
                this.analyzeSkillGaps(e.target.closest('.skill-gap-btn'));
            }
        });
    }

    handleCourseInteraction(e) {
        const courseCard = e.target.closest('.course-card, .recommendation-card');
        if (!courseCard) return;

        const courseId = courseCard.dataset.courseId;
        if (!courseId) return;

        let interactionType = 'view';
        
        if (e.target.closest('.enroll-btn')) {
            interactionType = 'enroll';
        } else if (e.target.closest('.continue-btn')) {
            interactionType = 'lesson_complete';
        } else if (e.target.closest('.quiz-btn')) {
            interactionType = 'quiz_attempt';
        }

        this.logInteraction(courseId, interactionType);
    }

    async logInteraction(courseId, interactionType, value = 1.0) {
        if (!this.userId) return;

        try {
            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('interaction_type', interactionType);
            formData.append('interaction_value', value);

            const response = await fetch('../api/log_interaction.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                console.log(`Interaction logged: ${interactionType} for course ${courseId}`);
                
                // Update recommendations after interaction
                if (Math.random() < 0.3) { // 30% chance to update
                    this.refreshRecommendations();
                }
            }
        } catch (error) {
            console.error('Error logging interaction:', error);
        }
    }

    async refreshRecommendations() {
        const recommendationsContainer = document.querySelector('.recommendations-container');
        if (!recommendationsContainer) return;

        // Show loading state
        this.showLoadingState(recommendationsContainer);

        try {
            const response = await fetch(`../api/get_recommendations.php?limit=6&type=knn`);
            const data = await response.json();

            if (data.success) {
                this.renderRecommendations(data.recommendations, recommendationsContainer);
                this.showNotification('Recommendations updated!', 'success');
            } else {
                this.showNotification('Failed to update recommendations', 'error');
            }
        } catch (error) {
            console.error('Error refreshing recommendations:', error);
            this.showNotification('Error updating recommendations', 'error');
        }
    }

    renderRecommendations(recommendations, container) {
        const recommendationsHTML = recommendations.map((course, index) => `
            <div class="recommendation-card" data-course-id="${course.id}" style="animation-delay: ${index * 0.1}s">
                ${course.recommendation_score ? `
                    <div class="recommendation-score">
                        ${Math.round(course.recommendation_score * 100)}%
                    </div>
                ` : ''}
                
                <div class="recommendation-header">
                    <div>
                        <h6 class="recommendation-title">${this.escapeHtml(course.title)}</h6>
                        <p class="recommendation-description">
                            ${this.escapeHtml(course.description || '').substring(0, 120)}...
                        </p>
                    </div>
                </div>

                <div class="course-meta">
                    <span class="badge bg-primary">${this.escapeHtml(course.category_name || 'General')}</span>
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>${this.escapeHtml(course.instructor_name)}
                    </small>
                </div>

                ${course.recommendation_reason ? `
                    <div class="recommendation-reason">
                        <i class="fas fa-info-circle me-1"></i>
                        ${this.escapeHtml(course.recommendation_reason)}
                    </div>
                ` : ''}

                <div class="recommendation-action">
                    <a href="../course-details.php?id=${course.id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        `).join('');

        container.innerHTML = recommendationsHTML;
    }

    async showProgressDetails(button) {
        const courseId = button.dataset.courseId;
        if (!courseId) return;

        try {
            const response = await fetch(`../api/get_progress.php?course_id=${courseId}`);
            const data = await response.json();

            if (data.success) {
                this.renderProgressModal(data.progress, courseId);
            } else {
                this.showNotification('Failed to load progress details', 'error');
            }
        } catch (error) {
            console.error('Error loading progress details:', error);
            this.showNotification('Error loading progress details', 'error');
        }
    }

    renderProgressModal(progress, courseId) {
        const modalHTML = `
            <div class="modal fade" id="progressModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-chart-line me-2"></i>Progress Details
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="progress-circular">
                                        <svg width="120" height="120">
                                            <defs>
                                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                                    <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                                                </linearGradient>
                                            </defs>
                                            <circle class="progress-circular-circle" cx="60" cy="60" r="54"></circle>
                                            <circle class="progress-circular-progress" cx="60" cy="60" r="54"
                                                stroke-dasharray="${progress.completion_percentage * 3.39} 339"
                                                stroke-dashoffset="0"></circle>
                                        </svg>
                                        <div class="progress-circular-text">
                                            ${Math.round(progress.completion_percentage)}%
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="completion-probability">
                                        <div class="probability-indicator">
                                            <span class="probability-dot ${this.getProbabilityClass(progress.completion_probability)}"></span>
                                            <span>Completion Probability</span>
                                        </div>
                                        <strong>${Math.round(progress.completion_probability * 100)}%</strong>
                                    </div>
                                    
                                    ${progress.estimated_completion_time > 0 ? `
                                        <div class="estimated-time">
                                            <i class="fas fa-clock me-1"></i>
                                            Est. ${progress.estimated_completion_time} days to complete
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            ${progress.alerts && progress.alerts.length > 0 ? `
                                <div class="mt-4">
                                    <h6><i class="fas fa-bell me-2"></i>Progress Alerts</h6>
                                    <div class="progress-alerts">
                                        ${progress.alerts.map(alert => `
                                            <div class="progress-alert ${alert.type}">
                                                <i class="fas fa-${this.getAlertIcon(alert.type)} me-2"></i>
                                                <span>${this.escapeHtml(alert.message)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            ${progress.next_steps && progress.next_steps.length > 0 ? `
                                <div class="mt-4">
                                    <h6><i class="fas fa-tasks me-2"></i>Next Steps</h6>
                                    <div class="next-steps">
                                        ${progress.next_steps.map(step => `
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-${step.priority === 'high' ? 'danger' : 'warning'} me-2">
                                                    ${step.priority}
                                                </span>
                                                <span>${this.escapeHtml(step.action)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if present
        const existingModal = document.getElementById('progressModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('progressModal'));
        modal.show();
    }

    async generateLearningPath(button) {
        if (!this.userId) return;

        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';

            const response = await fetch(`../api/get_learning_path.php?user_id=${this.userId}`);
            const data = await response.json();

            if (data.success) {
                this.renderLearningPath(data.learning_path);
                this.showNotification('Learning path generated successfully!', 'success');
            } else {
                this.showNotification('Failed to generate learning path', 'error');
            }
        } catch (error) {
            console.error('Error generating learning path:', error);
            this.showNotification('Error generating learning path', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-route me-1"></i>Generate Path';
        }
    }

    renderLearningPath(learningPath) {
        const modalHTML = `
            <div class="modal fade" id="learningPathModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-route me-2"></i>${this.escapeHtml(learningPath.path_name)}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="learning-path-timeline">
                                ${learningPath.course_sequence.map((step, index) => `
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <span class="timeline-number">${index + 1}</span>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>${this.escapeHtml(step.course.title)}</h6>
                                            <p class="text-muted small">${this.escapeHtml(step.course.description || '').substring(0, 100)}...</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary">${this.escapeHtml(step.course.category_name)}</span>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>${step.estimated_duration} hours
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6><i class="fas fa-info-circle me-2"></i>Path Summary</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Total Duration:</strong> ${learningPath.estimated_duration} hours
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Difficulty:</strong> ${learningPath.difficulty_progression}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Courses:</strong> ${learningPath.course_sequence.length}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if present
        const existingModal = document.getElementById('learningPathModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('learningPathModal'));
        modal.show();
    }

    startRealTimeUpdates() {
        // Update progress every 30 seconds
        this.updateInterval = setInterval(() => {
            this.updateProgressIndicators();
        }, 30000);

        // Update recommendations every 5 minutes
        setInterval(() => {
            this.refreshRecommendations();
        }, 300000);
    }

    updateProgressIndicators() {
        const progressBars = document.querySelectorAll('.progress-bar-enhanced');
        progressBars.forEach(bar => {
            const currentWidth = parseFloat(bar.style.width) || 0;
            const courseId = bar.closest('[data-course-id]')?.dataset.courseId;
            
            if (courseId && this.progressData.has(courseId)) {
                const newWidth = this.progressData.get(courseId);
                this.animateProgressBar(bar, currentWidth, newWidth);
            }
        });
    }

    animateProgressBar(bar, fromWidth, toWidth) {
        const duration = 800;
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentWidth = fromWidth + (toWidth - fromWidth) * this.easeOutCubic(progress);
            bar.style.width = currentWidth + '%';

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    setupProgressAnimations() {
        // Animate progress bars on page load
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progressBar = entry.target.querySelector('.progress-bar-enhanced');
                    if (progressBar) {
                        const targetWidth = progressBar.style.width;
                        progressBar.style.width = '0%';
                        setTimeout(() => {
                            progressBar.style.width = targetWidth;
                        }, 100);
                    }
                }
            });
        });

        document.querySelectorAll('.progress-enhanced').forEach(el => {
            observer.observe(el);
        });
    }

    initializeTooltips() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add custom tooltips for progress indicators
        document.querySelectorAll('.completion-probability').forEach(el => {
            el.setAttribute('data-bs-toggle', 'tooltip');
            el.setAttribute('data-bs-placement', 'top');
            el.setAttribute('title', 'Based on your learning patterns and engagement');
        });

        document.querySelectorAll('.recommendation-score').forEach(el => {
            el.setAttribute('data-bs-toggle', 'tooltip');
            el.setAttribute('data-bs-placement', 'top');
            el.setAttribute('title', 'Match score based on your preferences');
        });
    }

    showLoadingState(container) {
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Updating recommendations...</p>
            </div>
        `;
    }

    showNotification(message, type = 'info') {
        const notificationHTML = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', notificationHTML);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const notification = document.querySelector('.alert:last-of-type');
            if (notification) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    getProbabilityClass(probability) {
        if (probability > 0.7) return 'high';
        if (probability > 0.4) return 'medium';
        return 'low';
    }

    getAlertIcon(type) {
        const icons = {
            success: 'check-circle',
            warning: 'exclamation-triangle',
            critical: 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardEnhanced = new DashboardEnhanced();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (window.dashboardEnhanced) {
        window.dashboardEnhanced.destroy();
    }
});
