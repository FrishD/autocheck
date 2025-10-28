<?php
// C:\xampp\htdocs\autocheck\includes\header.php
require_once dirname(__DIR__) . '/core/init.php';
$currentUser = $auth->isLoggedIn() ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/autocheck/assets/css/styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul>
                <?php if ($currentUser): ?>
                    <li><a href="/autocheck/pages/dashboard.php">לוח בקרה</a></li>
                    <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'teacher'): ?>
                        <li><a href="/autocheck/pages/create_registration_code.php">צור קוד הרשמה</a></li>
                    <?php endif; ?>
                    <li><a href="/autocheck/pages/profile.php">הפרופיל שלי</a></li>
                    <li><a href="/autocheck/pages/logout.php">התנתק</a></li>
                <?php else: ?>
                    <li><a href="/autocheck/pages/login.php">התחבר</a></li>
                    <li><a href="/autocheck/pages/register.php">הירשם</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>