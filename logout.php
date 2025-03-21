<?php
session_start(); // Start the session

// Destroy all session data
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Redirect the user to the login page or any other page
header("Location: index.php"); // Replace 'index.php' with your login page or home page
exit();
?>
