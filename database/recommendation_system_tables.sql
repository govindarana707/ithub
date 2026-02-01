-- Recommendation System and Progress Tracking Database Schema

-- Table for logging user interactions
CREATE TABLE IF NOT EXISTS `user_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `interaction_type` enum('view','enroll','lesson_complete','quiz_attempt','discussion_post') NOT NULL,
  `interaction_value` decimal(5,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_course` (`user_id`, `course_id`),
  KEY `idx_interaction_type` (`interaction_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing user similarity scores
CREATE TABLE IF NOT EXISTS `user_similarity_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_1` int(11) NOT NULL,
  `user_id_2` int(11) NOT NULL,
  `similarity_score` decimal(5,4) NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_pair` (`user_id_1`, `user_id_2`),
  KEY `idx_user_1` (`user_id_1`),
  KEY `idx_user_2` (`user_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing course recommendations
CREATE TABLE IF NOT EXISTS `course_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `recommendation_score` decimal(5,4) NOT NULL,
  `recommendation_type` enum('knn','collaborative','content_based','cold_start') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_recommendations` (`user_id`, `recommendation_score` DESC),
  KEY `idx_course_recommendations` (`course_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for enhanced progress tracking with dynamic programming
CREATE TABLE IF NOT EXISTS `learning_progress_dp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `progress_state` json NOT NULL,
  `optimal_path_score` decimal(5,2) DEFAULT 0.00,
  `completion_probability` decimal(5,4) DEFAULT 0.0000,
  `estimated_completion_time` int(11) DEFAULT 0,
  `last_calculated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_course` (`user_id`, `course_id`),
  KEY `idx_user_progress` (`user_id`),
  KEY `idx_course_progress` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for learning path recommendations
CREATE TABLE IF NOT EXISTS `learning_paths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `path_name` varchar(255) NOT NULL,
  `course_sequence` json NOT NULL,
  `estimated_duration` int(11) DEFAULT 0,
  `difficulty_progression` enum('linear','adaptive','mixed') DEFAULT 'adaptive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_paths` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample interaction weights for different interaction types
INSERT INTO `user_interactions` (`user_id`, `course_id`, `interaction_type`, `interaction_value`) 
SELECT 1, c.id, 'view', 1.0 FROM `courses_new` c LIMIT 3
ON DUPLICATE KEY UPDATE interaction_value = interaction_value;

-- Create indexes for better performance
ALTER TABLE `user_interactions` ADD INDEX `idx_user_interaction_weight` (`user_id`, `interaction_type`, `interaction_value`);
ALTER TABLE `course_recommendations` ADD INDEX `idx_active_recommendations` (`user_id`, `expires_at`, `recommendation_score` DESC);
