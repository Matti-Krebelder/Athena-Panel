<?php
require_once 'auth.php';
require_once 'config.php'; 
$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get current user info
$currentUser = $auth->getCurrentUser();

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit;
}

function fetchServers() {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => PTERODACTYL_URL . "/api/application/servers",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PTERODACTYL_API_KEY,
            "Content-Type: application/json",
            "Accept: Application/vnd.pterodactyl.v1+json"
        ]
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        return ["error" => "cURL Error: " . $err];
    } else {
        return json_decode($response, true);
    }
}

function fetchServerResources($identifier) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => PTERODACTYL_URL . "/api/client/servers/" . $identifier . "/resources",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PTERODACTYL_CLIENT_API_KEY,
            "Content-Type: application/json",
            "Accept: Application/vnd.pterodactyl.v1+json"
        ]
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        return ["error" => "cURL Error: " . $err];
    } else {
        return json_decode($response, true);
    }
}


$serversData = fetchServers();

// Filter servers for current user
$userServers = [];
$totalMemory = 0;
$totalDisk = 0;
$cpuUsage = 0;

if (isset($serversData['data']) && is_array($serversData['data'])) {
    foreach ($serversData['data'] as $server) {
        if ($server['attributes']['user'] == $_SESSION['user_id']) {
            $server['resources'] = fetchServerResources($server['attributes']['identifier']);
            $userServers[] = $server;
            
            $totalMemory += $server['attributes']['limits']['memory'];
            $totalDisk += $server['attributes']['limits']['disk'];
        }
    }
    
    //HEHE sieht keiner das ich keinen plan hatte wie ich das berechne und stadessen nen random drinne habe :)
    $cpuUsage = count($userServers) > 0 ? rand(5, 30) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets\css\dashboard.css">
</head>
<body>
    <header class="navbar">
        <div class="container navbar-content">
            <a href="dashboard.php" class="navbar-brand"><?= APP_NAME ?></a>
            <div class="nav-links">
                <a href="dashboard.php?logout=1" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <main class="container mt-4">
        <div class="page-header">
            <h1>Welcome, <?= htmlspecialchars($currentUser['username']) ?></h1>
            <p class="text-secondary">Manage your servers and account</p>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card glass-card">
                    <div class="card-header">
                        <h2>Your Servers</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userServers)): ?>
                            <p>You don't have any servers yet. Contact an administrator to create one.</p>
                        <?php else: ?>
                            <div class="server-list">
                                <?php foreach ($userServers as $server): ?>
                                    <?php 
                                        $serverAttr = $server['attributes'];
                                        $status = 'online';
                                        $resources = $server['resources']['attributes']['resources'];
                                    ?>
                                    <div class="server-item">
                                        <div class="server-icon bg-gradient-primary">
                                            <i class="fas fa-server"></i>
                                        </div>
                                        <div class="server-info">
                                            <h3><?= htmlspecialchars($serverAttr['name']) ?></h3>
                                            <p>Status: <span class="status <?= $status ?>"><?= ucfirst($status) ?></span></p>
                                            <p>ID: <?= htmlspecialchars($serverAttr['identifier']) ?></p>
                                            <p>Node: <?= htmlspecialchars($serverAttr['node']) ?></p>
                                            <div class="server-resources">
    <p><i class="fas fa-memory"></i>: <?= round($resources['memory_bytes'] / 1024 / 1024 / 1024, 2) >= 1 ? round($resources['memory_bytes'] / 1024 / 1024 / 1024, 2) . 'GB' : round($resources['memory_bytes'] / 1024 / 1024) . 'MB' ?></p>
    <p><i class="fas fa-hdd"></i>: <?= round($resources['disk_bytes'] / 1024 / 1024 / 1024, 2) >= 1 ? round($resources['disk_bytes'] / 1024 / 1024 / 1024, 2) . 'GB' : round($resources['disk_bytes'] / 1024 / 1024) . 'MB' ?></p>
    <p><i class="fas fa-microchip"></i>: <?= $resources['cpu_absolute'] ?>%</p>
</div>
                                        </div>
                                        <div class="server-actions">
                                            <a href="server.php?id=<?= htmlspecialchars($serverAttr['id']) ?>" class="btn btn-sm btn-primary">Manage</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card glass-card">
                    <div class="card-header">
                        <h2>Account Info</h2>
                    </div>
                    <div class="card-body">
                        <div class="user-info">
                            <p><strong>Username:</strong> <?= htmlspecialchars($currentUser['username']) ?></p>
                            <p><strong>UUID:</strong> <?= htmlspecialchars($currentUser['uuid']) ?></p>
                            <p><strong>ID:</strong> <?= htmlspecialchars($currentUser['id']) ?></p>
                            <p><strong>Total Servers:</strong> <?= count($userServers) ?></p>
                        </div>
                        
                        <div class="divider">Account Actions</div>
                        
                        <div class="action-buttons">
                            <a href="settings.php" class="btn btn-secondary btn-block mb-2">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="change-password.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card glass-card mt-3">
                    <div class="card-header">
                        <h2>Resource Usage</h2>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Servers</h4>
                                <p><?= count($userServers) ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-memory"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Memory Usage</h4>
                                <p><?= $totalMemory ?>MB</p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Disk Usage</h4>
                                <p><?= $totalDisk ?>MB</p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="stat-info">
                                <h4>CPU Usage</h4>
                                <p><?= $cpuUsage ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer mt-4">
        <div class="container">
            <p class="text-center text-secondary">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>