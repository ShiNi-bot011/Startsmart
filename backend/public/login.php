<?php
session_start();
include '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $email = mysqli_real_escape_string($conn, $email);
        $sql = "SELECT * FROM students WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        $student = mysqli_fetch_assoc($result);

        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['studentId'] = $student['studentId'];
            $_SESSION['studentName'] = $student['name'];
            header('Location: home.php');
            exit;
        } else {
            $error = 'Incorrect email or password!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - StartSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="logo">🚀 StartSmart</div>
        <p class="tagline">Get unstuck and start your tasks with confidence.</p>

        <h2>Welcome back</h2>
        <br>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label class="field">Email
                <input type="email" name="email" placeholder="you@university.edu" required>
            </label>

            <label class="field">Password
                <input type="password" name="password" placeholder="Your password" required>
            </label>

            <button type="submit" class="btn-block">Log In</button>
        </form>

        <p class="alt">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
