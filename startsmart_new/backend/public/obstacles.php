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

// Join academic_tasks with subjects so obstacle prompts adapt to the subject & task type
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

// Fetch latest saved obstacle (if student is updating an existing blocker over time)
$latestObstacleRes = mysqli_query(
    $conn,
    "SELECT * FROM task_obstacles WHERE taskId = $taskId ORDER BY obstacleId DESC LIMIT 1"
);
$latestObstacle = $latestObstacleRes ? mysqli_fetch_assoc($latestObstacleRes) : null;

$promptSet = us005_getSubjectAwareObstaclePromptSet($task);
$allowedCategories = array_merge($promptSet['categories'], ['Custom / Other']);
$allowedSecondary = $promptSet['secondaryOptions'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category        = trim($_POST['category'] ?? '');
    $customCategory  = trim($_POST['custom_category'] ?? '');
    $answer2         = trim($_POST['answer2'] ?? '');
    $answer3         = trim($_POST['answer3'] ?? '');
    $details         = trim($_POST['details'] ?? '');
    $confirmed       = isset($_POST['confirm']) ? 1 : 0;

    if (isset($_POST['skip'])) {
        $category  = $promptSet['categories'][0] ?? 'Unclear where to start';
        $answer2   = '';
        $answer3   = '';
        $details   = '';
        $confirmed = 1;
    } else {
        if (!in_array($category, $allowedCategories, true)) {
            $error = 'Please choose an obstacle option from the list.';
        } elseif ($category === 'Custom / Other') {
            if ($customCategory === '') {
                $error = 'Please enter your custom obstacle label.';
            } elseif (strlen($customCategory) > 100) {
                $error = 'Custom obstacle must be 100 characters or fewer.';
            } else {
                $category = $customCategory;
            }
        }

        if ($error === '' && $answer2 !== '' && !in_array($answer2, $allowedSecondary, true)) {
            $error = 'Please choose a valid option for the second question.';
        }
        if ($error === '' && $answer3 !== '' && !in_array($answer3, $allowedSecondary, true)) {
            $error = 'Please choose a valid option for the third question.';
        }
        if ($error === '' && strlen($details) > 2000) {
            $error = 'Details must be 2,000 characters or fewer.';
        }
    }

    if ($error === '') {
        $safeCategory = mysqli_real_escape_string($conn, $category);
        $safeAnswer2  = mysqli_real_escape_string($conn, $answer2);
        $safeAnswer3  = mysqli_real_escape_string($conn, $answer3);
        $safeDetails  = mysqli_real_escape_string($conn, $details);
        $subjectId    = (int) $task['subjectId'];

        $sql = "INSERT INTO task_obstacles
                (subjectId, taskId, obstacleType, description, answer1, answer2, answer3, isConfirmed)
                VALUES ($subjectId, $taskId, '$safeCategory', '$safeDetails', '$safeCategory', '$safeAnswer2', '$safeAnswer3', $confirmed)";

        if (mysqli_query($conn, $sql)) {
            header("Location: task.php?id=$taskId");
            exit;
        } else {
            $error = 'Could not save your answer.';
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
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <main class="container">
        <section class="card">
            <h2>🧩 Identify &amp; Update Task Obstacles</h2>
            <div class="callout">
                <strong>Subject:</strong> <?php echo htmlspecialchars($subjectDisplay); ?><br>
                <strong>Task:</strong> <?php echo htmlspecialchars($task['title']); ?>
                <span class="badge"><?php echo htmlspecialchars($task['taskType']); ?></span><br>
                <small>Tailored context: <?php echo htmlspecialchars($promptSet['contextLabel']); ?></small>
            </div>

            <?php if ($latestObstacle): ?>
                <p class="note">
                    <strong>Current Active Obstacle:</strong> <?php echo htmlspecialchars($latestObstacle['obstacleType']); ?>
                    (Saving below updates your active blocker as your progress changes.)
                </p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="obstacles.php?task_id=<?php echo $taskId; ?>" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

                <label class="field"><?php echo htmlspecialchars($promptSet['q1Label']); ?>
                    <select name="category" id="categorySelect" required onchange="toggleCustomInput()">
                        <?php foreach ($promptSet['categories'] as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>"
                                <?php echo ($latestObstacle && $latestObstacle['obstacleType'] === $opt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opt); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Custom / Other">Custom / Other (Type your own)</option>
                    </select>
                </label>

                <label class="field" id="customCategoryField" style="display:none;">Custom obstacle label
                    <input type="text" name="custom_category" maxlength="100"
                           placeholder="e.g., API authentication error or Exam schedule clash">
                </label>

                <label class="field"><?php echo htmlspecialchars($promptSet['q2Label']); ?>
                    <select name="answer2">
                        <option value="">Skip</option>
                        <?php foreach ($promptSet['secondaryOptions'] as $opt2): ?>
                            <option value="<?php echo htmlspecialchars($opt2); ?>">
                                <?php echo htmlspecialchars($opt2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field"><?php echo htmlspecialchars($promptSet['q3Label']); ?>
                    <select name="answer3">
                        <option value="">Skip</option>
                        <?php foreach ($promptSet['secondaryOptions'] as $opt3): ?>
                            <option value="<?php echo htmlspecialchars($opt3); ?>">
                                <?php echo htmlspecialchars($opt3); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">Specific details or notes <small>(optional — helps generate better hints)</small>
                    <textarea name="details" placeholder="Describe what you already tried or where you got stuck..."><?php
                        echo htmlspecialchars($latestObstacle['description'] ?? '');
                    ?></textarea>
                </label>

                <label class="check"><input type="checkbox" name="confirm" checked> I confirm this is my current obstacle</label>

                <div class="btn-row">
                    <button type="submit">Save &amp; Update Obstacle</button>
                    <button type="submit" name="skip" value="1" class="btn-ghost">Skip</button>
                    <a class="btn btn-ghost" href="task.php?id=<?php echo $taskId; ?>">Back to Task</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        function toggleCustomInput() {
            var select = document.getElementById('categorySelect');
            var customField = document.getElementById('customCategoryField');
            customField.style.display = (select.value === 'Custom / Other') ? 'block' : 'none';
        }
        toggleCustomInput();
    </script>
</body>
</html>