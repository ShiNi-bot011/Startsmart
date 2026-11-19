<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['studentId'];
$message = '';
$error = '';
$requestedSemester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT);
$selectedSemester = $requestedSemester !== false && $requestedSemester !== null && $requestedSemester >= 1 && $requestedSemester <= 8
    ? $requestedSemester
    : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $subjectName = trim($_POST['subject_name'] ?? '');
        $subjectCode = mysqli_real_escape_string($conn, trim($_POST['subject_code'] ?? ''));
        $semesterNumber = (int) ($_POST['semester_number'] ?? 0);
        if ($subjectName === '') {
            $error = 'Subject name cannot be empty.';
        } elseif ($semesterNumber < 1 || $semesterNumber > 8) {
            $error = 'Choose a valid semester.';
        } else {
            $subjectName = mysqli_real_escape_string($conn, $subjectName);
            $sql = "INSERT INTO subjects (studentId, subjectName, subjectCode, semesterNumber)
                    VALUES ('$studentId', '$subjectName', '$subjectCode', $semesterNumber)";
        }

        if ($error === '' && mysqli_query($conn, $sql)) {
            $message = 'Subject added.';
            $selectedSemester = $semesterNumber;
        } elseif ($error === '') {
            $error = 'Subject already exists or could not be saved.';
        }
    }

    if (isset($_POST['rename_subject'])) {
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $subjectName = trim($_POST['subject_name'] ?? '');
        $subjectCode = mysqli_real_escape_string($conn, trim($_POST['subject_code'] ?? ''));
        $semesterNumber = (int) ($_POST['semester_number'] ?? 0);
        if ($subjectName === '') {
            $error = 'Subject name cannot be empty.';
        } elseif ($semesterNumber < 1 || $semesterNumber > 8) {
            $error = 'Choose a valid semester.';
        } else {
            $subjectName = mysqli_real_escape_string($conn, $subjectName);
            $sql = "UPDATE subjects
                    SET subjectName = '$subjectName', subjectCode = '$subjectCode', semesterNumber = $semesterNumber
                    WHERE subjectId = $subjectId AND studentId = $studentId";
        }

        if ($error === '' && mysqli_query($conn, $sql)) {
            $message = 'Subject updated.';
            $selectedSemester = $semesterNumber;
        } elseif ($error === '') {
            $error = 'Subject already exists or could not be updated.';
        }
    }

    if (isset($_POST['move_subject'])) {
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        $subjects = mysqli_query(
            $conn,
            "SELECT subjectId FROM subjects
             WHERE studentId = $studentId AND semesterNumber = $selectedSemester
             ORDER BY sortOrder, subjectId"
        );
        $subjectIds = [];

        if ($subjects) {
            while ($subject = mysqli_fetch_assoc($subjects)) {
                $subjectIds[] = $subject['subjectId'];
            }
        }

        $position = array_search($subjectId, $subjectIds);
        $nextPosition = $direction === 'up' ? $position - 1 : $position + 1;

        if ($direction !== 'up' && $direction !== 'down') {
            $error = 'Choose a valid subject order.';
        } elseif (!$subjects) {
            $error = 'Could not load subjects for reordering.';
        } elseif ($position !== false && isset($subjectIds[$nextPosition])) {
            $firstId = $subjectIds[$position];
            $secondId = $subjectIds[$nextPosition];
            $subjectIds[$position] = $secondId;
            $subjectIds[$nextPosition] = $firstId;

            if (!mysqli_begin_transaction($conn)) {
                $error = 'Could not start updating subject order.';
            } else {
                foreach ($subjectIds as $order => $id) {
                    if (!mysqli_query(
                        $conn,
                        "UPDATE subjects SET sortOrder = $order
                         WHERE subjectId = $id AND studentId = $studentId AND semesterNumber = $selectedSemester"
                    )) {
                        mysqli_rollback($conn);
                        $error = 'Could not update subject order.';
                        break;
                    }
                }
                if ($error === '') {
                    if (mysqli_commit($conn)) {
                        $message = 'Subject order updated.';
                    } else {
                        mysqli_rollback($conn);
                        $error = 'Could not save subject order.';
                    }
                }
            }
        }
    }
}

$subjects = mysqli_query(
    $conn,
    "SELECT * FROM subjects
     WHERE studentId = $studentId AND semesterNumber = $selectedSemester
     ORDER BY sortOrder, subjectId"
);
$tasks = mysqli_query(
    $conn,
    "SELECT academic_tasks.*, subjects.subjectName
     FROM academic_tasks
     JOIN subjects ON academic_tasks.subjectId = subjects.subjectId
     WHERE academic_tasks.studentId = $studentId AND subjects.semesterNumber = $selectedSemester
     ORDER BY academic_tasks.dueDate, academic_tasks.taskId"
);
$semesterYear = intdiv($selectedSemester - 1, 2) + 1;
$semesterInYear = (($selectedSemester - 1) % 2) + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - StartSmart</title>
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
        <section class="hero">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['studentName']); ?>! 👋</h1>
            <p>Pick a task, find what's blocking you, and get started.</p>
        </section>

        <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <section class="card">
            <div class="section-head">
                <h2>📚 Manage Subjects</h2>
                <span>Year <?php echo $semesterYear; ?> · Semester <?php echo $semesterInYear; ?></span>
            </div>

            <nav class="semester-pagination" aria-label="Choose semester">
                <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                    <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                    <a href="home.php?semester=<?php echo $semester; ?>"
                       <?php echo $semester === $selectedSemester ? 'aria-current="page"' : ''; ?>>
                        Y<?php echo $year; ?> S<?php echo $inYear; ?>
                    </a>
                <?php endfor; ?>
            </nav>

            <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST" class="add-form">
                <input type="text" name="subject_name" placeholder="Subject name" required>
                <input type="text" name="subject_code" placeholder="Code (e.g. SECV3104)">
                <select name="semester_number" aria-label="Subject semester" required>
                    <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                        <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                        <option value="<?php echo $semester; ?>" <?php echo $semester === $selectedSemester ? 'selected' : ''; ?>>
                            Year <?php echo $year; ?>, Semester <?php echo $inYear; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" name="add_subject">+ Add Subject</button>
            </form>

            <div style="margin-top:12px;">
                <?php if (mysqli_num_rows($subjects) === 0): ?>
                    <p class="empty">No subjects in this semester yet. Add one or import your UTM calendar.</p>
                <?php endif; ?>
                <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                    <div class="subject-row">
                        <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST" class="edit-form">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                            <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['subjectName']); ?>" required>
                            <input type="text" name="subject_code" value="<?php echo htmlspecialchars($subject['subjectCode']); ?>">
                            <select name="semester_number" aria-label="Subject semester" required>
                                <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                                    <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                                    <option value="<?php echo $semester; ?>" <?php echo (int) $subject['semesterNumber'] === $semester ? 'selected' : ''; ?>>
                                        Y<?php echo $year; ?> S<?php echo $inYear; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" name="rename_subject" class="btn-small">Save</button>
                        </form>

                        <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" name="move_subject" class="btn-icon" title="Move up">↑</button>
                        </form>

                        <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['subjectId']; ?>">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" name="move_subject" class="btn-icon" title="Move down">↓</button>
                        </form>

                        <form action="delete_subject.php" method="POST"
                              onsubmit="return confirm('Delete this subject and its tasks?');">
                            <input type="hidden" name="id" value="<?php echo $subject['subjectId']; ?>">
                            <input type="hidden" name="semester" value="<?php echo $selectedSemester; ?>">
                            <button type="submit" class="btn-small btn-danger">Delete</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="card">
            <div class="section-head">
                <h2>✅ Academic Tasks</h2>
                <a class="btn btn-small" href="create_task.php">+ Create Task</a>
            </div>

            <?php if (mysqli_num_rows($tasks) === 0): ?>
                <p class="empty">No tasks yet. Create your first one to get started!</p>
            <?php endif; ?>

            <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                <?php
                    $typeSlug = strtolower(str_replace(' ', '-', $task['taskType']));
                    $overdue = $task['dueDate'] && strtotime($task['dueDate']) < strtotime('today');
                ?>
                <a class="task-item type-<?php echo $typeSlug; ?>" href="task.php?id=<?php echo $task['taskId']; ?>">
                    <div>
                        <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                        <div class="task-meta">
                            <?php echo htmlspecialchars($task['subjectName']); ?> ·
                            <span class="badge badge-<?php echo $typeSlug; ?>"><?php echo htmlspecialchars($task['taskType']); ?></span>
                        </div>
                    </div>
                    <div class="task-due<?php echo $overdue ? ' overdue' : ''; ?>">
                        📅 <?php echo htmlspecialchars($task['dueDate'] ?: 'No due date'); ?>
                    </div>
                </a>
            <?php endwhile; ?>
        </section>
    </main>
</body>
</html>
