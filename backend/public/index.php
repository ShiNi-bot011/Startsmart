<?php
// backend/public/index.php
// Redirects visitor to login or home
session_start();

if (isset($_SESSION['studentId'])) {
    header("Location: home.php");
} else {
    header("Location: login.php");
}
exit;
