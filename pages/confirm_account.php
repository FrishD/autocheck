<?php
// confirm_account.php - Email verification page
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

$error = '';
$success = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $auth->sanitizeInput($_GET['token']);
    
    // Verify token
    $result = $auth->verifyEmailToken($token);
    
    if ($result['success']) {
        $success = $result['message'];
        // Redirect to login after showing success message
        header('refresh:3;url=login.php');
    } else {
        $error = $result['message'];
    }
} else {
    $error = 'קישור אימות חסר או לא תקין';
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>אימות חשבון | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">AutoCheck</div>
            <nav>
                <ul>
                    <li><a href="login.php">התחברות</a></li>
                    <li><a href="register.php">הרשמה</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>אימות חשבון</h1>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                    <div class="auth-links">
                        <a href="login.php">חזרה לעמוד ההתחברות</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                    <p>מועבר לעמוד ההתחברות...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>כל הזכויות שמורות © <?php echo date('Y'); ?> מערכת AutoCheck</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html>