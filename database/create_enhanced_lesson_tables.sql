-- Enhanced Course Builder Tables
-- Run this script to create the necessary tables for the enhanced course builder

USE it_hub_clean;

-- Create lesson_assignments table if not exists
CREATE TABLE IF NOT EXISTS `lesson_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assignment_type` enum('assignment','quiz','project','discussion','exam') DEFAULT 'assignment',
  `due_date` datetime DEFAULT NULL,
  `points_possible` int(11) DEFAULT 100,
  `instructions` longtext DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lesson_assignments_lesson` (`lesson_id`),
  KEY `idx_lesson_assignments_instructor` (`instructor_id`),
  KEY `idx_lesson_assignments_type` (`assignment_type`),
  KEY `idx_lesson_assignments_published` (`is_published`),
  CONSTRAINT `fk_lesson_assignments_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_assignments_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create lesson_notes table if not exists
CREATE TABLE IF NOT EXISTS `lesson_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `instructor_notes` longtext DEFAULT NULL,
  `study_materials` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_lesson_notes_lesson` (`lesson_id`),
  KEY `idx_lesson_notes_instructor` (`instructor_id`),
  CONSTRAINT `fk_lesson_notes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_notes_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create lesson_resources table if not exists (enhanced version)
CREATE TABLE IF NOT EXISTS `lesson_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `resource_type` enum('document','presentation','video','audio','image','link','other') DEFAULT 'document',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `external_url` varchar(1000) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_downloadable` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lesson_resources_lesson` (`lesson_id`),
  KEY `idx_lesson_resources_instructor` (`instructor_id`),
  KEY `idx_lesson_resources_type` (`resource_type`),
  CONSTRAINT `fk_lesson_resources_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_resources_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create uploads directories if they don't exist
-- Note: This would be done via PHP, but documenting here for completeness
-- uploads/resources/
-- uploads/videos/

-- Show table creation status
SELECT 'Tables created successfully' as status;
