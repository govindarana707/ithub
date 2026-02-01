-- Add missing columns to users table and insert sample data
USE it_hub_new;

-- Check if users table exists and add missing columns
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `email_verified` tinyint(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `verification_token` varchar(64) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `verification_expires_at` timestamp NULL DEFAULT NULL;

-- Insert sample categories (if categories table exists)
INSERT IGNORE INTO `categories` (`name`, `description`, `status`) VALUES
('Programming', 'Learn various programming languages and frameworks', 'active'),
('Web Development', 'Build modern websites and web applications', 'active'),
('Mobile Development', 'Create native and cross-platform mobile apps', 'active'),
('Data Science', 'Analyze data and build machine learning models', 'active'),
('Design', 'UI/UX design, graphic design, and creative skills', 'active'),
('Business', 'Business skills, marketing, and entrepreneurship', 'active');

-- Insert sample courses (if courses table exists)
INSERT IGNORE INTO `courses` (`title`, `description`, `instructor_id`, `price`, `status`) VALUES
('Complete Web Development Bootcamp', 'Learn HTML, CSS, JavaScript, React, Node.js and more', 2, 89.99, 'published'),
('Python for Data Science', 'Master Python programming for data analysis and machine learning', 2, 79.99, 'published'),
('UI/UX Design Fundamentals', 'Learn the principles of user interface and user experience design', 2, 69.99, 'published');

SELECT 'Database setup completed successfully!' as message;
