<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Fetch the order_id from the URL parameters, ensure it is valid (now accepting string format)
$order_id = isset($_GET['order_id']) && !empty($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : '';

if (!empty($order_id)) {
    // Retrieve the corresponding transaction details from the recharge_history table
    $sql = "SELECT trx, recharge_amount, transaction_date FROM recharge_history WHERE trx = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param('s', $order_id); // Bind as a string since order_id is now non-numeric

    if (!$stmt->execute()) {
        die("SQL execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();

    // Check if the query returned any result
    if ($transaction) {
        $trx = $transaction['trx'] ?? 'Unknown';
        $unit_price = '₱' . number_format($transaction['recharge_amount'], 2);
        $transaction_date = date("H:i:s d M Y", strtotime($transaction['transaction_date']));
    } else {
        // Log when no results are returned
        error_log("No transaction found for order_id: " . $order_id);
        $trx = 'Unknown';
        $unit_price = '₱0.00';
        $transaction_date = 'Unknown';
    }
    $stmt->close();
} else {
    // Log when order_id is not set correctly
    error_log("Invalid or missing order_id");
    // Default values if order_id is not set or invalid
    $trx = 'Unknown';
    $unit_price = '₱0.00';
    $transaction_date = 'Unknown';
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['voucher']) && $_FILES['voucher']['error'] === 0) {
        $fileTmpPath = $_FILES['voucher']['tmp_name'];
        $fileName = $_FILES['voucher']['name'];
        $uploadFileDir = './uploads/';
        $dest_path = $uploadFileDir . basename($fileName);

        // Ensure the uploads directory exists
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0777, true);
        }

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $message = 'Voucher uploaded successfully.';
        } else {
            $message = 'There was an error moving the file to the upload directory.';
        }
    } else {
        $message = 'No file uploaded or there was an upload error.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment Voucher</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 100px;
        }
        .header {
            background-color: #fff;
            padding: 15px;
            text-align: center;
            width: 100%;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 20px;
            padding-right: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header img {
            height: 40px;
        }
        .header .icon {
            height: 30px;
            cursor: pointer;
        }
        .upload-section {
            text-align: center;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            margin: 30px auto;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 1px solid #e0e0e0;
        }
        .upload-section h2 {
            font-size: 24px;
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .upload-section p {
            color: #6e6e6e;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .upload-section .confirming {
            color: #f5a623;
            font-weight: 600;
            margin-top: 10px;
        }
        .upload-section input[type="file"] {
            display: none;
        }
        .upload-section label {
            cursor: pointer;
            display: inline-block;
            padding: 12px 25px;
            background-color: #00875a;
            color: white;
            border-radius: 30px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .upload-section label:hover {
            background-color: #006f46;
        }
        .upload-section button {
            background-color: #00875a;
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .upload-section button:hover {
            background-color: #006f46;
        }
        .message {
            text-align: center;
            color: #28a745;
            font-size: 16px;
            margin-top: 15px;
        }
        .example {
            text-align: center;
            margin-top: 20px;
            color: #00875a;
            font-size: 15px;
            font-weight: 500;
            text-decoration: underline;
        }
        .example a {
            color: inherit;
            text-decoration: none;
        }
        .example a:hover {
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #fff;
            border-top: 1px solid #ddd;
            position: fixed;
            width: 100%;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.05);
        }
        .footer div {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #555;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .footer div a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        .footer div img {
            width: 30px;
            height: 30px;
        }
        .footer div span {
            font-size: 12px;
            margin-top: 5px;
        }
        .footer div:hover {
            color: #1a7f37;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<div class="header">
    <img src="logo.png" alt="7-Eleven">
    <img src="icon.png" class="icon" alt="Icon">
</div>

<!-- Voucher Upload Section -->
<div class="upload-section">
    <h2>Transaction ID: <?php echo htmlspecialchars($trx); ?></h2>
    <p>Unit Price: <?php echo htmlspecialchars($unit_price); ?></p>
    <p>Transaction Date: <?php echo htmlspecialchars($transaction_date); ?></p>
    <p class="confirming">Confirming</p>

    <form action="upload.php?order_id=<?php echo htmlspecialchars($order_id); ?>" method="post" enctype="multipart/form-data">
        <label for="voucher">Add payment voucher</label>
        <input type="file" id="voucher" name="voucher" required>
        <button type="submit">Upload</button>
    </form>

    <?php if (isset($message)) { echo '<div class="message">' . htmlspecialchars($message) . '</div>'; } ?>
    <div class="example"><a href="#">View example</a></div>
</div>

<!-- Bottom Navigation -->
<div class="footer">
    <div>
        <a href="dashboard.php">
            <img src="home.png" alt="Home">
            <span>Home</span>
        </a>
    </div>
    <div>
        <a href="order.php">
            <img src="order.png" alt="Order">
            <span>Order</span>
        </a>
    </div>
    <div>
        <a href="about_us.php">
            <img src="about_us.png" alt="About Us">
            <span>About Us</span>
        </a>
    </div>
    <div>
        <a href="menu.php">
            <img src="menu.png" alt="Menu">
            <span>Menu</span>
        </a>
    </div>
</div>

</body>
</html>
