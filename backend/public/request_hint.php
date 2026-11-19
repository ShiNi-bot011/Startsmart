<?php
session_start();
include '../config/database.php';
include '../config/ai_obstacles_hints.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['studentId'];
$taskId = (int) ($_GET['task_id'] ?? $_POST['task_id'] ?? 0);

// Fetch task joined with subject details
$taskResult = mysqli_query(
    $conn,
    "SELECT t.*, s.subjectName, s.subjectCode
     FROM academic_tasks t
     INNER JOIN subjects s ON t.subjectId = s.subjectId
     WHERE t.taskId = $taskId AND t.studentId = $studentId"
);
$task = mysqli_fetch_assoc($taskResult);

if (!$task) {
    die('Task not found.');
}

// Fetch latest obstacle from US005 so hints directly address the student's blocker
$obstacleResult = mysqli_query(
    $conn,
    "SELECT * FROM task_obstacles WHERE taskId = $taskId ORDER BY obstacleId DESC LIMIT 1"
);
$obstacle = $obstacleResult ? mysqli_fetch_assoc($obstacleResult) : null;

$error = '';
$generatedHint = null;
$lastContext = '';
$hintLevel = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $context = trim($_POST['context'] ?? '');
    $hintLevel = (int) ($_POST['hint_level'] ?? 1);
    if ($hintLevel < 1 || $hintLevel > 2) {
        $hintLevel = 1;
    }

    if (strlen($context) > 2000) {
        $error = 'Please keep your question under 2,000 characters.';
    } else {
        $lastContext = $context;
        $safeContext = mysqli_real_escape_string($conn, $context);
        $sql = "INSERT INTO task_hint_requests (studentId, taskId, requestContext)
                VALUES ($studentId, $taskId, '$safeContext')";

        if (mysqli_query($conn, $sql)) {
            $generatedHint = us009_generateContextAwareHint($task, $obstacle, $context, $hintLevel);

            $subjectLabel = trim(($task['subjectCode'] ? $task['subjectCode'] . ' - ' : '') . $task['subjectName']);
            us009_logAiInteraction(
                $conn,
                $taskId,
                $subjectLabel,
                "[Level {$hintLevel} Hint Request] " . ($context !== '' ? $context : 'General step guidance'),
                $generatedHint['text']
            );
        } else {
            $error = 'Could not record your hint request.';
        }
    }
}

$subjectDisplay = trim(($task['subjectCode'] ? $task['subjectCode'] . ' - ' : '') . $task['subjectName']);
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
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <main class="container">
        <section class="card">
            <h2>💡 AI Task Hint</h2>
            <div class="callout">
                <strong>Subject:</strong> <?php echo htmlspecialchars($subjectDisplay); ?><br>
                <strong>Task:</strong> <?php echo htmlspecialchars($task['title']); ?>
                <span class="badge"><?php echo htmlspecialchars($task['taskType']); ?></span><br>
                <strong>Active Obstacle:</strong>
                <?php if ($obstacle): ?>
                    <?php echo htmlspecialchars($obstacle['obstacleType']); ?>
                <?php else: ?>
                    <em>None set yet</em> (<a href="obstacles.php?task_id=<?php echo $taskId; ?>">Identify obstacle first</a>)
                <?php endif; ?>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($generatedHint !== null): ?>
                <div class="callout" style="border-left: 4px solid var(--primary, #2563eb);">
                    <strong>Level <?php echo $hintLevel; ?> Hint</strong>
                    <small style="display:block; margin-bottom:8px;">Source: <?php echo htmlspecialchars($generatedHint['source']); ?></small>
                    <p style="margin-bottom:0;"><?php echo nl2br(htmlspecialchars($generatedHint['text'])); ?></p>
                </div>
            <?php endif; ?>

            <form action="request_hint.php?task_id=<?php echo $taskId; ?>" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                <input type="hidden" name="hint_level" value="1">

                <label class="field">What specific part do you need a hint on? <small>(optional)</small>
                    <textarea name="context" placeholder="e.g., How should I structure the SQL join, or which section should I draft first?"><?php
                        echo htmlspecialchars($lastContext);
                    ?></textarea>
                </label>

                <div class="btn-row">
                    <button type="submit">Get Hint</button>
                    <?php if ($generatedHint !== null && $hintLevel === 1): ?>
                        <button type="submit" name="hint_level" value="2" class="btn-ghost">Get Deeper Hint (Level 2)</button>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="task.php?id=<?php echo $taskId; ?>">Back to Task</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
