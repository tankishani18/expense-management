<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_management');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Helper function to clean input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function check_role($required_role) {
    if (!is_logged_in()) {
        header("Location: ../index.php");
        exit();
    }
    
    if ($_SESSION['role'] != $required_role && $_SESSION['role'] != 'Admin') {
        header("Location: ../dashboard.php");
        exit();
    }
}

// Get user data
function get_user_data() {
    global $conn;
    if (!is_logged_in()) return null;
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT u.*, c.currency_code, c.currency_symbol, c.name as company_name
              FROM users u
              JOIN companies c ON u.company_id = c.id
              WHERE u.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Currency conversion function
function convert_currency($amount, $from_currency, $to_currency) {
    if ($from_currency == $to_currency) {
        return $amount;
    }
    
    $url = "https://api.exchangerate-api.com/v4/latest/" . $from_currency;
    $response = @file_get_contents($url);
    
    if ($response === FALSE) {
        return $amount;
    }
    
    $data = json_decode($response, true);
    if (isset($data['rates'][$to_currency])) {
        $rate = $data['rates'][$to_currency];
        return $amount * $rate;
    }
    
    return $amount;
}

// Format currency
function format_currency($amount, $currency_symbol = '₹') {
    return $currency_symbol . ' ' . number_format($amount, 2);
}
?>