<?php
require_once '../config.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$user = get_user_data();
$categories = $conn->query("SELECT * FROM expense_categories WHERE company_id = {$_SESSION['company_id']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Receipt Scanner - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        #imagePreview { max-width: 100%; margin: 20px 0; border-radius: 10px; }
        .ocr-result { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .loading { display: none; text-align: center; }
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
            <h2 class="mb-4"><i class="bi bi-camera-fill"></i> OCR Receipt Scanner</h2>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Upload a receipt image and our AI will automatically extract expense details!
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Upload Receipt Image *</label>
                        <input type="file" id="receiptImage" class="form-control" accept="image/*" required>
                    </div>
                    
                    <button onclick="scanReceipt()" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="bi bi-upc-scan"></i> Scan Receipt
                    </button>
                    
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Scanning receipt... Please wait</p>
                    </div>
                    
                    <img id="imagePreview" style="display:none;">
                </div>
                
                <div class="col-md-6">
                    <div id="extractedForm" style="display:none;">
                        <h5 class="mb-3">Extracted Information</h5>
                        <form method="POST" action="submit_expense.php" enctype="multipart/form-data">
                            <input type="hidden" name="ocr_scanned" value="1">
                            <input type="hidden" name="receipt_temp" id="receipt_temp">
                            
                            <div class="mb-3">
                                <label class="form-label">Amount *</label>
                                <input type="number" name="amount" id="extracted_amount" class="form-control" step="0.01" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Currency *</label>
                                <select name="currency" id="extracted_currency" class="form-control" required>
                                    <option value="<?php echo $user['currency_code']; ?>"><?php echo $user['currency_code']; ?></option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="INR">INR</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" id="extracted_category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Date *</label>
                                <input type="date" name="expense_date" id="extracted_date" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea name="description" id="extracted_description" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Merchant/Vendor</label>
                                <input type="text" id="extracted_merchant" class="form-control" readonly>
                            </div>
                            
                            <button type="submit" name="submit_expense" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-circle"></i> Submit Expense
                            </button>
                        </form>
                    </div>
                    
                    <div id="ocrResult" class="ocr-result" style="display:none;">
                        <h6>Raw OCR Text:</h6>
                        <pre id="ocrText" style="white-space: pre-wrap; font-size: 12px;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <script>
        let uploadedFile = null;

        document.getElementById('receiptImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadedFile = file;
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function scanReceipt() {
            if (!uploadedFile) {
                alert('Please upload a receipt image first!');
                return;
            }

            document.querySelector('.loading').style.display = 'block';
            document.getElementById('extractedForm').style.display = 'none';

            Tesseract.recognize(
                uploadedFile,
                'eng',
                {
                    logger: m => console.log(m)
                }
            ).then(({ data: { text } }) => {
                document.querySelector('.loading').style.display = 'none';
                document.getElementById('ocrText').textContent = text;
                document.getElementById('ocrResult').style.display = 'block';
                
                // Parse the OCR text
                parseReceiptText(text);
                document.getElementById('extractedForm').style.display = 'block';
            }).catch(err => {
                document.querySelector('.loading').style.display = 'none';
                alert('Error scanning receipt: ' + err.message);
            });
        }

        function parseReceiptText(text) {
            // Extract amount (looks for numbers with currency symbols or decimal patterns)
            const amountRegex = /(?:₹|Rs\.?|INR|USD|\$|EUR|€|GBP|£)\s*(\d+(?:,\d{3})(?:\.\d{2})?)|(\d+(?:,\d{3})(?:\.\d{2})?)\s*(?:₹|Rs\.?|INR|USD|\$|EUR|€|GBP|£)/gi;
            const amounts = [];
            let match;
            while ((match = amountRegex.exec(text)) !== null) {
                const amount = (match[1] || match[2]).replace(/,/g, '');
                amounts.push(parseFloat(amount));
            }
            if (amounts.length > 0) {
                document.getElementById('extracted_amount').value = Math.max(...amounts);
            }

            // Extract currency
            if (text.includes('₹') || text.includes('INR') || text.includes('Rs')) {
                document.getElementById('extracted_currency').value = 'INR';
            } else if (text.includes('$') || text.includes('USD')) {
                document.getElementById('extracted_currency').value = 'USD';
            } else if (text.includes('€') || text.includes('EUR')) {
                document.getElementById('extracted_currency').value = 'EUR';
            } else if (text.includes('£') || text.includes('GBP')) {
                document.getElementById('extracted_currency').value = 'GBP';
            }

            // Extract date
            const dateRegex = /(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})|(\d{2,4}[-\/]\d{1,2}[-\/]\d{1,2})/g;
            const dateMatch = text.match(dateRegex);
            if (dateMatch) {
                const dateStr = dateMatch[0];
                const parts = dateStr.split(/[-\/]/);
                let formattedDate;
                if (parts[0].length === 4) {
                    formattedDate = ${parts[0]}-${parts[1].padStart(2, '0')}-${parts[2].padStart(2, '0')};
                } else {
                    const year = parts[2].length === 2 ? '20' + parts[2] : parts[2];
                    formattedDate = ${year}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')};
                }
                document.getElementById('extracted_date').value = formattedDate;
            } else {
                document.getElementById('extracted_date').value = new Date().toISOString().split('T')[0];
            }

            // Extract merchant name (usually in first few lines)
            const lines = text.split('\n').filter(line => line.trim().length > 0);
            if (lines.length > 0) {
                document.getElementById('extracted_merchant').value = lines[0].trim();
                document.getElementById('extracted_description').value = Expense at ${lines[0].trim()};
            }

            // Auto-detect category based on keywords
            const lowerText = text.toLowerCase();
            if (lowerText.includes('restaurant') || lowerText.includes('food') || lowerText.includes('cafe') || lowerText.includes('dining')) {
                selectCategoryByName('Food & Dining');
            } else if (lowerText.includes('hotel') || lowerText.includes('accommodation')) {
                selectCategoryByName('Accommodation');
            } else if (lowerText.includes('taxi') || lowerText.includes('uber') || lowerText.includes('transport')) {
                selectCategoryByName('Transportation');
            } else if (lowerText.includes('flight') || lowerText.includes('train') || lowerText.includes('travel')) {
                selectCategoryByName('Travel');
            }
        }

        function selectCategoryByName(categoryName) {
            const select = document.getElementById('extracted_category');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text.includes(categoryName)) {
                    select.selectedIndex = i;
                    break;
                }
            }
        }
    </script>
</body>
</html>