<?php
require_once '../config.php';
check_role('Admin');

$user = get_user_data();

// Get all expenses with employee details
$expenses_query = $conn->query("SELECT e.*, u.full_name as employee_name, c.name as category_name 
                                FROM expenses e 
                                JOIN users u ON e.employee_id = u.id 
                                LEFT JOIN expense_categories c ON e.category_id = c.id 
                                WHERE e.company_id = " . $user['company_id'] . " 
                                ORDER BY e.submitted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Expenses - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .status-pending { background: #fbbf24; }
        .status-approved { background: #10b981; }
        .status-rejected { background: #ef4444; }
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
            <h2 class="mb-4">📊 All Expenses</h2>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses_query->num_rows > 0): ?>
                            <?php while ($expense = $expenses_query->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $expense['id']; ?></td>
                                <td><?php echo $expense['employee_name']; ?></td>
                                <td>
                                    <?php 
                                    if ($expense['currency_code'] != $user['currency_code']) {
                                        $converted = convert_currency($expense['amount'], $expense['currency_code'], $user['currency_code']);
                                        echo format_currency($converted, $user['currency_symbol']);
                                        echo '<br><small class="text-muted">(' . $expense['amount'] . ' ' . $expense['currency_code'] . ')</small>';
                                    } else {
                                        echo format_currency($expense['amount'], $user['currency_symbol']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo $expense['category_name'] ?? 'N/A'; ?></td>
                                <td><?php echo substr($expense['description'], 0, 50); ?>...</td>
                                <td><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = 'status-' . strtolower($expense['status']);
                                    echo '<span class="badge ' . $status_class . '">' . $expense['status'] . '</span>';
                                    ?>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($expense['submitted_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No expenses found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>