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
$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get expense details
$expense_query = $conn->prepare("SELECT e.*, u.full_name as employee_name, u.email as employee_email, 
    c.name as category_name
    FROM expenses e
    JOIN users u ON e.employee_id = u.id
    LEFT JOIN expense_categories c ON e.category_id = c.id
    WHERE e.id = ? AND e.company_id = ?");
$expense_query->bind_param("ii", $expense_id, $_SESSION['company_id']);
$expense_query->execute();
$expense = $expense_query->get_result()->fetch_assoc();

if (!$expense) {
    $_SESSION['error'] = "Expense not found!";
    header("Location: pending_approvals.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $comments = clean_input($_POST['comments']);
    
    if ($action == 'approve' || $action == 'reject') {
        $new_status = ($action == 'approve') ? 'Approved' : 'Rejected';
        
        // Update expense status
        $update_stmt = $conn->prepare("UPDATE expenses SET status = ?, current_approver_id = NULL WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $expense_id);
        $update_stmt->execute();
        
        // Add to approval history
        $history_action = ($action == 'approve') ? 'Approved' : 'Rejected';
        $history_stmt = $conn->prepare("INSERT INTO approval_history (expense_id, approver_id, action, comments) VALUES (?, ?, ?, ?)");
        $history_stmt->bind_param("iiss", $expense_id, $_SESSION['user_id'], $history_action, $comments);
        $history_stmt->execute();
        
        $_SESSION['success'] = "Expense " . strtolower($new_status) . " successfully!";
        header("Location: pending_approvals.php");
        exit();
    }
}

// Get approval history
$history_query = $conn->prepare("SELECT ah.*, u.full_name as approver_name 
    FROM approval_history ah
    JOIN users u ON ah.approver_id = u.id
    WHERE ah.expense_id = ?
    ORDER BY ah.action_date DESC");
$history_query->bind_param("i", $expense_id);
$history_query->execute();
$history = $history_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Expense - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .receipt-preview { max-width: 100%; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php"><i class="bi bi-cash-coin"></i> Expense Manager</a>
            <a href="pending_approvals.php" class="btn btn-light btn-sm">Back to Approvals</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="content-card">
                    <h2 class="mb-4"><i class="bi bi-file-earmark-text"></i> Expense Details</h2>
                    
                    <div class="mb-3">
                        <h5>Employee Information</h5>
                        <p><strong>Name:</strong> <?php echo $expense['employee_name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $expense['employee_email']; ?></p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h5>Expense Information</h5>
                        <p><strong>Amount:</strong> 
                            <?php 
                            if ($expense['currency_code'] != $user['currency_code']) {
                                echo format_currency($expense['converted_amount'], $user['currency_symbol']);
                                echo ' <small class="text-muted">(' . $expense['amount'] . ' ' . $expense['currency_code'] . ')</small>';
                            } else {
                                echo format_currency($expense['amount'], $user['currency_symbol']);
                            }
                            ?>
                        </p>
                        <p><strong>Category:</strong> <span class="badge bg-secondary"><?php echo $expense['category_name'] ?? 'N/A'; ?></span></p>
                        <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($expense['expense_date'])); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br($expense['description']); ?></p>
                        <p><strong>Submitted On:</strong> <?php echo date('d M Y H:i', strtotime($expense['submitted_at'])); ?></p>
                        <p><strong>Current Status:</strong> <span class="badge bg-warning"><?php echo $expense['status']; ?></span></p>
                    </div>
                    
                    <?php if ($expense['receipt_path']): ?>
                        <hr>
                        <h5>Receipt</h5>
                        <?php 
                        $file_ext = pathinfo($expense['receipt_path'], PATHINFO_EXTENSION);
                        if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])): 
                        ?>
                            <img src="../<?php echo $expense['receipt_path']; ?>" class="receipt-preview" alt="Receipt">
                        <?php else: ?>
                            <a href="../<?php echo $expense['receipt_path']; ?>" target="_blank" class="btn btn-info">
                                <i class="bi bi-file-earmark-pdf"></i> View Receipt
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="content-card">
                    <h5 class="mb-4">Action Required</h5>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea name="comments" class="form-control" rows="4" placeholder="Add your comments here..."></textarea>
                        </div>
                        
                        <button type="submit" name="action" value="approve" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to reject this expense?')">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    </form>
                </div>
                
                <?php if ($history->num_rows > 0): ?>
                    <div class="content-card mt-3">
                        <h5 class="mb-3">Approval History</h5>
                        <?php while ($h = $history->fetch_assoc()): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <p class="mb-1"><strong><?php echo $h['approver_name']; ?></strong></p>
                                <p class="mb-1">
                                    <span class="badge bg-<?php echo $h['action'] == 'Approved' ? 'success' : ($h['action'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                        <?php echo $h['action']; ?>
                                    </span>
                                </p>
                                <?php if ($h['comments']): ?>
                                    <p class="mb-1"><small><?php echo $h['comments']; ?></small></p>
                                <?php endif; ?>
                                <p class="mb-0"><small class="text-muted"><?php echo date('d M Y H:i', strtotime($h['action_date'])); ?></small></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>