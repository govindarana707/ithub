<?php
// Clean output buffer to buffer to prevent HTML in JSON response
if (ob_get_level()) {
    ob_end_clean();
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin' && getUserRole() !== 'instructor') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = new Database();
$conn = $db->getConnection();

if ($method === 'GET') {
    $noteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
    
    // Fetch single note by ID
    if ($noteId > 0) {
        // Check permissions and get note
        if (getUserRole() === 'instructor') {
            // For instructors, check if they own the course that contains the lesson that contains the note
            $stmt = $conn->prepare("
                SELECT ln.*, c.instructor_id
                FROM lesson_notes ln
                JOIN lessons l ON ln.lesson_id = l.id
                JOIN courses_new c ON l.course_id = c.id
                WHERE ln.id = ?
            ");
            $stmt->bind_param('i', $noteId);
            $stmt->execute();
            $note = $stmt->get_result()->fetch_assoc();
            
            if (!$note) {
                sendJSON(['success' => false, 'message' => 'Note not found']);
            }
            
            if ($note['instructor_id'] != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
            
            // Remove instructor_id from response
            unset($note['instructor_id']);
        } else {
            // For admin, just fetch the note
            $stmt = $conn->prepare("SELECT id, lesson_id, title, content, note_type, file_path, created_at FROM lesson_notes WHERE id = ?");
            $stmt->bind_param('i', $noteId);
            $stmt->execute();
            $note = $stmt->get_result()->fetch_assoc();
            
            if (!$note) {
                sendJSON(['success' => false, 'message' => 'Note not found']);
            }
        }
        
        // Clean HTML entities
        array_walk_recursive($note, function(&$item) {
            if (is_string($item)) {
                $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML401);
                $item = strip_tags($item);
            }
        });
        
        sendJSON(['success' => true, 'note' => $note]);
    }
    // Fetch notes for a lesson
    else if ($lessonId > 0) {
        // Check instructor permissions
        if (getUserRole() === 'instructor') {
            $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
            $stmt->bind_param('i', $lessonId);
            $stmt->execute();
            $lesson = $stmt->get_result()->fetch_assoc();
            
            if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
        }

        $stmt = $conn->prepare("SELECT id, lesson_id, title, content, note_type, file_path, created_at FROM lesson_notes WHERE lesson_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Clean any HTML entities from the data
        array_walk_recursive($notes, function(&$item) {
            if (is_array($item)) {
                array_walk_recursive($item, function(&$value) {
                    if (is_string($value)) {
                        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML401);
                        $value = strip_tags($value);
                    }
                });
            } elseif (is_string($item)) {
                $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML401);
                $item = strip_tags($item);
            }
        });

        sendJSON(['success' => true, 'notes' => $notes]);
    }
    else {
        sendJSON(['success' => false, 'message' => 'Please provide either id or lesson_id parameter']);
    }
}

if ($method !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = $payload['action'] ?? '';

if ($action === 'save') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    $title = trim((string)($payload['title'] ?? 'Instructor Notes'));
    $content = trim((string)($payload['content'] ?? ''));

    if ($lessonId <= 0) {
        sendJSON(['success' => false, 'message' => 'Missing lesson_id']);
    }

    // Check instructor permissions
    if (getUserRole() === 'instructor') {
        $stmt = $conn->prepare("SELECT c.instructor_id FROM lessons l JOIN courses_new c ON l.course_id = c.id WHERE l.id = ?");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();
        
        if (!$lesson || $lesson['instructor_id'] != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    }

    // Check if notes already exist for this lesson
    $stmt = $conn->prepare("SELECT id FROM lesson_notes WHERE lesson_id = ?");
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $existingNotes = $stmt->get_result()->fetch_assoc();

    if ($existingNotes) {
        // Update existing notes
        $stmt = $conn->prepare("UPDATE lesson_notes SET title = ?, content = ?, note_type = 'markdown' WHERE lesson_id = ?");
        $stmt->bind_param('ssi', $title, $content, $lessonId);
    } else {
        // Insert new notes
        $instructorId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO lesson_notes (lesson_id, instructor_id, title, content, note_type, created_at) VALUES (?, ?, ?, ?, 'markdown', NOW())");
        $stmt->bind_param('iiss', $lessonId, $instructorId, $title, $content);
    }

    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to save notes']);
    }

    sendJSON(['success' => true]);
}

sendJSON(['success' => false, 'message' => 'Unknown action']);
