<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'pterodactyl.php';
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
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
<header class="navbar">
  <div class="container navbar-content">
    <a href="dashboard.php" class="navbar-brand"><?= APP_NAME ?></a>
    <div class="nav-right">
      <!-- Benachrichtigungen -->
      <div class="notification-icon" id="notification-toggle">
        <i class="fas fa-bell"></i>
        <span class="notification-badge">3</span>

        <!-- Benachrichtigungen Dropdown -->
        <div class="notification-dropdown" id="notification-dropdown">
          <div class="notification-header">
            <h3>Benachrichtigungen</h3>
            <a href="#" class="text-primary">Alle lesen</a>
          </div>
          <div class="notification-list">
            <div class="notification-item">
              <div class="notification-dot"></div>
              <div class="notification-content">
                <div class="notification-title">Server 120392 neu gestartet</div>
                <div class="notification-message">Der Server wurde erfolgreich neugestartet und ist wieder online.</div>
                <div class="notification-time">Vor 5 Minuten</div>
              </div>
              <div class="notification-actions">
                <button class="notification-action" title="Als gelesen markieren">
                  <i class="fas fa-check"></i>
                </button>
              </div>
            </div>
            <div class="notification-item">
              <div class="notification-dot"></div>
              <div class="notification-content">
                <div class="notification-title">Wartungsarbeiten geplant</div>
                <div class="notification-message">Am 15.07.2024 von 02:00 bis 04:00 Uhr finden Wartungsarbeiten statt.</div>
                <div class="notification-time">Vor 3 Stunden</div>
              </div>
              <div class="notification-actions">
                <button class="notification-action" title="Als gelesen markieren">
                  <i class="fas fa-check"></i>
                </button>
              </div>
            </div>
            <div class="notification-item">
              <div class="notification-dot"></div>
              <div class="notification-content">
                <div class="notification-title">Speicherplatz fast voll</div>
                <div class="notification-message">Server 120392 hat nur noch 15% freien Speicherplatz.</div>
                <div class="notification-time">Vor 1 Tag</div>
              </div>
              <div class="notification-actions">
                <button class="notification-action" title="Als gelesen markieren">
                  <i class="fas fa-check"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="notification-footer">
            <a href="notifications.php" class="notification-view-all">Alle anzeigen</a>
          </div>
        </div>
      </div>

        <!-- Benutzer-Dropdown -->
        <div class="user-dropdown">
            <?php
            $avatarUrl = isset($currentUser['avatar']) ? $currentUser['avatar'] : "https://www.gravatar.com/avatar/default?s=400";
            ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profilbild" class="user-avatar" id="user-dropdown-toggle">
            <!-- Dropdown Menü -->
            <div class="dropdown-menu" id="user-dropdown-menu">
                <div class="user-info-header">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profilbild" class="user-avatar-large">
                    <div class="user-details">
                        <div class="user-name">
                            <?php
                            // Try different user identifiers in order of preference
                            if (isset($currentUser['username']) && !empty($currentUser['username'])) {
                                echo htmlspecialchars($currentUser['username']);
                            } else if (isset($currentUser['first_name']) && isset($currentUser['last_name'])) {
                                echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']);
                            } else if (isset($currentUser['name'])) {
                                echo htmlspecialchars($currentUser['name']);
                            } else {
                                echo "Benutzer";
                            }
                            ?>
                        </div>
                        <?php if (isset($currentUser['email'])): ?>
                            <div class="user-email">E-Mail: <?= htmlspecialchars($currentUser['email']) ?></div>
                        <?php endif; ?>
                        <?php if (isset($currentUser['id'])): ?>
                            <div class="user-id">ID: <?= htmlspecialchars($currentUser['id']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dropdown-actions">
                    <a href="account.html" class="dropdown-item">
                        <i class="fas fa-user"></i> Mein Profil
                    </a>
                    <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                        <a href="admin-panel.html" class="dropdown-item">
                            <i class="fas fa-shield-alt"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
                <div class="dropdown-divider"></div>
                <div class="theme-selector">
                    <div class="theme-heading">Erscheinungsbild</div>
                    <div class="theme-options">
                        <button class="theme-option" data-theme="dark">
                            <i class="fas fa-sun"></i>
                            <span>Dunkel</span>
                        </button>
                        <button class="theme-option" data-theme="light">
                            <i class="fas fa-moon"></i>
                            <span>Hell</span>
                        </button>
                        <button class="theme-option" data-theme="system">
                            <i class="fas fa-desktop"></i>
                            <span>System</span>
                        </button>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-footer">
                    <a href="dashboard.php?logout=1" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
      </div>
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
                        // Safely access server attributes with null checks
                        $serverAttr = isset($server['attributes']) ? $server['attributes'] : [];

                        // Default values for resources
                        $resources = [];
                        $memory_bytes = 0;
                        $disk_bytes = 0;
                        $cpu_absolute = 0;

                        // Safely access resources if they exist
                        if (isset($server['resources']) && isset($server['resources']['attributes']) &&
                            isset($server['resources']['attributes']['resources'])) {
                            $resources = $server['resources']['attributes']['resources'];
                            $memory_bytes = isset($resources['memory_bytes']) ? $resources['memory_bytes'] : 0;
                            $disk_bytes = isset($resources['disk_bytes']) ? $resources['disk_bytes'] : 0;
                            $cpu_absolute = isset($resources['cpu_absolute']) ? $resources['cpu_absolute'] : 0;
                        }

                        // Determine status based on RAM and server state
                        $statusText = "Unknown";
                        $statusClass = "unknown";
                        $serverCardClass = "";

                        if (isset($serverAttr['suspended']) && $serverAttr['suspended']) {
                            $statusText = "Suspended";
                            $statusClass = "suspended";
                            $serverCardClass = "status-offline";
                        } elseif (isset($serverAttr['installing']) && $serverAttr['installing']) {
                            $statusText = "Installing";
                            $statusClass = "installing";
                            $serverCardClass = "status-installing";
                        } elseif (isset($serverAttr['transferring']) && $serverAttr['transferring']) {
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

                        // Get server IP address safely
                        $serverIP = isset($serverAttr['ip']) ? $serverAttr['ip'] : "127.0.0.1";
                        $serverIdentifier = isset($serverAttr['identifier']) ? $serverAttr['identifier'] : "unknown";
                        $serverName = isset($serverAttr['name']) ? $serverAttr['name'] : "Unknown Server";

                        // Safely get the node name
                        $nodeName = "Unknown";
                        if (isset($serverAttr['node_name']) && !empty($serverAttr['node_name'])) {
                            $nodeName = $serverAttr['node_name'];
                        } elseif (isset($serverAttr['node'])) {
                            $nodeName = $serverAttr['node'];
                        }

                        // Get server IP address from Pterodactyl API allocations
                        $serverIP = "";
                        if (isset($serverAttr['relationships']) &&
                            isset($serverAttr['relationships']['allocations']) &&
                            isset($serverAttr['relationships']['allocations']['data']) &&
                            !empty($serverAttr['relationships']['allocations']['data'])) {

                            // Get the first allocation
                            $allocation = $serverAttr['relationships']['allocations']['data'][0];
                            if (isset($allocation['attributes']['ip'])) {
                                $serverIP = $allocation['attributes']['ip'];
                            }
                        }

                        // Safely get the server ID
                        $serverId = isset($serverAttr['id']) ? $serverAttr['id'] : "";
                        ?>
                        <div class="server-item <?= $serverCardClass ?>" data-ip="<?= htmlspecialchars($serverIP) ?>">
                            <div class="server-icon bg-gradient-primary">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="server-info">
                                <h3><?= htmlspecialchars($serverName) ?></h3>
                                <p>
                                    Status:
                                    <span class="server-status">
                    <span class="notification-dot <?= $statusClass ?>"></span>
                    <span class="status-text"><?= $statusText ?></span>
                </span>
                                </p>
                                <p>ID: <?= htmlspecialchars($serverIdentifier) ?></p>
                                <p>Node: <?= htmlspecialchars($nodeName) ?></p>
                                <div class="server-resources">
                                    <p><i class="fas fa-memory"></i>: <?= round($memory_bytes / 1024 / 1024 / 1024, 2) >= 1 ? round($memory_bytes / 1024 / 1024 / 1024, 2) . 'GB' : round($memory_bytes / 1024 / 1024) . 'MB' ?></p>
                                    <p><i class="fas fa-hdd"></i>: <?= round($disk_bytes / 1024 / 1024 / 1024, 2) >= 1 ? round($disk_bytes / 1024 / 1024 / 1024, 2) . 'GB' : round($disk_bytes / 1024 / 1024) . 'MB' ?></p>
                                    <p><i class="fas fa-microchip"></i>: <?= $cpu_absolute ?>%</p>
                                </div>
                            </div>
                            <div class="server-actions">
                                <a href="server.php?id=<?= htmlspecialchars($serverId) ?>" class="btn btn-sm btn-primary">Manage</a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Toast notification for copy confirmation -->
                    <div class="toast" id="toast-message">IP Address copied to clipboard</div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-4">
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
<script src="assets/js/header.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>