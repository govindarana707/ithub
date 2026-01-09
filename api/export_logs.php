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

$logs = $conn->query("
    SELECT al.*, u.full_name, u.email
    FROM admin_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10000
")->fetch_all(MYSQLI_ASSOC);

// Create CSV content
$csv = "ID,User Name,User Email,Action,Details,IP Address,Created At\n";

foreach ($logs as $log) {
    $csv .= "{$log['id']},\"{$log['full_name']}\",\"{$log['email']}\",{$log['action']},\"{$log['details']}\",{$log['ip_address']},{$log['created_at']}\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=activity_logs_export_' . date('Y-m-d') . '.csv');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
?>
