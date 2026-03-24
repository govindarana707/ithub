-- =====================================================
-- Optimized Database Schema for IT HUB LMS
-- =====================================================

-- Enable performance optimizations
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL query_cache_size = 268435456; -- 256MB
SET GLOBAL innodb_log_file_size = 268435456; -- 256MB

-- =====================================================
-- Users Table (Enhanced)
-- =====================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('admin','instructor','student') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_email_verified` (`email_verified`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Categories Table (Enhanced)
-- =====================================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Courses Table (Optimized)
-- =====================================================
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `original_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `duration_hours` int(11) NOT NULL DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `thumbnail` varchar(255) DEFAULT NULL,
  `preview_video` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived','under_review') NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `enrollment_limit` int(11) DEFAULT NULL,
  `enrollment_count` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_instructor_id` (`instructor_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_difficulty_level` (`difficulty_level`),
  KEY `idx_price` (`price`),
  KEY `idx_rating` (`rating`),
  KEY `idx_enrollment_count` (`enrollment_count`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `ft_search` (`title`, `description`, `short_description`),
  CONSTRAINT `fk_courses_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Lessons Table (Enhanced)
-- =====================================================
CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('text','video','audio','document','quiz','assignment') NOT NULL DEFAULT 'text',
  `content_data` longtext DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_duration` int(11) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `lesson_order` int(11) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `estimated_minutes` int(11) DEFAULT NULL,
  `resources` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_course_lesson` (`course_id`, `slug`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_order` (`lesson_order`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_is_published` (`is_published`),
  KEY `idx_is_free` (`is_free`),
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Enrollments Table (Optimized)
-- =====================================================
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_type` enum('paid','free','trial') NOT NULL DEFAULT 'paid',
  `payment_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('active','completed','cancelled','expired','suspended') NOT NULL DEFAULT 'active',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `lessons_completed` int(11) NOT NULL DEFAULT 0,
  `total_lessons` int(11) NOT NULL DEFAULT 0,
  `last_accessed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_id` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_student_course` (`student_id`, `course_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_enrollment_type` (`enrollment_type`),
  KEY `idx_progress` (`progress_percentage`),
  KEY `idx_last_accessed` (`last_accessed_at`),
  KEY `idx_enrolled_at` (`enrolled_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Lesson Progress Table (New)
-- =====================================================
CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `time_spent_minutes` int(11) NOT NULL DEFAULT 0,
  `last_position` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_enrollment_lesson` (`enrollment_id`, `lesson_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_lesson_progress_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_progress_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Quiz System Tables (Enhanced)
-- =====================================================
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 60.00,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `randomize_answers` tinyint(1) NOT NULL DEFAULT 0,
  `show_correct_answers` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_quizzes_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quizzes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `explanation` text DEFAULT NULL,
  `question_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_question_order` (`question_order`),
  CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `option_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_option_order` (`option_order`),
  CONSTRAINT `fk_quiz_options_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','completed','abandoned','expired') NOT NULL DEFAULT 'in_progress',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `time_taken_minutes` int(11) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_percentage` (`percentage`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_quiz_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_attempts_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Payments Table (Enhanced)
-- =====================================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `payment_method` enum('esewa','khalti','bank_transfer','card','paypal') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `gateway_response` json DEFAULT NULL,
  `refunded_amount` decimal(10,2) DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_status` (`status`),
  KEY `idx_amount` (`amount`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Certificates Table (Enhanced)
-- =====================================================
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `course_title` varchar(255) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `completion_date` date NOT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `qr_code_url` varchar(255) DEFAULT NULL,
  `verification_token` varchar(100) NOT NULL,
  `status` enum('issued','revoked','expired') NOT NULL DEFAULT 'issued',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `revoked_reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_certificate_number` (`certificate_number`),
  UNIQUE KEY `idx_verification_token` (`verification_token`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_issued_at` (`issued_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_certificates_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificates_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificates_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- System Tables (Enhanced)
-- =====================================================
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Performance Indexes
-- =====================================================

-- Composite indexes for common queries
CREATE INDEX `idx_courses_instructor_status` ON `courses` (`instructor_id`, `status`);
CREATE INDEX `idx_courses_category_status` ON `courses` (`category_id`, `status`);
CREATE INDEX `idx_courses_status_featured` ON `courses` (`status`, `featured`);
CREATE INDEX `idx_enrollments_student_status` ON `enrollments` (`student_id`, `status`);
CREATE INDEX `idx_enrollments_course_status` ON `enrollments` (`course_id`, `status`);
CREATE INDEX `idx_lesson_progress_student_status` ON `lesson_progress` (`student_id`, `status`);

-- Full-text search indexes
CREATE FULLTEXT INDEX `ft_courses_search` ON `courses` (`title`, `description`, `short_description`);
CREATE FULLTEXT INDEX `ft_lessons_search` ON `lessons` (`title`, `description`);

-- =====================================================
-- Views for Common Queries
-- =====================================================

CREATE VIEW `course_statistics` AS
SELECT 
    c.id,
    c.title,
    c.instructor_id,
    COUNT(e.id) as enrollment_count,
    AVG(e.progress_percentage) as avg_progress,
    COUNT(DISTINCT e.student_id) as unique_students,
    COALESCE(SUM(p.amount), 0) as total_revenue,
    AVG(qa.percentage) as avg_quiz_score
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
LEFT JOIN quiz_attempts qa ON e.id = qa.enrollment_id AND qa.status = 'completed'
GROUP BY c.id, c.title, c.instructor_id;

CREATE VIEW `instructor_performance` AS
SELECT 
    u.id as instructor_id,
    u.full_name,
    COUNT(c.id) as total_courses,
    COUNT(CASE WHEN c.status = 'published' THEN 1 END) as published_courses,
    COUNT(DISTINCT e.student_id) as total_students,
    COALESCE(SUM(p.amount), 0) as total_revenue,
    AVG(e.progress_percentage) as avg_student_progress
FROM users u
LEFT JOIN courses c ON u.id = c.instructor_id
LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
WHERE u.role = 'instructor'
GROUP BY u.id, u.full_name;

-- =====================================================
-- Triggers for Data Integrity
-- =====================================================

DELIMITER //

-- Update course enrollment count
CREATE TRIGGER `update_course_enrollment_count`
AFTER INSERT ON `enrollments`
FOR EACH ROW
BEGIN
    UPDATE courses 
    SET enrollment_count = enrollment_count + 1 
    WHERE id = NEW.course_id;
END//

CREATE TRIGGER `decrease_course_enrollment_count`
AFTER DELETE ON `enrollments`
FOR EACH ROW
BEGIN
    UPDATE courses 
    SET enrollment_count = GREATEST(0, enrollment_count - 1) 
    WHERE id = OLD.course_id;
END//

-- Update enrollment progress
CREATE TRIGGER `update_enrollment_progress`
AFTER INSERT ON `lesson_progress`
FOR EACH ROW
BEGIN
    UPDATE enrollments e
    SET 
        lessons_completed = (
            SELECT COUNT(*) 
            FROM lesson_progress lp 
            WHERE lp.enrollment_id = e.id AND lp.status = 'completed'
        ),
        progress_percentage = (
            SELECT (COUNT(*) * 100.0 / (
                SELECT COUNT(*) 
                FROM lessons 
                WHERE course_id = e.course_id AND is_published = 1
            ))
            FROM lesson_progress lp 
            WHERE lp.enrollment_id = e.id AND lp.status = 'completed'
        ),
        last_accessed_at = NEW.updated_at
    WHERE e.id = NEW.enrollment_id;
END//

DELIMITER ;

-- =====================================================
-- Stored Procedures for Performance
-- =====================================================

DELIMITER //

-- Get instructor dashboard data
CREATE PROCEDURE `GetInstructorDashboard`(IN instructor_id INT)
BEGIN
    SELECT 
        COUNT(*) as total_courses,
        COUNT(CASE WHEN status = 'published' THEN 1 END) as published_courses,
        COUNT(DISTINCT e.student_id) as total_students,
        COALESCE(SUM(p.amount), 0) as total_revenue,
        AVG(e.progress_percentage) as avg_progress
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
    WHERE c.instructor_id = instructor_id;
END//

-- Get course analytics
CREATE PROCEDURE `GetCourseAnalytics`(IN course_id INT)
BEGIN
    SELECT 
        c.*,
        COUNT(e.id) as enrollment_count,
        AVG(e.progress_percentage) as avg_progress,
        COUNT(DISTINCT e.student_id) as unique_students,
        COALESCE(SUM(p.amount), 0) as total_revenue,
        (SELECT COUNT(*) FROM lessons WHERE course_id = course_id AND is_published = 1) as total_lessons
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
    WHERE c.id = course_id
    GROUP BY c.id;
END//

DELIMITER ;

-- =====================================================
-- Sample Data Insert
-- =====================================================

INSERT INTO `system_settings` (`key`, `value`, `type`, `description`, `is_public`) VALUES
('site_name', 'IT HUB Learning Management System', 'string', 'Site name', 1),
('site_description', 'Professional IT Training Platform', 'string', 'Site description', 1),
('max_file_size', '52428800', 'number', 'Maximum file upload size in bytes', 0),
('allowed_file_types', '["pdf","doc","docx","ppt","pptx","jpg","jpeg","png","gif","mp4","avi","mov"]', 'json', 'Allowed file types for upload', 0),
('default_currency', 'USD', 'string', 'Default currency for payments', 1),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status', 0),
('session_timeout', '1800', 'number', 'Session timeout in seconds', 0),
('password_min_length', '8', 'number', 'Minimum password length', 0),
('email_verification_required', 'true', 'boolean', 'Require email verification', 0);

-- =====================================================
-- Performance Optimization Complete
-- =====================================================
