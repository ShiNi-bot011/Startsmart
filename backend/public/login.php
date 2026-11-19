<?php
session_start();
include '../config/database.php';

$error = '';
$info = '';
$mode = (isset($_GET['mode']) && $_GET['mode'] === 'forgot') ? 'forgot' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!$conn) {
            $error = 'Unable to connect to the database. Please try again later.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT studentId, name, password FROM students WHERE email = ? LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $student = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($stmt);

                if ($student && password_verify($password, $student['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['studentId'] = $student['studentId'];
                    $_SESSION['studentName'] = $student['name'];
                    header('Location: home.php');
                    exit;
                } else {
                    $error = 'Incorrect email or password. Please check your credentials and try again.';
                }
            } else {
                $error = 'Login could not be processed right now. Please try again later.';
            }
        }
    } elseif ($action === 'forgot') {
        $mode = 'forgot';
        $resetEmail = strtolower(trim($_POST['reset_email'] ?? ''));

        if ($resetEmail === '' || !filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Marked as KIV (Keep in View) for Harith:
            // Hook up reset token generation and email sending in a future sprint.
            $info = 'Forgot Password (KIV for Harith): Request noted for ' . $resetEmail . '. Password reset flow is kept in view for Harith.';
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

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($info !== ''): ?>
            <div class="alert alert-info" role="status"><?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mode === 'forgot'): ?>
            <h2>Forgot Password</h2>
            <p class="tagline">Marked as KIV (Keep in View) for Harith.</p>
            <br>
            <form action="login.php?mode=forgot" method="POST">
                <input type="hidden" name="action" value="forgot">
                <label class="field">Email
                    <input type="email" name="reset_email" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['reset_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <button type="submit" class="btn-block">Submit Reset Request (KIV)</button>
            </form>

            <p class="alt"><a href="login.php">Back to Login</a></p>
        <?php else: ?>
            <h2>Welcome back</h2>
            <br>
            <form action="login.php" method="POST">
                <input type="hidden" name="action" value="login">
                <label class="field">Email
                    <input type="email" name="email" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="field">Password
                    <input type="password" name="password" placeholder="Your password" required>
                </label>

                <p class="alt"><a href="login.php?mode=forgot">Forgot Password? (KIV - Harith)</a></p>
                <br>

                <button type="submit" class="btn-block">Log In</button>
            </form>

            <p class="alt">Don't have an account? <a href="register.php">Register here</a></p>
        <?php endif; ?>
    </div>
</body>
</html>