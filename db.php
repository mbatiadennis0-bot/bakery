<?php
// Database configuration for InfinityFree
$servername = "sql308.infinityfree.com"; // MySQL Hostname
$username = "if0_41459113";             // MySQL Username
$password = "MLYWTSpYP5";               // Your specific MySQL Password
$dbname = "if0_41459113_bakerysystem";  // Your specific Database Name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure special characters and currency symbols (like Ksh) display correctly
$conn->set_charset("utf8mb4");
?>