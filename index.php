<?php
require_once 'auth.php';
require_once 'pterodactyl.php';
$auth = new Auth();

// Initialize message variables
$message = '';
$messageType = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password';
        $messageType = 'danger';
    } else {
        $result = $auth->login($username, $password);

        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);

    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        $message = 'All fields are required';
        $messageType = 'danger';
    } else if ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'danger';
    } else {
        $result = $auth->register($username, $password, $email, $firstName, $lastName);

        if ($result['success']) {
            $message = $result['message'] . '. Please log in.';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="login-card glass-card fade-in">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>

        <div class="card-body">
            <div class="login-header">
                <h1 class="login-title"><?= APP_NAME ?></h1>
                <p class="login-subtitle">Control your servers with style</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="toggle-container">
                <div class="toggle-slider"></div>
                <div class="toggle-btn active" id="login-btn">Login</div>
                <div class="toggle-btn" id="register-btn">Register</div>
            </div>

            <!-- Login Form -->
            <form id="login-form" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <div class="form-input">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control input-with-icon" placeholder="Username" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control input-with-icon" placeholder="Password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-block">
                    Log In <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>

            <!-- Register Form -->
            <form id="register-form" action="<?= $_SERVER['PHP_SELF'] ?>" method="post" style="display: none;">
                <div class="form-input">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control input-with-icon" placeholder="Username" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control input-with-icon" placeholder="Email" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="first_name" class="form-control input-with-icon" placeholder="First Name" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="last_name" class="form-control input-with-icon" placeholder="Last Name" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control input-with-icon" placeholder="Password" required>
                </div>

                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" class="form-control input-with-icon" placeholder="Confirm Password" required>
                </div>

                <button type="submit" name="register" class="btn btn-primary btn-block">
                    Create Account <i class="fas fa-user-plus ml-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/login.js"></script>
</body>