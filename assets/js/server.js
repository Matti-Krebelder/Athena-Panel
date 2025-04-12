// assets/js/server.js
document.addEventListener('DOMContentLoaded', function() {
    const socket = {
        instance: null,
        token: null,
        connected: false
    };

    // Initialize tabs
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');

            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Add active class to selected tab
            button.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Update UI based on server status
    function updateServerStatus(status) {
        const startBtn = document.getElementById('start-server');
        const restartBtn = document.getElementById('restart-server');
        const stopBtn = document.getElementById('stop-server');
        const consoleInput = document.getElementById('console-command');
        const sendBtn = document.getElementById('send-command');
        const serverStatusEl = document.querySelector('.server-status');

        // Update status indicator
        serverStatusEl.className = `server-status status-${status}`;
        serverStatusEl.innerHTML = `<i class="fas fa-circle"></i> ${status.charAt(0).toUpperCase() + status.slice(1)}`;

        // Update buttons state
        if (startBtn) {
            startBtn.disabled = (status === 'running');
        }

        if (restartBtn) {
            restartBtn.disabled = (status !== 'running');
        }

        if (stopBtn) {
            stopBtn.disabled = (status !== 'running');
        }

        if (consoleInput) {
            consoleInput.disabled = (status !== 'running');
        }

        if (sendBtn) {
            sendBtn.disabled = (status !== 'running');
        }

        SERVER_DATA.status = status;
    }

    // Initialize WebSocket for console
    function initConsoleSocket() {
        if (!SERVER_DATA.permissions.console) return;

        // First, get auth token for WebSocket
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/websocket`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.data && data.data.token) {
                    socket.token = data.data.token;
                    connectWebSocket(data.data.socket, data.data.token);
                }
            })
            .catch(error => {
                console.error('Error fetching WebSocket details:', error);
                addConsoleMessage('Error connecting to server console. Please refresh the page.', 'error');
            });
    }

    // Connect to WebSocket
    function connectWebSocket(socketUrl, token) {
        // Close existing socket if any
        if (socket.instance && socket.connected) {
            socket.instance.close();
        }

        // Create new WebSocket connection
        socket.instance = new WebSocket(`${socketUrl}?token=${token}`);

        socket.instance.onopen = () => {
            socket.connected = true;
            addConsoleMessage('Connected to server console.', 'system');
        };

        socket.instance.onclose = () => {
            socket.connected = false;
            addConsoleMessage('Disconnected from server console.', 'system');

            // Try to reconnect after a delay
            setTimeout(() => {
                if (!socket.connected) {
                    initConsoleSocket();
                }
            }, 5000);
        };

        socket.instance.onerror = (error) => {
            console.error('WebSocket error:', error);
            socket.connected = false;
        };

        socket.instance.onmessage = (event) => {
            const data = JSON.parse(event.data);

            // Handle different message types
            switch (data.event) {
                case 'auth success':
                    // Send request for stats
                    socket.instance.send(JSON.stringify({
                        event: 'send stats',
                        args: []
                    }));
                    break;

                case 'console output':
                    addConsoleMessage(data.args[0], 'console');
                    break;

                case 'status':
                    updateServerStatus(data.args[0]);
                    break;

                case 'stats':
                    updateResourceUsage(data.args[0]);
                    break;
            }
        };
    }

    // Add message to console output
    function addConsoleMessage(message, type = 'console') {
        const consoleOutput = document.getElementById('console-output');
        if (!consoleOutput) return;

        const line = document.createElement('div');
        line.className = `console-line ${type}`;
        line.textContent = message;

        consoleOutput.appendChild(line);
        consoleOutput.scrollTop = consoleOutput.scrollHeight;

        // Limit console lines
        while (consoleOutput.childNodes.length > 500) {
            consoleOutput.removeChild(consoleOutput.firstChild);
        }
    }

    // Update resource usage display
    function updateResourceUsage(stats) {
        if (!stats) return;

        // Memory usage
        const memoryUsage = Math.round(stats.memory_bytes / 1024 / 1024);
        const memoryLimit = SERVER_DATA.limits?.memory || 1;
        const memoryPercent = Math.min(memoryUsage / memoryLimit * 100, 100);

        const memoryProgressBar = document.querySelector('.resource-card:nth-child(1) .progress');
        const memoryText = document.querySelector('.resource-card:nth-child(1) .progress-text');

        if (memoryProgressBar) {
            memoryProgressBar.style.width = `${memoryPercent}%`;
        }

        if (memoryText) {
            memoryText.textContent = `${memoryUsage} MB / ${memoryLimit} MB`;
        }

        // Disk usage
        const diskUsage = Math.round(stats.disk_bytes / 1024 / 1024);
        const diskLimit = SERVER_DATA.limits?.disk || 1;
        const diskPercent = Math.min(diskUsage / diskLimit * 100, 100);

        const diskProgressBar = document.querySelector('.resource-card:nth-child(2) .progress');
        const diskText = document.querySelector('.resource-card:nth-child(2) .progress-text');

        if (diskProgressBar) {
            diskProgressBar.style.width = `${diskPercent}%`;
        }

        if (diskText) {
            diskText.textContent = `${diskUsage} MB / ${diskLimit} MB`;
        }

        // CPU usage
        const cpuUsage = Math.min(stats.cpu_absolute, 100);

        const cpuProgressBar = document.querySelector('.resource-card:nth-child(3) .progress');
        const cpuText = document.querySelector('.resource-card:nth-child(3) .progress-text');

        if (cpuProgressBar) {
            cpuProgressBar.style.width = `${cpuUsage}%`;
        }

        if (cpuText) {
            cpuText.textContent = `${cpuUsage}%`;
        }
    }

    // Power controls
    function setupPowerControls() {
        const startBtn = document.getElementById('start-server');
        const restartBtn = document.getElementById('restart-server');
        const stopBtn = document.getElementById('stop-server');

        if (startBtn) {
            startBtn.addEventListener('click', () => sendPowerAction('start'));
        }

        if (restartBtn) {
            restartBtn.addEventListener('click', () => sendPowerAction('restart'));
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', () => sendPowerAction('stop'));
        }
    }

    // Send power action (start/stop/restart)
    function sendPowerAction(action) {
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/power`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            },
            body: JSON.stringify({ signal: action })
        })
            .then(response => {
                if (response.ok) {
                    addConsoleMessage(`Power signal '${action}' sent successfully.`, 'system');
                } else {
                    addConsoleMessage(`Failed to send power signal '${action}'.`, 'error');
                }
            })
            .catch(error => {
                console.error('Error sending power action:', error);
                addConsoleMessage(`Error sending power signal '${action}'.`, 'error');
            });
    }

    // Console command setup
    function setupConsoleInput() {
        const commandInput = document.getElementById('console-command');
        const sendBtn = document.getElementById('send-command');

        if (!commandInput || !sendBtn) return;

        sendBtn.addEventListener('click', () => {
            sendConsoleCommand();
        });

        commandInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendConsoleCommand();
            }
        });
    }

    function sendConsoleCommand() {
        const commandInput = document.getElementById('console-command');
        if (!commandInput || !socket.connected) return;

        const command = commandInput.value.trim();
        if (!command) return;

        // Send command to server
        socket.instance.send(JSON.stringify({
            event: 'send command',
            args: [command]
        }));

        // Clear input after sending
        commandInput.value = '';
    }

    // File manager functionality
    function initFileManager() {
        if (!SERVER_DATA.permissions.files) return;

        // Load file listing
        loadFiles('/');

        // Setup new file/folder creation
        const newFileBtn = document.getElementById('new-file');
        const newFolderBtn = document.getElementById('new-folder');

        if (newFileBtn) {
            newFileBtn.addEventListener('click', () => {
                // Implement file creation modal/logic
                console.log('New file creation clicked');
            });
        }

        if (newFolderBtn) {
            newFolderBtn.addEventListener('click', () => {
                // Implement folder creation modal/logic
                console.log('New folder creation clicked');
            });
        }
    }

    function loadFiles(path) {
        const fileList = document.querySelector('.file-list');
        if (!fileList) return;

        // Show loading
        fileList.innerHTML = '<div class="file-loading"><i class="fas fa-spinner fa-spin"></i> Loading files...</div>';

        // Update breadcrumbs
        updateBreadcrumbs(path);

        // Fetch files from API
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/files/list?directory=${encodeURIComponent(path)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                displayFiles(data.data, path);
            })
            .catch(error => {
                console.error('Error loading files:', error);
                fileList.innerHTML = '<div class="error-message">Error loading files. Please try again.</div>';
            });
    }

    function updateBreadcrumbs(path) {
        const breadcrumbs = document.querySelector('.breadcrumbs');
        if (!breadcrumbs) return;

        // Clear current breadcrumbs except home
        while (breadcrumbs.children.length > 1) {
            breadcrumbs.removeChild(breadcrumbs.lastChild);
        }

        // Add path segments
        const segments = path.split('/').filter(s => s);
        let currentPath = '/';

        for (const segment of segments) {
            currentPath += segment + '/';

            const crumb = document.createElement('span');
            crumb.className = 'breadcrumb-item';
            crumb.textContent = segment;
            crumb.setAttribute('data-path', currentPath);
            crumb.addEventListener('click', (e) => {
                const clickedPath = e.target.getAttribute('data-path');
                loadFiles(clickedPath);
            });

            breadcrumbs.appendChild(crumb);
        }
    }

    function displayFiles(files, currentPath) {
        const fileList = document.querySelector('.file-list');
        if (!fileList) return;

        // Clear previous content
        fileList.innerHTML = '';

        // Display parent directory option if not at root
        if (currentPath !== '/') {
            const parentPath = currentPath.split('/').slice(0, -2).join('/') + '/';

            const parentDir = document.createElement('div');
            parentDir.className = 'file-item directory';
            parentDir.innerHTML = '<i class="fas fa-arrow-up"></i> ..';
            parentDir.addEventListener('click', () => {
                loadFiles(parentPath);
            });

            fileList.appendChild(parentDir);
        }

        // Display files and directories
        if (files.length === 0) {
            fileList.innerHTML += '<div class="empty-directory">This directory is empty</div>';
            return;
        }

        // Sort: directories first, then files
        const sortedFiles = files.sort((a, b) => {
            if (a.is_file !== b.is_file) {
                return a.is_file ? 1 : -1;
            }
            return a.name.localeCompare(b.name);
        });

        for (const file of sortedFiles) {
            const fileItem = document.createElement('div');
            fileItem.className = `file-item ${file.is_file ? 'file' : 'directory'}`;

            if (file.is_file) {
                fileItem.innerHTML = `<i class="fas fa-file"></i> ${file.name}`;
                // File actions
                fileItem.addEventListener('click', () => {
                    // Implement file view/edit functionality
                    console.log(`View/edit file: ${currentPath}${file.name}`);
                });
            } else {
                fileItem.innerHTML = `<i class="fas fa-folder"></i> ${file.name}`;
                // Directory navigation
                fileItem.addEventListener('click', () => {
                    loadFiles(`${currentPath}${file.name}/`);
                });
            }

            fileList.appendChild(fileItem);
        }
    }

    // Database management
    function initDatabaseManager() {
        if (!SERVER_DATA.permissions.databases) return;

        // Load databases
        loadDatabases();

        // Setup new database creation
        const newDbBtn = document.getElementById('new-database');
        if (newDbBtn) {
            newDbBtn.addEventListener('click', () => {
                // Implement database creation modal/logic
                console.log('New database creation clicked');
            });
        }
    }

    function loadDatabases() {
        const dbList = document.querySelector('.database-list');
        if (!dbList) return;

        // Show loading
        dbList.innerHTML = '<div class="database-loading"><i class="fas fa-spinner fa-spin"></i> Loading databases...</div>';

        // Fetch databases from API
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/databases`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                displayDatabases(data.data);
            })
            .catch(error => {
                console.error('Error loading databases:', error);
                dbList.innerHTML = '<div class="error-message">Error loading databases. Please try again.</div>';
            });
    }

    function displayDatabases(databases) {
        const dbList = document.querySelector('.database-list');
        if (!dbList) return;

        // Clear previous content
        dbList.innerHTML = '';

        if (databases.length === 0) {
            dbList.innerHTML = '<div class="empty-list">No databases found</div>';
            return;
        }

        for (const db of databases) {
            const dbItem = document.createElement('div');
            dbItem.className = 'database-item';
            dbItem.innerHTML = `
                <div class="database-info">
                    <h4>${db.attributes.name}</h4>
                    <div class="database-details">
                        <span>Host: ${db.attributes.host}</span>
                        <span>Username: ${db.attributes.username}</span>
                    </div>
                </div>
                <div class="database-actions">
                    <button class="btn btn-sm btn-primary view-password" data-id="${db.attributes.id}">View Password</button>
                    <button class="btn btn-sm btn-danger delete-db" data-id="${db.attributes.id}">Delete</button>
                </div>
            `;

            dbList.appendChild(dbItem);
        }

        // Setup password view buttons
        const viewPasswordBtns = document.querySelectorAll('.view-password');
        viewPasswordBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const dbId = e.target.getAttribute('data-id');
                // Implement password view functionality
                console.log(`View password for database ID: ${dbId}`);
            });
        });

        // Setup delete buttons
        const deleteDbBtns = document.querySelectorAll('.delete-db');
        deleteDbBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const dbId = e.target.getAttribute('data-id');
                // Implement database deletion functionality
                console.log(`Delete database ID: ${dbId}`);
            });
        });
    }

    // User management (subusers)
    function initUserManager() {
        if (!SERVER_DATA.permissions.users) return;

        // Load users
        loadUsers();

        // Setup new user creation
        const newUserBtn = document.getElementById('new-user');
        if (newUserBtn) {
            newUserBtn.addEventListener('click', () => {
                // Implement user creation modal/logic
                console.log('New user creation clicked');
            });
        }
    }

    function loadUsers() {
        const userList = document.querySelector('.user-list');
        if (!userList) return;

        // Show loading
        userList.innerHTML = '<div class="user-loading"><i class="fas fa-spinner fa-spin"></i> Loading users...</div>';

        // Fetch users from API
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/users`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                displayUsers(data.data);
            })
            .catch(error => {
                console.error('Error loading users:', error);
                userList.innerHTML = '<div class="error-message">Error loading users. Please try again.</div>';
            });
    }

    function displayUsers(users) {
        const userList = document.querySelector('.user-list');
        if (!userList) return;

        // Clear previous content
        userList.innerHTML = '';

        if (users.length === 0) {
            userList.innerHTML = '<div class="empty-list">No subusers found</div>';
            return;
        }

        for (const user of users) {
            const userItem = document.createElement('div');
            userItem.className = 'user-item';
            userItem.innerHTML = `
                <div class="user-info">
                    <h4>${user.attributes.email}</h4>
                    <div class="user-permissions">
                        ${Object.entries(user.attributes.permissions).filter(([k, v]) => v).map(([k, v]) => `<span class="permission-tag">${k}</span>`).join('')}
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary edit-user" data-id="${user.attributes.id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-user" data-id="${user.attributes.id}">Delete</button>
                </div>
            `;

            userList.appendChild(userItem);
        }

        // Setup edit buttons
        const editUserBtns = document.querySelectorAll('.edit-user');
        editUserBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.target.getAttribute('data-id');
                // Implement user edit functionality
                console.log(`Edit user ID: ${userId}`);
            });
        });

        // Setup delete buttons
        const deleteUserBtns = document.querySelectorAll('.delete-user');
        deleteUserBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.target.getAttribute('data-id');
                // Implement user deletion functionality
                console.log(`Delete user ID: ${userId}`);
            });
        });
    }

    // Backup management
    function initBackupManager() {
        if (!SERVER_DATA.permissions.backups) return;

        // Load backups
        loadBackups();

        // Setup new backup creation
        const newBackupBtn = document.getElementById('new-backup');
        if (newBackupBtn) {
            newBackupBtn.addEventListener('click', () => {
                // Implement backup creation modal/logic
                console.log('New backup creation clicked');
            });
        }
    }

    function loadBackups() {
        const backupList = document.querySelector('.backup-list');
        if (!backupList) return;

        // Show loading
        backupList.innerHTML = '<div class="backup-loading"><i class="fas fa-spinner fa-spin"></i> Loading backups...</div>';

        // Fetch backups from API
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/backups`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                displayBackups(data.data);
            })
            .catch(error => {
                console.error('Error loading backups:', error);
                backupList.innerHTML = '<div class="error-message">Error loading backups. Please try again.</div>';
            });
    }

    function displayBackups(backups) {
        const backupList = document.querySelector('.backup-list');
        if (!backupList) return;

        // Clear previous content
        backupList.innerHTML = '';

        if (backups.length === 0) {
            backupList.innerHTML = '<div class="empty-list">No backups found</div>';
            return;
        }

        for (const backup of backups) {
            const backupItem = document.createElement('div');
            backupItem.className = 'backup-item';

            // Format date
            const createdDate = new Date(backup.attributes.created_at);
            const formattedDate = createdDate.toLocaleString();

            // Format size
            const sizeMB = Math.round(backup.attributes.bytes / 1024 / 1024 * 100) / 100;

            backupItem.innerHTML = `
                <div class="backup-info">
                    <h4>${backup.attributes.name || 'Backup #' + backup.attributes.uuid.substring(0, 8)}</h4>
                    <div class="backup-details">
                        <span>Created: ${formattedDate}</span>
                        <span>Size: ${sizeMB} MB</span>
                        <span class="backup-status ${backup.attributes.is_successful ? 'success' : 'failed'}">${backup.attributes.is_successful ? 'Completed' : 'Failed'}</span>
                    </div>
                </div>
                <div class="backup-actions">
                    ${backup.attributes.is_successful ? `<button class="btn btn-sm btn-primary download-backup" data-id="${backup.attributes.uuid}">Download</button>` : ''}
                    <button class="btn btn-sm btn-danger delete-backup" data-id="${backup.attributes.uuid}">Delete</button>
                </div>
            `;

            backupList.appendChild(backupItem);
        }

        // Setup download buttons
        const downloadBackupBtns = document.querySelectorAll('.download-backup');
        downloadBackupBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const backupId = e.target.getAttribute('data-id');
                // Implement backup download functionality
                console.log(`Download backup ID: ${backupId}`);
            });
        });

        // Setup delete buttons
        const deleteBackupBtns = document.querySelectorAll('.delete-backup');
        deleteBackupBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const backupId = e.target.getAttribute('data-id');
                // Implement backup deletion functionality
                console.log(`Delete backup ID: ${backupId}`);
            });
        });
    }

    // Settings management
    function initSettingsManager() {
        if (!SERVER_DATA.permissions.settings) return;

        // Setup save details button
        const saveDetailsBtn = document.getElementById('save-details');
        if (saveDetailsBtn) {
            saveDetailsBtn.addEventListener('click', () => {
                // Implement save server details functionality
                console.log('Save server details clicked');
            });
        }

        // Load startup variables if user has startup permission
        if (SERVER_DATA.permissions.startup) {
            loadStartupVariables();

            // Setup save startup button
            const saveStartupBtn = document.getElementById('save-startup');
            if (saveStartupBtn) {
                saveStartupBtn.addEventListener('click', () => {
                    // Implement save startup variables functionality
                    console.log('Save startup variables clicked');
                });
            }
        }
    }

    function loadStartupVariables() {
        const startupVars = document.querySelector('.startup-variables');
        if (!startupVars) return;

        // Show loading
        startupVars.innerHTML = '<div class="startup-loading"><i class="fas fa-spinner fa-spin"></i> Loading startup configuration...</div>';

        // Fetch startup variables from API
        fetch(`${SERVER_DATA.apiUrl}/api/client/servers/${SERVER_DATA.uuid}/startup`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('pterodactyl_token')}`
            }
        })
            .then(response => response.json())
            .then(data => {
                displayStartupVariables(data.data);
            })
            .catch(error => {
                console.error('Error loading startup variables:', error);
                startupVars.innerHTML = '<div class="error-message">Error loading startup configuration. Please try again.</div>';
            });
    }

    function displayStartupVariables(data) {
        const startupVars = document.querySelector('.startup-variables');
        if (!startupVars) return;

        // Clear previous content
        startupVars.innerHTML = '';

        // Display startup command
        const startupCommand = document.createElement('div');
        startupCommand.className = 'startup-command';
        startupCommand.innerHTML = `
            <label>Startup Command</label>
            <div class="code-block">${data.attributes.startup_command}</div>
        `;
        startupVars.appendChild(startupCommand);

        // Display variables
        if (data.attributes.variables && data.attributes.variables.length > 0) {
            const variablesContainer = document.createElement('div');
            variablesContainer.className = 'startup-variables-list';

            for (const variable of data.attributes.variables) {
                const varItem = document.createElement('div');
                varItem.className = 'variable-item';

                const inputType = variable.rules.includes('boolean') ? 'checkbox' : 'text';
                const inputValue = inputType === 'checkbox' ? (variable.server_value === '1' ? 'checked' : '') : variable.server_value;

                varItem.innerHTML = `
                    <div class="variable-info">
                        <label for="var-${variable.env_variable}">${variable.name}</label>
                        ${variable.description ? `<p class="variable-description">${variable.description}</p>` : ''}
                    </div>
                    <div class="variable-input">
                        ${inputType === 'checkbox'
                    ? `<input type="checkbox" id="var-${variable.env_variable}" name="var-${variable.env_variable}" data-env="${variable.env_variable}" ${inputValue}>`
                    : `<input type="text" id="var-${variable.env_variable}" name="var-${variable.env_variable}" data-env="${variable.env_variable}" value="${inputValue}" class="form-control">`
                }
                    </div>
                `;

                variablesContainer.appendChild(varItem);
            }

            startupVars.appendChild(variablesContainer);
        } else {
            startupVars.innerHTML += '<div class="empty-list">No startup variables available</div>';
        }
    }

    // Helper function to get cookie by name
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Initialize components based on permissions
    // First update server status based on initial data
    updateServerStatus(SERVER_DATA.status);

    // Then initialize features
    initConsoleSocket();
    setupPowerControls();
    setupConsoleInput();
    initFileManager();
    initDatabaseManager();
    initUserManager();
    initBackupManager();
    initSettingsManager();

    // Auto-select first tab
    if (tabButtons.length > 0) {
        tabButtons[0].click();
    }
});

// Live Resource Updates
function updateServerResources() {
    const serverId = SERVER_DATA.id;

    // Fetch updated resources
    fetch(`api/server_resources.php?id=${serverId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const resources = data.data.attributes.resources;

                // Update memory
                const memoryMB = Math.round(resources.memory_bytes / 1024 / 1024);
                const memoryText = memoryMB >= 1024 ?
                    (Math.round(memoryMB / 1024 * 100) / 100) + 'GB' :
                    memoryMB + 'MB';
                document.querySelector('.memory-usage').textContent = memoryText;

                // Update disk
                const diskMB = Math.round(resources.disk_bytes / 1024 / 1024);
                const diskText = diskMB >= 1024 ?
                    (Math.round(diskMB / 1024 * 100) / 100) + 'GB' :
                    diskMB + 'MB';
                document.querySelector('.disk-usage').textContent = diskText;

                // Update CPU
                document.querySelector('.cpu-usage').textContent = resources.cpu_absolute + '%';

                // Update progress bars
                document.querySelector('.memory-usage').parentNode.previousElementSibling.querySelector('.progress').style.width =
                    Math.min((resources.memory_bytes / (1024 * 1024 * 1024)) * 100, 100) + '%';
                document.querySelector('.disk-usage').parentNode.previousElementSibling.querySelector('.progress').style.width =
                    Math.min((resources.disk_bytes / (1024 * 1024 * 1024)) * 100, 100) + '%';
                document.querySelector('.cpu-usage').parentNode.previousElementSibling.querySelector('.progress').style.width =
                    resources.cpu_absolute + '%';

                // Update server status based on memory usage
                const isOnline = resources.memory_bytes > 0;
                const currentState = data.data.attributes.current_state;

                // Get the status elements
                const statusDot = document.querySelector('.notification-dot');
                const statusText = document.querySelector('.status-text');
                const serverContainer = document.querySelector('.server-header');

                // Remove all existing status classes
                statusDot.classList.remove('online', 'offline', 'starting', 'stopping', 'installing', 'suspended', 'unknown');
                serverContainer.classList.remove('status-online', 'status-offline', 'status-installing', 'status-transferring');

                // Set appropriate status class
                if (currentState) {
                    statusDot.classList.add(currentState);
                    statusText.textContent = currentState.charAt(0).toUpperCase() + currentState.slice(1);

                    if (currentState === 'online' || currentState === 'running') {
                        serverContainer.classList.add('status-online');
                    } else if (currentState === 'offline' || currentState === 'stopping') {
                        serverContainer.classList.add('status-offline');
                    } else if (currentState === 'installing') {
                        serverContainer.classList.add('status-installing');
                    } else if (currentState === 'transferring') {
                        serverContainer.classList.add('status-transferring');
                    }
                } else {
                    // Fallback to memory-based status if no current_state
                    if (isOnline) {
                        statusDot.classList.add('online');
                        statusText.textContent = 'Online';
                        serverContainer.classList.add('status-online');
                    } else {
                        statusDot.classList.add('offline');
                        statusText.textContent = 'Offline';
                        serverContainer.classList.add('status-offline');
                    }
                }
            } else {
                console.error('Error in server response:', data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching server resources:', error);
        });
}

// Update resources every 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.server-resources')) {
        updateServerResources(); // Initial update
        setInterval(updateServerResources, 10000); // Update every 10 seconds
    }
});