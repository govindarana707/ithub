-- eSewa Payment Integration Database Schema
-- Created for secure course enrollment payment processing

-- Payments table to track all payment transactions
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_uuid VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    payment_method ENUM('esewa', 'khalti', 'free', 'other') NOT NULL DEFAULT 'esewa',
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NPR',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
    gateway_status VARCHAR(50) NULL,
    gateway_transaction_id VARCHAR(100) NULL,
    gateway_response TEXT NULL,
    signature VARCHAR(255) NULL,
    signed_field_names VARCHAR(255) NULL,
    product_code VARCHAR(50) DEFAULT 'EPAYTEST',
    failure_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_transaction_uuid (transaction_uuid),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),
    INDEX idx_gateway_transaction_id (gateway_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment verification logs for audit trail
CREATE TABLE IF NOT EXISTS payment_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    verification_type ENUM('signature', 'status_check', 'amount_validation', 'product_code_validation') NOT NULL,
    status ENUM('success', 'failed', 'error') NOT NULL,
    request_data TEXT NULL,
    response_data TEXT NULL,
    error_message TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_verification_type (verification_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced enrollments table (if not exists, modify existing)
CREATE TABLE IF NOT EXISTS enrollments_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    payment_id INT NULL,
    enrollment_type ENUM('paid', 'free_trial', 'complimentary') NOT NULL DEFAULT 'paid',
    status ENUM('active', 'completed', 'suspended', 'cancelled') NOT NULL DEFAULT 'active',
    progress_percentage DECIMAL(5, 2) DEFAULT 0.00,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    
    -- Unique constraint to prevent duplicate enrollments
    UNIQUE KEY unique_user_course (user_id, course_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    INDEX idx_enrolled_at (enrolled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment settings configuration table
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'boolean', 'integer', 'json') NOT NULL DEFAULT 'string',
    description TEXT NULL,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default eSewa settings
INSERT INTO payment_settings (setting_key, setting_value, setting_type, description) VALUES
('esewa_secret_key', '8gBm/:&EnhH.1/q(', 'string', 'eSewa secret key for HMAC signature'),
('esewa_product_code', 'EPAYTEST', 'string', 'eSewa product code for testing'),
('esewa_merchant_id', '', 'string', 'eSewa merchant ID'),
('esewa_test_mode', 'true', 'boolean', 'Enable eSewa test mode'),
('esewa_success_url', 'payments/esewa_success.php', 'string', 'eSewa success callback URL'),
('esewa_failure_url', 'payments/esewa_failure.php', 'string', 'eSewa failure callback URL'),
('payment_timeout_minutes', '30', 'integer', 'Payment session timeout in minutes'),
('enable_payment_logging', 'true', 'boolean', 'Enable detailed payment logging'),
('max_payment_attempts', '3', 'integer', 'Maximum payment attempts per transaction');

-- Create view for payment analytics
CREATE OR REPLACE VIEW payment_analytics AS
SELECT 
    DATE(p.created_at) as payment_date,
    p.payment_method,
    p.status,
    COUNT(*) as transaction_count,
    SUM(p.amount) as total_amount,
    AVG(p.amount) as average_amount,
    COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as successful_payments,
    COUNT(CASE WHEN p.status = 'failed' THEN 1 END) as failed_payments,
    ROUND(COUNT(CASE WHEN p.status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
FROM payments p
GROUP BY DATE(p.created_at), p.payment_method, p.status;

-- Create stored procedure for payment cleanup (optional)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredPayments()
BEGIN
    -- Mark payments as failed if they are pending for more than 30 minutes
    UPDATE payments 
    SET status = 'failed', 
        failure_reason = 'Payment expired',
        updated_at = CURRENT_TIMESTAMP
    WHERE status = 'pending' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE);
    
    -- Log the cleanup
    INSERT INTO payment_verification_logs (payment_id, verification_type, status, request_data, response_data)
    SELECT p.id, 'status_check', 'success', 'Scheduled cleanup', 'Payment expired and marked as failed'
    FROM payments p
    WHERE p.status = 'failed' 
    AND p.failure_reason = 'Payment expired'
    AND p.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Create trigger for payment status changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_payment_status_update
BEFORE UPDATE ON payments
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        -- Log status change
        INSERT INTO payment_verification_logs (payment_id, verification_type, status, request_data, response_data)
        VALUES (NEW.id, 'status_change', 'success', 
                CONCAT('Status changed from ', OLD.status, ' to ', NEW.status),
                CONCAT('Payment ', NEW.id, ' status updated'));
    END IF;
END //
DELIMITER ;

-- Add indexes for better performance
ALTER TABLE payments ADD INDEX idx_user_course_status (user_id, course_id, status);
ALTER TABLE payments ADD INDEX idx_payment_method_status (payment_method, status);
ALTER TABLE payment_verification_logs ADD INDEX idx_payment_created (payment_id, created_at);

-- Create function to generate unique transaction UUID
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_transaction_uuid() 
RETURNS VARCHAR(100)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE uuid VARCHAR(100);
    SET uuid = CONCAT(UUID(), '-', UNIX_TIMESTAMP());
    RETURN uuid;
END //
DELIMITER ;
