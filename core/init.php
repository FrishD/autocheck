<?php
// init.php - Initialize application
require_once 'auth.php';
require_once 'user.php';
require_once 'db.php'; // Make sure db is included

// Initialize authentication
$auth = new Auth();
$auth->startSecureSession();

// Initialize user manager
$user = new User();

// --- Create default users if they don't exist ---
$db_instance = Database::getInstance();

// Check for admin user
$adminExists = $db_instance->getRow("SELECT id FROM users WHERE email = :email", ['email' => 'admin']);
if (!$adminExists) {
    $user->createDirectUser([
        'email' => 'admin',
        'password' => 'admin123',
        'role' => 'teacher',
        'id_number' => '123456789',
        'phone' => '0540000000'
    ]);
}

// Check for student user
$studentExists = $db_instance->getRow("SELECT id FROM users WHERE email = :email", ['email' => 'student']);
if (!$studentExists) {
    $user->createDirectUser([
        'email' => 'student',
        'password' => 'student123',
        'role' => 'student',
        'id_number' => '987654321',
        'phone' => '0520000000'
    ]);
}
// --- End of default user creation ---


// Function to get current page
function getCurrentPage() {
    $page = basename($_SERVER['PHP_SELF']);
    return $page;
}

// Function to check if user has access to current page
function checkAccess($allowedRoles = null) {
    global $auth;

    // Public pages - no login required
    $publicPages = ['login.php', 'register.php', 'index.php'];
    $currentPage = getCurrentPage();

    if (in_array($currentPage, $publicPages)) {
        return true;
    }

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // Check if user has required role
    if ($allowedRoles !== null && !$auth->hasRole($allowedRoles)) {
        header('Location: unauthorized.php');
        exit;
    }

    return true;
}

// Function to display error message
function showError($message) {
    return '<div class="error-message">' . $message . '</div>';
}

// Function to display success message
function showSuccess($message) {
    return '<div class="success-message">' . $message . '</div>';
}

// Set error reporting based on environment
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    // Development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production environment
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set('Asia/Jerusalem');