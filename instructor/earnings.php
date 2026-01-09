<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];
$dateRange = $_GET['date_range'] ?? '30days';

// Get earnings data
$earnings = $instructor->getInstructorEarnings($instructorId, $dateRange);
$analytics = $instructor->getInstructorAnalytics($instructorId, $dateRange);

// Get instructor profile
$profile = $instructor->getInstructorProfile($instructorId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .earnings-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .earnings-card:hover {
            transform: translateY(-5px);
        }
        .earnings-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .earnings-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .revenue-table {
            font-size: 0.9rem;
        }
        .revenue-table .progress {
            height: 20px;
        }
        .payout-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-chalkboard-teacher me-1"></i> My Courses
                </a>
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users me-1"></i> Students
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="fas fa-chart-line me-1"></i> Analytics
                </a>
                <a class="nav-link" href="earnings.php">
                    <i class="fas fa-dollar-sign me-1"></i> Earnings
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Date Range</h6>
                        <form method="GET">
                            <select name="date_range" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="7days" <?php echo $dateRange === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30days" <?php echo $dateRange === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="90days" <?php echo $dateRange === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                                <option value="1year" <?php echo $dateRange === '1year' ? 'selected' : ''; ?>>Last Year</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Earnings Dashboard</h1>
                    <div>
                        <span class="badge bg-success">Instructor Earnings</span>
                    </div>
                </div>

                <!-- Earnings Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="earnings-card">
                            <h3>Rs<?php echo number_format($earnings['summary']['total_revenue'] ?? 0, 2); ?></h3>
                            <p>Total Revenue</p>
                            <small><i class="fas fa-rupee-sign"></i></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="earnings-card">
                            <h3><?php echo $earnings['summary']['total_enrollments'] ?? 0; ?></h3>
                            <p>Total Enrollments</p>
                            <small><i class="fas fa-users"></i></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="earnings-card">
                            <h3>Rs<?php echo number_format($earnings['summary']['avg_course_price'] ?? 0, 2); ?></h3>
                            <p>Avg Course Price</p>
                            <small><i class="fas fa-tag"></i></small>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Trend -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Revenue Trend</h5>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue by Course -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Revenue by Course</h5>
                        <div class="table-responsive">
                            <table class="table revenue-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Price</th>
                                        <th>Enrollments</th>
                                        <th>Revenue</th>
                                        <th>Performance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($earnings['by_course'] as $course): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">Rs<?php echo number_format($course['price'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo $course['enrollments']; ?></span>
                                                    <div class="progress ms-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo min(100, ($course['enrollments'] / max(1, $earnings['summary']['total_enrollments'])) * 100); ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">Rs<?php echo number_format($course['revenue'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="text-muted"><?php echo round(($course['revenue'] / max(1, $earnings['summary']['total_revenue'])) * 100, 1); ?>%</span>
                                                    <div class="progress ms-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar bg-info" style="width: <?php echo ($course['revenue'] / max(1, $earnings['summary']['total_revenue'])) * 100; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="course-stats.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Stats">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <a href="../admin/course_builder.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-success" title="Edit Course">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payout Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="payout-card">
                            <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Payout Information</h5>
                            <div class="mb-3">
                                <strong>Current Balance:</strong>
                                <span class="text-success fs-5">Rs<?php echo number_format($earnings['summary']['total_revenue'] ?? 0, 2); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Next Payout Date:</strong>
                                <span>End of Month</span>
                            </div>
                            <div class="mb-3">
                                <strong>Payout Method:</strong>
                                <span>Bank Transfer</span>
                            </div>
                            <div class="mb-3">
                                <strong>Commission Rate:</strong>
                                <span>70% (You keep 70% of revenue)</span>
                            </div>
                            <button class="btn btn-primary" onclick="showPayoutDetails()">
                                <i class="fas fa-cog me-2"></i>Configure Payout
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="payout-card">
                            <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Revenue Breakdown</h5>
                            <div class="chart-container" style="height: 200px;">
                                <canvas id="revenueBreakdownChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Transactions</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Get recent enrollments for transaction history
                                    $conn = connectDB();
                                    $stmt = $conn->prepare("
                                        SELECT e.enrolled_at, c.title, u.full_name, c.price
                                        FROM enrollments e
                                        JOIN courses c ON e.course_id = c.id
                                        JOIN users u ON e.student_id = u.id
                                        WHERE c.instructor_id = ?
                                        ORDER BY e.enrolled_at DESC
                                        LIMIT 10
                                    ");
                                    $stmt->bind_param("i", $instructorId);
                                    $stmt->execute();
                                    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    
                                    foreach ($transactions as $transaction):
                                    ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($transaction['enrolled_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                            <td class="text-success">Rs<?php echo number_format($transaction['price'], 2); ?></td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Monthly Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode(array_reverse($earnings['monthly_trend'])); ?>;
        
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueData.map(item => item.month),
                datasets: [{
                    label: 'Monthly Revenue',
                    data: revenueData.map(item => parseFloat(item.monthly_revenue)),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }, {
                    label: 'Monthly Enrollments',
                    data: revenueData.map(item => item.monthly_enrollments),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (Rs)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Enrollments'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Revenue Breakdown Chart
        const breakdownCtx = document.getElementById('revenueBreakdownChart').getContext('2d');
        const courseData = <?php echo json_encode($earnings['by_course']); ?>;
        
        new Chart(breakdownCtx, {
            type: 'doughnut',
            data: {
                labels: courseData.map(item => item.title),
                datasets: [{
                    data: courseData.map(item => parseFloat(item.revenue)),
                    backgroundColor: [
                        '#28a745',
                        '#667eea',
                        '#ffc107',
                        '#dc3545',
                        '#20c997',
                        '#fd7e14'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                }
            }
        });

        function showPayoutDetails() {
            alert('Payout configuration feature coming soon! You will be able to set up your preferred payment method and schedule.');
        }
    </script>
</body>
</html>
