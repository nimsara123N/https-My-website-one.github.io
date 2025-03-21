<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Fetch FAQ items from the database (or hardcoded)
$faqs = [
    ["question" => "I deposited, why didn’t I get my order?", "answer" => "Enter 'Payment Record' to upload a screenshot of this payment and enter other info in that page if required and submit. Your application will be under review and you’ll get your order recovered within 24h."],
    ["question" => "Why my withdrawal is still processing?", "answer" => "The withdrawal is usually transferred automatically within minutes. At times, it might be delayed for a little while but no more than 24h. If you still haven’t received your withdrawal after 24h, you shall check your bank info carefully and correct it if anything wrong, or reach out to our support for assistance."],
    ["question" => "Why my withdrawal got failed?", "answer" => "Normally this won’t happen as long as your bank info is entered correctly. We suggest you carefully recheck your bank info and correct it if anything wrong. Swiping to another bank card to withdraw is also recommended if it still fails. If none of these above make it, please contact support for assistance."]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Iberdrola España</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        .header h1 {
            font-size: 18px;
            color: #004d40;
            font-weight: 600;
            flex: 1;
            text-align: center;
        }
        .faq-list {
            width: 100%;
            max-width: 600px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }
        .faq-item {
            border-bottom: 1px solid #eeeeee;
        }
        .faq-item:last-child {
            border-bottom: none;
        }
        .faq-question {
            padding: 15px 20px;
            font-size: 16px;
            color: #004d40;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .faq-answer {
            padding: 15px 20px;
            display: none;
            background-color: #f9f9f9;
            color: #333333;
            font-size: 14px;
            border-top: 1px solid #eeeeee;
        }
        .faq-answer.show {
            display: block;
        }
        .faq-question:hover {
            background-color: #f2f2f2;
        }
        .icon {
            transform: rotate(0deg);
            transition: transform 0.3s ease;
        }
        .icon.rotate {
            transform: rotate(90deg);
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
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="back.png" alt="Back" onclick="window.history.back();">
        <h1>FAQ</h1>
        <img src="icon.png" alt="Support">
    </div>

    <!-- FAQ List -->
    <div class="faq-list">
        <?php foreach ($faqs as $index => $faq): ?>
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(<?php echo $index; ?>)">
                <span><?php echo $faq['question']; ?></span>
                <span class="icon" id="icon-<?php echo $index; ?>">&#x25B6;</span>
            </div>
            <div class="faq-answer" id="answer-<?php echo $index; ?>">
                <?php echo $faq['answer']; ?>
            </div>
        </div>
        <?php endforeach; ?>
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
        function toggleFAQ(index) {
            var answer = document.getElementById('answer-' + index);
            var icon = document.getElementById('icon-' + index);
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                icon.classList.remove('rotate');
            } else {
                answer.classList.add('show');
                icon.classList.add('rotate');
            }
        }
    </script>
</body>
</html>