<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['studentId'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int) $_POST['subject_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $taskType = $_POST['task_type'];
    $dueDate = $_POST['due_date'];

    $subjectCheck = mysqli_query(
        $conn,
        "SELECT subjectId FROM subjects WHERE subjectId = $subjectId AND studentId = $studentId"
    );

    if (mysqli_num_rows($subjectCheck) === 0) {
        $error = 'Choose one of your subjects.';
    } elseif ($title === '') {
        $error = 'Task description cannot be empty.';
    } elseif (!in_array($taskType, ['General', 'Exam Prep', 'Major Project'])) {
        $error = 'Choose a valid task type.';
    } elseif ($taskType === 'Exam Prep' && $dueDate === '') {
        $error = 'Exam Prep needs an exam date.';
    } elseif ($dueDate !== '' && (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) ||
        !checkdate((int) substr($dueDate, 5, 2), (int) substr($dueDate, 8, 2), (int) substr($dueDate, 0, 4))
    )) {
        $error = 'Enter a valid due date.';
    } elseif ($taskType === 'Exam Prep' && strtotime($dueDate) <= strtotime('today')) {
        $error = 'Exam date must be in the future.';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $description = mysqli_real_escape_string($conn, $description);
        $taskType = mysqli_real_escape_string($conn, $taskType);
        $dueDateSql = $dueDate === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $dueDate) . "'";

        $sql = "INSERT INTO academic_tasks (studentId, subjectId, title, description, taskType, dueDate)
                VALUES ($studentId, $subjectId, '$title', '$description', '$taskType', $dueDateSql)";

        if (mysqli_query($conn, $sql)) {
            $taskId = mysqli_insert_id($conn);
            header("Location: obstacles.php?task_id=$taskId");
            exit;
        } else {
            $error = 'Could not save task.';
        }
    }
}

$subjects = mysqli_query($conn, "SELECT * FROM subjects WHERE studentId = $studentId ORDER BY subjectName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Task - StartSmart</title>
</head>
<body>
    <h2>Create Academic Task</h2>
    <p><a href="home.php">Home</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="create_task.php" method="POST">
        Subject:<br>
        <select name="subject_id" required>
            <option value="">Choose a subject</option>
            <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                <option value="<?php echo $subject['subjectId']; ?>">
                    <?php echo htmlspecialchars($subject['subjectName']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        Task description:<br>
        <input type="text" name="title" maxlength="150" required><br><br>

        Additional details:<br>
        <textarea name="description"></textarea><br><br>

        Task type:<br>
        <select name="task_type" required>
            <option value="General">General</option>
            <option value="Exam Prep">Exam Prep</option>
            <option value="Major Project">Major Project</option>
        </select><br><br>

        Due date (required for Exam Prep):<br>
        <input type="date" name="due_date"><br><br>

        <button type="submit">Save Task</button>
    </form>
</body>
</html>
