<?php
// profile.php - User profile management
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user is logged in
checkAccess();

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance(); // Add the missing $db instantiation
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $userData = [];
        
        // Phone number update
        if (isset($_POST['phone']) && $_POST['phone'] !== $currentUser['phone']) {
            $userData['phone'] = $auth->sanitizeInput($_POST['phone']);
        }
        
        // Birth date update
        if (isset($_POST['birth_date']) && $_POST['birth_date'] !== $currentUser['birth_date']) {
            $userData['birth_date'] = $auth->sanitizeInput($_POST['birth_date']);
        }
        
        // Grade and group update (only for teachers)
        if ($currentUser['role'] === 'teacher') {
            if (isset($_POST['grade']) && $_POST['grade'] !== $currentUser['grade']) {
                $userData['grade'] = $auth->sanitizeInput($_POST['grade']);
            }
            
            if (isset($_POST['user_group']) && $_POST['user_group'] !== $currentUser['user_group']) {
                $userData['user_group'] = $auth->sanitizeInput($_POST['user_group']);
            }
        }
        
        // Password change
        if (!empty($_POST['new_password'])) {
            $userData['current_password'] = $_POST['current_password']; // Don't sanitize password
            $userData['new_password'] = $_POST['new_password']; // Don't sanitize password
        }
        
        // Update profile
        $result = $user->updateProfile($currentUser['id'], $userData);
        
        if ($result['success']) {
            $success = $result['message'];
            $currentUser = $auth->getCurrentUser(); // Refresh user data
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
    <title>הפרופיל שלי | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <h1>הפרופיל שלי</h1>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <div class="profile-info">
            <div class="info-group">
                <label>אימייל:</label>
                <span><?php echo $db->escape($currentUser['email']); ?></span>
                <small>לא ניתן לשנות את האימייל</small>
            </div>
            
            <div class="info-group">
                <label>תעודת זהות:</label>
                <span><?php echo $db->escape($currentUser['id_number']); ?></span>
                <small>לא ניתן לשנות את תעודת הזהות</small>
            </div>
            
            <div class="info-group">
                <label>תפקיד:</label>
                <span><?php echo getHebrewRole($currentUser['role']); ?></span>
            </div>
            
            <?php if ($currentUser['role'] === 'student'): ?>
                <div class="info-group">
                    <label>שכבה:</label>
                    <span><?php echo $db->escape($currentUser['grade'] ?: '-'); ?></span>
                    <small>לא ניתן לשנות את השכבה</small>
                </div>
                
                <div class="info-group">
                    <label>קבוצה:</label>
                    <span><?php echo $db->escape($currentUser['user_group'] ?: '-'); ?></span>
                    <small>לא ניתן לשנות את הקבוצה</small>
                </div>
            <?php endif; ?>
        </div>
        
        <h2>עדכון פרטים</h2>
        <form method="post" action="" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="phone">מספר טלפון:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo $db->escape($currentUser['phone']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="birth_date">תאריך לידה:</label>
                <input type="date" id="birth_date" name="birth_date" value="<?php echo $db->escape($currentUser['birth_date']); ?>" required>
            </div>
            
            <?php if ($currentUser['role'] === 'teacher'): ?>
                <div class="form-group">
                    <label for="grade">שכבת לימוד (אופציונלי):</label>
                    <input type="text" id="grade" name="grade" value="<?php echo $db->escape($currentUser['grade'] ?: ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_group">קבוצה (אופציונלי):</label>
                    <input type="text" id="user_group" name="user_group" value="<?php echo $db->escape($currentUser['user_group'] ?: ''); ?>">
                </div>
            <?php endif; ?>
            
            <h3>שינוי סיסמה</h3>
            <div class="form-group">
                <label for="current_password">סיסמה נוכחית:</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">סיסמה חדשה:</label>
                <input type="password" id="new_password" name="new_password">
                <small>הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">עדכן פרטים</button>
            </div>
        </form>
        
        <div class="links">
            <a href="dashboard.php">חזרה ללוח הבקרה</a>
        </div>
    </div>
    
    <?php include 'C:\xampp\htdocs\autocheck\includes\footer.php'; ?>
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