-- Enhanced Progress Tracking System for IT Hub
-- This script creates and updates the progress tracking system

USE it_hub_clean;

-- Create enhanced course_progress table if not exists
CREATE TABLE IF NOT EXISTS course_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    lesson_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completion_time TIMESTAMP NULL DEFAULT NULL,
    time_spent_minutes INT DEFAULT 0,
    last_position_seconds INT DEFAULT 0, -- For video progress
    watch_percentage DECIMAL(5,2) DEFAULT 0.00, -- For video completion
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (student_id, course_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_course_progress_student (student_id),
    INDEX idx_course_progress_course (course_id),
    INDEX idx_course_progress_completion (is_completed)
);

-- Create lesson_progress table for detailed tracking
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
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lesson_progress (lesson_id, student_id),
    INDEX idx_lesson_progress_student (student_id),
    INDEX idx_lesson_progress_completion (completed_at)
);

-- Update enrollments table to better track progress
ALTER TABLE enrollments 
ADD COLUMN IF NOT EXISTS last_accessed_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS total_time_spent_minutes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS lessons_completed INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS lessons_total INT DEFAULT 0;

-- Create progress_summary table for aggregated data
CREATE TABLE IF NOT EXISTS progress_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    total_lessons INT DEFAULT 0,
    completed_lessons INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    total_time_spent_minutes INT DEFAULT 0,
    average_time_per_lesson INT DEFAULT 0,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estimated_completion_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_summary (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    INDEX idx_progress_summary_student (student_id),
    INDEX idx_progress_summary_completion (completion_percentage)
);

-- Create study_sessions table for time tracking
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    lesson_id INT NOT NULL,
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP NULL,
    duration_minutes INT DEFAULT 0,
    activity_type ENUM('video', 'reading', 'quiz', 'assignment') DEFAULT 'reading',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses_new(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_study_sessions_student (student_id),
    INDEX idx_study_sessions_date (session_start)
);

-- Create triggers to automatically update progress summaries
DELIMITER //

CREATE TRIGGER IF NOT EXISTS update_progress_summary_after_lesson_completion
AFTER UPDATE ON course_progress
FOR EACH ROW
BEGIN
    IF NEW.is_completed = TRUE AND (OLD.is_completed = FALSE OR OLD.is_completed IS NULL) THEN
        INSERT INTO progress_summary (student_id, course_id, completed_lessons, completion_percentage)
        VALUES (NEW.student_id, NEW.course_id, 1, 0.00)
        ON DUPLICATE KEY UPDATE
            completed_lessons = completed_lessons + 1,
            completion_percentage = (completed_lessons * 100.0 / total_lessons),
            last_activity_at = CURRENT_TIMESTAMP;
    END IF;
END//

CREATE TRIGGER IF NOT EXISTS update_enrollment_progress
AFTER UPDATE ON progress_summary
FOR EACH ROW
BEGIN
    UPDATE enrollments SET
        progress_percentage = NEW.completion_percentage,
        last_accessed_at = NEW.last_activity_at,
        lessons_completed = NEW.completed_lessons
    WHERE student_id = NEW.student_id AND course_id = NEW.course_id;
END//

DELIMITER ;

-- Create stored procedure to calculate course progress
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS CalculateCourseProgress(
    IN p_student_id INT,
    IN p_course_id INT
)
BEGIN
    DECLARE total_lessons_count INT DEFAULT 0;
    DECLARE completed_lessons_count INT DEFAULT 0;
    DECLARE completion_pct DECIMAL(5,2) DEFAULT 0.00;
    
    -- Get total lessons for the course
    SELECT COUNT(*) INTO total_lessons_count
    FROM lessons
    WHERE course_id = p_course_id;
    
    -- Get completed lessons for the student
    SELECT COUNT(*) INTO completed_lessons_count
    FROM course_progress
    WHERE student_id = p_student_id AND course_id = p_course_id AND is_completed = TRUE;
    
    -- Calculate completion percentage
    IF total_lessons_count > 0 THEN
        SET completion_pct = (completed_lessons_count * 100.0) / total_lessons_count;
    END IF;
    
    -- Update progress summary
    INSERT INTO progress_summary (student_id, course_id, total_lessons, completed_lessons, completion_percentage)
    VALUES (p_student_id, p_course_id, total_lessons_count, completed_lessons_count, completion_pct)
    ON DUPLICATE KEY UPDATE
        total_lessons = total_lessons_count,
        completed_lessons = completed_lessons_count,
        completion_percentage = completion_pct,
        last_activity_at = CURRENT_TIMESTAMP;
        
    -- Update enrollment
    UPDATE enrollments SET
        progress_percentage = completion_pct,
        lessons_completed = completed_lessons_count,
        lessons_total = total_lessons_count,
        last_accessed_at = CURRENT_TIMESTAMP
    WHERE student_id = p_student_id AND course_id = p_course_id;
    
    SELECT completion_pct as progress_percentage, completed_lessons_count, total_lessons_count;
END//

DELIMITER ;

-- Create view for student progress overview
CREATE OR REPLACE VIEW student_progress_overview AS
SELECT 
    u.id as student_id,
    u.username,
    u.full_name,
    c.id as course_id,
    c.title as course_title,
    e.enrolled_at,
    e.progress_percentage,
    e.lessons_completed,
    e.lessons_total,
    ps.last_activity_at,
    ps.total_time_spent_minutes,
    CASE 
        WHEN e.progress_percentage >= 100 THEN 'completed'
        WHEN e.progress_percentage > 0 THEN 'in_progress'
        ELSE 'not_started'
    END as status
FROM users_new u
JOIN enrollments e ON u.id = e.student_id
JOIN courses_new c ON e.course_id = c.id
LEFT JOIN progress_summary ps ON e.student_id = ps.student_id AND e.course_id = ps.course_id
WHERE u.role = 'student';

-- Create view for detailed lesson progress
CREATE OR REPLACE VIEW lesson_progress_detail AS
SELECT 
    cp.student_id,
    cp.course_id,
    cp.lesson_id,
    l.title as lesson_title,
    l.lesson_type,
    l.duration_minutes as lesson_duration,
    cp.is_completed,
    cp.completion_time,
    cp.time_spent_minutes,
    cp.watch_percentage,
    lp.video_completion_percentage,
    lp.notes_viewed,
    lp.assignments_completed,
    lp.resources_viewed,
    cp.updated_at as last_updated
FROM course_progress cp
JOIN lessons l ON cp.lesson_id = l.id
LEFT JOIN lesson_progress lp ON cp.lesson_id = lp.lesson_id AND cp.student_id = lp.student_id;
