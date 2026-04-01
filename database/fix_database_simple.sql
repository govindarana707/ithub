-- Simplified Database Fix Script (without complex operations)
-- For execution when MySQL server has issues

-- =====================================================
-- STEP 1: Basic Data Verification
-- =====================================================

-- Check current table status
SELECT 'Checking table structures...' as status;

-- Show which tables exist
SHOW TABLES LIKE '%courses%';
SHOW TABLES LIKE '%users%';
SHOW TABLES LIKE '%enrollments%';

-- =====================================================
-- STEP 2: Check Data Consistency
-- =====================================================

-- Check for orphaned records in enrollments_new
SELECT 'Checking enrollments_new data integrity...' as status;
SELECT COUNT(*) as orphaned_enrollments
FROM enrollments_new en
LEFT JOIN users_new u ON en.user_id = u.id
LEFT JOIN courses_new c ON en.course_id = c.id
WHERE u.id IS NULL OR c.id IS NULL;

-- Check for orphaned records in course_reviews
SELECT 'Checking course_reviews data integrity...' as status;
SELECT COUNT(*) as orphaned_reviews
FROM course_reviews cr
LEFT JOIN users_new u ON cr.student_id = u.id
LEFT JOIN courses_new c ON cr.course_id = c.id
WHERE u.id IS NULL OR c.id IS NULL;

-- =====================================================
-- STEP 3: Basic Fixes (Safe Operations)
-- =====================================================

-- Fix categories_new data if missing
INSERT IGNORE INTO categories_new (id, name, description, created_at, updated_at)
SELECT id, name, description, created_at, created_at
FROM categories 
WHERE id NOT IN (SELECT id FROM categories_new);

-- Fix users_new data if missing  
INSERT IGNORE INTO users_new (id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at)
SELECT id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at
FROM users 
WHERE id NOT IN (SELECT id FROM users_new);

-- Fix courses_new data if missing
INSERT IGNORE INTO courses_new (id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at)
SELECT id, title, description, category_id, instructor_id, thumbnail, price, duration_hours, difficulty_level, status, created_at, updated_at
FROM courses 
WHERE id NOT IN (SELECT id FROM courses_new);

-- =====================================================
-- STEP 4: Display Summary
-- =====================================================

SELECT 'Database fix completed!' as status;
SELECT 'Run the full fix script when MySQL is properly running' as note;
SELECT 'Current record counts:' as summary;

SELECT 'users_new' as table_name, COUNT(*) as record_count FROM users_new
UNION ALL
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL  
SELECT 'courses_new' as table_name, COUNT(*) as record_count FROM courses_new
UNION ALL
SELECT 'courses' as table_name, COUNT(*) as record_count FROM courses
UNION ALL
SELECT 'enrollments_new' as table_name, COUNT(*) as record_count FROM enrollments_new
UNION ALL
SELECT 'enrollments' as table_name, COUNT(*) as record_count FROM enrollments;
