<?php
include('db.php');

// Function to calculate income for all orders associated with products
function processIncomeByProducts($conn) {
    // Start the HTML structure with CSS
    echo "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f9;
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .container {
                max-width: 900px;
                margin: 0 auto;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            h2 {
                text-align: center;
                color: #4CAF50;
                margin-bottom: 20px;
            }
            .product-result, .user-result {
                border-bottom: 1px solid #e0e0e0;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .product-result:last-child, .user-result:last-child {
                border-bottom: none;
            }
            .status-success {
                color: #4CAF50;
                font-weight: bold;
            }
            .status-wait {
                color: #ff9800;
                font-weight: bold;
            }
            .status-label {
                font-size: 14px;
                width: 100%;
                margin-bottom: 10px;
            }
            .balance {
                background-color: #f0f8f5;
                border-radius: 4px;
                padding: 5px 10px;
                font-size: 16px;
                color: #333;
                margin-top: 10px;
                text-align: center;
                width: 100%;
            }
            /* Mobile responsive */
            @media (max-width: 600px) {
                .product-result, .user-result {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .balance {
                    font-size: 14px;
                    padding: 8px;
                }
                .status-label {
                    font-size: 12px;
                }
            }
        </style>
    </head>
    <body>
    <div class='container'>
        <h2>Processing Income for Orders by Products (Every 24 Hours)</h2>
    ";

    // Fetch all products from the database
    $query_products = "SELECT id, product_name, daily_return FROM products";
    $result_products = $conn->query($query_products);

    if ($result_products->num_rows > 0) {
        while ($product = $result_products->fetch_assoc()) {
            $product_id = $product['id'];
            $product_name = $product['product_name'];
            $daily_return_rate = $product['daily_return'];

            // Calculate the income for all orders associated with this product
            $income = calculateProductIncome($conn, $product_id, $daily_return_rate);

            // Display product-specific information
            if ($income > 0) {
                echo "
                <div class='product-result'>
                    <div>
                        <p class='status-label'>Product: $product_name </p>
                        <span class='status-success'>Total Income Credited: ₱" . number_format($income, 2) . "</span>
                    </div>
                </div>";
            } else {
                echo "
                <div class='product-result'>
                    <div>
                        <p class='status-label'>Product: $product_name </p>
                        <span class='status-wait'>No income credited yet (within the 24-hour window).</span>
                    </div>
                </div>";
            }
        }
    } else {
        echo "<p>No products found in the database.</p>";
    }

    // End HTML structure
    echo "
    </div>
    </body>
    </html>";
}

// Function to calculate the income for all orders of a specific product
function calculateProductIncome($conn, $product_id, $daily_return_rate) {
    $total_income = 0;
    $current_time = new DateTime();

    // Fetch all activated orders for this product
    $query_orders = "SELECT orders.id, orders.price, orders.user_id, orders.last_return_date, orders.total_paid, users.balance 
                     FROM orders 
                     JOIN users ON orders.user_id = users.id 
                     WHERE orders.product_id = ? AND orders.status = 1"; // Only activated orders
    $stmt_orders = $conn->prepare($query_orders);
    if (!$stmt_orders) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt_orders->bind_param("i", $product_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    // Calculate income for each order if 24 hours have passed
    while ($order = $result_orders->fetch_assoc()) {
        $order_price = $order['price'];
        $last_return_date = new DateTime($order['last_return_date']);
        $user_id = $order['user_id'];
        $current_balance = $order['balance'];

        // Check if 24 hours have passed since last return
        $interval = $current_time->diff($last_return_date);
        if ($interval->h >= 24 || $interval->days > 0) {
            // Calculate income for this order
            $order_income = ($order_price * $daily_return_rate) / 100;
            $total_income += $order_income;

            // Update the order's total_paid and last return date
            $new_total_paid = $order['total_paid'] + $order_income;
            $update_order = "UPDATE orders SET total_paid = ?, last_return_date = ? WHERE id = ?";
            $stmt_update_order = $conn->prepare($update_order);
            $current_time_str = $current_time->format('Y-m-d H:i:s');
            $stmt_update_order->bind_param("dsi", $new_total_paid, $current_time_str, $order['id']);
            $stmt_update_order->execute();
            $stmt_update_order->close();

            // Update the user's balance with the credited income
            $new_balance = $current_balance + $order_income;
            updateUserBalance($conn, $user_id, $new_balance);

            // Display user-specific information
            echo "
            <div class='user-result'>
                <p class='status-label'>User ID: $user_id</p>
                <span class='balance'>New Balance: ₱" . number_format($new_balance, 2) . "</span>
            </div>";
        }
    }

    $stmt_orders->close();
    return $total_income;
}

// Function to update the user's balance in the database
function updateUserBalance($conn, $user_id, $new_balance) {
    $update_balance_query = "UPDATE users SET balance = ? WHERE id = ?";
    $stmt_update_balance = $conn->prepare($update_balance_query);

    if (!$stmt_update_balance) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt_update_balance->bind_param("di", $new_balance, $user_id);
    if (!$stmt_update_balance->execute()) {
        die("Execute failed: " . $stmt_update_balance->error);
    }

    $stmt_update_balance->close();
}

// Run the process for all products
processIncomeByProducts($conn);

$conn->close();
?>
