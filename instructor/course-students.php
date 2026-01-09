<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

$instructorId = $_SESSION['user_id'];
$courseId = (int)($_GET['id'] ?? 0);
if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course ID.';
    header('Location: courses.php');
    exit;
}

$conn = connectDB();

// Verify course ownership
$stmt = $conn->prepare("SELECT id, title, status FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param('ii', $courseId, $instructorId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    $conn->close();
    $_SESSION['error_message'] = 'Course not found or access denied.';
    header('Location: courses.php');
    exit;
}

// Filters
$statusFilter = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Export
$export = ($_GET['export'] ?? '') === 'csv';

// Base query
$baseSql = "
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    WHERE e.course_id = ?
";
$params = [$courseId];
$types = 'i';

if ($statusFilter !== '' && in_array($statusFilter, ['active', 'completed', 'dropped'], true)) {
    $baseSql .= " AND e.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($search !== '') {
    $baseSql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

// Count for pagination
$countSql = "SELECT COUNT(DISTINCT u.id) as total " . $baseSql;
$stmt = $conn->prepare($countSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalStudents = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// Fetch students
$sql = "
    SELECT u.id, u.full_name, u.email, u.username,
           e.enrolled_at, e.completed_at, e.progress_percentage, e.status
    $baseSql
    ORDER BY e.enrolled_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$statsSql = "
    SELECT
        COUNT(DISTINCT u.id) as total,
        COUNT(DISTINCT CASE WHEN e.status = 'active' THEN u.id END) as active,
        COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN u.id END) as completed,
        COUNT(DISTINCT CASE WHEN e.status = 'dropped' THEN u.id END) as dropped,
        AVG(e.progress_percentage) as avg_progress
    $baseSql
";
$stmt = $conn->prepare($statsSql);
$stmt->bind_param(substr($types, 0, -2), ...array_slice($params, 0, -2));
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Export CSV
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="course_students_' . $courseId . '_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student ID', 'Full Name', 'Email', 'Username', 'Enrolled At', 'Completed At', 'Progress %', 'Status']);

    $exportSql = "
        SELECT u.id, u.full_name, u.email, u.username,
               e.enrolled_at, e.completed_at, e.progress_percentage, e.status
        $baseSql
        ORDER BY e.enrolled_at DESC
    ";
    $stmt = $conn->prepare($exportSql);
    $stmt->bind_param(substr($types, 0, -2), ...array_slice($params, 0, -2));
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    foreach ($rows as $row) {
        fputcsv($out, [
            $row['id'],
            $row['full_name'],
            $row['email'],
            $row['username'],
            $row['enrolled_at'],
            $row['completed_at'],
            $row['progress_percentage'],
            $row['status'],
        ]);
    }

    fclose($out);
    exit;
}

$conn->close();

$totalPages = max(1, (int)ceil($totalStudents / $limit));

function buildUrl($overrides = []) {
    $base = [
        'id' => $_GET['id'] ?? '',
        'status' => $_GET['status'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? 1,
    ];
    $q = array_merge($base, $overrides);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return 'course-students.php?' . http_build_query($q);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Students - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .card-soft {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
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
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                <a class="nav-link" href="courses.php"><i class="fas fa-chalkboard-teacher me-1"></i> My Courses</a>
                <a class="nav-link" href="students.php"><i class="fas fa-users me-1"></i> Students</a>
                <a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle me-1"></i> Quizzes</a>
                <a class="nav-link" href="analytics.php"><i class="fas fa-chart-line me-1"></i> Analytics</a>
                <a class="nav-link" href="earnings.php"><i class="fas fa-rupee-sign me-1"></i> Earnings</a>
                <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
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
                    <a href="create-course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i> Quizzes
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="mb-1">Course Students</h1>
                        <div class="text-muted">
                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                            <span class="badge bg-<?php echo ($course['status'] ?? '') === 'published' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst((string)($course['status'] ?? 'draft')); ?>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(buildUrl(['export' => 'csv', 'page' => 1])); ?>">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                        <a class="btn btn-outline-primary" href="courses.php">
                            <i class="fas fa-arrow-left me-2"></i>Back to Courses
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card-soft">
                            <div class="h3 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
                            <div class="text-muted">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-soft">
                            <div class="h3 mb-0"><?php echo (int)($stats['active'] ?? 0); ?></div>
                            <div class="text-muted">Active</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-soft">
                            <div class="h3 mb-0"><?php echo (int)($stats['completed'] ?? 0); ?></div>
                            <div class="text-muted">Completed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-soft">
                            <div class="h3 mb-0"><?php echo round((float)($stats['avg_progress'] ?? 0), 1); ?>%</div>
                            <div class="text-muted">Avg Progress</div>
                        </div>
                    </div>
                </div>

                <div class="card-soft mb-3">
                    <form class="row g-2" method="GET">
                        <input type="hidden" name="id" value="<?php echo $courseId; ?>">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="dropped" <?php echo $statusFilter === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email or username">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button class="btn btn-primary w-100" type="submit">Filter</button>
                                <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(buildUrl(['status' => '', 'search' => '', 'page' => 1])); ?>">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Contact</th>
                                        <th>Enrolled</th>
                                        <th>Completed</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No students found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $s): ?>
                                            <?php
                                                $name = (string)($s['full_name'] ?? '');
                                                $initial = strtoupper(substr($name !== '' ? $name : (($s['username'] ?? '') ?: 'S'), 0, 1));
                                                $progress = (float)($s['progress_percentage'] ?? 0);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar bg-primary text-white">
                                                            <?php echo htmlspecialchars($initial); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($s['full_name'] ?? $s['username'] ?? ''); ?></div>
                                                            <div class="text-muted small">ID: <?php echo (int)$s['id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($s['email'] ?? ''); ?></div>
                                                    <div class="text-muted small">@<?php echo htmlspecialchars($s['username'] ?? ''); ?></div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        <?php echo !empty($s['enrolled_at']) ? date('M j, Y', strtotime($s['enrolled_at'])) : '-'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        <?php echo !empty($s['completed_at']) ? date('M j, Y', strtotime($s['completed_at'])) : '-'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted" style="min-width: 48px; text-align: right;">
                                                            <?php echo round($progress, 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                        $status = $s['status'] ?? 'active';
                                                        $badgeMap = [
                                                            'active' => 'success',
                                                            'completed' => 'info',
                                                            'dropped' => 'danger',
                                                        ];
                                                        $badgeClass = $badgeMap[$status] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo htmlspecialchars(buildUrl(['page' => max(1, $page - 1)])); ?>">Previous</a>
                                    </li>
                                    <?php
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                    ?>
                                    <?php for ($p = $start; $p <= $end; $p++): ?>
                                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(buildUrl(['page' => $p])); ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo htmlspecialchars(buildUrl(['page' => min($totalPages, $page + 1)])); ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
