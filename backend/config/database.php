<?php
// backend/config/database.php
// Shared MySQL connection for the StartSmart PHP app

$host     = "127.0.0.1";
$port     = 8889;         // Default MAMP MySQL port
$username = "root";
$password = "root";       // Default MAMP password
$database = "startsmart"; // Database name

// 1. Connect to MySQL
$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
