<?php
// Check admin credentials in database
require_once 'config/config.php';

echo "=== Checking Admin Credentials ===\n\n";

$conn = connectDB();

// Check if admin user exists
$stmt = $conn->prepare("SELECT id, username, email, full_name, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$email = 'admin@ithub.com';
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "✅ Admin user found:\n";
    echo "  - ID: {$user['id']}\n";
    echo "  - Username: {$user['username']}\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - Full Name: {$user['full_name']}\n";
    echo "  - Role: {$user['role']}\n";
    echo "  - Status: {$user['status']}\n";
    
    // Check password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $passwordResult = $stmt->get_result();
    $passwordHash = $passwordResult->fetch_assoc()['password'];
    
    echo "\n🔐 Password verification:\n";
    
    // Test with common passwords
    $testPasswords = ['admin123', 'admin', 'password', '123456', 'ithub'];
    
    foreach ($testPasswords as $testPassword) {
        if (password_verify($testPassword, $passwordHash)) {
            echo "✅ Password matches: '$testPassword'\n";
            echo "🔑 Use this password to login: $testPassword\n";
            break;
        } else {
            echo "❌ Password does not match: '$testPassword'\n";
        }
    }
    
    // If no password matches, create a new admin user with known password
    $found = false;
    foreach ($testPasswords as $testPassword) {
        if (password_verify($testPassword, $passwordHash)) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "\n⚠️  No known password matches. Creating new admin user...\n";
        
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        
        if ($stmt->execute()) {
            echo "✅ Admin password reset to: '$newPassword'\n";
            echo "🔑 New login credentials:\n";
            echo "  - Email: $email\n";
            echo "  - Password: $newPassword\n";
        } else {
            echo "❌ Failed to reset password\n";
        }
    }
    
} else {
    echo "❌ Admin user not found in database\n";
    echo "🔧 Creating admin user...\n";
    
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->bind_param("sssss", $username, $email, $hashedPassword, $fullName, $role);
    
    $username = 'admin';
    $fullName = 'Administrator';
    $role = 'admin';
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully\n";
        echo "🔑 Login credentials:\n";
        echo "  - Email: $email\n";
        echo "  - Password: $password\n";
    } else {
        echo "❌ Failed to create admin user\n";
    }
}

$stmt->close();
$conn->close();

echo "\n=== Login Test ===\n";
echo "🌐 Go to: http://localhost:8000/\n";
echo "📧 Email: admin@ithub.com\n";
echo "🔑 Password: admin123\n";
echo "\n✅ Try logging in with these credentials\n";
?>
