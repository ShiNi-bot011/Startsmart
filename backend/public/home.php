<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['studentId'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $subjectName = trim($_POST['subject_name']);
        $subjectCode = mysqli_real_escape_string($conn, trim($_POST['subject_code']));
        if ($subjectName === '') {
            $error = 'Subject name cannot be empty.';
        } else {
            $subjectName = mysqli_real_escape_string($conn, $subjectName);
            $sql = "INSERT INTO subjects (studentId, subjectName, subjectCode)
                    VALUES ('$studentId', '$subjectName', '$subjectCode')";
        }

        if ($error === '' && mysqli_query($conn, $sql)) {
            $message = 'Subject added.';
        } elseif ($error === '') {
            $error = 'Subject already exists or could not be saved.';
        }
    }

    if (isset($_POST['rename_subject'])) {
        $subjectId = (int) $_POST['subject_id'];
        $subjectName = trim($_POST['subject_name']);
        $subjectCode = mysqli_real_escape_string($conn, trim($_POST['subject_code']));
        if ($subjectName === '') {
            $error = 'Subject name cannot be empty.';
        } else {
            $subjectName = mysqli_real_escape_string($conn, $subjectName);
            $sql = "UPDATE subjects SET subjectName = '$subjectName', subjectCode = '$subjectCode'
                    WHERE subjectId = $subjectId AND studentId = $studentId";
        }

        if ($error === '' && mysqli_query($conn, $sql)) {
            $message = 'Subject updated.';
        } elseif ($error === '') {
            $error = 'Subject already exists or could not be updated.';
        }
    }

    if (isset($_POST['move_subject'])) {
        $subjectId = (int) $_POST['subject_id'];
        $direction = $_POST['direction'];
        $subjects = mysqli_query($conn, "SELECT subjectId FROM subjects WHERE studentId = $studentId ORDER BY sortOrder, subjectId");
        $subjectIds = [];

        while ($subject = mysqli_fetch_assoc($subjects)) {
            $subjectIds[] = $subject['subjectId'];
        }

        $position = array_search($subjectId, $subjectIds);
        $nextPosition = $direction === 'up' ? $position - 1 : $position + 1;

        if ($position !== false && isset($subjectIds[$nextPosition])) {
            $firstId = $subjectIds[$position];
            $secondId = $subjectIds[$nextPosition];
            $subjectIds[$position] = $secondId;
            $subjectIds[$nextPosition] = $firstId;

            foreach ($subjectIds as $order => $id) {
                if (!mysqli_query($conn, "UPDATE subjects SET sortOrder = $order WHERE subjectId = $id AND studentId = $studentId")) {
                    die('Could not update subject order: ' . mysqli_error($conn));
                }
            }
            $message = 'Subject order updated.';
        }
    }
}

$subjects = mysqli_query($conn, "SELECT * FROM subjects WHERE studentId = $studentId ORDER BY sortOrder, subjectId");
$tasks = mysqli_query(
    $conn,
    "SELECT academic_tasks.*, subjects.subjectName
     FROM academic_tasks
     JOIN subjects ON academic_tasks.subjectId = subjects.subjectId
     WHERE academic_tasks.studentId = $studentId
     ORDER BY academic_tasks.dueDate, academic_tasks.taskId"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home - StartSmart</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['studentName']); ?>!</h1>
    <p><a href="logout.php">Log Out</a></p>

    <?php if ($message !== ''): ?><p style="color:green;"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <h2>Manage Subjects</h2>
    <form action="home.php" method="POST">
        Subject name:
        <input type="text" name="subject_name" required>
        Subject code:
        <input type="text" name="subject_code">
        <button type="submit" name="add_subject">Add Subject</button>
    </form>

    <ul>
        <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
            <li>
                <form action="home.php" method="POST" style="display:inline;">
                    <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                    <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['subjectName']); ?>" required>
                    <input type="text" name="subject_code" value="<?php echo htmlspecialchars($subject['subjectCode']); ?>">
                    <button type="submit" name="rename_subject">Save</button>
                </form>

                <form action="home.php" method="POST" style="display:inline;">
                    <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" name="move_subject">Move up</button>
                </form>

                <form action="home.php" method="POST" style="display:inline;">
                    <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" name="move_subject">Move down</button>
                </form>

                <form action="delete_subject.php" method="POST" style="display:inline;"
                      onsubmit="return confirm('Delete this subject and its tasks?');">
                    <input type="hidden" name="id" value="<?php echo $subject['subjectId']; ?>">
                    <button type="submit">Delete</button>
                </form>
            </li>
        <?php endwhile; ?>
    </ul>

    <h2>Academic Tasks</h2>
    <p><a href="create_task.php">Create Task</a></p>
    <ul>
        <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
            <li>
                <a href="task.php?id=<?php echo $task['taskId']; ?>"><?php echo htmlspecialchars($task['title']); ?></a>
                — <?php echo htmlspecialchars($task['subjectName']); ?>
                (<?php echo htmlspecialchars($task['taskType']); ?>)
                Due: <?php echo htmlspecialchars($task['dueDate'] ?: 'Not set'); ?>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>
