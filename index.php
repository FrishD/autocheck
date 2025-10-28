<?php
// index.php - Main entry point
require_once 'core/init.php';

// Redirect based on login status
if ($auth->isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit;
} else {
    header('Location: pages/login.php');
    exit;
}
?>