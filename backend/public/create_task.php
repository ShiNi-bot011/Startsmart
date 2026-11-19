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

$subjects = mysqli_query(
    $conn,
    "SELECT * FROM subjects WHERE studentId = $studentId ORDER BY semesterNumber, sortOrder, subjectId"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Task - StartSmart</title>
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
            <h2>📝 Create Academic Task</h2>
            <p class="note">Tell us what you need to do. Next, we'll help you figure out what's in the way.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="create_task.php" method="POST">
                <label class="field">Subject
                    <select name="subject_id" required>
                        <option value="">Choose a subject</option>
                        <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $subject['subjectId']; ?>">
                                Year <?php echo intdiv((int) $subject['semesterNumber'] - 1, 2) + 1; ?>,
                                Semester <?php echo (((int) $subject['semesterNumber'] - 1) % 2) + 1; ?> —
                                <?php echo htmlspecialchars($subject['subjectName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <label class="field">Task description
                    <input type="text" name="title" maxlength="150" placeholder="e.g. Write the SRS test cases" required>
                </label>

                <label class="field">Additional details <small>(optional)</small>
                    <textarea name="description"></textarea>
                </label>

                <label class="field">Task type
                    <select name="task_type" required>
                        <option value="General">General</option>
                        <option value="Exam Prep">Exam Prep</option>
                        <option value="Major Project">Major Project</option>
                    </select>
                </label>

                <label class="field">Due date <small>(required for Exam Prep)</small>
                    <input type="date" name="due_date">
                </label>

                <div class="btn-row">
                    <button type="submit">Save Task</button>
                    <a class="btn btn-ghost" href="home.php">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
