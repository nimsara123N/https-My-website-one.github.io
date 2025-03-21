<?php
$servername = "localhost";
$username = "profelar_db"; // Update with your MySQL username
$password = "profelar_db"; // Update with your MySQL password
$dbname = "profelar_db"; // Update with your preferred database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
