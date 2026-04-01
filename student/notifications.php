<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/session_helper.php';

// Initialize session
initializeSession();

if (!isUserLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

$userId = $_SESSION['user_id'];

// Get notifications
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Mark individual notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$unreadCount = $stmt->get_result()->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        /* Modern Dashboard Color Scheme */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius-modern: 20px;
        }
        
        /* Modern Dashboard Header */
        .dashboard-header {
            background: var(--primary-gradient);
            border-radius: var(--border-radius-modern);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(35deg);
            pointer-events: none;
        }
        
        .dashboard-header h1 {
            color: white !important;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header p {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Modern Content Cards */
        .modern-card {
            background: white;
            border-radius: var(--border-radius-modern);
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modern-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
            border-radius: var(--border-radius-modern) var(--border-radius-modern) 0 0;
        }
        
        .modern-card .card-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .modern-card .card-body {
            padding: 2rem;
        }
        
        /* Enhanced Notification Items */
        .notification-item {
            border-left: 4px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .notification-item.unread {
            border-left-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }
        
        .notification-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .notification-icon.info { 
            background: var(--info-gradient); 
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .notification-icon.success { 
            background: var(--success-gradient); 
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .notification-icon.warning { 
            background: var(--warning-gradient); 
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .notification-icon.error { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .notification-icon:hover {
            transform: scale(1.1);
        }
        
        .notification-time {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Modern Buttons */
        .btn-modern {
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Override buttons to use modern styling */
        .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }
        
        .btn-outline-primary {
            border-color: #667eea !important;
            color: #667eea !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            transition: all 0.4s ease !important;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-gradient) !important;
            border-color: transparent !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }
        
        /* Badge Enhancements */
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* List Group Enhancements */
        .list-group {
            border-radius: var(--border-radius-modern);
            overflow: hidden;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Staggered Animation for Notification Items */
        .notification-item {
            animation: fadeInUp 0.6s ease both;
        }
        
        .notification-item:nth-child(1) { animation-delay: 0.1s; }
        .notification-item:nth-child(2) { animation-delay: 0.2s; }
        .notification-item:nth-child(3) { animation-delay: 0.3s; }
        .notification-item:nth-child(4) { animation-delay: 0.4s; }
        .notification-item:nth-child(5) { animation-delay: 0.5s; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .modern-card .card-body {
                padding: 1.5rem;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/universal_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php require_once 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Modern Dashboard Header -->
                <div class="dashboard-header">
                    <div class="position-relative">
                        <h1 class="mb-3">Notifications 🔔</h1>
                        <p class="mb-0">Stay updated with your latest activities and announcements</p>
                    </div>
                    <div class="position-absolute top-0 end-0">
                        <span class="badge bg-white text-primary px-3 py-2">Student</span>
                    </div>
                </div>

                <!-- Enhanced Notification Stats -->
                <div class="modern-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2 text-primary"></i>
                            Notification Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">Unread:</span>
                                    <span class="badge bg-primary rounded-pill"><?php echo $unreadCount; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">Total:</span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo count($notifications); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if ($unreadCount > 0): ?>
                            <div class="mt-2">
                                <a href="?mark_all_read=1" class="btn btn-primary btn-modern w-100">
                                    <i class="fas fa-check-double me-1"></i>Mark All Read
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Notifications List -->
                <?php if (empty($notifications)): ?>
                    <div class="modern-card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-bell-slash fa-4x text-muted" style="opacity: 0.4;"></i>
                            </div>
                            <h4 class="fw-bold text-muted mb-3">No notifications</h4>
                            <p class="text-muted mb-4">You're all caught up! Check back later for new updates.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="dashboard.php" class="btn btn-primary-modern btn-modern">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a href="my-courses.php" class="btn btn-outline-primary btn-modern">
                                    <i class="fas fa-book-open me-2"></i>My Courses
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="modern-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bell me-2 text-primary"></i>
                                    Recent Notifications
                                </h5>
                                <span class="badge bg-secondary rounded-pill">
                                    <?php echo count($notifications); ?> items
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <div class="notification-icon <?php echo $notification['notification_type']; ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $notification['notification_type'] === 'info' ? 'info-circle' : 
                                                            ($notification['notification_type'] === 'success' ? 'check-circle' : 
                                                            ($notification['notification_type'] === 'warning' ? 'exclamation-triangle' : 'times-circle')); 
                                                    ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                        <small class="notification-time">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo getTimeAgo($notification['created_at']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="ms-2">
                                                        <?php if (!$notification['is_read']): ?>
                                                            <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary btn-modern">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enhanced animations for notification items
            $('.notification-item').each(function(index) {
                $(this).css('opacity', '0');
                $(this).css('transform', 'translateY(30px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 600, 'easeOutCubic');
                }, 100 * index);
            });
            
            // Hover effects for notification items
            $('.notification-item').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-5px) scale(1.02)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0) scale(1)');
            });
            
            // Button ripple effect
            $('.btn-modern').on('click', function(e) {
                const button = $(this);
                const ripple = $('<span class="ripple"></span>');
                
                button.append(ripple);
                
                const x = e.pageX - button.offset().left;
                const y = e.pageY - button.offset().top;
                
                ripple.css({
                    left: x,
                    top: y
                });
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Parallax effect for dashboard header
            $(window).on('scroll', function() {
                const scrolled = $(window).scrollTop();
                $('.dashboard-header').css('transform', `translateY(${scrolled * 0.3}px)`);
            });
            
            // Auto-refresh notifications every 30 seconds with visual feedback
            setInterval(() => {
                // Show loading state
                $('.dashboard-header h1').fadeTo(300, 0.5);
                
                // Simulate data refresh
                setTimeout(() => {
                    $('.dashboard-header h1').fadeTo(300, 1);
                    console.log('Checking for new notifications...');
                }, 1000);
            }, 30000);
            
            // Enhanced mark as read functionality
            $('.notification-item').click(function() {
                const unreadItem = $(this).hasClass('unread');
                if (unreadItem) {
                    const notificationId = $(this).find('a').attr('href')?.match(/mark_read=(\d+)/)?.[1];
                    if (notificationId) {
                        // Show loading state
                        $(this).find('.btn-outline-primary').html('<i class="fas fa-spinner fa-spin"></i>');
                        
                        // Simulate API call
                        setTimeout(() => {
                            window.location.href = `?mark_read=${notificationId}`;
                        }, 500);
                    }
                }
            });
            
            // Add hover effect to notification icons
            $('.notification-icon').on('mouseenter', function() {
                $(this).css('transform', 'scale(1.1) rotate(5deg)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'scale(1) rotate(0deg)');
            });
            
            // Add click animation to mark as read buttons
            $('.btn-outline-primary').on('click', function(e) {
                e.stopPropagation();
                const $this = $(this);
                const originalHTML = $this.html();
                
                $this.html('<i class="fas fa-spinner fa-spin"></i>');
                $this.prop('disabled', true);
                
                // Simulate API call
                setTimeout(() => {
                    $this.html('<i class="fas fa-check"></i>');
                    setTimeout(() => {
                        $this.fadeOut(300, function() {
                            $(this).remove();
                        });
                        // Remove unread class from parent
                        $this.closest('.notification-item').removeClass('unread');
                    }, 300);
                }, 500);
            });
        });
    </script>
    
    <!-- Add CSS for ripple effect -->
    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>
</html>

<?php
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>
