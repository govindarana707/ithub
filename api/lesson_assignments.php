<?php
// Clean output buffer to prevent HTML in JSON response
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
    $assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
    
    // Fetch single assignment by ID
    if ($assignmentId > 0) {
        // Check permissions and get assignment
        if (getUserRole() === 'instructor') {
            // For instructors, check if they own the course that contains the lesson that contains the assignment
            $stmt = $conn->prepare("
                SELECT la.*, c.instructor_id
                FROM lesson_assignments la
                JOIN lessons l ON la.lesson_id = l.id
                JOIN courses_new c ON l.course_id = c.id
                WHERE la.id = ?
            ");
            $stmt->bind_param('i', $assignmentId);
            $stmt->execute();
            $assignment = $stmt->get_result()->fetch_assoc();
            
            if (!$assignment) {
                sendJSON(['success' => false, 'message' => 'Assignment not found']);
            }
            
            if ($assignment['instructor_id'] != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'message' => 'Access denied']);
            }
            
            // Remove instructor_id from response
            unset($assignment['instructor_id']);
        } else {
            // For admin, just fetch the assignment
            $stmt = $conn->prepare("SELECT * FROM lesson_assignments WHERE id = ?");
            $stmt->bind_param('i', $assignmentId);
            $stmt->execute();
            $assignment = $stmt->get_result()->fetch_assoc();
            
            if (!$assignment) {
                sendJSON(['success' => false, 'message' => 'Assignment not found']);
            }
        }
        
        // Clean HTML entities
        array_walk_recursive($assignment, function(&$item) {
            if (is_string($item)) {
                $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML401);
                $item = strip_tags($item);
            }
        });
        
        sendJSON(['success' => true, 'assignment' => $assignment]);
    }
    // Fetch all assignments for a lesson
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

        $stmt = $conn->prepare("SELECT id, lesson_id, title, description, assignment_type, due_date, max_points, instructions, is_published, created_at FROM lesson_assignments WHERE lesson_id = ? ORDER BY id ASC");
        $stmt->bind_param('i', $lessonId);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Clean any HTML entities from the data
        array_walk_recursive($assignments, function(&$item) {
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

        sendJSON(['success' => true, 'assignments' => $assignments]);
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

if ($action === 'create') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    $title = trim((string)($payload['title'] ?? ''));
    $description = trim((string)($payload['description'] ?? ''));
    $assignmentType = (string)($payload['assignment_type'] ?? 'text_submission');
    $dueDate = trim((string)($payload['due_date'] ?? ''));
    $maxPoints = (int)($payload['max_points'] ?? 100);
    $instructions = trim((string)($payload['instructions'] ?? ''));
    $isPublished = (int)($payload['is_published'] ?? 0);

    if ($lessonId <= 0 || $title === '') {
        sendJSON(['success' => false, 'message' => 'Missing required fields']);
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

    $instructorId = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO lesson_assignments (lesson_id, instructor_id, title, description, assignment_type, due_date, max_points, instructions, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('iissssii', $lessonId, $instructorId, $title, $description, $assignmentType, $dueDate, $maxPoints, $instructions, $isPublished);

    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to create assignment']);
    }

    $assignmentId = $conn->insert_id;
    sendJSON(['success' => true, 'assignment_id' => $assignmentId]);
}

if ($action === 'update') {
    $assignmentId = (int)($payload['assignment_id'] ?? 0);
    if ($assignmentId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid assignment_id']);
    }

    // Check permissions
    $stmt = $conn->prepare("SELECT la.instructor_id FROM lesson_assignments la WHERE la.id = ?");
    $stmt->bind_param('i', $assignmentId);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    
    if (!$assignment) {
        sendJSON(['success' => false, 'message' => 'Assignment not found']);
    }

    if (getUserRole() === 'instructor' && $assignment['instructor_id'] != $_SESSION['user_id']) {
        sendJSON(['success' => false, 'message' => 'Access denied']);
    }

    $title = trim((string)($payload['title'] ?? ''));
    $description = trim((string)($payload['description'] ?? ''));
    $assignmentType = (string)($payload['assignment_type'] ?? 'assignment');
    $dueDate = trim((string)($payload['due_date'] ?? ''));
    $pointsPossible = (int)($payload['points_possible'] ?? 100);
    $instructions = trim((string)($payload['instructions'] ?? ''));
    $isPublished = (int)($payload['is_published'] ?? 0);
    $sortOrder = (int)($payload['sort_order'] ?? 0);

    if ($title === '') {
        sendJSON(['success' => false, 'message' => 'Title is required']);
    }

    $stmt = $conn->prepare("UPDATE lesson_assignments SET title = ?, description = ?, assignment_type = ?, due_date = ?, points_possible = ?, instructions = ?, is_published = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssssisiii', $title, $description, $assignmentType, $dueDate, $pointsPossible, $instructions, $isPublished, $sortOrder, $assignmentId);

    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to update assignment']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'delete') {
    $assignmentId = (int)($payload['assignment_id'] ?? 0);
    if ($assignmentId <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid assignment_id']);
    }

    // Check permissions
    $stmt = $conn->prepare("SELECT la.instructor_id FROM lesson_assignments la WHERE la.id = ?");
    $stmt->bind_param('i', $assignmentId);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    
    if (!$assignment) {
        sendJSON(['success' => false, 'message' => 'Assignment not found']);
    }

    if (getUserRole() === 'instructor' && $assignment['instructor_id'] != $_SESSION['user_id']) {
        sendJSON(['success' => false, 'message' => 'Access denied']);
    }

    $stmt = $conn->prepare("DELETE FROM lesson_assignments WHERE id = ?");
    $stmt->bind_param('i', $assignmentId);
    
    if (!$stmt->execute()) {
        sendJSON(['success' => false, 'message' => 'Failed to delete assignment']);
    }

    sendJSON(['success' => true]);
}

if ($action === 'reorder') {
    $lessonId = (int)($payload['lesson_id'] ?? 0);
    $order = $payload['order'] ?? [];

    if ($lessonId <= 0 || !is_array($order)) {
        sendJSON(['success' => false, 'message' => 'Invalid payload']);
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

    $stmt = $conn->prepare("UPDATE lesson_assignments SET sort_order = ? WHERE id = ? AND lesson_id = ?");

    $pos = 1;
    foreach ($order as $assignmentId) {
        $assignmentId = (int)$assignmentId;
        if ($assignmentId <= 0) {
            continue;
        }
        $stmt->bind_param('iii', $pos, $assignmentId, $lessonId);
        $stmt->execute();
        $pos++;
    }

    sendJSON(['success' => true]);
}

sendJSON(['success' => false, 'message' => 'Unknown action']);
