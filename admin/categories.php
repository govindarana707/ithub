<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireAdmin();

// Check if output buffer has issues
ob_start();

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_category':
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("INSERT INTO categories_new (name, description, created_at) VALUES (?, ?, NOW())");
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
            $stmt = $conn->prepare("UPDATE categories_new SET name = ?, description = ? WHERE id = ?");
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
            $stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM courses_new WHERE category_id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $courseCount = $row['course_count'];
            $stmt->close();
            
            if ($courseCount > 0) {
                $_SESSION['error_message'] = 'Cannot delete category with associated courses';
            } else {
                $stmt = $conn->prepare("DELETE FROM categories_new WHERE id = ?");
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

// Get all categories with course counts (using categories_new table)
$categories = [];
$error_msg = '';
try {
    $result = $conn->query("SELECT c.*, COUNT(co.id) as course_count FROM categories_new c LEFT JOIN courses_new co ON c.id = co.category_id GROUP BY c.id ORDER BY c.name");
    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_msg = "Query failed: " . $conn->error;
    }
} catch (Exception $e) {
    $error_msg = "Exception: " . $e->getMessage();
    $categories = [];
}

$conn->close();

require_once dirname(__DIR__) . '/includes/universal_header.php';
?>

<link rel="stylesheet" href="../assets/css/admin-theme.css">

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Admin Dashboard Header -->
            <div class="admin-dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">🏷️ Categories</h2>
                        <p class="mb-0 opacity-75">Manage course categories</p>
                    </div>
                    <div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Categories Card -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-tags me-2"></i>
                            Course Categories
                        </div>
                        <button class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (empty($categories)): ?>
                            <div class="col-12 text-center py-5">
                                <div class="admin-empty-state">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <h5>No categories found</h5>
                                    <p class="text-muted">Create categories to organize your courses</p>
                                    <button class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                        <i class="fas fa-plus me-2"></i>Create First Category
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="admin-category-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="admin-category-icon">
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
                        <button type="submit" class="btn-modern btn-primary-modern">
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
                        <button type="submit" class="btn-modern btn-primary-modern">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Debug: Check if Bootstrap is loaded
    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    
    // Manual dropdown toggle for fallback
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.dropdown-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var dropdown = this.closest('.dropdown').querySelector('.dropdown-menu');
                if (dropdown) {
                    dropdown.classList.toggle('show');
                }
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                });
            }
        });
    });
    
    function editCategory(id, name, description) {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_description').value = description;
        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
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

    // Add animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Animate content card
        const contentCard = document.querySelector('.admin-content-card');
        if (contentCard) {
            contentCard.style.opacity = '0';
            contentCard.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                contentCard.style.transition = 'all 0.5s ease';
                contentCard.style.opacity = '1';
                contentCard.style.transform = 'translateY(0)';
            }, 100);
        }
    });
</script>

<style>
    /* Fix dropdown visibility */
    .dropdown-menu {
        z-index: 1050 !important;
    }
    .admin-category-card {
        overflow: visible !important;
    }
    .admin-category-card .card-body {
        overflow: visible !important;
    }
</style>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; 

// Debug: Check if page loaded completely
echo "<!-- Page loaded successfully - PHP execution complete -->";
if (!empty($categories)) {
    echo "<!-- Categories loaded: " . count($categories) . " -->";
} else {
    echo "<!-- No categories loaded -->";
}
?>
