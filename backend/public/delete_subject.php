<?php
// backend/public/delete_subject.php
session_start();
include '../src/Config/Database.php';

if (!isset($_SESSION['studentId'])) {
    header("Location: login.php");
    exit;
}

$subjectId = $_GET['id'] ?? 0;
$studentId = $_SESSION['studentId'];

// Delete subject belonging to this student
$sql = "DELETE FROM subjects WHERE subjectId = '$subjectId' AND studentId = '$studentId'";
mysqli_query($conn, $sql);

// Redirect back to home.php
header("Location: home.php");
exit;
