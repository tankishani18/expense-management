<?php
require_once '../config.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

// Handle expense submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_expense'])) {
    $amount = floatval($_POST['amount']);
    $original_currency = clean_input($_POST['currency']);
    $category_id = intval($_POST['category']);
    $description = clean_input($_POST['description']);
    $expense_date = clean_input($_POST['expense_date']);
    
    // Get company currency
    $user_data = get_user_data();
    $base_currency = $user_data['currency_code'];
    
    // Convert currency
    $converted_amount = convert_currency($amount, $original_currency, $base_currency);
    
    // Handle receipt upload
    $receipt_path = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $new_filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $receipt_path = 'uploads/' . $new_filename;
        
        move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_dir . $new_filename);
    }
    
    // Find approver (manager)
    $current_approver_id = null;
    $stmt = $conn->prepare("SELECT manager_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && $result['manager_id']) {
        $current_approver_id = $result['manager_id'];
    }
    
    // Insert expense
    $stmt = $conn->prepare("INSERT INTO expenses (employee_id, company_id, amount, currency_code, converted_amount, category_id, description, expense_date, receipt_path, current_approver_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsdisssi", 
        $_SESSION['user_id'], 
        $_SESSION['company_id'], 
        $amount, 
        $original_currency, 
        $converted_amount, 
        $category_id, 
        $description, 
        $expense_date, 
        $receipt_path, 
        $current_approver_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Expense submitted successfully!";
        header("Location: my_expenses.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM expense_categories WHERE company_id = {$_SESSION['company_id']}");

// Get user currency
$user_data = get_user_data();

if (!$user_data) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Expense - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-top: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php"><i class="bi bi-cash-coin"></i> Expense Manager</a>
            <a href="../dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="content-card">
            <h2 class="mb-4"><i class="bi bi-plus-circle-fill"></i> Submit New Expense</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="bi bi-file-earmark-plus"></i> Expense Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Amount *</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" required placeholder="0.00">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Currency *</label>
                                        <select name="currency" class="form-control" required>
                                            <option value="<?php echo $user_data['currency_code']; ?>" selected>
                                                <?php echo $user_data['currency_code']; ?> (<?php echo $user_data['currency_symbol']; ?>)
                                            </option>
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                            <option value="GBP">GBP (£)</option>
                                            <option value="INR">INR (₹)</option>
                                            <option value="AED">AED (د.إ)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category *</label>
                                        <select name="category" class="form-control" required>
                                            <option value="">Select Category</option>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Expense Date *</label>
                                        <input type="date" name="expense_date" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea name="description" class="form-control" rows="3" required placeholder="Enter expense details..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Upload Receipt (Optional)</label>
                                    <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Max 5MB. Supported: JPG, PNG, PDF</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Your expense will be sent to your manager for approval.
                                </div>
                                
                                <button type="submit" name="submit_expense" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-send"></i> Submit Expense
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>