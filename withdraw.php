<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user'];

// Fetch user information and bank card details from the database
$query = "SELECT users.mobile_number, users.balance, bank_accounts.bank_account_number FROM users
          LEFT JOIN bank_accounts ON users.id = bank_accounts.user_id
          WHERE users.id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Variables to hold user information
$mobile_number = $user['mobile_number'];
$balance = $user['balance'];
$bank_account_number = $user['bank_account_number'] ?? null; // Check if the bank account number is present

$stmt->close();

$message = "";

// Withdrawal handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($bank_account_number)) {
    $withdraw_amount = $_POST['withdraw_amount'];

    // Set minimum withdrawal to ₱50 and apply 0% tax
    if ($withdraw_amount >= 50 && $withdraw_amount <= $balance) {
        $new_balance = $balance - $withdraw_amount; // Deduct the full amount from the balance

        // Update the user's balance in the database
        $conn->begin_transaction();

        try {
            // Update user balance
            $update_query = "UPDATE users SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();
            $stmt->close();

            // Insert new withdrawal record
            $insert_query = "INSERT INTO withdrawals (user_id, bank_account_number, amount, status) VALUES (?, ?, ?, ?)";
            $status = 2; // Pending status
            $stmt = $conn->prepare($insert_query);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("isdi", $user_id, $bank_account_number, $withdraw_amount, $status);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            // Update balance locally
            $balance = $new_balance;
            $message = "Withdrawal of ₱{$withdraw_amount} submitted successfully and is pending approval.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "An error occurred during withdrawal: " . $e->getMessage();
        }
    } else {
        $message = $withdraw_amount > $balance ? "Insufficient balance." : "Minimum withdrawal is ₱50.";
    }
}

// Fetch the user's withdrawal records from the database
$records_query = "SELECT amount, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($records_query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$withdrawals_result = $stmt->get_result();
$withdrawals = $withdrawals_result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw - Delor</title>
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
            color: #333;
        }

        /* Header Styling */
        .header {
            background-color: #ffffff;
            padding: 20px;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        /* Withdraw Container Styling */
        .withdraw-container {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
            margin-top: 30px;
        }

        h2 {
            font-size: 22px;
            color: #222;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .balance {
            background-color: #eaf6ed;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 1px solid #d4e9d6;
        }

        .balance p {
            margin: 10px 0;
            font-size: 20px;
            color: #1a7f37;
            font-weight: 500;
        }

        .balance p.id {
            font-size: 18px;
            font-weight: 600;
            color: #555;
        }

        /* Bank Card Information */
        .bank-card-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            font-size: 18px;
            color: #333;
            margin-bottom: 25px;
        }

        .bank-card-info .edit {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 600;
        }

        .withdraw-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        input[type="number"], input[type="submit"] {
            padding: 16px;
            font-size: 18px;
            border-radius: 12px;
            border: 1px solid #dcdcdc;
            width: 100%;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input[type="number"]:focus, input[type="submit"]:focus {
            outline: none;
            border-color: #1a7f37;
        }

        input[type="submit"] {
            background-color: #1a7f37;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        input[type="submit"]:hover {
            background-color: #14863b;
            transform: scale(1.02);
        }

        .bank-link {
            color: #1a73e8;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        .message {
            margin-top: 20px;
            font-size: 16px;
            color: #43a047;
            font-weight: 600;
        }

        /* Withdrawal Record Section */
        .withdraw-record {
            margin-top: 40px;
            text-align: left;
            width: 100%;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .withdraw-record h4 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .withdraw-record table {
            width: 100%;
            border-collapse: collapse;
        }

        .withdraw-record th, .withdraw-record td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .withdraw-record th {
            font-size: 18px;
            color: #555;
            font-weight: 600;
        }

        .withdraw-record td {
            font-size: 16px;
            color: #666;
        }

        .withdraw-record .pending {
            color: #f39c12;
        }

        .withdraw-record .success {
            color: #43a047;
        }

        .withdraw-record .rejected {
            color: #e74c3c;
        }

        .no-record {
            font-size: 16px;
            color: #888;
            text-align: center;
            padding: 20px 0;
        }

        /* Bottom Navigation Styling */
        .footer {
            text-align: center;
            padding: 15px;
            background-color: #ffffff;
            border-top: 1px solid #ddd;
            position: fixed;
            width: 100%;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.1);
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
            font-size: 13px;
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
        <img src="logos.png" alt="Iberdrola España">
        <img src="icon.png" class="icon" alt="Icon">
    </div>

    <!-- Withdraw Section -->
    <div class="withdraw-container">
        <h2>Withdraw Funds</h2>

        <div class="balance">
            <p class="id">ID <?php echo htmlspecialchars($mobile_number); ?></p>
            <p>Balance: ₱<?php echo number_format($balance, 2); ?></p>
            <p>Tax Fee: 0%</p> <!-- Tax fee set to 0% -->
        </div>

        <!-- Display Bank Card Information -->
        <?php if (!empty($bank_account_number)): ?>
            <div class="bank-card-info">
                <span>Bank Card: <?php echo htmlspecialchars($bank_account_number); ?></span>
                <a href="bank_card.php" class="edit">Edit</a>
            </div>

            <!-- Withdrawal Form -->
            <form class="withdraw-form" method="post" action="">
                <input type="number" name="withdraw_amount" placeholder="Enter the withdrawal amount" min="50" required>
                <input type="submit" value="Submit">
            </form>

            <?php if (isset($message)): ?>
                <p class='message'><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

        <?php else: ?>
            <a href="bank_card.php" class="bank-link">Click to link a bank account</a>
        <?php endif; ?>
    </div>

    <!-- Withdrawal Record Section -->
    <div class="withdraw-record">
        <h4>Withdrawal Record</h4>
        <table>
            <tr>
                <th>Amount</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
            <?php if (count($withdrawals) > 0): ?>
                <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td>₱<?php echo number_format($withdrawal['amount'], 2); ?></td>
                        <td class="<?php echo $withdrawal['status'] == 1 ? 'success' : ($withdrawal['status'] == 2 ? 'pending' : 'rejected'); ?>">
                            <?php echo $withdrawal['status'] == 1 ? 'Success' : ($withdrawal['status'] == 2 ? 'Pending' : 'Rejected'); ?>
                        </td>
                        <td><?php echo date('Y/m/d H:i:s', strtotime($withdrawal['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="no-record">No record</td>
                </tr>
            <?php endif; ?>
        </table>
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
