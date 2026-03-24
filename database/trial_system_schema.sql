-- Free Trial System Database Schema
-- Complete schema for comprehensive trial management

-- Trial settings configuration
INSERT INTO payment_settings (setting_key, setting_value, setting_type, description) VALUES
('trial_duration_days', '30', 'integer', 'Default trial duration in days'),
('trial_reminder_days', '[7, 3, 1]', 'json', 'Days before expiration to send reminders'),
('enable_trial_notifications', 'true', 'boolean', 'Enable trial expiration notifications'),
('max_active_trials_per_user', '3', 'integer', 'Maximum active trials per user'),
('trial_conversion_discount', '20', 'integer', 'Discount percentage for trial conversion (optional)');

-- Notifications table (if not exists)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    related_id INT NULL,
    related_type VARCHAR(50) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trial activity logs (enhanced admin_logs)
ALTER TABLE admin_logs 
ADD COLUMN IF NOT EXISTS log_type ENUM('general', 'trial', 'payment', 'enrollment') DEFAULT 'general',
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL,
ADD COLUMN IF NOT EXISTS user_agent TEXT NULL;

-- Create indexes for better performance
ALTER TABLE enrollments_new ADD INDEX IF NOT EXISTS idx_trial_expiration (enrollment_type, expires_at, status);
ALTER TABLE enrollments_new ADD INDEX IF NOT EXISTS idx_user_trials (user_id, enrollment_type, status);

-- Trial conversion tracking view
CREATE OR REPLACE VIEW trial_conversion_analytics AS
SELECT 
    DATE(e.enrolled_at) as trial_date,
    c.title as course_title,
    c.category_id,
    e.user_id,
    e.enrolled_at as trial_start,
    e.expires_at as trial_end,
    CASE 
        WHEN e.status = 'active' AND e.expires_at > NOW() THEN 'active_trial'
        WHEN e.status = 'active' AND e.expires_at <= NOW() THEN 'expired_trial'
        WHEN e.status = 'completed' THEN 'completed_trial'
        WHEN e.status = 'cancelled' THEN 'cancelled_trial'
        ELSE 'unknown'
    END as trial_status,
    e.progress_percentage as trial_progress,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
        ) THEN 1 ELSE 0
    END as converted_to_paid,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
        ) THEN (
            SELECT DATEDIFF(e2.enrolled_at, e.enrolled_at)
            FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
            LIMIT 1
        )
        ELSE NULL
    END as conversion_days
FROM enrollments_new e
JOIN courses_new c ON e.course_id = c.id
WHERE e.enrollment_type = 'free_trial'
ORDER BY e.enrolled_at DESC;

-- Trial performance summary view
CREATE OR REPLACE VIEW trial_performance_summary AS
SELECT 
    c.title as course_title,
    c.category_id,
    cat.name as category_name,
    COUNT(e.id) as total_trials,
    COUNT(CASE WHEN e.status = 'active' AND e.expires_at > NOW() THEN 1 END) as active_trials,
    COUNT(CASE WHEN e.status = 'active' AND e.expires_at <= NOW() THEN 1 END) as expired_trials,
    COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_trials,
    COUNT(CASE WHEN EXISTS (
        SELECT 1 FROM enrollments_new e2 
        WHERE e2.user_id = e.user_id 
        AND e2.course_id = e.course_id 
        AND e2.enrollment_type = 'paid'
        AND e2.enrolled_at > e.enrolled_at
    ) THEN 1 END) as converted_trials,
    ROUND(
        COUNT(CASE WHEN EXISTS (
            SELECT 1 FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
        ) THEN 1 END) * 100.0 / COUNT(e.id), 2
    ) as conversion_rate,
    AVG(e.progress_percentage) as avg_progress,
    AVG(DATEDIFF(e.expires_at, e.enrolled_at)) as avg_trial_duration_days
FROM enrollments_new e
JOIN courses_new c ON e.course_id = c.id
LEFT JOIN categories cat ON c.category_id = cat.id
WHERE e.enrollment_type = 'free_trial'
GROUP BY e.course_id, c.title, c.category_id, cat.name
ORDER BY conversion_rate DESC, total_trials DESC;

-- User trial behavior view
CREATE OR REPLACE VIEW user_trial_behavior AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.email,
    COUNT(e.id) as total_trials_started,
    COUNT(CASE WHEN e.status = 'active' AND e.expires_at > NOW() THEN 1 END) as active_trials,
    COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_trials,
    COUNT(CASE WHEN EXISTS (
        SELECT 1 FROM enrollments_new e2 
        WHERE e2.user_id = e.user_id 
        AND e2.course_id = e.course_id 
        AND e2.enrollment_type = 'paid'
        AND e2.enrolled_at > e.enrolled_at
    ) THEN 1 END) as converted_trials,
    ROUND(
        COUNT(CASE WHEN EXISTS (
            SELECT 1 FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
        ) THEN 1 END) * 100.0 / NULLIF(COUNT(e.id), 0), 2
    ) as user_conversion_rate,
    AVG(e.progress_percentage) as avg_trial_progress,
    MIN(e.enrolled_at) as first_trial_date,
    MAX(e.enrolled_at) as last_trial_date,
    DATEDIFF(NOW(), MIN(e.enrolled_at)) as days_since_first_trial
FROM users_new u
LEFT JOIN enrollments_new e ON u.id = e.user_id AND e.enrollment_type = 'free_trial'
GROUP BY u.id, u.full_name, u.email
HAVING total_trials_started > 0
ORDER BY total_trials_started DESC, user_conversion_rate DESC;

-- Stored procedure for trial cleanup and analytics
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS AnalyzeTrialPerformance(IN date_from DATE, IN date_to DATE)
BEGIN
    -- Get trial statistics for the period
    SELECT 
        'Total Trials Started' as metric,
        COUNT(*) as value,
        COUNT(*) as total
    FROM enrollments_new 
    WHERE enrollment_type = 'free_trial' 
    AND DATE(enrolled_at) BETWEEN date_from AND date_to
    
    UNION ALL
    
    SELECT 
        'Trials Converted' as metric,
        COUNT(CASE WHEN EXISTS (
            SELECT 1 FROM enrollments_new e2 
            WHERE e2.user_id = e.user_id 
            AND e2.course_id = e.course_id 
            AND e2.enrollment_type = 'paid'
            AND e2.enrolled_at > e.enrolled_at
            AND DATE(e2.enrolled_at) BETWEEN date_from AND date_to
        ) THEN 1 END) as value,
        COUNT(*) as total
    FROM enrollments_new 
    WHERE enrollment_type = 'free_trial' 
    AND DATE(enrolled_at) BETWEEN date_from AND date_to
    
    UNION ALL
    
    SELECT 
        'Average Trial Progress' as metric,
        ROUND(AVG(progress_percentage), 2) as value,
        COUNT(*) as total
    FROM enrollments_new 
    WHERE enrollment_type = 'free_trial' 
    AND DATE(enrolled_at) BETWEEN date_from AND date_to;
END //
DELIMITER ;

-- Function to check if user can start new trial
DELIMITER //
CREATE FUNCTION IF NOT EXISTS CanUserStartTrial(user_id_param INT, course_id_param INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE active_trials_count INT;
    DECLARE has_active_trial BOOLEAN;
    
    -- Count active trials
    SELECT COUNT(*) INTO active_trials_count
    FROM enrollments_new 
    WHERE user_id = user_id_param 
    AND enrollment_type = 'free_trial' 
    AND status = 'active' 
    AND expires_at > NOW();
    
    -- Check if has active trial for this course
    SELECT COUNT(*) > 0 INTO has_active_trial
    FROM enrollments_new 
    WHERE user_id = user_id_param 
    AND course_id = course_id_param 
    AND enrollment_type = 'free_trial' 
    AND status = 'active' 
    AND expires_at > NOW()
    LIMIT 1;
    
    -- Return true if less than 3 active trials and no active trial for this course
    RETURN (active_trials_count < 3) AND (has_active_trial = FALSE);
END //
DELIMITER ;

-- Trigger to log trial activity automatically
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_trial_enrollment
AFTER INSERT ON enrollments_new
FOR EACH ROW
BEGIN
    IF NEW.enrollment_type = 'free_trial' THEN
        INSERT INTO admin_logs (user_id, action, details, log_type, ip_address, user_agent, created_at)
        VALUES (
            NEW.user_id, 
            'trial_enrolled', 
            JSON_OBJECT(
                'course_id', NEW.course_id,
                'enrollment_id', NEW.id,
                'expires_at', NEW.expires_at,
                'trial_duration_days', DATEDIFF(NEW.expires_at, NEW.enrolled_at)
            ),
            'trial',
            NULL,
            NULL,
            NOW()
        );
    END IF;
END //
DELIMITER ;

-- Trigger to log trial conversions
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_trial_conversion
AFTER UPDATE ON enrollments_new
FOR EACH ROW
BEGIN
    IF OLD.enrollment_type = 'free_trial' AND NEW.enrollment_type = 'paid' THEN
        INSERT INTO admin_logs (user_id, action, details, log_type, ip_address, user_agent, created_at)
        VALUES (
            NEW.user_id, 
            'trial_converted', 
            JSON_OBJECT(
                'course_id', NEW.course_id,
                'old_enrollment_id', OLD.id,
                'new_enrollment_id', NEW.id,
                'conversion_days', DATEDIFF(NEW.enrolled_at, OLD.enrolled_at),
                'trial_progress', OLD.progress_percentage
            ),
            'trial',
            NULL,
            NULL,
            NOW()
        );
    END IF;
END //
DELIMITER ;
