<?php
require_once 'config/config.php';
require_once 'includes/AuthEnhancements.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if (!empty($token)) {
    $auth = new AuthEnhancements();
    $conn = connectDB();
    
    // Find verification token
    $stmt = $conn->prepare("
        SELECT ev.user_id, ev.email, ev.expires_at 
        FROM email_verifications ev
        WHERE ev.verification_token = ? AND ev.is_verified = FALSE
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $verification = $result->fetch_assoc();
        
        // Check if token is still valid
        if (strtotime($verification['expires_at']) > time()) {
            // Mark as verified
            $conn->begin_transaction();
            
            try {
                // Update verification table
                $stmt = $conn->prepare("
                    UPDATE email_verifications 
                    SET is_verified = TRUE 
                    WHERE verification_token = ?
                ");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                // Update user table
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET email_verified = TRUE, verification_token = NULL, verification_expires_at = NULL 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $verification['user_id']);
                $stmt->execute();
                
                $conn->commit();
                
                $success = true;
                $message = "Email verified successfully! You can now login to your account.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Verification failed. Please try again or contact support.";
            }
        } else {
            $message = "Verification link has expired. Please register again.";
        }
    } else {
        $message = "Invalid verification link.";
    }
} else {
    $message = "No verification token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center">
                        <?php if ($success): ?>
                            <div class="text-success mb-4">
                                <i class="fas fa-check-circle fa-4x"></i>
                            </div>
                            <h3 class="card-title text-success">Email Verified!</h3>
                            <p class="card-text"><?php echo $message; ?></p>
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
                            </a>
                        <?php else: ?>
                            <div class="text-danger mb-4">
                                <i class="fas fa-exclamation-triangle fa-4x"></i>
                            </div>
                            <h3 class="card-title text-danger">Verification Failed</h3>
                            <p class="card-text"><?php echo $message; ?></p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Register Again
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
