CREATE DATABASE IF NOT EXISTS it_hub;
USE it_hub;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    profile_image VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Course categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    instructor_id INT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    duration_hours INT DEFAULT 0,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Course enrollments
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    UNIQUE KEY unique_enrollment (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Lessons table
CREATE TABLE lessons (
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
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Lesson materials
CREATE TABLE lesson_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Quizzes table
CREATE TABLE quizzes (
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
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
);

-- Quiz questions
CREATE TABLE quiz_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
    points DECIMAL(5,2) DEFAULT 1.00,
    question_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz options (for multiple choice questions)
CREATE TABLE quiz_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Quiz attempts
CREATE TABLE quiz_attempts (
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
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz answers
CREATE TABLE quiz_answers (
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

-- Discussion forums
CREATE TABLE discussions (
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
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES discussions(id) ON DELETE CASCADE
);

-- Feedback system
CREATE TABLE feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    instructor_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT DEFAULT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin activity logs
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Course progress tracking
CREATE TABLE course_progress (
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
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Chat messages
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NULL DEFAULT NULL,
    course_id INT NULL DEFAULT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'file') DEFAULT 'text',
    file_path VARCHAR(500) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('IT Fundamentals', 'Basic computer and IT concepts for beginners'),
('Programming', 'Learn various programming languages and concepts'),
('Web Development', 'Frontend and backend web development technologies'),
('Database', 'Database design and management systems'),
('Cybersecurity', 'Information security and ethical hacking'),
('Mobile Development', 'iOS and Android app development'),
('Cloud Computing', 'Cloud platforms and services'),
('Data Science', 'Data analysis, machine learning, and AI');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample instructors
INSERT INTO users (username, email, password, full_name, role, bio) VALUES
('john_instructor', 'john@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'instructor', 'Experienced web developer with 10+ years in industry'),
('sarah_instructor', 'sarah@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'instructor', 'Data science expert and machine learning specialist');

-- Insert sample students
INSERT INTO users (username, email, password, full_name, role) VALUES
('alice_student', 'alice@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Wilson', 'student'),
('bob_student', 'bob@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Brown', 'student'),
('charlie_student', 'charlie@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie Davis', 'student');

-- Create indexes for better performance
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_course ON enrollments(course_id);
CREATE INDEX idx_lessons_course ON lessons(course_id);
CREATE INDEX idx_quizzes_course ON quizzes(course_id);
CREATE INDEX idx_quiz_attempts_student ON quiz_attempts(student_id);
CREATE INDEX idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
CREATE INDEX idx_discussions_course ON discussions(course_id);
CREATE INDEX idx_feedback_course ON feedback(course_id);
CREATE INDEX idx_course_progress_student ON course_progress(student_id);
CREATE INDEX idx_chat_messages_sender ON chat_messages(sender_id);
CREATE INDEX idx_chat_messages_receiver ON chat_messages(receiver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
?>
