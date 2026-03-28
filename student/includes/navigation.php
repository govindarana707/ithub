<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = pathinfo($current_page, PATHINFO_FILENAME);
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: var(--gradient-primary);">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>IT HUB
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="studentNavbar">
            <!-- Main Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'courses' ? 'active' : ''; ?>" href="courses.php">
                        <i class="fas fa-book me-1"></i> Browse Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'my-courses' ? 'active' : ''; ?>" href="my-courses.php">
                        <i class="fas fa-book-open me-1"></i> My Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'certificates' ? 'active' : ''; ?>" href="certificates.php">
                        <i class="fas fa-certificate me-1"></i> Certificates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'quiz-results' ? 'active' : ''; ?>" href="quiz-results.php">
                        <i class="fas fa-chart-bar me-1"></i> Quiz Results
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'discussions' ? 'active' : ''; ?>" href="discussions.php">
                        <i class="fas fa-comments me-1"></i> Discussions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'notifications' ? 'active' : ''; ?>" href="notifications.php">
                        <i class="fas fa-bell me-1"></i> Notifications
                    </a>
                </li>
            </ul>
            
            <!-- User Menu -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i> 
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Student Account</h6></li>
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user-edit me-2"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Sidebar -->
<div class="mobile-nav-overlay d-lg-none" id="mobileNavOverlay"></div>
<nav class="mobile-nav-sidebar d-lg-none" id="mobileNavSidebar">
    <div class="mobile-nav-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-graduation-cap fa-2x text-white me-3"></i>
            <div>
                <h5 class="text-white mb-0">IT HUB</h5>
                <small class="text-white-50">Student Portal</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" id="closeMobileNav"></button>
    </div>
    
    <div class="mobile-nav-body">
        <div class="mobile-nav-user">
            <div class="d-flex align-items-center mb-3">
                <div class="mobile-nav-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ms-3">
                    <div class="fw-bold text-white"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?></div>
                    <small class="text-white-50">Student</small>
                </div>
            </div>
        </div>
        
        <ul class="mobile-nav-menu">
            <li>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="courses.php" class="<?php echo $current_page === 'courses' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Browse Courses
                </a>
            </li>
            <li>
                <a href="my-courses.php" class="<?php echo $current_page === 'my-courses' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i> My Courses
                </a>
            </li>
            <li>
                <a href="certificates.php" class="<?php echo $current_page === 'certificates' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i> Certificates
                </a>
            </li>
            <li>
                <a href="quiz-results.php" class="<?php echo $current_page === 'quiz-results' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Quiz Results
                </a>
            </li>
            <li>
                <a href="discussions.php" class="<?php echo $current_page === 'discussions' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Discussions
                </a>
            </li>
            <li>
                <a href="notifications.php" class="<?php echo $current_page === 'notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                </a>
            </li>
            <li class="mobile-nav-divider"></li>
            <li>
                <a href="profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit"></i> Profile
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li>
                <a href="../logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
/* Mobile Navigation Styles */
.mobile-nav-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-nav-overlay.show {
    opacity: 1;
    visibility: visible;
}

.mobile-nav-sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--gradient-primary);
    z-index: 1050;
    transition: left 0.3s ease;
    overflow-y: auto;
}

.mobile-nav-sidebar.show {
    left: 0;
}

.mobile-nav-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: between;
    align-items: center;
}

.mobile-nav-user {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-nav-avatar {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.mobile-nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-nav-menu li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.mobile-nav-menu a {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
}

.mobile-nav-menu a:hover,
.mobile-nav-menu a.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.mobile-nav-menu i {
    width: 20px;
    margin-right: 12px;
}

.mobile-nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 10px 0;
}

/* Desktop Navigation Enhancements */
@media (min-width: 992px) {
    .navbar-nav .nav-link {
        border-radius: 6px;
        margin: 0 2px;
        transition: all 0.3s ease;
    }
    
    .navbar-nav .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-1px);
    }
    
    .navbar-nav .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        font-weight: 600;
    }
}

/* Mobile Menu Toggle Button */
.navbar-toggler {
    border: none;
    padding: 0.25rem 0.5rem;
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileNavToggle = document.querySelector('.navbar-toggler');
    const mobileNavSidebar = document.getElementById('mobileNavSidebar');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const closeMobileNav = document.getElementById('closeMobileNav');
    
    function openMobileNav() {
        mobileNavSidebar.classList.add('show');
        mobileNavOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileNavFunc() {
        mobileNavSidebar.classList.remove('show');
        mobileNavOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    if (mobileNavToggle) {
        mobileNavToggle.addEventListener('click', openMobileNav);
    }
    
    if (closeMobileNav) {
        closeMobileNav.addEventListener('click', closeMobileNavFunc);
    }
    
    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', closeMobileNavFunc);
    }
    
    // Close mobile nav when clicking on a link (optional)
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-menu a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', closeMobileNavFunc);
    });
});
</script>
