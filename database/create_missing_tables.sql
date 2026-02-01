-- Create missing tables for it_hub_clean database
-- This script creates all tables that are missing from the database

USE it_hub_clean;

-- Create completed_lessons table
CREATE TABLE IF NOT EXISTS completed_lessons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_lesson (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    certificate_id VARCHAR(50) UNIQUE NOT NULL,
    certificate_url VARCHAR(255),
    issued_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- Create admin_logs table
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE
);

-- Create chat_messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NULL DEFAULT NULL,
    course_id INT NULL DEFAULT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'file') DEFAULT 'text',
    file_path VARCHAR(500) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users_new(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE SET NULL
);

-- Create course_progress table
CREATE TABLE IF NOT EXISTS course_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    lesson_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completion_time TIMESTAMP NULL DEFAULT NULL,
    time_spent_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (student_id, course_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create course_reviews table
CREATE TABLE IF NOT EXISTS course_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (course_id, student_id),
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE
);

-- Create discussions table
CREATE TABLE IF NOT EXISTS discussions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    parent_id INT NULL DEFAULT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES discussions(id) ON DELETE CASCADE
);

-- Create enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    UNIQUE KEY unique_enrollment (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    instructor_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT DEFAULT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users_new(id) ON DELETE CASCADE
);

-- Create instructor_activity_log table
CREATE TABLE IF NOT EXISTS instructor_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    course_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE SET NULL
);

-- Create instructor_meta table
CREATE TABLE IF NOT EXISTS instructor_meta (
    instructor_id INT NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (instructor_id, meta_key),
    FOREIGN KEY (instructor_id) REFERENCES users_new(id) ON DELETE CASCADE
);

-- Create lessons table
CREATE TABLE IF NOT EXISTS lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    lesson_order INT NOT NULL DEFAULT 0,
    lesson_type ENUM('video', 'text', 'quiz') DEFAULT 'text',
    duration_minutes INT DEFAULT 0,
    is_free BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- Create lesson_materials table
CREATE TABLE IF NOT EXISTS lesson_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_new(id) ON DELETE CASCADE
);

-- Create quizzes table
CREATE TABLE IF NOT EXISTS quizzes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    lesson_id INT NULL DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    time_limit_minutes INT DEFAULT 30,
    passing_score DECIMAL(5,2) DEFAULT 70.00,
    max_attempts INT DEFAULT 3,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
);

-- Create quiz_attempts table
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    attempt_number INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    score DECIMAL(5,2) DEFAULT 0.00,
    total_points DECIMAL(5,2) DEFAULT 0.00,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    passed BOOLEAN DEFAULT FALSE,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Create quiz_answers table
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT NULL DEFAULT NULL,
    answer_text TEXT DEFAULT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    points_earned DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES quiz_options(id) ON DELETE SET NULL
);

-- Create quiz_options table
CREATE TABLE IF NOT EXISTS quiz_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Create quiz_questions table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
    points DECIMAL(5,2) DEFAULT 1.00,
    question_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Create course_meta table
CREATE TABLE IF NOT EXISTS course_meta (
    course_id INT NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (course_id, meta_key),
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE
);

-- Add foreign key constraints to existing tables if they don't exist
ALTER TABLE courses_new 
ADD CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES categories_new(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_courses_instructor FOREIGN KEY (instructor_id) REFERENCES users_new(id) ON DELETE CASCADE;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_enrollments_student ON enrollments(student_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_course ON enrollments(course_id);
CREATE INDEX IF NOT EXISTS idx_lessons_course ON lessons(course_id);
CREATE INDEX IF NOT EXISTS idx_quizzes_course ON quizzes(course_id);
CREATE INDEX IF NOT EXISTS idx_quiz_attempts_student ON quiz_attempts(student_id);
CREATE INDEX IF NOT EXISTS idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
CREATE INDEX IF NOT EXISTS idx_discussions_course ON discussions(course_id);
CREATE INDEX IF NOT EXISTS idx_feedback_course ON feedback(course_id);
CREATE INDEX IF NOT EXISTS idx_course_progress_student ON course_progress(student_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender ON chat_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_receiver ON chat_messages(receiver_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_instructor_activity_instructor ON instructor_activity_log(instructor_id);
CREATE INDEX IF NOT EXISTS idx_instructor_activity_created ON instructor_activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_course_reviews_course ON course_reviews(course_id);
CREATE INDEX IF NOT EXISTS idx_course_reviews_rating ON course_reviews(rating);
