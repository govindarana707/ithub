<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                            <h2>Join IT HUB</h2>
                            <p class="text-muted">Create your account and start learning</p>
                        </div>
                        
                        <form id="registerForm" method="POST" action="process_register.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-at"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">I want to join as</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select your role</option>
                                    <option value="student">Student</option>
                                    <option value="instructor">Instructor</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="form-text">Password must be at least 8 characters long</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                            
                            <div class="text-center">
                                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By registering and using IT HUB, you agree to comply with and be bound by these terms and conditions.</p>
                    
                    <h6>2. User Responsibilities</h6>
                    <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                    
                    <h6>3. Course Content</h6>
                    <p>All course materials are provided for educational purposes only. Unauthorized distribution or reproduction is prohibited.</p>
                    
                    <h6>4. Privacy Policy</h6>
                    <p>We respect your privacy and are committed to protecting your personal information in accordance with our Privacy Policy.</p>
                    
                    <h6>5. Code of Conduct</h6>
                    <p>Users must maintain professional conduct in all interactions within the platform.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#registerForm').submit(function(e) {
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
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creating account...');
                
                $.ajax({
                    url: 'process_register.php',
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
            
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('#registerForm').prepend(alertHtml);
            }
        });
    </script>
</body>
</html>
