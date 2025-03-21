<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user'];
$submitted_url = ""; // Variable to hold the submitted URL after successful submission
$status = -1; // Default status value if no submission exists

// Check if the user has already submitted a link
$sql_check = "SELECT share_url, status FROM shared_links WHERE user_id = ? LIMIT 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    // User has already submitted a link
    $stmt_check->bind_result($submitted_url, $status);
    $stmt_check->fetch();
}

$stmt_check->close();

// Step 1: Process form submission (only if the user hasn't already submitted)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['share_url']) && $status == -1) {
    $share_url = $_POST['share_url'];

    // Prepare SQL to insert the shared link for review
    $sql = "INSERT INTO shared_links (user_id, share_url, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $initial_status = 0; // 0 means pending review
    $stmt->bind_param("iss", $user_id, $share_url, $initial_status);
    
    if ($stmt->execute()) {
        // Form submission is successful
        $status = 0; // Set status to "Review" (pending)
        $submitted_url = $share_url; // Save the submitted URL to display below
        echo "<script>alert('Thank you! Your link has been submitted for review.');</script>";
    } else {
        echo "<script>alert('There was an error submitting your link. Please try again.');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Link</title>
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
        h1 {
            font-size: 20px;
            margin-bottom: 20px;
            color: black;
        }
        p {
            margin-bottom: 20px;
            color: #666;
            line-height: 1.5;
            font-size: 14px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #1f2e51;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .input-group label {
            font-size: 14px;
            color: black;
        }
        .input-group button {
            background-color: #1f2e51;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn {
            background-color: #1f2e51;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-top: 30px;
        }
        .btn:hover {
            background-color: #0f1a3b;
        }
        .success-message {
            margin-top: 20px;
            color: green;
            font-size: 16px;
            word-wrap: break-word; /* Ensures long URLs wrap properly */
            word-break: break-all;  /* Breaks long words or URLs if necessary */
            white-space: normal;    /* Makes sure long URLs will wrap */
            text-align: left;
        }
    </style>
</head>
<body>

<!-- Header with Back Button -->
<div class="header">
    <button class="back-button" onclick="history.back();">
        &larr; Back
    </button>
    <span>Share Link</span>
</div>

<div class="container">
    <p>Please share our platform to other groups or social media platforms. We will review it. If the review passes, you will get a bonus. Please enter your sharing address below for our review.</p>

    <form method="POST" action="">
        <div class="input-group">
            <label for="state">STATE</label>
            <button type="button" id="shareButton">
                <?php
                // Determine the button text based on the status
                if ($status == 0) {
                    echo "Review";  // 0 means pending review
                } elseif ($status == 1) {
                    echo "Success"; // 1 means approved
                } elseif ($status == 2) {
                    echo "Reject";  // 2 means rejected
                } else {
                    echo "Please share"; // Default case when no submission exists
                }
                ?>
            </button>
        </div>

        <label for="share_url">Share LINK URL</label>
        <input type="text" id="share_url" name="share_url" placeholder="Paste the URL where you shared the platform" required
            <?php if ($status != -1) echo "disabled"; ?>> <!-- Disable input if already submitted -->

        <input type="submit" class="btn" value="Continue" <?php if ($status != -1) echo "disabled"; ?>>
    </form>

    <?php if ($status != -1): ?>
        <div class="success-message">
            <p>Your submitted link: <strong><?php echo htmlspecialchars($submitted_url); ?></strong></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
