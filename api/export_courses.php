<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

require_once '../models/Course.php';

$course = new Course();
$courses = $course->getAllCourses(1000, 0);

// Create CSV content
$csv = "ID,Title,Description,Category,Instructor,Price,Duration,Difficulty,Status,Created At\n";

foreach ($courses as $course) {
    $csv .= "{$course['id']},\"{$course['title']}\",\"{$course['description']}\",\"{$course['category_name']}\",\"{$course['instructor_name']}\",{$course['price']},{$course['duration_hours']},{$course['difficulty_level']},{$course['status']},{$course['created_at']}\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=courses_export_' . date('Y-m-d') . '.csv');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
?>
