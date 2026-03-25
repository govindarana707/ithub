<?php
require_once 'config/config.php';
require_once 'models/Course.php';

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    $_SESSION['error_message'] = 'No course selected';
    redirect('courses.php');
}

// Validate user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=billing.php?course_id=' . intval($_GET['course_id']));
}

// Validate user role
if (getUserRole() !== 'student') {
    $_SESSION['error_message'] = 'Only students can enroll in courses';
    redirect('courses.php');
}

$courseId = intval($_GET['course_id']);
$userId = $_SESSION['user_id'];

// Initialize course model with error handling
$course = new Course();

try {
    $courseDetails = $course->getCourseById($courseId);
} catch (Exception $e) {
    error_log("Course fetch error: " . $e->getMessage());
    $courseDetails = null;
}

if (!$courseDetails) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('courses.php');
}

if (isset($courseDetails['status']) && $courseDetails['status'] !== 'published') {
    $_SESSION['error_message'] = 'This course is not available for enrollment';
    redirect('courses.php');
}

// Check if already enrolled
try {
    require_once 'services/EnrollmentServiceNew.php';
    $enrollmentService = new EnrollmentServiceNew();
    
    if ($enrollmentService->isUserEnrolled($userId, $courseId)) {
        $_SESSION['info_message'] = 'You are already enrolled in this course';
        redirect('student/my-courses.php');
    }
} catch (Exception $e) {
    error_log("Enrollment check error: " . $e->getMessage());
    // Continue anyway - don't block enrollment for service errors
}

// Get instructor details
$conn = connectDB();
$instructor = null;

// Safely check for instructor_id
$instructorId = isset($courseDetails['instructor_id']) ? $courseDetails['instructor_id'] : null;

if ($instructorId) {
    // Try users_new first, then users
    $stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_image FROM users_new WHERE id = ? AND role = 'instructor' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $instructor = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // If not found, try users table
    if (!$instructor) {
        $stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_image FROM users WHERE id = ? AND role = 'instructor' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $instructor = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
}
$conn->close();

// Store course_id in session for payment processing
$_SESSION['billing_course_id'] = $courseId;
$_SESSION['billing_user_id'] = $userId;

// Generate CSRF token for forms
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in <?php echo htmlspecialchars($courseDetails['title']); ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="student/css/student-theme.css" rel="stylesheet">
    <style>
        .billing-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .billing-header {
            background: var(--gradient-primary);
            color: white;
            padding: 30px;
            border-radius: var(--radius-md);
            margin-bottom: 30px;
        }
        
        .course-summary {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: var(--transition);
            background: var(--bg-primary);
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option .check-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            color: var(--primary-color);
            transition: var(--transition);
        }
        
        .payment-option.selected .check-icon {
            opacity: 1;
        }
        
        .payment-icon {
            font-size: 32px;
            width: 60px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            margin-right: 15px;
        }
        
        .esewa-icon { background: var(--gradient-success); color: white; }
        .khalti-icon { background: var(--gradient-info); color: white; }
        .trial-icon { background: var(--gradient-warning); color: white; }
        
        .proceed-btn {
            background: var(--gradient-primary);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: var(--font-weight-medium);
            color: white;
        }
        
        .proceed-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .proceed-btn:disabled {
            background: var(--gray-color);
            cursor: not-allowed;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: var(--font-weight-medium);
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .price-tag {
            font-size: 28px;
            font-weight: var(--font-weight-bold);
            color: var(--primary-color);
        }
        
        .original-price {
            text-decoration: line-through;
            color: var(--gray-color);
            font-size: 18px;
        }
        
        .free-tag {
            background: var(--gradient-success);
            color: white;
            padding: 5px 15px;
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-bold);
        }
        
        /* Khalti Payment Modal Styles */
        .khalti-modal .modal-body {
            padding: 30px;
        }
        
        .khalti-payment-form {
            text-align: center;
        }
        
        .khalti-btn {
            background: #7733e6;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .khalti-btn:hover {
            background: #6629c7;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="billing-container">
        <!-- Back Link -->
        <a href="course-details.php?id=<?php echo $courseId; ?>" class="back-link">
            <i class="fas fa-arrow-left me-2"></i>Back to Course Details
        </a>
        
        <!-- Header -->
        <div class="billing-header">
            <h2 class="mb-2"><i class="fas fa-credit-card me-2"></i>Complete Your Enrollment</h2>
            <p class="mb-0">Choose your preferred payment method to enroll in this course</p>
        </div>
        
        <!-- Course Summary -->
        <div class="course-summary">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <?php if ($courseDetails['thumbnail']): ?>
                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseDetails['thumbnail'])); ?>" 
                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($courseDetails['title']); ?>">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 120px;">
                            <i class="fas fa-book fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h4><?php echo htmlspecialchars($courseDetails['title']); ?></h4>
                    <div class="d-flex flex-wrap gap-3 text-muted mb-2">
                        <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($courseDetails['instructor_name']); ?></span>
                        <span><i class="fas fa-clock me-1"></i> <?php echo $courseDetails['duration_hours']; ?> hours</span>
                        <span><i class="fas fa-signal me-1"></i> <?php echo ucfirst($courseDetails['difficulty_level']); ?></span>
                    </div>
                    <?php if ($courseDetails['price'] > 0): ?>
                        <div class="price-tag">
                            Rs <?php echo number_format($courseDetails['price'], 2); ?>
                        </div>
                    <?php else: ?>
                        <span class="free-tag">FREE</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Options -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Select Payment Method</h5>
            </div>
            <div class="card-body">
                <!-- eSewa Option -->
                <?php if ($courseDetails['price'] > 0): ?>
                <label class="payment-option position-relative" id="option-esewa">
                    <input type="radio" name="payment_method" value="esewa">
                    <div class="d-flex align-items-center">
                        <div class="payment-icon esewa-icon">
                            <img src="assets/images/esewa.png" alt="eSewa" style="height: 40px; width: auto; object-fit: contain;">
                        </div>
                        <div>
                            <h5 class="mb-1">Pay with eSewa</h5>
                            <p class="text-muted mb-0">Secure payment via eSewa mobile app or web</p>
                        </div>
                    </div>
                    <i class="fas fa-check-circle check-icon"></i>
                </label>
                
                <!-- Khalti Option -->
                <label class="payment-option position-relative" id="option-khalti">
                    <input type="radio" name="payment_method" value="khalti">
                    <div class="d-flex align-items-center">
                        <div class="payment-icon khalti-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Pay with Khalti</h5>
                            <p class="text-muted mb-0">Pay using Khalti digital wallet</p>
                        </div>
                    </div>
                    <i class="fas fa-check-circle check-icon"></i>
                </label>
                <?php endif; ?>
                
                <!-- Free Trial Option -->
                <label class="payment-option position-relative" id="option-trial">
                    <input type="radio" name="payment_method" value="trial">
                    <div class="d-flex align-items-center">
                        <div class="payment-icon trial-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Free Trial</h5>
                            <p class="text-muted mb-0">Start learning with 30-day free trial</p>
                        </div>
                    </div>
                    <i class="fas fa-check-circle check-icon"></i>
                </label>
                
                <!-- Error Message -->
                <div id="payment-error" class="alert alert-danger mt-3" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Proceed Button -->
        <div class="text-center">
            <button type="button" id="proceedBtn" class="proceed-btn text-white" disabled>
                <i class="fas fa-arrow-right me-2"></i>Proceed to Payment
            </button>
        </div>
        
        <!-- Loading Overlay - created dynamically -->
        
        <!-- Khalti Payment Modal -->
        <div class="modal fade" id="khaltiModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pay with Khalti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <img src="https://khalti.com/static/images/khalti-logo.png" alt="Khalti" style="max-width: 150px;">
                        </div>
                        <p class="text-center">You will be redirected to Khalti to complete your payment of <strong>Rs <?php echo number_format($courseDetails['price'], 2); ?></strong></p>
                        <div class="d-grid gap-2">
                            <button type="button" id="initiateKhaltiPayment" class="khalti-btn">
                                <i class="fas fa-external-link-alt me-2"></i>Pay Now
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- eSewa Form (Hidden) -->
        <div id="esewaFormContainer" style="display: none;"></div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection
        const paymentOptions = document.querySelectorAll('.payment-option');
        const proceedBtn = document.getElementById('proceedBtn');
        let selectedMethod = null;
        
        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Enable proceed button
                selectedMethod = radio.value;
                proceedBtn.disabled = false;
                
                // Update button text based on selection
                if (selectedMethod === 'trial') {
                    proceedBtn.innerHTML = '<i class="fas fa-gift me-2"></i>Start Free Trial';
                } else if (selectedMethod === 'esewa') {
                    proceedBtn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Pay with eSewa';
                } else if (selectedMethod === 'khalti') {
                    proceedBtn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Pay with Khalti';
                }
                
                // Hide any error message
                document.getElementById('payment-error').style.display = 'none';
            });
        });
        
        // Proceed button click handler
        proceedBtn.addEventListener('click', function() {
            if (!selectedMethod) {
                showError('Please select a payment method');
                return;
            }
            
            if (selectedMethod === 'trial') {
                processFreeTrial();
            } else if (selectedMethod === 'esewa') {
                processESewa();
            } else if (selectedMethod === 'khalti') {
                processKhalti();
            }
        });
        
        // Process Free Trial
        function processFreeTrial() {
            showLoading('Starting your free trial...');
            
            const formData = new FormData();
            formData.append('course_id', '<?php echo $courseId; ?>');
            formData.append('payment_method', 'trial');
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            
            fetch('api/enroll_course.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    // Show success and redirect
                    showSuccessAndRedirect(data);
                } else {
                    showError(data.message || 'Enrollment failed. Please try again.');
                }
            })
            .catch(error => {
                hideLoading();
                showError('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
        
        // Process eSewa Payment
        function processESewa() {
            showLoading('Initiating eSewa payment...');
            
            const formData = new FormData();
            formData.append('course_id', '<?php echo $courseId; ?>');
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            
            fetch('api/esewa_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success && data.payment_form) {
                    // Create and submit the eSewa form
                    const formContainer = document.getElementById('esewaFormContainer');
                    const formHtml = `
                        <form id="esewaPaymentForm" method="POST" action="${data.payment_form.form_action}">
                            ${Object.entries(data.payment_form.form_data).map(([key, value]) => 
                                `<input type="hidden" name="${key}" value="${value}">`
                            ).join('')}
                        </form>
                    `;
                    formContainer.innerHTML = formHtml;
                    document.getElementById('esewaPaymentForm').submit();
                } else {
                    showError(data.message || 'Failed to initiate eSewa payment');
                }
            })
            .catch(error => {
                hideLoading();
                showError('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
        
        // Process Khalti Payment - Show modal first
        function processKhalti() {
            const khaltiModal = new bootstrap.Modal(document.getElementById('khaltiModal'));
            khaltiModal.show();
        }
        
        // Initialize Khalti Payment
        document.getElementById('initiateKhaltiPayment').addEventListener('click', function() {
            showLoading('Initiating Khalti payment...');
            
            const formData = new FormData();
            formData.append('course_id', '<?php echo $courseId; ?>');
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            
            fetch('api/khalti_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                // Close modal first
                try {
                    bootstrap.Modal.getInstance(document.getElementById('khaltiModal')).hide();
                } catch(e) {}
                
                if (data.success) {
                    // Handle free course case
                    if (data.free_course) {
                        // If free course, redirect to enroll API
                        window.location.href = data.redirect_url + '?course_id=<?php echo $courseId; ?>&payment_method=trial&csrf_token=<?php echo $csrfToken; ?>';
                        return;
                    }
                    
                    // Handle payment URL
                    if (data.payment_url) {
                        window.location.href = data.payment_url;
                    } else {
                        showError(data.message || 'Failed to get payment URL');
                    }
                } else {
                    showError(data.message || 'Failed to initiate Khalti payment');
                }
            })
            .catch(error => {
                hideLoading();
                try {
                    bootstrap.Modal.getInstance(document.getElementById('khaltiModal')).hide();
                } catch(e) {}
                showError('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
        
        // Helper functions - dynamically create/remove loading overlay
        function showLoading(message) {
            var overlay = document.getElementById('loadingOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'loadingOverlay';
                overlay.className = 'position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center';
                overlay.style.cssText = 'z-index: 9999;';
                overlay.innerHTML = '<div class="bg-white p-4 rounded text-center"><div class="loading-spinner"></div><p class="mb-0">Processing...</p></div>';
                document.body.appendChild(overlay);
            }
            overlay.querySelector('p').textContent = message;
            overlay.style.display = 'flex';
        }
        
        function hideLoading() {
            var overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('payment-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        function showSuccessAndRedirect(data) {
            // Store success data in sessionStorage for the success page
            sessionStorage.setItem('enrollmentSuccess', JSON.stringify(data));
            window.location.href = 'student/enrollment-success.php?course_id=<?php echo $courseId; ?>';
        }
        
    </script>
</body>
</html>