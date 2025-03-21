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

// Fetch the user information from the database
$query = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user was found
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $balance = $row['balance'];
} else {
    // User not found, handle the error or redirect to logout
    header("Location: logout.php");
    exit();
}

// Example referral link based on user ID
$referral_link = "https://delor.sbs/?ref=$user_id";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Delor</title>
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
        .user-card {
            background-color: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.3s ease;
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
            font-size: 20px;
        }
        .balance-info p {
            font-size: 30px;
            font-weight: bold;
            margin: 5px 0;
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
        .referral-link span {
            font-weight: bold;
            color: #2e7d32;
            font-size: 16px;
        }
        .referral-link input {
            border: none;
            background: none;
            width: 80%;
            font-size: 16px;
            color: #4CAF50;
            text-overflow: ellipsis;
            outline: none;
        }
        .referral-link img {
            width: 30px;
            cursor: pointer;
        }
        .product-list {
            width: 90%;
            max-width: 1000px;
            margin: 20px 0;
        }
        .product-item {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            transition: transform 0.3s ease;
        }
        .product-item:hover {
            transform: translateY(-5px);
        }
        .product-image {
            flex: 0 0 100px;
            margin-right: 20px;
        }
        .product-image img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .product-details {
            flex: 1;
            padding: 0 20px;
            text-align: left;
        }
        .product-details h4 {
            color: #2e7d32;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .product-details p {
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .product-details .highlight {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
        }
        .return-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 10px 0;
        }
        .return-section {
            background-color: #e6f9f0;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            width: 48%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .return-percentage {
            color: #2e7d32;
            font-size: 22px;
            font-weight: bold;
        }
        .return-value {
            font-size: 16px;
            color: #555;
            margin-top: 5px;
        }
        .product-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-top: 20px;
        }
        .btn-detail, .btn-buy {
            width: 48%;
            padding: 12px;
            border-radius: 30px;
            font-size: 18px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-detail {
            background-color: #fff;
            color: #4CAF50;
            border: 2px solid #4CAF50;
        }
        .btn-detail:hover {
            background-color: #4CAF50;
            color: #fff;
        }
        .btn-buy {
            background-color: #4CAF50;
            color: white;
            border: none;
        }
        .btn-buy:hover {
            background-color: #45a049;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 100px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); }
            to { transform: translateY(0); }
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-close:hover,
        .modal-close:focus {
            color: #000;
        }
        .modal-header {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        .product-info {
            background-color: #e6f9f0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .product-info h4 {
            font-size: 18px;
            color: #2e7d32;
            margin: 0;
        }
        .product-info p {
            margin: 5px 0;
            font-size: 16px;
            color: #555;
        }
        .modal-body p {
            font-size: 18px;
            color: #555;
            margin: 10px 0;
        }
        .modal-body .highlight {
            font-weight: bold;
            color: #2e7d32;
        }
        .payment-method {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .payment-method label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .payment-method input[type="radio"] {
            appearance: none;
            background-color: #fff;
            border: 2px solid #4CAF50;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            outline: none;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }
        .payment-method input[type="radio"]:checked {
            background-color: #4CAF50;
        }
        .btn-place-order {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            font-size: 20px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .btn-place-order:hover {
            background-color: #45a049;
        }
        
        /* Fullscreen Detail View */
        .detail-view {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff;
            overflow-y: auto;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        .detail-view.active {
            display: block;
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-header h2 {
            font-size: 22px;
            color: #333;
        }
        .detail-content {
            margin-top: 20px;
            text-align: center;
        }
        .detail-content img {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .detail-content h3 {
            color: #2e7d32;
            font-size: 24px;
        }
        .detail-info {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .detail-info div {
            text-align: center;
            font-size: 18px;
            color: #555;
        }
        .detail-info div span {
            display: block;
            font-size: 20px;
            color: #2e7d32;
            font-weight: bold;
        }
        .detail-description {
            padding: 20px;
            text-align: left;
            font-size: 16px;
            color: #555;
            background-color: #f9f9f9;
            border-radius: 10px;
        }
        .btn-back {
            background-color: #fff;
            color: #4CAF50;
            border: 2px solid #4CAF50;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn-back:hover {
            background-color: #4CAF50;
            color: #fff;
        }
        .btn-buy-detail {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            font-size: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn-buy-detail:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="logos.png" alt="Delor"> <!-- Replace with actual logo path -->
        <img src="icon.png" class="icon" alt="Icon"> <!-- Replace with actual icon path -->
    </div>

    <div class="user-card">
        <div class="balance-section">
            <img src="wallet.png" alt="Balance"> <!-- Replace with actual wallet image path -->
            <div class="balance-info">
                <h3>ID: <?php echo $user_id; ?></h3>
                <p>₱<?php echo number_format($balance, 2); ?></p>
            </div>
            <img src="money.png" alt="Money"> <!-- Replace with actual money image path -->
        </div>
        
        <div class="referral-link">
            <span>My link</span>
            <input id="referralLink" type="text" value="<?php echo $referral_link; ?>" readonly>
            <img id="copyButton" src="send.png" alt="Send" style="cursor: pointer;"> <!-- Replace with actual send image path -->
        </div>
    </div>

    <div class="product-list">
        <?php
        $query = "SELECT * FROM products"; // Adjust the query to match your table structure
        $result = $conn->query($query);
        while($row = $result->fetch_assoc()): ?>
            <div class="product-item">
                <div class="product-image">
                    <img src="wind_farm.jpg" alt="<?php echo $row['product_name']; ?>"> <!-- Replace with actual image path -->
                </div>
                <div class="product-details">
                    <h4><?php echo $row['product_name']; ?></h4>
                    <p>Duration: <span class="highlight"><?php echo $row['duration']; ?> days</span></p>
                    <div class="return-info">
                        <div class="return-section">
                            <h5>Total Return</h5>
                            <p class="return-percentage"><?php echo $row['total_return']; ?>%</p>
                            <p class="return-value">₱<?php echo number_format($row['price'] * ($row['total_return'] / 76), 2); ?></p>
                        </div>
                        <div class="return-section">
                            <h5>Daily Return</h5>
                            <p class="return-percentage"><?php echo $row['daily_return']; ?>%</p>
                            <p class="return-value">₱<?php echo number_format(($row['price'] * ($row['daily_return'] / 100)), 2); ?></p>
                        </div>
                    </div>
                    <p>Price: ₱<?php echo number_format($row['price'], 2); ?></p>
                </div>
                <div class="product-actions">
                    <button class="btn-detail" data-product-name="<?php echo $row['product_name']; ?>" data-image="wind_farm.jpg" data-duration="<?php echo $row['duration']; ?>" data-total-return="<?php echo $row['total_return']; ?>" data-today-return="<?php echo $row['daily_return']; ?>" data-total-return-value="₱<?php echo number_format($row['price'] * ($row['total_return'] / 76), 2); ?>" data-today-return-value="₱<?php echo number_format(($row['price'] * ($row['daily_return'] / 100)), 2); ?>" data-description="The Wikinger offshore wind farm marked Iberdrola’s entry into the German electricity market, where the construction of two other offshore wind farms is underway.">Detail</button>
                    <button class="btn-buy" data-product-id="<?php echo $row['id']; ?>" data-product-name="<?php echo $row['product_name']; ?>" data-total-return="<?php echo $row['total_return']; ?>" data-daily-return="<?php echo $row['daily_return']; ?>" data-price="<?php echo $row['price']; ?>" data-date="<?php echo date('H:i:s d M Y'); ?>">Buy ₱<?php echo number_format($row['price'], 2); ?></button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Modal -->
    <div id="buyModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2 class="modal-header">Bought Product</h2>
            <div class="product-info">
                <h4 id="product-name"></h4>
                <p id="duration"></p>
                <div style="display: flex; justify-content: space-between;">
                    <p>Today Return: <span id="today-return"></span>%</p>
                    <p>Total Return: <span id="total-return"></span>%</p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <p>₱<span id="daily-return-value"></span></p>
                    <p>₱<span id="total-return-value"></span></p>
                </div>
            </div>
            <div class="modal-body">
                <p>Price: ₱<span id="price"></span></p>
                <p>Date: <span id="date"></span></p>
                <div class="payment-method">
                    <label>
                        <input type="radio" name="gateway" value="1" checked>
                        <span class="gateway-label">Gateway 1</span>
                    </label>
                    <label>
                        <input type="radio" name="gateway" value="2">
                        <span class="gateway-label">Gateway 2</span>
                    </label>
                </div>
            </div>
            <button id="placeOrderButton" class="btn-place-order">Place an order</button>
        </div>
    </div>

    <!-- Detail View -->
    <div id="detailView" class="detail-view">
        <div class="detail-header">
            <button class="btn-back">Back</button>
            <h2>Product detail</h2>
        </div>
        <div class="detail-content">
            <img id="detail-image" src="" alt="Product Image">
            <h3 id="detail-product-name"></h3>
            <p id="detail-duration"></p>
            <div class="detail-info">
                <div>
                    <span id="detail-total-return"></span>
                    Total Return
                </div>
                <div>
                    <span id="detail-today-return"></span>
                    Today Return
                </div>
            </div>
            <div class="detail-info">
                <div>
                    <span id="detail-total-return-value"></span>
                    Total Value
                </div>
                <div>
                    <span id="detail-today-return-value"></span>
                    Today's Value
                </div>
            </div>
            <div class="detail-description" id="detail-description">
            </div>
            <button class="btn-buy-detail">Buy</button>
        </div>
    </div>

    <div class="footer">
        <div>
            <a href="dashboard.php">
                <img src="home.png" alt="Home"> <!-- Replace with actual .png path -->
                <span>Home</span>
            </a>
        </div>
        <div>
            <a href="order.php">
                <img src="order.png" alt="Order"> <!-- Replace with actual .png path -->
                <span>Order</span>
            </a>
        </div>
        <div>
            <a href="about_us.php">
                <img src="about_us.png" alt="About Us"> <!-- Replace with actual .png path -->
                <span>About Us</span>
            </a>
        </div>
        <div>
            <a href="menu.php">
                <img src="menu.png" alt="Menu"> <!-- Replace with actual .png path -->
                <span>Menu</span>
            </a>
        </div>
    </div>

    <script>
        // Clipboard copy functionality
        document.getElementById('copyButton').addEventListener('click', function() {
            var referralLink = document.getElementById('referralLink');
            referralLink.select();
            referralLink.setSelectionRange(0, 99999); // For mobile devices

            navigator.clipboard.writeText(referralLink.value).then(function() {
                alert("Referral link copied to clipboard!");
            }, function(err) {
                alert("Failed to copy the referral link: ", err);
            });
        });

        // Modal Elements
        var modal = document.getElementById("buyModal");
        var detailView = document.getElementById("detailView");
        var span = document.getElementsByClassName("modal-close")[0];
        var backBtn = document.querySelector('.btn-back');

        let selectedProductId = null;
        let selectedPrice = null;

        // Show Buy Modal
        document.querySelectorAll('.btn-buy').forEach(function(button) {
            button.onclick = function() {
                var productName = this.getAttribute("data-product-name");
                var totalReturn = this.getAttribute("data-total-return");
                var dailyReturn = this.getAttribute("data-daily-return");
                selectedPrice = this.getAttribute("data-price");
                var date = this.getAttribute("data-date");
                selectedProductId = this.getAttribute("data-product-id");

                document.getElementById("product-name").innerText = productName;
                document.getElementById("today-return").innerText = dailyReturn;
                document.getElementById("total-return").innerText = totalReturn;
                document.getElementById("price").innerText = selectedPrice;
                document.getElementById("date").innerText = date;
                document.getElementById("daily-return-value").innerText = (selectedPrice * (dailyReturn / 100)).toFixed(2);
                document.getElementById("total-return-value").innerText = (selectedPrice * (totalReturn / 76)).toFixed(2);

                modal.style.display = "block";
            }
        });

        // Place order button functionality with dynamic gateway
        document.getElementById('placeOrderButton').onclick = function() {
            // Get the selected gateway value
            var selectedGateway = document.querySelector('input[name="gateway"]:checked').value;
            
            // Determine the URL based on the selected gateway
            var url = selectedGateway === "1" ? 'deposit.php' : 'deposit2.php';

            // Redirect to the appropriate deposit page with parameters
            window.location.href = `${url}?amount=${selectedPrice}&product=${selectedProductId}`;

        }

        // Close Buy Modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Show Detail View
        document.querySelectorAll('.btn-detail').forEach(function(button) {
            button.onclick = function() {
                var productName = this.getAttribute("data-product-name");
                var image = this.getAttribute("data-image");
                var duration = this.getAttribute("data-duration");
                var totalReturn = this.getAttribute("data-total-return");
                var todayReturn = this.getAttribute("data-today-return");
                var totalReturnValue = this.getAttribute("data-total-return-value");
                var todayReturnValue = this.getAttribute("data-today-return-value");
                var description = this.getAttribute("data-description");

                document.getElementById("detail-product-name").innerText = productName;
                document.getElementById("detail-image").src = image;
                document.getElementById("detail-duration").innerText = `Duration: ${duration} days`;
                document.getElementById("detail-total-return").innerText = `${totalReturn}%`;
                document.getElementById("detail-today-return").innerText = `${todayReturn}%`;
                document.getElementById("detail-total-return-value").innerText = totalReturnValue;
                document.getElementById("detail-today-return-value").innerText = todayReturnValue;
                document.getElementById("detail-description").innerText = description;

                detailView.classList.add("active");
            }
        });

        // Close Detail View
        backBtn.onclick = function() {
            detailView.classList.remove("active");
        }
    </script>
</body>
</html>
