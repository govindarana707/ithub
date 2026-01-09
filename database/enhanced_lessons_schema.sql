-- Enhanced Lessons Table with Video Upload and Google Drive Support
-- This script adds new columns to the existing lessons table

USE it_hub;

-- Add new columns to lessons table for advanced video support
ALTER TABLE lessons 
ADD COLUMN video_file_path VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded video file',
ADD COLUMN google_drive_url VARCHAR(1000) DEFAULT NULL COMMENT 'Google Drive video URL',
ADD COLUMN video_source ENUM('upload', 'google_drive', 'external_url', 'none') DEFAULT 'none' COMMENT 'Source of video content',
ADD COLUMN video_file_size BIGINT DEFAULT NULL COMMENT 'Size of uploaded video file in bytes',
ADD COLUMN video_duration VARCHAR(20) DEFAULT NULL COMMENT 'Duration of video in HH:MM:SS format',
ADD COLUMN video_thumbnail VARCHAR(500) DEFAULT NULL COMMENT 'Path to video thumbnail image',
ADD COLUMN video_processing_status ENUM('pending', 'processing', 'completed', 'failed', 'none') DEFAULT 'none' COMMENT 'Video processing status',
ADD COLUMN video_mime_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME type of uploaded video',
ADD COLUMN video_quality ENUM('360p', '480p', '720p', '1080p', '4k') DEFAULT '720p' COMMENT 'Video quality preference',
ADD COLUMN is_downloadable BOOLEAN DEFAULT FALSE COMMENT 'Whether video can be downloaded by students',
ADD COLUMN auto_generate_thumbnail BOOLEAN DEFAULT TRUE COMMENT 'Auto-generate thumbnail from video';

-- Create video processing queue table
CREATE TABLE IF NOT EXISTS video_processing_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    video_file_path VARCHAR(500) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    processing_started_at TIMESTAMP NULL DEFAULT NULL,
    processing_completed_at TIMESTAMP NULL DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    thumbnail_generated BOOLEAN DEFAULT FALSE,
    duration_extracted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Create video analytics table
CREATE TABLE IF NOT EXISTS video_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    watch_time_seconds INT DEFAULT 0,
    total_video_duration INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_watched_position INT DEFAULT 0,
    watch_count INT DEFAULT 0,
    completed_watching BOOLEAN DEFAULT FALSE,
    first_watched_at TIMESTAMP NULL DEFAULT NULL,
    last_watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lesson_student (lesson_id, student_id)
);

-- Update existing lessons to have default video source
UPDATE lessons SET video_source = 'none' WHERE video_source IS NULL;

-- Add indexes for better performance
CREATE INDEX idx_lessons_video_source ON lessons(video_source);
CREATE INDEX idx_lessons_video_processing ON lessons(video_processing_status);
CREATE INDEX idx_video_analytics_lesson ON video_analytics(lesson_id);
CREATE INDEX idx_video_analytics_student ON video_analytics(student_id);
CREATE INDEX idx_video_queue_status ON video_processing_queue(status);
