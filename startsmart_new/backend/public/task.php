<?php
// Turn on error reporting so if something fails, you see WHY instead of a blank page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int)$_SESSION['studentId'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId === 0) {
    die('Invalid Task ID.');
}

// Handle Edit Task (Name, Deadline, Category)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $newTitle = mysqli_real_escape_string($conn, trim($_POST['edit_title']));
    $newType = mysqli_real_escape_string($conn, $_POST['edit_type']);
    
    // Combine Date and Time
    $editDate = $_POST['edit_date'];
    $editTime = $_POST['edit_time'];
    $newDueDate = $editDate . ' ' . $editTime . ':00';

    if ($newTitle !== '') {
        $sql = "UPDATE academic_tasks 
                SET title = '$newTitle', taskType = '$newType', dueDate = '$newDueDate' 
                WHERE taskId = $taskId AND studentId = $studentId";
        mysqli_query($conn, $sql);
    }
    header("Location: task.php?id=$taskId");
    exit;
}

$taskResult = mysqli_query(
    $conn,
    "SELECT * FROM academic_tasks WHERE taskId = $taskId AND studentId = $studentId"
);

if (!$taskResult) {
    die('Database Error: ' . mysqli_error($conn));
}

$task = mysqli_fetch_assoc($taskResult);

if (!$task) {
    die('Task not found or you do not have permission to view it.');
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

// Format dates for the edit form
$displayDate = $task['dueDate'] ? date('Y-m-d', strtotime($task['dueDate'])) : '';
$displayTime = $task['dueDate'] ? date('H:i', strtotime($task['dueDate'])) : '';
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
    <style>
        .title-row { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        
        .back-home-link {
            display: inline-block; margin-bottom: 20px; color: #6366f1; text-decoration: none;
            font-weight: 600; font-size: 14px; background: #e0e7ff; padding: 8px 12px;
            border-radius: 6px; transition: background 0.2s;
        }
        .back-home-link:hover { background: #c7d2fe; }

        .action-buttons { display: flex; align-items: center; gap: 10px; margin-left: auto; }

        .btn-mark-done {
            background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px;
            font-weight: 600; cursor: pointer; font-size: 15px; display: inline-flex; align-items: center; gap: 8px;
            transition: background 0.2s;
        }
        .btn-mark-done:hover { background: #059669; }

        /* 3 Dots Menu Style */
        .menu-container { position: relative; display: inline-block; }
        .dots-btn { 
            background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 20px; font-weight: bold; 
            color: #64748b; cursor: pointer; padding: 8px 12px; border-radius: 6px; 
            display: flex; align-items: center; justify-content: center;
        }
        .dots-btn:hover { background: #e2e8f0; color: #1e293b; }
        
        .dropdown-menu { 
            display: none; position: absolute; right: 0; top: 110%; background: white; 
            border: 1px solid #e2e8f0; border-radius: 6px; z-index: 100; min-width: 140px; padding: 4px 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .dropdown-menu button { 
            display: block; width: 100%; text-align: left; padding: 8px 15px; border: none; 
            background: none; font-size: 14px; color: #333; cursor: pointer; 
        }
        .dropdown-menu button:hover { background: #f1f5f9; }

        /* Edit Task Form */
        .edit-task-form {
            display: none; margin-top: 20px; background: #f8fafc; padding: 20px;
            border-radius: 8px; border: 1px solid #e2e8f0; flex-direction: column; gap: 12px;
        }
        .edit-task-form label { font-weight: 600; font-size: 14px; color: #1e293b; }
        .edit-task-form input, .edit-task-form select { 
            padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 100%;
        }
        .edit-form-row { display: flex; gap: 10px; }
        .edit-form-row > div { flex: 1; }
    </style>
</head>
<body>
    <header class="navbar">
        <a class="brand" href="home.php">🚀 StartSmart</a>
        <nav>
            <a href="home.php">Home</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <main class="container">
        <a href="home.php" class="back-home-link">&larr; Back to Home</a>

        <?php $typeSlug = strtolower(str_replace(' ', '-', $task['taskType'])); ?>
        
        <section class="hero">
            <div class="title-row" style="justify-content: space-between;">
                <div>
                    <h1 style="margin: 0;"><?php echo htmlspecialchars($task['title']); ?></h1>
                </div>
                
                <div class="action-buttons">
                    <!-- Mark as Done Button -->
                    <button class="btn-mark-done" onclick="markAsDone()">✔ Mark as Done</button>
                    <!-- 3 Dots Menu -->
                    <div class="menu-container">
                        <button class="dots-btn" onclick="toggleMenu(event)">&#8942;</button>
                        <div class="dropdown-menu" id="taskMenu">
                            <button onclick="toggleEditForm()">Edit Task</button>
                        </div>
                    </div>

                </div>
            </div>

            <p style="margin-top: 15px;"><?php echo htmlspecialchars($subject['subjectName']); ?></p>

            <!-- Hidden Edit Form -->
            <form method="POST" class="edit-task-form" id="editTaskForm">
                <h3 style="margin-top: 0; margin-bottom: 5px;">Edit Task Details</h3>
                
                <div>
                    <label>Task Name</label>
                    <input type="text" name="edit_title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div>
                    <label>Category (Type)</label>
                    <select name="edit_type" required>
                        <option value="General" <?php echo $task['taskType'] == 'General' ? 'selected' : ''; ?>>General</option>
                        <option value="Exam Prep" <?php echo $task['taskType'] == 'Exam Prep' ? 'selected' : ''; ?>>Exam Prep</option>
                        <option value="Major Project" <?php echo $task['taskType'] == 'Major Project' ? 'selected' : ''; ?>>Major Project</option>
                    </select>
                </div>

                <div class="edit-form-row">
                    <div>
                        <label>Deadline Date</label>
                        <input type="date" name="edit_date" value="<?php echo $displayDate; ?>" required>
                    </div>
                    <div>
                        <label>Deadline Time</label>
                        <input type="time" name="edit_time" value="<?php echo $displayTime; ?>" required>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" name="update_task" style="background: #6366f1; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Save Changes</button>
                    <button type="button" onclick="toggleEditForm()" style="background: #e2e8f0; color: #333; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </section>

        <!-- Task Details Section -->
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
                <div>
                    <div class="label">Status</div>
                    <div class="value"><?php echo htmlspecialchars($task['status']); ?></div>
                </div>
            </div>
            <?php if (trim($task['description']) !== ''): ?>
                <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">
                <div class="label" style="font-size:0.75rem;text-transform:uppercase;color:var(--muted);">Details</div>
                <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
            <?php endif; ?>
        </section>

        <!-- Task Obstacle Section -->
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

        <!-- Task Hint Section -->
        <section class="card">
            <h3>💡 Task Hint</h3>
            <p class="note">Hint requests recorded</p>
            <p class="hint-count" style="font-size: 24px; font-weight: bold; margin: 10px 0;"><?php echo $hintCount; ?></p>
            <a class="btn btn-small" href="request_hint.php?task_id=<?php echo $taskId; ?>">Request a Hint</a>
        </section>

    </main>

    <script>
        // Close menu when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dots-btn')) {
                document.getElementById('taskMenu').style.display = 'none';
            }
        }

        function toggleMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('taskMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        function toggleEditForm() {
            const form = document.getElementById('editTaskForm');
            form.style.display = form.style.display === 'flex' ? 'none' : 'flex';
            document.getElementById('taskMenu').style.display = 'none'; // Close menu
        }

        function markAsDone() {
            if (confirm('Mark this task as completed? It will be moved to the Archive.')) {
                fetch('update_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=<?php echo $taskId; ?>&action=complete'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Task completed successfully!');
                        window.location.href = 'home.php';
                    } else {
                        alert('Failed to complete task.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
            }
        }
    </script>
</body>
</html>