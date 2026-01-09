<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$courseModel = new Course();

$instructorId = $_SESSION['user_id'];

// Filters
$courseId = intval($_GET['course_id'] ?? 0);
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Export
$export = ($_GET['export'] ?? '') === 'csv';

// Fetch instructor courses for filter dropdown
$instructorCourses = $instructor->getInstructorCourses($instructorId, null, 500, 0);

// Base student list (aggregated per student)
$students = $instructor->getInstructorStudents($instructorId, $courseId > 0 ? $courseId : null, $limit, $offset);

// Total rows for pagination (done via separate query for accuracy)
$conn = connectDB();
$countSql = "
    SELECT COUNT(DISTINCT u.id) as total
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.instructor_id = ? AND u.role = 'student'
";
$params = [$instructorId];
$types = 'i';

if ($courseId > 0) {
    $countSql .= " AND c.id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

if ($search !== '') {
    $countSql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$stmt = $conn->prepare($countSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalStudents = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// If we have a search query, filter current page results in PHP for now
// (Instructor::getInstructorStudents does not currently accept search)
if ($search !== '') {
    $students = array_values(array_filter($students, function ($s) use ($search) {
        $q = strtolower($search);
        return (strpos(strtolower((string)($s['full_name'] ?? '')), $q) !== false)
            || (strpos(strtolower((string)($s['email'] ?? '')), $q) !== false)
            || (strpos(strtolower((string)($s['username'] ?? '')), $q) !== false);
    }));
}

// Export CSV (detailed rows per enrollment)
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="instructor_students_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student ID', 'Full Name', 'Email', 'Username', 'Course', 'Progress %', 'Enrollment Status', 'Enrolled At']);

    $exportSql = "
        SELECT u.id as student_id, u.full_name, u.email, u.username,
               c.title as course_title,
               e.progress_percentage, e.status as enrollment_status, e.enrolled_at
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        JOIN courses c ON c.id = e.course_id
        WHERE c.instructor_id = ?
    ";
    $exportParams = [$instructorId];
    $exportTypes = 'i';

    if ($courseId > 0) {
        $exportSql .= " AND c.id = ?";
        $exportParams[] = $courseId;
        $exportTypes .= 'i';
    }

    if ($search !== '') {
        $exportSql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
        $like = "%$search%";
        $exportParams[] = $like;
        $exportParams[] = $like;
        $exportParams[] = $like;
        $exportTypes .= 'sss';
    }

    $exportSql .= " ORDER BY e.enrolled_at DESC";

    $stmt = $conn->prepare($exportSql);
    $stmt->bind_param($exportTypes, ...$exportParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    foreach ($rows as $row) {
        fputcsv($out, [
            $row['student_id'],
            $row['full_name'],
            $row['email'],
            $row['username'],
            $row['course_title'],
            $row['progress_percentage'],
            $row['enrollment_status'],
            $row['enrolled_at'],
        ]);
    }

    fclose($out);
    exit;
}

// Stats cards
$stats = [
    'total' => $totalStudents,
    'active' => 0,
    'completed' => 0,
    'avg_progress' => 0,
];

$statsSql = "
    SELECT
        COUNT(DISTINCT u.id) as total,
        COUNT(DISTINCT CASE WHEN e.status = 'active' THEN u.id END) as active_students,
        COUNT(DISTINCT CASE WHEN e.status = 'completed' OR e.progress_percentage = 100 THEN u.id END) as completed_students,
        AVG(e.progress_percentage) as avg_progress
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    WHERE c.instructor_id = ?
";
$statsParams = [$instructorId];
$statsTypes = 'i';

if ($courseId > 0) {
    $statsSql .= " AND c.id = ?";
    $statsParams[] = $courseId;
    $statsTypes .= 'i';
}

$stmt = $conn->prepare($statsSql);
$stmt->bind_param($statsTypes, ...$statsParams);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if ($row) {
    $stats['total'] = (int)($row['total'] ?? 0);
    $stats['active'] = (int)($row['active_students'] ?? 0);
    $stats['completed'] = (int)($row['completed_students'] ?? 0);
    $stats['avg_progress'] = $row['avg_progress'] ? round((float)$row['avg_progress'], 1) : 0;
}

$totalPages = max(1, (int)ceil($totalStudents / $limit));

function buildStudentsUrl($overrides = []) {
    $base = [
        'course_id' => $_GET['course_id'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? 1,
    ];
    $q = array_merge($base, $overrides);
    // remove empty
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return 'students.php?' . http_build_query($q);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .filter-card {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
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
                <a class="nav-link" href="analytics.php"><i class="fas fa-chart-line me-1"></i> Analytics</a>
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
                    <a href="students.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i> Students
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
                        <h1 class="mb-1">Students</h1>
                        <div class="text-muted">Students enrolled in your courses</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(buildStudentsUrl(['export' => 'csv', 'page' => 1])); ?>">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="h3 mb-0"><?php echo $stats['total']; ?></div>
                            <div class="text-muted">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="h3 mb-0"><?php echo $stats['active']; ?></div>
                            <div class="text-muted">Active</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="h3 mb-0"><?php echo $stats['completed']; ?></div>
                            <div class="text-muted">Completed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="h3 mb-0"><?php echo $stats['avg_progress']; ?>%</div>
                            <div class="text-muted">Avg Progress</div>
                        </div>
                    </div>
                </div>

                <div class="filter-card mb-3">
                    <form class="row g-2" method="GET">
                        <div class="col-md-5">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course_id">
                                <option value="0">All Courses</option>
                                <?php foreach ($instructorCourses as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$courseId === (int)$c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email or username">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button class="btn btn-primary w-100" type="submit">Filter</button>
                                <a class="btn btn-outline-secondary" href="students.php">Clear</a>
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
                                        <th class="text-center">Enrolled Courses</th>
                                        <th class="text-center">Completed</th>
                                        <th>Avg Progress</th>
                                        <th>Last Enrollment</th>
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
                                                $progress = isset($s['avg_progress']) ? round((float)$s['avg_progress'], 1) : 0;
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
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo (int)($s['enrolled_courses'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo (int)($s['completed_courses'] ?? 0); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 10px;">
                                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted" style="min-width: 48px; text-align: right;">
                                                            <?php echo $progress; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        <?php echo !empty($s['last_enrollment']) ? date('M j, Y', strtotime($s['last_enrollment'])) : '-'; ?>
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
                                        <a class="page-link" href="<?php echo htmlspecialchars(buildStudentsUrl(['page' => max(1, $page - 1)])); ?>">Previous</a>
                                    </li>
                                    <?php
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                    ?>
                                    <?php for ($p = $start; $p <= $end; $p++): ?>
                                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(buildStudentsUrl(['page' => $p])); ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo htmlspecialchars(buildStudentsUrl(['page' => min($totalPages, $page + 1)])); ?>">Next</a>
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
