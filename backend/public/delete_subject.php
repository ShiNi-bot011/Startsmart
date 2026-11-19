<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: home.php');
    exit;
}

$subjectId = (int) $_POST['id'];
$studentId = (int) $_SESSION['studentId'];
$semester = filter_var($_POST['semester'] ?? 1, FILTER_VALIDATE_INT);
$semester = $semester !== false && $semester >= 1 && $semester <= 8 ? $semester : 1;

$sql = "DELETE FROM subjects WHERE subjectId = $subjectId AND studentId = $studentId";
if (!mysqli_query($conn, $sql)) {
    http_response_code(500);
    exit('Could not delete subject.');
}

header("Location: home.php?semester=$semester");
exit;
