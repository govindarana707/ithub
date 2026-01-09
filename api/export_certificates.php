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

$certificates = $conn->query("
    SELECT c.id, c.student_id, c.course_id, c.certificate_code, c.issued_at,
           u.full_name as student_name, u.email as student_email,
           c.title as course_title
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    ORDER BY c.issued_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Create CSV content
$csv = "ID,Student Name,Student Email,Course Title,Certificate Code,Issued At\n";

foreach ($certificates as $certificate) {
    $csv .= "{$certificate['id']},\"{$certificate['student_name']}\",\"{$certificate['student_email']}\",\"{$certificate['course_title']}\",{$certificate['certificate_code']},{$certificate['issued_at']}\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=certificates_export_' . date('Y-m-d') . '.csv');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
?>
