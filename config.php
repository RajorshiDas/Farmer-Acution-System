<?php
$host = "localhost";
$user = "root";   // change if you set a password
$pass = "";
$db   = "farmer_auction_system";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
