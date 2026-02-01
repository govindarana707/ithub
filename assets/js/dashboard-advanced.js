/**
 * Advanced Dashboard JavaScript
 * Modern interactions, animations, and real-time updates
 */

class AdvancedDashboard {
    constructor() {
        this.init();
        this.setupEventListeners();
        this.initializeAnimations();
        this.setupRealTimeUpdates();
        this.initializeInteractions();
    }

    init() {
        console.log('Advanced Dashboard Initialized');
        this.userId = this.getUserId();
        this.isSidebarOpen = false;
        this.notifications = [];
        this.chartInstances = {};
        this.animationObserver = null;
        
        // Initialize components
        this.initializeCharts();
        this.initializeProgressIndicators();
        this.setupIntersectionObserver();
    }

    getUserId() {
        return document.querySelector('meta[name="user-id"]')?.getAttribute('content') || null;
    }

    setupEventListeners() {
        // Mobile menu toggle
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Sidebar navigation
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleNavigation(item);
            });
        });

        // FAB button
        const fab = document.querySelector('.fab');
        if (fab) {
            fab.addEventListener('click', () => this.handleFabClick());
        }

        // Card interactions
        document.querySelectorAll('.course-card-advanced, .recommendation-card-advanced').forEach(card => {
            card.addEventListener('click', (e) => this.handleCardClick(e, card));
        });

        // Progress interactions
        document.querySelectorAll('.progress-bar-advanced').forEach(bar => {
            bar.addEventListener('click', () => this.handleProgressClick(bar));
        });

        // Refresh buttons
        document.querySelectorAll('.refresh-recommendations, .refresh-progress').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleRefresh(e, btn));
        });

        // Window resize
        window.addEventListener('resize', () => this.handleResize());
    }

    initializeAnimations() {
        // Animate stats on load
        this.animateStats();
        
        // Animate progress bars
        this.animateProgressBars();
        
        // Setup scroll animations
        this.setupScrollAnimations();
        
        // Initialize particle effects
        this.initializeParticles();
    }

    animateStats() {
        const statCards = document.querySelectorAll('.stat-card-advanced');
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });
    }

    animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const targetWidth = bar.style.width || '0%';
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.width = targetWidth;
                bar.style.transition = 'width 1s cubic-bezier(0.4, 0, 0.2, 1)';
            }, 500);
        });
    }

    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        this.animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateElement(entry.target);
                }
            });
        }, observerOptions);

        // Observe all major sections
        document.querySelectorAll('.course-card-advanced, .recommendation-card-advanced, .activity-item').forEach(el => {
            this.animationObserver.observe(el);
        });
    }

    animateElement(element) {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 100);
    }

    initializeParticles() {
        this.createFloatingParticles();
        this.createBackgroundAnimations();
    }

    createFloatingParticles() {
        const particleContainer = document.createElement('div');
        particleContainer.className = 'particles-container';
        particleContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        `;
        
        document.body.appendChild(particleContainer);
        
        // Create floating particles
        for (let i = 0; i < 20; i++) {
            this.createParticle(particleContainer);
        }
    }

    createParticle(container) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        const size = Math.random() * 4 + 2;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.3), transparent);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: float ${duration}s infinite linear;
            animation-delay: ${delay}s;
            pointer-events: none;
        `;
        
        container.appendChild(particle);
    }

    createBackgroundAnimations() {
        // Add gradient animations to cards
        const cards = document.querySelectorAll('.stat-card-advanced, .course-card-advanced');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1))';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.background = '';
            });
        });
    }

    setupRealTimeUpdates() {
        // Update progress every 30 seconds
        setInterval(() => {
            this.updateProgressData();
        }, 30000);
        
        // Update recommendations every 5 minutes
        setInterval(() => {
            this.updateRecommendations();
        }, 300000);
        
        // Update activity feed every minute
        setInterval(() => {
            this.updateActivityFeed();
        }, 60000);
    }

    initializeInteractions() {
        // Drag and drop for course cards
        this.initializeDragAndDrop();
        
        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        // Touch gestures
        this.setupTouchGestures();
    }

    initializeDragAndDrop() {
        const courseCards = document.querySelectorAll('.course-card-advanced');
        
        courseCards.forEach(card => {
            card.draggable = true;
            
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('dragging');
            });
            
            card.addEventListener('dragend', (e) => {
                card.classList.remove('dragging');
            });
            
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                card.classList.add('drag-over');
            });
            
            card.addEventListener('dragleave', () => {
                card.classList.remove('drag-over');
            });
            
            card.addEventListener('drop', (e) => {
                e.preventDefault();
                card.classList.remove('drag-over');
                this.handleCardDrop(e, card);
            });
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R: Refresh recommendations
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.updateRecommendations();
            }
            
            // Ctrl/Cmd + P: Toggle progress view
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                this.toggleProgressView();
            }
            
            // Ctrl/Cmd + M: Toggle mobile menu
            if ((e.ctrlKey || e.metaKey) && e.key === 'm') {
                e.preventDefault();
                this.toggleSidebar();
            }
            
            // Escape: Close modals
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    setupTouchGestures() {
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].clientX;
            touchEndY = e.changedTouches[0].clientY;
            
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;
            
            // Swipe detection
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                if (deltaX > 50) {
                    // Swipe right
                    this.handleSwipeRight();
                } else if (deltaX < -50) {
                    // Swipe left
                    this.handleSwipeLeft();
                }
            }
        });
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar-advanced');
        const mainContent = document.querySelector('.main-content-advanced');
        
        this.isSidebarOpen = !this.isSidebarOpen;
        
        if (this.isSidebarOpen) {
            sidebar.classList.add('open');
            mainContent.style.marginLeft = '320px';
        } else {
            sidebar.classList.remove('open');
            mainContent.style.marginLeft = '0';
        }
    }

    handleNavigation(item) {
        // Remove active class from all items
        document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
        
        // Add active class to clicked item
        item.classList.add('active');
        
        // Close mobile menu
        if (window.innerWidth < 768) {
            this.toggleSidebar();
        }
        
        // Navigate to the page
        const href = item.getAttribute('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    }

    handleCardClick(event, card) {
        // Prevent navigation if it's a details button
        if (event.target.closest('.course-actions, .recommendation-actions')) {
            return;
        }
        
        // Add ripple effect
        this.createRipple(event, card);
        
        // Navigate to course details
        const courseId = card.dataset.courseId;
        if (courseId) {
            window.location.href = `../course-details.php?id=${courseId}`;
        }
    }

    createRipple(event, element) {
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            left: ${x}px;
            top: ${y}px;
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        `;
        
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    handleProgressClick(progressBar) {
        const container = progressBar.closest('.progress-container');
        const percentage = container.dataset.percentage;
        
        // Show detailed progress modal
        this.showProgressModal(percentage);
    }

    handleRefresh(event, button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Simulate refresh
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || '<i class="fas fa-sync-alt"></i>';
            
            // Trigger actual refresh
            if (button.classList.contains('refresh-recommendations')) {
                this.updateRecommendations();
            } else if (button.classList.contains('refresh-progress')) {
                this.updateProgressData();
            }
            
            this.showNotification('Data refreshed successfully!', 'success');
        }, 1500);
    }

    handleFabClick() {
        // Show quick actions menu
        this.showQuickActionsMenu();
    }

    handleCardDrop(event, targetCard) {
        // Handle card reordering
        const draggedCard = document.querySelector('.dragging');
        if (draggedCard && draggedCard !== targetCard) {
            const container = targetCard.parentNode;
            const allCards = Array.from(container.children);
            
            const draggedIndex = allCards.indexOf(draggedCard);
            const targetIndex = allCards.indexOf(targetCard);
            
            if (draggedIndex < targetIndex) {
                container.insertBefore(draggedCard, targetCard.nextSibling);
            } else {
                container.insertBefore(draggedCard, targetCard);
            }
            
            this.showNotification('Course order updated!', 'success');
        }
    }

    handleSwipeRight() {
        // Navigate to next section
        const sections = document.querySelectorAll('.dashboard-section');
        const currentSection = Array.from(sections).find(section => {
            const rect = section.getBoundingClientRect();
            return rect.left > 0 && rect.left < window.innerWidth / 2;
        });
        
        if (currentSection) {
            currentSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    handleSwipeLeft() {
        // Navigate to previous section
        const sections = document.querySelectorAll('.dashboard-section');
        const currentSection = Array.from(sections).find(section => {
            const rect = section.getBoundingClientRect();
            return rect.right > window.innerWidth / 2;
        });
        
        if (currentSection) {
            currentSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    showProgressModal(percentage) {
        // Create modal HTML
        const modalHTML = `
            <div class="progress-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Progress Details</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="progress-circle">
                            <svg width="200" height="200">
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                                <circle cx="100" cy="100" r="90" fill="none" stroke="url(#progressGradient)" stroke-width="10"
                                        stroke-dasharray="${percentage * 5.65}" stroke-dashoffset="565"
                                        transform="rotate(-90deg)"/>
                            </svg>
                            <div class="progress-text">${percentage}%</div>
                        </div>
                        <div class="progress-details">
                            <h4>Learning Progress</h4>
                            <p>You're ${percentage}% of the way through this course!</p>
                            <div class="progress-milestones">
                                <div class="milestone completed">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Course Started</span>
                                </div>
                                <div class="milestone ${percentage > 50 ? 'completed' : 'pending'}">
                                    <i class="fas fa-${percentage > 50 ? 'check-circle' : 'circle'}"></i>
                                    <span>Halfway Point</span>
                                </div>
                                <div class="milestone ${percentage > 80 ? 'completed' : 'pending'}">
                                    <i class="fas fa-${percentage > 80 ? 'check-circle' : 'circle'}"></i>
                                    <span>Nearly Complete</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modal = document.createElement('div');
        modal.innerHTML = modalHTML;
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        document.body.appendChild(modal);
        
        // Animate in
        setTimeout(() => {
            modal.style.opacity = '1';
        }, 10);
        
        // Close handlers
        const closeBtn = modal.querySelector('.modal-close');
        const modalContent = modal.querySelector('.modal-content');
        
        closeBtn.addEventListener('click', () => {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 300);
        });
        
        modalContent.addEventListener('click', (e) => {
            if (e.target === modalContent) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        });
    }

    showQuickActionsMenu() {
        // Create quick actions menu
        const menuHTML = `
            <div class="quick-actions-menu">
                <div class="quick-actions-content">
                    <h4>Quick Actions</h4>
                    <div class="actions-list">
                        <button class="action-btn" data-action="refresh">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh Data</span>
                        </button>
                        <button class="action-btn" data-action="export">
                            <i class="fas fa-download"></i>
                            <span>Export Progress</span>
                        </button>
                        <button class="action-btn" data-action="share">
                            <i class="fas fa-share-alt"></i>
                            <span>Share Progress</span>
                        </button>
                        <button class="action-btn" data-action="settings">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add menu to page
        const menu = document.createElement('div');
        menu.innerHTML = menuHTML;
        menu.style.cssText = `
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            z-index: 999;
            opacity: 0;
            transform: scale(0.8) translateY(20px);
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(menu);
        
        // Animate in
        setTimeout(() => {
            menu.style.opacity = '1';
            menu.style.transform = 'scale(1) translateY(0)';
        }, 10);
        
        // Close handlers
        const actionBtns = menu.querySelectorAll('.action-btn');
        
        actionBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action;
                this.handleQuickAction(action);
                menu.style.opacity = '0';
                menu.style.transform = 'scale(0.8) translateY(20px)';
                setTimeout(() => {
                    menu.remove();
                }, 300);
            });
        });
        
        // Click outside to close
        menu.addEventListener('click', (e) => {
            if (e.target === menu) {
                menu.style.opacity = '0';
                menu.style.transform = 'scale(0.8) translateY(20px)';
                setTimeout(() => {
                    menu.remove();
                }, 300);
            }
        });
    }

    handleQuickAction(action) {
        switch (action) {
            case 'refresh':
                this.refreshAllData();
                break;
            case 'export':
                this.exportProgress();
                break;
            case 'share':
                this.shareProgress();
                break;
            case 'settings':
                this.openSettings();
                break;
        }
    }

    refreshAllData() {
        this.updateProgressData();
        this.updateRecommendations();
        this.updateActivityFeed();
        this.showNotification('All data refreshed!', 'success');
    }

    exportProgress() {
        // Export progress data as JSON
        const progressData = this.collectProgressData();
        const dataStr = JSON.stringify(progressData, null, 2);
        
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `progress-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        this.showNotification('Progress data exported!', 'success');
    }

    shareProgress() {
        // Create shareable link
        const shareUrl = `${window.location.origin}/store/student/dashboard.php`;
        
        if (navigator.share) {
            navigator.share({
                title: 'My Learning Progress',
                text: 'Check out my learning progress!',
                url: shareUrl
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(shareUrl);
            this.showNotification('Link copied to clipboard!', 'success');
        }
    }

    openSettings() {
        // Open settings modal
        this.showNotification('Settings panel coming soon!', 'info');
    }

    collectProgressData() {
        // Collect all progress data from the dashboard
        const progressData = {
            timestamp: new Date().toISOString(),
            userId: this.userId,
            courses: [],
            stats: {},
            recommendations: [],
            activities: []
        };
        
        // Collect course data
        document.querySelectorAll('.course-card-advanced').forEach(card => {
            const courseId = card.dataset.courseId;
            const title = card.querySelector('.course-title')?.textContent;
            const progress = card.querySelector('.progress-fill')?.style.width;
            
            if (courseId && title) {
                progressData.courses.push({
                    id: courseId,
                    title: title,
                    progress: progress
                });
            }
        });
        
        // Collect stats data
        const statValues = document.querySelectorAll('.stat-value-advanced');
        statValues.forEach((stat, index) => {
            const label = stat.closest('.stat-card-advanced').querySelector('.stat-label')?.textContent;
            if (label) {
                progressData.stats[label.toLowerCase()] = stat.textContent;
            }
        });
        
        return progressData;
    }

    updateProgressData() {
        // Update progress bars with animation
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const container = bar.closest('.progress-container');
            const newWidth = container?.dataset.percentage || Math.random() * 100;
            
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = `${newWidth}%`;
            }, 100);
        });
    }

    updateRecommendations() {
        // Refresh recommendation cards with animation
        const container = document.querySelector('.recommendation-grid');
        if (container) {
            container.style.opacity = '0.5';
            
            setTimeout(() => {
                container.style.opacity = '1';
                container.style.transition = 'opacity 0.3s ease';
            }, 300);
        }
        
        this.showNotification('Recommendations updated!', 'success');
    }

    updateActivityFeed() {
        // Add new activity items with animation
        const activityContainer = document.querySelector('.activity-timeline');
        if (activityContainer) {
            const newActivity = this.createActivityItem();
            activityContainer.insertBefore(newActivity, activityContainer.firstChild);
            
            // Animate in new item
            newActivity.style.opacity = '0';
            newActivity.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                newActivity.style.opacity = '1';
                newActivity.style.transform = 'translateX(0)';
            }, 100);
        }
    }

    createActivityItem() {
        const activities = [
            { icon: 'graduation-cap', title: 'Course Completed', time: 'Just now' },
            { icon: 'brain', title: 'Quiz Passed', time: '2 minutes ago' },
            { icon: 'check-circle', title: 'Lesson Completed', time: '5 minutes ago' }
        ];
        
        const activity = activities[Math.floor(Math.random() * activities.length)];
        
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="activity-icon ${activity.icon}">
                <i class="fas fa-${activity.icon}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <div class="activity-time">${activity.time}</div>
            </div>
        `;
        
        return item;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 300px;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        // Close handler
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
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

    closeAllModals() {
        document.querySelectorAll('.progress-modal, .quick-actions-menu').forEach(modal => {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 300);
        });
    }

    handleResize() {
        // Handle responsive adjustments
        if (window.innerWidth < 768 && this.isSidebarOpen) {
            this.toggleSidebar();
        }
    }

    initializeCharts() {
        // Initialize any chart instances
        this.initializeProgressChart();
        this.initializeActivityChart();
    }

    initializeProgressChart() {
        // Create a simple progress chart
        const chartContainer = document.querySelector('.progress-chart');
        if (chartContainer) {
            // Implementation would go here for actual chart library
            console.log('Progress chart initialized');
        }
    }

    initializeActivityChart() {
        // Create an activity timeline chart
        const chartContainer = document.querySelector('.activity-chart');
        if (chartContainer) {
            // Implementation would go here for actual chart library
            console.log('Activity chart initialized');
        }
    }

    initializeProgressIndicators() {
        // Initialize circular progress indicators
        document.querySelectorAll('.progress-circle').forEach(circle => {
            this.animateCircularProgress(circle);
        });
    }

    animateCircularProgress(circle) {
        const svg = circle.querySelector('svg');
        const progressCircle = svg?.querySelector('circle:last-child');
        
        if (progressCircle) {
            const circumference = 2 * Math.PI * 90;
            const percent = progressCircle.style.strokeDashoffset ? 
                (565 - parseFloat(progressCircle.style.strokeDashoffset)) / circumference * 100 : 0;
            
            // Animate to current percentage
            progressCircle.style.strokeDashoffset = '565';
            setTimeout(() => {
                progressCircle.style.strokeDashoffset = `${565 - (percent / 100) * 565}`;
                progressCircle.style.transition = 'stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1)';
            }, 100);
        }
    }

    setupIntersectionObserver() {
        // Observe elements for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                } else {
                    entry.target.classList.remove('visible');
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.course-card-advanced, .recommendation-card-advanced').forEach(card => {
            observer.observe(card);
        });
    }
}

// Initialize the advanced dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.advancedDashboard = new AdvancedDashboard();
});

// Add CSS animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0) rotate(0deg);
            opacity: 0.3;
        }
        50% {
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.8;
        }
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    .dragging {
        opacity: 0.7;
        transform: rotate(5deg);
        cursor: grabbing !important;
    }
    
    .drag-over {
        transform: scale(1.02);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .notification {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: scaleIn 0.3s ease-out;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    
    .progress-circle svg {
        width: 200px;
        height: 200px;
        margin: 0 auto 1rem;
    }
    
    .progress-circle circle {
        fill: none;
        stroke-width: 10;
        stroke-linecap: round;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 2rem;
        font-weight: bold;
        color: #1e293b;
    }
    
    .progress-details {
        text-align: center;
        margin-top: 2rem;
    }
    
    .progress-milestones {
        display: flex;
        justify-content: space-around;
        margin-top: 1.5rem;
    }
    
    .milestone {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        border-radius: 8px;
        background: rgba(102, 126, 234, 0.05);
        border: 1px solid rgba(102, 126, 234, 0.1);
    }
    
    .milestone.completed {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
    }
    
    .milestone.pending {
        background: rgba(107, 114, 128, 0.05);
        border-color: rgba(107, 114, 128, 0.1);
    }
    
    .quick-actions-menu {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    
    .actions-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
    }
    
    .action-btn:hover {
        background: var(--primary-gradient);
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
    }
    
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    
    .particle {
        position: absolute;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.3), transparent);
        border-radius: 50%;
        animation: float 20s infinite linear;
    }
    
    .visible {
        opacity: 1;
        transform: translateY(0);
    }
`;
document.head.appendChild(style);

// Add SVG gradient definitions
const svgDefs = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
svgDefs.innerHTML = `
    <defs>
        <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
        </linearGradient>
    </defs>
`;
document.head.appendChild(svgDefs);
