<?php
require_once '../config.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$user = get_user_data();

// Fetch user's expenses
$expenses = $conn->query("SELECT e.*, c.name as category_name, u.full_name as approver_name
                          FROM expenses e
                          LEFT JOIN expense_categories c ON e.category_id = c.id
                          LEFT JOIN users u ON e.current_approver_id = u.id
                          WHERE e.employee_id = {$_SESSION['user_id']}
                          ORDER BY e.submitted_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Expenses - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
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
            <h2 class="mb-4"><i class="bi bi-list-ul"></i> My Expenses</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Current Approver</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses->num_rows > 0): ?>
                            <?php while ($expense = $expenses->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $expense['id']; ?></td>
                                <td>
                                    <strong><?php echo format_currency($expense['converted_amount'], $user['currency_symbol']); ?></strong>
                                    <?php if ($expense['currency_code'] != $user['currency_code']): ?>
                                        <br><small class="text-muted">(<?php echo $expense['currency_code']; ?> <?php echo $expense['amount']; ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo $expense['category_name'] ?? 'N/A'; ?></span></td>
                                <td><?php echo substr($expense['description'], 0, 40); ?>...</td>
                                <td><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = $expense['status'] == 'Approved' ? 'success' : 
                                                  ($expense['status'] == 'Rejected' ? 'danger' : 'warning');
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo $expense['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $expense['approver_name'] ?? '-'; ?></td>
                                <td>
                                    <?php if ($expense['receipt_path']): ?>
                                        <a href="../<?php echo $expense['receipt_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                    <p class="mt-3 text-muted">No expenses submitted yet</p>
                                    <a href="submit_expense.php" class="btn btn-primary">Submit First Expense</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>