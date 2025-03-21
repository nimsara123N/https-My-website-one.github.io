<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user'];

// Fetch the user's balance from the database
$query = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Set a default balance if none is found
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $balance = $row['balance'];
} else {
    $balance = 0.00; // Set a default balance if the user is not found
}

$referral_link = "https://delor.sbs/?ref=$user_id";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Delor</title>
    <!-- Import Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f5f7;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-bottom: 100px;
        }
        .header {
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 20px;
            padding-right: 20px;
        }
        .header img {
            height: 40px;
        }
        .header .icon {
            height: 30px;
            cursor: pointer;
        }
        .content {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .user-card {
            background-color: #ffffff;
            border-radius: 20px;
            margin: 20px 0;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        .balance-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
        }
        .balance-section img {
            width: 50px;
        }
        .balance-info {
            text-align: center;
        }
        .balance-info h3 {
            margin: 0;
            color: #2e7d32;
            font-size: 22px;
        }
        .balance-info p {
            font-size: 32px;
            font-weight: bold;
            margin: 5px 0;
            color: #333333;
        }
        .referral-link {
            background-color: #e6f9f0;
            padding: 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        .referral-link input {
            border: none;
            background-color: transparent;
            width: 80%;
            font-size: 16px;
            color: #4CAF50;
            outline: none;
        }
        .referral-link img {
            width: 20px;
            cursor: pointer;
        }
        .menu-list {
            background-color: #ffffff;
            border-radius: 10px;
            margin: 20px;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            list-style: none;
            width: 100%;
            max-width: 600px;
        }
        .menu-list li {
            padding: 15px 20px;
            border-bottom: 1px solid #eeeeee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s ease, transform 0.3s ease;
            cursor: pointer;
        }
        .menu-list li:hover {
            background-color: #f9f9f9;
            transform: translateX(10px);
        }
        .menu-list li img {
            width: 18px;
        }
        .menu-list li a {
            text-decoration: none;
            color: #333;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .menu-list li a span {
            font-size: 16px;
        }
        .menu-list li:last-child {
            border-bottom: none;
        }
        /* Bottom Navigation */
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
        .submenu {
            display: none;
            padding-left: 20px;
            background-color: #fafafa;
            border-left: 2px solid #e0e0e0;
        }
        .submenu li {
            border-bottom: none;
            padding: 10px 20px;
        }
        .submenu li a {
            text-decoration: none;
            color: inherit;
        }
        .menu-list .active .submenu {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <img src="logos.png" alt="Iberdrola España">
        <img src="icon.png" class="icon" alt="Icon">
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- User Card Section -->
        <div class="user-card">
            <div class="balance-section">
                <img src="wallet.png" alt="Balance">
                <div class="balance-info">
                    <h3>ID: <?php echo htmlspecialchars($user_id); ?></h3>
                    <p>₱<?php echo number_format($balance, 2); ?></p>
                </div>
                <img src="money.png" alt="Money">
            </div>
            
            <div class="referral-link">
                <input type="text" value="<?php echo htmlspecialchars($referral_link); ?>" readonly>
                <img src="send.png" alt="Copy" onclick="copyReferralLink()">
            </div>
        </div>

        <!-- Menu List Section -->
        <ul class="menu-list">
            <li onclick="toggleMenu('wallet-submenu', this)">
                <span>Wallet</span>
                <img src="arrow-right.png" alt="Arrow">
            </li>
            <ul id="wallet-submenu" class="submenu">
                <li><a href="withdraw.php">Withdraw</a></li>
                <li><a href="bank_card.php">Bank card</a></li>
            </ul>
            <li>
                <a href="reward_list.php">
                    <span>Reward List</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="share_link.php">
                    <span>Share Link</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="team.php">
                    <span>Team</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="faq.php">
                    <span>FAQ</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="https://t.me/">
                    <span>Official Group</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="https://t.me/">
                    <span>Official Channel</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <span>Log Out</span>
                    <img src="arrow-right.png" alt="Arrow">
                </a>
            </li>
        </ul>
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
            <a href="menu.php" class="active">
                <img src="menu.png" alt="Menu">
                <span>Menu</span>
            </a>
        </div>
    </div>

    <script>
        function toggleMenu(submenuId, element) {
            var submenu = document.getElementById(submenuId);
            var isActive = submenu.style.display === 'block';
            
            if (isActive) {
                submenu.style.display = 'none';
                element.classList.remove('active');
            } else {
                submenu.style.display = 'block';
                element.classList.add('active');
            }
        }

        function copyReferralLink() {
            var referralLink = document.querySelector('.referral-link input');
            referralLink.select();
            referralLink.setSelectionRange(0, 99999);  // For mobile devices

            navigator.clipboard.writeText(referralLink.value).then(function() {
                alert("Referral link copied to clipboard!");
            }, function(err) {
                alert("Failed to copy the referral link: " + err);
            });
        }
    </script>
</body>
</html>
