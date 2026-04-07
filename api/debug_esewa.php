<?php
// Debug endpoint to test eSewa payment API
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>eSewa Payment API Debug</h1>";

// Test 1: Basic PHP
echo "<h2>1. Basic PHP</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Working directory: " . getcwd() . "<br>";

// Test 2: Check files exist
echo "<h2>2. File Existence</h2>";
$files = [
    '../includes/session_helper.php',
    '../config/config.php',
    '../models/User.php',
    '../includes/AuthEnhancements.php',
    '../services/PaymentService.php',
    '../services/SignatureService.php',
    '../services/EnrollmentServiceNew.php',
    '../models/Course.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    echo $file . ": " . (file_exists($fullPath) ? "EXISTS" : "MISSING") . "<br>";
}

// Test 3: Try loading files one by one
echo "<h2>3. Loading Files</h2>";
try {
    echo "Loading session_helper...<br>";
    require_once '../includes/session_helper.php';
    echo "OK<br>";
    
    echo "Loading config...<br>";
    require_once '../config/config.php';
    echo "OK<br>";
    
    echo "Loading User model...<br>";
    require_once '../models/User.php';
    echo "OK<br>";
    
    echo "Loading AuthEnhancements...<br>";
    require_once '../includes/AuthEnhancements.php';
    echo "OK<br>";
    
    echo "Loading PaymentService...<br>";
    require_once '../services/PaymentService.php';
    echo "OK<br>";
    
    echo "Loading EnrollmentServiceNew...<br>";
    require_once '../services/EnrollmentServiceNew.php';
    echo "OK<br>";
    
    echo "Loading Course model...<br>";
    require_once '../models/Course.php';
    echo "OK<br>";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Check database
echo "<h2>4. Database Check</h2>";
try {
    $conn = connectDB();
    echo "Database connection: OK<br>";
    
    // Check payments table
    $result = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($result->num_rows > 0) {
        echo "payments table: EXISTS<br>";
        
        // Check columns
        $cols = $conn->query("DESCRIBE payments");
        echo "<h3>payments table columns:</h3><ul>";
        while ($col = $cols->fetch_assoc()) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "payments table: MISSING<br>";
    }
    
    // Check payment_settings table
    $result = $conn->query("SHOW TABLES LIKE 'payment_settings'");
    if ($result->num_rows > 0) {
        echo "payment_settings table: EXISTS<br>";
    } else {
        echo "payment_settings table: MISSING<br>";
    }
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>Done</h2>";
