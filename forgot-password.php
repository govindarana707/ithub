<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - IT HUB</title>
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
                            <p class="text-muted">Reset your password</p>
                        </div>

                        <!-- Forgot Password Form -->
                        <form id="forgotPasswordForm" method="POST" action="process_forgot_password.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-text">Enter your email address and we'll send you a link to reset your password.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>

                            <div class="text-center">
                                <p>Remember your password? <a href="login.php">Sign in here</a></p>
                            </div>
                        </form>
                        <!-- /Forgot Password Form -->

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
        // AJAX forgot password submission
        $('#forgotPasswordForm').submit(function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');

            $.ajax({
                url: 'process_forgot_password.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        submitBtn.prop('disabled', true).html('<i class="fas fa-check me-2"></i>Email Sent');
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
            $('#forgotPasswordForm').prepend(alertHtml);
        }
    });
    </script>
</body>
</html>
