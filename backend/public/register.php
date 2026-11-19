<?php
session_start();
include '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!$conn) {
        $error = 'Unable to connect to the database. Please try again later.';
    } else {
        $checkStmt = mysqli_prepare($conn, 'SELECT studentId FROM students WHERE email = ? LIMIT 1');
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 's', $email);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $existing = $checkResult ? mysqli_fetch_assoc($checkResult) : null;
            mysqli_stmt_close($checkStmt);

            if ($existing) {
                $error = 'That email is already registered. Please log in or use another email address.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = mysqli_prepare($conn, 'INSERT INTO students (name, email, password) VALUES (?, ?, ?)');

                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, 'sss', $name, $email, $passwordHash);
                    try {
                        $inserted = mysqli_stmt_execute($insertStmt);
                        if ($inserted) {
                            session_regenerate_id(true);
                            $_SESSION['studentId'] = mysqli_insert_id($conn);
                            $_SESSION['studentName'] = $name;
                            mysqli_stmt_close($insertStmt);
                            header('Location: home.php');
                            exit;
                        }
                        $error = 'Could not create account. Please try again.';
                    } catch (mysqli_sql_exception $e) {
                        if ((int) $e->getCode() === 1062) {
                            $error = 'That email is already registered. Please log in or use another email address.';
                        } else {
                            $error = 'Could not create account right now. Please try again later.';
                        }
                    }
                    mysqli_stmt_close($insertStmt);
                } else {
                    $error = 'Registration could not be processed right now. Please try again later.';
                }
            }
        } else {
            $error = 'Registration could not be processed right now. Please try again later.';
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
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label class="field">Name
                <input type="text" name="name" placeholder="Your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="field">Email
                <input type="email" name="email" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
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