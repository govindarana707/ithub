<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/LessonContent.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$lessonContent = new LessonContent();

if ($method === 'GET') {
    switch ($action) {
        case 'get_lesson_content':
            $lessonId = (int)($_GET['lesson_id'] ?? 0);
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            // Check enrollment
            $conn = connectDB();
            $stmt = $conn->prepare("
                SELECT c.id FROM courses c
                JOIN lessons l ON c.id = l.course_id
                JOIN enrollments e ON c.id = e.course_id
                WHERE l.id = ? AND e.student_id = ?
            ");
            $stmt->bind_param('ii', $lessonId, $_SESSION['user_id']);
            $stmt->execute();
            
            if (!$stmt->get_result()->fetch_assoc()) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
            
            $content = $lessonContent->getLessonContent($lessonId, $_SESSION['user_id']);
            sendJSON(['success' => true, 'content' => $content]);
            break;
            
        case 'get_assignment_submission':
            $assignmentId = (int)($_GET['assignment_id'] ?? 0);
            $submission = $lessonContent->getAssignmentSubmission($assignmentId, $_SESSION['user_id']);
            sendJSON(['success' => true, 'submission' => $submission]);
            break;
    }
}

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    switch ($action) {
        case 'save_student_notes':
            $lessonId = (int)($payload['lesson_id'] ?? 0);
            $title = trim($payload['title'] ?? '');
            $content = trim($payload['content'] ?? '');
            
            if ($lessonId <= 0 || empty($title) || empty($content)) {
                sendJSON(['success' => false, 'message' => 'Missing required fields']);
            }
            
            if ($lessonContent->saveStudentNotes($lessonId, $_SESSION['user_id'], $title, $content)) {
                sendJSON(['success' => true, 'message' => 'Notes saved successfully']);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to save notes']);
            }
            break;
            
        case 'submit_assignment':
            $assignmentId = (int)($payload['assignment_id'] ?? 0);
            $lessonId = (int)($payload['lesson_id'] ?? 0);
            $submissionType = $payload['submission_type'] ?? '';
            
            if ($assignmentId <= 0 || empty($submissionType)) {
                sendJSON(['success' => false, 'message' => 'Missing required fields']);
            }
            
            $submissionData = [
                'submission_type' => $submissionType,
                'lesson_id' => $lessonId
            ];
            
            // Handle file upload
            if ($submissionType === 'file_upload' && isset($_FILES['submission_file'])) {
                $file = $_FILES['submission_file'];
                
                // Validate file
                $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip', 'jpg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($fileExtension, $allowedTypes)) {
                    sendJSON(['success' => false, 'message' => 'Invalid file type']);
                }
                
                if ($file['size'] > 10 * 1024 * 1024) { // 10MB
                    sendJSON(['success' => false, 'message' => 'File size too large']);
                }
                
                // Create upload directory
                $uploadDir = dirname(__DIR__) . '/uploads/assignments/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileName = 'assignment_' . uniqid() . '_' . time() . '.' . $fileExtension;
                $filePath = 'uploads/assignments/' . $fileName;
                $fullPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                    $submissionData['file_path'] = $filePath;
                    $submissionData['file_size'] = $file['size'];
                } else {
                    sendJSON(['success' => false, 'message' => 'Failed to upload file']);
                }
            } elseif ($submissionType === 'text_submission') {
                $submissionData['text_content'] = trim($payload['text_content'] ?? '');
                if (empty($submissionData['text_content'])) {
                    sendJSON(['success' => false, 'message' => 'Text content is required']);
                }
            }
            
            $result = $lessonContent->submitAssignment($assignmentId, $_SESSION['user_id'], $submissionData);
            sendJSON($result);
            break;
            
        case 'update_video_progress':
            $lessonId = (int)($payload['lesson_id'] ?? 0);
            $watchTime = (int)($payload['watch_time_seconds'] ?? 0);
            $completionPercentage = (float)($payload['completion_percentage'] ?? 0);
            
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            $updates = [
                'video_watch_time_seconds' => $watchTime,
                'video_completion_percentage' => $completionPercentage
            ];
            
            if ($completionPercentage >= 90) {
                $updates['completed_at'] = date('Y-m-d H:i:s');
            }
            
            if ($lessonContent->updateLessonProgress($lessonId, $_SESSION['user_id'], $updates)) {
                sendJSON(['success' => true]);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to update progress']);
            }
            break;
            
        case 'mark_resource_viewed':
            $lessonId = (int)($payload['lesson_id'] ?? 0);
            $totalResources = (int)($payload['total_resources'] ?? 0);
            
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            // Increment resources viewed count
            $conn = connectDB();
            $stmt = $conn->prepare("
                INSERT INTO lesson_progress (lesson_id, student_id, resources_viewed)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                resources_viewed = LEAST(resources_viewed + 1, ?),
                last_accessed_at = CURRENT_TIMESTAMP
            ");
            $stmt->bind_param('iii', $lessonId, $_SESSION['user_id'], $totalResources);
            
            if ($stmt->execute()) {
                sendJSON(['success' => true]);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to update progress']);
            }
            break;
    }
}

// Instructor-only actions
if (in_array(getUserRole(), ['instructor', 'admin'])) {
    if ($method === 'POST') {
        switch ($action) {
            case 'save_instructor_note':
                $lessonId = (int)($payload['lesson_id'] ?? 0);
                $title = trim($payload['title'] ?? '');
                $content = trim($payload['content'] ?? '');
                $noteType = $payload['note_type'] ?? 'markdown';
                
                if ($lessonId <= 0 || empty($title) || empty($content)) {
                    sendJSON(['success' => false, 'message' => 'Missing required fields']);
                }
                
                // Check instructor permissions
                $conn = connectDB();
                $stmt = $conn->prepare("
                    SELECT c.instructor_id FROM courses c
                    JOIN lessons l ON c.id = l.course_id
                    WHERE l.id = ?
                ");
                $stmt->bind_param('i', $lessonId);
                $stmt->execute();
                $course = $stmt->get_result()->fetch_assoc();
                
                if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
                    sendJSON(['success' => false, 'message' => 'Access denied']);
                }
                
                $noteId = $lessonContent->saveInstructorNote($lessonId, $_SESSION['user_id'], $title, $content, $noteType);
                if ($noteId) {
                    sendJSON(['success' => true, 'note_id' => $noteId]);
                } else {
                    sendJSON(['success' => false, 'message' => 'Failed to save note']);
                }
                break;
                
            case 'create_assignment':
                $required = ['lesson_id', 'title', 'description', 'assignment_type'];
                foreach ($required as $field) {
                    if (empty($payload[$field])) {
                        sendJSON(['success' => false, 'message' => "Missing required field: $field"]);
                    }
                }
                
                // Check instructor permissions
                $conn = connectDB();
                $stmt = $conn->prepare("
                    SELECT c.instructor_id FROM courses c
                    JOIN lessons l ON c.id = l.course_id
                    WHERE l.id = ?
                ");
                $stmt->bind_param('i', $payload['lesson_id']);
                $stmt->execute();
                $course = $stmt->get_result()->fetch_assoc();
                
                if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
                    sendJSON(['success' => false, 'message' => 'Access denied']);
                }
                
                $payload['instructor_id'] = $_SESSION['user_id'];
                $assignmentId = $lessonContent->createAssignment($payload);
                
                if ($assignmentId) {
                    sendJSON(['success' => true, 'assignment_id' => $assignmentId]);
                } else {
                    sendJSON(['success' => false, 'message' => 'Failed to create assignment']);
                }
                break;
                
            case 'add_resource':
                $required = ['lesson_id', 'title', 'resource_type'];
                foreach ($required as $field) {
                    if (empty($payload[$field])) {
                        sendJSON(['success' => false, 'message' => "Missing required field: $field"]);
                    }
                }
                
                // Check instructor permissions
                $conn = connectDB();
                $stmt = $conn->prepare("
                    SELECT c.instructor_id FROM courses c
                    JOIN lessons l ON c.id = l.course_id
                    WHERE l.id = ?
                ");
                $stmt->bind_param('i', $payload['lesson_id']);
                $stmt->execute();
                $course = $stmt->get_result()->fetch_assoc();
                
                if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
                    sendJSON(['success' => false, 'message' => 'Access denied']);
                }
                
                // Handle file upload for resources
                if (isset($_FILES['resource_file'])) {
                    $file = $_FILES['resource_file'];
                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    $uploadDir = dirname(__DIR__) . '/uploads/resources/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = 'resource_' . uniqid() . '_' . time() . '.' . $fileExtension;
                    $filePath = 'uploads/resources/' . $fileName;
                    $fullPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                        $payload['file_path'] = $filePath;
                        $payload['file_size'] = $file['size'];
                        $payload['mime_type'] = $file['type'];
                    }
                }
                
                $payload['instructor_id'] = $_SESSION['user_id'];
                
                if ($lessonContent->addResource($payload['lesson_id'], $_SESSION['user_id'], $payload)) {
                    sendJSON(['success' => true]);
                } else {
                    sendJSON(['success' => false, 'message' => 'Failed to add resource']);
                }
                break;
                
            case 'grade_submission':
                $submissionId = (int)($payload['submission_id'] ?? 0);
                $pointsEarned = (float)($payload['points_earned'] ?? 0);
                $feedback = trim($payload['feedback'] ?? '');
                
                if ($submissionId <= 0) {
                    sendJSON(['success' => false, 'message' => 'Invalid submission ID']);
                }
                
                if ($lessonContent->gradeSubmission($submissionId, $_SESSION['user_id'], $pointsEarned, $feedback)) {
                    sendJSON(['success' => true]);
                } else {
                    sendJSON(['success' => false, 'message' => 'Failed to grade submission']);
                }
                break;
        }
    }
}

sendJSON(['success' => false, 'message' => 'Invalid action']);
?>
