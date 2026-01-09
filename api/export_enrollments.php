<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$db = new Database();
$conn = $db->getConnection();

$enrollments = $conn->query("
    SELECT e.id, e.student_id, e.course_id, e.enrolled_at, e.progress_percentage,
           u.full_name as student_name, u.email as student_email,
           c.title as course_title
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Create CSV content
$csv = "ID,Student Name,Student Email,Course Title,Enrolled Date,Progress (%)\n";

foreach ($enrollments as $enrollment) {
    $csv .= "{$enrollment['id']},\"{$enrollment['student_name']}\",\"{$enrollment['student_email']}\",\"{$enrollment['course_title']}\",{$enrollment['enrolled_at']},{$enrollment['progress_percentage']}\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=enrollments_export_' . date('Y-m-d') . '.csv');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
?>
