<?php
// my_registration_codes.php - View registration codes created by user
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user has permission (admin or teacher)
checkAccess(['admin', 'teacher']);

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance(); // Add the missing $db instantiation

// Get all registration codes created by current user
$registrationCodes = $user->getRegistrationCodes($currentUser['id']);

// Generate CSRF token for any actions
$csrfToken = $auth->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>קודי הרשמה שלי | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <h1>קודי הרשמה שיצרתי</h1>
        
        <?php if (empty($registrationCodes)): ?>
            <p>לא יצרת עדיין קודי הרשמה.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>קוד</th>
                        <th>תפקיד</th>
                        <th>שכבה</th>
                        <th>קבוצה</th>
                        <th>נוצר בתאריך</th>
                        <th>בתוקף עד</th>
                        <th>סטטוס</th>
                        <th>משתמש בקוד</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrationCodes as $code): ?>
                        <tr>
                            <td><?php echo $code['code']; ?></td>
                            <td><?php echo getHebrewRole($code['role']); ?></td>
                            <td><?php echo $code['grade'] ?: '-'; ?></td>
                            <td><?php echo $code['user_group'] ?: '-'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($code['expires_at'])); ?></td>
                            <td>
                                <?php
                                if ($code['used']) {
                                    echo '<span class="status-used">נוצל</span>';
                                } else if (strtotime($code['expires_at']) < time()) {
                                    echo '<span class="status-expired">פג תוקף</span>';
                                } else {
                                    echo '<span class="status-valid">בתוקף</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $code['used_by_email'] ?: '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="registration-link">
                <p>קישור מלא להרשמה:</p>
                <code><?php 
                    echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . 
                         $_SERVER['HTTP_HOST'] . 
                         dirname($_SERVER['PHP_SELF']) . 
                         "/register.php?code="; 
                ?></code><span>[הקוד]</span>
            </div>
        <?php endif; ?>
        
        <div class="links">
            <a href="dashboard.php">חזרה ללוח הבקרה</a>
            <a href="create_registration_code.php">צור קוד הרשמה חדש</a>
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