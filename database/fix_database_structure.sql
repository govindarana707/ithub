-- Database Structure Fix Migration Script
-- Fixes inconsistencies and data integrity issues
-- Created: 2026-03-29

-- =====================================================
-- STEP 1: Fix Foreign Key References to Use New Tables
-- =====================================================

-- Update course_meta to reference courses_new instead of courses
ALTER TABLE course_meta 
DROP FOREIGN KEY fk_course_meta_course;

ALTER TABLE course_meta 
ADD CONSTRAINT fk_course_meta_course_new 
FOREIGN KEY (course_id) REFERENCES courses_new (id) ON DELETE CASCADE;

-- Update instructor_meta to reference users_new instead of users
ALTER TABLE instructor_meta 
DROP FOREIGN KEY fk_instructor_meta_user;

ALTER TABLE instructor_meta 
ADD CONSTRAINT fk_instructor_meta_user_new 
FOREIGN KEY (instructor_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- =====================================================
-- STEP 2: Fix Missing Foreign Key Constraints
-- =====================================================

-- Add missing foreign key for course_reviews
ALTER TABLE course_reviews 
ADD CONSTRAINT fk_course_reviews_course 
FOREIGN KEY (course_id) REFERENCES courses_new (id) ON DELETE CASCADE;

ALTER TABLE course_reviews 
ADD CONSTRAINT fk_course_reviews_student 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix wishlists foreign keys
ALTER TABLE wishlists 
DROP FOREIGN KEY fk_wishlist_course;

ALTER TABLE wishlists 
ADD CONSTRAINT fk_wishlist_course_new 
FOREIGN KEY (course_id) REFERENCES courses_new (id) ON DELETE CASCADE;

ALTER TABLE wishlists 
DROP FOREIGN KEY fk_wishlist_student;

ALTER TABLE wishlists 
ADD CONSTRAINT fk_wishlist_student_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix completed_lessons foreign keys
ALTER TABLE completed_lessons 
DROP FOREIGN KEY completed_lessons_ibfk_1;

ALTER TABLE completed_lessons 
ADD CONSTRAINT completed_lessons_ibfk_1_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix discussions foreign keys
ALTER TABLE discussions 
ADD CONSTRAINT fk_discussions_course_new 
FOREIGN KEY (course_id) REFERENCES courses_new (id) ON DELETE CASCADE;

ALTER TABLE discussions 
DROP FOREIGN KEY discussions_ibfk_1;

ALTER TABLE discussions 
ADD CONSTRAINT discussions_ibfk_1_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix student_notes foreign keys
ALTER TABLE student_notes 
DROP FOREIGN KEY student_notes_ibfk_1;

ALTER TABLE student_notes 
ADD CONSTRAINT student_notes_ibfk_1_new 
FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE;

ALTER TABLE student_notes 
DROP FOREIGN KEY student_notes_ibfk_2;

ALTER TABLE student_notes 
ADD CONSTRAINT student_notes_ibfk_2_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix video_analytics foreign keys
ALTER TABLE video_analytics 
DROP FOREIGN KEY video_analytics_ibfk_2;

ALTER TABLE video_analytics 
ADD CONSTRAINT video_analytics_ibfk_2_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- Fix lesson_progress foreign keys
ALTER TABLE lesson_progress 
DROP FOREIGN KEY lesson_progress_ibfk_2;

ALTER TABLE lesson_progress 
ADD CONSTRAINT lesson_progress_ibfk_2_new 
FOREIGN KEY (student_id) REFERENCES users_new (id) ON DELETE CASCADE;

-- =====================================================
-- STEP 3: Data Migration - Move Active Data to New Tables
-- =====================================================

-- Migrate active courses from courses to courses_new if not exists
INSERT IGNORE INTO courses_new (id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at)
SELECT id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at
FROM courses 
WHERE id NOT IN (SELECT id FROM courses_new);

-- Migrate active users from users to users_new if not exists
INSERT IGNORE INTO users_new (id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at)
SELECT id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at
FROM users 
WHERE id NOT IN (SELECT id FROM users_new);

-- Migrate categories from categories to categories_new if not exists
INSERT IGNORE INTO categories_new (id, name, description, created_at, updated_at)
SELECT id, name, description, created_at, created_at
FROM categories 
WHERE id NOT IN (SELECT id FROM categories_new);

-- =====================================================
-- STEP 4: Fix Inconsistent Enum Values
-- =====================================================

-- Fix users_new role enum to match system expectations
ALTER TABLE users_new 
MODIFY COLUMN role ENUM('admin','instructor','student') DEFAULT 'student';

-- Fix courses_new status enum consistency
ALTER TABLE courses_new 
MODIFY COLUMN status ENUM('draft','published','archived') DEFAULT 'draft';

-- =====================================================
-- STEP 5: Add Missing Indexes for Performance
-- =====================================================

-- Add composite indexes for better query performance
ALTER TABLE enrollments_new 
ADD INDEX idx_user_course_status (user_id, course_id, status);

ALTER TABLE lesson_progress 
ADD INDEX idx_student_lesson_completion (student_id, completed, lesson_id);

ALTER TABLE video_analytics 
ADD INDEX idx_student_completion (student_id, completed_watching, lesson_id);

ALTER TABLE quiz_attempts 
ADD INDEX idx_student_quiz_status (student_id, quiz_id, status);

-- =====================================================
-- STEP 6: Clean Up Orphaned Data
-- =====================================================

-- Remove lesson_progress records for non-existent lessons
DELETE lp FROM lesson_progress lp
LEFT JOIN lessons l ON lp.lesson_id = l.id
WHERE l.id IS NULL;

-- Remove video_analytics records for non-existent lessons
DELETE va FROM video_analytics va
LEFT JOIN lessons l ON va.lesson_id = l.id
WHERE l.id IS NULL;

-- Remove completed_lessons records for non-existent lessons
DELETE cl FROM completed_lessons cl
LEFT JOIN lessons l ON cl.lesson_id = l.id
WHERE l.id IS NULL;

-- =====================================================
-- STEP 7: Update View Definitions
-- =====================================================

-- Drop and recreate payment_analytics view with correct references
DROP VIEW IF EXISTS payment_analytics;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW payment_analytics AS 
SELECT 
    CAST(p.created_at AS DATE) AS payment_date,
    p.payment_method AS payment_method,
    p.status AS status,
    COUNT(*) AS transaction_count,
    SUM(p.amount) AS total_amount,
    AVG(p.amount) AS average_amount,
    COUNT(CASE WHEN p.status = 'completed' THEN 1 END) AS successful_payments,
    COUNT(CASE WHEN p.status = 'failed' THEN 1 END) AS failed_payments,
    ROUND(COUNT(CASE WHEN p.status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) AS success_rate 
FROM payments p 
GROUP BY CAST(p.created_at AS DATE), p.payment_method, p.status;

-- =====================================================
-- STEP 8: Verify Data Integrity
-- =====================================================

-- Create a procedure to check data integrity
DELIMITER //
CREATE PROCEDURE check_data_integrity()
BEGIN
    -- Check for orphaned enrollments
    SELECT COUNT(*) as orphaned_enrollments
    FROM enrollments_new en
    LEFT JOIN users_new u ON en.user_id = u.id
    LEFT JOIN courses_new c ON en.course_id = c.id
    WHERE u.id IS NULL OR c.id IS NULL;
    
    -- Check for orphaned lesson progress
    SELECT COUNT(*) as orphaned_lesson_progress
    FROM lesson_progress lp
    LEFT JOIN users_new u ON lp.student_id = u.id
    LEFT JOIN lessons l ON lp.lesson_id = l.id
    WHERE u.id IS NULL OR l.id IS NULL;
    
    -- Check for orphaned certificates
    SELECT COUNT(*) as orphaned_certificates
    FROM certificates cert
    LEFT JOIN users_new u ON cert.student_id = u.id
    LEFT JOIN courses_new c ON cert.course_id = c.id
    WHERE u.id IS NULL OR c.id IS NULL;
END //
DELIMITER ;

-- =====================================================
-- STEP 9: Create Backup Tables (Optional Safety)
-- =====================================================

-- Create backup of critical tables before major changes
CREATE TABLE users_backup_20260329 AS SELECT * FROM users;
CREATE TABLE courses_backup_20260329 AS SELECT * FROM courses;
CREATE TABLE enrollments_backup_20260329 AS SELECT * FROM enrollments;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'Database structure fix completed successfully!' as status;
SELECT 'Run CALL check_data_integrity(); to verify data integrity' as next_step;

-- Notes:
-- 1. This script fixes foreign key references to use new tables
-- 2. Migrates any missing data from old to new tables
-- 3. Adds missing constraints and indexes
-- 4. Cleans up orphaned data
-- 5. Creates verification procedures
-- 
-- After running this script:
-- - Test all application functionality
-- - Run CALL check_data_integrity(); to verify
-- - Consider dropping old tables after thorough testing
