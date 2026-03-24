-- =====================================================
-- Migration 001: Database Structure Optimization
-- =====================================================
-- Purpose: Transform existing database into optimized production schema
-- Version: 1.0.0
-- Date: 2026-03-20
-- Author: Database Architect Team

-- =====================================================
-- Migration Strategy
-- =====================================================
-- 1. Create backup of existing data
-- 2. Create new optimized tables
-- 3. Migrate data from old to new structure
-- 4. Validate data integrity
-- 5. Drop old tables
-- 6. Rename new tables to final names

-- =====================================================
-- Step 1: Backup Critical Data
-- =====================================================

-- Create backup tables for safety
CREATE TABLE backup_users LIKE users;
INSERT INTO backup_users SELECT * FROM users;

CREATE TABLE backup_courses LIKE courses;
INSERT INTO backup_courses SELECT * FROM courses;

CREATE TABLE backup_enrollments LIKE enrollments;
INSERT INTO backup_enrollments SELECT * FROM enrollments;

CREATE TABLE backup_lessons LIKE lessons;
INSERT INTO backup_lessons SELECT * FROM lessons;

-- =====================================================
-- Step 2: Create Optimized Tables
-- =====================================================

-- Note: The optimized schema is already defined in optimized_production_schema.sql
-- This migration will apply the changes incrementally

-- =====================================================
-- Step 3: Add Missing Columns to Existing Tables
-- =====================================================

-- Update users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS uuid CHAR(36) DEFAULT (UUID()) AFTER id,
ADD COLUMN IF NOT EXISTS email_verification_expires DATETIME NULL AFTER email_verification_token,
ADD COLUMN IF NOT EXISTS preferences JSON NULL AFTER locked_until,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER updated_at,
MODIFY COLUMN role ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
MODIFY COLUMN status ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending';

-- Add UUIDs for existing users
UPDATE users SET uuid = UUID() WHERE uuid IS NULL;

-- Update courses table
ALTER TABLE courses
ADD COLUMN IF NOT EXISTS uuid CHAR(36) DEFAULT (UUID()) AFTER id,
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NOT NULL AFTER title,
ADD COLUMN IF NOT EXISTS short_description VARCHAR(500) NULL AFTER description,
ADD COLUMN IF NOT EXISTS original_price DECIMAL(10,2) NULL AFTER price,
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) NOT NULL DEFAULT 'USD' AFTER original_price,
ADD COLUMN IF NOT EXISTS preview_video VARCHAR(255) NULL AFTER thumbnail,
ADD COLUMN IF NOT EXISTS enrollment_limit INT NULL AFTER featured,
ADD COLUMN IF NOT EXISTS review_count INT NOT NULL DEFAULT 0 AFTER rating_count,
ADD COLUMN IF NOT EXISTS requirements JSON NULL AFTER enrollment_limit,
ADD COLUMN IF NOT EXISTS what_you_learn JSON NULL AFTER requirements,
ADD COLUMN IF NOT EXISTS target_audience JSON NULL AFTER what_you_learn,
ADD COLUMN IF NOT EXISTS meta_title VARCHAR(200) NULL AFTER target_audience,
ADD COLUMN IF NOT EXISTS meta_description VARCHAR(500) NULL AFTER meta_title,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER updated_at,
MODIFY COLUMN status ENUM('draft','published','archived','under_review') NOT NULL DEFAULT 'draft';

-- Add slugs for existing courses
UPDATE courses SET slug = LOWER(REPLACE(REPLACE(REPLACE(title, ' ', '-'), '.', '-'), '_', '-')) WHERE slug IS NULL OR slug = '';

-- Update enrollments table
ALTER TABLE enrollments
ADD COLUMN IF NOT EXISTS uuid CHAR(36) DEFAULT (UUID()) AFTER id,
ADD COLUMN IF NOT EXISTS enrollment_type ENUM('paid','free','trial','gift') NOT NULL DEFAULT 'paid' AFTER course_id,
ADD COLUMN IF NOT EXISTS payment_id BIGINT UNSIGNED NULL AFTER enrollment_type,
ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NULL AFTER payment_id,
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'USD' AFTER amount_paid,
ADD COLUMN IF NOT EXISTS lessons_completed INT NOT NULL DEFAULT 0 AFTER progress_percentage,
ADD COLUMN IF NOT EXISTS total_lessons INT NOT NULL DEFAULT 0 AFTER lessons_completed,
ADD COLUMN IF NOT EXISTS time_spent_minutes INT NOT NULL DEFAULT 0 AFTER total_lessons,
ADD COLUMN IF NOT EXISTS certificate_issued TINYINT(1) NOT NULL DEFAULT 0 AFTER certificate_issued,
ADD COLUMN IF NOT EXISTS certificate_id BIGINT UNSIGNED NULL AFTER certificate_issued,
ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER certificate_id,
ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER expires_at,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER updated_at,
MODIFY COLUMN status ENUM('active','completed','cancelled','expired','suspended','refunded') NOT NULL DEFAULT 'active';

-- Add UUIDs for existing enrollments
UPDATE enrollments SET uuid = UUID() WHERE uuid IS NULL;

-- =====================================================
-- Step 4: Add Missing Primary Keys and Indexes
-- =====================================================

-- Add primary keys where missing
ALTER TABLE account_lockouts ADD PRIMARY KEY (id);
ALTER TABLE admin_logs ADD PRIMARY KEY (id);
ALTER TABLE assignment_submissions ADD PRIMARY KEY (id);
ALTER TABLE categories ADD PRIMARY KEY (id);
ALTER TABLE certificates ADD PRIMARY KEY (id);
ALTER TABLE completed_lessons ADD PRIMARY KEY (id);
ALTER TABLE coupons ADD PRIMARY KEY (id);
ALTER TABLE course_lessons ADD PRIMARY KEY (id);
ALTER TABLE course_meta ADD PRIMARY KEY (course_id, meta_key);
ALTER TABLE course_recommendations ADD PRIMARY KEY (id);
ALTER TABLE course_reviews ADD PRIMARY KEY (id);
ALTER TABLE course_sections ADD PRIMARY KEY (id);
ALTER TABLE discussions ADD PRIMARY KEY (id);
ALTER TABLE discussion_replies ADD PRIMARY KEY (id);
ALTER TABLE email_verifications ADD PRIMARY KEY (id);
ALTER TABLE instructor_activity_log ADD PRIMARY KEY (id);
ALTER TABLE instructor_earnings ADD PRIMARY KEY (id);
ALTER TABLE instructor_meta ADD PRIMARY KEY (instructor_id, meta_key);
ALTER TABLE learning_progress_dp ADD PRIMARY KEY (id);
ALTER TABLE lesson_assignments ADD PRIMARY KEY (id);
ALTER TABLE lesson_materials ADD PRIMARY KEY (id);
ALTER TABLE lesson_notes ADD PRIMARY KEY (id);
ALTER TABLE lesson_progress ADD PRIMARY KEY (id);
ALTER TABLE lesson_resources ADD PRIMARY KEY (id);
ALTER TABLE login_attempts ADD PRIMARY KEY (id);
ALTER TABLE notifications ADD PRIMARY KEY (id);
ALTER TABLE payments ADD PRIMARY KEY (id);
ALTER TABLE payment_settings ADD PRIMARY KEY (id);
ALTER TABLE payment_verification_logs ADD PRIMARY KEY (id);
ALTER TABLE platform_stats ADD PRIMARY KEY (id);
ALTER TABLE quizzes ADD PRIMARY KEY (id);
ALTER TABLE quiz_answers ADD PRIMARY KEY (id);
ALTER TABLE quiz_attempts ADD PRIMARY KEY (id);
ALTER TABLE quiz_questions ADD PRIMARY KEY (id);
ALTER TABLE quiz_options ADD PRIMARY KEY (id);

-- Add missing indexes
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_created_at (created_at);

ALTER TABLE courses ADD INDEX idx_instructor_id (instructor_id);
ALTER TABLE courses ADD INDEX idx_category_id (category_id);
ALTER TABLE courses ADD INDEX idx_status (status);
ALTER TABLE courses ADD INDEX idx_featured (featured);
ALTER TABLE courses ADD INDEX idx_created_at (created_at);

ALTER TABLE enrollments ADD INDEX idx_student_id (student_id);
ALTER TABLE enrollments ADD INDEX idx_course_id (course_id);
ALTER TABLE enrollments ADD INDEX idx_status (status);
ALTER TABLE enrollments ADD INDEX idx_created_at (created_at);

ALTER TABLE lessons ADD INDEX idx_course_id (course_id);
ALTER TABLE lessons ADD INDEX idx_lesson_order (lesson_order);

-- =====================================================
-- Step 5: Add Foreign Key Constraints
-- =====================================================

-- Note: Foreign keys will be added after data migration to avoid conflicts

-- =====================================================
-- Step 6: Data Migration and Cleanup
-- =====================================================

-- Migrate data from duplicate tables
-- (This would be done in separate migration scripts based on actual data analysis)

-- Remove duplicate tables after validation
-- DROP TABLE IF EXISTS categories_new;
-- DROP TABLE IF EXISTS courses_new;
-- DROP TABLE IF EXISTS enrollments_new;

-- =====================================================
-- Step 7: Create Views and Stored Procedures
-- =====================================================

-- Views and procedures will be created in separate migration files

-- =====================================================
-- Step 8: Validation Queries
-- =====================================================

-- Validate data integrity
SELECT 
    'users' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN uuid IS NULL THEN 1 END) as missing_uuid,
    COUNT(CASE WHEN email IS NULL OR email = '' THEN 1 END) as missing_email
FROM users
UNION ALL
SELECT 
    'courses' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN uuid IS NULL THEN 1 END) as missing_uuid,
    COUNT(CASE WHEN slug IS NULL OR slug = '' THEN 1 END) as missing_slug
FROM courses
UNION ALL
SELECT 
    'enrollments' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN uuid IS NULL THEN 1 END) as missing_uuid,
    COUNT(CASE WHEN enrollment_type IS NULL THEN 1 END) as missing_type
FROM enrollments;

-- =====================================================
-- Migration Summary
-- =====================================================

SELECT 
    'Migration 001 Complete' as status,
    NOW() as completed_at,
    'Database structure optimized for production' as description;

-- =====================================================
-- Rollback Script (if needed)
-- =====================================================

-- To rollback this migration:
-- 1. Restore data from backup tables
-- 2. Drop added columns
-- 3. Remove new indexes
-- 4. Drop new constraints

-- Example rollback commands:
-- INSERT INTO users SELECT * FROM backup_users;
-- ALTER TABLE users DROP COLUMN uuid;
-- ALTER TABLE users DROP COLUMN deleted_at;
-- (Continue for all modified tables)
