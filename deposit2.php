<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Set the timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get values from URL or session
$product_id = mysqli_real_escape_string($conn, $_GET['product'] ?? '');
$amount = filter_var($_GET['amount'] ?? '0.00', FILTER_VALIDATE_FLOAT);
$user_id = mysqli_real_escape_string($conn, $_SESSION['user'] ?? ''); // Get the user ID from the session

// Get the user's IP address
$ip_address = $_SERVER['REMOTE_ADDR'];

// Check required parameters
if (empty($user_id) || empty($product_id) || $amount === false) {
    echo "Invalid or missing parameters.";
    exit();
}

// Setup merchant and secret key
$merchant = 'KLDPay';
$secret_key = '695c1209e5830ba041dcbdd34dc5c2a5';

// Function to generate order ID
function generateOrderId() {
    return 'AGT' . substr(str_shuffle("0123456789"), 0, 10);
}

// Function to generate the signature for the API request
function generateSignature($params, $secret_key) {
    ksort($params); // Sort by key in ASCII ascending order
    $query_string = urldecode(http_build_query($params));
    return md5($query_string . '&key=' . $secret_key); // Append the secret key and hash it
}

// Function to process the payment
function processPayment($payment_params) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://cloud.la2568.site/api/transfer"); // Galaxy API payment endpoint
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payment_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    if ($response === false) {
        return ['status' => 0, 'message' => curl_error($ch)];
    }

    curl_close($ch);
    return json_decode($response, true);
}

// Function to insert deposit after successful payment, including IP address
function insertRechargeHistory($conn, $user_id, $product_id, $amount, $payment_gateway, $order_id, $status, $ip_address) {
    $transaction_date = date('Y-m-d H:i:s'); // Get the current date/time in Asia/Manila
    $stmt = $conn->prepare("INSERT INTO recharge_history (user_id, product_id, recharge_amount, payment_channel, trx, status, transaction_date, ip_address) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisissss", $user_id, $product_id, $amount, $payment_gateway, $order_id, $status, $transaction_date, $ip_address);
    return $stmt->execute();
}

// Function to check if the user has reached the pending deposit limit of 2
function checkUserPendingDepositLimit($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM recharge_history WHERE user_id = ? AND status = 2"); // Count pending deposits
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();
    return $pending_count;
}

// Check if the user has 2 pending deposits
$pending_count = checkUserPendingDepositLimit($conn, $user_id);
if ($pending_count >= 2) {
    echo "The merchant's channel was not opened:UGF5bWVudENoZWNrQ29udHJvbGxlci5waHA=:221";
    exit();
}

// Generate a new order ID
$order_id = generateOrderId();

// Set up the payment parameters
$payment_params = [
    'merchant' => $merchant,
    'payment_type' => '3', // According to the document: 1 = Qrcode
    'amount' => $amount,
    'order_id' => $order_id,
    'bank_code' => 'PMP', // Using Gcash for the transaction
    'callback_url' => "https://delor.sbs/callback.php", // Your callback URL
    'return_url' => "https://delor.sbs/order.php", // Your return URL after payment
];

// Generate the signature for payment
$payment_params['sign'] = generateSignature($payment_params, $secret_key);

// Process the payment via the Galaxy API
$payment_data = processPayment($payment_params);

// Check if the payment is successful
if (isset($payment_data['status']) && $payment_data['status'] == 1) {
    // Ensure the deposit record is only inserted if it doesn't already exist
    // Insert the successful payment as a pending deposit, including the IP address
    $status = 2;  // Pending status since payment is initiated
    $payment_gateway = 'gcash';

    if (insertRechargeHistory($conn, $user_id, $product_id, $amount, $payment_gateway, $order_id, $status, $ip_address)) {
        // Redirect to the Galaxy payment page
        header("Location: " . $payment_data['redirect_url']);
        exit();
    } else {
        echo "Error inserting deposit record: " . mysqli_error($conn);
    }
} else {
    // Handle payment failure
    $error_message = $payment_data['message'] ?? 'Payment Maintenance';
    echo $error_message;
}

// Close the database connection
mysqli_close($conn);
?>
