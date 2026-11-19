<?php
session_start();
include '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['studentId'];

// Check if task ID is provided in the URL
if (isset($_GET['id'])) {
    $taskId = (int) $_GET['id'];
    
    // Delete the task, ensuring it belongs to the logged-in student
    $sql = "DELETE FROM academic_tasks WHERE taskId = $taskId AND studentId = $studentId";
    
    if (mysqli_query($conn, $sql)) {
        // Successfully deleted, redirect back to home
        header("Location: home.php");
        exit;
    } else {
        // Handle error
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    // If no ID provided, just go back home
    header("Location: home.php");
    exit;
}
?>