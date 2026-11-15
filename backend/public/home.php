<?php
// backend/public/home.php
// Student Dashboard & Manage Subjects (US003)
session_start();
include '../src/Config/Database.php';

// Guard: Kick to login if not authenticated
if (!isset($_SESSION['studentId'])) {
    header("Location: login.php");
    exit;
}

$studentId   = $_SESSION['studentId'];
$studentName = $_SESSION['studentName'];
$msg         = '';

// Handle "Add Subject" Form Submission (US003 - Shi Ni)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subjectName = trim($_POST['subject_name']);
    $subjectCode = trim($_POST['subject_code']);

    if (empty($subjectName)) {
        $msg = "Subject name cannot be empty.";
    } else {
        // Check for duplicate subject name for this student
        $checkSql = "SELECT * FROM subjects WHERE studentId = '$studentId' AND subjectName = '$subjectName'";
        $checkResult = mysqli_query($conn, $checkSql);

        if (mysqli_num_rows($checkResult) > 0) {
            $msg = "Subject already exists!";
        } else {
            $insertSql = "INSERT INTO subjects (studentId, subjectName, subjectCode) VALUES ('$studentId', '$subjectName', '$subjectCode')";
            mysqli_query($conn, $insertSql);
            $msg = "Subject added successfully!";
        }
    }
}

// Fetch all subjects for this student
$sql = "SELECT * FROM subjects WHERE studentId = '$studentId' ORDER BY sortOrder ASC, subjectId ASC";
$subjects = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home - StartSmart</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($studentName); ?>!</h1>
    <p><a href="logout.php">Log Out</a></p>
    <hr>

    <h3>Manage Your Subjects (US003)</h3>

    <?php if (!empty($msg)): ?>
        <p style="color:blue;"><?php echo $msg; ?></p>
    <?php endif; ?>

    <!-- Form to Add Subject -->
    <form action="home.php" method="POST">
        <label>Subject Name:</label><br>
        <input type="text" name="subject_name" placeholder="e.g. Web Programming" required><br><br>

        <label>Subject Code (optional):</label><br>
        <input type="text" name="subject_code" placeholder="e.g. SECV2223"><br><br>

        <button type="submit" name="add_subject">Add Subject</button>
    </form>

    <h3>Your Subject List:</h3>
    <ul>
        <?php if (mysqli_num_rows($subjects) === 0): ?>
            <li>No subjects yet. Add one above!</li>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($subjects)): ?>
                <li>
                    <strong><?php echo htmlspecialchars($row['subjectName']); ?></strong>
                    <?php if (!empty($row['subjectCode'])): ?>
                        <span>(<?php echo htmlspecialchars($row['subjectCode']); ?>)</span>
                    <?php endif; ?>

                    <a href="delete_subject.php?id=<?php echo $row['subjectId']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this subject?');"
                       style="color:red; margin-left:15px;">Delete</a>
                </li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
</body>
</html>
