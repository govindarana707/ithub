<?php
require_once 'includes/session_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                        <h2>IT HUB</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <!-- Login Form -->
                    <form id="loginForm" method="POST" action="process_login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>

                        <div class="text-center">
                            <p><a href="forgot-password.php">Forgot password?</a></p>
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </form>
                    <!-- /Login Form -->

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // AJAX login submission
    $('#loginForm').submit(function(e) {
        e.preventDefault();

        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Logging in...');

        $.ajax({
            url: 'process_login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Redirect to dashboard based on role
                    window.location.href = response.redirect;
                } else {
                    // Enhanced error handling for rate limiting
                    let errorMessage = response.message;
                    let alertType = 'danger';
                    
                    if (response.locked) {
                        alertType = 'warning';
                        // Add countdown timer for rate limit
                        if (response.remaining_minutes) {
                            errorMessage += `<br><small>You can try again in <span id="countdown">${response.remaining_minutes}</span> minute(s).</small>`;
                            startCountdown(response.remaining_minutes);
                        }
                    }
                    
                    showAlert(errorMessage, alertType);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Login Error Details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                // Try to parse error response
                let errorMessage = 'An error occurred. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // If JSON parse fails, check for PHP errors
                    if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                        errorMessage = 'Server error occurred. Please contact support.';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Invalid server response. Please try again.';
                    }
                }
                
                showAlert(errorMessage + ' (' + status + ')', 'danger');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Show alert function
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the form
        $('#loginForm').prepend(alertHtml);
        
        // Scroll to top to show the alert
        $('html, body').animate({
            scrollTop: $('#loginForm').offset().top - 100
        }, 300);
    }
    
    // Countdown timer function for rate limiting
    function startCountdown(minutes) {
        let seconds = minutes * 60;
        const countdownElement = $('#countdown');
        
        if (countdownElement.length) {
            const interval = setInterval(function() {
                if (seconds > 0) {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    countdownElement.text(mins + ':' + (secs < 10 ? '0' : '') + secs);
                    seconds--;
                } else {
                    clearInterval(interval);
                    countdownElement.text('now');
                    // Refresh the page after a short delay to allow immediate login
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            }, 1000);
        }
    }
});
</script>
</body>
</html>
