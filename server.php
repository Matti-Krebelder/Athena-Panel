<?php
require_once 'config.php';
require_once 'session.php';
require_once 'auth.php';
require_once 'server_resources.php';
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

// Check if server ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$serverId = $_GET['id'];

// Get user information
$userData = $currentUser;
$userId = $userData['id'];

// Fetch server details from Pterodactyl API
function fetchServerDetails($serverId) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => PTERODACTYL_URL . "/api/application/servers/" . $serverId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PTERODACTYL_API_KEY,
            "Content-Type: application/json",
            "Accept: Application/vnd.pterodactyl.v1+json"
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ["error" => "cURL Error: " . $err, "httpCode" => $httpCode];
    } else {
        return ["data" => json_decode($response, true), "httpCode" => $httpCode];
    }
}

// Fetch server details
$serverDetails = fetchServerDetails($serverId);
$httpCode = $serverDetails["httpCode"];

// Check if server exists and user has access
if ($httpCode !== 200) {
    header('Location: dashboard.php?error=noaccess');
    exit;
}

$serverData = $serverDetails["data"];
$serverAttributes = $serverData['attributes'];
$serverName = $serverAttributes['name'];
$serverIdentifier = $serverAttributes['identifier'];
$serverUuid = $serverAttributes['uuid'];
$serverNode = $serverAttributes['node'];
$serverDescription = isset($serverAttributes['description']) ? $serverAttributes['description'] : 'No description';

// Get permissions for the user
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PTERODACTYL_URL . '/api/client/servers/' . $serverId . '/permissions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PTERODACTYL_CLIENT_API_KEY,
    'Accept: application/json',
    'Content-Type: application/json',
]);

$permissionsResponse = curl_exec($ch);
$permissionsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$permissions = [];
$isOwner = false;

if ($permissionsHttpCode === 200) {
    $permissionsData = json_decode($permissionsResponse, true);

    // Check if user is owner
    if (isset($serverAttributes['server_owner']) && $serverAttributes['server_owner'] == true) {
        $isOwner = true;
        // If owner, grant all permissions
        $permissions = [
            'console' => true,
            'start' => true,
            'stop' => true,
            'restart' => true,
            'files' => true,
            'databases' => true,
            'schedules' => true,
            'users' => true,
            'backups' => true,
            'allocation' => true,
            'startup' => true,
            'settings' => true
        ];
    } else {
        // Parse permissions for subuser
        if (isset($permissionsData['attributes']['permissions'])) {
            $permissionsList = $permissionsData['attributes']['permissions'];

            // Map Pterodactyl permissions to our simplified permission set
            $permissions = [
                'console' => in_array('control.console', $permissionsList),
                'start' => in_array('control.start', $permissionsList),
                'stop' => in_array('control.stop', $permissionsList),
                'restart' => in_array('control.restart', $permissionsList),
                'files' => in_array('file.read', $permissionsList) || in_array('file.write', $permissionsList),
                'databases' => in_array('database.read', $permissionsList) || in_array('database.create', $permissionsList),
                'schedules' => in_array('schedule.read', $permissionsList) || in_array('schedule.create', $permissionsList),
                'users' => in_array('user.read', $permissionsList) || in_array('user.create', $permissionsList),
                'backups' => in_array('backup.read', $permissionsList) || in_array('backup.create', $permissionsList),
                'allocation' => in_array('allocation.read', $permissionsList) || in_array('allocation.create', $permissionsList),
                'startup' => in_array('startup.read', $permissionsList) || in_array('startup.update', $permissionsList),
                'settings' => in_array('settings.rename', $permissionsList) || in_array('settings.reinstall', $permissionsList)
            ];
        }
    }
}

// Nach dem Definieren des $permissions Array in server.php
// Stelle sicher, dass alle erforderlichen Schlüssel existieren
$defaultPermissions = [
    'console' => false,
    'start' => false,
    'stop' => false,
    'restart' => false,
    'files' => false,
    'databases' => false,
    'schedules' => false,
    'users' => false,
    'backups' => false,
    'allocation' => false,
    'startup' => false,
    'settings' => false
];

// Zusammenführen der Standard-Berechtigungen mit den tatsächlichen Berechtigungen
$permissions = array_merge($defaultPermissions, $permissions);

// Get server status
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PTERODACTYL_URL . '/api/application/servers/' . $serverId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PTERODACTYL_CLIENT_API_KEY,
    'Accept: application/json',
    'Content-Type: application/json',
]);

$resourcesResponse = curl_exec($ch);
$resourcesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$serverStatus = 'offline';
$resources = [
    'memory' => 0,
    'disk' => 0,
    'cpu' => 0
];

// Ressourcennutzung auslesen
if ($resourcesHttpCode === 200) {
    $resourcesData = json_decode($resourcesResponse, true);

    // Ressourcen abfragen
    if (isset($resourcesData['attributes']['resources'])) {
        $resourcesAttributes = $resourcesData['attributes']['resources'];
        $memory_bytes = isset($resourcesAttributes['memory_bytes']) ? $resourcesAttributes['memory_bytes'] : 0;
        $disk_bytes = isset($resourcesAttributes['disk_bytes']) ? $resourcesAttributes['disk_bytes'] : 0;
        $cpu_absolute = isset($resourcesAttributes['cpu_absolute']) ? $resourcesAttributes['cpu_absolute'] : 0;

        $resources = [
            'memory' => round($memory_bytes / 1024 / 1024, 2),
            'disk' => round($disk_bytes / 1024 / 1024, 2),
            'cpu' => $cpu_absolute
        ];

        // Status basierend auf RAM-Nutzung bestimmen
        if ($memory_bytes == 0) {
            $serverStatus = 'offline';
        } else {
            $serverStatus = 'online';
        }
    }

    // Status aus current_state überschreiben, falls vorhanden
    if (isset($resourcesData['attributes']['current_state'])) {
        $serverStatus = $resourcesData['attributes']['current_state'];
    }
}

// Statusklassen für die Anzeige bestimmen
$statusText = "Unknown";
$statusClass = "unknown";
$serverCardClass = "";

if (isset($serverAttributes['suspended']) && $serverAttributes['suspended']) {
    $statusText = "Suspended";
    $statusClass = "suspended";
    $serverCardClass = "status-offline";
} elseif (isset($serverAttributes['installing']) && $serverAttributes['installing']) {
    $statusText = "Installing";
    $statusClass = "installing";
    $serverCardClass = "status-installing";
} elseif (isset($serverAttributes['transferring']) && $serverAttributes['transferring']) {
    $statusText = "Transferring";
    $statusClass = "transferring";
    $serverCardClass = "status-transferring";
} elseif ($memory_bytes == 0) {
    $statusText = "Offline";
    $statusClass = "offline";
    $serverCardClass = "status-offline";
} else {
    $statusText = "Online";
    $statusClass = "online";
    $serverCardClass = "status-online";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/server.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="dashboard.php"><?= APP_NAME ?></a>
            </div>
            <div class="header-actions">
                <button id="back-to-dashboard" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </button>

                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-icon">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="dropdown-content">
                        <div class="dropdown-item">
                            <p>You have no notifications</p>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-icon">
                        <i class="fas fa-user"></i>
                    </button>
                    <div class="dropdown-content">
                        <div class="dropdown-item">
                            <p><?= htmlspecialchars($userData['username']) ?></p>
                        </div>
                        <a href="profile.php" class="dropdown-item">Profile</a>
                        <a href="?logout=1" class="dropdown-item">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <div class="server-header <?= $serverCardClass ?>">
            <div class="server-title">
                <h1><?= htmlspecialchars($serverName) ?></h1>
                <span class="server-status status-<?= $statusClass ?>">
                    <span class="notification-dot <?= $statusClass ?>"></span>
                    <span class="status-text"><?= $statusText ?></span>
                </span>
            </div>
            <div class="server-info">
                <span class="server-id"><i class="fas fa-fingerprint"></i> <?= htmlspecialchars($serverIdentifier) ?></span>
                <span class="server-node"><i class="fas fa-server"></i> <?= htmlspecialchars($serverNode) ?></span>
            </div>
        </div>

        <div class="server-tabs">
            <div class="tab-navigation">
                <?php if ($permissions['console']): ?>
                    <button class="tab-button active" data-tab="tab-console">Console</button>
                <?php endif; ?>

                <?php if ($permissions['files']): ?>
                    <button class="tab-button" data-tab="tab-files">Files</button>
                <?php endif; ?>

                <?php if ($permissions['databases']): ?>
                    <button class="tab-button" data-tab="tab-databases">Databases</button>
                <?php endif; ?>

                <?php if ($permissions['schedules']): ?>
                    <button class="tab-button" data-tab="tab-schedules">Schedules</button>
                <?php endif; ?>

                <?php if ($permissions['users']): ?>
                    <button class="tab-button" data-tab="tab-users">Users</button>
                <?php endif; ?>

                <?php if ($permissions['backups']): ?>
                    <button class="tab-button" data-tab="tab-backups">Backups</button>
                <?php endif; ?>

                <?php if ($permissions['settings']): ?>
                    <button class="tab-button" data-tab="tab-settings">Settings</button>
                <?php endif; ?>
            </div>

            <div class="tab-content">
                <?php if ($permissions['console']): ?>
                    <div id="tab-console" class="tab-pane active">
                        <div class="console-container">
                            <div class="console-output" id="console-output"></div>
                            <div class="console-input">
                                <input type="text" id="console-command" placeholder="Type a command..." <?= $serverStatus !== 'running' ? 'disabled' : '' ?>>
                                <button id="send-command" class="btn btn-primary" <?= $serverStatus !== 'running' ? 'disabled' : '' ?>>Send</button>
                            </div>
                        </div>
                        <div class="power-controls">
                            <?php if ($permissions['start']): ?>
                                <button id="start-server" class="btn btn-success" <?= $serverStatus === 'running' ? 'disabled' : '' ?>>
                                    <i class="fas fa-play"></i> Start
                                </button>
                            <?php endif; ?>

                            <?php if ($permissions['restart']): ?>
                                <button id="restart-server" class="btn btn-warning" <?= $serverStatus !== 'running' ? 'disabled' : '' ?>>
                                    <i class="fas fa-sync"></i> Restart
                                </button>
                            <?php endif; ?>

                            <?php if ($permissions['stop']): ?>
                                <button id="stop-server" class="btn btn-danger" <?= $serverStatus !== 'running' ? 'disabled' : '' ?>>
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['files']): ?>
                    <div id="tab-files" class="tab-pane">
                        <div class="file-manager">
                            <div class="file-header">
                                <div class="breadcrumbs">
                                    <span class="breadcrumb-item home" data-path="/">
                                        <i class="fas fa-home"></i>
                                    </span>
                                </div>
                                <div class="file-actions">
                                    <button id="new-file" class="btn btn-sm btn-primary">
                                        <i class="fas fa-file"></i> New File
                                    </button>
                                    <button id="new-folder" class="btn btn-sm btn-primary">
                                        <i class="fas fa-folder"></i> New Folder
                                    </button>
                                    <button id="upload-file" class="btn btn-sm btn-primary">
                                        <i class="fas fa-upload"></i> Upload
                                    </button>
                                </div>
                            </div>
                            <div class="file-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['databases']): ?>
                    <div id="tab-databases" class="tab-pane">
                        <div class="database-manager">
                            <div class="database-header">
                                <h2>Database Manager</h2>
                                <button id="new-database" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New Database
                                </button>
                            </div>
                            <div class="database-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['schedules']): ?>
                    <div id="tab-schedules" class="tab-pane">
                        <div class="schedule-manager">
                            <div class="schedule-header">
                                <h2>Schedule Manager</h2>
                                <button id="new-schedule" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New Schedule
                                </button>
                            </div>
                            <div class="schedule-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['users']): ?>
                    <div id="tab-users" class="tab-pane">
                        <div class="user-manager">
                            <div class="user-header">
                                <h2>User Manager</h2>
                                <button id="new-user" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New User
                                </button>
                            </div>
                            <div class="user-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['backups']): ?>
                    <div id="tab-backups" class="tab-pane">
                        <div class="backup-manager">
                            <div class="backup-header">
                                <h2>Backup Manager</h2>
                                <button id="new-backup" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New Backup
                                </button>
                            </div>
                            <div class="backup-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['settings']): ?>
                    <div id="tab-settings" class="tab-pane">
                        <div class="settings-manager">
                            <div class="settings-section">
                                <h3>Server Details</h3>
                                <div class="form-group">
                                    <label for="server-name">Server Name</label>
                                    <input type="text" id="server-name" class="form-control" value="<?= htmlspecialchars($serverName) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="server-description">Description</label>
                                    <textarea id="server-description" class="form-control"><?= htmlspecialchars($serverDescription) ?></textarea>
                                </div>
                                <button id="save-details" class="btn btn-primary">Save Changes</button>
                            </div>

                            <?php if ($permissions['startup']): ?>
                                <div class="settings-section">
                                    <h3>Startup Configuration</h3>
                                    <div class="startup-variables"></div>
                                    <button id="save-startup" class="btn btn-primary">Save Changes</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="server-resources" data-server-id="<?= htmlspecialchars($serverId) ?>">
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="resource-info">
                    <h3>Memory</h3>
                    <div class="progress-bar">
                        <div class="progress" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">
                        <span class="memory-usage"><?= round($resources['memory'] / 1024, 2) >= 1 ? round($resources['memory'] / 1024, 2) . 'GB' : $resources['memory'] . 'MB' ?></span>
                    </div>
                </div>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="resource-info">
                    <h3>Disk</h3>
                    <div class="progress-bar">
                        <div class="progress" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">
                        <span class="disk-usage"><?= round($resources['disk'] / 1024, 2) >= 1 ? round($resources['disk'] / 1024, 2) . 'GB' : $resources['disk'] . 'MB' ?></span>
                    </div>
                </div>
            </div>

            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="resource-info">
                    <h3>CPU</h3>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?= $resources['cpu'] ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span class="cpu-usage"><?= $resources['cpu'] ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
    </div>
</footer>

<!-- Server data for JavaScript -->
<script>
    const SERVER_DATA = {
        id: '<?= $serverId ?>',
        uuid: '<?= $serverUuid ?>',
        status: '<?= $serverStatus ?>',
        permissions: <?= json_encode($permissions) ?>,
        apiUrl: '<?= PTERODACTYL_URL ?>',
        isOwner: <?= $isOwner ? 'true' : 'false' ?>
    };
</script>
<script src="assets/js/header.js"></script>
<script src="assets/js/server.js"></script>
</body>
</html>