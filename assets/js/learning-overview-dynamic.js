/**
 * Dynamic Learning Overview JavaScript
 * Real-time data fetching and visualization
 */

class DynamicLearningOverview {
    constructor() {
        this.userId = this.getUserId();
        this.overviewData = null;
        this.refreshInterval = null;
        this.charts = {};
        this.isRefreshing = false;
        
        this.init();
    }

    init() {
        console.log('Dynamic Learning Overview Initialized');
        this.setupEventListeners();
        this.loadOverviewData();
        this.startAutoRefresh();
        this.initializeCharts();
    }

    getUserId() {
        return document.querySelector('meta[name="user-id"]')?.getAttribute('content') || null;
    }

    setupEventListeners() {
        // Refresh button
        const refreshBtn = document.querySelector('.refresh-overview');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshOverview());
        }

        // Window focus/blur events for smart refreshing
        window.addEventListener('focus', () => {
            if (this.isPageVisible()) {
                this.loadOverviewData();
            }
        });

        window.addEventListener('blur', () => {
            this.pauseAutoRefresh();
        });

        // Visibility change for tab switching
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadOverviewData();
            }
        });
    }

    async loadOverviewData() {
        if (this.isRefreshing) return;
        
        this.isRefreshing = true;
        this.showLoadingState();

        try {
            const response = await fetch('../api/learning_overview.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.overviewData = data.data;
                this.updateOverviewUI();
                this.updateLastUpdateTime(data.timestamp);
                this.showNotification('Learning overview updated successfully', 'success');
            } else {
                throw new Error(data.message || 'Failed to load overview data');
            }
        } catch (error) {
            console.error('Error loading overview data:', error);
            this.showNotification('Failed to load learning overview', 'error');
            this.showFallbackData();
        } finally {
            this.isRefreshing = false;
            this.hideLoadingState();
        }
    }

    updateOverviewUI() {
        if (!this.overviewData) return;

        this.updateProfileStats();
        this.updateLearningVelocity();
        this.updateQuizPerformance();
        this.updateTimeInvestment();
        this.updateProgressDistribution();
        this.updateCategoryProgress();
        this.updateRecentActivity();
        this.updateAchievements();
        this.updateCharts();
    }

    updateProfileStats() {
        const profile = this.overviewData.user_profile;
        
        // Update total enrolled
        const totalEnrolled = document.getElementById('total-enrolled');
        if (totalEnrolled) {
            this.animateValue(totalEnrolled, 0, profile.total_enrolled || 0, 1000);
        }

        // Update completion rate
        const completionRate = document.getElementById('completion-rate');
        if (completionRate) {
            const rate = profile.total_enrolled > 0 ? 
                Math.round((profile.completed_courses / profile.total_enrolled) * 100) : 0;
            completionRate.textContent = `${rate}% completion rate`;
            
            // Update progress bar
            const progressBar = document.getElementById('profile-progress-fill');
            if (progressBar) {
                this.animateProgressBar(progressBar, rate);
            }
        }
    }

    updateLearningVelocity() {
        const profile = this.overviewData.user_profile;
        
        const velocity = document.getElementById('learning-velocity');
        if (velocity) {
            const velocityValue = profile.learning_velocity || 0;
            this.animateValue(velocity, 0, velocityValue, 1000, 1);
        }

        const velocityStatus = document.getElementById('velocity-status');
        if (velocityStatus) {
            const velocityValue = profile.learning_velocity || 0;
            let status = 'Starting';
            if (velocityValue >= 2) status = 'Excellent';
            else if (velocityValue >= 1) status = 'Good';
            else if (velocityValue >= 0.5) status = 'Average';
            
            velocityStatus.textContent = status;
        }

        // Update velocity progress bar
        const velocityProgressBar = document.getElementById('velocity-progress-fill');
        if (velocityProgressBar) {
            const velocityValue = Math.min((profile.learning_velocity || 0) * 50, 100); // Scale to 0-100
            this.animateProgressBar(velocityProgressBar, velocityValue);
        }
    }

    updateQuizPerformance() {
        const stats = this.overviewData.learning_stats?.quizzes;
        
        const successRate = document.getElementById('quiz-success-rate');
        if (successRate) {
            const rate = stats?.success_rate || 0;
            this.animateValue(successRate, 0, rate, 1000, 1);
        }

        const attempts = document.getElementById('quiz-attempts');
        if (attempts) {
            attempts.textContent = `${stats?.total_attempts || 0} attempts`;
        }

        // Update quiz progress bar
        const quizProgressBar = document.getElementById('quiz-progress-fill');
        if (quizProgressBar) {
            const rate = stats?.success_rate || 0;
            this.animateProgressBar(quizProgressBar, rate);
        }
    }

    updateTimeInvestment() {
        const timeStats = this.overviewData.learning_stats?.time;
        
        const totalHours = document.getElementById('total-hours');
        if (totalHours) {
            const hours = timeStats?.total_hours || 0;
            this.animateValue(totalHours, 0, hours, 1000, 1);
        }

        const efficiency = document.getElementById('learning-efficiency');
        if (efficiency) {
            const effValue = timeStats?.learning_efficiency || 0;
            efficiency.textContent = `Efficiency: ${effValue.toFixed(2)} courses/hour`;
        }

        // Update time progress bar
        const timeProgressBar = document.getElementById('time-progress-fill');
        if (timeProgressBar) {
            const hours = timeStats?.total_hours || 0;
            const progress = Math.min((hours / 100) * 100, 100); // Scale to 100 hours = 100%
            this.animateProgressBar(timeProgressBar, progress);
        }
    }

    updateProgressDistribution() {
        const distribution = this.overviewData.progress_metrics?.distribution;
        if (!distribution || distribution.length === 0) return;

        const container = document.getElementById('progress-distribution-chart');
        if (!container) return;

        let html = '<div class="progress-distribution">';
        distribution.forEach(item => {
            const bgColor = this.getProgressColor(item.range);
            html += `
                <div class="distribution-item mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">${item.range}</small>
                        <small class="text-muted">${item.count} courses (${item.percentage}%)</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${bgColor}" style="width: ${item.percentage}%"></div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    updateCategoryProgress() {
        const categories = this.overviewData.progress_metrics?.by_category;
        if (!categories || categories.length === 0) return;

        const container = document.getElementById('category-progress-chart');
        if (!container) return;

        let html = '<div class="category-progress">';
        categories.forEach(category => {
            html += `
                <div class="category-item mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>${category.category}</strong>
                        <small class="text-muted">${category.avg_progress}%</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: ${category.avg_progress}%"></div>
                    </div>
                    <small class="text-muted">
                        ${category.completed}/${category.enrolled} courses completed
                    </small>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    updateRecentActivity() {
        const activity = this.overviewData.progress_metrics?.recent_activity;
        if (!activity || activity.length === 0) return;

        const container = document.getElementById('recent-activity-chart');
        if (!container) return;

        let html = '<div class="recent-activity">';
        html += '<div class="activity-chart">';
        
        // Create a simple bar chart
        const maxLessons = Math.max(...activity.map(a => a.lessons_completed));
        
        activity.forEach(day => {
            const height = maxLessons > 0 ? (day.lessons_completed / maxLessons) * 100 : 0;
            const date = new Date(day.date);
            const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
            
            html += `
                <div class="activity-bar-container" style="flex: 1; text-align: center;">
                    <div class="activity-bar" style="height: 60px; display: flex; align-items: flex-end; justify-content: center;">
                        <div class="bar-fill bg-primary" style="width: 20px; height: ${height}%; background: linear-gradient(to top, #007bff, #0056b3); border-radius: 2px;"></div>
                    </div>
                    <small class="text-muted d-block mt-1">${dayName}</small>
                    <small class="text-muted">${day.lessons_completed}</small>
                </div>
            `;
        });
        
        html += '</div></div>';
        
        container.innerHTML = html;
    }

    updateAchievements() {
        const achievements = this.overviewData.achievements;
        if (!achievements || achievements.length === 0) return;

        const container = document.getElementById('achievements-container');
        if (!container) return;

        let html = '<div class="achievements-grid">';
        achievements.forEach(achievement => {
            const iconColor = this.getAchievementColor(achievement.type);
            html += `
                <div class="achievement-item">
                    <div class="achievement-icon ${iconColor}">
                        <i class="fas fa-${achievement.icon}"></i>
                    </div>
                    <div class="achievement-details">
                        <h6>${achievement.title}</h6>
                        <p class="text-muted small">${achievement.description}</p>
                        <small class="text-muted">Earned: ${this.formatDate(achievement.earned_at)}</small>
                    </div>
                    <div class="achievement-points">
                        <span class="badge bg-warning">${achievement.points} pts</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    updateCharts() {
        // Update any existing charts with new data
        this.updateProgressChart();
        this.updateActivityChart();
    }

    updateProgressChart() {
        // Implementation for progress chart updates
        // This would integrate with a charting library like Chart.js
    }

    updateActivityChart() {
        // Implementation for activity chart updates
        // This would integrate with a charting library like Chart.js
    }

    initializeCharts() {
        // Initialize any chart libraries
        this.loadChartLibrary();
    }

    loadChartLibrary() {
        // Load Chart.js or similar library if needed
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => this.setupCharts();
            document.head.appendChild(script);
        } else {
            this.setupCharts();
        }
    }

    setupCharts() {
        // Setup Chart.js charts
        this.setupProgressDistributionChart();
        this.setupActivityChart();
    }

    setupProgressDistributionChart() {
        const ctx = document.getElementById('progress-distribution-chart');
        if (!ctx || typeof Chart === 'undefined') return;

        const distribution = this.overviewData.progress_metrics?.distribution || [];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: distribution.map(d => d.range),
                datasets: [{
                    data: distribution.map(d => d.count),
                    backgroundColor: [
                        '#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    setupActivityChart() {
        const ctx = document.getElementById('recent-activity-chart');
        if (!ctx || typeof Chart === 'undefined') return;

        const activity = this.overviewData.progress_metrics?.recent_activity || [];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: activity.map(a => new Date(a.date).toLocaleDateString()),
                datasets: [{
                    label: 'Lessons Completed',
                    data: activity.map(a => a.lessons_completed),
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    refreshOverview() {
        this.loadOverviewData();
    }

    startAutoRefresh() {
        // Auto-refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            if (this.isPageVisible()) {
                this.loadOverviewData();
            }
        }, 300000); // 5 minutes
    }

    pauseAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    resumeAutoRefresh() {
        if (!this.refreshInterval) {
            this.startAutoRefresh();
        }
    }

    isPageVisible() {
        return !document.hidden && document.visibilityState === 'visible';
    }

    showLoadingState() {
        const refreshBtn = document.querySelector('.refresh-overview');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;
        }

        // Show loading indicators
        document.querySelectorAll('.stat-value').forEach(el => {
            el.classList.add('loading');
        });
    }

    hideLoadingState() {
        const refreshBtn = document.querySelector('.refresh-overview');
        if (refreshBtn) {
            refreshBtn.innerHTML = refreshBtn.dataset.originalText || '<i class="fas fa-sync-alt"></i>';
            refreshBtn.disabled = false;
        }

        // Hide loading indicators
        document.querySelectorAll('.stat-value').forEach(el => {
            el.classList.remove('loading');
        });
    }

    showFallbackData() {
        // Show fallback data when API fails
        const fallbackData = {
            user_profile: {
                total_enrolled: 0,
                completed_courses: 0,
                learning_velocity: 0
            },
            learning_stats: {
                quizzes: { success_rate: 0, total_attempts: 0 },
                time: { total_hours: 0, learning_efficiency: 0 }
            },
            progress_metrics: {
                distribution: [],
                by_category: [],
                recent_activity: []
            },
            achievements: []
        };

        this.overviewData = fallbackData;
        this.updateOverviewUI();
    }

    updateLastUpdateTime(timestamp) {
        const updateElement = document.getElementById('last-update');
        if (updateElement) {
            const now = new Date();
            const updateTime = new Date(timestamp);
            const diff = Math.floor((now - updateTime) / 1000);
            
            if (diff < 60) {
                updateElement.textContent = 'Just now';
            } else if (diff < 3600) {
                updateElement.textContent = `${Math.floor(diff / 60)} min ago`;
            } else {
                updateElement.textContent = updateTime.toLocaleTimeString();
            }
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    animateValue(element, start, end, duration, decimals = 0) {
        const startTime = performance.now();
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const value = start + (end - start) * this.easeOutQuad(progress);
            element.textContent = value.toFixed(decimals);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    animateProgressBar(element, percentage) {
        element.style.width = '0%';
        element.style.transition = 'width 1s ease-out';
        
        setTimeout(() => {
            element.style.width = `${percentage}%`;
        }, 100);
    }

    easeOutQuad(t) {
        return t * (2 - t);
    }

    getProgressColor(range) {
        if (range.includes('Excellent')) return 'bg-success';
        if (range.includes('Good')) return 'bg-info';
        if (range.includes('Average')) return 'bg-warning';
        if (range.includes('Below Average')) return 'bg-orange';
        return 'bg-danger';
    }

    getAchievementColor(type) {
        switch (type) {
            case 'milestone': return 'text-primary';
            case 'achievement': return 'text-success';
            case 'badge': return 'text-warning';
            default: return 'text-info';
        }
    }

    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

    destroy() {
        // Cleanup
        this.pauseAutoRefresh();
        // Destroy charts
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dynamicLearningOverview = new DynamicLearningOverview();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.dynamicLearningOverview) {
        window.dynamicLearningOverview.destroy();
    }
});
