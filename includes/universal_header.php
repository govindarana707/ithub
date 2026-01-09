<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

/**
 * Universal Header Component - Adapts to different modules
 * Provides consistent navigation and branding across all modules
 */

// Get current module and user info
$currentPath = $_SERVER['PHP_SELF'];
$moduleName = '';
$pageTitle = '';
$breadcrumbItems = [];
$showUserMenu = true;
$showSearch = false;
$showNotifications = false;

// Determine module based on current path
if (strpos($currentPath, '/admin/') !== false) {
    $moduleName = 'admin';
    $pageTitle = 'Admin Dashboard';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'url' => 'dashboard.php'],
        ['name' => 'Admin Panel', 'url' => '#']
    ];
    $showNotifications = true;
} elseif (strpos($currentPath, '/instructor/') !== false) {
    $moduleName = 'instructor';
    $pageTitle = 'Instructor Dashboard';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'url' => 'dashboard.php'],
        ['name' => 'Instructor Panel', 'url' => '#']
    ];
    $showNotifications = true;
} elseif (strpos($currentPath, '/student/') !== false) {
    $moduleName = 'student';
    $pageTitle = 'Student Dashboard';
    $breadcrumbItems = [
        ['name' => 'Dashboard', 'url' => 'dashboard.php'],
        ['name' => 'Student Portal', 'url' => '#']
    ];
    $showNotifications = true;
} else {
    // Root level pages
    if (basename($currentPath) === 'index.php') {
        $moduleName = 'home';
        $pageTitle = 'IT HUB - Online Learning Platform';
        $breadcrumbItems = [['name' => 'Home', 'url' => '#']];
        $showSearch = true;
    } elseif (basename($currentPath) === 'courses.php') {
        $moduleName = 'courses';
        $pageTitle = 'All Courses - IT HUB';
        $breadcrumbItems = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Courses', 'url' => '#']
        ];
        $showSearch = true;
    } elseif (basename($currentPath) === 'course-details.php') {
        $moduleName = 'course';
        $pageTitle = 'Course Details - IT HUB';
        $breadcrumbItems = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Courses', 'url' => 'courses.php'],
            ['name' => 'Course Details', 'url' => '#']
        ];
    } else {
        $moduleName = 'general';
        $pageTitle = 'IT HUB - Online Learning Platform';
        $breadcrumbItems = [['name' => 'Home', 'url' => 'index.php']];
    }
}

// Get user info if logged in
$userInfo = null;
if (isLoggedIn()) {
    $conn = connectDB();
    // Add fatal error checking for user info query
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role, profile_image FROM users WHERE id = ?");
    if (!$stmt) {
        die("Fatal Error: Could not prepare user query. MySQL Error: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        die("Fatal Error: Could not execute user query. MySQL Error: " . htmlspecialchars($stmt->error));
    }
    $userInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$userInfo) {
        // Log out user if session is valid but user record is missing
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
}

// Get notification count if user is logged in
$notificationCount = 0;
if ($userInfo && $showNotifications) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if (!$stmt) {
        // Log notification error but do not halt execution
        error_log("SQL Prepare failed in universal_header.php (notifications): " . $conn->error);
        $conn->close();
        $notificationCount = 0;
    } else {
        $stmt->bind_param("i", $userInfo['id']);
        if (!$stmt->execute()) {
            error_log("SQL Execute failed in universal_header.php (notifications): " . $stmt->error);
            $notificationCount = 0;
        } else {
            $result = $stmt->get_result()->fetch_assoc();
            $notificationCount = $result['count'];
        }
        $stmt->close();
        $conn->close();
    }
}

// Module-specific configurations
$moduleConfig = [
    'admin' => [
        'primary_color' => '#dc3545',
        'secondary_color' => '#c82333',
        'icon' => 'fas fa-shield-alt',
        'menu_items' => [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'Users', 'url' => 'users.php', 'icon' => 'fas fa-users'],
            ['name' => 'Courses', 'url' => 'courses.php', 'icon' => 'fas fa-book'],
            ['name' => 'Analytics', 'url' => 'analytics.php', 'icon' => 'fas fa-chart-line'],
            ['name' => 'Settings', 'url' => 'settings.php', 'icon' => 'fas fa-cog']
        ]
    ],
    'instructor' => [
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'icon' => 'fas fa-chalkboard-teacher',
        'menu_items' => [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'My Courses', 'url' => 'courses.php', 'icon' => 'fas fa-graduation-cap'],
            ['name' => 'Create Course', 'url' => 'create-course.php', 'icon' => 'fas fa-plus'],
            ['name' => 'Students', 'url' => 'students.php', 'icon' => 'fas fa-users'],
            ['name' => 'Analytics', 'url' => 'analytics.php', 'icon' => 'fas fa-chart-line'],
            ['name' => 'Earnings', 'url' => 'earnings.php', 'icon' => 'fas fa-rupee-sign']
        ]
    ],
    'student' => [
        'primary_color' => '#4f46e5',
        'secondary_color' => '#7c3aed',
        'icon' => 'fas fa-user-graduate',
        'menu_items' => [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'My Courses', 'url' => 'my-courses.php', 'icon' => 'fas fa-graduation-cap'],
            ['name' => 'Quizzes', 'url' => 'quizzes.php', 'icon' => 'fas fa-brain'],
            ['name' => 'Certificates', 'url' => 'certificates.php', 'icon' => 'fas fa-certificate'],
            ['name' => 'Discussions', 'url' => 'discussions.php', 'icon' => 'fas fa-comments'],
            ['name' => 'Profile', 'url' => 'profile.php', 'icon' => 'fas fa-user']
        ]
    ],
    'home' => [
        'primary_color' => '#10b981',
        'secondary_color' => '#059669',
        'icon' => 'fas fa-home',
        'menu_items' => [
            ['name' => 'Home', 'url' => 'index.php', 'icon' => 'fas fa-home'],
            ['name' => 'Courses', 'url' => 'courses.php', 'icon' => 'fas fa-book'],
            ['name' => 'About', 'url' => 'about.php', 'icon' => 'fas fa-info-circle'],
            ['name' => 'Contact', 'url' => 'contact.php', 'icon' => 'fas fa-envelope']
        ]
    ]
];

$currentConfig = $moduleConfig[$moduleName] ?? $moduleConfig['home'];
$primaryColor = $currentConfig['primary_color'];
$secondaryColor = $currentConfig['secondary_color'];
$moduleIcon = $currentConfig['icon'];
$menuItems = $currentConfig['menu_items'];

// Generate dynamic styles to match instructor courses page theme
$dynamicStyles = "
.universal-header {
    background: #007bff !important;
}

.universal-header .navbar-brand {
    color: white !important;
    font-weight: bold;
    font-size: 1.5rem;
}

.universal-header .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500;
    transition: color 0.3s ease;
}

.universal-header .nav-link:hover {
    color: white !important;
}

.universal-header .dropdown-menu {
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.universal-header .dropdown-item {
    color: #212529;
    padding: 0.5rem 1rem;
    transition: background-color 0.3s ease;
}

.universal-header .dropdown-item:hover {
    background: #f8f9fa;
    color: #212529;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.breadcrumb {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
}

.breadcrumb-item {
    color: #6c757d;
}

.breadcrumb-item.active {
    color: #495057;
}

.breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #0056b3;
}

.search-box {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    color: white;
    transition: all 0.3s ease;
}

.search-box:focus {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    color: white;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
}

.search-box::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.mobile-menu-toggle {
    background: transparent;
    border: none;
    color: white;
    padding: 0.25rem 0.5rem;
}

.mobile-menu-toggle:hover {
    color: rgba(255, 255, 255, 0.8);
}

/* Dashboard card styles matching admin dashboard */
.dashboard-card {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.dashboard-card h3 {
    color: #495057;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.stat-card.primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.375rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card.success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.375rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card.info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.375rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card.warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.375rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-card p {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-card small {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* Advanced GUI Components for Instructor Dashboard */
.pulse-dot {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

.quick-actions-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-action-item {
    position: relative;
}

.quick-action-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.quick-action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s ease;
}

.quick-action-card:hover::before {
    left: 100%;
}

.quick-action-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    margin-right: 1rem;
    position: relative;
    z-index: 1;
}

.quick-action-icon.primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.quick-action-icon.success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.quick-action-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
.quick-action-icon.warning { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }

.quick-action-content {
    flex: 1;
}

.quick-action-content h6 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
}

.quick-action-content small {
    color: #6c757d;
    font-size: 0.8rem;
}

.quick-action-arrow {
    color: #6c757d;
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.quick-action-card:hover .quick-action-arrow {
    transform: translateX(3px);
    color: #007bff;
}

.activity-filter {
    display: flex;
    gap: 0.5rem;
}

.filter-btn {
    padding: 0.375rem 0.75rem;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: #f8f9fa;
    color: #495057;
}

.filter-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.activity-timeline {
    position: relative;
}

.activity-scroll {
    padding-right: 0.5rem;
}

.activity-scroll::-webkit-scrollbar {
    width: 6px;
}

.activity-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.activity-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.activity-scroll::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.activity-timeline-item {
    display: flex;
    margin-bottom: 1.5rem;
    position: relative;
    animation: slideInLeft 0.5s ease;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.activity-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 1rem;
}

.activity-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 2;
}

.activity-timeline-item[data-activity-type=\"course\"] .activity-dot {
    background: #28a745;
}

.activity-timeline-item[data-activity-type=\"student\"] .activity-dot {
    background: #ffc107;
}

.activity-timeline-item[data-activity-type=\"quiz\"] .activity-dot {
    background: #17a2b8;
}

.activity-line {
    width: 2px;
    height: 100%;
    background: #e9ecef;
    position: absolute;
    top: 12px;
    z-index: 1;
}

.activity-content {
    flex: 1;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
    transition: all 0.3s ease;
}

.activity-content:hover {
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.activity-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.activity-type i {
    font-size: 0.9rem;
    color: #007bff;
}

.activity-timeline-item[data-activity-type=\"course\"] .activity-type i {
    color: #28a745;
}

.activity-timeline-item[data-activity-type=\"student\"] .activity-type i {
    color: #ffc107;
}

.activity-timeline-item[data-activity-type=\"quiz\"] .activity-type i {
    color: #17a2b8;
}

.activity-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.activity-details {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h6 {
    margin: 0 0 0.5rem 0;
    color: #495057;
}

.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

/* Enhanced stat cards with animations */
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.stat-card h3 {
    position: relative;
    z-index: 1;
}

.stat-card p {
    position: relative;
    z-index: 1;
}

.stat-card small {
    position: relative;
    z-index: 1;
}

/* Enhanced dashboard cards */
.dashboard-card {
    position: relative;
    overflow: hidden;
}

.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #007bff, transparent);
    animation: slideTop 3s ease-in-out infinite;
}

@keyframes slideTop {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: -100%; }
}

/* Advanced Chart and Table Styles */
.advanced-chart {
    position: relative;
}

.chart-controls {
    display: flex;
    align-items: center;
}

.chart-container {
    position: relative;
    height: 300px;
    margin: 1rem 0;
}

.chart-summary {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}

.summary-item {
    text-align: center;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
    color: #495057;
}

.summary-value.positive {
    color: #28a745;
}

.summary-value.negative {
    color: #dc3545;
}

.summary-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.engagement-indicator {
    position: relative;
    width: 20px;
    height: 20px;
}

.pulse-ring {
    width: 12px;
    height: 12px;
    background: #28a745;
    border-radius: 50%;
    position: absolute;
    top: 4px;
    left: 4px;
    animation: pulse-ring 2s infinite;
}

.pulse-ring::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 12px;
    height: 12px;
    background: #28a745;
    border-radius: 50%;
    animation: pulse-ring 2s infinite;
    animation-delay: 0.5s;
}

@keyframes pulse-ring {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.5);
        opacity: 0.3;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.engagement-donut {
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.engagement-legend {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0;
    border-radius: 0.25rem;
    transition: background-color 0.3s ease;
}

.legend-item:hover {
    background: #f8f9fa;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-label {
    flex: 1;
    font-size: 0.875rem;
    color: #495057;
}

.legend-value {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.empty-state-chart {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state-table {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

/* Advanced Table Styles */
.table-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.advanced-table {
    border-collapse: separate;
    border-spacing: 0;
}

.advanced-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sortable-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: color 0.3s ease;
}

.sortable-header:hover {
    color: #007bff;
}

.sortable-header i {
    font-size: 0.75rem;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.sortable-header:hover i {
    opacity: 1;
}

.course-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.course-thumbnail {
    width: 60px;
    height: 40px;
    border-radius: 0.375rem;
    overflow: hidden;
    flex-shrink: 0;
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-placeholder {
    width: 100%;
    height: 100%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.875rem;
}

.course-details {
    flex: 1;
    min-width: 0;
}

.course-title {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.status-published {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.status-draft {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-badge.status-archived {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.student-count {
    text-align: center;
}

.count-number {
    display: block;
    font-size: 1.25rem;
    font-weight: bold;
    color: #495057;
}

.progress-sm {
    height: 4px;
}

.progress-info {
    text-align: center;
}

.progress-circle {
    position: relative;
    display: inline-block;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: bold;
    color: #495057;
}

.revenue-info {
    text-align: center;
}

.revenue-amount {
    display: block;
    font-size: 1.1rem;
    font-weight: bold;
    color: #28a745;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
}

/* Card View Styles */
.course-card-modern {
    background: white;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
}

.course-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card-image {
    position: relative;
    height: 150px;
    overflow: hidden;
}

.card-image img,
.card-img-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-img-placeholder {
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 2rem;
}

.card-overlay {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}

.card-body {
    padding: 1rem;
}

.card-stats {
    display: flex;
    justify-content: space-between;
    margin: 1rem 0;
}

.card-stats .stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.card-stats .stat i {
    font-size: 0.875rem;
    color: #6c757d;
}

.card-stats .stat span {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}

.card-actions {
    display: flex;
    gap: 0.5rem;
}

/* Modern card styles matching instructor courses page */
.course-card-modern {
    transition: all 0.3s ease;
    overflow: hidden;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.course-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

.course-thumbnail-modern {
    height: 200px;
    object-fit: cover;
}

.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-img-top {
    border-top-left-radius: 0.375rem !important;
    border-top-right-radius: 0.375rem !important;
}

.badge.rounded-pill {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.775rem;
}

.card-title {
    line-height: 1.3;
}

.card-body {
    padding: 1.25rem;
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
}

.border-0 {
    border: 0 !important;
}

.opacity-75 {
    opacity: 0.75 !important;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.fw-semibold {
    font-weight: 600 !important;
}

/* Sidebar styles to match instructor courses theme */
.sidebar {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1rem;
    margin-bottom: 1rem;
}

.list-group-item {
    border: none;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
    color: #495057;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background: #f8f9fa;
    transform: translateX(0.25rem);
}

.list-group-item.active {
    background: #007bff;
    color: white;
}

.main-content {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1.5rem;
}

.stat-card {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-left: 4px solid #007bff;
}

.stat-card:hover {
    transform: translateY(-0.25rem);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.stat-card.success {
    border-left-color: #28a745;
}

.stat-card.warning {
    border-left-color: #ffc107;
}

.stat-card.info {
    border-left-color: #17a2b8;
}

.stat-card.danger {
    border-left-color: #dc3545;
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: #495057;
}

.metric-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.visual-container {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.course-card {
    background: white;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1rem;
    transition: transform 0.3s ease;
    margin-bottom: 1rem;
}

.course-card:hover {
    transform: translateY(-0.25rem);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.activity-item {
    padding: 0.75rem;
    border-left: 3px solid #007bff;
    background: #f8f9fa;
    border-radius: 0 0.375rem 0.375rem 0;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(0.25rem);
}

.engagement-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.engagement-item:hover {
    background: #e9ecef;
    transform: translateX(0.25rem);
}

.engagement-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-weight: bold;
    font-size: 0.875rem;
}

.trend-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    margin-bottom: 0.5rem;
    border-radius: 0 0.375rem 0.375rem 0;
    transition: all 0.3s ease;
}

.trend-item:hover {
    background: #e9ecef;
    transform: translateX(0.25rem);
}

.trend-up { border-left-color: #28a745; }
.trend-down { border-left-color: #dc3545; }
.trend-neutral { border-left-color: #6c757d; }

.streak-badge {
    background: #ffc107;
    color: #212529;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 0.125rem 0.25rem rgba(255, 193, 7, 0.3);
}

.achievement-badge {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #495057;
    transition: all 0.3s ease;
}

.achievement-badge:hover {
    background: #e9ecef;
    transform: translateX(0.25rem);
}

.quick-action-btn {
    border-radius: 0.375rem;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    background: white;
    color: #495057;
}

.quick-action-btn:hover {
    border-color: #007bff;
    color: #007bff;
    transform: translateY(-0.25rem);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
";

// Helper function to convert hex to RGB
function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "{$r}, {$g}, {$b}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        <?php echo $dynamicStyles; ?>
    </style>
</head>
<body>
    <!-- Universal Header -->
    <header class="universal-header navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <!-- Module-specific menu items -->
                <?php foreach ($menuItems as $item): ?>
                    <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>">
                        <i class="<?php echo $item['icon']; ?> me-1"></i>
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                <?php endforeach; ?>
                
                <!-- User Menu -->
                <?php if ($userInfo): ?>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                <?php else: ?>
                    <!-- Login/Register buttons for guests -->
                    <a class="nav-link" href="../login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="nav-link" href="../register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Breadcrumb Navigation -->
    <?php if (count($breadcrumbItems) > 1): ?>
        <nav class="universal-header" style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));">
            <div class="container-fluid py-2">
                <ol class="breadcrumb mb-0">
                    <?php foreach ($breadcrumbItems as $index => $item): ?>
                        <?php if ($index === count($breadcrumbItems) - 1): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <i class="<?php echo $moduleIcon; ?> me-1"></i>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </nav>
    <?php endif; ?>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info_message'])): ?>
        <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['info_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['info_message']); ?>
    <?php endif; ?>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Auto-hide alerts after 5 seconds
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 800);
        }
    });
    
    // Active state for navigation
    var currentPath = window.location.pathname;
    $('.nav-link').each(function() {
        var linkPath = $(this).attr('href');
        if (linkPath && currentPath.includes(linkPath)) {
            $(this).addClass('active');
        }
    });
});
</script>
