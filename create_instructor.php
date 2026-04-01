<?php
require_once 'config/config.php';

echo "<h2>Create Instructor User Tool</h2>";

// Connect to database
$conn = connectDB();

// Check if instructor@ithub.com already exists
$stmt = $conn->prepare("SELECT id FROM users_new WHERE email = ?");
$email = "instructor@ithub.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>ℹ️ User with email 'instructor@ithub.com' already exists.</p>";
    
    // Update the password for existing user
    $newPassword = "instructor123";
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("UPDATE users_new SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $email);
    
    if ($updateStmt->execute()) {
        echo "<p style='color: green;'>✅ Password updated for existing instructor user!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating password: " . $conn->error . "</p>";
    }
} else {
    // Create new instructor user
    $username = "instructor";
    $email = "instructor@ithub.com";
    $password = "instructor123";
    $fullName = "Instructor User";
    $role = "instructor";
    $phone = "1234567890";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users_new (username, email, password, full_name, role, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $status = 'active';
    $stmt->bind_param("sssssss", $username, $email, $hashedPassword, $fullName, $role, $phone, $status);
    
    if ($stmt->execute()) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "<h3>✅ Instructor User Created Successfully!</h3>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p><strong>Password:</strong> <code>instructor123</code></p>";
        echo "<p><strong>Full Name:</strong> " . htmlspecialchars($fullName) . "</p>";
        echo "<p><strong>Role:</strong> " . htmlspecialchars($role) . "</p>";
        echo "<p><strong>Status:</strong> active</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Error creating instructor user: " . $conn->error . "</p>";
    }
}

echo "<h3>Login Credentials:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Email:</strong> <code>instructor@ithub.com</code></p>";
echo "<p><strong>Password:</strong> <code>instructor123</code></p>";
echo "<p><strong>Role:</strong> <code>instructor</code></p>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to the login page: <a href='login.php'>Login</a></li>";
echo "<li>Use email: <strong>instructor@ithub.com</strong></li>";
echo "<li>Use password: <strong>instructor123</strong></li>";
echo "<li>The login should now work successfully</li>";
echo "</ol>";

echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

$conn->close();
?>
