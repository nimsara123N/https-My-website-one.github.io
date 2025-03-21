<?php
session_start();
include('db.php');

// Enable error reporting to catch issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user'];

// Fetch the user's balance and referral information from the database
$query = "SELECT balance, referral_id, referred_by FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$balance = $user['balance'];
$referred_by = $user['referred_by']; // Get the referral ID (who referred this user)

// Generate referral link
$referral_link = "https://delor.sbs/?ref=" . $user_id;

// Calculate the number of referrals for each level

// Level 1: Direct referrals by this user
$query_level_1 = "SELECT COUNT(*) AS level_1_count FROM users WHERE referred_by = ?";
$stmt = $conn->prepare($query_level_1);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_level_1 = $stmt->get_result();
$level_1_data = $result_level_1->fetch_assoc();
$level_1_count = $level_1_data['level_1_count'];

// Level 2: Referrals by the users referred by this user (Level 1 referrals)
$query_level_2 = "SELECT COUNT(*) AS level_2_count FROM users WHERE referred_by IN (SELECT id FROM users WHERE referred_by = ?)";
$stmt = $conn->prepare($query_level_2);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_level_2 = $stmt->get_result();
$level_2_data = $result_level_2->fetch_assoc();
$level_2_count = $level_2_data['level_2_count'];

// Level 3: Referrals by Level 2 referrals
$query_level_3 = "SELECT COUNT(*) AS level_3_count FROM users WHERE referred_by IN (SELECT id FROM users WHERE referred_by IN (SELECT id FROM users WHERE referred_by = ?))";
$stmt = $conn->prepare($query_level_3);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_level_3 = $stmt->get_result();
$level_3_data = $result_level_3->fetch_assoc();
$level_3_count = $level_3_data['level_3_count'];

// Fetch or initialize team-related commissions and levels
$query_team = "SELECT * FROM teams WHERE user_id = ?";
$stmt = $conn->prepare($query_team);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_team = $stmt->get_result();
$team = $result_team->fetch_assoc();

$total_commission = 0;
$todays_commission = 0;
$current_date = date("Y-m-d");

// If no record exists, initialize it
if (!$team) {
    $query_insert = "INSERT INTO teams (user_id, level_1_count, level_2_count, level_3_count, total_commission, todays_commission, last_updated) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query_insert);
    $stmt->bind_param("iiiiiss", $user_id, $level_1_count, $level_2_count, $level_3_count, $total_commission, $todays_commission, $current_date);
    $stmt->execute();
} else {
    $total_commission = $team['total_commission'];
    $todays_commission = $team['todays_commission'];
    $last_updated = $team['last_updated'];

    // Check if today's date is different from the last updated date
    if ($last_updated != $current_date) {
        // Reset today's commission
        $todays_commission = 0;
        $query_update = "UPDATE teams SET todays_commission = ?, last_updated = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query_update);
        $stmt->bind_param("dsi", $todays_commission, $current_date, $user_id);
        $stmt->execute();
    }

    $query_update = "UPDATE teams SET level_1_count = ?, level_2_count = ?, level_3_count = ?, total_commission = ?, todays_commission = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query_update);
    $stmt->bind_param("iiiiii", $level_1_count, $level_2_count, $level_3_count, $total_commission, $todays_commission, $user_id);
    $stmt->execute();
}

// Fetch updated team-related data
$query_team_updated = "SELECT * FROM teams WHERE user_id = ?";
$stmt = $conn->prepare($query_team_updated);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_team_updated = $stmt->get_result();
$team_updated = $result_team_updated->fetch_assoc();

$total_commission = $team_updated['total_commission'];
$todays_commission = $team_updated['todays_commission'];

// Function to record commission for the referred user
function record_commission($referred_user_id, $commission_amount, $level) {
    global $conn;

    // Insert the commission into the `commissions` table
    $query_insert_commission = "INSERT INTO commissions (user_id, level, amount) VALUES (?, ?, ?)";
    $stmt_insert_commission = $conn->prepare($query_insert_commission);
    $stmt_insert_commission->bind_param("iid", $referred_user_id, $level, $commission_amount);
    $stmt_insert_commission->execute();

    // Update total and today's commission in the `teams` table
    $query_check = "SELECT total_commission, todays_commission FROM teams WHERE user_id = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $referred_user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $team_data = $result_check->fetch_assoc();

    if ($team_data) {
        // Update the existing commission data
        $total_commission = $team_data['total_commission'] + $commission_amount;
        $todays_commission = $team_data['todays_commission'] + $commission_amount;

        $query_update = "UPDATE teams SET total_commission = ?, todays_commission = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("ddi", $total_commission, $todays_commission, $referred_user_id);
        $stmt_update->execute();
    } else {
        // Insert new commission record into `teams`
        $query_insert_team = "INSERT INTO teams (user_id, total_commission, todays_commission) VALUES (?, ?, ?)";
        $stmt_insert_team = $conn->prepare($query_insert_team);
        $stmt_insert_team->bind_param("idd", $referred_user_id, $commission_amount, $commission_amount);
        $stmt_insert_team->execute();
    }
}

// Close connection (no need to close $stmt because it's reused multiple times)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Team</title>
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

        .header {
            background-color: #fff;
            padding: 20px;
            text-align: center;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .team-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            text-align: center;
            margin-top: 20px;
        }

        h2 {
            font-size: 22px;
            color: #222;
            margin-bottom: 10px;
        }

        .balance {
            background-color: #eaf6ed;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid #d4e9d6;
        }

        .balance p {
            margin: 5px 0;
            font-size: 18px;
            color: #1a7f37;
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
            margin-bottom: 20px;
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

        .team-section {
            margin-top: 30px;
        }

        .team-section p {
            font-size: 18px;
            font-weight: bold;
        }

        .commission-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f0f4f9;
            padding: 10px 20px;
            border-radius: 10px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .level {
            display: flex;
            justify-content: space-between;
            background-color: #f5f7fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .level h4 {
            font-size: 18px;
            color: #333;
        }

        .level p {
            margin: 0;
            color: #333;
        }

        .detail-button {
            background-color: #4caf50;
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
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
        <img src="logos.png" alt="Logo">
        <h1>My team</h1>
        <img src="icon.png" class="icon" alt="Menu">
    </div>

    <!-- Team Container -->
    <div class="team-container">
        <div class="balance">
            <p>ID <?php echo $user_id; ?></p>
            <p>₱<?php echo number_format($balance, 2); ?></p>
            <p>Referred By ID: <?php echo $referred_by ? $referred_by : 'None'; ?></p>
        </div>

        <div class="referral-link">
            <input type="text" value="<?php echo $referral_link; ?>" readonly>
            <img src="send.png" alt="Copy" onclick="copyReferralLink()">
        </div>

        <div class="team-section">
            <p>Total Commission: ₱<?php echo number_format($total_commission, 2); ?></p>
            <p>Today's Commission: ₱<?php echo number_format($todays_commission, 2); ?></p>

            <!-- Level 1 -->
            <div class="level">
                <div>
                    <h4>Level 1 - 70%</h4>
                    <p>Number: <?php echo $level_1_count; ?></p>
                </div>
                <a href="level_detail.php?level=1" class="detail-button">Detail</a>
            </div>

            <!-- Level 2 -->
            <div class="level">
                <div>
                    <h4>Level 2 - 2%</h4>
                    <p>Number: <?php echo $level_2_count; ?></p>
                </div>
                <a href="level_detail.php?level=2" class="detail-button">Detail</a>
            </div>

            <!-- Level 3 -->
            <div class="level">
                <div>
                    <h4>Level 3 - 1%</h4>
                    <p>Number: <?php echo $level_3_count; ?></p>
                </div>
                <a href="level_detail.php?level=3" class="detail-button">Detail</a>
            </div>
        </div>
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
        function copyReferralLink() {
            var referralLink = document.querySelector('.referral-link input');
            referralLink.select();
            document.execCommand("copy");
            alert("Referral link copied to clipboard");
        }
    </script>
</body>
</html>
