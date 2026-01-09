<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireAdmin();

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_category':
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $name, $description);
            
            if ($stmt->execute()) {
                $categoryId = $conn->insert_id;
                logActivity($_SESSION['user_id'], 'category_created', "Created category: $name");
                $_SESSION['success_message'] = 'Category created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create category';
            }
            $stmt->close();
            $conn->close();
            break;
            
        case 'update_category':
            $categoryId = intval($_POST['category_id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $categoryId);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'category_updated', "Updated category ID: $categoryId");
                $_SESSION['success_message'] = 'Category updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update category';
            }
            $stmt->close();
            $conn->close();
            break;
            
        case 'delete_category':
            $categoryId = intval($_POST['category_id']);
            
            $db = new Database();
            $conn = $db->getConnection();
            // Check if category is being used
            $stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM courses WHERE category_id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $courseCount = $stmt->fetch_assoc()['course_count'];
            $stmt->close();
            
            if ($courseCount > 0) {
                $_SESSION['error_message'] = 'Cannot delete category with associated courses';
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $categoryId);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'category_deleted', "Deleted category ID: $categoryId");
                    $_SESSION['success_message'] = 'Category deleted successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to delete category';
                }
                $stmt->close();
            }
            $conn->close();
            break;
    }
    
    header('Location: categories.php');
    exit;
}

// Get all categories
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT c.*, COUNT(co.id) as course_count FROM categories c LEFT JOIN courses co ON c.id = co.category_id GROUP BY c.id ORDER BY c.name");
$categories = $stmt->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="users.php">User Management</a></li>
                        <li><a class="dropdown-item" href="courses.php">Course Management</a></li>
                        <li><a class="dropdown-item" href="analytics.php">Analytics</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2"></i> User Management
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> Course Management
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tags me-2"></i> Categories
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Reports
                    </a>
                    <a href="logs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Categories</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Course Categories</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>
                    
                    <div class="row">
                        <?php if (empty($categories)): ?>
                            <div class="col-12 text-center py-4">
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <h5>No categories found</h5>
                                <p class="text-muted">Create categories to organize your courses</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                    <i class="fas fa-plus me-2"></i>Create First Category
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card category-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="category-icon">
                                                    <i class="fas fa-tag fa-2x text-primary"></i>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>')">
                                                                <i class="fas fa-edit me-2"></i>Edit
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-book me-1"></i>
                                                    <?php echo $category['course_count']; ?> courses
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_category">
                        
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function editCategory(id, name, description) {
            $('#edit_category_id').val(id);
            $('#edit_name').val(name);
            $('#edit_description').val(description);
            $('#editCategoryModal').modal('show');
        }
        
        function deleteCategory(id, name) {
            if (confirm('Are you sure you want to delete the category "' + name + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
