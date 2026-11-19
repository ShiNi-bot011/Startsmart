<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    exit;
}

$studentId = (int) $_SESSION['studentId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if 'id' is provided
    if (isset($_POST['id'])) {
        $taskId = (int) $_POST['id'];
        $action = $_POST['action'] ?? '';

        // ACTION 1: Mark as Completed
        if ($action === 'complete') {
            $sql = "UPDATE academic_tasks 
                    SET status = 'Completed', progress = 100 
                    WHERE taskId = $taskId AND studentId = $studentId";
            
            if (mysqli_query($conn, $sql)) {
                // Send success response for JavaScript
                echo json_encode(['status' => 'success', 'message' => 'Task completed']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to complete task']);
            }
        } 
        // ACTION 2: Restore from Archive
        elseif ($action === 'restore') {
            $sql = "UPDATE academic_tasks 
                    SET status = 'In Progress', progress = 0 
                    WHERE taskId = $taskId AND studentId = $studentId";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['status' => 'success', 'message' => 'Task restored']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to restore task']);
            }
        }
    }
}
?>