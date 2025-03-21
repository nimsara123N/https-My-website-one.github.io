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

// Fetch the user's balance
$query_balance = "SELECT balance FROM users WHERE id = ?";
$stmt_balance = $conn->prepare($query_balance);

if (!$stmt_balance) {
    die("Prepare failed: " . $conn->error);
}

$stmt_balance->bind_param("i", $user_id);

if (!$stmt_balance->execute()) {
    die("Execute failed: " . $stmt_balance->error);
}

$result_balance = $stmt_balance->get_result();

if ($result_balance->num_rows === 0) {
    die("User balance not found.");
}

$user_balance = $result_balance->fetch_assoc()['balance'];

// Initialize total paid and today's return
$total_paid = 0;
$todays_return = 0;

// Fetch the user's activated orders and calculate total paid and today's return
$query_activated = "SELECT orders.id, orders.price AS order_price, orders.total_paid, orders.created_at, products.daily_return, products.product_name 
                    FROM orders
                    JOIN products ON orders.product_id = products.id
                    WHERE orders.user_id = ? AND orders.status = 1";  // Activated orders have status 1
$stmt_activated = $conn->prepare($query_activated);

if (!$stmt_activated) {
    die("Prepare failed: " . $conn->error);
}

$stmt_activated->bind_param("i", $user_id);

if (!$stmt_activated->execute()) {
    die("Execute failed: " . $stmt_activated->error);
}

$result_activated = $stmt_activated->get_result();

$activated_orders = [];
while ($row = $result_activated->fetch_assoc()) {
    $activated_orders[] = $row;
    // Add to total paid
    $total_paid += $row['total_paid'];
    
    // Calculate today's return using the `daily_return` from the `products` table
    $daily_return = ($row['order_price'] * $row['daily_return']) / 100;
    $todays_return += $daily_return;
}

// Fetch the user's unactivated orders (all statuses)
$query_unactivated = "SELECT recharge_history.trx, recharge_history.recharge_amount, recharge_history.transaction_date, recharge_history.status 
                      FROM recharge_history 
                      WHERE recharge_history.user_id = ?";
$stmt_unactivated = $conn->prepare($query_unactivated);

if (!$stmt_unactivated) {
    die("Prepare failed: " . $conn->error);
}

$stmt_unactivated->bind_param("i", $user_id);

if (!$stmt_unactivated->execute()) {
    die("Execute failed: " . $stmt_unactivated->error);
}

$result_unactivated = $stmt_unactivated->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Premium Design</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7fafc;
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
        .balance-card {
            background: #eaf6ed;
            padding: 25px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
            margin-bottom: 20px;
            text-align: center;
        }
        .balance-card h2 {
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #1a7f37;
        }
        .balance-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .balance-info div {
            text-align: center;
            flex: 1;
        }
        .balance-info div:not(:last-child) {
            border-right: 1px solid #e5e5e5;
        }
        .balance-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .balance-info .highlight {
            font-size: 16px;
            font-weight: bold;
            color: #1a7f37;
        }
        .tab-buttons {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 20px;
        }
        .tab-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 0 5px;
        }
        .activated {
            background-color: #1a7f37;
            color: white;
        }
        .unactivated {
            background-color: #ffffff;
            color: #1a7f37;
            border: 2px solid #1a7f37;
        }
        .order-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 0px 10px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            max-width: 500px;
            width: 100%;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
        }
        .order-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .order-header p {
            font-size: 12px;
            color: #666;
        }

        .order-summary {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .order-summary div {
            text-align: left;
            margin-bottom: 10px;
        }
        .order-summary p {
            font-size: 14px;
            margin: 0;
        }
        .highlight {
            font-weight: bold;
            color: #1a7f37;
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
        <img src="logos.png" alt="Iberdrola España">
        <img src="icon.png" class="icon" alt="Icon">
    </div>

    <!-- Balance Card Section -->
    <div class="balance-card">
        <h2>ID <?php echo htmlspecialchars($user_id); ?></h2>
        <p class="highlight">₱<?php echo number_format($user_balance, 2); ?></p>

        <div class="balance-info">
            <div>
                <p class="highlight">₱<?php echo number_format($total_paid, 2); ?></p>
                <p>Total Paid</p>
            </div>
            <div>
                <p class="highlight">₱<?php echo number_format($todays_return, 2); ?></p>
                <p>Today's Return</p>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="tab-buttons">
        <button id="activated-tab" class="activated" onclick="showActivated()">Activated Orders</button>
        <button id="unactivated-tab" class="unactivated" onclick="showUnactivated()">Unactivated Orders</button>
    </div>

    <!-- Activated Orders Section -->
    <div class="content" id="activated-orders">
        <?php if (count($activated_orders) > 0): ?>
            <?php foreach ($activated_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                        <p><?php echo htmlspecialchars($order['created_at']); ?></p>
                    </div>

                    <div class="order-summary">
                        <div>
                            <p>Unit Price</p>
                            <p class="highlight">₱<?php echo number_format($order['order_price'], 2); ?></p>
                        </div>
                        <div>
                            <p>Daily Return</p>
                            <p class="highlight">₱<?php echo number_format(($order['order_price'] * $order['daily_return']) / 100, 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No activated orders found.</p>
        <?php endif; ?>
    </div>

    <!-- Unactivated Orders Section -->
    <div class="content" id="unactivated-orders" style="display: none;">
        <?php if ($result_unactivated->num_rows > 0): ?>
            <?php while ($row = $result_unactivated->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3>REF: <?php echo htmlspecialchars($row['trx']); ?></h3>
                        <p><?php echo htmlspecialchars($row['transaction_date']); ?></p>
                    </div>

                    <div class="order-summary">
                        <div>
                            <p>Recharge Amount</p>
                            <p class="highlight">₱<?php echo number_format($row['recharge_amount'], 2); ?></p>
                        </div>
                        <div>
                            <?php if ($row['status'] == 2): ?>
                                <p>Status</p>
                                <p class="highlight" style="color: #ffa000;">Pending</p>
                            <?php elseif ($row['status'] == 1): ?>
                                <p>Status</p>
                                <p class="highlight" style="color: #1a7f37;">Success</p>
                            <?php elseif ($row['status'] == 3): ?>
                                <p>Status</p>
                                <p class="highlight" style="color: #e63946;">Rejected</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No unactivated orders found.</p>
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

    <script>
        function showActivated() {
            document.getElementById('activated-orders').style.display = 'block';
            document.getElementById('unactivated-orders').style.display = 'none';
            document.getElementById('activated-tab').classList.add('activated');
            document.getElementById('unactivated-tab').classList.remove('activated');
        }

        function showUnactivated() {
            document.getElementById('unactivated-orders').style.display = 'block';
            document.getElementById('activated-orders').style.display = 'none';
            document.getElementById('unactivated-tab').classList.add('activated');
            document.getElementById('activated-tab').classList.remove('activated');
        }
    </script>
</body>
</html>

<?php
$stmt_balance->close();
$stmt_activated->close();
$stmt_unactivated->close();
$conn->close();
?>
