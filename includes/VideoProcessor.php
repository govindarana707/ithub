<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Database.php';

/**
 * Video Processing Utility
 * Handles video validation, thumbnail generation, and metadata extraction
 */
class VideoProcessor {
    private $db;
    private $conn;
    private $uploadDir;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->uploadDir = dirname(__DIR__) . '/uploads/videos/';
        $this->thumbnailDir = dirname(__DIR__) . '/uploads/thumbnails/';
        
        // Ensure directories exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0755, true);
        }
    }
    
    /**
     * Validate video file
     */
    public function validateVideo($file) {
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $maxSize = 500 * 1024 * 1024; // 500MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid video file type. Allowed: MP4, WebM, OGG'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Video file size must be less than 500MB'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Process uploaded video
     */
    public function processVideo($lessonId, $videoFilePath) {
        try {
            $fullPath = dirname(__DIR__) . '/' . $videoFilePath;
            
            if (!file_exists($fullPath)) {
                throw new Exception('Video file not found');
            }
            
            // Update processing status
            $this->updateProcessingStatus($lessonId, 'processing');
            
            // Get video duration
            $duration = $this->getVideoDuration($fullPath);
            
            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($fullPath, $lessonId);
            
            // Update lesson with video metadata
            $this->updateLessonMetadata($lessonId, $duration, $thumbnailPath);
            
            // Mark as completed
            $this->updateProcessingStatus($lessonId, 'completed');
            
            return ['success' => true, 'duration' => $duration, 'thumbnail' => $thumbnailPath];
            
        } catch (Exception $e) {
            $this->updateProcessingStatus($lessonId, 'failed', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get video duration using FFprobe
     */
    private function getVideoDuration($videoPath) {
        // Try to use FFprobe if available
        $ffprobePath = $this->findFFprobe();
        
        if ($ffprobePath) {
            $cmd = escapeshellcmd($ffprobePath) . ' -v quiet -show_entries format=duration -of csv=p=0 ' . escapeshellarg($videoPath);
            $output = shell_exec($cmd);
            
            if ($output && is_numeric($output)) {
                $duration = floatval($output);
                return $this->formatDuration($duration);
            }
        }
        
        // Fallback: estimate based on file size (rough estimate)
        $fileSize = filesize($videoPath);
        $estimatedDuration = $fileSize / (1024 * 1024); // Rough estimate: 1MB = 1 second
        return $this->formatDuration($estimatedDuration);
    }
    
    /**
     * Generate video thumbnail
     */
    private function generateThumbnail($videoPath, $lessonId) {
        $thumbnailPath = 'uploads/thumbnails/thumb_' . $lessonId . '_' . time() . '.jpg';
        $fullThumbnailPath = dirname(__DIR__) . '/' . $thumbnailPath;
        
        // Try to use FFmpeg if available
        $ffmpegPath = $this->findFFmpeg();
        
        if ($ffmpegPath) {
            // Extract thumbnail at 10% of video duration
            $cmd = escapeshellcmd($ffmpegPath) . ' -i ' . escapeshellarg($videoPath) . ' -ss 00:00:01 -vframes 1 -vf "scale=320:240" ' . escapeshellarg($fullThumbnailPath) . ' 2>&1';
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($fullThumbnailPath)) {
                return $thumbnailPath;
            }
        }
        
        // Fallback: create a default thumbnail
        $this->createDefaultThumbnail($fullThumbnailPath);
        return $thumbnailPath;
    }
    
    /**
     * Create default thumbnail
     */
    private function createDefaultThumbnail($thumbnailPath) {
        // Create a simple placeholder image using GD
        $width = 320;
        $height = 240;
        
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 52, 152, 219); // Blue background
        $textColor = imagecolorallocate($image, 255, 255, 255); // White text
        
        imagefill($image, 0, 0, $bgColor);
        
        // Add "Video" text
        $font = 5;
        $text = 'Video';
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Add play icon
        $playSize = 40;
        $playX = ($width - $playSize) / 2;
        $playY = ($height - $playSize) / 2 + 30;
        
        // Simple triangle for play button
        $points = [
            $playX, $playY,
            $playX + $playSize, $playY + $playSize / 2,
            $playX, $playY + $playSize
        ];
        imagefilledpolygon($image, $points, 3, $textColor);
        
        imagejpeg($image, $thumbnailPath, 90);
        imagedestroy($image);
    }
    
    /**
     * Find FFmpeg executable
     */
    private function findFFmpeg() {
        $paths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg' // Try system PATH
        ];
        
        foreach ($paths as $path) {
            if (is_executable($path) || shell_exec("which $path")) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Find FFprobe executable
     */
    private function findFFprobe() {
        $paths = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            'ffprobe' // Try system PATH
        ];
        
        foreach ($paths as $path) {
            if (is_executable($path) || shell_exec("which $path")) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Format duration in HH:MM:SS
     */
    private function formatDuration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = floor($seconds % 60);
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    
    /**
     * Update processing status
     */
    private function updateProcessingStatus($lessonId, $status, $errorMessage = null) {
        $stmt = $this->conn->prepare("UPDATE lessons SET video_processing_status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $lessonId);
        $stmt->execute();
        
        // Update processing queue
        if ($status === 'processing') {
            $stmt = $this->conn->prepare("UPDATE video_processing_queue SET status = ?, processing_started_at = NOW() WHERE lesson_id = ?");
            $stmt->bind_param('si', $status, $lessonId);
        } elseif ($status === 'completed') {
            $stmt = $this->conn->prepare("UPDATE video_processing_queue SET status = ?, processing_completed_at = NOW() WHERE lesson_id = ?");
            $stmt->bind_param('si', $status, $lessonId);
        } elseif ($status === 'failed') {
            $stmt = $this->conn->prepare("UPDATE video_processing_queue SET status = ?, error_message = ?, processing_completed_at = NOW() WHERE lesson_id = ?");
            $stmt->bind_param('ssi', $status, $errorMessage, $lessonId);
        }
        $stmt->execute();
    }
    
    /**
     * Update lesson metadata
     */
    private function updateLessonMetadata($lessonId, $duration, $thumbnailPath) {
        $stmt = $this->conn->prepare("UPDATE lessons SET video_duration = ?, video_thumbnail = ? WHERE id = ?");
        $stmt->bind_param('ssi', $duration, $thumbnailPath, $lessonId);
        $stmt->execute();
    }
    
    /**
     * Process video queue (run via cron job)
     */
    public function processQueue() {
        $stmt = $this->conn->prepare("SELECT * FROM video_processing_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 5");
        $stmt->execute();
        $queue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($queue as $item) {
            $this->processVideo($item['lesson_id'], $item['video_file_path']);
        }
        
        return count($queue);
    }
    
    /**
     * Get Google Drive embed URL
     */
    public function getGoogleDriveEmbedUrl($url) {
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileId = $matches[1];
            return "https://drive.google.com/file/d/{$fileId}/preview";
        }
        return null;
    }
    
    /**
     * Validate Google Drive URL
     */
    public function validateGoogleDriveUrl($url) {
        return !empty($url) && strpos($url, 'drive.google.com') !== false && preg_match('/\/file\/d\/[a-zA-Z0-9_-]+/', $url);
    }
}

// CLI handler for processing queue
if (php_sapi_name() === 'cli') {
    $processor = new VideoProcessor();
    $processed = $processor->processQueue();
    echo "Processed {$processed} videos from queue.\n";
}
?>
