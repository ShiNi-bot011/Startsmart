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
    
$sort = $_GET['sort'] ?? 'default';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Subject
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
    
    // Handle Edit Subject
    if (isset($_POST['edit_subject'])) {
        $editSubjectId = (int)$_POST['edit_subject_id'];
        $newName = mysqli_real_escape_string($conn, trim($_POST['edit_subject_name']));
        $newCode = mysqli_real_escape_string($conn, trim($_POST['edit_subject_code']));
        $newSemester = (int)$_POST['edit_semester_number'];
        
        $sql = "UPDATE subjects SET subjectName = '$newName', subjectCode = '$newCode', semesterNumber = $newSemester 
                WHERE subjectId = $editSubjectId AND studentId = $studentId";
        if (mysqli_query($conn, $sql)) {
            $message = 'Subject updated successfully.';
            $selectedSemester = $newSemester;
        } else {
            $error = 'Could not update subject.';
        }
    }
    
    // Handle Bulk Task Deletion
    if (isset($_POST['delete_selected_tasks']) && !empty($_POST['task_ids'])) {
        $taskIds = implode(',', array_map('intval', $_POST['task_ids']));
        $deleteSql = "DELETE FROM academic_tasks WHERE taskId IN ($taskIds) AND studentId = $studentId";
        if (mysqli_query($conn, $deleteSql)) {
            $message = 'Selected tasks deleted.';
        }
    }
}

$subjects = mysqli_query(
    $conn,
    "SELECT * FROM subjects
     WHERE studentId = $studentId AND semesterNumber = $selectedSemester
     ORDER BY sortOrder, subjectId"
);

$orderBy = "ORDER BY academic_tasks.dueDate ASC";
if ($sort === 'az') $orderBy = "ORDER BY academic_tasks.title ASC";
elseif ($sort === 'modified') $orderBy = "ORDER BY academic_tasks.taskId DESC";
elseif ($sort === 'deadline') $orderBy = "ORDER BY academic_tasks.dueDate ASC";

$tasks = mysqli_query(
    $conn,
    "SELECT academic_tasks.*, subjects.subjectName, subjects.subjectId
     FROM academic_tasks
     JOIN subjects ON academic_tasks.subjectId = subjects.subjectId
     WHERE academic_tasks.studentId = $studentId 
     AND subjects.semesterNumber = $selectedSemester
     AND academic_tasks.status != 'Completed'
     $orderBy"
);

$archiveTasks = mysqli_query(
    $conn,
    "SELECT academic_tasks.*, subjects.subjectName
     FROM academic_tasks
     JOIN subjects ON academic_tasks.subjectId = subjects.subjectId
     WHERE academic_tasks.studentId = $studentId 
     AND subjects.semesterNumber = $selectedSemester
     AND academic_tasks.status = 'Completed'
     ORDER BY academic_tasks.dueDate DESC"
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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        .search-filter-row { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; }
        .search-bar { flex-grow: 1; padding: 10px 15px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; }
        .filter-select { padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; background: white; cursor: pointer; }
        
        .archive-item { padding: 12px 15px; border-bottom: 1px solid #eee; color: #64748b; display: flex; justify-content: space-between; align-items: center;}
        .archive-title { text-decoration: line-through; font-weight: 600; color: #94a3b8; }
        .btn-view { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-view:hover { background: #c7d2fe; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 24px; border-radius: 8px; width: 100%; max-width: 400px; }
        .modal-content label { display: block; margin-top: 10px; font-weight: 600; font-size: 14px; }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ddd; border-radius: 4px; }
        .modal-btns { margin-top: 20px; display: flex; gap: 10px; }
        
        .subject-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .subject-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;}
        .subject-title-display { font-weight: 700; font-size: 18px; color: #1e293b; display: flex; align-items: center; }
        .subject-title-display span { font-weight: 500; color: #64748b; font-size: 14px; margin-left: 8px; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;}
        
        .task-item { display: flex; justify-content: space-between; align-items: center; color: inherit; background: #f8fafc; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e2e8f0; transition: background 0.2s; gap: 15px;}
        .task-item:hover { background-color: #f1f5f9; border-color: #cbd5e1; }
        
        .task-horizontal-info { display: flex; align-items: center; gap: 20px; flex-grow: 1; min-width: 0; }
        .task-type-col { min-width: 100px; font-size: 13px; color: #64748b; flex-shrink: 0; }
        
        .task-title-col { flex-grow: 1; min-width: 0; }
        .task-title-col a { font-weight: 600; font-size: 15px; color: #6366f1; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .task-title-col a:hover { text-decoration: underline; }
        
        .task-status-col { min-width: 100px; font-size: 13px; color: #0ea5e9; font-weight: 600; flex-shrink: 0;}
        .task-due-col { min-width: 150px; font-size: 13px; color: #64748b; text-align: right; flex-shrink: 0;}
        
        .task-actions-col { display: flex; align-items: center; gap: 10px; margin-left: 10px; flex-shrink: 0;}
        
        .icon-btn { font-size: 18px; text-decoration: none; cursor: pointer; padding: 6px 8px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; transition: transform 0.2s, background 0.2s; border: none; outline: none; position: relative; background: #f1f5f9; }
        .icon-btn:hover { transform: scale(1.1); background: #e2e8f0; }
        .icon-btn:focus { outline: none; box-shadow: none; }
        
        .icon-btn::after { position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%); background-color: #333; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 10; }
        .icon-btn:hover::after { opacity: 1; }
        .hint-btn::after { content: "Task Hint"; }
        .obstacle-btn::after { content: "Identify Obstacles"; }
        
        .drag-handle { display: none; cursor: grab; font-size: 20px; color: #94a3b8; padding-right: 15px; user-select: none; }
        .is-editing .drag-handle { display: block; }
        .sortable-ghost { opacity: 0.4; background-color: #e0e7ff !important; border: 2px dashed #6366f1 !important; }
        
        .menu-container { position: relative; display: inline-block; }
        .dots-btn { background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 4px 8px; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
        .dots-btn:hover { background: #f1f5f9; color: #1e293b; }
        
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 6px; z-index: 100; min-width: 140px; padding: 4px 0; }
        .dropdown-menu a, .dropdown-menu button { display: block; width: 100%; text-align: left; padding: 8px 15px; border: none; background: none; font-size: 14px; color: #333; cursor: pointer; text-decoration: none; }
        .dropdown-menu a:hover, .dropdown-menu button:hover { background: #f1f5f9; }
        
        .bulk-delete-bar { display: none; background: #fee2e2; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; justify-content: space-between; align-items: center; border: 1px solid #f87171;}
        .bulk-delete-bar button { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;}
        .task-select-checkbox { display: none; width: 20px; height: 20px; cursor: pointer; margin-right: 10px; accent-color: #ef4444; flex-shrink: 0;}
        .edit-mode-active .task-select-checkbox { display: block; }
        
        .select-all-label { display: flex; align-items: center; gap: 5px; font-size: 14px; font-weight: 600; color: #991b1b; cursor: pointer; }
    </style>
</head>
<body>
    <header class="navbar">
        <a class="brand" href="home.php">🚀 StartSmart</a>
        <nav>
            <a href="home.php">Home</a>
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

        <section class="card" style="background: transparent; box-shadow: none; padding: 0; border: none;">
            
            <div class="section-head" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin: 0;">📚 Manage Subjects</h2>
                    <span style="font-size: 14px; color: #64748b;">Year <?php echo $semesterYear; ?> · Semester <?php echo $semesterInYear; ?></span>
                </div>
                
                <form method="GET" class="search-filter-row">
                    <input type="hidden" name="semester" value="<?php echo $selectedSemester; ?>">
                    <input type="text" id="taskSearch" class="search-bar" placeholder="Search a task/subject..." onkeyup="filterTasks()">
                    
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Filter: Default</option>
                        <option value="az" <?php echo $sort == 'az' ? 'selected' : ''; ?>>A to Z</option>
                        <option value="modified" <?php echo $sort == 'modified' ? 'selected' : ''; ?>>Date Modified</option>
                        <option value="deadline" <?php echo $sort == 'deadline' ? 'selected' : ''; ?>>Deadline Order</option>
                    </select>
                </form>

                <div style="display: flex; gap: 10px;">
                    <button id="editOrderBtn" class="btn btn-small" onclick="toggleEditOrder()" style="background: #e2e8f0; color: #333;">Edit Order</button>
                    <button class="btn btn-small" onclick="openSubjectModal()" style="background: #6366f1; color: white; padding: 10px 15px;">+ Add Subject</button>
                </div>
            </div>

            <nav class="semester-pagination" aria-label="Choose semester" style="margin-bottom: 20px;">
                <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                    <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                    <a href="home.php?semester=<?php echo $semester; ?>&sort=<?php echo $sort; ?>"
                       <?php echo $semester === $selectedSemester ? 'aria-current="page"' : ''; ?>>
                        Y<?php echo $year; ?> S<?php echo $inYear; ?>
                    </a>
                <?php endfor; ?>
            </nav>

            <div id="subjectContainer">
                <?php if (mysqli_num_rows($subjects) === 0): ?>
                    <p class="empty">No subjects in this semester yet. Click "+ Add Subject" above to start.</p>
                <?php endif; ?>

                <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
                    <div class="subject-card" data-id="<?php echo $subject['subjectId']; ?>" id="subject-card-<?php echo $subject['subjectId']; ?>">
                        <div class="subject-header-row">
                            <div style="display: flex; align-items: center;">
                                <div class="drag-handle" title="Drag to reorder">≡</div>
                                <div class="subject-title-display">
                                    <?php echo htmlspecialchars($subject['subjectName']); ?> 
                                    <span><?php echo htmlspecialchars($subject['subjectCode']); ?></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" class="btn-small" style="background-color: #6366f1; color: white;" 
                                        onclick="openTaskModal(<?php echo $subject['subjectId']; ?>, '<?php echo htmlspecialchars($subject['subjectName'], ENT_QUOTES); ?>')">
                                    + Add Task
                                </button>
                                
                                <div class="menu-container">
                                    <button class="dots-btn" onclick="toggleMenu(event, <?php echo $subject['subjectId']; ?>)">&#8942;</button>
                                    <div class="dropdown-menu" id="menu-<?php echo $subject['subjectId']; ?>">
                                        <button onclick="openEditSubjectModal(<?php echo $subject['subjectId']; ?>, '<?php echo htmlspecialchars($subject['subjectName'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($subject['subjectCode'], ENT_QUOTES); ?>', <?php echo $subject['semesterNumber']; ?>)">
                                            Edit Subject
                                        </button>
                                        <button onclick="toggleTaskEditMode(<?php echo $subject['subjectId']; ?>)">
                                            Edit Tasks
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" id="bulk-delete-form-<?php echo $subject['subjectId']; ?>">
                            <div class="bulk-delete-bar" id="bulk-bar-<?php echo $subject['subjectId']; ?>">
                                <label class="select-all-label">
                                    <input type="checkbox" id="select-all-<?php echo $subject['subjectId']; ?>" onchange="toggleSelectAll(<?php echo $subject['subjectId']; ?>, this)">
                                    Select All
                                </label>
                                <div>
                                    <button type="submit" name="delete_selected_tasks" onclick="return confirm('Delete selected tasks?')">Delete Selected</button>
                                    <button type="button" onclick="cancelTaskEditMode(<?php echo $subject['subjectId']; ?>)" style="background: #64748b; margin-left: 10px;">Cancel</button>
                                </div>
                            </div>

                            <div class="subject-tasks" id="tasks-container-<?php echo $subject['subjectId']; ?>">
                                <?php 
                                $subjectHasTasks = false;
                                mysqli_data_seek($tasks, 0);
                                while ($task = mysqli_fetch_assoc($tasks)): 
                                    if ($task['subjectId'] == $subject['subjectId']):
                                        $subjectHasTasks = true;
                                        $typeSlug = strtolower(str_replace(' ', '-', $task['taskType']));
                                        $overdue = $task['dueDate'] && strtotime($task['dueDate']) < strtotime('today');
                                        $displayDate = $task['dueDate'] ? date('M d, Y g:i A', strtotime($task['dueDate'])) : 'No due date';
                                ?>
                                    <div class="task-item type-<?php echo $typeSlug; ?> task-row" data-title="<?php echo strtolower(htmlspecialchars($task['title'] . ' ' . $subject['subjectName'])); ?>">
                                        
                                        <input type="checkbox" name="task_ids[]" value="<?php echo $task['taskId']; ?>" class="task-select-checkbox task-checkbox-<?php echo $subject['subjectId']; ?>">
                                        
                                        <div class="task-horizontal-info">
                                            <div class="task-type-col">
                                                <span class="badge badge-<?php echo $typeSlug; ?>"><?php echo htmlspecialchars($task['taskType']); ?></span>
                                            </div>
                                            <div class="task-title-col">
                                                <a href="task.php?id=<?php echo $task['taskId']; ?>" title="<?php echo htmlspecialchars($task['title']); ?>">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                            </div>
                                            <div class="task-status-col">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </div>
                                        </div>

                                        <div class="task-actions-col">
                                            <a href="obstacles.php?task_id=<?php echo $task['taskId']; ?>" class="icon-btn obstacle-btn">
                                                🧩
                                            </a>
                                            <a href="request_hint.php?task_id=<?php echo $task['taskId']; ?>" class="icon-btn hint-btn">
                                                💡
                                            </a>
                                            
                                            <div class="task-due-col<?php echo $overdue ? ' text-red-500' : ''; ?>">
                                                📅 <?php echo htmlspecialchars($displayDate); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endwhile; 
                                if (!$subjectHasTasks): 
                                ?>
                                    <p class="empty" style="font-size: 14px; padding: 5px; color: #94a3b8; margin: 0;">No tasks yet for this subject.</p>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="card">
            <div class="section-head"><h2>📂 Task Logbook (Archive)</h2></div>
            <div id="archiveList">
                <?php if (mysqli_num_rows($archiveTasks) === 0): ?>
                    <p class="empty">No completed tasks yet.</p>
                <?php else: ?>
                    <?php while ($arc = mysqli_fetch_assoc($archiveTasks)): ?>
                        <div class="archive-item">
                            <div>
                                <span class="archive-title"><?php echo htmlspecialchars($arc['title']); ?></span> 
                                <span style="font-size: 12px;">(<?php echo htmlspecialchars($arc['subjectName']); ?>)</span>
                            </div>
                            <div>
                                <!-- View button links to task.php -->
                                <a href="task.php?id=<?php echo $arc['taskId']; ?>" class="btn-view">View</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- MODAL: ADD SUBJECT -->
    <div id="subjectModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Add New Subject</h3>
            <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST">
                <label>Subject Name</label>
                <input type="text" name="subject_name" required placeholder="e.g. Application Development">
                <label>Subject Code</label>
                <input type="text" name="subject_code" placeholder="e.g. SECV3104">
                <label>Year & Semester</label>
                <select name="semester_number" required>
                    <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                        <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                        <option value="<?php echo $semester; ?>" <?php echo $semester === $selectedSemester ? 'selected' : ''; ?>>
                            Year <?php echo $year; ?>, Semester <?php echo $inYear; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="modal-btns">
                    <button type="submit" name="add_subject" class="btn-small" style="background: #6366f1; color: white; padding: 10px 15px;">Save Subject</button>
                    <button type="button" class="btn-small" onclick="closeSubjectModal()" style="padding: 10px 15px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT SUBJECT -->
    <div id="editSubjectModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Edit Subject</h3>
            <form action="home.php?semester=<?php echo $selectedSemester; ?>" method="POST">
                <input type="hidden" name="edit_subject_id" id="editSubjectId">
                <label>Subject Name</label>
                <input type="text" name="edit_subject_name" id="editSubjectName" required>
                <label>Subject Code</label>
                <input type="text" name="edit_subject_code" id="editSubjectCode">
                <label>Year & Semester</label>
                <select name="edit_semester_number" id="editSemesterNumber" required>
                    <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                        <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                        <option value="<?php echo $semester; ?>">
                            Year <?php echo $year; ?>, Semester <?php echo $inYear; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="modal-btns">
                    <button type="submit" name="edit_subject" class="btn-small" style="background: #10b981; color: white; padding: 10px 15px;">Update Subject</button>
                    <button type="button" class="btn-small" onclick="closeEditSubjectModal()" style="padding: 10px 15px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD TASK -->
    <div id="taskModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Create New Task</h3>
            <form id="createTaskForm">
                <input type="hidden" id="modalSubjectId" name="subject_id">
                <label>Subject</label>
                <input type="text" id="modalSubjectName" readonly style="background: #f3f4f6;">
                <label>Task Title</label>
                <input type="text" name="title" required placeholder="e.g. Write SRS test cases">
                <label>Task Type</label>
                <select name="task_type" required>
                    <option value="General">General</option>
                    <option value="Exam Prep">Exam Prep</option>
                    <option value="Major Project">Major Project</option>
                </select>
                <label>Due Date & Time</label>
                <input type="date" name="due_date" id="dueDateInput" required>
                <input type="time" name="due_time" id="dueTimeInput" required style="margin-top: 5px;">
                <div class="modal-btns">
                    <button type="submit" class="btn-small" style="background: #6366f1; color: white; padding: 10px 15px;">Save Task</button>
                    <button type="button" class="btn-small" onclick="closeTaskModal()" style="padding: 10px 15px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu(event, subjectId) {
            event.stopPropagation(); 
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== 'menu-' + subjectId) menu.style.display = 'none';
            });
            const menu = document.getElementById('menu-' + subjectId);
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dots-btn')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        }

        function toggleTaskEditMode(subjectId) {
            const card = document.getElementById('subject-card-' + subjectId);
            const bar = document.getElementById('bulk-bar-' + subjectId);
            card.classList.toggle('edit-mode-active');
            bar.style.display = card.classList.contains('edit-mode-active') ? 'flex' : 'none';
            document.getElementById('menu-' + subjectId).style.display = 'none';
        }

        function cancelTaskEditMode(subjectId) {
            const card = document.getElementById('subject-card-' + subjectId);
            const bar = document.getElementById('bulk-bar-' + subjectId);
            card.classList.remove('edit-mode-active');
            bar.style.display = 'none';
            document.querySelectorAll(`#bulk-delete-form-${subjectId} .task-select-checkbox`).forEach(cb => cb.checked = false);
            document.getElementById('select-all-' + subjectId).checked = false;
        }

        function toggleSelectAll(subjectId, selectAllCheckbox) {
            const checkboxes = document.querySelectorAll(`.task-checkbox-${subjectId}`);
            checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        }

        let isEditingOrder = false;
        const subjectContainer = document.getElementById('subjectContainer');
        
        const sortable = new Sortable(subjectContainer, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                const subjectCards = document.querySelectorAll('.subject-card');
                let orderData = [];
                subjectCards.forEach((card, index) => {
                    orderData.push({ id: card.getAttribute('data-id'), order: index });
                });
                fetch('update_subject_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
            }
        });

        function toggleEditOrder() {
            isEditingOrder = !isEditingOrder;
            const btn = document.getElementById('editOrderBtn');
            subjectContainer.classList.toggle('is-editing', isEditingOrder);
            btn.textContent = isEditingOrder ? 'Done Editing' : 'Edit Order';
            btn.style.background = isEditingOrder ? '#10b981' : '#e2e8f0';
            btn.style.color = isEditingOrder ? 'white' : '#333';
        }

        function openSubjectModal() { document.getElementById('subjectModal').style.display = 'flex'; }
        function closeSubjectModal() { document.getElementById('subjectModal').style.display = 'none'; }
        
        // Open Edit Subject Modal
        function openEditSubjectModal(id, name, code, semester) {
            document.getElementById('editSubjectId').value = id;
            document.getElementById('editSubjectName').value = name;
            document.getElementById('editSubjectCode').value = code;
            document.getElementById('editSemesterNumber').value = semester;
            document.getElementById('editSubjectModal').style.display = 'flex';
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
        }
        function closeEditSubjectModal() { document.getElementById('editSubjectModal').style.display = 'none'; }

        function openTaskModal(subjectId, subjectName) {
            document.getElementById('modalSubjectId').value = subjectId;
            document.getElementById('modalSubjectName').value = subjectName;
            document.getElementById('taskModal').style.display = 'flex';
        }
        function closeTaskModal() { document.getElementById('taskModal').style.display = 'none'; }

        document.getElementById('createTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('create_task.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') { alert(data.message); location.reload(); } 
                else { alert(data.message); }
            });
        });

        function filterTasks() {
            const input = document.getElementById('taskSearch').value.toLowerCase();
            const items = document.querySelectorAll('.task-row');
            items.forEach(item => {
                const text = item.getAttribute('data-title');
                item.style.display = text.includes(input) ? 'flex' : 'none';
            });
        }

        const now = new Date();
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        document.getElementById('dueDateInput').min = todayStr;
    </script>
</body>
</html>