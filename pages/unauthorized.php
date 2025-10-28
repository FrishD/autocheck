<?php
// unauthorized.php - Access denied page
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Get referring page if available
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';

// Check if user is logged in
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>גישה נדחתה | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    <?php else: ?>
        <header>
            <div class="container">
                <div class="logo"><?php echo SITE_NAME; ?></div>
                <nav>
                    <ul>
                        <li><a href="login.php">התחברות</a></li>
                        <li><a href="register.php">הרשמה</a></li>
                    </ul>
                </nav>
            </div>
        </header>
    <?php endif; ?>
    
    <div class="container">
        <div class="unauthorized-container">
            <div class="unauthorized-icon">
                <i class="fa fa-lock"></i>
            </div>
            
            <h1>גישה נדחתה</h1>
            
            <div class="error-message">
                <p>אין לך הרשאה לצפות בדף זה.</p>
                <?php if ($isLoggedIn): ?>
                    <p>
                        אתה מחובר כ<?php echo getHebrewRole($currentUser['role']); ?>, 
                        אך הדף המבוקש דורש הרשאות גבוהות יותר.
                    </p>
                <?php else: ?>
                    <p>עליך להתחבר תחילה כדי לצפות בדף זה.</p>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn btn-primary">חזרה ללוח הבקרה</a>
                    <a href="<?php echo $referrer; ?>" class="btn btn-secondary">חזרה לדף הקודם</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">התחבר עכשיו</a>
                    <a href="register.php" class="btn btn-secondary">הירשם למערכת</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>כל הזכויות שמורות © <?php echo date('Y'); ?> מערכת <?php echo SITE_NAME; ?></p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html>

<?php
// Helper function to get Hebrew role name
function getHebrewRole($role) {
    $roles = [
        'admin' => 'מנהל',
        'teacher' => 'מורה',
        'student' => 'תלמיד'
    ];
    
    return $roles[$role] ?? $role;
}
?>