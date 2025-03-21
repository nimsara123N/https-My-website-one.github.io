<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user'];

// Fetch the user's bank card information from the database
$query = "SELECT account_holder, bank_name, bank_account_number FROM bank_accounts WHERE user_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bank_info = $result->fetch_assoc();

// Variables to prefill the form
$account_holder = $bank_info['account_holder'] ?? '';
$bank_name = $bank_info['bank_name'] ?? 'GCASH';  // Default to GCASH
$bank_account_number = $bank_info['bank_account_number'] ?? '';

$message = "";

// Handle form submission for updating bank card info
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_holder = $_POST['account_holder'];
    $bank_name = $_POST['bank_name'];
    $bank_account_number = $_POST['bank_account_number'];

    // Validate input
    if (empty($account_holder) || empty($bank_name) || empty($bank_account_number)) {
        $message = "All fields are required.";
    } else {
        // Check if the user already has a bank account in the database
        $check_query = "SELECT * FROM bank_accounts WHERE user_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update the existing bank account information for the user
            $update_query = "UPDATE bank_accounts SET account_holder = ?, bank_name = ?, bank_account_number = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            if (!$stmt) {
                die("Error preparing statement: " . $conn->error);
            }
            $stmt->bind_param("sssi", $account_holder, $bank_name, $bank_account_number, $user_id);
            if ($stmt->execute()) {
                $message = "Bank account information updated successfully!";
            } else {
                $message = "Error updating bank account information.";
            }
        } else {
            // Insert new bank account information for the user
            $insert_query = "INSERT INTO bank_accounts (user_id, account_holder, bank_name, bank_account_number) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            if (!$stmt) {
                die("Error preparing statement: " . $conn->error);
            }
            $stmt->bind_param("isss", $user_id, $account_holder, $bank_name, $bank_account_number);
            if ($stmt->execute()) {
                $message = "Bank account information added successfully!";
            } else {
                $message = "Error adding bank account information.";
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Card - Delor</title>
    <!-- Import Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f5f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 100px;
        }
        .header {
            background-color: #fff;
            padding: 20px;
            text-align: center;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        .bank-card-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            width: 90%;
            max-width: 450px;
            text-align: center;
        }
        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        img.bank-card-img {
            width: 80px;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }
        .form-group {
            width: 100%;
            text-align: left;
        }
        .form-group label {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }
        input[type="text"], select, input[type="submit"] {
            padding: 14px;
            font-size: 16px;
            border-radius: 12px;
            border: 1px solid #dcdcdc;
            width: 100%;
            max-width: 350px;
            box-sizing: border-box;
            text-align: center;
        }
        input[type="number"] {
            padding: 14px;
            font-size: 16px;
            border-radius: 12px;
            border: 1px solid #dcdcdc;
            width: 100%;
            max-width: 350px;
            box-sizing: border-box;
            text-align: center;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 15px;
            font-size: 16px;
            color: #28a745;
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
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
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
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <img src="logos.png" alt="Iberdrola Espa�a">
        <img src="icon.png" class="icon" alt="Icon">
    </div>

    <!-- Bank Card Section -->
    <div class="bank-card-container">
        <h2>Link your bank to save and invest</h2>
        <img src="bank_card_image.png" alt="Bank Image" class="bank-card-img">
        <form method="post" action="">
            <div class="form-group">
                <label for="account_holder">Account Holder's Name</label>
                <input type="text" name="account_holder" id="account_holder" placeholder="Enter account holder's name" value="<?php echo htmlspecialchars($account_holder); ?>" required>
            </div>
            <div class="form-group">
                <label for="bank_name">Bank Name</label>
                <select name="bank_name" id="bank_name" required>
                    <option value="GCASH" <?php echo $bank_name === 'GCASH' ? 'selected' : ''; ?>>GCASH</option>
                </select>
            </div>
            <div class="form-group">
                <label for="bank_account_number">Bank Account Number</label>
                <input type="number" name="bank_account_number" id="bank_account_number" placeholder="Enter your bank account number" value="<?php echo htmlspecialchars($bank_account_number); ?>" required>
            </div>
            <input type="submit" value="Submit">
        </form>

        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
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
