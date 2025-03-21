<?php
session_start();
include('db.php');

// Initialize an empty error message
$error_message = "";

// Handle form submission for login and signup
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $mobile = $_POST['mobile'];
        $password = $_POST['password'];

        // Login process: fetch user details based on the mobile number
        $stmt = $conn->prepare("SELECT * FROM users WHERE mobile_number = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verify the hashed password
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row['id']; // Store user information in session
                header("Location: dashboard.php"); // Redirect to dashboard after login
                exit();
            } else {
                $error_message = "Invalid login credentials.";
            }
        } else {
            $error_message = "Invalid login credentials.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        $mobile = $_POST['mobile'];
        $password = $_POST['password'];
        $captcha = $_POST['captcha'];

        // Referral logic: capture the referral ID if provided in the URL
        $referred_by = null;
        if (isset($_GET['ref']) && is_numeric($_GET['ref'])) {
            $referred_by = (int)$_GET['ref']; // Get the referral ID from the query parameter
        }

        // Sign up process with CAPTCHA validation
        if ($captcha == $_SESSION['captcha']) {
            // Check if the mobile number is already registered
            $stmt = $conn->prepare("SELECT * FROM users WHERE mobile_number = ?");
            $stmt->bind_param("s", $mobile);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Hash the password before storing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database with an initial balance of ₱20 and store the referred_by user
                $stmt = $conn->prepare("INSERT INTO users (mobile_number, password, balance, referred_by) VALUES (?, ?, 20.00, ?)");
                $stmt->bind_param("ssi", $mobile, $hashed_password, $referred_by);
                if ($stmt->execute()) {
                    // Get the inserted user's ID (new referral_id)
                    $new_user_id = $conn->insert_id;

                    // Update the referral_id of the new user to be their own ID
                    $update_stmt = $conn->prepare("UPDATE users SET referral_id = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_user_id, $new_user_id);
                    $update_stmt->execute();

                    $_SESSION['user'] = $new_user_id; // Store user information in session
                    $_SESSION['captcha'] = rand(1000, 9999); // Refresh CAPTCHA after successful signup
                    header("Location: dashboard.php"); // Redirect to dashboard after signup
                    exit();
                } else {
                    $error_message = "Error signing up. Please try again.";
                }
            } else {
                $error_message = "Mobile number is already registered.";
            }
        } else {
            $error_message = "Invalid CAPTCHA.";
        }
    }
}

// Generate a new CAPTCHA if it's not already set
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = rand(1000, 9999); // Generate a 4-digit random number
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup - Delor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-image: url('forest.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .top-logo {
            position: absolute;
            top: 20px;
            text-align: center;
            width: 100%;
        }

        .top-logo img {
            width: 300px;
            height: auto;
        }

        .login-container {
            background-color: #e6f2e6;
            border-radius: 20px;
            padding: 40px;
            width: 350px;
            text-align: center;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-top: 240px;
        }

        .login-container h1 {
            color: #2e7d32;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .login-container p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .tab-menu {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .tab-menu div {
            width: 50%;
            padding: 10px 0;
            font-size: 16px;
            cursor: pointer;
            color: #2e7d32;
            font-weight: bold;
            text-align: center;
        }

        .tab-menu .active {
            color: #2e7d32;
            border-bottom: 3px solid #2e7d32;
        }

        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 14px;
            color: #2e7d32;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-size: 16px;
        }

        .form-group input::placeholder {
            color: #ccc;
        }

        button {
            width: 100%;
            padding: 12px;
            border-radius: 30px;
            background-color: #2e7d32;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #1e5a23;
        }

        .footer-text {
            color: #2e7d32;
            font-size: 12px;
            margin-top: 20px;
        }

        .captcha-display {
            font-size: 24px;
            font-weight: bold;
            background-color: #fff;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-top: 10px;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
    </style>
    <script>
        function showForm(formType) {
            if (formType === 'login') {
                document.getElementById('loginForm').style.display = 'block';
                document.getElementById('signupForm').style.display = 'none';
                document.getElementById('loginTab').classList.add('active');
                document.getElementById('signupTab').classList.remove('active');
            } else {
                document.getElementById('loginForm').style.display = 'none';
                document.getElementById('signupForm').style.display = 'block';
                document.getElementById('loginTab').classList.remove('active');
                document.getElementById('signupTab').classList.add('active');
            }
        }
    </script>
</head>
<body>
    <div class="top-logo">
        <img src="logos.png" alt="Delor Logo"> <!-- Make sure to replace this with the actual path to your logo -->
    </div>

    <div class="login-container">
        <h1 id="formTitle">Delor</h1>
        <p>Let's create wealth together</p>

        <div class="tab-menu">
            <div id="loginTab" class="active" onclick="showForm('login')">Login</div>
            <div id="signupTab" onclick="showForm('signup')">Sign up</div>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="mobile">Mobile number</label>
                <input type="text" id="mobile" name="mobile" required placeholder="Enter your Mobile number">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Password (≥6 characters)">
            </div>
            <input type="hidden" name="action" value="login">
            <button type="submit">Login</button>
        </form>

        <!-- Sign Up Form -->
        <form id="signupForm" method="POST" action="" style="display: none;">
            <div class="form-group">
                <label for="mobile">Mobile number</label>
                <input type="text" id="mobile" name="mobile" required placeholder="Enter your Mobile number">
            </div>
            <div class="form-group">
                <label for="captcha">Captcha</label>
                <input type="text" id="captcha" name="captcha" required placeholder="Enter Captcha">
                <div class="captcha-display"><?php echo $_SESSION['captcha']; ?></div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Password (≥6 characters)">
            </div>
            <input type="hidden" name="action" value="signup">
            <button type="submit">Sign up</button>
        </form>

        <p class="footer-text">© 2024 Delor, S.A. Reservados todos los derechos.</p>
    </div>
</body>
</html>
