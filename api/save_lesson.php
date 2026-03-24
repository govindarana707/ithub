<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Course.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$courseId = intval($_POST['course_id']);
$lessonId = intval($_POST['lesson_id'] ?? 0);

// Verify course ownership
$course = new Course();
$courseData = $course->getCourseById($courseId);

if (!$courseData) {
    sendJSON(['success' => false, 'message' => 'Course not found']);
}

$role = getUserRole();
if ($role !== 'admin' && ($role === 'instructor' && (int)$courseData['instructor_id'] !== (int)$_SESSION['user_id'])) {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

// Get form data
$title = sanitize($_POST['title'] ?? '');
$contentType = sanitize($_POST['content_type'] ?? '');
$duration = intval($_POST['duration'] ?? 0);
$additionalNotes = sanitize($_POST['additional_notes'] ?? '');

// Validate required fields
if (empty($title) || empty($contentType) || $duration <= 0) {
    sendJSON(['success' => false, 'message' => 'Please fill in all required fields']);
}

$conn = connectDB();

// Handle content based on type
$content = '';
$videoUrl = '';

switch ($contentType) {
    case 'video':
        // Handle video file upload
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $videoUpload = uploadFile($_FILES['video_file'], ['mp4', 'avi', 'mov', 'wmv', 'webm'], 'videos');
            if (!$videoUpload['success']) {
                sendJSON(['success' => false, 'message' => 'Video upload failed: ' . $videoUpload['message']]);
            }
            $videoUrl = 'uploads/' . $videoUpload['filename'];
        } elseif (!empty($_POST['video_url'])) {
            $videoUrl = sanitize($_POST['video_url']);
        } elseif ($lessonId > 0) {
            // Keep existing video URL when updating without new upload
            $stmt = $conn->prepare("SELECT video_url FROM lessons WHERE id = ?");
            $stmt->bind_param("i", $lessonId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $videoUrl = $existing['video_url'] ?? '';
        } else {
            sendJSON(['success' => false, 'message' => 'Please upload a video file or provide a video URL']);
        }
        
        // Handle video thumbnail upload
        $thumbnailPath = null;
        if (isset($_FILES['video_thumbnail']) && $_FILES['video_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $thumbUpload = uploadFile($_FILES['video_thumbnail'], ['jpg', 'jpeg', 'png', 'gif'], 'thumbnails');
            if ($thumbUpload['success']) {
                $thumbnailPath = 'uploads/thumbnails/' . $thumbUpload['filename'];
            }
        }
        
        $content = "Video lesson" . ($thumbnailPath ? ". Thumbnail: " . $thumbnailPath : "");
        break;
        
    case 'text':
        $textContent = sanitize($_POST['text_content'] ?? '');
        if (empty($textContent)) {
            sendJSON(['success' => false, 'message' => 'Please enter text content']);
        }
        $content = $textContent;
        break;
        
    case 'quiz':
        $questions = $_POST['quiz_question'] ?? [];
        $optionsA = $_POST['quiz_option_a'] ?? [];
        $optionsB = $_POST['quiz_option_b'] ?? [];
        $optionsC = $_POST['quiz_option_c'] ?? [];
        $optionsD = $_POST['quiz_option_d'] ?? [];
        $correctAnswers = $_POST['quiz_correct_answer'] ?? [];
        
        $quizData = [];
        foreach ($questions as $index => $question) {
            if (!empty(trim($question)) && !empty($correctAnswers[$index])) {
                $quizData[] = [
                    'question' => sanitize($question),
                    'options' => [
                        'a' => sanitize($optionsA[$index] ?? ''),
                        'b' => sanitize($optionsB[$index] ?? ''),
                        'c' => sanitize($optionsC[$index] ?? ''),
                        'd' => sanitize($optionsD[$index] ?? '')
                    ],
                    'correct_answer' => sanitize($correctAnswers[$index])
                ];
            }
        }
        
        if (empty($quizData)) {
            sendJSON(['success' => false, 'message' => 'Please add at least one complete quiz question']);
        }
        
        $content = json_encode($quizData);
        break;
        
    case 'assignment':
        $description = sanitize($_POST['assignment_description'] ?? '');
        $dueDate = sanitize($_POST['assignment_due_date'] ?? '');
        
        if (empty($description)) {
            sendJSON(['success' => false, 'message' => 'Please enter assignment description']);
        }
        
        $assignmentFiles = [];
        if (isset($_FILES['assignment_files'])) {
            foreach ($_FILES['assignment_files']['name'] as $key => $name) {
                if ($_FILES['assignment_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $name,
                        'type' => $_FILES['assignment_files']['type'][$key],
                        'tmp_name' => $_FILES['assignment_files']['tmp_name'][$key],
                        'error' => $_FILES['assignment_files']['error'][$key],
                        'size' => $_FILES['assignment_files']['size'][$key]
                    ];
                    
                    $upload = uploadFile($file, ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'], 'assignment_files');
                    if ($upload['success']) {
                        $assignmentFiles[] = $upload['filename'];
                    }
                }
            }
        }
        
        $content = json_encode([
            'description' => $description,
            'due_date' => $dueDate,
            'files' => $assignmentFiles
        ]);
        break;
        
    default:
        sendJSON(['success' => false, 'message' => 'Invalid content type']);
}

// Append additional notes to content if provided
if (!empty($additionalNotes)) {
    if ($contentType === 'quiz' || $contentType === 'assignment') {
        // For JSON content, add notes as additional field
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $decoded['additional_notes'] = $additionalNotes;
            $content = json_encode($decoded);
        }
    } else {
        // For text/video content, append notes
        $content .= "\n\n--- Additional Notes ---\n" . $additionalNotes;
    }
}

// Get next sort order for the lesson
$stmt = $conn->prepare("SELECT MAX(lesson_order) as max_order FROM lessons WHERE course_id = ?");
$stmt->bind_param("i", $courseId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$lessonOrder = ($result['max_order'] ?? 0) + 1;

// Save or update lesson
if ($lessonId > 0) {
    // Update existing lesson
    $stmt = $conn->prepare("
        UPDATE lessons SET 
            title = ?, content = ?, lesson_type = ?, duration_minutes = ?, 
            video_url = ?, updated_at = NOW()
        WHERE id = ? AND course_id = ?
    ");
    $stmt->bind_param("sssisii", $title, $content, $contentType, $duration, $videoUrl, $lessonId, $courseId);
    
    if ($stmt->execute()) {
        sendJSON(['success' => true, 'message' => 'Lesson updated successfully', 'lesson_id' => $lessonId]);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to update lesson: ' . $stmt->error]);
    }
} else {
    // Create new lesson
    $stmt = $conn->prepare("
        INSERT INTO lessons (course_id, title, content, lesson_type, duration_minutes, video_url, lesson_order, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("isssisi", $courseId, $title, $content, $contentType, $duration, $videoUrl, $lessonOrder);
    
    if ($stmt->execute()) {
        $newLessonId = $conn->insert_id;
        sendJSON(['success' => true, 'message' => 'Lesson created successfully', 'lesson_id' => $newLessonId]);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to create lesson: ' . $stmt->error]);
    }
}
?>
