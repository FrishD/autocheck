<?php
// logout.php - Handle user logout
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Destroy the session
$auth->logout();

// Redirect to login page
header('Location: login.php');
exit;
?>