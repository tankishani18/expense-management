<?php
require_once '../config.php';
check_role('Admin');

$user = get_user_data();
$success = '';
$error = '';

// Add Approval Rule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_rule'])) {
    $rule_name = clean_input($_POST['rule_name']);
    $is_manager_approver = isset($_POST['is_manager_approver']) ? 1 : 0;
    $approval_type = clean_input($_POST['approval_type']);
    $percentage_required = !empty($_POST['percentage_required']) ? floatval($_POST['percentage_required']) : NULL;
    $specific_approver_id = !empty($_POST['specific_approver_id']) ? intval($_POST['specific_approver_id']) : NULL;
    
    $stmt = $conn->prepare("INSERT INTO approval_rules (company_id, rule_name, is_manager_approver, approval_type, percentage_required, specific_approver_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisdi", $user['company_id'], $rule_name, $is_manager_approver, $approval_type, $percentage_required, $specific_approver_id);
    
    if ($stmt->execute()) {
        $success = "Approval rule added successfully!";
    } else {
        $error = "Error adding approval rule!";
    }
}

// Get all rules
$rules_query = $conn->query("SELECT ar.*, u.full_name as approver_name 
                            FROM approval_rules ar 
                            LEFT JOIN users u ON ar.specific_approver_id = u.id 
                            WHERE ar.company_id = " . $user['company_id'] . " 
                            ORDER BY ar.created_at DESC");

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
    <title>Approval Rules - Expense Management</title>
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
            <h2 class="mb-4">⚙ Approval Rules</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addRuleModal">➕ Add New Rule</button>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Rule Name</th>
                        <th>Manager Approval</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rule = $rules_query->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rule['rule_name']; ?></td>
                        <td><?php echo $rule['is_manager_approver'] ? '✅ Yes' : '❌ No'; ?></td>
                        <td><span class="badge bg-info"><?php echo $rule['approval_type']; ?></span></td>
                        <td>
                            <?php 
                            if ($rule['approval_type'] == 'Percentage') {
                                echo $rule['percentage_required'] . '% approval required';
                            } elseif ($rule['approval_type'] == 'Specific') {
                                echo 'Approver: ' . $rule['approver_name'];
                            } elseif ($rule['approval_type'] == 'Hybrid') {
                                echo $rule['percentage_required'] . '% OR ' . $rule['approver_name'];
                            } else {
                                echo 'Sequential approval';
                            }
                            ?>
                        </td>
                        <td><?php echo $rule['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Rule Modal -->
    <div class="modal fade" id="addRuleModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5>Add Approval Rule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Rule Name *</label>
                            <input type="text" name="rule_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_manager_approver" class="form-check-input" id="managerCheck" checked>
                            <label class="form-check-label" for="managerCheck">
                                Manager must approve first
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label>Approval Type *</label>
                            <select name="approval_type" id="approvalType" class="form-select" required>
                                <option value="Sequential">Sequential (Multi-level)</option>
                                <option value="Percentage">Percentage Based</option>
                                <option value="Specific">Specific Approver</option>
                                <option value="Hybrid">Hybrid (Percentage OR Specific)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="percentageField" style="display:none;">
                            <label>Percentage Required (%)</label>
                            <input type="number" name="percentage_required" class="form-control" min="1" max="100" step="0.01">
                        </div>
                        
                        <div class="mb-3" id="approverField" style="display:none;">
                            <label>Specific Approver</label>
                            <select name="specific_approver_id" class="form-select">
                                <option value="">Select Approver</option>
                                <?php while ($m = $managers->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['full_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_rule" class="btn btn-primary">Add Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('approvalType').addEventListener('change', function() {
            const type = this.value;
            const percentageField = document.getElementById('percentageField');
            const approverField = document.getElementById('approverField');
            
            percentageField.style.display = 'none';
            approverField.style.display = 'none';
            
            if (type === 'Percentage' || type === 'Hybrid') {
                percentageField.style.display = 'block';
            }
            if (type === 'Specific' || type === 'Hybrid') {
                approverField.style.display = 'block';
            }
        });
    </script>
</body>
</html>