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
<?php require_once '../includes/universal_header.php'; ?>

<style>
/* Enhanced Earnings Dashboard Styles */
.earnings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.earnings-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="30" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.earnings-header-content {
    position: relative;
    z-index: 1;
}

.earnings-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    animation: fadeInDown 0.8s ease;
}

.earnings-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    animation: fadeInUp 0.8s ease;
}

.earnings-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(102, 126, 234, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.earnings-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.earnings-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
}

.earnings-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.earnings-card-icon.revenue {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.earnings-card-icon.enrollments {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.earnings-card-icon.average {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.earnings-card-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
    animation: countUp 1s ease;
}

.earnings-card-label {
    color: #6b7280;
    font-size: 1rem;
    font-weight: 500;
}

.earnings-card-trend {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.9rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
}

.trend-up {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.trend-down {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.modern-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(102, 126, 234, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.15);
}

.modern-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.modern-card-body {
    padding: 2rem;
}

.revenue-table {
    border-radius: 10px;
    overflow: hidden;
}

.revenue-table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    color: #475569;
    font-weight: 600;
    border: none;
    padding: 1rem;
}

.revenue-table tbody tr {
    transition: all 0.2s ease;
}

.revenue-table tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
}

.revenue-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.progress-modern {
    height: 8px;
    border-radius: 10px;
    background: #f1f5f9;
    overflow: hidden;
}

.progress-modern .progress-bar {
    border-radius: 10px;
    transition: width 1s ease;
}

.payout-card {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.btn-modern {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
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
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-modern:hover::before {
    width: 300px;
    height: 300px;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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

@keyframes countUp {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .earnings-title {
        font-size: 2rem;
    }
    
    .earnings-card-value {
        font-size: 2rem;
    }
    
    .modern-card-body {
        padding: 1.5rem;
    }
}
</style>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <?php require_once '../includes/instructor_sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <!-- Enhanced Header -->
                <div class="earnings-header">
                    <div class="earnings-header-content">
                        <h1 class="earnings-title">
                            <i class="fas fa-chart-line me-3"></i>Earnings Dashboard
                        </h1>
                        <p class="earnings-subtitle">Track your revenue and monitor course performance</p>
                    </div>
                </div>

                <!-- Enhanced Date Range Filter -->
                <div class="modern-card mb-4">
                    <div class="modern-card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><i class="fas fa-calendar-alt me-2"></i>Date Range</h5>
                                <p class="text-muted mb-0">Select period for earnings analysis</p>
                            </div>
                            <form method="GET" class="m-0">
                                <select name="date_range" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <option value="7days" <?php echo $dateRange === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30days" <?php echo $dateRange === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="90days" <?php echo $dateRange === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                                    <option value="1year" <?php echo $dateRange === '1year' ? 'selected' : ''; ?>>Last Year</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Earnings Overview -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="earnings-card">
                            <div class="earnings-card-trend trend-up">
                                <i class="fas fa-arrow-up me-1"></i>+12%
                            </div>
                            <div class="earnings-card-icon revenue">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                            <div class="earnings-card-value">Rs<?php echo number_format($earnings['summary']['total_revenue'] ?? 0, 2); ?></div>
                            <div class="earnings-card-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="earnings-card">
                            <div class="earnings-card-trend trend-up">
                                <i class="fas fa-arrow-up me-1"></i>+8%
                            </div>
                            <div class="earnings-card-icon enrollments">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="earnings-card-value"><?php echo $earnings['summary']['total_enrollments'] ?? 0; ?></div>
                            <div class="earnings-card-label">Total Enrollments</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="earnings-card">
                            <div class="earnings-card-trend trend-up">
                                <i class="fas fa-arrow-up me-1"></i>+5%
                            </div>
                            <div class="earnings-card-icon average">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="earnings-card-value">Rs<?php echo number_format($earnings['summary']['avg_course_price'] ?? 0, 2); ?></div>
                            <div class="earnings-card-label">Avg Course Price</div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Monthly Revenue Trend -->
                <div class="modern-card mb-4">
                    <div class="modern-card-header">
                        <i class="fas fa-chart-line me-2"></i>Monthly Revenue Trend
                    </div>
                    <div class="modern-card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Revenue by Course -->
                <div class="modern-card mb-4">
                    <div class="modern-card-header">
                        <i class="fas fa-graduation-cap me-2"></i>Revenue by Course
                    </div>
                    <div class="modern-card-body">
                        <div class="table-responsive">
                            <table class="table revenue-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-book me-2"></i>Course</th>
                                        <th><i class="fas fa-tag me-2"></i>Price</th>
                                        <th><i class="fas fa-users me-2"></i>Enrollments</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Revenue</th>
                                        <th><i class="fas fa-chart-pie me-2"></i>Performance</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($earnings['by_course'] as $index => $course): ?>
                                        <tr style="animation: fadeInUp 0.5s ease <?php echo $index * 0.1; ?>s both;">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                                            <i class="fas fa-book"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success fs-6">Rs<?php echo number_format($course['price'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold me-2"><?php echo $course['enrollments']; ?></span>
                                                    <div class="progress-modern" style="width: 60px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo min(100, ($course['enrollments'] / max(1, $earnings['summary']['total_enrollments'])) * 100); ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">Rs<?php echo number_format($course['revenue'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="text-muted me-2"><?php echo round(($course['revenue'] / max(1, $earnings['summary']['total_revenue'])) * 100, 1); ?>%</span>
                                                    <div class="progress-modern" style="width: 60px;">
                                                        <div class="progress-bar bg-info" style="width: <?php echo ($course['revenue'] / max(1, $earnings['summary']['total_revenue'])) * 100; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="course-stats.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Stats">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <a href="course_builder.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-success" title="Edit Course">
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

                <!-- Enhanced Payout Information & Revenue Breakdown -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <i class="fas fa-info-circle me-2"></i>Payout Information
                            </div>
                            <div class="modern-card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Current Balance:</span>
                                        <span class="text-success fs-5 fw-bold">Rs<?php echo number_format($earnings['summary']['total_revenue'] ?? 0, 2); ?></span>
                                    </div>
                                    <div class="progress-modern" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: 75%;"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Next Payout Date:</span>
                                        <span class="fw-bold">End of Month</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Payout Method:</span>
                                        <span class="badge bg-info">Bank Transfer</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Commission Rate:</span>
                                        <span class="badge bg-success">70% (You keep 70%)</span>
                                    </div>
                                </div>
                                <button class="btn-modern btn-primary w-100" onclick="showPayoutDetails()">
                                    <i class="fas fa-cog me-2"></i>Configure Payout
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <i class="fas fa-chart-pie me-2"></i>Revenue Breakdown
                            </div>
                            <div class="modern-card-body">
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="revenueBreakdownChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Recent Transactions -->
                <div class="modern-card">
                    <div class="modern-card-header">
                        <i class="fas fa-history me-2"></i>Recent Transactions
                    </div>
                    <div class="modern-card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th><i class="fas fa-book me-2"></i>Course</th>
                                        <th><i class="fas fa-user me-2"></i>Student</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-check-circle me-2"></i>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Get recent enrollments for transaction history
                                    $conn = connectDB();
                                    $stmt = $conn->prepare("
                                        SELECT e.enrolled_at, c.title, u.full_name, c.price
                                        FROM enrollments_new e
                                        JOIN courses_new c ON e.course_id = c.id
                                        JOIN users_new u ON e.user_id = u.id
                                        WHERE c.instructor_id = ?
                                        ORDER BY e.enrolled_at DESC
                                        LIMIT 10
                                    ");
                                    if ($stmt === false) {
                                        $transactions = [];
                                    } else {
                                        $stmt->bind_param("i", $instructorId);
                                        $stmt->execute();
                                        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                        $stmt->close();
                                    }
                                    
                                    foreach ($transactions as $index => $transaction):
                                    ?>
                                        <tr style="animation: fadeInUp 0.5s ease <?php echo $index * 0.05; ?>s both;">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-calendar-day text-muted me-2"></i>
                                                    <span><?php echo date('M j, Y', strtotime($transaction['enrolled_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
                                                            <i class="fas fa-book"></i>
                                                        </div>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($transaction['title']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($transaction['full_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">Rs<?php echo number_format($transaction['price'], 2); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Completed</span>
                                            </td>
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
