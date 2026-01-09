-- Comprehensive Lesson Content System Schema
-- This script adds support for notes, assignments, and enhanced lesson content

USE it_hub;

-- Create lesson_notes table for instructor notes
CREATE TABLE IF NOT EXISTS lesson_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    note_type ENUM('text', 'markdown', 'html') DEFAULT 'markdown',
    is_downloadable BOOLEAN DEFAULT FALSE,
    file_path VARCHAR(500) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_lesson_notes_lesson (lesson_id),
    INDEX idx_lesson_notes_instructor (instructor_id)
);

-- Create lesson_assignments table for assignments
CREATE TABLE IF NOT EXISTS lesson_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    instructions TEXT,
    assignment_type ENUM('file_upload', 'text_submission', 'quiz', 'project') DEFAULT 'file_upload',
    max_points DECIMAL(10,2) DEFAULT 100.00,
    due_date TIMESTAMP NULL,
    allow_late_submission BOOLEAN DEFAULT FALSE,
    late_penalty_percent DECIMAL(5,2) DEFAULT 10.00,
    max_attempts INT DEFAULT 1,
    time_limit_minutes INT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_lesson_assignments_lesson (lesson_id),
    INDEX idx_lesson_assignments_instructor (instructor_id),
    INDEX idx_lesson_assignments_due_date (due_date)
);

-- Create assignment_submissions table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('file_upload', 'text_submission', 'quiz_attempt') NOT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    text_content TEXT DEFAULT NULL,
    submission_data JSON DEFAULT NULL, -- For quiz attempts and structured data
    points_earned DECIMAL(10,2) DEFAULT NULL,
    points_possible DECIMAL(10,2) DEFAULT NULL,
    percentage_score DECIMAL(5,2) DEFAULT NULL,
    is_late BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    graded_by INT NULL,
    feedback TEXT DEFAULT NULL,
    status ENUM('submitted', 'graded', 'returned', 'resubmitted') DEFAULT 'submitted',
    attempt_number INT DEFAULT 1,
    FOREIGN KEY (assignment_id) REFERENCES lesson_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_assignment_submissions_assignment (assignment_id),
    INDEX idx_assignment_submissions_student (student_id),
    INDEX idx_assignment_submissions_status (status)
);

-- Create student_notes table for student personal notes
CREATE TABLE IF NOT EXISTS student_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_lesson_note (lesson_id, student_id),
    INDEX idx_student_notes_lesson (lesson_id),
    INDEX idx_student_notes_student (student_id)
);

-- Create lesson_resources table for additional materials
CREATE TABLE IF NOT EXISTS lesson_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    resource_type ENUM('document', 'presentation', 'video', 'audio', 'link', 'image') NOT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    external_url VARCHAR(1000) DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    is_downloadable BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_lesson_resources_lesson (lesson_id),
    INDEX idx_lesson_resources_type (resource_type)
);

-- Update lessons table to include content organization
ALTER TABLE lessons 
ADD COLUMN content_sections JSON DEFAULT NULL COMMENT 'Organized content sections',
ADD COLUMN learning_objectives TEXT DEFAULT NULL COMMENT 'Learning objectives for the lesson',
ADD COLUMN prerequisites TEXT DEFAULT NULL COMMENT 'Prerequisites for this lesson',
ADD COLUMN estimated_time_minutes INT DEFAULT NULL COMMENT 'Estimated time to complete lesson',
ADD COLUMN difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner';

-- Create lesson_progress table for detailed progress tracking
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    video_watch_time_seconds INT DEFAULT 0,
    video_completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    notes_viewed BOOLEAN DEFAULT FALSE,
    assignments_completed INT DEFAULT 0,
    assignments_total INT DEFAULT 0,
    resources_viewed INT DEFAULT 0,
    resources_total INT DEFAULT 0,
    time_spent_minutes INT DEFAULT 0,
    last_accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lesson_progress (lesson_id, student_id),
    INDEX idx_lesson_progress_student (student_id),
    INDEX idx_lesson_progress_completion (completed_at)
);

-- Insert sample data for testing
INSERT IGNORE INTO lesson_notes (lesson_id, instructor_id, title, content, note_type) 
SELECT id, instructor_id, 'Lesson Overview', CONCAT('This is an overview for lesson: ', title), 'markdown' 
FROM lessons WHERE id <= 5 LIMIT 3;

INSERT IGNORE INTO lesson_resources (lesson_id, instructor_id, title, resource_type, file_path, is_downloadable)
SELECT id, instructor_id, 'Additional Reading Material', 'document', 'uploads/resources/sample.pdf', TRUE 
FROM lessons WHERE id <= 5 LIMIT 3;
