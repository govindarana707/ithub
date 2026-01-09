<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

requireAdmin();

$user = new User();

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            $data = [
                'username' => sanitize($_POST['username']),
                'email' => sanitize($_POST['email']),
                'password' => $_POST['password'],
                'full_name' => sanitize($_POST['full_name']),
                'role' => sanitize($_POST['role']),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'status' => 'active'
            ];
            
            $result = $user->register($data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'User created successfully!';
                logActivity($_SESSION['user_id'], 'user_created', "Created user: {$data['full_name']} ({$data['role']})");
            } else {
                $_SESSION['error_message'] = 'Failed to create user: ' . $result['error'];
            }
            break;
            
        case 'update_user':
            $userId = intval($_POST['user_id']);
            $data = [
                'full_name' => sanitize($_POST['full_name']),
                'email' => sanitize($_POST['email']),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'role' => sanitize($_POST['role']),
                'status' => sanitize($_POST['status'])
            ];
            
            if ($user->updateUser($userId, $data)) {
                $_SESSION['success_message'] = 'User updated successfully!';
                logActivity($_SESSION['user_id'], 'user_updated', "Updated user ID: $userId");
            } else {
                $_SESSION['error_message'] = 'Failed to update user';
            }
            break;
            
        case 'delete_user':
            $userId = intval($_POST['user_id']);
            if ($userId != $_SESSION['user_id']) {
                if ($user->deleteUser($userId)) {
                    $_SESSION['success_message'] = 'User deleted successfully!';
                    logActivity($_SESSION['user_id'], 'user_deleted', "Deleted user ID: $userId");
                } else {
                    $_SESSION['error_message'] = 'Failed to delete user';
                }
            } else {
                $_SESSION['error_message'] = 'Cannot delete your own account';
            }
            break;
            
        case 'toggle_status':
            $userId = intval($_POST['user_id']);
            $newStatus = $_POST['status'] === 'active' ? 'blocked' : 'active';
            
            if ($user->updateUser($userId, ['status' => $newStatus])) {
                $_SESSION['success_message'] = "User status updated to $newStatus!";
                logActivity($_SESSION['user_id'], 'user_status_changed', "Changed user ID: $userId status to $newStatus");
            } else {
                $_SESSION['error_message'] = 'Failed to update user status';
            }
            break;
    }
    
    header('Location: users.php');
    exit;
}

// Get users with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$users = $user->getAllUsers($role, $status, $limit, ($page - 1) * $limit, $search);

// Get total count for pagination
$conn = connectDB();
$sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];
$types = "";

if ($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalUsers = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$conn->close();

$totalPages = ceil($totalUsers / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IT HUB</title>
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
                    <a href="users.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users-cog me-2"></i> User Management
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> Course Management
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
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
                    <h1>User Management</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="dashboard-card mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="instructor" <?php echo $role === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-user-plus me-2"></i>Add User
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Users (<?php echo $totalUsers; ?> total)</h3>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportUsers()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="refreshUsers()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Avatar</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No users found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <?php if ($user['profile_image']): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                         class="rounded-circle" width="30" height="30" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                         style="width: 30px; height: 30px; font-size: 12px;">
                                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getRoleBadgeColor($user['role']); ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeColor($user['status']); ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                            onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                                        <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>&search=<?php echo $search; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>&search=<?php echo $search; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="blocked">Blocked</option>
                            </select>
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
        function editUser(userId) {
            // Fetch user data and populate modal
            $.get('api/get_user.php', {user_id: userId}, function(data) {
                if (data.success) {
                    $('#edit_user_id').val(data.user.id);
                    $('#edit_full_name').val(data.user.full_name);
                    $('#edit_email').val(data.user.email);
                    $('#edit_phone').val(data.user.phone || '');
                    $('#edit_role').val(data.user.role);
                    $('#edit_status').val(data.user.status);
                    
                    $('#editUserModal').modal('show');
                } else {
                    alert('Failed to load user data');
                }
            });
        }
        
        function toggleStatus(userId, currentStatus) {
            if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'block' : 'unblock') + ' this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="status" value="${currentStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function exportUsers() {
            window.location.href = 'api/export_users.php';
        }
        
        function refreshUsers() {
            location.reload();
        }
    </script>
</body>
</html>

<?php
function getRoleBadgeColor($role) {
    $colors = [
        'admin' => 'danger',
        'instructor' => 'primary',
        'student' => 'success'
    ];
    return $colors[$role] ?? 'secondary';
}

function getStatusBadgeColor($status) {
    $colors = [
        'active' => 'success',
        'blocked' => 'danger'
    ];
    return $colors[$status] ?? 'warning';
}
?>
