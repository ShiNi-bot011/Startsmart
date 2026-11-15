<?php
// backend/config/database.php
// Shared MySQL connection for the StartSmart PHP app

$host     = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password is empty
$database = "startsmart"; // Database name

// 1. Connect to MySQL
$conn = new mysqli($host, $username, $password, $database);

// 2. Check if connection failed
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 3. Set charset so emojis and Malay names display properly
$conn->set_charset("utf8mb4");
