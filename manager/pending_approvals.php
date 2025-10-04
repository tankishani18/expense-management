<?php
require_once '../config.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

// Check if user is Manager or Admin
if ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'Admin') {
    header("Location: ../dashboard.php");
    exit();
}

$user = get_user_data();

// Get pending expenses for this manager
$pending_query = "SELECT e.*, u.full_name as employee_name, c.name as category_name
    FROM expenses e
    JOIN users u ON e.employee_id = u.id
    LEFT JOIN expense_categories c ON e.category_id = c.id
    WHERE e.status = 'Pending' 
    AND e.company_id = {$_SESSION['company_id']}";

// If Manager role, only show expenses where they are the approver
if ($_SESSION['role'] == 'Manager') {
    $pending_query .= " AND e.current_approver_id = {$_SESSION['user_id']}";
}

$pending_query .= " ORDER BY e.submitted_at DESC";
$pending_expenses = $conn->query($pending_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .expense-card { background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 15px; }
        .expense-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php"><i class="bi bi-cash-coin"></i> Expense Manager</a>
            <a href="../dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="content-card">
            <h2 class="mb-4"><i class="bi bi-clipboard-check"></i> Pending Approvals</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($pending_expenses->num_rows > 0): ?>
                <?php while ($expense = $pending_expenses->fetch_assoc()): ?>
                    <div class="expense-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-2"><?php echo $expense['employee_name']; ?></h5>
                                <p class="mb-1"><strong>Amount:</strong> 
                                    <?php 
                                    if ($expense['currency_code'] != $user['currency_code']) {
                                        echo format_currency($expense['converted_amount'], $user['currency_symbol']);
                                        echo ' <small class="text-muted">(' . $expense['amount'] . ' ' . $expense['currency_code'] . ')</small>';
                                    } else {
                                        echo format_currency($expense['amount'], $user['currency_symbol']);
                                    }
                                    ?>
                                </p>
                                <p class="mb-1"><strong>Category:</strong> <span class="badge bg-secondary"><?php echo $expense['category_name'] ?? 'N/A'; ?></span></p>
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y', strtotime($expense['expense_date'])); ?></p>
                                <p class="mb-1"><strong>Description:</strong> <?php echo $expense['description']; ?></p>
                                <p class="mb-0"><small class="text-muted">Submitted: <?php echo date('d M Y H:i', strtotime($expense['submitted_at'])); ?></small></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($expense['receipt_path']): ?>
                                    <a href="../<?php echo $expense['receipt_path']; ?>" target="_blank" class="btn btn-sm btn-info mb-2 w-100">
                                        <i class="bi bi-eye"></i> View Receipt
                                    </a>
                                <?php endif; ?>
                                <a href="approve_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Review
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 64px; color: #ccc;"></i>
                    <p class="mt-3 text-muted">No pending approvals at the moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>