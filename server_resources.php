<?php
// Aktiviere die Fehleranzeige nur während der Entwicklung (zur Fehlersuche)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Stille die Ausgabe, um JSON-Antwort nicht zu beschädigen
ob_start();

// Korrigierte Pfade mit dirname(__FILE__)
require_once 'config.php';
require_once 'session.php';
require_once 'auth.php';

// Sicherstellen, dass der Benutzer angemeldet ist
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    ob_end_clean(); // Puffer leeren
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Prüfen, ob eine Server-ID angegeben wurde
if (!isset($_GET['id']) || empty($_GET['id'])) {
    ob_end_clean(); // Puffer leeren
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID is required']);
    exit;
}

$serverId = $_GET['id'];

// Ressourcen vom Pterodactyl API abrufen
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => PTERODACTYL_URL . '/api/application/servers/' . $serverId,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PTERODACTYL_CLIENT_API_KEY,
        'Accept: application/json',
        'Content-Type: application/json',
    ]
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

// Antwort zusammenstellen
$result = ['success' => false];

if ($httpCode === 200) {
    $data = json_decode($response, true);

    // Serverdetails abrufen (nur wenn Ressourcen erfolgreich abgerufen wurden)
    $serverDetails = getServerDetails($serverId);

    // Überprüfen, ob der Server im "Installing" oder "Suspended" Status ist
    if ($serverDetails && isset($serverDetails['attributes'])) {
        $serverAttr = $serverDetails['attributes'];

        // Füge die Server-Statusdaten zum API-Response hinzu
        if (isset($serverAttr['suspended']) && $serverAttr['suspended']) {
            $data['attributes']['current_state'] = 'suspended';
        } else if (isset($serverAttr['installing']) && $serverAttr['installing']) {
            $data['attributes']['current_state'] = 'installing';
        } else if (isset($serverAttr['transferring']) && $serverAttr['transferring']) {
            $data['attributes']['current_state'] = 'transferring';
        }
    }

    // Überprüfe RAM-Verbrauch für den Status
    if (isset($data['attributes']['resources']['memory_bytes']) &&
        $data['attributes']['resources']['memory_bytes'] == 0 &&
        (!isset($data['attributes']['current_state']) ||
            $data['attributes']['current_state'] == 'running')) {
        $data['attributes']['current_state'] = 'offline';
    }

    $result = ['success' => true, 'data' => $data];
} else {
    $result = [
        'success' => false,
        'error' => 'Failed to fetch server resources',
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'api_url' => PTERODACTYL_URL . '/api/client/servers/' . $serverId . '/resources'
    ];
}

// Puffer leeren und korrekten Content-Type setzen
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($result);
exit;

/**
 * Ruft die Serverdetails vom Pterodactyl API ab
 *
 * @param string $serverId Die ID des Servers
 * @return array|null Die Serverdetails oder null im Fehlerfall
 */
function getServerDetails($serverId) {
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
    curl_close($curl);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }

    return null;
}