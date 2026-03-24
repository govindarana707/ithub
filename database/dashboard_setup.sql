-- Dashboard Database Setup
-- Run this SQL to ensure all dashboard functionality works properly

-- 1. lessons table (if not exists)
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
    INDEX idx_lesson_order (lesson_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. lesson_progress table (tracks student lesson completion)
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    course_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    time_spent_minutes INT DEFAULT 0,
    last_accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (student_id, lesson_id),
    INDEX idx_student_id (student_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_course_id (course_id),
    INDEX idx_completed (completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. quizzes table
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 70,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. quiz_attempts table
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_quiz_id (quiz_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. study_sessions table
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    study_time INT DEFAULT 0 COMMENT 'Study time in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. completed_lessons table (alternative tracking)
CREATE TABLE IF NOT EXISTS completed_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (student_id, lesson_id),
    INDEX idx_student_id (student_id),
    INDEX idx_lesson_id (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(100) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    pdf_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_certificate_code (certificate_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample lessons for testing
INSERT IGNORE INTO lessons (id, course_id, title, description, duration_minutes, lesson_order, is_published) VALUES
(1, 1, 'Introduction to Course', 'Welcome to the course overview', 15, 1, TRUE),
(2, 1, 'Basic Concepts', 'Learn the fundamental concepts', 30, 2, TRUE),
(3, 1, 'Advanced Topics', 'Dive into advanced material', 45, 3, TRUE),
(4, 2, 'Getting Started', 'Setup and installation guide', 20, 1, TRUE),
(5, 2, 'Core Principles', 'Understanding the core principles', 40, 2, TRUE),
(6, 3, 'Module 1: Basics', 'Introduction to basics', 25, 1, TRUE);

-- Insert sample quizzes for testing
INSERT IGNORE INTO quizzes (id, course_id, title, description, time_limit_minutes, passing_score, is_published) VALUES
(1, 1, 'Course Assessment', 'Test your knowledge', 30, 70, TRUE),
(2, 2, 'Midterm Quiz', 'Check your progress', 45, 75, TRUE),
(3, 3, 'Final Assessment', 'Comprehensive test', 60, 80, TRUE);

-- Add demo lesson progress for existing enrolled students
INSERT IGNORE INTO lesson_progress (student_id, lesson_id, course_id, completed, time_spent_minutes)
SELECT e.student_id, l.id, e.course_id, 
    CASE WHEN e.progress_percentage >= 100 THEN TRUE ELSE FALSE END,
    CASE WHEN e.progress_percentage >= 100 THEN 120 ELSE ROUND(e.progress_percentage * 1.2) END
FROM enrollments e
JOIN lessons l ON l.course_id = e.course_id
WHERE e.status = 'active';
