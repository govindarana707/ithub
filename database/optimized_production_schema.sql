-- =====================================================
-- IT HUB LMS - Optimized Production Database Schema
-- =====================================================
-- Database: it_hub_new
-- Engine: MariaDB 10.4+
-- Character Set: utf8mb4_unicode_ci
-- Collation: utf8mb4_unicode_ci

-- Performance Settings
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456; -- 256MB
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL innodb_flush_method = O_DIRECT;
SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL query_cache_size = 134217728; -- 128MB
SET GLOBAL query_cache_type = 1;

-- =====================================================
-- Users Table (Optimized)
-- =====================================================
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('admin','instructor','student') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_email_verified` (`email_verified`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_last_login` (`last_login_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_role` CHECK (`role` IN ('admin','instructor','student')),
  CONSTRAINT `chk_status` CHECK (`status` IN ('active','inactive','suspended','pending'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Categories Table (Optimized)
-- =====================================================
CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `course_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_course_count` (`course_count`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Courses Table (Optimized)
-- =====================================================
CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `instructor_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
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
  `review_count` int(11) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `what_you_learn` json DEFAULT NULL,
  `target_audience` json DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
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
  KEY `idx_deleted_at` (`deleted_at`),
  FULLTEXT KEY `ft_search` (`title`, `description`, `short_description`),
  CONSTRAINT `fk_courses_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Lessons Table (Optimized)
-- =====================================================
CREATE TABLE `lessons` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('text','video','audio','document','quiz','assignment') NOT NULL DEFAULT 'text',
  `content_data` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_duration` int(11) DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `document_size` bigint(20) DEFAULT NULL,
  `lesson_order` int(11) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `estimated_minutes` int(11) DEFAULT NULL,
  `resources` json DEFAULT NULL,
  `transcript` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_course_lesson` (`course_id`, `slug`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_lesson_order` (`lesson_order`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_is_published` (`is_published`),
  KEY `idx_is_free` (`is_free`),
  KEY `idx_deleted_at` (`deleted_at`),
  FULLTEXT KEY `ft_search` (`title`, `description`),
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Course Sections Table
-- =====================================================
CREATE TABLE `course_sections` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `section_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_section_order` (`section_order`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_sections_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Enrollments Table (Optimized)
-- =====================================================
CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_type` enum('paid','free','trial','gift') NOT NULL DEFAULT 'paid',
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('active','completed','cancelled','expired','suspended','refunded') NOT NULL DEFAULT 'active',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `lessons_completed` int(11) NOT NULL DEFAULT 0,
  `total_lessons` int(11) NOT NULL DEFAULT 0,
  `time_spent_minutes` int(11) NOT NULL DEFAULT 0,
  `last_accessed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_student_course` (`student_id`, `course_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_enrollment_type` (`enrollment_type`),
  KEY `idx_progress` (`progress_percentage`),
  KEY `idx_last_accessed` (`last_accessed_at`),
  KEY `idx_enrolled_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Lesson Progress Table (New)
-- =====================================================
CREATE TABLE `lesson_progress` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `time_spent_seconds` int(11) NOT NULL DEFAULT 0,
  `last_position` int(11) DEFAULT NULL,
  `completion_data` json DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_enrollment_lesson` (`enrollment_id`, `lesson_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_lesson_progress_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lesson_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lesson_progress_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Quiz System Tables (Optimized)
-- =====================================================
CREATE TABLE `quizzes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 60.00,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `randomize_answers` tinyint(1) NOT NULL DEFAULT 0,
  `show_correct_answers` tinyint(1) NOT NULL DEFAULT 1,
  `allow_review` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quizzes_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quizzes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `quiz_questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` longtext NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay','fill_blank') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `explanation` longtext DEFAULT NULL,
  `question_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_question_order` (`question_order`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `quiz_options` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_text` varchar(1000) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `option_order` int(11) NOT NULL DEFAULT 0,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_option_order` (`option_order`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_quiz_options_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `quiz_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','completed','abandoned','expired') NOT NULL DEFAULT 'in_progress',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `time_taken_seconds` int(11) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `review_data` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_percentage` (`percentage`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_quiz_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quiz_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quiz_attempts_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Payments Table (Optimized)
-- =====================================================
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` enum('esewa','khalti','bank_transfer','card','paypal','stripe','free') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_transaction_id` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded','cancelled','expired') NOT NULL DEFAULT 'pending',
  `gateway_response` json DEFAULT NULL,
  `refunded_amount` decimal(10,2) DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_status` (`status`),
  KEY `idx_amount` (`amount`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Certificates Table (Optimized)
-- =====================================================
CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `course_title` varchar(255) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `completion_date` date NOT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `certificate_url` varchar(500) DEFAULT NULL,
  `qr_code_url` varchar(500) DEFAULT NULL,
  `verification_token` varchar(100) NOT NULL,
  `template_id` int(11) DEFAULT 1,
  `status` enum('issued','revoked','expired') NOT NULL DEFAULT 'issued',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `revoked_reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `last_downloaded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_certificate_number` (`certificate_number`),
  UNIQUE KEY `idx_verification_token` (`verification_token`),
  KEY `idx_enrollment_id` (`enrollment_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_issued_at` (`issued_at`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_certificates_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_certificates_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_certificates_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Discussions Table (Optimized)
-- =====================================================
CREATE TABLE `discussions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `instructor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `type` enum('question','discussion','announcement') NOT NULL DEFAULT 'question',
  `status` enum('open','closed','archived','deleted') NOT NULL DEFAULT 'open',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_answered` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `reply_count` int(11) NOT NULL DEFAULT 0,
  `upvote_count` int(11) NOT NULL DEFAULT 0,
  `last_reply_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_instructor_id` (`instructor_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_pinned` (`is_pinned`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_last_reply` (`last_reply_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  FULLTEXT KEY `ft_search` (`title`, `content`),
  CONSTRAINT `fk_discussions_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_discussions_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_discussions_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_discussions_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `discussion_replies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `discussion_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content` longtext NOT NULL,
  `is_instructor_reply` tinyint(1) NOT NULL DEFAULT 0,
  `is_best_answer` tinyint(1) NOT NULL DEFAULT 0,
  `upvote_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_discussion_id` (`discussion_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_best_answer` (`is_best_answer`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_discussion_replies_discussion` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_discussion_replies_parent` FOREIGN KEY (`parent_id`) REFERENCES `discussion_replies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_discussion_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- System Tables (Optimized)
-- =====================================================
CREATE TABLE `admin_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (UUID()),
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` enum('string','number','boolean','json','array') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_editable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid` (`uuid`),
  UNIQUE KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =====================================================
-- Performance Indexes (Strategic)
-- =====================================================

-- Composite indexes for common queries
CREATE INDEX `idx_courses_instructor_status` ON `courses` (`instructor_id`, `status`);
CREATE INDEX `idx_courses_category_status` ON `courses` (`category_id`, `status`);
CREATE INDEX `idx_courses_status_featured` ON `courses` (`status`, `featured`);
CREATE INDEX `idx_courses_published_featured` ON `courses` (`published_at`, `featured`);

CREATE INDEX `idx_enrollments_student_status` ON `enrollments` (`student_id`, `status`);
CREATE INDEX `idx_enrollments_course_status` ON `enrollments` (`course_id`, `status`);
CREATE INDEX `idx_enrollments_active_progress` ON `enrollments` (`status`, `progress_percentage`);

CREATE INDEX `idx_lessons_course_published` ON `lessons` (`course_id`, `is_published`);
CREATE INDEX `idx_lessons_section_order` ON `lessons` (`section_id`, `lesson_order`);

CREATE INDEX `idx_quiz_attempts_student_status` ON `quiz_attempts` (`student_id`, `status`);
CREATE INDEX `idx_quiz_attempts_quiz_completed` ON `quiz_attempts` (`quiz_id`, `completed_at`);

-- =====================================================
-- Database Views (Optimized)
-- =====================================================

CREATE VIEW `v_course_statistics` AS
SELECT 
    c.id,
    c.uuid,
    c.title,
    c.instructor_id,
    c.category_id,
    c.status,
    c.price,
    c.enrollment_count,
    c.rating,
    c.rating_count,
    c.created_at,
    COUNT(DISTINCT e.student_id) as unique_students,
    COALESCE(AVG(e.progress_percentage), 0) as avg_progress,
    COALESCE(SUM(p.amount), 0) as total_revenue,
    COUNT(DISTINCT l.id) as lesson_count,
    COUNT(DISTINCT qa.id) as quiz_attempts,
    COALESCE(AVG(qa.percentage), 0) as avg_quiz_score,
    u.full_name as instructor_name,
    cat.name as category_name
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active' AND e.deleted_at IS NULL
LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed' AND p.deleted_at IS NULL
LEFT JOIN lessons l ON c.id = l.course_id AND l.is_published = 1 AND l.deleted_at IS NULL
LEFT JOIN quiz_attempts qa ON e.id = qa.enrollment_id AND qa.status = 'completed'
LEFT JOIN users u ON c.instructor_id = u.id AND u.deleted_at IS NULL
LEFT JOIN categories cat ON c.category_id = cat.id AND cat.deleted_at IS NULL
WHERE c.deleted_at IS NULL
GROUP BY c.id, c.uuid, c.title, c.instructor_id, c.category_id, c.status, c.price, c.enrollment_count, c.rating, c.rating_count, c.created_at, u.full_name, cat.name;

CREATE VIEW `v_instructor_performance` AS
SELECT 
    u.id as instructor_id,
    u.uuid,
    u.full_name,
    u.email,
    u.created_at as instructor_since,
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT CASE WHEN c.status = 'published' THEN c.id END) as published_courses,
    COUNT(DISTINCT e.student_id) as total_students,
    COALESCE(SUM(p.amount), 0) as total_revenue,
    COALESCE(AVG(e.progress_percentage), 0) as avg_student_progress,
    COALESCE(AVG(c.rating), 0) as avg_course_rating,
    COUNT(DISTINCT d.id) as total_discussions,
    COUNT(DISTINCT CASE WHEN d.is_answered = 1 THEN d.id END) as answered_discussions
FROM users u
LEFT JOIN courses c ON u.id = c.instructor_id AND c.deleted_at IS NULL
LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active' AND e.deleted_at IS NULL
LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed' AND p.deleted_at IS NULL
LEFT JOIN discussions d ON c.id = d.course_id AND d.deleted_at IS NULL
WHERE u.role = 'instructor' AND u.deleted_at IS NULL
GROUP BY u.id, u.uuid, u.full_name, u.email, u.created_at;

CREATE VIEW `v_student_progress` AS
SELECT 
    u.id as student_id,
    u.uuid,
    u.full_name,
    u.email,
    COUNT(DISTINCT e.id) as enrolled_courses,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_courses,
    COALESCE(AVG(e.progress_percentage), 0) as avg_progress,
    COALESCE(SUM(e.time_spent_minutes), 0) as total_learning_time,
    COUNT(DISTINCT CASE WHEN lp.status = 'completed' THEN lp.lesson_id END) as completed_lessons,
    COUNT(DISTINCT cert.id) as certificates_earned,
    COALESCE(AVG(qa.percentage), 0) as avg_quiz_score,
    COUNT(DISTINCT qa.id) as quiz_attempts
FROM users u
LEFT JOIN enrollments e ON u.id = e.student_id AND e.deleted_at IS NULL
LEFT JOIN lesson_progress lp ON e.id = lp.enrollment_id AND lp.deleted_at IS NULL
LEFT JOIN certificates cert ON e.id = cert.enrollment_id AND cert.deleted_at IS NULL
LEFT JOIN quiz_attempts qa ON e.id = qa.enrollment_id AND qa.status = 'completed'
WHERE u.role = 'student' AND u.deleted_at IS NULL
GROUP BY u.id, u.uuid, u.full_name, u.email;

-- =====================================================
-- Triggers (Data Integrity)
-- =====================================================

DELIMITER //

-- Update course enrollment count
CREATE TRIGGER `tr_update_course_enrollment_count`
AFTER INSERT ON `enrollments`
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND NEW.deleted_at IS NULL THEN
        UPDATE courses 
        SET enrollment_count = enrollment_count + 1 
        WHERE id = NEW.course_id AND deleted_at IS NULL;
    END IF;
END//

CREATE TRIGGER `tr_decrease_course_enrollment_count`
AFTER UPDATE ON `enrollments`
FOR EACH ROW
BEGIN
    IF OLD.status = 'active' AND (NEW.status != 'active' OR NEW.deleted_at IS NOT NULL) THEN
        UPDATE courses 
        SET enrollment_count = GREATEST(0, enrollment_count - 1) 
        WHERE id = OLD.course_id AND deleted_at IS NULL;
    END IF;
END//

CREATE TRIGGER `tr_restore_course_enrollment_count`
AFTER UPDATE ON `enrollments`
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND OLD.status != 'active' AND NEW.deleted_at IS NULL THEN
        UPDATE courses 
        SET enrollment_count = enrollment_count + 1 
        WHERE id = NEW.course_id AND deleted_at IS NULL;
    END IF;
END//

-- Update course rating
CREATE TRIGGER `tr_update_course_rating`
AFTER INSERT ON `course_reviews`
FOR EACH ROW
BEGIN
    UPDATE courses c
    SET 
        rating = (
            SELECT AVG(rating) 
            FROM course_reviews 
            WHERE course_id = NEW.course_id AND deleted_at IS NULL
        ),
        rating_count = (
            SELECT COUNT(*) 
            FROM course_reviews 
            WHERE course_id = NEW.course_id AND deleted_at IS NULL
        )
    WHERE c.id = NEW.course_id AND c.deleted_at IS NULL;
END//

-- Update lesson progress in enrollment
CREATE TRIGGER `tr_update_enrollment_progress`
AFTER INSERT ON `lesson_progress`
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND NEW.deleted_at IS NULL THEN
        UPDATE enrollments e
        SET 
            lessons_completed = (
                SELECT COUNT(*) 
                FROM lesson_progress lp 
                WHERE lp.enrollment_id = NEW.enrollment_id 
                AND lp.status = 'completed' 
                AND lp.deleted_at IS NULL
            ),
            progress_percentage = (
                SELECT (COUNT(*) * 100.0 / (
                    SELECT COUNT(*) 
                    FROM lessons l
                    WHERE l.course_id = (SELECT course_id FROM enrollments WHERE id = NEW.enrollment_id)
                    AND l.is_published = 1 
                    AND l.deleted_at IS NULL
                ))
                FROM lesson_progress lp 
                WHERE lp.enrollment_id = NEW.enrollment_id 
                AND lp.status = 'completed' 
                AND lp.deleted_at IS NULL
            ),
            last_accessed_at = NEW.updated_at,
            time_spent_minutes = time_spent_minutes + FLOOR(NEW.time_spent_seconds / 60)
        WHERE e.id = NEW.enrollment_id AND e.deleted_at IS NULL;
    END IF;
END//

-- Auto-complete course when all lessons are done
CREATE TRIGGER `tr_auto_complete_course`
AFTER UPDATE ON `lesson_progress`
FOR EACH ROW
BEGIN
    DECLARE total_lessons INT DEFAULT 0;
    DECLARE completed_lessons INT DEFAULT 0;
    
    -- Get lesson counts
    SELECT COUNT(*) INTO total_lessons
    FROM lessons l
    WHERE l.course_id = (SELECT course_id FROM enrollments WHERE id = NEW.enrollment_id)
    AND l.is_published = 1 AND l.deleted_at IS NULL;
    
    SELECT COUNT(*) INTO completed_lessons
    FROM lesson_progress lp
    WHERE lp.enrollment_id = NEW.enrollment_id 
    AND lp.status = 'completed' 
    AND lp.deleted_at IS NULL;
    
    -- Auto-complete if all lessons are done
    IF total_lessons > 0 AND completed_lessons = total_lessons THEN
        UPDATE enrollments 
        SET 
            status = 'completed',
            progress_percentage = 100.00,
            completed_at = NOW()
        WHERE id = NEW.enrollment_id AND deleted_at IS NULL;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- Stored Procedures (Performance)
-- =====================================================

DELIMITER //

-- Get instructor dashboard data
CREATE PROCEDURE `sp_get_instructor_dashboard`(IN p_instructor_id BIGINT)
BEGIN
    SELECT 
        COUNT(*) as total_courses,
        COUNT(CASE WHEN status = 'published' THEN 1 END) as published_courses,
        COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_courses,
        COUNT(DISTINCT e.student_id) as total_students,
        COALESCE(SUM(p.amount), 0) as total_revenue,
        COALESCE(AVG(e.progress_percentage), 0) as avg_student_progress,
        COUNT(DISTINCT d.id) as total_discussions,
        COUNT(DISTINCT CASE WHEN d.is_answered = 0 THEN d.id END) as unanswered_discussions
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active' AND e.deleted_at IS NULL
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed' AND p.deleted_at IS NULL
    LEFT JOIN discussions d ON c.id = d.course_id AND d.deleted_at IS NULL
    WHERE c.instructor_id = p_instructor_id AND c.deleted_at IS NULL;
END//

-- Get course analytics
CREATE PROCEDURE `sp_get_course_analytics`(IN p_course_id BIGINT)
BEGIN
    SELECT 
        c.*,
        COUNT(DISTINCT e.student_id) as enrollment_count,
        COALESCE(AVG(e.progress_percentage), 0) as avg_progress,
        COALESCE(SUM(p.amount), 0) as total_revenue,
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT CASE WHEN l.is_published = 1 THEN l.id END) as published_lessons,
        COUNT(DISTINCT qa.id) as quiz_attempts,
        COALESCE(AVG(qa.percentage), 0) as avg_quiz_score,
        COUNT(DISTINCT d.id) as discussions,
        COUNT(DISTINCT cert.id) as certificates_issued
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active' AND e.deleted_at IS NULL
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed' AND p.deleted_at IS NULL
    LEFT JOIN lessons l ON c.id = l.course_id AND l.deleted_at IS NULL
    LEFT JOIN quiz_attempts qa ON e.id = qa.enrollment_id AND qa.status = 'completed'
    LEFT JOIN discussions d ON c.id = d.course_id AND d.deleted_at IS NULL
    LEFT JOIN certificates cert ON e.id = cert.enrollment_id AND cert.deleted_at IS NULL
    WHERE c.id = p_course_id AND c.deleted_at IS NULL
    GROUP BY c.id;
END//

-- Get student progress overview
CREATE PROCEDURE `sp_get_student_progress`(IN p_student_id BIGINT)
BEGIN
    SELECT 
        u.full_name,
        u.email,
        COUNT(DISTINCT e.id) as enrolled_courses,
        COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_courses,
        COALESCE(AVG(e.progress_percentage), 0) as avg_progress,
        COALESCE(SUM(e.time_spent_minutes), 0) as total_learning_time,
        COUNT(DISTINCT lp.lesson_id) as lessons_completed,
        COUNT(DISTINCT cert.id) as certificates_earned,
        COALESCE(AVG(qa.percentage), 0) as avg_quiz_score
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id AND e.deleted_at IS NULL
    LEFT JOIN lesson_progress lp ON e.id = lp.enrollment_id AND lp.status = 'completed' AND lp.deleted_at IS NULL
    LEFT JOIN certificates cert ON e.id = cert.enrollment_id AND cert.deleted_at IS NULL
    LEFT JOIN quiz_attempts qa ON e.id = qa.enrollment_id AND qa.status = 'completed'
    WHERE u.id = p_student_id AND u.deleted_at IS NULL
    GROUP BY u.id, u.full_name, u.email;
END//

DELIMITER ;

-- =====================================================
-- Sample Data Insert
-- =====================================================

INSERT INTO `system_settings` (`key`, `value`, `type`, `description`, `is_public`) VALUES
('site_name', 'IT HUB Learning Management System', 'string', 'Site name', 1),
('site_description', 'Professional IT Training Platform', 'string', 'Site description', 1),
('max_file_size', '52428800', 'number', 'Maximum file upload size in bytes', 0),
('allowed_file_types', '["pdf","doc","docx","ppt","pptx","jpg","jpeg","png","gif","mp4","avi","mov","webm"]', 'json', 'Allowed file types for upload', 0),
('default_currency', 'USD', 'string', 'Default currency for payments', 1),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status', 0),
('session_timeout', '1800', 'number', 'Session timeout in seconds', 0),
('password_min_length', '8', 'number', 'Minimum password length', 0),
('email_verification_required', 'true', 'boolean', 'Require email verification', 0),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', 0),
('lockout_duration', '900', 'number', 'Account lockout duration in seconds', 0),
('enable_notifications', 'true', 'boolean', 'Enable email notifications', 0),
('enable_discussions', 'true', 'boolean', 'Enable course discussions', 1),
('enable_certificates', 'true', 'boolean', 'Enable course certificates', 1),
('certificate_template', 'default', 'string', 'Default certificate template', 0);

-- =====================================================
-- Performance Optimization Complete
-- =====================================================

-- Show optimization summary
SELECT 
    'Database Optimization Complete' as status,
    COUNT(*) as total_tables,
    SUM(CASE WHEN table_name LIKE 'v_%' THEN 1 ELSE 0 END) as views,
    SUM(CASE WHEN table_name LIKE 'sp_%' THEN 1 ELSE 0 END) as procedures,
    SUM(CASE WHEN table_name LIKE 'tr_%' THEN 1 ELSE 0 END) as triggers
FROM information_schema.tables 
WHERE table_schema = DATABASE();
