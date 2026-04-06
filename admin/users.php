<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireAdmin();

require_once '../models/Database.php';
require_once '../models/User.php';

$db = new Database();
$conn = $db->getConnection();
$userModel = new User();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add_user':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $fullName = trim($_POST['full_name']);
            $role = $_POST['role'];

            // Validate
            if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            // Check if username or email exists
            $stmt = $conn->prepare("SELECT id FROM users_new WHERE username = ? OR email = ?");
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                exit;
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users_new (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param('sssss', $username, $email, $hashedPassword, $fullName, $role);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            exit;

        case 'update_user':
            $userId = intval($_POST['user_id']);
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $status = $_POST['status'];

            $stmt = $conn->prepare("UPDATE users_new SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $fullName, $email, $role, $status, $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
            exit;

        case 'delete_user':
            $userId = intval($_POST['user_id']);

            // Don't allow deleting yourself
            if ($userId == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM users_new WHERE id = ?");
            $stmt->bind_param('i', $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
            exit;

        case 'reset_password':
            $userId = intval($_POST['user_id']);
            $newPassword = password_hash('password123', PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users_new SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $newPassword, $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password reset to: password123']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
            }
            exit;

        case 'bulk_delete':
            $userIds = json_decode($_POST['user_ids'], true);

            // Remove current user from list
            $userIds = array_filter($userIds, fn($id) => $id != $_SESSION['user_id']);

            if (empty($userIds)) {
                echo json_encode(['success' => false, 'message' => 'No users to delete']);
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $conn->prepare("DELETE FROM users_new WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => count($userIds) . ' users deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete users']);
            }
            exit;

        case 'bulk_status':
            $userIds = json_decode($_POST['user_ids'], true);
            $status = $_POST['status'];

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $conn->prepare("UPDATE users_new SET status = ? WHERE id IN ($placeholders)");
            $types = 's' . str_repeat('i', count($userIds));
            $params = array_merge([$status], $userIds);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated for ' . count($userIds) . ' users']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
            exit;
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM users_new $whereClause";
$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalUsers = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users
$sql = "SELECT * FROM users_new $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as c FROM users_new")->fetch_assoc()['c'],
    'students' => $conn->query("SELECT COUNT(*) as c FROM users_new WHERE role = 'student'")->fetch_assoc()['c'],
    'instructors' => $conn->query("SELECT COUNT(*) as c FROM users_new WHERE role = 'instructor'")->fetch_assoc()['c'],
    'admins' => $conn->query("SELECT COUNT(*) as c FROM users_new WHERE role = 'admin'")->fetch_assoc()['c'],
    'active' => $conn->query("SELECT COUNT(*) as c FROM users_new WHERE status = 'active'")->fetch_assoc()['c'],
];

require_once '../includes/universal_header.php';
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
                        <h2 class="mb-1">👥 User Management</h2>
                        <p class="mb-0 opacity-75">Manage system users and permissions</p>
                    </div>
                    <div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-2-4 mb-3">
                    <div class="admin-stat-card primary">
                        <div class="admin-stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="admin-stat-value"><?php echo $stats['total']; ?></div>
                        <div class="admin-stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-2-4 mb-3">
                    <div class="admin-stat-card success">
                        <div class="admin-stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="admin-stat-value"><?php echo $stats['students']; ?></div>
                        <div class="admin-stat-label">Students</div>
                    </div>
                </div>
                <div class="col-md-2-4 mb-3">
                    <div class="admin-stat-card warning">
                        <div class="admin-stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="admin-stat-value"><?php echo $stats['instructors']; ?></div>
                        <div class="admin-stat-label">Instructors</div>
                    </div>
                </div>
                <div class="col-md-2-4 mb-3">
                    <div class="admin-stat-card info">
                        <div class="admin-stat-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="admin-stat-value"><?php echo $stats['admins']; ?></div>
                        <div class="admin-stat-label">Admins</div>
                    </div>
                </div>
                <div class="col-md-2-4 mb-3">
                    <div class="admin-stat-card danger">
                        <div class="admin-stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="admin-stat-value"><?php echo $stats['active']; ?></div>
                        <div class="admin-stat-label">Active Users</div>
                    </div>
                </div>
            </div>
            <!-- Filters -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-filter me-2"></i>
                    Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search users..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Students</option>
                                <option value="instructor" <?php echo $roleFilter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn-modern btn-primary-modern w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users me-2"></i>
                            User Management
                            <small class="text-muted ms-2">Manage system users and permissions</small>
                        </div>
                        <div>
                            <button class="btn-modern btn-primary-modern" onclick="showAddUserModal()">
                                <i class="fas fa-plus me-1"></i>Add User
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bulk-actions-header" id="bulkActionsHeader">
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light">
                        <div>
                            <span id="selectedCount">0</span> users selected
                        </div>
                        <div class="bulk-actions" id="bulkActions">
                            <button class="btn-modern btn-success-modern" onclick="bulkStatus('active')">
                                <i class="fas fa-check me-1"></i>Activate
                            </button>
                            <button class="btn-modern btn-warning-modern" onclick="bulkStatus('inactive')">
                                <i class="fas fa-pause me-1"></i>Deactivate
                            </button>
                            <button class="btn-modern btn-danger-modern" onclick="bulkDelete()">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="admin-modern-table">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>"
                                                onchange="updateBulkActions()">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($user['profile_image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>"
                                                        class="admin-user-avatar" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="admin-avatar-placeholder">
                                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="admin-badge <?php
                                            echo $user['role'] === 'admin' ? 'danger' :
                                                ($user['role'] === 'instructor' ? 'warning' : 'primary');
                                            ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="admin-badge <?php
                                            echo $user['status'] === 'active' ? 'success' :
                                                ($user['status'] === 'blocked' ? 'danger' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="admin-action-btn" onclick='editUser(<?php echo json_encode($user); ?>)'
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="admin-action-btn" onclick="resetPassword(<?php echo $user['id']; ?>)"
                                                title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="admin-action-btn text-danger" onclick="deleteUser(<?php echo $user['id']; ?>)"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="admin-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>"
                                    class="admin-page-btn">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>"
                                    class="admin-page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>"
                                    class="admin-page-btn">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">Create User</button>
                </div>
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
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="editFullName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show add user modal
        function showAddUserModal() {
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        }

        // Add user
        function addUser() {
            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);
            formData.append('action', 'add_user');

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        }

        // Edit user
        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editFullName').value = user.full_name;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            document.getElementById('editStatus').value = user.status;

            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        // Update user
        function updateUser() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            formData.append('action', 'update_user');

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        }

        // Delete user
        function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        }

        // Reset password
        function resetPassword(userId) {
            if (!confirm('Reset password to default (password123)?')) return;

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('user_id', userId);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                });
        }

        // Select all
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                cb.checked = checked;
            });
            updateBulkActions();
        }

        // Update bulk actions
        function updateBulkActions() {
            const selected = document.querySelectorAll('.user-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected;
            document.getElementById('bulkActions').classList.toggle('active', selected > 0);
            document.getElementById('bulkActionsHeader').classList.toggle('active', selected > 0);
        }

        // Bulk delete
        function bulkDelete() {
            const userIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => parseInt(cb.value));

            if (!confirm(`Delete ${userIds.length} users?`)) return;

            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            formData.append('user_ids', JSON.stringify(userIds));

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        // Bulk status
        function bulkStatus(status) {
            const userIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => parseInt(cb.value));

            const formData = new FormData();
            formData.append('action', 'bulk_status');
            formData.append('user_ids', JSON.stringify(userIds));
            formData.append('status', status);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        // Enhanced animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards
            const statCards = document.querySelectorAll('.admin-stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animate content cards
            const contentCards = document.querySelectorAll('.admin-content-card');
            contentCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 500 + (index * 200));
            });

            // Animate table rows
            const tableRows = document.querySelectorAll('.admin-modern-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                row.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 800 + (index * 50));
            });
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>