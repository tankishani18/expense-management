<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = clean_input($_POST['company_name']);
    $country = clean_input($_POST['country']);
    $currency_code = clean_input($_POST['currency_code']);
    $currency_symbol = clean_input($_POST['currency_symbol']);
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($company_name) || empty($country) || empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $conn->begin_transaction();
            
            try {
                $stmt = $conn->prepare("INSERT INTO companies (name, country, currency_code, currency_symbol) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $company_name, $country, $currency_code, $currency_symbol);
                $stmt->execute();
                $company_id = $conn->insert_id;
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Admin';
                $stmt = $conn->prepare("INSERT INTO users (company_id, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $company_id, $email, $hashed_password, $full_name, $role);
                $stmt->execute();
                
                $categories = ['Travel', 'Food & Dining', 'Office Supplies', 'Accommodation', 'Transportation', 'Entertainment', 'Others'];
                $stmt = $conn->prepare("INSERT INTO expense_categories (company_id, name) VALUES (?, ?)");
                foreach ($categories as $category) {
                    $stmt->bind_param("is", $company_id, $category);
                    $stmt->execute();
                }
                
                $conn->commit();
                $success = "Company created successfully! Please login.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error creating account: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .signup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="signup-card">
                    <div class="card-header">
                        <h3>🏢 Create Your Company</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <a href="index.php" class="btn btn-primary w-100">Go to Login</a>
                        <?php else: ?>
                        <form method="POST">
                            <h5 class="mb-3">Company Information</h5>
                            <div class="mb-3">
                                <label>Company Name *</label>
                                <input type="text" name="company_name" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Country *</label>
                                    <select name="country" id="country" class="form-select" required>
                                        <option value="">Select Country</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Currency</label>
                                    <input type="text" id="currency_display" class="form-control" readonly>
                                    <input type="hidden" name="currency_code" id="currency_code">
                                    <input type="hidden" name="currency_symbol" id="currency_symbol">
                                </div>
                            </div>
                            
                            <hr>
                            <h5 class="mb-3">Admin User Details</h5>
                            
                            <div class="mb-3">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Password *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Confirm Password *</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mt-3">Create Company & Sign Up</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="index.php">Login here</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

    <script>
    // Fetch country & currency data from API
    fetch('https://restcountries.com/v3.1/all?fields=name,currencies')
        .then(response => response.json())
        .then(data => {
            const countrySelect = document.getElementById('country');
            const sortedCountries = data.sort((a, b) => 
                a.name.common.localeCompare(b.name.common)
            );
            
            sortedCountries.forEach(country => {
                const option = document.createElement('option');
                option.value = country.name.common;
                option.textContent = country.name.common;

                // Attach currency data to each option
                option.dataset.currencies = JSON.stringify(country.currencies);
                countrySelect.appendChild(option);
            });
        })
        .catch(err => console.error("Error loading countries:", err));

    // When user selects a country
    document.getElementById('country').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.currencies) {
            const currencies = JSON.parse(selectedOption.dataset.currencies);
            const currencyCode = Object.keys(currencies)[0];  // e.g. "USD"
            const currency = currencies[currencyCode];        // e.g. { name: "United States dollar", symbol: "$" }

            document.getElementById('currency_code').value = currencyCode;
            document.getElementById('currency_symbol').value = currency.symbol || currencyCode;
            document.getElementById('currency_display').value = `${currencyCode} (${currency.symbol || ''})`;
        } else {
            // Clear if no currency info found
            document.getElementById('currency_code').value = '';
            document.getElementById('currency_symbol').value = '';
            document.getElementById('currency_display').value = '';
        }
    });
</script>

</body>
</html>