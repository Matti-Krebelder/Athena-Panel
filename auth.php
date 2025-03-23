<?php
require_once 'database.php';
require_once 'pterodactyl.php';
require_once 'session.php';

class Auth {
    private $db;
    private $pterodactyl;
    private $session;
    
    public function __construct() {
        $this->db = new Database();
        $this->pterodactyl = new PterodactylAPI();
        $this->session = new SessionManager();
    }
    
    public function register($username, $password, $email, $firstName, $lastName) {
        if ($this->db->getUserByUsername($username)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        if ($this->db->getUserByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        $pterodactylResponse = $this->pterodactyl->createUser(
            $username, 
            $email, 
            $firstName, 
            $lastName, 
            $password
        );
        
        if (!$pterodactylResponse) {
            return ['success' => false, 'message' => 'Failed to create user in the Backend :('];
        }
        
        $pterodactylId = $pterodactylResponse['attributes']['id'];
        $pterodactylUuid = $pterodactylResponse['attributes']['uuid'];
        
        $result = $this->db->registerUser(
            $username, 
            $password, 
            $email, 
            $firstName, 
            $lastName, 
            $pterodactylUuid, 
            $pterodactylId
        );
        
        if (!$result) {
            return ['success' => false, 'message' => 'Failed to create local user account'];
        }
        
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    public function login($username, $password) {
        $user = $this->db->verifyUserCredentials($username, $password);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        $this->session->createUserSession($user);
        
        return ['success' => true, 'message' => 'Login successful'];
    }
    
    public function logout() {
        $this->session->destroySession();
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    public function isLoggedIn() {
        return $this->session->isLoggedIn();
    }
    
    public function getCurrentUser() {
        return $this->session->getCurrentUser();
    }
}