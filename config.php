<?php
// Database configuration
define('DB_PATH', __DIR__ . '/database.sqlite');

// Pterodactyl API configuration
define('PTERODACTYL_URL', 'YOUR_PTERODACTYL_URL_HERE'); //example: https://hosting.devsconnect.de
define('PTERODACTYL_API_KEY', 'yourapikeyhere');    // you can find it here: https://YOUR_PTERODACTYL_URL/admin/api
define('PTERODACTYL_CLIENT_API_KEY', 'yourapikeyhere'); // you can find it here: https://YOUR_PTERODACTYL_URL/account/api

// Application settings
define('APP_NAME', 'Devsconnect');
define('SESSION_LIFETIME', 86400); // 24 hours

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);