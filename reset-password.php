<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - IT HUB</title>
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
                            <p class="text-muted">Set your new password</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Reset Password Form -->
                        <form id="resetPasswordForm" method="POST" action="process_reset_password.php">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-key me-2"></i>Reset Password
                            </button>

                            <div class="text-center">
                                <p>Remember your password? <a href="login.php">Sign in here</a></p>
                            </div>
                        </form>
                        <!-- /Reset Password Form -->

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

        // AJAX reset password submission
        $('#resetPasswordForm').submit(function(e) {
            e.preventDefault();

            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long', 'danger');
                return;
            }
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'danger');
                return;
            }

            const formData = $(this).serialize();
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Resetting...');

            $.ajax({
                url: 'process_reset_password.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        showAlert(response.message, 'danger');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showAlert('An error occurred. Please try again.', 'danger');
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
            $('#resetPasswordForm').prepend(alertHtml);
        }
    });
    </script>
</body>
</html>
