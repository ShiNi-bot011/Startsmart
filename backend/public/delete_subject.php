<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$subjectId = (int) $_POST['id'];
$studentId = $_SESSION['studentId'];

$sql = "DELETE FROM subjects WHERE subjectId = $subjectId AND studentId = $studentId";
mysqli_query($conn, $sql);

header('Location: home.php');
exit;
