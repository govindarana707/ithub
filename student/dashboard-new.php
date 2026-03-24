<?php
/**
 * Student Dashboard - Enhanced with AJAX + SweetAlert2
 * Fully dynamic, no page reloads
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Student';
$userEmail = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IT Hub Learning</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/student-dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="https://via.placeholder.com/40" alt="Logo" class="logo">
                <h4>IT Hub</h4>
            </div>
            <ul class="nav-links">
                <li class="active">
                    <a href="#dashboard" data-section="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#courses" data-section="courses">
                        <i class="fas fa-book"></i>
                        <span>Browse Courses</span>
                    </a>
                </li>
                <li>
                    <a href="#my-courses" data-section="my-courses">
                        <i class="fas fa-graduation-cap"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li>
                    <a href="#progress" data-section="progress">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress</span>
                    </a>
                </li>
                <li>
                    <a href="#assignments" data-section="assignments">
                        <i class="fas fa-tasks"></i>
                        <span>Assignments</span>
                    </a>
                </li>
                <li>
                    <a href="#notifications" data-section="notifications">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                        <span class="badge badge-danger notification-badge" id="notification-count" style="display:none;">0</span>
                    </a>
                </li>
                <li>
                    <a href="#profile" data-section="profile">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-btn" id="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search courses..." id="global-search">
                </div>
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="btn btn-link notification-btn" type="button" id="notificationDropdown" data-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge badge-danger notification-badge" id="header-notification-count" style="display:none;">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="notification-dropdown">
                            <div class="dropdown-header">Notifications</div>
                            <div id="notification-list"></div>
                            <a class="dropdown-item text-center" href="#notifications" data-section="notifications">View All</a>
                        </div>
                    </div>
                    <div class="user-menu">
                        <img src="https://via.placeholder.com/35" alt="User" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </header>

            <!-- Content Sections -->
            <div class="content-area">
                <!-- Dashboard Section -->
                <section id="section-dashboard" class="content-section active">
                    <h2 class="section-title">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
                    
                    <!-- Stats Cards -->
                    <div class="stats-grid" id="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="stat-enrolled">-</h3>
                                <p>Enrolled Courses</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="stat-completed">-</h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="stat-progress">-</h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="stat-hours">-</h3>
                                <p>Study Hours</p>
                            </div>
                        </div>
                    </div>

                    <!-- Continue Learning Section -->
                    <div class="dashboard-section mt-4">
                        <h4><i class="fas fa-play-circle mr-2"></i>Continue Learning</h4>
                        <div class="continue-learning-grid" id="continue-learning">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Loading your courses...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-section mt-4">
                        <h4><i class="fas fa-history mr-2"></i>Recent Activity</h4>
                        <div class="activity-list" id="recent-activity">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Browse Courses Section -->
                <section id="section-courses" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Browse Courses</h2>
                        <div class="filter-controls">
                            <select id="category-filter" class="form-control">
                                <option value="">All Categories</option>
                            </select>
                            <select id="difficulty-filter" class="form-control">
                                <option value="">All Levels</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div class="course-grid" id="course-grid">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Loading courses...</p>
                        </div>
                    </div>
                    <div class="pagination-container" id="course-pagination"></div>
                </section>

                <!-- My Courses Section -->
                <section id="section-my-courses" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">My Courses</h2>
                        <div class="filter-controls">
                            <select id="status-filter" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="my-courses-grid" id="my-courses-grid">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Loading your courses...</p>
                        </div>
                    </div>
                    <div class="pagination-container" id="my-courses-pagination"></div>
                </section>

                <!-- Progress Section -->
                <section id="section-progress" class="content-section">
                    <h2 class="section-title">Learning Progress</h2>
                    <div class="progress-overview" id="progress-overview">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Loading progress...</p>
                        </div>
                    </div>
                </section>

                <!-- Assignments Section -->
                <section id="section-assignments" class="content-section">
                    <h2 class="section-title">Assignments</h2>
                    <div class="assignments-container" id="assignments-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Loading assignments...</p>
                        </div>
                    </div>
                </section>

                <!-- Notifications Section -->
                <section id="section-notifications" class="content-section">
                    <h2 class="section-title">Notifications</h2>
                    <div class="notifications-container" id="notifications-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Loading notifications...</p>
                        </div>
                    </div>
                </section>

                <!-- Profile Section -->
                <section id="section-profile" class="content-section">
                    <h2 class="section-title">My Profile</h2>
                    <div class="profile-container" id="profile-container">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="https://via.placeholder.com/150" alt="Profile" class="img-fluid rounded-circle mb-3">
                                        <button class="btn btn-primary btn-sm" id="change-photo-btn">
                                            <i class="fas fa-camera mr-1"></i> Change Photo
                                        </button>
                                    </div>
                                    <div class="col-md-9">
                                        <form id="profile-form">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Full Name</label>
                                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($userName); ?>" readonly>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save mr-1"></i> Update Profile
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Assignment Submission Modal -->
    <div class="modal fade" id="assignment-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Assignment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="assignment-modal-body">
                    <!-- Dynamic content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Course Details Modal -->
    <div class="modal fade" id="course-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Course Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="course-modal-body">
                    <!-- Dynamic content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/student-api.js"></script>
    <script src="js/student-dashboard.js"></script>
</body>
</html>
