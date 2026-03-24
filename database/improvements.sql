-- Logical Improvements for Courses System
-- Run these SQL commands to enable the enhanced features

-- 1. Add FULLTEXT index for better search performance
ALTER TABLE courses_new ADD FULLTEXT(title, description);

-- 2. Add enrollment integrity constraint
ALTER TABLE enrollments ADD CONSTRAINT unique_enrollment UNIQUE (student_id, course_id);

-- 3. Add soft delete capability
ALTER TABLE courses_new ADD COLUMN deleted_at TIMESTAMP NULL;
ALTER TABLE categories_new ADD COLUMN deleted_at TIMESTAMP NULL;

-- 4. Add approval workflow
ALTER TABLE courses_new ADD COLUMN approved BOOLEAN DEFAULT TRUE;
ALTER TABLE courses_new ADD COLUMN approved_by INT NULL;
ALTER TABLE courses_new ADD COLUMN approved_at TIMESTAMP NULL;

-- 5. Add visibility control
ALTER TABLE courses_new ADD COLUMN visibility ENUM('public', 'private', 'unlisted') DEFAULT 'public';

-- 6. Add course rating system
ALTER TABLE courses_new ADD COLUMN rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE courses_new ADD COLUMN rating_count INT DEFAULT 0;

-- 7. Add enrollment analytics table (if not exists)
CREATE TABLE IF NOT EXISTS enrollment_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50) DEFAULT 'web',
    payment_method VARCHAR(50) DEFAULT 'trial',
    completed_at TIMESTAMP NULL,
    last_activity TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    INDEX idx_student_course (student_id, course_id),
    INDEX idx_enrolled_at (enrolled_at),
    INDEX idx_source (source),
    INDEX idx_payment_method (payment_method),
    
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- 8. Add course statistics table for better performance
CREATE TABLE IF NOT EXISTS course_statistics (
    course_id INT PRIMARY KEY,
    total_enrollments INT DEFAULT 0,
    active_enrollments INT DEFAULT 0,
    completed_enrollments INT DEFAULT 0,
    average_progress DECIMAL(5,2) DEFAULT 0.00,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- 9. Add search tracking for analytics
CREATE TABLE IF NOT EXISTS search_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    filters TEXT NULL,
    results_count INT DEFAULT 0,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_query (query),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- 10. Add course tags for better categorization
CREATE TABLE IF NOT EXISTS course_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS course_tag_relations (
    course_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (course_id, tag_id),
    
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES course_tags(id) ON DELETE CASCADE
);

-- 11. Insert some default tags
INSERT IGNORE INTO course_tags (name, slug, color) VALUES
('JavaScript', 'javascript', '#f7df1e'),
('Python', 'python', '#3776ab'),
('PHP', 'php', '#777bb4'),
('React', 'react', '#61dafb'),
('Vue', 'vue', '#4fc08d'),
('Angular', 'angular', '#dd0031'),
('Node.js', 'nodejs', '#339933'),
('Database', 'database', '#336791'),
('DevOps', 'devops', '#ff6b35'),
('Mobile', 'mobile', '#a4c639');

-- 12. Create indexes for better performance
CREATE INDEX idx_courses_status_visibility ON courses_new(status, visibility, deleted_at);
CREATE INDEX idx_courses_category_difficulty ON courses_new(category_id, difficulty_level);
CREATE INDEX idx_courses_price_range ON courses_new(price);
CREATE INDEX idx_courses_rating ON courses_new(rating DESC);
CREATE INDEX idx_courses_created ON courses_new(created_at DESC);

-- 13. Add course prerequisites table (if using separate table)
CREATE TABLE IF NOT EXISTS course_prerequisites (
    course_id INT NOT NULL,
    prerequisite_course_id INT NOT NULL,
    PRIMARY KEY (course_id, prerequisite_course_id),
    
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- 14. Add course completion tracking
CREATE TABLE IF NOT EXISTS course_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_time_hours DECIMAL(5,2) DEFAULT 0.00,
    certificate_issued BOOLEAN DEFAULT FALSE,
    certificate_url VARCHAR(255) NULL,
    
    INDEX idx_student_course (student_id, course_id),
    INDEX idx_completed_at (completed_at),
    
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- 15. Update existing courses to have proper defaults
UPDATE courses_new SET 
    approved = TRUE,
    visibility = 'public',
    deleted_at = NULL,
    rating = 0.00,
    rating_count = 0
WHERE approved IS NULL OR visibility IS NULL;

-- 16. Create a view for active courses
CREATE OR REPLACE VIEW active_courses AS
SELECT 
    c.*,
    cat.name as category_name,
    u.full_name as instructor_name,
    cs.total_enrollments,
    cs.average_rating,
    cs.average_progress
FROM courses_new c
LEFT JOIN categories_new cat ON c.category_id = cat.id
LEFT JOIN users_new u ON c.instructor_id = u.id
LEFT JOIN course_statistics cs ON c.id = cs.course_id
WHERE c.status = 'published' 
  AND c.visibility = 'public' 
  AND c.deleted_at IS NULL 
  AND c.approved = TRUE;

-- 17. Add trigger to update course statistics
DELIMITER //
CREATE TRIGGER update_course_stats_after_enrollment
AFTER INSERT ON enrollments
FOR EACH ROW
BEGIN
    INSERT INTO course_statistics (course_id, total_enrollments, active_enrollments)
    VALUES (NEW.course_id, 1, 1)
    ON DUPLICATE KEY UPDATE 
        total_enrollments = total_enrollments + 1,
        active_enrollments = active_enrollments + 1,
        last_updated = CURRENT_TIMESTAMP;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER update_course_stats_after_completion
AFTER UPDATE ON enrollments
FOR EACH ROW
BEGIN
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        UPDATE course_statistics 
        SET completed_enrollments = completed_enrollments + 1,
            last_updated = CURRENT_TIMESTAMP
        WHERE course_id = NEW.course_id;
    END IF;
END//
DELIMITER ;

-- 18. Add stored procedure for advanced search
DELIMITER //
CREATE PROCEDURE search_courses_advanced(
    IN search_query VARCHAR(255),
    IN category_id INT,
    IN difficulty_level VARCHAR(20),
    IN price_min DECIMAL(10,2),
    IN price_max DECIMAL(10,2),
    IN rating_min DECIMAL(3,2),
    IN limit_offset INT,
    IN limit_count INT
)
BEGIN
    SELECT 
        c.*,
        cat.name as category_name,
        u.full_name as instructor_name,
        cs.total_enrollments,
        cs.average_rating,
        MATCH(c.title, c.description) AGAINST(search_query IN NATURAL LANGUAGE MODE) as relevance_score
    FROM courses_new c
    LEFT JOIN categories_new cat ON c.category_id = cat.id
    LEFT JOIN users_new u ON c.instructor_id = u.id
    LEFT JOIN course_statistics cs ON c.id = cs.course_id
    WHERE c.status = 'published' 
      AND c.visibility = 'public' 
      AND c.deleted_at IS NULL 
      AND c.approved = TRUE
      AND (search_query IS NULL OR search_query = '' OR MATCH(c.title, c.description) AGAINST(search_query IN NATURAL LANGUAGE MODE))
      AND (category_id IS NULL OR c.category_id = category_id)
      AND (difficulty_level IS NULL OR c.difficulty_level = difficulty_level)
      AND (price_min IS NULL OR c.price >= price_min)
      AND (price_max IS NULL OR c.price <= price_max)
      AND (rating_min IS NULL OR cs.average_rating >= rating_min)
    ORDER BY 
        CASE 
            WHEN search_query IS NOT NULL AND search_query != '' THEN relevance_score
            ELSE c.created_at
        END DESC,
        cs.total_enrollments DESC
    LIMIT limit_offset, limit_count;
END//
DELIMITER ;
