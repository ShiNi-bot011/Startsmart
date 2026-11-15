<?php
// backend/public/login.php
session_start();
include '../src/Config/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        // Query students table
        $sql = "SELECT * FROM students WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        $student = mysqli_fetch_assoc($result);

        // Verify password
        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['studentId']   = $student['studentId'];
            $_SESSION['studentName'] = $student['name'];

            header("Location: home.php");
            exit;
        } else {
            $error = "Incorrect email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - StartSmart</title>
</head>
<body>
    <h2>Log In</h2>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        Email:<br>
        <input type="email" name="email" required><br><br>

        Password:<br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Log In</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
