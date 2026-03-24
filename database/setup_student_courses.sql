-- Database Setup for Student Courses Functionality
-- This script ensures all necessary tables exist for the my-courses.php page

-- Create enrollments table if not exists
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'suspended', 'cancelled') DEFAULT 'active',
    last_accessed TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    INDEX idx_enrolled_at (enrolled_at),
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create lessons table if not exists
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content LONGTEXT,
    video_url VARCHAR(500),
    duration_minutes INT DEFAULT 0,
    lesson_order INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_course_id (course_id),
    INDEX idx_lesson_order (lesson_order),
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create completed_lessons table if not exists
CREATE TABLE IF NOT EXISTS completed_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (student_id, lesson_id),
    INDEX idx_student_id (student_id),
    INDEX idx_lesson_id (lesson_id),
    CONSTRAINT fk_completed_lessons_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_completed_lessons_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create lesson_progress table if not exists (alternative to completed_lessons)
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    time_spent_minutes INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (student_id, lesson_id),
    INDEX idx_student_id (student_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_status (status),
    CONSTRAINT fk_lesson_progress_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create study_sessions table if not exists
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    study_time INT DEFAULT 0 COMMENT 'Study time in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_start_time (start_time),
    CONSTRAINT fk_study_sessions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_study_sessions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create certificates table if not exists
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_id VARCHAR(100) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    pdf_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_status (status),
    CONSTRAINT fk_certificates_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificates_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create course_meta table if not exists
CREATE TABLE IF NOT EXISTS course_meta (
    course_id INT NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (course_id, meta_key),
    CONSTRAINT fk_course_meta_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Insert sample data for testing (optional)
INSERT IGNORE INTO courses (id, title, description, instructor_id, price, duration_hours, difficulty_level, status, thumbnail) VALUES
(1, 'Introduction to Web Development', 'Learn the basics of HTML, CSS, and JavaScript', 1, 0, 20, 'beginner', 'published', 'web-dev.jpg'),
(2, 'Advanced PHP Programming', 'Master PHP with advanced concepts and best practices', 1, 49.99, 30, 'advanced', 'published', 'php-advanced.jpg'),
(3, 'Database Design Fundamentals', 'Learn how to design and optimize databases', 2, 29.99, 15, 'intermediate', 'published', 'database.jpg');

INSERT IGNORE INTO users (id, username, email, full_name, role) VALUES
(1, 'instructor1', 'instructor@example.com', 'John Instructor', 'instructor'),
(2, 'student1', 'student@example.com', 'Jane Student', 'student');

INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Web Development'),
(2, 'Programming'),
(3, 'Database');

-- Update courses with categories
UPDATE courses SET category_id = 1 WHERE id = 1;
UPDATE courses SET category_id = 2 WHERE id = 2;
UPDATE courses SET category_id = 3 WHERE id = 3;

-- Sample enrollments for testing
INSERT IGNORE INTO enrollments (student_id, course_id, progress_percentage, status) VALUES
(2, 1, 75.5, 'active'),
(2, 2, 100.0, 'completed'),
(2, 3, 25.0, 'active');

-- Sample lessons for testing
INSERT IGNORE INTO lessons (course_id, title, description, lesson_order, is_published) VALUES
(1, 'Introduction to HTML', 'Learn the basics of HTML', 1, TRUE),
(1, 'CSS Fundamentals', 'Learn how to style web pages', 2, TRUE),
(1, 'JavaScript Basics', 'Introduction to JavaScript programming', 3, TRUE),
(2, 'PHP Syntax and Variables', 'Basic PHP syntax and variable handling', 1, TRUE),
(2, 'Functions and Classes', 'Object-oriented programming in PHP', 2, TRUE),
(3, 'Database Normalization', 'Understanding database normalization', 1, TRUE),
(3, 'SQL Queries', 'Writing effective SQL queries', 2, TRUE);

-- Sample lesson progress for testing
INSERT IGNORE INTO lesson_progress (student_id, lesson_id, status, progress_percentage) VALUES
(2, 1, 'completed', 100.0),
(2, 2, 'completed', 100.0),
(2, 3, 'in_progress', 50.0),
(2, 4, 'completed', 100.0),
(2, 5, 'not_started', 0.0),
(2, 6, 'in_progress', 75.0),
(2, 7, 'not_started', 0.0);

-- Sample certificates for testing
INSERT IGNORE INTO certificates (student_id, course_id, certificate_id, status) VALUES
(2, 2, 'CERT-PHP-2024-001', 'active');

-- Sample study sessions for testing
INSERT IGNORE INTO study_sessions (student_id, course_id, start_time, end_time, study_time) VALUES
(2, 1, '2024-01-15 10:00:00', '2024-01-15 11:30:00', 90),
(2, 1, '2024-01-16 14:00:00', '2024-01-16 15:00:00', 60),
(2, 2, '2024-01-10 09:00:00', '2024-01-10 11:00:00', 120),
(2, 3, '2024-01-17 16:00:00', '2024-01-17 16:45:00', 45);
