/**
 * Enhanced Dashboard Features
 * Advanced interactions, animations, and user experience improvements
 */

class EnhancedDashboard {
    constructor() {
        this.isCollapsed = false;
        this.isDarkMode = false;
        this.isDragging = false;
        this.draggedElement = null;
        this.notifications = [];
        this.init();
    }

    init() {
        this.setupSidebarToggle();
        this.setupCollapsibleCards();
        this.setupDragAndDrop();
        this.setupThemeToggle();
        this.setupNotifications();
        this.setupAdaptiveHeader();
        this.setupAnimatedCounters();
        this.setupInteractiveCharts();
        this.setupKeyboardNavigation();
        this.setupAccessibility();
        this.setupSearch();
        this.setupQuickActions();
        this.setupRealTimeUpdates();
        this.setupMobileGestures();
    }

    setupSidebarToggle() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        
        if (!sidebar || !toggleBtn) return;

        toggleBtn.addEventListener('click', () => {
            this.isCollapsed = !this.isCollapsed;
            sidebar.classList.toggle('collapsed');
            
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = this.isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
            }
            
            // Save preference
            localStorage.setItem('sidebar-collapsed', this.isCollapsed);
        });

        // Load saved preference
        const savedState = localStorage.getItem('sidebar-collapsed');
        if (savedState === 'true') {
            this.isCollapsed = true;
            sidebar.classList.add('collapsed');
            const icon = toggleBtn.querySelector('i');
            if (icon) icon.className = 'fas fa-chevron-right';
        }
    }

    setupCollapsibleCards() {
        document.querySelectorAll('.dashboard-card.expandable').forEach(card => {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'card-expand-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
            
            const expandableContent = card.querySelector('.card-expandable-content');
            if (expandableContent) {
                toggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    card.classList.toggle('collapsed');
                    const icon = toggleBtn.querySelector('i');
                    icon.className = card.classList.contains('collapsed') ? 
                        'fas fa-chevron-up' : 'fas fa-chevron-down';
                });
                card.appendChild(toggleBtn);
            }
        });
    }

    setupDragAndDrop() {
        const cards = document.querySelectorAll('.dashboard-card');
        
        cards.forEach(card => {
            card.draggable = true;
            card.addEventListener('dragstart', (e) => {
                this.isDragging = true;
                this.draggedElement = card;
                card.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            card.addEventListener('dragend', () => {
                this.isDragging = false;
                card.classList.remove('dragging');
            });
            
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (this.isDragging && this.draggedElement !== card) {
                    card.classList.add('drag-over');
                }
            });
            
            card.addEventListener('dragleave', () => {
                card.classList.remove('drag-over');
            });
            
            card.addEventListener('drop', (e) => {
                e.preventDefault();
                card.classList.remove('drag-over');
                
                if (this.isDragging && this.draggedElement !== card) {
                    const parent = card.parentNode;
                    const allCards = [...parent.children].filter(el => el.classList.contains('dashboard-card'));
                    const draggedIndex = allCards.indexOf(this.draggedElement);
                    const targetIndex = allCards.indexOf(card);
                    
                    if (draggedIndex < targetIndex) {
                        parent.insertBefore(this.draggedElement, card.nextSibling);
                    } else {
                        parent.insertBefore(this.draggedElement, card);
                    }
                    
                    this.saveCardOrder();
                }
            });
        });
    }

    setupThemeToggle() {
        // Create theme toggle button
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        themeToggle.title = 'Toggle Dark Mode';
        document.body.appendChild(themeToggle);
        
        themeToggle.addEventListener('click', () => {
            this.isDarkMode = !this.isDarkMode;
            document.body.classList.toggle('dark-mode');
            
            const icon = themeToggle.querySelector('i');
            icon.className = this.isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
            
            // Update CSS variables
            if (this.isDarkMode) {
                document.documentElement.style.setProperty('--bg-primary', '#0f172a');
                document.documentElement.style.setProperty('--bg-secondary', '#1e293b');
                document.documentElement.style.setProperty('--text-primary', '#f1f5f9');
            } else {
                document.documentElement.style.setProperty('--bg-primary', '#ffffff');
                document.documentElement.style.setProperty('--bg-secondary', '#f8fafc');
                document.documentElement.style.setProperty('--text-primary', '#1e293b');
            }
            
            localStorage.setItem('dark-mode', this.isDarkMode);
        });

        // Load saved theme
        const savedTheme = localStorage.getItem('dark-mode');
        if (savedTheme === 'true') {
            this.isDarkMode = true;
            document.body.classList.add('dark-mode');
            const icon = themeToggle.querySelector('i');
            if (icon) icon.className = 'fas fa-sun';
        }
    }

    setupNotifications() {
        // Create notifications panel
        const notificationsPanel = document.createElement('div');
        notificationsPanel.className = 'notifications-panel';
        notificationsPanel.innerHTML = `
            <div class="notifications-header">
                <h3>Notifications</h3>
                <button class="close-notifications">&times;</button>
            </div>
            <div class="notifications-content">
                <div class="no-notifications">No new notifications</div>
            </div>
        `;
        document.body.appendChild(notificationsPanel);

        const overlay = document.createElement('div');
        overlay.className = 'notifications-overlay';
        document.body.appendChild(overlay);

        // Notification button handler
        const notifBtn = document.querySelector('.btn-icon[title="Notifications"]');
        if (notifBtn) {
            notifBtn.addEventListener('click', () => {
                this.openNotifications();
            });
        }

        // Close handlers
        notificationsPanel.querySelector('.close-notifications').addEventListener('click', () => {
            this.closeNotifications();
        });

        overlay.addEventListener('click', () => {
            this.closeNotifications();
        });
    }

    openNotifications() {
        const panel = document.querySelector('.notifications-panel');
        const overlay = document.querySelector('.notifications-overlay');
        
        if (panel && overlay) {
            panel.classList.add('open');
            overlay.classList.add('active');
            this.loadNotifications();
        }
    }

    closeNotifications() {
        const panel = document.querySelector('.notifications-panel');
        const overlay = document.querySelector('.notifications-overlay');
        
        if (panel && overlay) {
            panel.classList.remove('open');
            overlay.classList.remove('active');
        }
    }

    loadNotifications() {
        // Simulate loading notifications
        const content = document.querySelector('.notifications-content');
        if (content) {
            content.innerHTML = `
                <div class="notification-item">
                    <div class="notification-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="notification-content">
                        <strong>Course Completed!</strong>
                        <p>You've successfully completed "Introduction to PHP"</p>
                        <small>2 minutes ago</small>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon info">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="notification-content">
                        <strong>New Achievement!</strong>
                        <p>You've earned the "Quiz Expert" badge</p>
                        <small>1 hour ago</small>
                    </div>
                </div>
            `;
        }
    }

    setupAdaptiveHeader() {
        const header = document.querySelector('.dashboard-header');
        if (!header) return;

        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScrollY = currentScrollY;
        });
    }

    setupAnimatedCounters() {
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-value, .quick-stat-value').forEach(counter => {
            observer.observe(counter);
        });
    }

    animateCounter(element) {
        const target = parseInt(element.textContent) || 0;
        const duration = 1000;
        const step = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += step;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
                element.classList.add('animated');
            }
        };

        updateCounter();
    }

    setupInteractiveCharts() {
        // Placeholder for Chart.js integration
        // This would be implemented with actual chart libraries
        console.log('Interactive charts ready for Chart.js integration');
    }

    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + B: Toggle sidebar
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                const toggleBtn = document.querySelector('.sidebar-toggle');
                if (toggleBtn) toggleBtn.click();
            }
            
            // Ctrl/Cmd + D: Toggle dark mode
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) themeToggle.click();
            }
            
            // Ctrl/Cmd + N: Open notifications
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.openNotifications();
            }
            
            // Escape: Close panels
            if (e.key === 'Escape') {
                this.closeNotifications();
            }
        });
    }

    setupAccessibility() {
        // Add ARIA labels and keyboard navigation
        document.querySelectorAll('.nav-item').forEach((item, index) => {
            item.setAttribute('role', 'menuitem');
            item.setAttribute('tabindex', '0');
            item.setAttribute('aria-label', item.querySelector('.nav-text')?.textContent || '');
        });

        // Focus management
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                // Handle tab navigation
                const focusableElements = document.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }

    setupSearch() {
        const searchInput = document.querySelector('.sidebar-search input');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(item => {
                const text = item.querySelector('.nav-text')?.textContent.toLowerCase() || '';
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    setupQuickActions() {
        // Add quick action buttons
        const quickActions = document.createElement('div');
        quickActions.className = 'quick-actions';
        quickActions.innerHTML = `
            <button class="quick-action-btn" data-action="new-course">
                <i class="fas fa-plus"></i>
                <span>New Course</span>
            </button>
            <button class="quick-action-btn" data-action="take-quiz">
                <i class="fas fa-brain"></i>
                <span>Take Quiz</span>
            </button>
            <button class="quick-action-btn" data-action="view-progress">
                <i class="fas fa-chart-line"></i>
                <span>Progress</span>
            </button>
        `;

        const header = document.querySelector('.dashboard-header .header-content');
        if (header) {
            header.insertBefore(quickActions, header.firstChild);
        }

        // Add click handlers
        quickActions.addEventListener('click', (e) => {
            const btn = e.target.closest('.quick-action-btn');
            if (btn) {
                const action = btn.dataset.action;
                this.handleQuickAction(action);
            }
        });
    }

    handleQuickAction(action) {
        switch(action) {
            case 'new-course':
                window.location.href = '../courses.php';
                break;
            case 'take-quiz':
                window.location.href = 'quizzes.php';
                break;
            case 'view-progress':
                window.location.href = 'my-courses.php';
                break;
        }
    }

    setupRealTimeUpdates() {
        // Simulate real-time updates
        setInterval(() => {
            this.updateRealTimeStats();
        }, 30000); // Update every 30 seconds
    }

    updateRealTimeStats() {
        // Update quick stats with animation
        document.querySelectorAll('.quick-stat-value').forEach(stat => {
            const currentValue = parseInt(stat.textContent) || 0;
            const newValue = currentValue + Math.floor(Math.random() * 3);
            
            if (newValue !== currentValue) {
                stat.classList.remove('animated');
                void stat.offsetWidth; // Trigger reflow
                stat.classList.add('animated');
                stat.textContent = newValue;
            }
        });
    }

    setupMobileGestures() {
        let touchStartX = 0;
        let touchEndX = 0;
        const sidebar = document.querySelector('.dashboard-sidebar');

        if (!sidebar) return;

        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const swipeDistance = touchEndX - touchStartX;

            // Swipe right to open sidebar
            if (swipeDistance > 50 && touchStartX < 50) {
                sidebar.classList.add('open');
            }
            // Swipe left to close sidebar
            else if (swipeDistance < -50 && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    }

    saveCardOrder() {
        const cards = document.querySelectorAll('.dashboard-card');
        const order = Array.from(cards).map(card => card.dataset.cardId || card.className);
        localStorage.setItem('card-order', JSON.stringify(order));
    }

    loadCardOrder() {
        const savedOrder = localStorage.getItem('card-order');
        if (savedOrder) {
            try {
                const order = JSON.parse(savedOrder);
                // Apply saved order logic here
                console.log('Loading saved card order:', order);
            } catch (e) {
                console.error('Failed to load card order:', e);
            }
        }
    }

    // Initialize tooltips
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Add breadcrumb functionality
    addBreadcrumbs() {
        const header = document.querySelector('.header-left');
        if (!header) return;

        const breadcrumb = document.createElement('div');
        breadcrumb.className = 'breadcrumb';
        breadcrumb.innerHTML = `
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">/</span>
            <span>Learning Overview</span>
        `;

        header.insertBefore(breadcrumb, header.firstChild);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.enhancedDashboard = new EnhancedDashboard();
    
    // Initialize tooltips after a short delay
    setTimeout(() => {
        if (window.enhancedDashboard) {
            window.enhancedDashboard.initTooltips();
            window.enhancedDashboard.addBreadcrumbs();
        }
    }, 100);
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.enhancedDashboard) {
        // Cleanup any ongoing operations
        console.log('Cleaning up enhanced dashboard features');
    }
});
