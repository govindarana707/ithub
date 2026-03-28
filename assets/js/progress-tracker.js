/**
 * Progress Tracker JavaScript Library
 * Handles frontend progress tracking and communication with the API
 */

class ProgressTracker {
    constructor() {
        this.apiEndpoint = '../api/update_progress.php';
        this.csrfToken = null;
        this.isTracking = false;
        this.currentLesson = null;
        this.studyStartTime = null;
        this.videoElement = null;
        
        this.init();
    }
    
    init() {
        // Get CSRF token from meta tag or form
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                        || document.querySelector('input[name="csrf_token"]')?.value;
        
        // Auto-initialize if we're on a lesson page
        if (window.location.pathname.includes('lesson.php')) {
            this.initializeLessonTracking();
        }
    }
    
    /**
     * Initialize tracking for the current lesson
     */
    initializeLessonTracking() {
        const lessonId = this.getLessonId();
        if (!lessonId) return;
        
        this.currentLesson = lessonId;
        this.studyStartTime = Date.now();
        
        // Find video element
        this.videoElement = document.querySelector('video');
        if (this.videoElement) {
            this.setupVideoTracking();
        }
        
        // Start tracking study time
        this.startStudyTimeTracking();
        
        // Track page visibility
        this.setupVisibilityTracking();
        
        // Track page unload
        window.addEventListener('beforeunload', () => {
            this.saveProgress();
        });
    }
    
    /**
     * Get lesson ID from URL or page content
     */
    getLessonId() {
        // Try to get from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        let lessonId = urlParams.get('lesson_id');
        
        // Try to get from course_id if lesson_id not found
        if (!lessonId) {
            const courseId = urlParams.get('course_id');
            if (courseId) {
                // Get first lesson of the course
                lessonId = this.getFirstLessonId(courseId);
            }
        }
        
        // Try to get from page content
        if (!lessonId) {
            const lessonElement = document.querySelector('[data-lesson-id]');
            if (lessonElement) {
                lessonId = lessonElement.getAttribute('data-lesson-id');
            }
        }
        
        return lessonId ? parseInt(lessonId) : null;
    }
    
    /**
     * Get first lesson ID for a course
     */
    async getFirstLessonId(courseId) {
        try {
            const response = await fetch(`../api/get_course_lessons.php?course_id=${courseId}`);
            const data = await response.json();
            if (data.success && data.lessons.length > 0) {
                return data.lessons[0].id;
            }
        } catch (error) {
            console.error('Error fetching first lesson:', error);
        }
        return null;
    }
    
    /**
     * Setup video progress tracking
     */
    setupVideoTracking() {
        if (!this.videoElement) return;
        
        let lastUpdateTime = Date.now();
        let lastWatchTime = 0;
        
        // Update progress every 5 seconds
        const updateInterval = setInterval(() => {
            if (!this.isTracking) return;
            
            const currentTime = Date.now();
            const watchTime = this.videoElement.currentTime;
            const duration = this.videoElement.duration;
            
            // Only update if there's actual progress
            if (watchTime > lastWatchTime || currentTime - lastUpdateTime > 10000) {
                const completion = duration > 0 ? (watchTime / duration) * 100 : 0;
                
                this.updateVideoProgress(Math.floor(watchTime), completion);
                
                lastWatchTime = watchTime;
                lastUpdateTime = currentTime;
            }
        }, 5000);
        
        // Handle video events
        this.videoElement.addEventListener('ended', () => {
            this.updateVideoProgress(Math.floor(this.videoElement.duration), 100);
            this.markLessonComplete();
        });
        
        this.videoElement.addEventListener('pause', () => {
            this.saveProgress();
        });
        
        this.videoElement.addEventListener('play', () => {
            this.isTracking = true;
        });
    }
    
    /**
     * Setup visibility tracking
     */
    setupVisibilityTracking() {
        // Track when user switches tabs
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.isTracking = false;
                this.saveProgress();
            } else {
                this.isTracking = true;
                this.studyStartTime = Date.now();
            }
        });
    }
    
    /**
     * Start study time tracking
     */
    startStudyTimeTracking() {
        setInterval(() => {
            if (this.isTracking && this.currentLesson) {
                this.updateStudyTime(1); // Add 1 minute every minute
            }
        }, 60000); // Every minute
    }
    
    /**
     * Update video progress
     */
    async updateVideoProgress(watchTime, completion) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_video_progress',
                    lesson_id: this.currentLesson,
                    watch_time: watchTime,
                    completion: completion.toFixed(2),
                    csrf_token: this.csrfToken
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.updateProgressUI(result.course_progress);
            }
        } catch (error) {
            console.error('Error updating video progress:', error);
        }
    }
    
    /**
     * Update study time
     */
    async updateStudyTime(additionalMinutes) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_study_time',
                    lesson_id: this.currentLesson,
                    additional_minutes: additionalMinutes,
                    csrf_token: this.csrfToken
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.updateProgressUI(result.course_progress);
            }
        } catch (error) {
            console.error('Error updating study time:', error);
        }
    }
    
    /**
     * Mark lesson as complete
     */
    async markLessonComplete() {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mark_lesson_complete',
                    lesson_id: this.currentLesson,
                    csrf_token: this.csrfToken
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.updateProgressUI(result.course_progress);
                this.showCompletionMessage();
            }
        } catch (error) {
            console.error('Error marking lesson complete:', error);
        }
    }
    
    /**
     * Update comprehensive lesson progress
     */
    async updateLessonProgress(progressData) {
        try {
            const formData = new URLSearchParams({
                action: 'update_lesson_progress',
                lesson_id: this.currentLesson,
                csrf_token: this.csrfToken
            });
            
            // Add progress data
            Object.keys(progressData).forEach(key => {
                formData.append(key, progressData[key]);
            });
            
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                this.updateProgressUI(result.course_progress);
            }
        } catch (error) {
            console.error('Error updating lesson progress:', error);
        }
    }
    
    /**
     * Save current progress
     */
    saveProgress() {
        if (!this.currentLesson) return;
        
        const progressData = {};
        
        // Add video progress if available
        if (this.videoElement) {
            progressData.video_watch_time = Math.floor(this.videoElement.currentTime);
            progressData.video_completion = (this.videoElement.currentTime / this.videoElement.duration) * 100;
        }
        
        // Calculate study time
        if (this.studyStartTime) {
            const studyMinutes = Math.floor((Date.now() - this.studyStartTime) / 60000);
            if (studyMinutes > 0) {
                progressData.time_spent = studyMinutes;
            }
        }
        
        if (Object.keys(progressData).length > 0) {
            this.updateLessonProgress(progressData);
        }
    }
    
    /**
     * Update progress UI elements
     */
    updateProgressUI(courseProgress) {
        // Update progress bars
        const progressBars = document.querySelectorAll('.course-progress-bar');
        progressBars.forEach(bar => {
            bar.style.width = `${courseProgress}%`;
            bar.textContent = `${courseProgress}%`;
        });
        
        // Update progress text
        const progressTexts = document.querySelectorAll('.progress-text');
        progressTexts.forEach(text => {
            text.textContent = `${courseProgress}% Complete`;
        });
        
        // Update progress circles
        const progressCircles = document.querySelectorAll('.progress-circle');
        progressCircles.forEach(circle => {
            const percentage = courseProgress;
            const circumference = 2 * Math.PI * 45; // Assuming radius of 45
            const offset = circumference - (percentage / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        });
    }
    
    /**
     * Show completion message
     */
    showCompletionMessage() {
        const message = document.createElement('div');
        message.className = 'alert alert-success position-fixed top-0 end-0 m-3';
        message.style.zIndex = '9999';
        message.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>Lesson Completed!</strong> Great job on finishing this lesson.
        `;
        
        document.body.appendChild(message);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            message.remove();
        }, 5000);
    }
    
    /**
     * Get student progress statistics
     */
    async getStudentStats() {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_student_stats',
                    csrf_token: this.csrfToken
                })
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error getting student stats:', error);
            return null;
        }
    }
    
    /**
     * Get course progress details
     */
    async getCourseProgressDetails(courseId) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_course_progress',
                    course_id: courseId,
                    csrf_token: this.csrfToken
                })
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error getting course progress:', error);
            return null;
        }
    }
}

// Initialize progress tracker when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.progressTracker = new ProgressTracker();
});

// Export for global access
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProgressTracker;
}
