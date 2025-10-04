<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$user = get_user_data();
$role = $_SESSION['role'];

if ($role == 'Admin') {
    $total_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE company_id = " . $user['company_id'])->fetch_assoc()['count'];
    $pending_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE company_id = " . $user['company_id'] . " AND status = 'Pending'")->fetch_assoc()['count'];
    $approved_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE company_id = " . $user['company_id'] . " AND status = 'Approved'")->fetch_assoc()['count'];
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE company_id = " . $user['company_id'])->fetch_assoc()['count'];
} else {
    $total_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE employee_id = " . $_SESSION['user_id'])->fetch_assoc()['count'];
    $pending_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE employee_id = " . $_SESSION['user_id'] . " AND status = 'Pending'")->fetch_assoc()['count'];
    $approved_expenses = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE employee_id = " . $_SESSION['user_id'] . " AND status = 'Approved'")->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-number { font-size: 36px; font-weight: 700; }
        .action-btn {
            display: block;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">💼 Expense Manager</a>
            <div class="text-white">
                <?php echo $user['full_name']; ?> (<?php echo $role; ?>)
                <a href="logout.php" class="btn btn-light btn-sm ms-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Welcome, <?php echo $user['full_name']; ?>! 👋</h2>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div>Total Expenses</div>
                    <div class="stat-number"><?php echo $total_expenses; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div>Pending</div>
                    <div class="stat-number"><?php echo $pending_expenses; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div>Approved</div>
                    <div class="stat-number"><?php echo $approved_expenses; ?></div>
                </div>
            </div>
            <?php if ($role == 'Admin'): ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div>Total Users</div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <h4>Quick Actions</h4>
                    <?php if ($role == 'Admin'): ?>
                        <a href="admin/manage_users.php" class="action-btn">👥 Manage Users</a>
                        <a href="admin/approval_rules.php" class="action-btn">⚙ Approval Rules</a>
                        <a href="admin/view_expenses.php" class="action-btn">📊 View All Expenses</a>
                    <?php endif; ?>
                    
                    <?php if ($role == 'Manager' || $role == 'Admin'): ?>
                        <a href="manager/pending_approvals.php" class="action-btn">✅ Pending Approvals</a>
                    <?php endif; ?>
                    
                    <a href="employee/submit_expense.php" class="action-btn">➕ Submit New Expense</a>
                    <a href="employee/ocr_scan.php" class="action-btn">📸 Scan Receipt (OCR)</a>
                    <a href="employee/my_expenses.php" class="action-btn">📋 My Expenses</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>