<?php
session_start();
include '../config/database.php';

// Must prevent HTML output and only echo JSON
header('Content-Type: application/json');

if (!isset($_SESSION['studentId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$studentId = (int) $_SESSION['studentId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int) $_POST['subject_id'];
    $title = trim($_POST['title']);
    $taskType = $_POST['task_type'] ?? 'General';
    $dueDate = $_POST['due_date'] ?? '';
    $dueTime = $_POST['due_time'] ?? '00:00';

    if ($title === '' || $dueDate === '') {
        echo json_encode(['status' => 'error', 'message' => 'Title and Date are required.']);
        exit;
    }

    // Combine Date and Time
    $dueDateTime = $dueDate . ' ' . $dueTime . ':00';
    $titleEscaped = mysqli_real_escape_string($conn, $title);
    $taskTypeEscaped = mysqli_real_escape_string($conn, $taskType);
    $dueDateTimeEscaped = mysqli_real_escape_string($conn, $dueDateTime);

    $sql = "INSERT INTO academic_tasks (studentId, subjectId, title, taskType, dueDate, status) 
            VALUES ($studentId, $subjectId, '$titleEscaped', '$taskTypeEscaped', '$dueDateTimeEscaped', 'In Progress')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Task added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
?>