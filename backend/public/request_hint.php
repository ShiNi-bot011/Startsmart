<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['studentId'];
$taskId = (int) ($_GET['task_id'] ?? $_POST['task_id'] ?? 0);
$taskResult = mysqli_query(
    $conn,
    "SELECT * FROM academic_tasks WHERE taskId = $taskId AND studentId = $studentId"
);
$task = mysqli_fetch_assoc($taskResult);

if (!$task) {
    die('Task not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $context = trim($_POST['context']);
    if (strlen($context) > 2000) {
        $error = 'Please keep the request under 2,000 characters.';
    } else {
        $context = mysqli_real_escape_string($conn, $context);
        $sql = "INSERT INTO task_hint_requests (studentId, taskId, requestContext)
                VALUES ($studentId, $taskId, '$context')";

        if (mysqli_query($conn, $sql)) {
            header("Location: task.php?id=$taskId");
            exit;
        } else {
            $error = 'Could not record your request.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Hint - StartSmart</title>
</head>
<body>
    <h2>Request a Hint</h2>
    <p>Task: <?php echo htmlspecialchars($task['title']); ?></p>
    <p>This records your request. It does not generate an AI hint.</p>
    <p><a href="task.php?id=<?php echo $taskId; ?>">Back to Task</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="request_hint.php?task_id=<?php echo $taskId; ?>" method="POST">
        <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
        What do you need help with?<br>
        <textarea name="context"></textarea><br><br>
        <button type="submit">Record Request</button>
    </form>
</body>
</html>
