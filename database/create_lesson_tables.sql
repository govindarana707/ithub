-- Create missing tables for lesson content

USE it_hub;

-- Lesson Notes (Instructor notes attached to a lesson)
CREATE TABLE IF NOT EXISTS lesson_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    note_type ENUM('markdown', 'text', 'html') DEFAULT 'markdown',
    file_path VARCHAR(255) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    is_downloadable BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lesson Assignments
CREATE TABLE IF NOT EXISTS lesson_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    assignment_type ENUM('file_upload', 'text_submission', 'quiz', 'external') DEFAULT 'file_upload',
    max_points INT DEFAULT 100,
    due_date DATETIME DEFAULT NULL,
    allow_late_submission BOOLEAN DEFAULT TRUE,
    late_penalty_percent INT DEFAULT 0,
    max_attempts INT DEFAULT 1,
    time_limit_minutes INT DEFAULT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lesson Resources (Additional files/links)
CREATE TABLE IF NOT EXISTS lesson_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    resource_type ENUM('document', 'presentation', 'video', 'audio', 'link', 'image', 'other') DEFAULT 'document',
    file_path VARCHAR(255) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    external_url VARCHAR(1000) DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    is_downloadable BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Student Notes (Personal notes for students)
CREATE TABLE IF NOT EXISTS student_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    content TEXT,
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_note (lesson_id, student_id)
);

-- Lesson Progress (Detailed tracking)
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    video_watch_time_seconds INT DEFAULT 0,
    video_completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    notes_viewed BOOLEAN DEFAULT FALSE,
    assignments_completed INT DEFAULT 0,
    assignments_total INT DEFAULT 0,
    resources_viewed INT DEFAULT 0,
    resources_total INT DEFAULT 0,
    time_spent_minutes INT DEFAULT 0,
    last_accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lesson_progress (lesson_id, student_id)
);

-- Assignment Submissions
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('file_upload', 'text_submission', 'link') DEFAULT 'file_upload',
    file_path VARCHAR(500) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    text_content TEXT DEFAULT NULL,
    submission_data JSON DEFAULT NULL,
    is_late BOOLEAN DEFAULT FALSE,
    attempt_number INT DEFAULT 1,
    points_earned DECIMAL(5,2) DEFAULT NULL,
    points_possible DECIMAL(5,2) DEFAULT 100.00,
    percentage_score DECIMAL(5,2) DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL DEFAULT NULL,
    graded_by INT DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    status ENUM('submitted', 'graded', 'returned', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES lesson_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Lesson Materials (Simple file attachments, possibly redundant with resources but used in lesson.php)
CREATE TABLE IF NOT EXISTS lesson_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) DEFAULT 'file',
    file_size BIGINT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);
