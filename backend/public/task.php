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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($task['title']); ?> - StartSmart</title>
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
        <?php $typeSlug = strtolower(str_replace(' ', '-', $task['taskType'])); ?>
        <section class="hero">
            <h1><?php echo htmlspecialchars($task['title']); ?></h1>
            <p><?php echo htmlspecialchars($subject['subjectName']); ?></p>
        </section>

        <section class="card">
            <div class="detail-grid">
                <div>
                    <div class="label">Type</div>
                    <div class="value"><span class="badge badge-<?php echo $typeSlug; ?>"><?php echo htmlspecialchars($task['taskType']); ?></span></div>
                </div>
                <div>
                    <div class="label">Due date</div>
                    <div class="value">📅 <?php echo htmlspecialchars($task['dueDate'] ?: 'Not set'); ?></div>
                </div>
            </div>
            <?php if (trim($task['description']) !== ''): ?>
                <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">
                <div class="label" style="font-size:0.75rem;text-transform:uppercase;color:var(--muted);">Details</div>
                <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h3>🧩 Task Obstacle</h3>
            <?php if ($obstacle): ?>
                <div class="callout">
                    <strong><?php echo htmlspecialchars($obstacle['obstacleType']); ?></strong>
                    <?php if (trim($obstacle['description']) !== ''): ?>
                        <p style="margin-bottom:0;"><?php echo nl2br(htmlspecialchars($obstacle['description'])); ?></p>
                    <?php endif; ?>
                </div>
                <a class="btn btn-ghost btn-small" href="obstacles.php?task_id=<?php echo $taskId; ?>">Update Answers</a>
            <?php else: ?>
                <p class="note">No obstacle answers yet.</p>
                <a class="btn btn-small" href="obstacles.php?task_id=<?php echo $taskId; ?>">Identify Obstacles</a>
            <?php endif; ?>
        </section>

        <section class="card">
            <h3>💡 Task Hint</h3>
            <p class="note">Hint requests recorded</p>
            <p class="hint-count"><?php echo $hintCount; ?></p>
            <br>
            <a class="btn btn-small" href="request_hint.php?task_id=<?php echo $taskId; ?>">Request a Hint</a>
        </section>
    </main>
</body>
</html>
