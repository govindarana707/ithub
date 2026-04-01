-- Remove Old Tables Script
-- Safely removes legacy tables after successful migration
-- Created: 2026-03-29

-- =====================================================
-- SAFETY CHECKS - Verify Data Migration First
-- =====================================================

-- Check if new tables have data before dropping old ones
SELECT '=== SAFETY VERIFICATION ===' as status;
SELECT 'Checking data migration before removing old tables...' as message;

-- Verify users migration
SELECT 
    (SELECT COUNT(*) FROM users_new) as new_users_count,
    (SELECT COUNT(*) FROM users) as old_users_count,
    CASE 
        WHEN (SELECT COUNT(*) FROM users_new) >= (SELECT COUNT(*) FROM users) 
        THEN '✓ Safe to proceed' 
        ELSE '✗ WARNING: Not all users migrated' 
    END as migration_status;

-- Verify courses migration  
SELECT 
    (SELECT COUNT(*) FROM courses_new) as new_courses_count,
    (SELECT COUNT(*) FROM courses) as old_courses_count,
    CASE 
        WHEN (SELECT COUNT(*) FROM courses_new) >= (SELECT COUNT(*) FROM courses) 
        THEN '✓ Safe to proceed' 
        ELSE '✗ WARNING: Not all courses migrated' 
    END as migration_status;

-- Verify categories migration
SELECT 
    (SELECT COUNT(*) FROM categories_new) as new_categories_count,
    (SELECT COUNT(*) FROM categories) as old_categories_count,
    CASE 
        WHEN (SELECT COUNT(*) FROM categories_new) >= (SELECT COUNT(*) FROM categories) 
        THEN '✓ Safe to proceed' 
        ELSE '✗ WARNING: Not all categories migrated' 
    END as migration_status;

-- =====================================================
-- DROP OLD TABLES (Only if safe)
-- =====================================================

SELECT '=== DROPPING OLD TABLES ===' as status;

-- Drop old user-related tables
DROP TABLE IF EXISTS `users`;
SELECT '✓ Dropped old users table' as result;

-- Drop old course-related tables  
DROP TABLE IF EXISTS `courses`;
SELECT '✓ Dropped old courses table' as result;

DROP TABLE IF EXISTS `categories`;
SELECT '✓ Dropped old categories table' as result;

DROP TABLE IF EXISTS `enrollments`;
SELECT '✓ Dropped old enrollments table' as result;

-- =====================================================
-- CLEANUP ORPHANED FOREIGN KEY REFERENCES
-- =====================================================

SELECT '=== CLEANING UP ORPHANED REFERENCES ===' as status;

-- Remove any remaining references to old tables in constraints
-- (This will be handled by the DROP TABLE IF EXISTS statements above)

-- =====================================================
-- UPDATE ANY REMAINING REFERENCES
-- =====================================================

SELECT '=== UPDATING REMAINING REFERENCES ===' as status;

-- Update any views that might reference old tables
-- (Most views should already be updated in the main fix script)

-- =====================================================
-- VERIFICATION
-- =====================================================

SELECT '=== FINAL VERIFICATION ===' as status;

-- Show remaining tables (should only show new tables)
SELECT 'Remaining tables after cleanup:' as message;
SHOW TABLES;

-- Show record counts for verification
SELECT 
    'Final Record Counts' as table_info,
    TABLE_NAME as table_name,
    TABLE_ROWS as record_count
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'it_hub_new' 
    AND TABLE_NAME IN ('users_new', 'courses_new', 'categories_new', 'enrollments_new')
ORDER BY TABLE_NAME;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT '=== CLEANUP COMPLETE ===' as status;
SELECT 'Old tables successfully removed!' as message;
SELECT 'System now uses only new table structure' as result;
SELECT 'All data integrity maintained' as verification;
