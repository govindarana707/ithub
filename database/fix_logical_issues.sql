-- Fix Logical Issues in Database Structure
-- Addresses data consistency, naming, and relationship problems
-- Created: 2026-03-29

-- =====================================================
-- STEP 1: Fix Data Type Inconsistencies
-- =====================================================

SELECT '=== FIXING DATA TYPE INCONSISTENCIES ===' as status;

-- Fix users_new role enum to match system expectations
ALTER TABLE users_new 
MODIFY COLUMN role ENUM('admin','instructor','student') NOT NULL DEFAULT 'student';

-- Fix courses_new status enum consistency
ALTER TABLE courses_new 
MODIFY COLUMN status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft';

-- Fix enrollments_new status enum to include all needed values
ALTER TABLE enrollments_new 
MODIFY COLUMN status ENUM('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active';

-- =====================================================
-- STEP 2: Fix Missing Indexes for Performance
-- =====================================================

SELECT '=== ADDING PERFORMANCE INDEXES ===' as status;

-- Add composite index for user authentication
ALTER TABLE users_new 
ADD INDEX idx_user_auth (username, email, status);

-- Add index for course searches
ALTER TABLE courses_new 
ADD INDEX idx_course_search (status, instructor_id, category_id);

-- Add index for enrollment lookups
ALTER TABLE enrollments_new 
ADD INDEX idx_enrollment_lookup (user_id, course_id, status);

-- Add index for lesson progress queries
ALTER TABLE lesson_progress 
ADD INDEX idx_lesson_progress_lookup (student_id, lesson_id, completed);

-- =====================================================
-- STEP 3: Fix Orphaned Data Issues
-- =====================================================

SELECT '=== FIXING ORPHANED DATA ===' as status;

-- Fix enrollments_new with invalid user_id
UPDATE enrollments_new en
SET user_id = NULL 
WHERE user_id NOT IN (SELECT id FROM users_new);

-- Fix enrollments_new with invalid course_id  
UPDATE enrollments_new en
SET course_id = NULL 
WHERE course_id NOT IN (SELECT id FROM courses_new);

-- Fix courses_new with invalid instructor_id
UPDATE courses_new c
SET instructor_id = NULL 
WHERE instructor_id NOT IN (SELECT id FROM users_new);

-- Fix courses_new with invalid category_id
UPDATE courses_new c
SET category_id = NULL 
WHERE category_id NOT IN (SELECT id FROM categories_new);

-- =====================================================
-- STEP 4: Fix Default Values and Constraints
-- =====================================================

SELECT '=== FIXING DEFAULT VALUES ===' as status;

-- Ensure proper defaults for users_new
ALTER TABLE users_new 
ALTER COLUMN status SET DEFAULT 'active',
ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ensure proper defaults for courses_new
ALTER TABLE courses_new 
ALTER COLUMN status SET DEFAULT 'draft',
ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ensure proper defaults for enrollments_new
ALTER TABLE enrollments_new 
ALTER COLUMN status SET DEFAULT 'active',
ALTER COLUMN enrollment_type SET DEFAULT 'paid',
ALTER COLUMN progress_percentage SET DEFAULT 0.00,
ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- =====================================================
-- STEP 5: Fix Logical Relationship Issues
-- =====================================================

SELECT '=== FIXING RELATIONSHIP ISSUES ===' as status;

-- Ensure all courses have valid instructors
UPDATE courses_new c
LEFT JOIN users_new u ON c.instructor_id = u.id
SET c.instructor_id = NULL
WHERE u.id IS NULL AND c.instructor_id IS NOT NULL;

-- Ensure all enrollments have valid users and courses
UPDATE enrollments_new en
LEFT JOIN users_new u ON en.user_id = u.id
LEFT JOIN courses_new c ON en.course_id = c.id
SET en.status = 'cancelled'
WHERE u.id IS NULL OR c.id IS NULL;

-- =====================================================
-- STEP 6: Fix Data Validation Issues
-- =====================================================

SELECT '=== FIXING DATA VALIDATION ===' as status;

-- Fix negative progress percentages
UPDATE enrollments_new 
SET progress_percentage = 0.00 
WHERE progress_percentage < 0;

-- Fix progress percentages over 100
UPDATE enrollments_new 
SET progress_percentage = 100.00 
WHERE progress_percentage > 100;

-- Fix future dates in created_at
UPDATE users_new 
SET created_at = CURRENT_TIMESTAMP 
WHERE created_at > CURRENT_TIMESTAMP;

UPDATE courses_new 
SET created_at = CURRENT_TIMESTAMP 
WHERE created_at > CURRENT_TIMESTAMP;

UPDATE enrollments_new 
SET created_at = CURRENT_TIMESTAMP 
WHERE created_at > CURRENT_TIMESTAMP;

-- =====================================================
-- STEP 7: Add Missing Constraints for Data Integrity
-- =====================================================

SELECT '=== ADDING MISSING CONSTRAINTS ===' as status;

-- Add check constraint for progress percentage
ALTER TABLE enrollments_new 
ADD CONSTRAINT chk_progress_percentage 
CHECK (progress_percentage >= 0 AND progress_percentage <= 100);

-- Add check constraint for course price
ALTER TABLE courses_new 
ADD CONSTRAINT chk_course_price 
CHECK (price >= 0);

-- Add check constraint for duration hours
ALTER TABLE courses_new 
ADD CONSTRAINT chk_duration_hours 
CHECK (duration_hours >= 0);

-- =====================================================
-- STEP 8: Optimize Table Structures
-- =====================================================

SELECT '=== OPTIMIZING TABLE STRUCTURES ===' as status;

-- Optimize users_new table
OPTIMIZE TABLE users_new;

-- Optimize courses_new table
OPTIMIZE TABLE courses_new;

-- Optimize enrollments_new table
OPTIMIZE TABLE enrollments_new;

-- Optimize categories_new table
OPTIMIZE TABLE categories_new;

-- =====================================================
-- STEP 9: Create Views for Common Queries
-- =====================================================

SELECT '=== CREATING OPTIMIZATION VIEWS ===' as status;

-- Create view for active courses with instructor info
CREATE OR REPLACE VIEW active_courses_view AS
SELECT 
    c.id,
    c.title,
    c.description,
    c.price,
    c.duration_hours,
    c.difficulty_level,
    c.created_at,
    u.full_name as instructor_name,
    u.email as instructor_email,
    cat.name as category_name
FROM courses_new c
LEFT JOIN users_new u ON c.instructor_id = u.id
LEFT JOIN categories_new cat ON c.category_id = cat.id
WHERE c.status = 'published';

-- Create view for user enrollments with course details
CREATE OR REPLACE VIEW user_enrollments_view AS
SELECT 
    en.id as enrollment_id,
    en.user_id,
    en.course_id,
    en.status as enrollment_status,
    en.progress_percentage,
    en.enrolled_at,
    c.title as course_title,
    c.price as course_price,
    u.full_name as student_name,
    instructor.full_name as instructor_name
FROM enrollments_new en
LEFT JOIN courses_new c ON en.course_id = c.id
LEFT JOIN users_new u ON en.user_id = u.id
LEFT JOIN users_new instructor ON c.instructor_id = instructor.id;

-- =====================================================
-- STEP 10: Verification and Summary
-- =====================================================

SELECT '=== VERIFICATION AND SUMMARY ===' as status;

-- Show table statistics
SELECT 
    TABLE_NAME as table_name,
    TABLE_ROWS as estimated_rows,
    ROUND(DATA_LENGTH/1024/1024, 2) as data_size_mb,
    ROUND(INDEX_LENGTH/1024/1024, 2) as index_size_mb
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'it_hub_new' 
    AND TABLE_NAME IN ('users_new', 'courses_new', 'categories_new', 'enrollments_new')
ORDER BY TABLE_NAME;

-- Show data consistency check
SELECT 'Data Consistency Check:' as check_type;

SELECT 
    'Users with valid email' as check_name,
    COUNT(*) as count,
    CASE WHEN COUNT(*) > 0 THEN '✓ Pass' ELSE '✗ Fail' END as status
FROM users_new 
WHERE email != '' AND email IS NOT NULL

UNION ALL

SELECT 
    'Courses with valid instructor' as check_name,
    COUNT(*) as count,
    CASE WHEN COUNT(*) = SUM(CASE WHEN instructor_id IS NOT NULL THEN 1 ELSE 0 END) THEN '✓ Pass' ELSE '✗ Fail' END as status
FROM courses_new

UNION ALL

SELECT 
    'Enrollments with valid user and course' as check_name,
    COUNT(*) as count,
    CASE WHEN COUNT(*) = SUM(CASE WHEN user_id IS NOT NULL AND course_id IS NOT NULL THEN 1 ELSE 0 END) THEN '✓ Pass' ELSE '✗ Fail' END as status
FROM enrollments_new;

SELECT 'Logical fixes completed successfully!' as final_status;
