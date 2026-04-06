<?php
require_once '../config/config.php';
require_once '../services/TrialService.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    redirect('login.php');
}

// Initialize trial service
$trialService = new TrialService();

// Get trial statistics
$stats = $trialService->getTrialStatistics();

// Handle date range filtering
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_stats'])) {
    $stats = $trialService->getTrialStatistics($dateFrom, $dateTo);
}

// Process manual expiration if requested
$processResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_expirations'])) {
    $processResult = $trialService->processTrialExpirations();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Analytics - IT HUB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .conversion-rate {
            font-size: 2rem;
            font-weight: bold;
        }
        .conversion-rate.high {
            color: #28a745;
        }
        .conversion-rate.medium {
            color: #ffc107;
        }
        .conversion-rate.low {
            color: #4169E1;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="admin-header p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-chart-line me-2"></i>Trial Analytics Dashboard</h2>
                    <p class="mb-0">Monitor free trial performance, conversion rates, and user engagement</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-warning" onclick="processExpirations()">
                        <i class="fas fa-clock me-2"></i>Process Expirations
                    </button>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="refresh_stats" class="btn btn-primary w-100">
                            <i class="fas fa-sync me-2"></i>Refresh Stats
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?php echo number_format($stats['total_trials']); ?></h3>
                        <p class="mb-0">Total Trials</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?php echo number_format($stats['active_trials']); ?></h3>
                        <p class="mb-0">Active Trials</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-warning"><?php echo number_format($stats['expired_trials']); ?></h3>
                        <p class="mb-0">Expired Trials</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-info"><?php echo number_format($stats['completed_trials']); ?></h3>
                        <p class="mb-0">Completed Trials</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversion Rate & Progress -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Conversion Rate</h5>
                        <div class="text-center">
                            <div class="conversion-rate <?php 
                                echo ($stats['conversion_rate'] >= 20) ? 'high' : 
                                     (($stats['conversion_rate'] >= 10) ? 'medium' : 'low'); 
                            ?>">
                                <?php echo number_format($stats['conversion_rate'], 1); ?>%
                            </div>
                            <p class="text-muted mt-2">
                                <?php echo number_format($stats['converted_trials']); ?> of <?php echo number_format($stats['total_trials']); ?> trials converted
                            </p>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar <?php 
                                echo ($stats['conversion_rate'] >= 20) ? 'bg-success' : 
                                     (($stats['conversion_rate'] >= 10) ? 'bg-warning' : 'bg-danger'); 
                            ?>" style="width: <?php echo $stats['conversion_rate']; ?>%">
                                <?php echo number_format($stats['conversion_rate'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Average Progress</h5>
                        <div class="text-center">
                            <h3 class="text-primary"><?php echo number_format($stats['avg_progress'], 1); ?>%</h3>
                            <p class="text-muted">Average completion rate across all trials</p>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $stats['avg_progress']; ?>%">
                                <?php echo number_format($stats['avg_progress'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Trial Status Distribution</h5>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Conversion Overview</h5>
                        <div class="chart-container">
                            <canvas id="conversionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Results -->
        <?php if ($processResult): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Expiration Processing Results</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Last processed: <?php echo $processResult['timestamp']; ?>
                    </div>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Processed <?php echo $processResult['processed']; ?> expired trials</li>
                        <li><i class="fas fa-envelope text-info me-2"></i>Sent <?php echo $processResult['notifications_sent']; ?> notifications</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" onclick="exportData()">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" onclick="sendReminders()">
                            <i class="fas fa-bell me-2"></i>Send Reminders
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-warning w-100" onclick="viewLogs()">
                            <i class="fas fa-file-alt me-2"></i>View Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Chart data
        const stats = <?php echo json_encode($stats); ?>;
        
        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Expired', 'Completed', 'Converted'],
                datasets: [{
                    data: [
                        stats.active_trials,
                        stats.expired_trials,
                        stats.completed_trials,
                        stats.converted_trials
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Conversion Chart
        const conversionCtx = document.getElementById('conversionChart').getContext('2d');
        new Chart(conversionCtx, {
            type: 'bar',
            data: {
                labels: ['Converted', 'Not Converted'],
                datasets: [{
                    label: 'Number of Trials',
                    data: [stats.converted_trials, stats.total_trials - stats.converted_trials],
                    backgroundColor: ['#28a745', '#4169E1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        function processExpirations() {
            if (confirm('Process trial expirations now? This will send notifications and update statuses.')) {
                const form = $('<form>', { method: 'POST' });
                form.append($('<input>', { type: 'hidden', name: 'process_expirations', value: '1' }));
                form.appendTo('body').submit();
            }
        }
        
        function exportData() {
            alert('Export functionality coming soon!');
        }
        
        function sendReminders() {
            alert('Manual reminder sending coming soon!');
        }
        
        function viewLogs() {
            window.open('../admin/logs.php?type=trial', '_blank');
        }
    </script>
</body>
</html>
