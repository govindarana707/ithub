-- Create payments table for eSewa and other payment methods
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    payment_method ENUM('esewa', 'khalti', 'paypal', 'stripe', 'trial') NOT NULL DEFAULT 'esewa',
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    response_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- Insert sample payment records for testing
INSERT INTO payments (student_id, course_id, amount, transaction_id, payment_method, status) VALUES
(1, 1, 1000.00, 'ESEWA_TEST_001', 'esewa', 'completed'),
(2, 2, 1500.00, 'ESEWA_TEST_002', 'esewa', 'pending'),
(3, 3, 2000.00, 'ESEWA_TEST_003', 'esewa', 'failed');
