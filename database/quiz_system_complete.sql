-- Quiz System Database Schema
-- Complete database structure for quiz functionality

-- Quiz Questions Table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL DEFAULT 'multiple_choice',
    points INT DEFAULT 1,
    question_order INT DEFAULT 0,
    explanation TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz_order (quiz_id, question_order)
);

-- Quiz Options Table
CREATE TABLE IF NOT EXISTS quiz_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_question_order (question_id, option_order)
);

-- Quiz Answers Table
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT DEFAULT NULL,
    answer_text TEXT DEFAULT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES quiz_options(id) ON DELETE SET NULL,
    INDEX idx_attempt_question (attempt_id, question_id)
);

-- Quiz Attempts Table (Enhanced)
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    attempt_number INT DEFAULT 1,
    score INT DEFAULT 0,
    total_points INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    passed BOOLEAN DEFAULT FALSE,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    time_taken_minutes INT DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_student_quiz (student_id, quiz_id),
    INDEX idx_status (status)
);

-- Add missing columns to existing quizzes table if they don't exist
ALTER TABLE quizzes 
ADD COLUMN IF NOT EXISTS passing_score INT DEFAULT 70,
ADD COLUMN IF NOT EXISTS max_attempts INT DEFAULT 3,
ADD COLUMN IF NOT EXISTS time_limit_minutes INT DEFAULT 60,
ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add explanation column to quiz_questions if it doesn't exist
ALTER TABLE quiz_questions 
ADD COLUMN IF NOT EXISTS explanation TEXT DEFAULT NULL;

-- Insert sample quiz data (optional)
INSERT IGNORE INTO quizzes (course_id, title, description, time_limit_minutes, passing_score, max_attempts, status) VALUES
(1, 'Introduction to PHP', 'Test your knowledge of PHP basics', 30, 70, 3, 'published'),
(1, 'HTML Fundamentals', 'Basic HTML concepts and tags', 20, 60, 3, 'published');

-- Sample questions for the first quiz
INSERT IGNORE INTO quiz_questions (quiz_id, question_text, question_type, points, question_order, explanation) VALUES
(1, 'What does PHP stand for?', 'multiple_choice', 1, 1, 'PHP is a recursive acronym for PHP: Hypertext Preprocessor'),
(1, 'PHP was originally created for what purpose?', 'multiple_choice', 2, 2, 'PHP was designed for web development'),
(1, 'Is PHP a compiled language?', 'true_false', 1, 3, 'PHP is an interpreted language, not compiled');

-- Sample options for multiple choice questions
INSERT IGNORE INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
(1, 'Personal Home Page', 0, 1),
(1, 'PHP: Hypertext Preprocessor', 1, 2),
(1, 'Private HTML Page', 0, 3),
(1, 'Public HTML Page', 0, 4),
(2, 'Desktop application development', 0, 1),
(2, 'Web development', 1, 2),
(2, 'Mobile app development', 0, 3),
(2, 'Database management', 0, 4);
