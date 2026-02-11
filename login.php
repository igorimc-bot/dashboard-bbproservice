<?php
require_once 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    $result = login($username, $password, $csrfToken);
    if ($result['status']) {
        header("Location: index.php");
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Stats</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body class="login-page">
    <div class="login-layout-container">
        <div class="login-logo-top">
            <span class="logo-text">Logo</span>
        </div>

        <div class="login-main-card">
            <div class="login-illustration-side">
                <div class="stats-illustration">
                    <!-- CSS/SVG Illustration will be here -->
                </div>
            </div>

            <div class="login-form-side">
                <div class="login-header">
                    <h2>Welcome Back!</h2>
                    <p>Login to continue</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="new-login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" placeholder="shaddadfatma@gmail.com" required
                            autofocus>
                    </div>

                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" placeholder="**********" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" checked>
                            <span class="checkmark"></span>
                            Remember Me
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-login">Sign In</button>

                    <div class="signup-prompt">
                        New User? <a href="#">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>

        <footer class="login-footer">
            <div class="copyright">Copyright Reserved @2026</div>
            <div class="footer-links">
                <a href="#">Terms and Conditions</a> | <a href="#">Privacy Policy</a>
            </div>
        </footer>
    </div>
</body>

</html>