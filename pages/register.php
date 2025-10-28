<?php
// register.php - Registration page
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$codeInfo = null;

// Check if registration code is provided
if (isset($_GET['code'])) {
    $code = $auth->sanitizeInput($_GET['code']);
    $codeInfo = $auth->isValidRegistrationCode($code);
    
    if (!$codeInfo) {
        $error = 'קוד ההרשמה אינו תקף או שפג תוקפו';
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $codeInfo) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $userData = [
            'email' => $auth->sanitizeInput($_POST['email']),
            'password' => $_POST['password'], // Don't sanitize password
            'id_number' => $auth->sanitizeInput($_POST['id_number']),
            'phone' => $auth->sanitizeInput($_POST['phone']),
            'birth_date' => $auth->sanitizeInput($_POST['birth_date']),
        ];
        
        // For teachers, get group and grade if provided
        if ($codeInfo['role'] === 'teacher') {
            $userData['grade'] = isset($_POST['grade']) ? $auth->sanitizeInput($_POST['grade']) : null;
            $userData['user_group'] = isset($_POST['user_group']) ? $auth->sanitizeInput($_POST['user_group']) : null;
        }
        
        // Attempt registration
        $result = $user->register($userData, $_GET['code']);
        
        if ($result['success']) {
            $success = $result['message'];
            
            // Redirect to login after a delay
            header('refresh:3;url=login.php');
        } else {
            $error = $result['message'];
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
    <title>הרשמה | <?php echo SITE_NAME; ?></title>
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
                <h1>הרשמה</h1>
                <p>צור חשבון חדש במערכת</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                <?php else: ?>
                    <?php if (!isset($_GET['code'])): ?>
                        <div class="code-request">
                            <p>יש צורך בקוד הרשמה כדי להירשם למערכת.</p>
                            <p>אנא בקש קוד הרשמה מהמנהל או מהמורה שלך.</p>
                            
                            <form method="get" action="" class="code-form">
                                <div class="form-group">
                                    <label for="code">קוד הרשמה:</label>
                                    <input type="text" id="code" name="code" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">המשך</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($codeInfo): ?>
                        <div class="registration-info">
                            <?php if ($codeInfo['role'] === 'teacher'): ?>
                                <p>אתה נרשם כמורה במערכת.</p>
                            <?php else: ?>
                                <p>אתה נרשם כתלמיד בשכבה <?php echo $codeInfo['grade']; ?>, קבוצה <?php echo $codeInfo['user_group']; ?>.</p>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" action="" class="registration-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="form-group">
                                <label for="email">אימייל:</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">סיסמה:</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small>הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_number">תעודת זהות:</label>
                                <input type="text" id="id_number" name="id_number" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">מספר טלפון:</label>
                                <input type="tel" id="phone" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="birth_date">תאריך לידה:</label>
                                <input type="date" id="birth_date" name="birth_date" class="form-control" required>
                            </div>
                            
                            <?php if ($codeInfo['role'] === 'teacher'): ?>
                                <div class="form-group">
                                    <label for="grade">שכבת לימוד (אופציונלי):</label>
                                    <input type="text" id="grade" name="grade" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="user_group">קבוצה (אופציונלי):</label>
                                    <input type="text" id="user_group" name="user_group" class="form-control">
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">הירשם</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="auth-links">
                        <a href="login.php">כבר יש לך חשבון? התחבר כאן</a>
                    </div>
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