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
<html>
<head>
    <title>Register - StartSmart</title>
</head>
<body>
    <h2>Register Account</h2>
    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        Name:<br>
        <input type="text" name="name" required><br><br>

        Email:<br>
        <input type="email" name="email" required><br><br>

        Password (minimum 8 characters):<br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
