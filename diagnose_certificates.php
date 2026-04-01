<?php
require_once 'config/config.php';

echo "<h1>Certificate Data Repair Tool</h1>";

// Connect to database
$conn = connectDB();

// Get current logged-in user info (for testing)
session_start();
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['full_name'] ?? 'Not logged in';

echo "<h2>Current Session Info</h2>";
echo "<p><strong>User ID:</strong> $currentUserId</p>";
echo "<p><strong>User Name:</strong> $currentUserName</p>";

echo "<h2>Certificate Ownership Analysis</h2>";

// Check all certificates and their ownership
$stmt = $conn->prepare("
    SELECT c.id, c.certificate_id, c.student_id, c.course_id,
           s.full_name as student_name,
           s.username as student_username,
           co.title as course_title,
           c.issued_at
    FROM certificates c
    LEFT JOIN users_new s ON c.student_id = s.id
    LEFT JOIN courses_new co ON c.course_id = co.id
    ORDER BY c.issued_at DESC
");

if ($stmt) {
    $stmt->execute();
    $certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Cert ID</th><th>Certificate Code</th><th>Student ID</th><th>Student Name</th><th>Username</th><th>Course</th><th>Issued</th><th>Status</th>";
    echo "</tr>";
    
    $issues = [];
    foreach ($certificates as $cert) {
        $status = "OK";
        $rowStyle = "";
        
        // Check for issues
        if (empty($cert['student_name']) || $cert['student_name'] === 'Unknown Student') {
            $status = "Missing Name";
            $rowStyle = "background: #ffe6e6;";
            $issues[] = "Certificate {$cert['certificate_id']} has missing student name";
        }
        
        if (empty($cert['student_username'])) {
            $status = "Missing Username";
            $rowStyle = "background: #fff3cd;";
        }
        
        echo "<tr style='$rowStyle'>";
        echo "<td>{$cert['id']}</td>";
        echo "<td><code>{$cert['certificate_id']}</code></td>";
        echo "<td>{$cert['student_id']}</td>";
        echo "<td><strong>{$cert['student_name']}</strong></td>";
        echo "<td>{$cert['student_username']}</td>";
        echo "<td>{$cert['course_title']}</td>";
        echo "<td>" . date('M j, Y', strtotime($cert['issued_at'])) . "</td>";
        echo "<td><span style='color: " . ($status === "OK" ? "green" : "red") . ";'>$status</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!empty($issues)) {
        echo "<h3 style='color: red;'>Issues Found:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
    }
    
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Users Verification</h2>";

// Show all users to verify Govinda Rana exists
$stmt = $conn->prepare("SELECT id, username, full_name, role, created_at FROM users_new ORDER BY created_at DESC");
if ($stmt) {
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<h3>All Users (Recent First):</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Created</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $rowStyle = "";
        if (strpos(strtolower($user['full_name']), 'govinda') !== false || strpos(strtolower($user['username']), 'govinda') !== false) {
            $rowStyle = "background: #d4edda; font-weight: bold;";
        }
        
        echo "<tr style='$rowStyle'>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>" . date('M j, Y', strtotime($user['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $stmt->close();
}

echo "<h2>Test Certificate Query for Govinda</h2>";

// Test query specifically for Govinda Rana
$stmt = $conn->prepare("
    SELECT c.id, c.certificate_id, c.student_id,
           s.full_name as student_name,
           s.username as student_username,
           co.title as course_title
    FROM certificates c
    LEFT JOIN users_new s ON c.student_id = s.id
    LEFT JOIN courses_new co ON c.course_id = co.id
    WHERE s.full_name LIKE '%Govinda%' OR s.username LIKE '%govinda%'
");

if ($stmt) {
    $stmt->execute();
    $govindaCerts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<h3>Govinda's Certificates:</h3>";
    if (empty($govindaCerts)) {
        echo "<p style='color: orange;'>No certificates found for Govinda Rana</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Cert ID</th><th>Certificate Code</th><th>Student Name</th><th>Course</th></tr>";
        foreach ($govindaCerts as $cert) {
            echo "<tr>";
            echo "<td>{$cert['id']}</td>";
            echo "<td><code>{$cert['certificate_id']}</code></td>";
            echo "<td><strong>{$cert['student_name']}</strong></td>";
            echo "<td>{$cert['course_title']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    $stmt->close();
}

$conn->close();

echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Check if Govinda Rana appears in the users table above</li>";
echo "<li>Verify if certificates show correct student names</li>";
echo "<li>If issues found, run the repair script below</li>";
echo "</ol>";
echo "</div>";
?>
