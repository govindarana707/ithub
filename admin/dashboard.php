<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';

$user = new User();
$course = new Course();

// Get statistics
$stats = [
    'users' => $user->getUserStats(),
    'courses' => $course->getCourseStats()
];

// Get recent activities
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT al.action, al.details, al.created_at, u.full_name, u.email
    FROM admin_logs al
    JOIN users_new u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get system overview data
$stmt = $conn->query("SELECT COUNT(*) as total FROM users_new WHERE role = 'student'");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $studentCount = $row['total'];
} else {
    $studentCount = 0;
}

$stmt = $conn->query("SELECT COUNT(*) as total FROM users_new WHERE role = 'instructor'");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $instructorCount = $row['total'];
} else {
    $instructorCount = 0;
}

$stmt = $conn->query("SELECT COUNT(*) as total FROM enrollments_new");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $enrollmentCount = $row['total'];
} else {
    $enrollmentCount = 0;
}

$stmt = $conn->query("SELECT COUNT(*) as total FROM quiz_attempts");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $quizAttempts = $row['total'];
} else {
    $quizAttempts = 0;
}

$stmt = $conn->query("SELECT COUNT(*) as total FROM courses_new WHERE status = 'published'");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $publishedCourses = $row['total'];
} else {
    $publishedCourses = 0;
}

$stmt = $conn->query("SELECT COUNT(*) as total FROM certificates");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $certificatesCount = $row['total'];
} else {
    $certificatesCount = 0;
}

// Get recent users
$stmt = $conn->prepare("SELECT id, full_name, email, role, status, created_at FROM users_new ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent courses
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.status, u.full_name as instructor_name, c.created_at, COALESCE(cat.name, 'N/A') as category_name
    FROM courses_new c
    JOIN users_new u ON c.instructor_id = u.id
    LEFT JOIN categories_new cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Dashboard Styles */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* Modern Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"25\" cy=\"25\" r=\"1\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"75\" cy=\"75\" r=\"1\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"50\" cy=\"10\" r=\"1\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"10\" cy=\"50\" r=\"1\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"90\" cy=\"30\" r=\"1\" fill=\"white\" opacity=\"0.1\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>') repeat;
            opacity: 0.3;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }

        /* Enhanced Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-top: 4px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color-light) 100%);
            opacity: 0.05;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card:hover::before {
            opacity: 0.1;
        }

        .stat-card.primary {
            border-top-color: #667eea;
            --card-color: #667eea;
            --card-color-light: #764ba2;
        }

        .stat-card.success {
            border-top-color: #10b981;
            --card-color: #10b981;
            --card-color-light: #059669;
        }

        .stat-card.warning {
            border-top-color: #f59e0b;
            --card-color: #f59e0b;
            --card-color-light: #d97706;
        }

        .stat-card.info {
            border-top-color: #3b82f6;
            --card-color: #3b82f6;
            --card-color-light: #2563eb;
        }

        .stat-card.danger {
            border-top-color: #ef4444;
            --card-color: #ef4444;
            --card-color-light: #dc2626;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .stat-card.primary .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card.success .stat-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-card.warning .stat-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-card.info .stat-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-card.danger .stat-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* Enhanced Content Cards */
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: -30px -30px 20px -30px;
            padding: 20px 30px;
            border-radius: 20px 20px 0 0;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header h3 {
            color: #1e293b;
            font-weight: 600;
            margin: 0;
            font-size: 1.3rem;
        }

        /* Enhanced Tables */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .modern-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .modern-table th {
            background: transparent;
            padding: 15px;
            font-weight: 600;
            color: #1e293b;
            border: none;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }

        /* Enhanced Badges */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Enhanced Buttons */
        .btn-modern {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }

        .content-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        .content-card:nth-child(2) { animation-delay: 0.2s; }
        .content-card:nth-child(3) { animation-delay: 0.4s; }

        /* Admin Badge */
        .admin-badge {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <?php require_once 'includes/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Modern Dashboard Header -->
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-shield-alt me-3"></i>Admin Dashboard</h1>
                            <p>System management and overview</p>
                        </div>
                        <div class="admin-badge">
                            <i class="fas fa-user-shield"></i>
                            <span>Administrator</span>
                        </div>
                    </div>
                </div>

                <!-- System Overview Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-value"><?php echo $studentCount; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-value"><?php echo $instructorCount; ?></div>
                            <div class="stat-label">Instructors</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-value"><?php echo $publishedCourses; ?></div>
                            <div class="stat-label">Published Courses</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="stat-value"><?php echo $certificatesCount; ?></div>
                            <div class="stat-label">Certificates</div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card danger">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo $enrollmentCount; ?></div>
                            <div class="stat-label">Total Enrollments</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="stat-value"><?php echo $quizAttempts; ?></div>
                            <div class="stat-label">Quiz Attempts</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value"><?php echo round(($publishedCourses / max($instructorCount, 1)), 1); ?></div>
                            <div class="stat-label">Avg Courses/Instructor</div>
                        </div>
                    </div>
                </div>

<!-- Recent Users Card -->
                <div class="content-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-users me-2"></i>Recent Users</h3>
                            <a href="users.php" class="btn-modern btn-primary-modern">
                                <i class="fas fa-arrow-right"></i>
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-placeholder" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'blocked' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Courses Card -->
                <div class="content-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-book me-2"></i>Recent Courses</h3>
                            <a href="courses.php" class="btn-modern btn-primary-modern">
                                <i class="fas fa-arrow-right"></i>
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Category</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCourses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-book text-primary"></i>
                                                <span><?php echo htmlspecialchars($course['title']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($course['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript -->
    <script>
        // Enhanced animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animate content cards
            const contentCards = document.querySelectorAll('.content-card');
            contentCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 500 + (index * 200));
            });

            // Add hover effects to stat cards
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
