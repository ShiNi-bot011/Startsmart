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
    $category = $_POST['category'];
    $answer2 = $_POST['answer2'];
    $answer3 = $_POST['answer3'];
    $details = trim($_POST['details']);
    $confirmed = isset($_POST['confirm']) ? 1 : 0;

    if (!isset($_POST['skip']) && (
        !in_array($category, ['Unclear requirements', 'Feeling overwhelmed', 'Not understanding a concept', 'Not enough time', 'Unclear where to start', 'Other']) ||
        ($answer2 !== '' && !in_array($answer2, ['I do not understand what is required.', 'The task feels too large.', 'I need help understanding a concept.', 'I am unsure how to begin.', 'Something else is getting in the way.'])) ||
        ($answer3 !== '' && !in_array($answer3, ['I do not understand what is required.', 'The task feels too large.', 'I need help understanding a concept.', 'I am unsure how to begin.', 'Something else is getting in the way.']))
    )) {
        $error = 'Choose an answer from the options shown.';
    } elseif (!isset($_POST['skip']) && strlen($details) > 2000) {
        $error = 'Details must be 2,000 characters or fewer.';
    }

    if (isset($_POST['skip'])) {
        $category = 'Unclear where to start';
        $answer2 = '';
        $answer3 = '';
        $details = '';
        $confirmed = 1;
    }

    if ($error === '') {
        $category = mysqli_real_escape_string($conn, $category);
        $answer2 = mysqli_real_escape_string($conn, $answer2);
        $answer3 = mysqli_real_escape_string($conn, $answer3);
        $details = mysqli_real_escape_string($conn, $details);
        $subjectId = $task['subjectId'];

        $sql = "INSERT INTO task_obstacles
                (subjectId, taskId, obstacleType, description, answer1, answer2, answer3, isConfirmed)
                VALUES ($subjectId, $taskId, '$category', '$details', '$category', '$answer2', '$answer3', $confirmed)";

        if (mysqli_query($conn, $sql)) {
            header("Location: task.php?id=$taskId");
            exit;
        } else {
            $error = 'Could not save your answer.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Obstacles - StartSmart</title>
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
            <h2>🧩 What is making this task difficult?</h2>
            <div class="callout"><strong>Task:</strong> <?php echo htmlspecialchars($task['title']); ?></div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="obstacles.php?task_id=<?php echo $taskId; ?>" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

                <label class="field">Main obstacle
                    <select name="category" required>
                        <option value="Unclear requirements">Unclear requirements</option>
                        <option value="Feeling overwhelmed">Feeling overwhelmed</option>
                        <option value="Not understanding a concept">Not understanding a concept</option>
                        <option value="Not enough time">Not enough time</option>
                        <option value="Unclear where to start">Unclear where to start</option>
                        <option value="Other">Other</option>
                    </select>
                </label>

                <label class="field">Another difficulty <small>(optional)</small>
                    <select name="answer2">
                        <option value="">Skip</option>
                        <option value="I do not understand what is required.">I do not understand what is required.</option>
                        <option value="The task feels too large.">The task feels too large.</option>
                        <option value="I need help understanding a concept.">I need help understanding a concept.</option>
                        <option value="I am unsure how to begin.">I am unsure how to begin.</option>
                        <option value="Something else is getting in the way.">Something else is getting in the way.</option>
                    </select>
                </label>

                <label class="field">Any other difficulty <small>(optional)</small>
                    <select name="answer3">
                        <option value="">Skip</option>
                        <option value="I do not understand what is required.">I do not understand what is required.</option>
                        <option value="The task feels too large.">The task feels too large.</option>
                        <option value="I need help understanding a concept.">I need help understanding a concept.</option>
                        <option value="I am unsure how to begin.">I am unsure how to begin.</option>
                        <option value="Something else is getting in the way.">Something else is getting in the way.</option>
                    </select>
                </label>

                <label class="field">Details <small>(optional)</small>
                    <textarea name="details"></textarea>
                </label>

                <label class="check"><input type="checkbox" name="confirm"> I confirm this obstacle</label>

                <div class="btn-row">
                    <button type="submit">Save Answers</button>
                    <button type="submit" name="skip" value="1" class="btn-ghost">Skip</button>
                    <a class="btn btn-ghost" href="task.php?id=<?php echo $taskId; ?>">Back to Task</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
