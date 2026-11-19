<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['studentId'];
$taskId = (int) $_GET['id'];
$taskResult = mysqli_query(
    $conn,
    "SELECT * FROM academic_tasks WHERE taskId = $taskId AND studentId = $studentId"
);
$task = mysqli_fetch_assoc($taskResult);

if (!$task) {
    die('Task not found.');
}

$subjectResult = mysqli_query($conn, "SELECT subjectName FROM subjects WHERE subjectId = " . $task['subjectId']);
$subject = mysqli_fetch_assoc($subjectResult);
$obstacleResult = mysqli_query(
    $conn,
    "SELECT * FROM task_obstacles WHERE taskId = $taskId ORDER BY obstacleId DESC LIMIT 1"
);
$obstacle = mysqli_fetch_assoc($obstacleResult);
$hintResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS hintCount FROM task_hint_requests WHERE taskId = $taskId AND studentId = $studentId"
);
$hintCount = mysqli_fetch_assoc($hintResult)['hintCount'];
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($task['title']); ?> - StartSmart</title>
</head>
<body>
    <h2><?php echo htmlspecialchars($task['title']); ?></h2>
    <p><a href="home.php">Home</a> | <a href="logout.php">Log Out</a></p>

    <p>Subject: <?php echo htmlspecialchars($subject['subjectName']); ?></p>
    <p>Type: <?php echo htmlspecialchars($task['taskType']); ?></p>
    <p>Due date: <?php echo htmlspecialchars($task['dueDate'] ?: 'Not set'); ?></p>
    <p>Details: <?php echo nl2br(htmlspecialchars($task['description'])); ?></p>

    <h3>Task Obstacle</h3>
    <?php if ($obstacle): ?>
        <p><?php echo htmlspecialchars($obstacle['obstacleType']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($obstacle['description'])); ?></p>
        <a href="obstacles.php?task_id=<?php echo $taskId; ?>">Update Answers</a>
    <?php else: ?>
        <p>No obstacle answers yet.</p>
        <a href="obstacles.php?task_id=<?php echo $taskId; ?>">Identify Obstacles</a>
    <?php endif; ?>

    <h3>Task Hint</h3>
    <p>Hint requests recorded: <?php echo $hintCount; ?></p>
    <a href="request_hint.php?task_id=<?php echo $taskId; ?>">Request a Hint</a>
</body>
</html>
