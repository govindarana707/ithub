-- Update quiz_answers table to match expected structure
-- Execute this script to add missing columns to the existing table

ALTER TABLE `quiz_answers` 
ADD COLUMN `attempt_id` int(11) NOT NULL AFTER `id`,
ADD COLUMN `selected_option_id` int(11) DEFAULT NULL AFTER `question_id`,
ADD COLUMN `points_earned` decimal(5,2) DEFAULT NULL AFTER `is_correct`,
ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `points_earned`;

-- Add foreign key constraints
ALTER TABLE `quiz_answers` 
ADD CONSTRAINT `fk_quiz_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_quiz_answers_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_quiz_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `quiz_options`(`id`) ON DELETE SET NULL;

-- Drop the old sort_order column if it exists and is no longer needed
ALTER TABLE `quiz_answers` DROP COLUMN `sort_order`;
