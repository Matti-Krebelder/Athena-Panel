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
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'uuid' => $_SESSION['uuid']
            ];
        }
        return null;
    }

    public function destroySession() {
        $_SESSION = [];
        session_destroy();
    }
}