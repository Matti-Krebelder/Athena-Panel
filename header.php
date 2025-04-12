<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<header class="top-header">
    <div class="logo">
        <a href="dashboard.php">
            <img src="assets/images/logo.png" alt="Athena">
            <span>Athena Panel</span>
        </a>
    </div>
    <nav class="top-nav">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="servers.php"><i class="fas fa-server"></i> Servers</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </nav>
    <div class="user-menu">
        <div class="user-info">
            <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
            <img src="assets/images/avatar.png" alt="User Avatar">
        </div>
        <div class="dropdown-menu">
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</header>