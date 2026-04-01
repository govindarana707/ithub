-- Complete Migration Script
-- Ensures all data is migrated from old to new tables
-- Created: 2026-03-29

-- =====================================================
-- STEP 1: Complete Users Migration
-- =====================================================

SELECT '=== MIGRATING USERS ===' as status;

-- Migrate any missing users from users to users_new
INSERT IGNORE INTO users_new (id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at)
SELECT id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at
FROM users 
WHERE id NOT IN (SELECT id FROM users_new);

SELECT CONCAT('Migrated ', ROW_COUNT(), ' users') as result;

-- =====================================================
-- STEP 2: Complete Categories Migration
-- =====================================================

SELECT '=== MIGRATING CATEGORIES ===' as status;

-- Migrate any missing categories from categories to categories_new
INSERT IGNORE INTO categories_new (id, name, description, created_at, updated_at)
SELECT id, name, description, created_at, created_at
FROM categories 
WHERE id NOT IN (SELECT id FROM categories_new);

SELECT CONCAT('Migrated ', ROW_COUNT(), ' categories') as result;

-- =====================================================
-- STEP 3: Complete Courses Migration
-- =====================================================

SELECT '=== MIGRATING COURSES ===' as status;

-- Migrate any missing courses from courses to courses_new
INSERT IGNORE INTO courses_new (id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at)
SELECT id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at
FROM courses 
WHERE id NOT IN (SELECT id FROM courses_new);

SELECT CONCAT('Migrated ', ROW_COUNT(), ' courses') as result;

-- =====================================================
-- STEP 4: Migrate Enrollments
-- =====================================================

SELECT '=== MIGRATING ENROLLMENTS ===' as status;

-- Migrate enrollments from old to new structure
INSERT IGNORE INTO enrollments_new (id, user_id, course_id, enrollment_type, status, progress_percentage, enrolled_at, completed_at, created_at, updated_at)
SELECT id, student_id as user_id, course_id, 
       CASE 
           WHEN progress_percentage >= 100 THEN 'completed'
           ELSE 'active'
       END as enrollment_type,
       CASE 
           WHEN status = 'completed' THEN 'completed'
           ELSE 'active'
       END as status,
       progress_percentage, enrolled_at, completed_at, enrolled_at as created_at, updated_at
FROM enrollments 
WHERE id NOT IN (SELECT id FROM enrollments_new);

SELECT CONCAT('Migrated ', ROW_COUNT(), ' enrollments') as result;

-- =====================================================
-- STEP 5: Verification
-- =====================================================

SELECT '=== MIGRATION VERIFICATION ===' as status;

-- Show final counts
SELECT 'Final Migration Status:' as summary;

SELECT 
    'users_new' as table_name, 
    COUNT(*) as new_count,
    (SELECT COUNT(*) FROM users) as old_count,
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM users) THEN '✓ Complete'
        ELSE '✗ Incomplete'
    END as status
FROM users_new

UNION ALL

SELECT 
    'courses_new' as table_name, 
    COUNT(*) as new_count,
    (SELECT COUNT(*) FROM courses) as old_count,
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM courses) THEN '✓ Complete'
        ELSE '✗ Incomplete'
    END as status
FROM courses_new

UNION ALL

SELECT 
    'categories_new' as table_name, 
    COUNT(*) as new_count,
    (SELECT COUNT(*) FROM categories) as old_count,
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM categories) THEN '✓ Complete'
        ELSE '✗ Incomplete'
    END as status
FROM categories_new

UNION ALL

SELECT 
    'enrollments_new' as table_name, 
    COUNT(*) as new_count,
    (SELECT COUNT(*) FROM enrollments) as old_count,
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM enrollments) THEN '✓ Complete'
        ELSE '✗ Incomplete'
    END as status
FROM enrollments_new;

SELECT 'Migration process completed!' as final_status;
