<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
require 'db0.php';

// Retrieve the raw POST data sent from Galaxy
$callback_data = $_POST;

// Define your merchant ID and secret key
$merchant_id = 'JTPay';  // Your merchant ID
$secret_key = 'f1e7a94f73da58608dc0b9df22196202';  // Your secret key

// Verify that the callback contains the necessary parameters
if (!isset($callback_data['order_id']) || !isset($callback_data['amount']) || !isset($callback_data['status']) || !isset($callback_data['sign'])) {
    die("Invalid callback parameters.");
}

// Retrieve the data
$order_id = $callback_data['order_id'];
$amount = $callback_data['amount'];
$status = $callback_data['status'];
$received_sign = $callback_data['sign'];

// Recreate the signature to verify the integrity of the data
$data_to_sign = [
    'merchant' => $callback_data['merchant'],
    'order_id' => $order_id,
    'amount' => $amount,
    'status' => $status
];

// Sort the data by ASCII order and append the secret key
ksort($data_to_sign);
$sign_str = http_build_query($data_to_sign) . "&key=" . $secret_key;
$calculated_sign = md5($sign_str);

// Verify the signature
if ($calculated_sign !== $received_sign) {
    die("Signature verification failed.");
}

// Process the payment status
if ($status == 5) { // Payment success
    // Update recharge history to 'approved' (status = 1)
    $update_recharge_query = "UPDATE recharge_history SET status = 1 WHERE trx = ?";
    $stmt = mysqli_prepare($conn, $update_recharge_query);
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    $stmt->execute();
    mysqli_stmt_close($stmt);

    // Optionally, add any further processing like creating an order, etc.

    // Return SUCCESS to stop further notifications
    echo "SUCCESS";
} elseif ($status == 3) { // Payment failure
    // Update recharge history to 'rejected' (status = 3)
    $update_recharge_query = "UPDATE recharge_history SET status = 3 WHERE trx = ?";
    $stmt = mysqli_prepare($conn, $update_recharge_query);
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    $stmt->execute();
    mysqli_stmt_close($stmt);

    // You can also return SUCCESS for rejected notifications
    echo "SUCCESS";
} else {
    // Handle other statuses if necessary
    echo "Unknown status received.";
}

// Close the database connection
mysqli_close($conn);
?>
