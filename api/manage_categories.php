<?php
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_category':
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        
        $conn = connectDB();
        $stmt = $conn->prepare("INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $categoryId = $conn->insert_id;
            logActivity($_SESSION['user_id'], 'category_created', "Created category: $name");
            sendJSON(['success' => true, 'category_id' => $categoryId]);
        } else {
            sendJSON(['success' => false, 'message' => 'Failed to create category']);
        }
        $stmt->close();
        $conn->close();
        break;
        
    case 'update_category':
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        
        $conn = connectDB();
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $categoryId);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'category_updated', "Updated category ID: $categoryId");
            sendJSON(['success' => true, 'message' => 'Category updated successfully']);
        } else {
            sendJSON(['success' => false, 'message' => 'Failed to update category']);
        }
        $stmt->close();
        $conn->close();
        break;
        
    case 'delete_category':
        $categoryId = intval($_POST['category_id']);
        
        $conn = connectDB();
        // Check if category is being used
        $stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM courses WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $courseCount = $stmt->get_result()->fetch_assoc()['course_count'];
        $stmt->close();
        
        if ($courseCount > 0) {
            sendJSON(['success' => false, 'message' => 'Cannot delete category with associated courses']);
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $categoryId);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'category_deleted', "Deleted category ID: $categoryId");
            sendJSON(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            sendJSON(['success' => false, 'message' => 'Failed to delete category']);
        }
        $stmt->close();
        $conn->close();
        break;
        
    default:
        sendJSON(['success' => false, 'message' => 'Invalid action']);
}
?>
