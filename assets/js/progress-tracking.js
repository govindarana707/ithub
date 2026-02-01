/**
 * Progress Tracking System
 * Handles real-time progress tracking for students
 */
class ProgressTracker {
    constructor() {
        this.apiBase = '../api/progress_tracking.php';
        this.currentSession = null;
        this.videoTrackingInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadOverallProgress();
        this.startSessionTracking();
    }

    setupEventListeners() {
        // Track video progress
        document.addEventListener('DOMContentLoaded', () => {
            const videos = document.querySelectorAll('video');
            videos.forEach(video => this.setupVideoTracking(video));
        });

        // Track page visibility for session tracking
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseTracking();
            } else {
                this.resumeTracking();
            }
        });

        // Track before page unload
        window.addEventListener('beforeunload', () => {
            this.endStudySession();
        });
    }

    setupVideoTracking(video) {
        const courseId = video.dataset.courseId;
        const lessonId = video.dataset.lessonId;

        if (!courseId || !lessonId) return;

        let lastUpdateTime = Date.now();
        let accumulatedTime = 0;

        video.addEventListener('play', () => {
            this.startStudySession(courseId, lessonId, 'video');
            lastUpdateTime = Date.now();
        });

        video.addEventListener('pause', () => {
            this.updateVideoProgress(courseId, lessonId, video.currentTime, video.duration);
            accumulatedTime += (Date.now() - lastUpdateTime) / 1000 / 60; // Convert to minutes
        });

        video.addEventListener('timeupdate', () => {
            // Update progress every 5 seconds
            if (Date.now() - lastUpdateTime > 5000) {
                this.updateVideoProgress(courseId, lessonId, video.currentTime, video.duration);
                lastUpdateTime = Date.now();
            }
        });

        video.addEventListener('ended', () => {
            this.markLessonCompleted(courseId, lessonId);
            this.endStudySession();
        });
    }

    async startStudySession(courseId, lessonId, activityType = 'reading') {
        try {
            const response = await this.apiCall('POST', 'start_study_session', {
                course_id: courseId,
                lesson_id: lessonId,
                activity_type: activityType
            });

            if (response.success) {
                this.currentSession = {
                    courseId,
                    lessonId,
                    activityType,
                    startTime: Date.now()
                };
                console.log('Study session started');
            }
        } catch (error) {
            console.error('Error starting study session:', error);
        }
    }

    async endStudySession() {
        if (!this.currentSession) return;

        try {
            const response = await this.apiCall('PUT', 'end_study_session', {
                watch_percentage: this.getCurrentWatchPercentage()
            });

            if (response.success) {
                console.log(`Study session ended. Duration: ${response.duration_minutes} minutes`);
                this.currentSession = null;
            }
        } catch (error) {
            console.error('Error ending study session:', error);
        }
    }

    async updateVideoProgress(courseId, lessonId, currentTime, duration) {
        try {
            const timeSpent = this.currentSession ? 
                (Date.now() - this.currentSession.startTime) / 1000 / 60 : 0;

            const response = await this.apiCall('PUT', 'update_video_progress', {
                course_id: courseId,
                lesson_id: lessonId,
                current_time: currentTime,
                duration: duration,
                time_spent_minutes: Math.round(timeSpent)
            });

            if (response.success) {
                this.updateProgressBar(response.progress_percentage || 0);
            }
        } catch (error) {
            console.error('Error updating video progress:', error);
        }
    }

    async markLessonCompleted(courseId, lessonId) {
        try {
            const response = await this.apiCall('POST', 'mark_lesson_completed', {
                course_id: courseId,
                lesson_id: lessonId
            });

            if (response.success) {
                this.showCompletionNotification();
                this.updateCourseProgress(courseId);
            }
        } catch (error) {
            console.error('Error marking lesson as completed:', error);
        }
    }

    async loadOverallProgress() {
        try {
            const response = await this.apiCall('GET', 'overall_progress');
            
            if (response.success) {
                this.renderProgressOverview(response.progress);
            }
        } catch (error) {
            console.error('Error loading overall progress:', error);
        }
    }

    async loadCourseProgress(courseId) {
        try {
            const response = await this.apiCall('GET', 'course_progress', { course_id: courseId });
            
            if (response.success) {
                this.renderCourseProgress(response.course_progress, response.lessons_progress);
            }
        } catch (error) {
            console.error('Error loading course progress:', error);
        }
    }

    async loadProgressStatistics() {
        try {
            const response = await this.apiCall('GET', 'statistics');
            
            if (response.success) {
                this.renderStatistics(response.statistics);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    renderProgressOverview(progress) {
        const container = document.getElementById('progress-overview');
        if (!container) return;

        let html = '<div class="progress-cards">';
        
        progress.forEach(course => {
            const statusClass = course.status === 'completed' ? 'completed' : 
                              course.status === 'in_progress' ? 'in-progress' : 'not-started';
            
            html += `
                <div class="progress-card ${statusClass}">
                    <h4>${course.title}</h4>
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${course.progress_percentage}%"></div>
                        </div>
                        <span class="progress-text">${Math.round(course.progress_percentage)}%</span>
                    </div>
                    <div class="progress-details">
                        <span>${course.lessons_completed}/${course.lessons_total} lessons</span>
                        <span>${this.formatTime(course.total_time_spent_minutes)}</span>
                    </div>
                    <button class="btn btn-primary" onclick="progressTracker.loadCourseProgress(${course.id})">
                        View Details
                    </button>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    renderCourseProgress(courseProgress, lessonsProgress) {
        const container = document.getElementById('course-progress-detail');
        if (!container) return;

        let html = `
            <div class="course-progress-header">
                <h3>Course Progress: ${Math.round(courseProgress.completion_percentage)}%</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${courseProgress.completion_percentage}%"></div>
                </div>
                <div class="progress-stats">
                    <span>${courseProgress.completed_lessons}/${courseProgress.total_lessons} lessons completed</span>
                    <span>${this.formatTime(courseProgress.total_time_spent_minutes)} total time</span>
                </div>
            </div>
            <div class="lessons-progress">
        `;

        lessonsProgress.forEach(lesson => {
            const isCompleted = lesson.is_completed;
            const watchPercentage = lesson.watch_percentage || 0;
            
            html += `
                <div class="lesson-progress-item ${isCompleted ? 'completed' : ''}">
                    <div class="lesson-info">
                        <h4>${lesson.title}</h4>
                        <span class="lesson-type">${lesson.lesson_type}</span>
                    </div>
                    <div class="lesson-progress-bar">
                        <div class="progress-fill" style="width: ${watchPercentage}%"></div>
                    </div>
                    <div class="lesson-details">
                        <span>${Math.round(watchPercentage)}% completed</span>
                        <span>${this.formatTime(lesson.time_spent_minutes)}</span>
                    </div>
                    ${!isCompleted ? `
                        <button class="btn btn-sm btn-primary" 
                                onclick="progressTracker.markLessonCompleted(${courseProgress.course_id}, ${lesson.id})">
                            Mark Complete
                        </button>
                    ` : '<span class="completed-badge">✓ Completed</span>'}
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    renderStatistics(stats) {
        const container = document.getElementById('progress-statistics');
        if (!container) return;

        const overall = stats.overall;
        const recentActivity = stats.recent_activity || [];
        const studyStreak = stats.study_streak || 0;

        let html = `
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>${overall.enrolled_courses}</h3>
                    <p>Enrolled Courses</p>
                </div>
                <div class="stat-card">
                    <h3>${overall.completed_courses}</h3>
                    <p>Completed Courses</p>
                </div>
                <div class="stat-card">
                    <h3>${Math.round(overall.average_progress)}%</h3>
                    <p>Average Progress</p>
                </div>
                <div class="stat-card">
                    <h3>${studyStreak}</h3>
                    <p>Day Study Streak</p>
                </div>
            </div>
            
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <div class="activity-list">
        `;

        recentActivity.forEach(activity => {
            html += `
                <div class="activity-item">
                    <span class="activity-title">${activity.course_title}</span>
                    <span class="activity-progress">${Math.round(activity.completion_percentage)}%</span>
                    <span class="activity-date">${this.formatDate(activity.last_activity_at)}</span>
                </div>
            `;
        });

        html += '</div></div>';
        container.innerHTML = html;
    }

    updateProgressBar(percentage) {
        const progressBars = document.querySelectorAll('.lesson-progress-bar .progress-fill');
        progressBars.forEach(bar => {
            bar.style.width = `${percentage}%`;
        });
    }

    showCompletionNotification() {
        const notification = document.createElement('div');
        notification.className = 'progress-notification success';
        notification.innerHTML = `
            <div class="notification-content">
                <h4>🎉 Lesson Completed!</h4>
                <p>Great job! You've completed this lesson.</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    getCurrentWatchPercentage() {
        const video = document.querySelector('video');
        if (!video || !video.duration) return 0;
        
        return (video.currentTime / video.duration) * 100;
    }

    formatTime(minutes) {
        if (!minutes) return '0 min';
        
        const hours = Math.floor(minutes / 60);
        const mins = Math.round(minutes % 60);
        
        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        
        return date.toLocaleDateString();
    }

    async apiCall(method, action, data = {}) {
        const url = new URL(this.apiBase);
        url.searchParams.set('action', action);

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (method !== 'GET' && Object.keys(data).length > 0) {
            options.body = JSON.stringify(data);
        } else if (method === 'GET' && Object.keys(data).length > 0) {
            Object.keys(data).forEach(key => {
                url.searchParams.set(key, data[key]);
            });
        }

        const response = await fetch(url.toString(), options);
        return await response.json();
    }

    pauseTracking() {
        if (this.videoTrackingInterval) {
            clearInterval(this.videoTrackingInterval);
        }
    }

    resumeTracking() {
        // Resume tracking if needed
    }

    startSessionTracking() {
        // Auto-save progress every 30 seconds
        setInterval(() => {
            if (this.currentSession) {
                this.saveCurrentProgress();
            }
        }, 30000);
    }

    async saveCurrentProgress() {
        if (!this.currentSession) return;

        const video = document.querySelector('video');
        if (video && !video.paused) {
            await this.updateVideoProgress(
                this.currentSession.courseId,
                this.currentSession.lessonId,
                video.currentTime,
                video.duration
            );
        }
    }
}

// Initialize the progress tracker
const progressTracker = new ProgressTracker();

// Export for use in other scripts
window.ProgressTracker = ProgressTracker;
window.progressTracker = progressTracker;
