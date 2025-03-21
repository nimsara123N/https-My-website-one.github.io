<?php
session_start();
include('db.php');

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user'];
$level = isset($_GET['level']) ? intval($_GET['level']) : 1; // Default level is 1 if not provided

// Define SQL query based on referral level
$referral_query = getReferralQueryByLevel($level);

// Prepare and execute the SQL query
$stmt = $conn->prepare($referral_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$results = $stmt->get_result();

$referrals = [];
$total_commission = 0;
$todays_commission = 0;

// Process fetched data and calculate total commissions
while ($row = $results->fetch_assoc()) {
    $referrals[] = $row;
    $total_commission += $row['total_commission'];
    $todays_commission += $row['todays_commission'];
}

$total_referrals = count($referrals);
$stmt->close();

// Function to generate the SQL query based on the referral level
function getReferralQueryByLevel($level) {
    switch ($level) {
        case 2:
            return "
                SELECT users.id, users.mobile_number, 
                       COALESCE(teams.total_commission, 0) AS total_commission, 
                       COALESCE(teams.todays_commission, 0) AS todays_commission 
                FROM users 
                LEFT JOIN teams ON users.id = teams.user_id
                WHERE users.referred_by IN (SELECT id FROM users WHERE referred_by = ?)";
        case 3:
            return "
                SELECT users.id, users.mobile_number, 
                       COALESCE(teams.total_commission, 0) AS total_commission, 
                       COALESCE(teams.todays_commission, 0) AS todays_commission 
                FROM users 
                LEFT JOIN teams ON users.id = teams.user_id
                WHERE users.referred_by IN (
                    SELECT id FROM users 
                    WHERE referred_by IN (SELECT id FROM users WHERE referred_by = ?)
                )";
        default:
            return "
                SELECT users.id, users.mobile_number, 
                       COALESCE(teams.total_commission, 0) AS total_commission, 
                       COALESCE(teams.todays_commission, 0) AS todays_commission 
                FROM users 
                LEFT JOIN teams ON users.id = teams.user_id
                WHERE users.referred_by = ?";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f5f7; display: flex; flex-direction: column; min-height: 100vh; padding-bottom: 100px; }
        .header { background-color: #fff; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); position: sticky; top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; }
        .header img { height: 40px; }
        .content { flex: 1; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .user-card, .level-card, .table-container { background-color: #fff; border-radius: 20px; padding: 20px; margin: 20px 0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 600px; }
        .user-card { text-align: center; }
        .level-card { display: flex; justify-content: space-between; align-items: center; background-color: #edf8f2; }
        .level-card .info { font-size: 18px; color: #2e7d32; }
        .level-card button { background-color: #4CAF50; color: white; border: none; padding: 8px 15px; border-radius: 30px; font-size: 16px; cursor: pointer; display: flex; align-items: center; }
        .level-card button img { margin-left: 8px; height: 20px; }
        .table-header, .table-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .table-header { color: #555; font-size: 16px; font-weight: bold; }
        .table-row { color: #333; font-size: 16px; }
        .no-record { text-align: center; padding: 30px; color: #888; }
        .no-record img { width: 50px; margin-bottom: 10px; }
        .footer { text-align: center; padding: 10px; background-color: #fff; border-top: 1px solid #ddd; position: fixed; width: 100%; bottom: 0; display: flex; justify-content: space-around; box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1); }
        .footer div { display: flex; flex-direction: column; align-items: center; color: #555; cursor: pointer; transition: color 0.3s ease; }
        .footer div a { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit; }
        .footer div img { width: 30px; height: 30px; }
        .footer div span { font-size: 12px; margin-top: 5px; }
        .footer div:hover { color: #4CAF50; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="logo.png" alt="Iberdrola España">
        <img src="icon.png" class="icon" alt="Icon">
    </div>

    <!-- Content Section -->
    <div class="content">
        <div class="user-card">
            <h2>My Team</h2>
            <p>You earn <strong>30%</strong> of your level 1 referrals' recharge amount as your bonus,<br>
               <strong>2%</strong> of your level 2 referrals' recharge amount as your bonus,<br>
               <strong>1%</strong> of your level 3 referrals' recharge amount as your bonus.</p>
        </div>

        <div class="level-card">
            <div class="info">
                <p>Level - <?php echo htmlspecialchars($level); ?></p>
            </div>
            <button>Invite <img src="send.png" alt="Invite"></button>
        </div>

        <?php if ($total_referrals > 0): ?>
        <div class="table-container">
            <div class="table-header">
                <span>ID</span>
                <span>Mobile</span>
                <span>Total</span>
                <span>Today</span>
            </div>
            <?php foreach ($referrals as $referral): ?>
            <div class="table-row">
                <span><?php echo htmlspecialchars($referral['id']); ?></span>
                <span><?php echo htmlspecialchars($referral['mobile_number']); ?></span>
                <span>₱<?php echo number_format($referral['total_commission'], 2); ?></span>
                <span>₱<?php echo number_format($referral['todays_commission'], 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="table-container">
            <div class="no-record">
                <img src="no-record.png" alt="No record">
                <p>No referrals yet.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="user-card">
            <h3>Total Commission: ₱<?php echo number_format($total_commission, 2); ?></h3>
            <h3>Today's Commission: ₱<?php echo number_format($todays_commission, 2); ?></h3>
        </div>
    </div>

    <!-- Footer Section -->
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

<?php
$conn->close();
?>
