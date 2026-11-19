<?php
// backend/config/database.php
// Shared MySQL connection for the StartSmart PHP app

$host     = "localhost";
$username = "root";
$password = "";       // Default MAMP password
$database = "startsmart"; // Database name

// 1. Connect to MySQL
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
