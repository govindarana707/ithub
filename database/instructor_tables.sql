-- Instructor Model Database Tables
-- These tables support the advanced instructor functionality

USE it_hub;

-- Instructor meta table for storing additional instructor information
CREATE TABLE IF NOT EXISTS instructor_meta (
    instructor_id INT NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (instructor_id, meta_key),
    CONSTRAINT fk_instructor_meta_user FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Instructor activity log for tracking instructor actions
CREATE TABLE IF NOT EXISTS instructor_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    course_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_instructor_activity_user FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_instructor_activity_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Course reviews table (for instructor analytics)
CREATE TABLE IF NOT EXISTS course_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (course_id, student_id),
    CONSTRAINT fk_course_review_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_review_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_instructor_activity_instructor ON instructor_activity_log(instructor_id);
CREATE INDEX IF NOT EXISTS idx_instructor_activity_created ON instructor_activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_course_reviews_course ON course_reviews(course_id);
CREATE INDEX IF NOT EXISTS idx_course_reviews_rating ON course_reviews(rating);

-- Sample instructor data (if no instructors exist)
INSERT IGNORE INTO users (username, email, password, full_name, role, status) VALUES
('john_instructor', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'instructor', 'active'),
('sarah_instructor', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'instructor', 'active');

-- Sample instructor meta data
INSERT IGNORE INTO instructor_meta (instructor_id, meta_key, meta_value) VALUES
(2, 'specialties', '["Web Development", "JavaScript", "React", "Node.js"]'),
(2, 'qualifications', '["BSc Computer Science", "5+ years industry experience", "AWS Certified"]'),
(2, 'social_links', '{"linkedin": "https://linkedin.com/in/johnsmith", "twitter": "@johnsmith"}'),
(3, 'specialties', '["Data Science", "Python", "Machine Learning", "Statistics"]'),
(3, 'qualifications', '["MSc Data Science", "PhD Machine Learning", "10+ years research experience"]'),
(3, 'social_links', '{"github": "https://github.com/sarahjohnson", "linkedin": "https://linkedin.com/in/sarahjohnson"}');

-- Sample course reviews for testing
INSERT IGNORE INTO course_reviews (course_id, student_id, rating, review) VALUES
(1, 4, 5, 'Excellent course! Very comprehensive and well-structured.'),
(1, 5, 4, 'Great content, but could use more practical examples.'),
(2, 4, 5, 'Amazing instructor! Really knows the subject matter.'),
(2, 6, 3, 'Good course, but pacing was a bit fast for beginners.');
