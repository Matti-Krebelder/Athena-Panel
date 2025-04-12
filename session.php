<?php
require_once 'config.php';

class SessionManager {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function createUserSession($user) {
        $_SESSION['user_id'] = $user['pterodactyl_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['uuid'] = $user['pterodactyl_uuid'];
        $_SESSION['email'] = isset($user['email']) ? $user['email'] : '';
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
    }

    public function isLoggedIn() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                $this->destroySession();
                return false;
            }

            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            // Get user email from Pterodactyl API if not already stored
            $email = isset($_SESSION['email']) && !empty($_SESSION['email'])
                ? $_SESSION['email']
                : $this->fetchUserEmailFromAPI($_SESSION['user_id']);

            // Create Gravatar URL using the email
            $avatarUrl = $this->getGravatarUrl($email);

            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'uuid' => $_SESSION['uuid'],
                'email' => $email,
                'avatar' => $avatarUrl
            ];
        }
        return null;
    }

    private function fetchUserEmailFromAPI($userId) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => PTERODACTYL_URL . "/api/application/users/" . $userId,
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

        if (!$err) {
            $userData = json_decode($response, true);
            if (isset($userData['attributes']['email'])) {
                $email = $userData['attributes']['email'];
                // Store email in session for future use
                $_SESSION['email'] = $email;
                return $email;
            }
        }

        return '';
    }

    private function getGravatarUrl($email) {
        // Make sure $email is a string and not empty
        $email = is_string($email) ? trim($email) : '';
        return "https://www.gravatar.com/avatar/" . md5(strtolower($email)) . "?s=400";
    }

    public function destroySession() {
        $_SESSION = [];
        session_destroy();
    }
}