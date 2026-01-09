-- Create completed_lessons table
CREATE TABLE IF NOT EXISTS completed_lessons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_lesson (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
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
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
