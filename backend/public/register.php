<?php
session_start();
include '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $name = mysqli_real_escape_string($conn, $name);
        $email = mysqli_real_escape_string($conn, $email);
        $password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO students (name, email, password)
                VALUES ('$name', '$email', '$password')";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['studentId'] = mysqli_insert_id($conn);
            $_SESSION['studentName'] = $name;
            header('Location: home.php');
            exit;
        } else {
            $error = 'Could not create account. The email may already be registered.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - StartSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="logo">🚀 StartSmart</div>
        <p class="tagline">Create your account and start smarter.</p>

        <h2>Register Account</h2>
        <br>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label class="field">Name
                <input type="text" name="name" placeholder="Your full name" required>
            </label>

            <label class="field">Email
                <input type="email" name="email" placeholder="you@university.edu" required>
            </label>

            <label class="field">Password <small>(minimum 8 characters)</small>
                <input type="password" name="password" placeholder="Create a password" required>
            </label>

            <button type="submit" class="btn-block">Create Account</button>
        </form>

        <p class="alt">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
