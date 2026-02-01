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
$format = $_GET['export'] ?? 'csv';
$selectedIds = $_GET['ids'] ?? '';

// Get filters from URL parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'category_id' => $_GET['category'] ?? '',
    'status' => $_GET['status'] ?? '',
    'instructor_id' => $_GET['instructor'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'difficulty' => $_GET['difficulty'] ?? ''
];

if (!empty($selectedIds)) {
    $idsArray = explode(',', $selectedIds);
    $courses = [];
    foreach ($idsArray as $id) {
        $courseData = $course->getCourseById(intval($id));
        if ($courseData) {
            $courses[] = $courseData;
        }
    }
} else {
    $courses = $course->getAdminCourses($filters, 1000, 0);
}

switch ($format) {
    case 'csv':
        exportCSV($courses);
        break;
    case 'excel':
        exportExcel($courses);
        break;
    case 'pdf':
        exportPDF($courses);
        break;
    default:
        exportCSV($courses);
}

function exportCSV($courses) {
    $csv = "ID,Title,Description,Category,Instructor,Price,Duration,Difficulty,Status,Students,Avg Progress,Created At\n";

    foreach ($courses as $course) {
        $title = str_replace('"', '""', $course['title']);
        $description = str_replace('"', '""', strip_tags($course['description']));
        $category = str_replace('"', '""', $course['category_name'] ?? 'N/A');
        $instructor = str_replace('"', '""', $course['instructor_name'] ?? 'N/A');
        
        $csv .= "{$course['id']},\"{$title}\",\"{$description}\",\"{$category}\",\"{$instructor}\",{$course['price']},{$course['duration_hours']},{$course['difficulty_level']},{$course['status']}," . ($course['enrollment_count'] ?? 0) . "," . ($course['avg_progress'] ?? 0) . ",{$course['created_at']}\n";
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=courses_export_' . date('Y-m-d_H-i-s') . '.csv');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $csv;
}

function exportExcel($courses) {
    // Simple Excel export using CSV format with Excel headers
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=courses_export_' . date('Y-m-d_H-i-s') . '.xls');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $csv = "ID\tTitle\tDescription\tCategory\tInstructor\tPrice\tDuration\tDifficulty\tStatus\tStudents\tAvg Progress\tCreated At\n";

    foreach ($courses as $course) {
        $title = str_replace(["\t", "\n"], ' ', $course['title']);
        $description = str_replace(["\t", "\n"], ' ', strip_tags($course['description']));
        $category = str_replace(["\t", "\n"], ' ', $course['category_name'] ?? 'N/A');
        $instructor = str_replace(["\t", "\n"], ' ', $course['instructor_name'] ?? 'N/A');
        
        $csv .= "{$course['id']}\t{$title}\t{$description}\t{$category}\t{$instructor}\t{$course['price']}\t{$course['duration_hours']}\t{$course['difficulty_level']}\t{$course['status']}\t" . ($course['enrollment_count'] ?? 0) . "\t" . ($course['avg_progress'] ?? 0) . "\t{$course['created_at']}\n";
    }

    echo $csv;
}

function exportPDF($courses) {
    require_once '../includes/PDFGenerator.php';
    
    // Generate print-friendly HTML with proper PDF styling
    $html = createSimplePDF($courses);
    
    // Set headers to force download as HTML file that can be printed to PDF
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="courses_export_' . date('Y-m-d_H-i-s') . '.html"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $html;
}
?>
