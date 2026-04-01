<?php
require_once 'config/config.php';

echo "<h2>Reset Admin Password Tool</h2>";

// Connect to database
$conn = connectDB();

// Get admin user
$stmt = $conn->prepare("SELECT id, username, email FROM users_new WHERE email = ?");
$email = "admin@ithub.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    
    // Hash the new password
    $newPassword = "admin123";
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $updateStmt = $conn->prepare("UPDATE users_new SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $admin['id']);
    
    if ($updateStmt->execute()) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "<h3>✅ Password Reset Successful!</h3>";
        echo "<p><strong>User:</strong> " . htmlspecialchars($admin['username']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p><strong>New Password:</strong> <code>admin123</code></p>";
        echo "</div>";
        
        // Verify the password was set correctly
        $verifyStmt = $conn->prepare("SELECT password FROM users_new WHERE id = ?");
        $verifyStmt->bind_param("i", $admin['id']);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result()->fetch_assoc();
        
        if (password_verify($newPassword, $verifyResult['password'])) {
            echo "<p style='color: green;'>✅ Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error updating password: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Admin user not found!</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to the login page: <a href='login.php'>Login</a></li>";
echo "<li>Use email: <strong>admin@ithub.com</strong></li>";
echo "<li>Use password: <strong>admin123</strong></li>";
echo "<li>The login should now work successfully</li>";
echo "</ol>";

echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

$conn->close();
?>
