<?php
// backend/public/register.php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'];
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    // 1. Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // 2. Check if email already exists in students table
        $sql = "SELECT studentId FROM students WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered!";
        } else {
            // 3. Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // 4. Insert new student into students table
            $insertSql = "INSERT INTO students (name, email, password) VALUES ('$name', '$email', '$hashed')";
            mysqli_query($conn, $insertSql);

            // 5. Auto login via session
            $_SESSION['studentId']   = mysqli_insert_id($conn);
            $_SESSION['studentName'] = $name;

            header("Location: home.php");
            exit;
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
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        Name:<br>
        <input type="text" name="name" required><br><br>

        Email:<br>
        <input type="email" name="email" required><br><br>

        Password (min 8 chars):<br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
