-- Create wishlist table for student course wishlist functionality
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`student_id`, `course_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `fk_wishlist_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance on courses table
ALTER TABLE `courses` ADD INDEX IF NOT EXISTS `idx_status_published` (`status`);
ALTER TABLE `courses` ADD INDEX IF NOT EXISTS `idx_category_status` (`category_id`, `status`);
ALTER TABLE `courses` ADD INDEX IF NOT EXISTS `idx_difficulty_status` (`difficulty_level`, `status`);
ALTER TABLE `courses` ADD INDEX IF NOT EXISTS `idx_price_status` (`price`, `status`);

-- Add indexes for enrollments table
ALTER TABLE `enrollments` ADD INDEX IF NOT EXISTS `idx_student_course` (`student_id`, `course_id`);
ALTER TABLE `enrollments` ADD INDEX IF NOT EXISTS `idx_course_status` (`course_id`, `status`);

-- Add indexes for course_reviews table if it exists
ALTER TABLE `course_reviews` ADD INDEX IF NOT EXISTS `idx_course_rating` (`course_id`, `rating`);

-- Add indexes for lessons table
ALTER TABLE `lessons` ADD INDEX IF NOT EXISTS `idx_course_order` (`course_id`, `lesson_order`);

-- Update courses table with missing columns if they don't exist
ALTER TABLE `courses` 
ADD COLUMN IF NOT EXISTS `thumbnail` varchar(255) DEFAULT NULL AFTER `difficulty_level`,
ADD COLUMN IF NOT EXISTS `enrollment_count` int(11) DEFAULT 0 AFTER `thumbnail`,
ADD COLUMN IF NOT EXISTS `avg_rating` decimal(3,2) DEFAULT 0.00 AFTER `enrollment_count`,
ADD COLUMN IF NOT EXISTS `lesson_count` int(11) DEFAULT 0 AFTER `avg_rating`;
