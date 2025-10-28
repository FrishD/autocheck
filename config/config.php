<?php
// config.php - Configuration file for the application
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'secure_login_system');

// Add email settings
define('EMAIL_USERNAME', 'noreplyf@arnons.net');
define('EMAIL_PASSWORD', 'yywo uwlk hgmy ejlh'); // Use Gmail app password
define('SITE_URL', 'http://localhost/autocheck/');
define('TOKEN_EXPIRY_HOURS', 24); // Email verification and password reset token expiry in hours

// Security constants
define('HASH_COST', 12); // Cost parameter for password_hash
define('SESSION_NAME', 'secure_session');
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_SECRET', 'change-this-to-a-random-secret-string');

// Application settings
define('SITE_NAME', 'AUTOCHECK');
define('REGISTRATION_CODE_EXPIRY', 7 * 24 * 60 * 60); // 7 days in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);