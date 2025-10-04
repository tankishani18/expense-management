<?php
require_once '../config.php';
check_role('Admin');

$user = get_user_data();
$success = '';
$error = '';

// Add/Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $full_name = clean_input($_POST['full_name']);
        $email = clean_input($_POST['email']);
        $password = $_POST['password'];
        $role = clean_input($_POST['role']);
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : NULL;
        
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (company_id, email, password, full_name, role, manager_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $user['company_id'], $email, $hashed_password, $full_name, $role, $manager_id);
            
            if ($stmt->execute()) {
                $success = "User added successfully!";
            } else {
                $error = "Error adding user!";
            }
        }
    } elseif ($action == 'edit') {
        $user_id = intval($_POST['user_id']);
        $role = clean_input($_POST['role']);
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : NULL;
        
        $stmt = $conn->prepare("UPDATE users SET role = ?, manager_id = ? WHERE id = ? AND company_id = ?");
        $stmt->bind_param("siii", $role, $manager_id, $user_id, $user['company_id']);
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
        } else {
            $error = "Error updating user!";
        }
    } elseif ($action == 'delete') {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND company_id = ? AND id != ?");
        $stmt->bind_param("iii", $user_id, $user['company_id'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user!";
        }
    }
}

// Get all users
$users_query = $conn->query("SELECT u.*, m.full_name as manager_name 
                             FROM users u 
                             LEFT JOIN users m ON u.manager_id = m.id 
                             WHERE u.company_id = " . $user['company_id'] . " 
                             ORDER BY u.created_at DESC");

// Get managers for dropdown
$managers = $conn->query("SELECT id, full_name FROM users 
                          WHERE company_id = " . $user['company_id'] . " 
                          AND role IN ('Manager', 'Admin') 
                          AND is_active = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">💼 Expense Manager</a>
            <a href="../dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="content-card">
            <h2 class="mb-4">👥 Manage Users</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addUserModal">➕ Add New User</button>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users_query->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $u['full_name']; ?></td>
                        <td><?php echo $u['email']; ?></td>
                        <td><span class="badge bg-primary"><?php echo $u['role']; ?></span></td>
                        <td><?php echo $u['manager_name'] ?? '-'; ?></td>
                        <td><?php echo $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>', <?php echo $u['manager_id'] ?? 'null'; ?>)">Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5>Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label>Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="Employee">Employee</option>
                                <option value="Manager">Manager</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Manager (Optional)</label>
                            <select name="manager_id" class="form-select">
                                <option value="">No Manager</option>
                                <?php $managers->data_seek(0); while ($m = $managers->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['full_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5>Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Role *</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="Employee">Employee</option>
                                <option value="Manager">Manager</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Manager</label>
                            <select name="manager_id" id="edit_manager" class="form-select">
                                <option value="">No Manager</option>
                                <?php $managers->data_seek(0); while ($m = $managers->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['full_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(id, role, manager_id) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_manager').value = manager_id || '';
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
    </script>
</body>
</html>