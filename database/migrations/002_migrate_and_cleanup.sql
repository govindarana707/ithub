-- =====================================================
-- Migration 002: Migrate Data and Clean Up Old Structure
-- =====================================================
-- Purpose: Migrate data to optimized schema and remove old tables
-- Version: 2.0.0
-- Date: 2026-03-20
-- Author: Database Architecture Team
-- 
-- ⚠️  WARNING: This script will permanently delete old tables!
--     Ensure you have a complete backup before running!
-- =====================================================

-- =====================================================
-- Step 0: Safety Checks and Preparations
-- =====================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Create migration log table
CREATE TABLE IF NOT EXISTS migration_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    migration_step VARCHAR(100) NOT NULL,
    status ENUM('started','completed','failed') NOT NULL,
    message TEXT,
    records_affected INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP()
);

-- Log migration start
INSERT INTO migration_log (migration_step, status, message) 
VALUES ('002_migrate_and_cleanup', 'started', 'Migration process initiated');

-- =====================================================
-- Step 1: Create Backup Tables (Safety Net)
-- =====================================================

CREATE TABLE IF NOT EXISTS backup_users_old LIKE users;
CREATE TABLE IF NOT EXISTS backup_courses_old LIKE courses;
CREATE TABLE IF NOT EXISTS backup_enrollments_old LIKE enrollments;
CREATE TABLE IF NOT EXISTS backup_lessons_old LIKE lessons;
CREATE TABLE IF NOT EXISTS backup_categories_old LIKE categories;
CREATE TABLE IF NOT EXISTS backup_payments_old LIKE payments;
CREATE TABLE IF NOT EXISTS backup_quizzes_old LIKE quizzes;

-- Backup critical data
INSERT INTO backup_users_old SELECT * FROM users;
INSERT INTO backup_courses_old SELECT * FROM courses;
INSERT INTO backup_enrollments_old SELECT * FROM enrollments;
INSERT INTO backup_lessons_old SELECT * FROM lessons;
INSERT INTO backup_categories_old SELECT * FROM categories;
INSERT INTO backup_payments_old SELECT * FROM payments;
INSERT INTO backup_quizzes_old SELECT * FROM quizzes;

-- Log backup completion
INSERT INTO migration_log (migration_step, status, message, records_affected) 
VALUES ('backup_creation', 'completed', 'Critical data backed up successfully', 7);

-- =====================================================
-- Step 2: Data Migration - Users Table
-- =====================================================

-- Check if new users table exists and has the required structure
SELECT COUNT(*) INTO @users_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'users_new';

IF @users_exists > 0 THEN
    -- Migrate users data
    INSERT INTO users_new (
        id, uuid, username, email, password_hash, full_name, phone, 
        profile_image, bio, role, status, email_verified, 
        created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        username,
        email,
        password_hash,
        full_name,
        phone,
        profile_image,
        bio,
        role,
        CASE 
            WHEN status = 'active' THEN 'active'
            WHEN status = 'inactive' THEN 'inactive'
            WHEN status = 'suspended' THEN 'suspended'
            ELSE 'pending'
        END as status,
        CASE 
            WHEN email_verified = 1 THEN 1 
            ELSE 0 
        END as email_verified,
        created_at,
        updated_at
    FROM users_old;
    
    -- Log migration
    SET @migrated_users = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('users_migration', 'completed', 'Users data migrated successfully', @migrated_users);
ELSE
    INSERT INTO migration_log (migration_step, status, message) 
    VALUES ('users_migration', 'failed', 'New users table not found', 0);
END IF;

-- =====================================================
-- Step 3: Data Migration - Categories Table
-- =====================================================

-- Check if new categories table exists
SELECT COUNT(*) INTO @categories_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'categories_new';

IF @categories_exists > 0 THEN
    -- Migrate categories data
    INSERT INTO categories_new (
        id, uuid, name, slug, description, icon, color, 
        sort_order, status, created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        name,
        LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '.', '-'), '_', '-')) as slug,
        description,
        NULL as icon,
        NULL as color,
        id as sort_order,
        'active' as status,
        created_at,
        created_at as updated_at
    FROM categories_old;
    
    -- Update course references to new categories
    UPDATE courses_new c
    SET c.category_id = (
        SELECT id FROM categories_new cn 
        WHERE cn.name = (SELECT name FROM categories_old co WHERE co.id = c.category_id)
    )
    WHERE c.category_id IN (SELECT id FROM categories_old);
    
    -- Log migration
    SET @migrated_categories = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('categories_migration', 'completed', 'Categories data migrated successfully', @migrated_categories);
ELSE
    INSERT INTO migration_log (migration_step, status, message) 
    VALUES ('categories_migration', 'failed', 'New categories table not found', 0);
END IF;

-- =====================================================
-- Step 4: Data Migration - Courses Table
-- =====================================================

-- Check if new courses table exists
SELECT COUNT(*) INTO @courses_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'courses_new';

IF @courses_exists > 0 THEN
    -- Migrate courses data
    INSERT INTO courses_new (
        id, uuid, instructor_id, category_id, title, slug, description,
        short_description, price, original_price, currency, duration_hours,
        difficulty_level, language, thumbnail, preview_video, status,
        featured, enrollment_count, rating, rating_count, published_at,
        created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        instructor_id,
        category_id,
        title,
        LOWER(REPLACE(REPLACE(REPLACE(title, ' ', '-'), '.', '-'), '_', '-')) as slug,
        description,
        LEFT(description, 200) as short_description,
        COALESCE(price, 0.00) as price,
        CASE 
            WHEN price > 0 THEN price * 1.2 
            ELSE NULL 
        END as original_price,
        'USD' as currency,
        COALESCE(duration_hours, 0) as duration_hours,
        COALESCE(difficulty_level, 'beginner') as difficulty_level,
        'en' as language,
        thumbnail,
        NULL as preview_video,
        status,
        COALESCE(featured, 0) as featured,
        COALESCE(enrollment_count, 0) as enrollment_count,
        COALESCE(rating, 0.00) as rating,
        COALESCE(rating_count, 0) as rating_count,
        CASE 
            WHEN status = 'published' THEN created_at 
            ELSE NULL 
        END as published_at,
        created_at,
        updated_at
    FROM courses_old;
    
    -- Log migration
    SET @migrated_courses = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('courses_migration', 'completed', 'Courses data migrated successfully', @migrated_courses);
ELSE
    INSERT INTO migration_log (migration_step, status, message) 
    VALUES ('courses_migration', 'failed', 'New courses table not found', 0);
END IF;

-- =====================================================
-- Step 5: Data Migration - Lessons Table
-- =====================================================

-- Check if new lessons table exists
SELECT COUNT(*) INTO @lessons_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'lessons_new';

IF @lessons_exists > 0 THEN
    -- Migrate lessons data
    INSERT INTO lessons_new (
        id, uuid, course_id, section_id, title, slug, description,
        content_type, content_data, video_url, video_duration, 
        lesson_order, is_free, is_published, estimated_minutes,
        created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        course_id,
        NULL as section_id,
        title,
        LOWER(REPLACE(REPLACE(REPLACE(title, ' ', '-'), '.', '-'), '_', '-')) as slug,
        description,
        COALESCE(content_type, 'text') as content_type,
        content_data,
        video_url,
        video_duration,
        lesson_order,
        COALESCE(is_free, 0) as is_free,
        COALESCE(is_published, 1) as is_published,
        NULL as estimated_minutes,
        created_at,
        updated_at
    FROM lessons_old;
    
    -- Log migration
    SET @migrated_lessons = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('lessons_migration', 'completed', 'Lessons data migrated successfully', @migrated_lessons);
ELSE
    INSERT INTO migration_log (migration_step, status, message) 
    VALUES ('lessons_migration', 'failed', 'New lessons table not found', 0);
END IF;

-- =====================================================
-- Step 6: Data Migration - Enrollments Table
-- =====================================================

-- Check if new enrollments table exists
SELECT COUNT(*) INTO @enrollments_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'enrollments_new';

IF @enrollments_exists > 0 THEN
    -- Migrate enrollments data
    INSERT INTO enrollments_new (
        id, uuid, student_id, course_id, enrollment_type, amount_paid,
        currency, status, progress_percentage, lessons_completed,
        total_lessons, time_spent_minutes, certificate_issued,
        created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        student_id,
        course_id,
        CASE 
            WHEN amount_paid IS NULL OR amount_paid = 0 THEN 'free'
            ELSE 'paid'
        END as enrollment_type,
        amount_paid,
        'USD' as currency,
        status,
        COALESCE(progress_percentage, 0.00) as progress_percentage,
        COALESCE(lessons_completed, 0) as lessons_completed,
        (
            SELECT COUNT(*) 
            FROM lessons l 
            WHERE l.course_id = e.course_id 
            AND l.is_published = 1
        ) as total_lessons,
        0 as time_spent_minutes,
        CASE 
            WHEN status = 'completed' THEN 1 
            ELSE 0 
        END as certificate_issued,
        enrolled_at as created_at,
        enrolled_at as updated_at
    FROM enrollments_old e;
    
    -- Update progress from completed_lessons
    UPDATE enrollments_new en
    SET lessons_completed = (
        SELECT COUNT(*) 
        FROM completed_lessons cl 
        WHERE cl.student_id = en.student_id 
        AND cl.lesson_id IN (
            SELECT id FROM lessons l 
            WHERE l.course_id = en.course_id 
            AND l.is_published = 1
        )
    ),
    progress_percentage = (
        CASE 
            WHEN total_lessons > 0 THEN (lessons_completed * 100.0 / total_lessons)
            ELSE 0 
        END
    );
    
    -- Log migration
    SET @migrated_enrollments = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('enrollments_migration', 'completed', 'Enrollments data migrated successfully', @migrated_enrollments);
ELSE
    INSERT INTO migration_log (migration_step, status, message) 
    VALUES ('enrollments_migration', 'failed', 'New enrollments table not found', 0);
END IF;

-- =====================================================
-- Step 7: Data Migration - Other Tables
-- =====================================================

-- Migrate payments data if exists
SELECT COUNT(*) INTO @payments_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'payments_new';

IF @payments_exists > 0 THEN
    INSERT INTO payments_new (
        id, uuid, user_id, course_id, transaction_id, amount,
        currency, status, gateway_response, created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        user_id,
        course_id,
        transaction_uuid as transaction_id,
        amount,
        'USD' as currency,
        status,
        NULL as gateway_response,
        created_at,
        updated_at
    FROM payments_old;
    
    SET @migrated_payments = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('payments_migration', 'completed', 'Payments data migrated successfully', @migrated_payments);
END IF;

-- Migrate quizzes data if exists
SELECT COUNT(*) INTO @quizzes_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'quizzes_new';

IF @quizzes_exists > 0 THEN
    INSERT INTO quizzes_new (
        id, uuid, course_id, title, description, time_limit_minutes,
        max_attempts, passing_score, status, created_at, updated_at
    )
    SELECT 
        id,
        UUID() as uuid,
        course_id,
        title,
        description,
        time_limit_minutes,
        max_attempts,
        passing_score,
        status,
        created_at,
        updated_at
    FROM quizzes_old;
    
    SET @migrated_quizzes = ROW_COUNT();
    INSERT INTO migration_log (migration_step, status, message, records_affected) 
    VALUES ('quizzes_migration', 'completed', 'Quizzes data migrated successfully', @migrated_quizzes);
END IF;

-- =====================================================
-- Step 8: Data Validation
-- =====================================================

-- Validate migrated data
SELECT 
    'Data Validation Results' as validation_type,
    (SELECT COUNT(*) FROM users_new) as migrated_users,
    (SELECT COUNT(*) FROM categories_new) as migrated_categories,
    (SELECT COUNT(*) FROM courses_new) as migrated_courses,
    (SELECT COUNT(*) FROM lessons_new) as migrated_lessons,
    (SELECT COUNT(*) FROM enrollments_new) as migrated_enrollments,
    (SELECT COUNT(*) FROM payments_new) as migrated_payments,
    (SELECT COUNT(*) FROM quizzes_new) as migrated_quizzes;

-- Check for data integrity issues
SELECT 
    'Data Integrity Check' as check_type,
    (SELECT COUNT(*) FROM users_new WHERE username IS NULL OR email IS NULL) as users_issues,
    (SELECT COUNT(*) FROM courses_new WHERE title IS NULL OR instructor_id IS NULL) as courses_issues,
    (SELECT COUNT(*) FROM enrollments_new WHERE student_id IS NULL OR course_id IS NULL) as enrollments_issues,
    (SELECT COUNT(*) FROM lessons_new WHERE title IS NULL OR course_id IS NULL) as lessons_issues;

-- Log validation results
INSERT INTO migration_log (migration_step, status, message) 
VALUES ('data_validation', 'completed', 'Data validation completed', 0);

-- =====================================================
-- Step 9: Table Swapping (Critical Step)
-- =====================================================

-- ⚠️  CRITICAL: This section will rename tables
-- Make sure all validation checks pass before proceeding

-- Create temporary table to store old table names
CREATE TABLE IF NOT EXISTS temp_old_tables (
    table_name VARCHAR(100),
    backup_name VARCHAR(100),
    action_taken VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP()
);

-- Store old table names before renaming
INSERT INTO temp_old_tables (table_name, backup_name, action_taken)
SELECT 
    table_name,
    CONCAT('backup_', table_name) as backup_name,
    'to_be_renamed' as action_taken
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name IN ('users', 'courses', 'categories', 'enrollments', 'lessons', 'payments', 'quizzes')
    AND table_name NOT LIKE 'backup_%'
    AND table_name NOT LIKE '%_old';

-- Rename old tables to backup names
RENAME TABLE users TO users_backup_migration;
RENAME TABLE courses TO courses_backup_migration;
RENAME TABLE categories TO categories_backup_migration;
RENAME TABLE enrollments TO enrollments_backup_migration;
RENAME TABLE lessons TO lessons_backup_migration;
RENAME TABLE payments TO payments_backup_migration;
RENAME TABLE quizzes TO quizzes_backup_migration;

-- Rename new tables to final names
RENAME TABLE users_new TO users;
RENAME TABLE courses_new TO courses;
RENAME TABLE categories_new TO categories;
RENAME TABLE enrollments_new TO enrollments;
RENAME TABLE lessons_new TO lessons;
RENAME TABLE payments_new TO payments;
RENAME TABLE quizzes_new TO quizzes;

-- Log table swapping
INSERT INTO migration_log (migration_step, status, message, records_affected) 
VALUES ('table_swapping', 'completed', 'Tables successfully renamed', 7);

-- =====================================================
-- Step 10: Update Foreign Key Constraints
-- =====================================================

-- Add foreign key constraints now that tables are in place
-- (These should already exist in the optimized schema)

ALTER TABLE courses 
ADD CONSTRAINT fk_courses_instructor 
FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE courses 
ADD CONSTRAINT fk_courses_category 
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE enrollments 
ADD CONSTRAINT fk_enrollments_student 
FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE enrollments 
ADD CONSTRAINT fk_enrollments_course 
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE lessons 
ADD CONSTRAINT fk_lessons_course 
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Log constraint addition
INSERT INTO migration_log (migration_step, status, message, records_affected) 
VALUES ('constraints_update', 'completed', 'Foreign key constraints updated', 5);

-- =====================================================
-- Step 11: Create Views and Stored Procedures
-- =====================================================

-- Create performance views (if they don't exist)
CREATE OR REPLACE VIEW v_course_statistics AS
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
    u.full_name as instructor_name,
    cat.name as category_name
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
LEFT JOIN lessons l ON c.id = l.course_id AND l.is_published = 1
LEFT JOIN users u ON c.instructor_id = u.id
LEFT JOIN categories cat ON c.category_id = cat.id
WHERE c.deleted_at IS NULL
GROUP BY c.id, c.uuid, c.title, c.instructor_id, c.category_id, c.status, c.price, c.enrollment_count, c.rating, c.rating_count, c.created_at, u.full_name, cat.name;

-- Create health check stored procedure
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_migration_health_check()
BEGIN
    SELECT 
        'Migration Health Check' as check_type,
        (SELECT COUNT(*) FROM users) as users_count,
        (SELECT COUNT(*) FROM courses) as courses_count,
        (SELECT COUNT(*) FROM enrollments) as enrollments_count,
        (SELECT COUNT(*) FROM lessons) as lessons_count,
        (SELECT COUNT(*) FROM categories) as categories_count,
        (SELECT COUNT(*) FROM payments) as payments_count,
        (SELECT COUNT(*) FROM quizzes) as quizzes_count,
        (SELECT COUNT(*) FROM migration_log) as migration_logs_count;
END //
DELIMITER ;

-- Log view creation
INSERT INTO migration_log (migration_step, status, message, records_affected) 
VALUES ('views_creation', 'completed', 'Views and stored procedures created', 2);

-- =====================================================
-- Step 12: Final Cleanup
-- =====================================================

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Update migration log with completion
INSERT INTO migration_log (migration_step, status, message) 
VALUES ('migration_completion', 'completed', 'Migration and cleanup process completed successfully', 0);

-- =====================================================
-- Step 13: Final Verification
-- =====================================================

-- Show migration summary
SELECT 
    'Migration Summary' as summary_type,
    (SELECT COUNT(*) FROM migration_log WHERE status = 'completed') as completed_steps,
    (SELECT COUNT(*) FROM migration_log WHERE status = 'failed') as failed_steps,
    (SELECT COUNT(*) FROM migration_log) as total_steps,
    NOW() as completion_time;

-- Show final table structure
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
    engine,
    table_comment
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name NOT LIKE 'backup_%'
    AND table_name NOT LIKE '%_backup%'
    AND table_name NOT LIKE '%_old%'
    AND table_name NOT LIKE 'temp_%'
    AND table_name NOT LIKE 'migration_%'
ORDER BY (data_length + index_length) DESC;

-- =====================================================
-- Rollback Script (Emergency Use Only)
-- =====================================================

/*
-- EMERGENCY ROLLBACK - USE ONLY IF MIGRATION FAILS
-- =====================================================

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Restore original tables
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS quizzes;

RENAME TABLE users_backup_migration TO users;
RENAME TABLE courses_backup_migration TO courses;
RENAME TABLE categories_backup_migration TO categories;
RENAME TABLE enrollments_backup_migration TO enrollments;
RENAME TABLE lessons_backup_migration TO lessons;
RENAME TABLE payments_backup_migration TO payments;
RENAME TABLE quizzes_backup_migration TO quizzes;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Log rollback
INSERT INTO migration_log (migration_step, status, message) 
VALUES ('emergency_rollback', 'completed', 'Emergency rollback executed', 0);

*/

-- =====================================================
-- Migration Complete
-- =====================================================

SELECT 
    '✅ MIGRATION COMPLETE' as status,
    'Database successfully migrated to optimized schema' as message,
    'All old tables have been backed up and can be restored if needed' as note,
    NOW() as completed_at;
