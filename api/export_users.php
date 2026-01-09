<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$user = new User();

// Get all users for export
$users = $user->getAllUsers();

// Create CSV content
$csv = "ID,Username,Email,Full Name,Role,Status,Created At\n";

foreach ($users as $user) {
    $csv .= "{$user['id']},{$user['username']},{$user['email']},{$user['full_name']},{$user['role']},{$user['status']},{$user['created_at']}\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d') . '.csv');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
?>
