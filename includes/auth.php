<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/csrf.php';

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        if (isset($_SESSION['expires_on']) && time() > $_SESSION['expires_on']) {
            session_destroy();
            redirect('login.php?error=session_expired');
            exit;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_on'] = time() + 1800; // Reset 30 minute timeout
    }
}

// Check session timeout on every page load
checkSessionTimeout();

/**
 * Allow only Admin
 */
function requireAdmin(): void
{
    if (!isLoggedIn() || getUserRole() !== 'admin') {
        $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
        redirect('dashboard.php');
        exit;
    }
}

/**
 * Allow Instructor + Admin
 */
function requireInstructor(): void
{
    if (!isLoggedIn() || !in_array(getUserRole(), ['instructor', 'admin'], true)) {
        $_SESSION['error_message'] = 'Access denied. Instructor privileges required.';
        redirect('dashboard.php');
        exit;
    }
}

/**
 * Allow Student + Admin
 */
function requireStudent(): void
{
    if (!isLoggedIn() || !in_array(getUserRole(), ['student', 'admin'], true)) {
        $_SESSION['error_message'] = 'Access denied. Student privileges required.';
        redirect('dashboard.php');
        exit;
    }
}

/* =========================================================
   DASHBOARD DATA PROVIDER
   ========================================================= */

function getDashboardData(int $userId, string $role): array
{
    $conn = connectDB();
    $data = [];
    
    // Ensure role is lowercase for consistency
    $role = strtolower($role);
    
    switch ($role) {

        /* ================= ADMIN ================= */
        case 'admin':

            $queries = [
                'total_users'       => "SELECT COUNT(*) total FROM users",
                'total_courses'     => "SELECT COUNT(*) total FROM courses",
                'total_enrollments' => "SELECT COUNT(*) total FROM enrollments",
                'total_attempts'    => "SELECT COUNT(*) total FROM quiz_attempts"
            ];

            foreach ($queries as $key => $sql) {
                $result = $conn->query($sql);
                $data[$key] = $result ? (int)$result->fetch_assoc()['total'] : 0;
            }

            $result = $conn->query("
                SELECT al.action, al.details, al.created_at, u.full_name
                FROM admin_logs al
                JOIN users u ON u.id = al.user_id
                ORDER BY al.created_at DESC
                LIMIT 10
            ");

            $data['recent_activities'] = $result
                ? $result->fetch_all(MYSQLI_ASSOC)
                : [];

            break;

        /* ================= INSTRUCTOR ================= */
        case 'instructor':

            $stmt = $conn->prepare("
                SELECT COUNT(*) total
                FROM courses
                WHERE instructor_id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['total_courses'] = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT e.student_id) total
                FROM enrollments e
                JOIN courses c ON c.id = e.course_id
                WHERE c.instructor_id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['total_students'] = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT c.id, c.title, c.status, COUNT(e.id) enrollment_count
                FROM courses c
                LEFT JOIN enrollments e ON e.course_id = c.id
                WHERE c.instructor_id = ?
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['recent_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            break;

        /* ================= STUDENT ================= */
        case 'student':

            $stmt = $conn->prepare("
                SELECT COUNT(*) total
                FROM enrollments
                WHERE student_id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['enrolled_courses'] = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT COUNT(*) total
                FROM quiz_attempts
                WHERE student_id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['quiz_attempts'] = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT AVG(percentage) avg_score
                FROM quiz_attempts
                WHERE student_id = ?
                  AND status = 'completed'
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $avg = $stmt->get_result()->fetch_assoc()['avg_score'];
            $data['average_score'] = $avg ? round($avg, 2) : 0;
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT c.id, c.title, c.description,
                       e.progress_percentage, e.enrolled_at
                FROM enrollments e
                JOIN courses c ON c.id = e.course_id
                WHERE e.student_id = ?
                  AND e.status = 'active'
                ORDER BY e.enrolled_at DESC
                LIMIT 5
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data['enrolled_courses_list'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            break;
    }

    $conn->close();
    return $data;
}
