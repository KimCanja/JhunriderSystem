<?php
// Application constants
define('APP_NAME', 'RentGuard');
define('BASE_URL', 'http://localhost/JhunriderSystem/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Session start - NO ECHO HERE!
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Role checking functions - NO ECHO HERE!
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function redirect($url) {
    header("Location: $url");
    exit;
}
?>