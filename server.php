<?php
require_once 'config.php'; // Include config file
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | Server Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/server.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">

                    <span><?= APP_NAME ?></span>
                </div>
                <button class="toggle-sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-section">
                        <span class="nav-section-title">Dashboard</span>
                        <ul>
                            <li class="active">
                                <a href="#"><i class="fa-solid fa-house"></i> Übersicht</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-server"></i> Meine Server</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-plus-circle"></i> Server erstellen</a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-section">
                        <span class="nav-section-title">Server Management</span>
                        <ul>
                            <li>
                                <a href="#"><i class="fa-solid fa-terminal"></i> Konsole</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-folder"></i> Dateien</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-database"></i> Datenbanken</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-network-wired"></i> Netzwerk</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-users"></i> Benutzer</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-calendar"></i> Scheduler</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-shield-alt"></i> Backups</a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-section">
                        <span class="nav-section-title">Einstellungen</span>
                        <ul>
                            <li>
                                <a href="#"><i class="fa-solid fa-cog"></i> Server Einstellungen</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-user-cog"></i> Mein Profil</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa-solid fa-key"></i> API Schlüssel</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="#" class="btn-help">
                    <i class="fa-solid fa-question-circle"></i> Hilfe
                </a>
                <a href="#" class="btn-logout">
                    <i class="fa-solid fa-sign-out-alt"></i> Abmelden
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="server-selector">
                    <div class="server-info">
                        <i class="fa-solid fa-cube text-primary"></i>
                        <div>
                            <h2>Minecraft Server #1</h2>
                            <span class="server-status online">
                                <i class="fa-solid fa-circle"></i> Online
                            </span>
                        </div>
                    </div>
                    <button class="btn-dropdown">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="top-bar-actions">
                    <button class="action-btn">
                        <i class="fa-solid fa-play"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fa-solid fa-stop"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fa-solid fa-redo"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                    
                    <div class="notification-bell">
                        <i class="fa-solid fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-container">
                <div class="breadcrumbs">
                    <ul>
                        <li><a href="#">Dashboard</a></li>
                        <li><a href="#">Server</a></li>
                        <li>Konsole</li>
                    </ul>
                </div>
                
                <div class="dashboard-content">
                    <div class="stats-container">
                        <div class="stats-card glass-card">
                            <div class="stats-icon">
                                <i class="fa-solid fa-microchip"></i>
                            </div>
                            <div class="stats-info">
                                <h3>CPU</h3>
                                <div class="stats-value">
                                    <span>23%</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 23%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-card glass-card">
                            <div class="stats-icon">
                                <i class="fa-solid fa-memory"></i>
                            </div>
                            <div class="stats-info">
                                <h3>RAM</h3>
                                <div class="stats-value">
                                    <span>1.2 GB / 4 GB</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 30%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-card glass-card">
                            <div class="stats-icon">
                                <i class="fa-solid fa-hdd"></i>
                            </div>
                            <div class="stats-info">
                                <h3>Speicher</h3>
                                <div class="stats-value">
                                    <span>8.2 GB / 25 GB</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 32%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-card glass-card">
                            <div class="stats-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stats-info">
                                <h3>Uptime</h3>
                                <div class="stats-value">
                                    <span>3d 14h 22m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="console-section glass-card">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-terminal"></i> Server Konsole</h2>
                            <div class="console-actions">
                                <button class="btn-icon" title="Konsole leeren">
                                    <i class="fa-solid fa-eraser"></i>
                                </button>
                                <button class="btn-icon" title="Konsole erweitern">
                                    <i class="fa-solid fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="console-output">
                                <div class="console-line"><span class="time">[12:04:32]</span> <span class="info">Server wird gestartet...</span></div>
                                <div class="console-line"><span class="time">[12:04:33]</span> <span class="info">Java Version: 17.0.7</span></div>
                                <div class="console-line"><span class="time">[12:04:33]</span> <span class="info">Loading libraries, please wait...</span></div>
                                <div class="console-line"><span class="time">[12:04:35]</span> <span class="warning">World generation settings have been changed since the last save.</span></div>
                                <div class="console-line"><span class="time">[12:04:42]</span> <span class="success">Done (8.723s)! For help, type "help"</span></div>
                                <div class="console-line"><span class="time">[12:05:14]</span> <span class="info">User MaxGamer connected from 192.168.1.101</span></div>
                                <div class="console-line"><span class="time">[12:06:32]</span> <span class="success">Saved the world</span></div>
                                <div class="console-line"><span class="time">[12:08:01]</span> <span class="error">Can't keep up! Is the server overloaded?</span></div>
                                <div class="console-line"><span class="time">[12:08:05]</span> <span class="info">Performance has stabilized.</span></div>
                            </div>
                            <div class="console-input">
                                <input type="text" class="form-control" placeholder="Befehl eingeben...">
                                <button class="btn btn-primary">Senden</button>
                            </div>
                        </div>
                    </div>
                    
                    
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>