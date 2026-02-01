<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireStudent();

require_once '../models/Database.php';
require_once '../models/Progress.php';

$progress = new Progress();
$userId = $_SESSION['user_id'];

// Get course ID from URL parameter
$courseId = $_GET['course_id'] ?? null;

// Get overall progress data
$overallProgress = $progress->getStudentOverallProgress($userId);
$progressStats = $progress->getProgressStatistics($userId);

// Get specific course progress if course ID is provided
$courseProgress = null;
$lessonsProgress = [];
if ($courseId) {
    $courseProgress = $progress->getCourseProgress($userId, $courseId);
    $lessonsProgress = $progress->getCourseLessonsProgress($userId, $courseId);
}

require_once '../includes/universal_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Tracking - IT Hub</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard-advanced.css">
    <link rel="stylesheet" href="../assets/css/progress-tracking.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/js/all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/student_nav.php'; ?>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="fas fa-chart-line me-2"></i>
                            Progress Tracking
                        </h1>
                        <p class="text-muted mb-0">Track your learning journey and achievements</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="progressTracker.loadOverallProgress()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button class="btn btn-primary" onclick="exportProgressReport()">
                            <i class="fas fa-download me-1"></i>Export Report
                        </button>
                    </div>
                </div>
                
                <!-- Progress Statistics Dashboard -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo count($overallProgress); ?></h3>
                            <p>Enrolled Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $progressStats['overall']['completed_courses'] ?? 0; ?></h3>
                            <p>Completed Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo round($progressStats['overall']['average_progress'] ?? 0); ?>%</h3>
                            <p>Average Progress</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $progressStats['study_streak'] ?? 0; ?></h3>
                            <p>Day Streak</p>
                        </div>
                    </div>
                </div>
                
                <!-- Course Progress Overview -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book-open me-2"></i>
                            Course Progress Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="progress-overview" class="progress-cards">
                            <!-- Progress cards will be loaded here by JavaScript -->
                            <div class="loading">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Loading progress data...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Course Progress (if course is selected) -->
                <?php if ($courseId && $courseProgress): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Detailed Progress: <?php echo htmlspecialchars($courseProgress['course_title'] ?? 'Course'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="course-progress-detail">
                                <!-- Course progress details will be loaded here by JavaScript -->
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Study Sessions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Recent Study Sessions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="study-sessions">
                            <!-- Study sessions will be loaded here by JavaScript -->
                            <div class="loading">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Loading study sessions...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Leaderboard -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Student Leaderboard
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="leaderboard">
                            <!-- Leaderboard will be loaded here by JavaScript -->
                            <div class="loading">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Loading leaderboard...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Tracking JavaScript -->
    <script src="../assets/js/progress-tracking.js"></script>
    
    <script>
        // Initialize progress tracking
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            progressTracker.loadOverallProgress();
            progressTracker.loadProgressStatistics();
            progressTracker.loadStudySessions();
            progressTracker.loadLeaderboard();
            
            <?php if ($courseId): ?>
                // Load specific course progress if course ID is provided
                progressTracker.loadCourseProgress(<?php echo $courseId; ?>);
            <?php endif; ?>
        });
        
        // Export progress report function
        function exportProgressReport() {
            // Create a CSV export of progress data
            const progressData = document.querySelectorAll('.progress-card');
            let csv = 'Course Title,Progress Percentage,Lessons Completed,Total Lessons,Time Spent\n';
            
            progressData.forEach(card => {
                const title = card.querySelector('h4')?.textContent || '';
                const progress = card.querySelector('.progress-text')?.textContent || '0%';
                const lessons = card.querySelector('.progress-details')?.textContent || '';
                
                csv += `"${title}",${progress},"${lessons}"\n`;
            });
            
            // Create download link
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', `progress_report_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
        
        // Enhanced progress tracking functions
        class EnhancedProgressTracker extends ProgressTracker {
            async loadStudySessions() {
                try {
                    const response = await this.apiCall('GET', 'study_sessions', { limit: 20 });
                    
                    if (response.success) {
                        this.renderStudySessions(response.sessions);
                    }
                } catch (error) {
                    console.error('Error loading study sessions:', error);
                    document.getElementById('study-sessions').innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            <p class="mt-2">Unable to load study sessions</p>
                        </div>
                    `;
                }
            }
            
            async loadLeaderboard() {
                try {
                    const response = await this.apiCall('GET', 'leaderboard', { limit: 10 });
                    
                    if (response.success) {
                        this.renderLeaderboard(response.leaderboard);
                    }
                } catch (error) {
                    console.error('Error loading leaderboard:', error);
                    document.getElementById('leaderboard').innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            <p class="mt-2">Unable to load leaderboard</p>
                        </div>
                    `;
                }
            }
            
            renderStudySessions(sessions) {
                const container = document.getElementById('study-sessions');
                
                if (!sessions || sessions.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-2x text-muted"></i>
                            <p class="mt-2">No study sessions recorded yet</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '<div class="session-list">';
                sessions.forEach(session => {
                    html += `
                        <div class="session-item">
                            <div class="session-info">
                                <h6>${session.lesson_title}</h6>
                                <small class="text-muted">${session.course_title}</small>
                            </div>
                            <div class="session-details">
                                <span class="badge bg-primary">${session.activity_type}</span>
                                <span class="text-muted">${this.formatTime(session.duration_minutes)}</span>
                                <span class="text-muted">${this.formatDate(session.session_start)}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
            }
            
            renderLeaderboard(leaderboard) {
                const container = document.getElementById('leaderboard');
                
                if (!leaderboard || leaderboard.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-trophy fa-2x text-muted"></i>
                            <p class="mt-2">No leaderboard data available</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '<div class="leaderboard-list">';
                leaderboard.forEach((student, index) => {
                    const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
                    
                    html += `
                        <div class="leaderboard-item">
                            <div class="rank">${medal}</div>
                            <div class="student-info">
                                <h6>${student.full_name || student.username}</h6>
                                <small class="text-muted">${student.enrolled_courses} courses</small>
                            </div>
                            <div class="student-stats">
                                <span class="badge bg-success">${Math.round(student.avg_progress)}%</span>
                                <span class="text-muted">${this.formatTime(student.total_time)}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
            }
        }
        
        // Replace the default progress tracker with enhanced version
        const enhancedTracker = new EnhancedProgressTracker();
        window.progressTracker = enhancedTracker;
    </script>
    
    <style>
        .session-item, .leaderboard-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .session-item:last-child, .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .rank {
            font-size: 24px;
            font-weight: bold;
            width: 50px;
            text-align: center;
        }
        
        .student-info h6 {
            margin: 0;
            font-weight: 600;
        }
        
        .student-stats {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .session-details {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>
</body>
</html>
