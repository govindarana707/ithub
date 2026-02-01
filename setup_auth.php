<?php
require_once 'config/config.php';

echo "<h2>🔧 Setting Up Enhanced Authentication System</h2>";

// Run the database setup
echo "<h3>📊 Setting up Database Tables</h3>";

try {
    $conn = connectDB();
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/email_verification_system.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if ($conn->query($statement)) {
                    echo "✅ Successfully executed: " . substr($statement, 0, 50) . "...<br>";
                } else {
                    echo "❌ Error executing: " . $conn->error . "<br>";
                }
            }
        }
    } else {
        echo "❌ SQL file not found: $sqlFile<br>";
    }
    
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p>Your authentication system has been enhanced with:</p>";
    echo "<ul>";
    echo "<li>✅ Email verification system</li>";
    echo "<li>✅ Rate limiting (3 registrations/5min, 5 logins/5min)</li>";
    echo "<li>✅ Enhanced password policy (8+ chars, uppercase, lowercase, number, special)</li>";
    echo "<li>✅ Account lockout mechanism (15 min lock after 5 failed attempts)</li>";
    echo "<li>✅ CAPTCHA protection</li>";
    echo "<li>✅ Password strength indicator</li>";
    echo "</ul>";
    
    echo "<h3>🔍 Testing Instructions</h3>";
    echo "<ol>";
    echo "<li>Visit <a href='register.php'>register.php</a> to test the enhanced registration</li>";
    echo "<li>Try registering with weak passwords to see validation</li>";
    echo "<li>Check browser console for email verification logs (localhost)</li>";
    echo "<li>Test rate limiting by submitting multiple forms quickly</li>";
    echo "<li>Visit <a href='login.php'>login.php</a> to test enhanced login</li>";
    echo "</ol>";
    
    echo "<h3>📧 Email Setup (Production)</h3>";
    echo "<p>For production deployment, update the <code>sendVerificationEmail()</code> method in <code>includes/AuthEnhancements.php</code> to use a proper email service like PHPMailer.</p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage();
}
?>
