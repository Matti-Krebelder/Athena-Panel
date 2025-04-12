<?php
require_once 'config.php';

class PterodactylAPI {
    private $apiUrl;
    private $apiKey;
    private $clientUrl;
    private $clientApiKey;

    public function __construct() {
        $this->apiUrl = PTERODACTYL_URL . '/api/application';
        $this->apiKey = PTERODACTYL_API_KEY;
        $this->clientUrl = PTERODACTYL_URL . '/api/client';
    }

    /**
     * Set client API key for user-specific operations
     *
     * @param string $apiKey The client API key
     */
    public function setClientApiKey($apiKey) {
        $this->clientApiKey = $apiKey;
    }

    /**
     * Create a new user in Pterodactyl
     *
     * @param string $username Username
     * @param string $email Email address
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param string $password Password
     * @return array|null Response data or null on failure
     */
    public function createUser($username, $email, $firstName, $lastName, $password) {
        $data = [
            'external_id' => 'user_' . time(),
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $password,
            'root_admin' => false,
            'language' => 'en'
        ];

        $response = $this->sendRequest('/users', 'POST', $data);
        return $response;
    }

    /**
     * Get a user by ID
     *
     * @param int $userId User ID
     * @return array|null User data or null on failure
     */
    public function getUser($userId) {
        $response = $this->sendRequest('/users/' . $userId);
        return $response;
    }

    /**
     * Update a user
     *
     * @param int $userId User ID
     * @param array $data Data to update
     * @return array|null Response data or null on failure
     */
    public function updateUser($userId, $data) {
        $response = $this->sendRequest('/users/' . $userId, 'PATCH', $data);
        return $response;
    }

    /**
     * Delete a user
     *
     * @param int $userId User ID
     * @return array|null Response data or null on failure
     */
    public function deleteUser($userId) {
        $response = $this->sendRequest('/users/' . $userId, 'DELETE');
        return $response;
    }

    /**
     * Create a server
     *
     * @param array $data Server configuration data
     * @return array|null Response data or null on failure
     */
    public function createServer($data) {
        $response = $this->sendRequest('/servers', 'POST', $data);
        return $response;
    }

    /**
     * Get all servers
     *
     * @param int $page Page number
     * @return array|null Servers data or null on failure
     */
    public function getServers($page = 1) {
        $response = $this->sendRequest('/servers?page=' . $page);
        return $response;
    }

    /**
     * Get a server by ID
     *
     * @param int $serverId Server ID
     * @return array|null Server data or null on failure
     */
    public function getServer($serverId) {
        $response = $this->sendRequest('/servers/' . $serverId);
        return $response;
    }

    /**
     * Update server details
     *
     * @param int $serverId Server ID
     * @param array $data Data to update
     * @return array|null Response data or null on failure
     */
    public function updateServerDetails($serverId, $data) {
        $response = $this->sendRequest('/servers/' . $serverId . '/details', 'PATCH', $data);
        return $response;
    }

    /**
     * Update server build configuration
     *
     * @param int $serverId Server ID
     * @param array $data Build configuration data
     * @return array|null Response data or null on failure
     */
    public function updateServerBuild($serverId, $data) {
        $response = $this->sendRequest('/servers/' . $serverId . '/build', 'PATCH', $data);
        return $response;
    }

    /**
     * Suspend a server
     *
     * @param int $serverId Server ID
     * @return array|null Response data or null on failure
     */
    public function suspendServer($serverId) {
        $response = $this->sendRequest('/servers/' . $serverId . '/suspend', 'POST');
        return $response;
    }

    /**
     * Unsuspend a server
     *
     * @param int $serverId Server ID
     * @return array|null Response data or null on failure
     */
    public function unsuspendServer($serverId) {
        $response = $this->sendRequest('/servers/' . $serverId . '/unsuspend', 'POST');
        return $response;
    }

    /**
     * Delete a server
     *
     * @param int $serverId Server ID
     * @param bool $force Force delete
     * @return array|null Response data or null on failure
     */
    public function deleteServer($serverId, $force = false) {
        $endpoint = '/servers/' . $serverId;
        if ($force) {
            $endpoint .= '/force';
        }
        $response = $this->sendRequest($endpoint, 'DELETE');
        return $response;
    }

    /**
     * Get server allocations
     *
     * @param int $nodeId Node ID
     * @return array|null Allocations data or null on failure
     */
    public function getNodeAllocations($nodeId) {
        $response = $this->sendRequest('/nodes/' . $nodeId . '/allocations');
        return $response;
    }

    /**
     * Get all nodes
     *
     * @return array|null Nodes data or null on failure
     */
    public function getNodes() {
        $response = $this->sendRequest('/nodes');
        return $response;
    }

    /**
     * Get server utilization using client API
     *
     * @param string $serverIdentifier Server identifier
     * @return array|null Utilization data or null on failure
     */
    public function getServerUtilization($serverIdentifier) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/resources');
        return $response;
    }

    /**
     * Get server details using client API
     *
     * @param string $serverIdentifier Server identifier
     * @return array|null Server details or null on failure
     */
    public function getServerDetails($serverIdentifier) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier);
        return $response;
    }

    /**
     * Send command to server console
     *
     * @param string $serverIdentifier Server identifier
     * @param string $command Command to send
     * @return array|null Response data or null on failure
     */
    public function sendServerCommand($serverIdentifier, $command) {
        $data = [
            'command' => $command
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/command', 'POST', $data);
        return $response;
    }

    /**
     * Change server power state
     *
     * @param string $serverIdentifier Server identifier
     * @param string $state Power state (start, stop, restart, kill)
     * @return array|null Response data or null on failure
     */
    public function setServerPowerState($serverIdentifier, $state) {
        $validStates = ['start', 'stop', 'restart', 'kill'];
        if (!in_array($state, $validStates)) {
            return null;
        }

        $data = [
            'signal' => $state
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/power', 'POST', $data);
        return $response;
    }

    /**
     * List files in a directory
     *
     * @param string $serverIdentifier Server identifier
     * @param string $directory Directory path
     * @return array|null Files list or null on failure
     */
    public function listFiles($serverIdentifier, $directory = '/') {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/files/list?directory=' . urlencode($directory));
        return $response;
    }

    /**
     * Get file contents
     *
     * @param string $serverIdentifier Server identifier
     * @param string $filePath File path
     * @return string|null File contents or null on failure
     */
    public function getFileContents($serverIdentifier, $filePath) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/files/contents?file=' . urlencode($filePath));
        return $response;
    }

    /**
     * Write file contents
     *
     * @param string $serverIdentifier Server identifier
     * @param string $filePath File path
     * @param string $content File content
     * @return array|null Response data or null on failure
     */
    public function writeFileContents($serverIdentifier, $filePath, $content) {
        $data = [
            'file' => $filePath,
            'content' => $content
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/files/write', 'POST', $data);
        return $response;
    }

    /**
     * Create a directory
     *
     * @param string $serverIdentifier Server identifier
     * @param string $path Directory path
     * @param string $name Directory name
     * @return array|null Response data or null on failure
     */
    public function createDirectory($serverIdentifier, $path, $name) {
        $data = [
            'root' => $path,
            'name' => $name
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/files/create-folder', 'POST', $data);
        return $response;
    }

    /**
     * Delete files
     *
     * @param string $serverIdentifier Server identifier
     * @param string $directory Directory path
     * @param array $files Files to delete
     * @return array|null Response data or null on failure
     */
    public function deleteFiles($serverIdentifier, $directory, $files) {
        $data = [
            'root' => $directory,
            'files' => $files
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/files/delete', 'POST', $data);
        return $response;
    }

    /**
     * Get server backups
     *
     * @param string $serverIdentifier Server identifier
     * @return array|null Backups data or null on failure
     */
    public function getServerBackups($serverIdentifier) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/backups');
        return $response;
    }

    /**
     * Create server backup
     *
     * @param string $serverIdentifier Server identifier
     * @return array|null Response data or null on failure
     */
    public function createServerBackup($serverIdentifier) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/backups', 'POST');
        return $response;
    }

    /**
     * Delete server backup
     *
     * @param string $serverIdentifier Server identifier
     * @param string $backupId Backup ID
     * @return array|null Response data or null on failure
     */
    public function deleteServerBackup($serverIdentifier, $backupId) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/backups/' . $backupId, 'DELETE');
        return $response;
    }

    /**
     * Get server databases
     *
     * @param string $serverIdentifier Server identifier
     * @return array|null Databases data or null on failure
     */
    public function getServerDatabases($serverIdentifier) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/databases');
        return $response;
    }

    /**
     * Create server database
     *
     * @param string $serverIdentifier Server identifier
     * @param string $databaseName Database name
     * @param string $remoteHost Remote host
     * @return array|null Response data or null on failure
     */
    public function createServerDatabase($serverIdentifier, $databaseName, $remoteHost = '%') {
        $data = [
            'database' => $databaseName,
            'remote' => $remoteHost
        ];
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/databases', 'POST', $data);
        return $response;
    }

    /**
     * Delete server database
     *
     * @param string $serverIdentifier Server identifier
     * @param string $databaseId Database ID
     * @return array|null Response data or null on failure
     */
    public function deleteServerDatabase($serverIdentifier, $databaseId) {
        $response = $this->sendClientRequest('/servers/' . $serverIdentifier . '/databases/' . $databaseId, 'DELETE');
        return $response;
    }

    /**
     * Check if user has access to server
     *
     * @param int $userId User ID
     * @param string $serverIdentifier Server identifier
     * @return bool True if has access, false otherwise
     */
    public function userHasServerAccess($userId, $serverIdentifier) {
        // Get all servers for user
        $servers = $this->getUserServers($userId);

        if (!$servers || !isset($servers['data'])) {
            return false;
        }

        foreach ($servers['data'] as $server) {
            if ($server['attributes']['identifier'] == $serverIdentifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all servers for a user
     *
     * @param int $userId User ID
     * @return array|null Servers data or null on failure
     */
    public function getUserServers($userId) {
        $response = $this->sendRequest('/users/' . $userId . '/servers');
        return $response;
    }

    /**
     * Send request to application API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array|null Response data or null on failure
     */
    private function sendRequest($endpoint, $method = 'GET', $data = []) {
        $ch = curl_init($this->apiUrl . $endpoint);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: Application/vnd.pterodactyl.v1+json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("API Error ($httpCode): " . $response);
            return null;
        }
    }

    /**
     * Send request to client API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array|null Response data or null on failure
     */
    private function sendClientRequest($endpoint, $method = 'GET', $data = []) {
        if (empty($this->clientApiKey)) {
            error_log("Client API key not set");
            return null;
        }

        $ch = curl_init($this->clientUrl . $endpoint);

        $headers = [
            'Authorization: Bearer ' . $this->clientApiKey,
            'Content-Type: application/json',
            'Accept: Application/vnd.pterodactyl.v1+json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Client API Error ($httpCode): " . $response);
            return null;
        }
    }
}