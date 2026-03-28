-- ============================================
-- DATABASE CLEANUP SCRIPT - it_hub
-- Generated: 2026-03-25
-- 
-- SAFETY: Run these commands incrementally
-- Always backup before deletion!
-- ============================================

-- ============================================
-- STEP 1: CREATE BACKUP OF CRITICAL TABLES
-- ============================================

-- Backup all critical tables before any cleanup
-- Uncomment these to create backups:
/*
CREATE TABLE IF NOT EXISTS backup_users_full LIKE users;
INSERT INTO backup_users_full SELECT * FROM users;

CREATE TABLE IF NOT EXISTS backup_courses_full LIKE courses;
INSERT INTO backup_courses_full SELECT * FROM courses;

CREATE TABLE IF NOT EXISTS backup_enrollments_full LIKE enrollments;
INSERT INTO backup_enrollments_full SELECT * FROM enrollments;

CREATE TABLE IF NOT EXISTS backup_courses_new_full LIKE courses_new;
INSERT INTO backup_courses_new_full SELECT * FROM courses_new;

CREATE TABLE IF NOT EXISTS backup_enrollments_new_full LIKE enrollments_new;
INSERT INTO backup_enrollments_new_full SELECT * FROM enrollments_new;

CREATE TABLE IF NOT EXISTS backup_users_new_full LIKE users_new;
INSERT INTO backup_users_new_full SELECT * FROM users_new;
*/


-- ============================================
-- STEP 2: SAFE CLEANUP - Migration Artifacts
-- Run these first - lowest risk
-- ============================================

-- Drop migration log table (only used in verification)
DROP TABLE IF EXISTS migration_log;

-- Drop temporary migration tables
DROP TABLE IF EXISTS temp_old_tables;

-- Drop old backup tables (created during migrations)
DROP TABLE IF EXISTS backup_users;
DROP TABLE IF EXISTS backup_courses;
DROP TABLE IF EXISTS backup_enrollments;
DROP TABLE IF EXISTS backup_lessons;
DROP TABLE IF EXISTS backup_users_old;
DROP TABLE IF EXISTS backup_courses_old;
DROP TABLE IF EXISTS backup_enrollments_old;
DROP TABLE IF EXISTS backup_lessons_old;
DROP TABLE IF EXISTS backup_categories_old;
DROP TABLE IF EXISTS backup_payments_old;
DROP TABLE IF EXISTS backup_quizzes_old;


-- ============================================
-- STEP 3: VERIFY UNUSED TABLES
-- Run this query to check for data before deletion
-- ============================================

/*
-- Check if unused tables have data (run this first!)
SELECT 'course_sections' as table_name, COUNT(*) as row_count FROM course_sections
UNION ALL
SELECT 'discussion_replies', COUNT(*) FROM discussion_replies
UNION ALL
SELECT 'course_lessons', COUNT(*) FROM course_lessons
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'platform_stats', COUNT(*) FROM platform_stats
UNION ALL
SELECT 'instructor_earnings', COUNT(*) FROM instructor_earnings
UNION ALL
SELECT 'coupons', COUNT(*) FROM coupons
UNION ALL
SELECT 'payment_analytics', COUNT(*) FROM payment_analytics;
*/


-- ============================================
-- STEP 4: CONDITIONAL DELETE - Unused Tables
-- Only run after verifying tables are empty!
-- ============================================

/*
-- Only uncomment and run if the verification query shows row_count = 0

-- Drop unused schema artifacts
-- DROP TABLE IF EXISTS course_sections;
-- DROP TABLE IF EXISTS discussion_replies;
-- DROP TABLE IF EXISTS course_lessons;
-- DROP TABLE IF EXISTS reviews;
-- DROP TABLE IF EXISTS platform_stats;
-- DROP TABLE IF EXISTS instructor_earnings;
-- DROP TABLE IF EXISTS coupons;
-- DROP TABLE IF EXISTS payment_analytics;
*/


-- ============================================
-- STEP 5: OPTIMIZATION - Add Missing Indexes
-- Run these to improve performance
-- ============================================

-- Progress tracking indexes
CREATE INDEX IF NOT EXISTS idx_lesson_progress_student ON lesson_progress(student_id);
CREATE INDEX IF NOT EXISTS idx_lesson_progress_lesson ON lesson_progress(lesson_id);
CREATE INDEX IF NOT EXISTS idx_study_sessions_student ON study_sessions(student_id);
CREATE INDEX IF NOT EXISTS idx_study_sessions_course ON study_sessions(course_id);

-- Recommendation system indexes  
CREATE INDEX IF NOT EXISTS idx_user_interactions_user ON user_interactions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_interactions_course ON user_interactions(course_id);
CREATE INDEX IF NOT EXISTS idx_course_recommendations_user ON course_recommendations(user_id);

-- Payment indexes
CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_course ON payments(course_id);
CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
CREATE INDEX IF NOT EXISTS idx_payments_transaction ON payments(transaction_uuid);

-- Enrollment indexes
CREATE INDEX IF NOT EXISTS idx_enrollments_new_user_course ON enrollments_new(user_id, course_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_new_status ON enrollments_new(status);

-- Wishlist indexes
CREATE INDEX IF NOT EXISTS idx_wishlists_student ON wishlists(student_id);
CREATE INDEX IF NOT EXISTS idx_wishlists_course ON wishlists(course_id);

-- Course meta indexes
CREATE INDEX IF NOT EXISTS idx_course_meta_course ON course_meta(course_id);

-- Instructor indexes
CREATE INDEX IF NOT EXISTS idx_instructor_meta_instructor ON instructor_meta(instructor_id);


-- ============================================
-- STEP 6: RENAME INSTEAD OF DELETE (Optional)
-- Safer alternative - rename tables instead of dropping
-- ============================================

/*
-- Example: Rename unused table instead of dropping
-- ALTER TABLE course_sections RENAME TO old_course_sections_backup_20260325;
-- ALTER TABLE discussion_replies RENAME TO old_discussion_replies_backup_20260325;
*/


-- ============================================
-- NOTES:
-- 1. Always run with database backup first
-- 2. Test in staging environment
-- 3. Run incrementally - check after each step
-- 4. Phase 3 (consolidation) requires code changes
-- ============================================
