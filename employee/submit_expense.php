<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

// Handle expense submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_expense'])) {
    $amount = floatval($_POST['amount']);
    $original_currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    
    // Get company currency
    $company = mysqli_fetch_assoc(mysqli_query($conn, "SELECT currency FROM companies WHERE id = {$_SESSION['company_id']}"));
    $base_currency = $company['currency'];
    
    // Convert currency
    $converted_amount = convertCurrency($amount, $original_currency, $base_currency);
    
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
    
    // Find approver
    $current_approver_id = null;
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT manager_id FROM users WHERE id = {$_SESSION['user_id']}"));
    if ($user_data['manager_id']) {
        $current_approver_id = $user_data['manager_id'];
    }
    
    // Insert expense
    $sql = "INSERT INTO expenses (employee_id, company_id, amount, currency_code, converted_amount, category_id, description, expense_date, receipt_path, current_approver_id) 
            VALUES ({$_SESSION['user_id']}, {$_SESSION['company_id']}, $amount, '$original_currency', $converted_amount, " . 
            (isset($_POST['category']) ? intval($_POST['category']) : 'NULL') . ", '$description', '$expense_date', " . 
            ($receipt_path ? "'$receipt_path'" : 'NULL') . ", " . 
            ($current_approver_id ? $current_approver_id : 'NULL') . ")";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Expense submitted successfully!";
        redirect('my_expenses.php');
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
}

// Get categories
$categories = mysqli_query($conn, "SELECT * FROM expense_categories WHERE company_id = {$_SESSION['company_id']}");

// Get user currency
$user_data = get_user_data();

include '../includes/header.php';
?>

<h2><i class="bi bi-plus-circle-fill"></i> Submit New Expense</h2>
<hr>

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
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
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

<?php include '../includes/footer.php'; ?>