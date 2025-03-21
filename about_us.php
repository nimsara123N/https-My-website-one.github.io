<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Delor</title>
    <!-- Import Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            font-family: 'Poppins', sans-serif;
            height: 100%;
            margin: 0;
            padding-top: 70px; /* To avoid the content going under the fixed header */
            background-color: #f4f4f9;
        }

        /* Header */
        .header {
            background-color: #ffffff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header img {
            height: 40px;
        }

        .header .icon {
            font-size: 24px;
            color: black;
            cursor: pointer;
        }

        /* Main Content */
        .content {
            display: flex;
            flex-direction: column;
            padding: 30px;
            flex-grow: 1;
            max-width: 1200px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .iframe-container {
            flex: 1;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .iframe-container iframe {
            border: none;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        /* Bottom Navigation */
        .footer {
            background-color: #fff;
            border-top: 1px solid #ddd;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            bottom: 0;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }

        .footer div {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #555;
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

        .footer div.active {
            color: #4CAF50;
        }

        /* Delor Investment Info */
        .delor-info {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .delor-info h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 500;
            color: #1c1c1e;
            margin-bottom: 10px;
        }

        .delor-info p {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 20px;
        }

        .delor-info p:last-child {
            margin-bottom: 0;
        }

        /* Buttons */
        .btn-primary {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background-color: #45a047;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <img src="logos.png" alt="Delor Logo">
        <i class="far fa-paper-plane icon"></i>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Delor Investment Information -->
        <div class="delor-info">
            <h2>Welcome to Delor Investment</h2>
            <p>
                Delor Investment is a trusted and leading platform offering sustainable financial growth opportunities. Whether you are a 
                beginner or experienced investor, our premium VIP plans are designed to meet your financial goals. With over 3 years of 
                successful operations in the Philippines, we pride ourselves on offering transparent and secure investment solutions.
            </p>
            <p>
                Our platform focuses on providing realistic returns, flexible plans, and 0% tax fees on withdrawals, ensuring that every investor 
                retains full control of their earnings. Begin your journey with a minimum deposit of just ₱200, and enjoy seamless withdrawals 
                with a minimum amount of ₱100.
            </p>
            <p>
                Delor Investment prioritizes your security, with cutting-edge technology that protects your investments, ensuring peace of mind. 
                Our expert team and 24/7 customer support are here to guide you at every step.
            </p>
            <p>
                Join thousands of satisfied investors today and secure your financial future with Delor. Together, let’s build a path toward long-term success.
            </p>
            <button class="btn-primary">Get Started Now</button>
        </div>

        <!-- Website View (iframe) -->
        <div class="iframe-container">
            <iframe src="proxy.php"></iframe>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="footer">
        <div class="active">
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
