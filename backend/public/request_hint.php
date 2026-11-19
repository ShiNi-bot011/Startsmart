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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Hint - StartSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="navbar">
        <a class="brand" href="home.php">🚀 StartSmart</a>
        <nav>
            <a href="home.php">Home</a>
            <a href="create_task.php">New Task</a>
            <a href="import_calendar.php">Import UTM Calendar</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <main class="container">
        <section class="card">
            <h2>💡 Request a Hint</h2>
            <div class="callout"><strong>Task:</strong> <?php echo htmlspecialchars($task['title']); ?></div>
            <p class="note">This records your request. It does not generate an AI hint.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="request_hint.php?task_id=<?php echo $taskId; ?>" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

                <label class="field">What do you need help with?
                    <textarea name="context"></textarea>
                </label>

                <div class="btn-row">
                    <button type="submit">Record Request</button>
                    <a class="btn btn-ghost" href="task.php?id=<?php echo $taskId; ?>">Back to Task</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
