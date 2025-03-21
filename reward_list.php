<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch successful shared links from the database for all users
$sql = "SELECT user_id, amount, status FROM shared_links WHERE status = 1";
$result = $conn->query($sql);
$shared_links = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward List</title>
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
            align-items: center;
            min-height: 100vh;
            padding-bottom: 100px;
        }
        .header {
            width: 100%;
            background-color: white;
            padding: 20px;
            text-align: center;
            color: black;
            font-size: 18px;
            font-weight: bold;
            position: fixed;
            top: 0;
            left: 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header .back-button {
            position: absolute;
            left: 10px;
            font-size: 18px;
            background: none;
            border: none;
            color: black;
            cursor: pointer;
        }
        .container {
            background-color: white;
            width: 100%;
            max-width: 600px;
            padding: 20px;
            margin-top: 100px; /* Adjust margin to account for the fixed header */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        table th, table td {
            padding: 15px;
            text-align: left;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f1f1f1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        table td {
            background-color: #fff;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .status-success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Header with Back Button -->
<div class="header">
    <button class="back-button" onclick="history.back();">
        &larr; Back
    </button>
    <span>Reward List</span>
</div>

<div class="container">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($shared_links)): ?>
                <?php foreach ($shared_links as $link): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($link['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($link['amount']); ?></td>
                        <td><span class="status-success">Success</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No successful rewards found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
