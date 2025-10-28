<?php
// dashboard.php - Main dashboard after login
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user is logged in
checkAccess();

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance(); // Add the missing $db instantiation
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>לוח בקרה | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <h1>ברוך הבא, <?php echo $db->escape($currentUser['email']); ?></h1>
        
        <div class="dashboard-info">
            <p>תפקיד: <?php echo getHebrewRole($currentUser['role']); ?></p>
            
            <?php if ($currentUser['grade']): ?>
                <p>שכבה: <?php echo $db->escape($currentUser['grade']); ?></p>
            <?php endif; ?>
            
            <?php if ($currentUser['user_group']): ?>
                <p>קבוצה: <?php echo $db->escape($currentUser['user_group']); ?></p>
            <?php endif; ?>
            
            <p>התחברות אחרונה: <?php echo $currentUser['last_login'] ? date('d/m/Y H:i:s', strtotime($currentUser['last_login'])) : 'לא התחברת בעבר'; ?></p>
        </div>
        
        <div class="dashboard-actions">
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="action-card">
                    <h3>ניהול מורים</h3>
                    <p>צור קודי הרשמה חדשים למורים וצפה במורים הקיימים במערכת.</p>
                    <a href="manage_teachers.php" class="btn btn-primary">ניהול מורים</a>
                </div>
                
                <div class="action-card">
                    <h3>ניהול תלמידים</h3>
                    <p>צפה בכל התלמידים הרשומים במערכת.</p>
                    <a href="manage_students.php" class="btn btn-primary">ניהול תלמידים</a>
                </div>
            <?php endif; ?>
            
            <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'teacher'): ?>
                <div class="action-card">
                    <h3>יצירת קוד הרשמה</h3>
                    <p>צור קוד הרשמה חדש <?php echo $currentUser['role'] === 'admin' ? 'למורה או תלמיד' : 'לתלמיד'; ?>.</p>
                    <a href="create_registration_code.php" class="btn btn-primary">צור קוד הרשמה</a>
                </div>

                <div class="action-card">
                    <h3>קודי ההרשמה שלי</h3>
                    <p>צפה בקודי ההרשמה שיצרת.</p>
                    <a href="my_registration_codes.php" class="btn btn-primary">קודי הרשמה שלי</a>
                </div>
            <?php endif; ?>
            
            <div class="action-card">
                <h3>הפרופיל שלי</h3>
                <p>צפה ועדכן את פרטי הפרופיל שלך.</p>
                <a href="profile.php" class="btn btn-primary">הפרופיל שלי</a>
            </div>
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