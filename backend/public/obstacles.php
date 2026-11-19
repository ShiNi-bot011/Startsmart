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
<html>
<head>
    <title>Task Obstacles - StartSmart</title>
</head>
<body>
    <h2>What is making this task difficult?</h2>
    <p>Task: <?php echo htmlspecialchars($task['title']); ?></p>
    <p><a href="task.php?id=<?php echo $taskId; ?>">Back to Task</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="obstacles.php?task_id=<?php echo $taskId; ?>" method="POST">
        <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

        Main obstacle:<br>
        <select name="category" required>
            <option value="Unclear requirements">Unclear requirements</option>
            <option value="Feeling overwhelmed">Feeling overwhelmed</option>
            <option value="Not understanding a concept">Not understanding a concept</option>
            <option value="Not enough time">Not enough time</option>
            <option value="Unclear where to start">Unclear where to start</option>
            <option value="Other">Other</option>
        </select><br><br>

        Another difficulty (optional):<br>
        <select name="answer2">
            <option value="">Skip</option>
            <option value="I do not understand what is required.">I do not understand what is required.</option>
            <option value="The task feels too large.">The task feels too large.</option>
            <option value="I need help understanding a concept.">I need help understanding a concept.</option>
            <option value="I am unsure how to begin.">I am unsure how to begin.</option>
            <option value="Something else is getting in the way.">Something else is getting in the way.</option>
        </select><br><br>

        Any other difficulty (optional):<br>
        <select name="answer3">
            <option value="">Skip</option>
            <option value="I do not understand what is required.">I do not understand what is required.</option>
            <option value="The task feels too large.">The task feels too large.</option>
            <option value="I need help understanding a concept.">I need help understanding a concept.</option>
            <option value="I am unsure how to begin.">I am unsure how to begin.</option>
            <option value="Something else is getting in the way.">Something else is getting in the way.</option>
        </select><br><br>

        Details (optional):<br>
        <textarea name="details"></textarea><br><br>

        <label><input type="checkbox" name="confirm"> I confirm this obstacle</label><br><br>
        <button type="submit">Save Answers</button>
        <button type="submit" name="skip" value="1">Skip</button>
    </form>
</body>
</html>
