/**
 * Enrollment System JavaScript
 * Handles the complete enrollment flow with payment processing
 */

class EnrollmentSystem {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkPendingPayment();
    }

    bindEvents() {
        // Enroll button clicks
        $(document).on('click', '.enroll-course-btn', (e) => {
            e.preventDefault();
            this.handleEnrollClick($(e.currentTarget));
        });

        // Payment method selection
        $(document).on('change', 'input[name="payment_method"]', (e) => {
            this.handlePaymentMethodChange($(e.currentTarget));
        });

        // Payment option card clicks
        $(document).on('click', '.payment-option', (e) => {
            const method = $(e.currentTarget).data('method');
            this.selectPaymentMethod(method);
        });

        // Proceed payment button
        $(document).on('click', '#proceedPayment', (e) => {
            e.preventDefault();
            this.handlePaymentProcessing();
        });

        // Cancel payment
        $(document).on('click', '.cancel-payment', (e) => {
            e.preventDefault();
            this.cancelPayment();
        });
    }

    /**
     * Handle enroll button click
     */
    handleEnrollClick($btn) {
        const courseId = $btn.data('course-id');
        
        if (!courseId) {
            this.showError('Invalid course ID');
            return;
        }

        // Check if user is logged in
        if (!this.isLoggedIn()) {
            this.showLoginRequired();
            return;
        }

        // Check enrollment status
        this.checkEnrollmentStatus(courseId, $btn);
    }

    /**
     * Check enrollment status before showing payment modal
     */
    checkEnrollmentStatus(courseId, $btn) {
        $.ajax({
            url: 'api/enrollment_api.php',
            method: 'POST',
            data: {
                action: 'check_status',
                course_id: courseId
            },
            dataType: 'json',
            beforeSend: () => {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Checking...');
            },
            success: (response) => {
                $btn.prop('disabled', false).html('<i class="fas fa-plus me-2"></i>Enroll Now');
                
                if (response.success) {
                    const data = response.data;
                    
                    if (data.enrolled) {
                        this.showAlreadyEnrolled(data.course);
                    } else if (data.can_enroll) {
                        this.showPaymentModal(courseId, data.course);
                    } else {
                        this.showError('Course not available for enrollment');
                    }
                } else {
                    this.showError(response.message || 'Failed to check enrollment status');
                }
            },
            error: (xhr, status, error) => {
                $btn.prop('disabled', false).html('<i class="fas fa-plus me-2"></i>Enroll Now');
                this.showError('Network error. Please try again.');
            }
        });
    }

    /**
     * Show payment modal
     */
    showPaymentModal(courseId, course) {
        // Store course data
        $('#proceedPayment').data('course-id', courseId);
        
        // Update course summary
        $('.course-summary strong').text(course.title);
        $('.course-summary small').text(`Duration: ${course.duration_hours || 0} hours`);
        $('.price-tag').text(`Rs${parseFloat(course.price || 0).toFixed(2)}`);
        
        // Reset payment method selection
        this.resetPaymentSelection();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('paymentOptionsModal'));
        modal.show();
    }

    /**
     * Handle payment method change
     */
    handlePaymentMethodChange($input) {
        const method = $input.val();
        this.updatePaymentInfo(method);
        this.highlightPaymentOption(method);
    }

    /**
     * Select payment method
     */
    selectPaymentMethod(method) {
        $(`#${method}`).prop('checked', true);
        this.updatePaymentInfo(method);
        this.highlightPaymentOption(method);
    }

    /**
     * Update payment information display
     */
    updatePaymentInfo(method) {
        const $proceedBtn = $('#proceedPayment');
        const $paymentInfo = $('#selectedPaymentInfo');
        const $paymentInfoText = $('#paymentInfoText');
        
        // Enable proceed button
        $proceedBtn.prop('disabled', false);
        
        // Show payment-specific information
        $paymentInfo.show();
        
        switch(method) {
            case 'esewa':
                $paymentInfoText.html('You will be redirected to Esewa to complete your payment securely.');
                $proceedBtn.html('<i class="fas fa-mobile-alt me-2"></i>Pay with Esewa');
                break;
            case 'khalti':
                $paymentInfoText.html('You will be redirected to Khalti to complete your payment securely.');
                $proceedBtn.html('<i class="fas fa-mobile-alt me-2"></i>Pay with Khalti');
                break;
            case 'trial':
                $paymentInfoText.html('Start your 7-day free trial. No payment required. You can upgrade anytime.');
                $proceedBtn.html('<i class="fas fa-play me-2"></i>Start Free Trial');
                break;
        }
    }

    /**
     * Highlight selected payment option
     */
    highlightPaymentOption(method) {
        $('.payment-option').removeClass('border-primary selected');
        $(`.payment-option[data-method="${method}"]`).addClass('border-primary selected');
    }

    /**
     * Reset payment selection
     */
    resetPaymentSelection() {
        $('input[name="payment_method"]').prop('checked', false);
        $('#proceedPayment').prop('disabled', true).html('<i class="fas fa-arrow-right me-2"></i>Proceed to Payment');
        $('#selectedPaymentInfo').hide();
        $('.payment-option').removeClass('border-primary selected');
    }

    /**
     * Handle payment processing
     */
    handlePaymentProcessing() {
        const courseId = $('#proceedPayment').data('course-id');
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        if (!courseId) {
            this.showError('Course ID not found');
            return;
        }
        
        if (!paymentMethod) {
            this.showError('Please select a payment method');
            return;
        }
        
        const $btn = $('#proceedPayment');
        const originalText = $btn.html();
        
        // Show loading state
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        
        // Process enrollment
        $.ajax({
            url: 'api/enrollment_api.php',
            method: 'POST',
            data: {
                action: 'enroll',
                course_id: courseId,
                payment_method: paymentMethod
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.handleEnrollmentSuccess(response);
                } else {
                    this.handleEnrollmentError(response, $btn, originalText);
                }
            },
            error: (xhr, status, error) => {
                this.handleEnrollmentError({
                    message: 'Network error. Please try again.',
                    error_code: 'NETWORK_ERROR'
                }, $btn, originalText);
            }
        });
    }

    /**
     * Handle successful enrollment
     */
    handleEnrollmentSuccess(response) {
        const $btn = $('#proceedPayment');
        
        if (response.action === 'redirect') {
            // Redirect to payment gateway
            this.redirectToPaymentGateway(response);
        } else {
            // Direct enrollment successful
            this.showEnrollmentSuccess(response);
        }
    }

    /**
     * Redirect to payment gateway
     */
    redirectToPaymentGateway(response) {
        const $btn = $('#proceedPayment');
        
        // Create and submit payment form
        const form = $('<form>', {
            method: 'POST',
            action: response.redirect_url,
            target: '_blank'
        });
        
        // Add form data
        $.each(response.form_data, (key, value) => {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });
        
        // Submit form
        form.appendTo('body').submit();
        
        // Show processing message
        $btn.html('<i class="fas fa-external-link-alt me-2"></i>Redirecting to Payment...');
        
        // Close modal after delay
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('paymentOptionsModal')).hide();
        }, 2000);
    }

    /**
     * Show enrollment success
     */
    showEnrollmentSuccess(response) {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('paymentOptionsModal')).hide();
        
        // Show success message
        this.showSuccess(response.message || 'Enrollment successful!');
        
        // Redirect if needed
        if (response.redirect_url) {
            setTimeout(() => {
                window.location.href = response.redirect_url;
            }, 2000);
        } else {
            // Reload page to update enrollment status
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    }

    /**
     * Handle enrollment error
     */
    handleEnrollmentError(response, $btn, originalText) {
        $btn.prop('disabled', false).html(originalText);
        this.showError(response.message || 'Enrollment failed');
    }

    /**
     * Check for pending payment
     */
    checkPendingPayment() {
        $.ajax({
            url: 'api/enrollment_api.php',
            method: 'GET',
            data: { action: 'pending_payment' },
            dataType: 'json',
            success: (response) => {
                if (response.success && response.data) {
                    this.showPendingPaymentNotification(response.data);
                }
            }
        });
    }

    /**
     * Show pending payment notification
     */
    showPendingPaymentNotification(pendingPayment) {
        const notification = `
            <div class="alert alert-warning alert-dismissible fade show pending-payment-alert" role="alert">
                <i class="fas fa-clock me-2"></i>
                <strong>Payment Pending:</strong> You have a pending payment for a course. 
                <a href="#" class="btn btn-sm btn-warning ms-2 complete-payment">Complete Payment</a>
                <a href="#" class="btn btn-sm btn-outline-secondary ms-1 cancel-payment">Cancel</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert notification at the top of the page
        $('.container').first().prepend(notification);
    }

    /**
     * Cancel payment
     */
    cancelPayment() {
        $.ajax({
            url: 'api/enrollment_api.php',
            method: 'POST',
            data: { action: 'cancel_payment' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Payment cancelled successfully');
                    $('.pending-payment-alert').fadeOut();
                } else {
                    this.showError(response.message || 'Failed to cancel payment');
                }
            },
            error: () => {
                this.showError('Network error. Please try again.');
            }
        });
    }

    /**
     * Show already enrolled message
     */
    showAlreadyEnrolled(course) {
        this.showInfo(`You are already enrolled in "${course.title}"`);
    }

    /**
     * Show login required message
     */
    showLoginRequired() {
        this.showInfo('Please login to enroll in courses');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
        }, 2000);
    }

    /**
     * Utility methods
     */
    isLoggedIn() {
        return typeof isLoggedIn !== 'undefined' ? isLoggedIn() : false;
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showInfo(message) {
        this.showAlert(message, 'info');
    }

    showAlert(message, type) {
        // Remove existing alerts
        $('.alert').remove();
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert at the top of the main content
        $('.container').first().prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
}

// Initialize enrollment system when DOM is ready
$(document).ready(() => {
    window.enrollmentSystem = new EnrollmentSystem();
});
