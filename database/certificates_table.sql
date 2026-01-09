-- Add certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(50) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_certificate_code (certificate_code),
    INDEX idx_student_course (student_id, course_id)
);
