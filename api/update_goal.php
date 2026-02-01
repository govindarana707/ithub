<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$studentId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handlePostRequest($conn, $studentId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handlePostRequest($conn, $studentId) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_goal':
            addGoal($conn, $studentId);
            break;
            
        case 'update_goal':
            updateGoal($conn, $studentId);
            break;
            
        case 'delete_goal':
            deleteGoal($conn, $studentId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function addGoal($conn, $studentId) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $targetDate = $_POST['target_date'] ?? '';
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Goal title is required']);
        return;
    }
    
    if (empty($targetDate) || !strtotime($targetDate)) {
        echo json_encode(['success' => false, 'message' => 'Valid target date is required']);
        return;
    }
    
    // Create student_goals table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS student_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            target_date DATE NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users_new(id) ON DELETE CASCADE
        )
    ";
    $conn->query($createTableSQL);
    
    $stmt = $conn->prepare("
        INSERT INTO student_goals (student_id, title, description, target_date)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isss", $studentId, $title, $description, $targetDate);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal added successfully',
            'goal_id' => $conn->insert_id()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding goal: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}

function updateGoal($conn, $studentId) {
    $goalId = (int)($_POST['goal_id'] ?? 0);
    $completed = isset($_POST['completed']) ? (bool)$_POST['completed'] : false;
    
    if ($goalId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid goal ID']);
        return;
    }
    
    // Verify goal belongs to student
    $stmt = $conn->prepare("
        SELECT id FROM student_goals 
        WHERE id = ? AND student_id = ?
    ");
    $stmt->bind_param("ii", $goalId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Goal not found']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("
        UPDATE student_goals 
        SET completed = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND student_id = ?
    ");
    
    $completedInt = $completed ? 1 : 0;
    $stmt->bind_param("iii", $completedInt, $goalId, $studentId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $completed ? 'Goal marked as completed!' : 'Goal marked as incomplete'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating goal: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}

function deleteGoal($conn, $studentId) {
    $goalId = (int)($_POST['goal_id'] ?? 0);
    
    if ($goalId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid goal ID']);
        return;
    }
    
    $stmt = $conn->prepare("
        DELETE FROM student_goals 
        WHERE id = ? AND student_id = ?
    ");
    
    $stmt->bind_param("ii", $goalId, $studentId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Goal deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Goal not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting goal: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}

$conn->close();
?>
