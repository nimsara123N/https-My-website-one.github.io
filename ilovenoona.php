<?php
include('db.php');

// Fetch all pending shared links for review
$sql = "SELECT shared_links.id, shared_links.user_id, shared_links.amount, shared_links.status, shared_links.share_url, users.mobile_number 
        FROM shared_links 
        JOIN users ON shared_links.user_id = users.id 
        WHERE shared_links.status = 0";
$result = $conn->query($sql);

// Handle approval or rejection of share links
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_id'])) {
    $link_id = $_POST['link_id'];
    $new_amount = $_POST['new_amount'];
    $action = $_POST['action']; // Either 'approve' or 'reject'

    // Get the shared link details
    $stmt = $conn->prepare("SELECT user_id FROM shared_links WHERE id = ?");
    $stmt->bind_param("i", $link_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    if ($action == 'approve') {
        // Approve the share link and add the new amount to the user's balance
        $stmt = $conn->prepare("UPDATE shared_links SET status = 1, amount = ? WHERE id = ?");
        $stmt->bind_param("di", $new_amount, $link_id);
        if ($stmt->execute()) {
            // Add the new amount to the user's balance
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $new_amount, $user_id);
            $stmt->execute();
            echo "<script>alert('Link approved and balance updated with the new amount!');</script>";
        }
        $stmt->close();
    } elseif ($action == 'reject') {
        // Reject the share link and do not change the user's balance
        $stmt = $conn->prepare("UPDATE shared_links SET status = 2 WHERE id = ?");
        $stmt->bind_param("i", $link_id);
        if ($stmt->execute()) {
            echo "<script>alert('Link rejected.');</script>";
        }
        $stmt->close();
    }

    // Refresh the page to reflect changes
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Share Links</title>
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
            padding: 20px;
        }
        .header {
            width: 100%;
            background-color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .container {
            margin-top: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .share-link {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .share-link h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .share-link p {
            margin: 5px 0;
            font-size: 14px;
        }
        .actions {
            margin-top: 10px;
        }
        .btn {
            padding: 10px 15px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        .btn-reject {
            background-color: #dc3545;
            color: white;
        }
        input[type="number"] {
            padding: 5px;
            font-size: 14px;
            width: 100px;
            text-align: right;
            margin-left: 10px;
        }
        .share-url {
            white-space: normal; /* Allow line breaks */
            word-break: break-word; /* Break long words if necessary */
            display: block;
        }
    </style>
</head>
<body>

<div class="header">
    Review Share Links
</div>

<div class="container">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="share-link">
                <h4>Mobile Number: <?php echo htmlspecialchars($row['mobile_number']); ?></h4>
                <p>Amount: 
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline-block;">
                        <input type="hidden" name="link_id" value="<?php echo $row['id']; ?>">
                        <input type="number" name="new_amount" value="<?php echo htmlspecialchars($row['amount']); ?>" required>
                </p>
                <p class="share-url">Share URL: <a href="<?php echo htmlspecialchars($row['share_url']); ?>" target="_blank"><?php echo htmlspecialchars($row['share_url']); ?></a></p>
                <div class="actions">
                    <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                </div>
                    </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;">No pending share links.</div>
    <?php endif; ?>
</div>

</body>
</html>
