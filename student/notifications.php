<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
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
    <style>
        .notification-item {
            border-left: 4px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            border-left-color: #007bff;
            background-color: #f8f9ff;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-icon.info { background-color: #d1ecf1; color: #0c5460; }
        .notification-icon.success { background-color: #d4edda; color: #155724; }
        .notification-icon.warning { background-color: #fff3cd; color: #856404; }
        .notification-icon.error { background-color: #f8d7da; color: #721c24; }
        
        .notification-time {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group rounded-3 shadow-sm mb-4">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> Browse Courses
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="certificates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-certificate me-2"></i> Certificates
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>

                <!-- Notification Stats -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">Notification Stats</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Unread:</span>
                            <strong><?php echo $unreadCount; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total:</span>
                            <strong><?php echo count($notifications); ?></strong>
                        </div>
                        <?php if ($unreadCount > 0): ?>
                            <div class="mt-2">
                                <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fas fa-check-double me-1"></i>Mark All Read
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-bell me-2"></i>Notifications</h1>
                    <?php if ($unreadCount > 0): ?>
                        <a href="?mark_all_read=1" class="btn btn-outline-primary">
                            <i class="fas fa-check-double me-1"></i>Mark All Read
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h4>No notifications</h4>
                            <p class="text-muted">You're all caught up! Check back later for new updates.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
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
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <small class="notification-time">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo getTimeAgo($notification['created_at']); ?>
                                                    </small>
                                                </div>
                                                <div class="ms-2">
                                                    <?php if (!$notification['is_read']): ?>
                                                        <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            // You could implement AJAX refresh here
            console.log('Checking for new notifications...');
        }, 30000);
        
        // Mark as read when clicking on notification
        $('.notification-item').click(function() {
            const unreadItem = $(this).hasClass('unread');
            if (unreadItem) {
                const notificationId = $(this).find('a').attr('href')?.match(/mark_read=(\d+)/)?.[1];
                if (notificationId) {
                    window.location.href = `?mark_read=${notificationId}`;
                }
            }
        });
    </script>
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
