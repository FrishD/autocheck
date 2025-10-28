<?php
// reset_password.php - Password reset page
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

$error = '';
$success = '';
$validToken = false;
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $auth->sanitizeInput($_GET['token']);
    
    // Check if token is valid
    $validToken = $auth->isValidResetToken($token);
    
    if (!$validToken) {
        $error = 'קישור איפוס הסיסמה אינו תקף או שפג תוקפו';
    }
} else {
    $error = 'קישור איפוס הסיסמה חסר או לא תקין';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Check if passwords match
        if ($password !== $confirmPassword) {
            $error = 'הסיסמאות אינן תואמות';
        } 
        // Check password strength
        elseif (!$user->isPasswordStrong($password)) {
            $error = 'הסיסמה אינה עומדת בדרישות האבטחה. הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד';
        } 
        else {
            // Reset password
            $result = $auth->resetPassword($token, $password);
            
            if ($result['success']) {
                $success = $result['message'];
                // Redirect to login after showing success message
                header('refresh:3;url=login.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>איפוס סיסמה | <?php echo SITE_NAME; ?></title>
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
                <h1>איפוס סיסמה</h1>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                    <?php if (!$validToken): ?>
                        <div class="auth-links">
                            <a href="login.php">חזרה לעמוד ההתחברות</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                    <p>מועבר לעמוד ההתחברות...</p>
                <?php elseif ($validToken): ?>
                    <form method="post" action="" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="password">סיסמה חדשה:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small>הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">אימות סיסמה:</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">שמור סיסמה חדשה</button>
                        </div>
                    </form>
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