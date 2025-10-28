<?php
// manage_teachers.php - Admin page to manage teachers
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user is admin
checkAccess('admin');

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance(); // Add the missing $db instantiation
$error = '';
$success = '';

// Get all teachers
$teachers = $user->getUsersByRole('teacher');

// Handle teacher status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $teacherId = intval($_POST['teacher_id']);
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        
        // Change teacher status
        $result = $user->changeUserStatus($teacherId, $isActive);
        
        if ($result['success']) {
            $success = $result['message'];
            // Refresh teachers list
            $teachers = $user->getUsersByRole('teacher');
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
    <title>ניהול מורים | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <h1>ניהול מורים</h1>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="create_registration_code.php" class="btn btn-primary">צור קוד הרשמה למורה חדש</a>
        </div>
        
        <?php if (empty($teachers)): ?>
            <p>אין מורים רשומים במערכת.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>אימייל</th>
                        <th>תעודת זהות</th>
                        <th>טלפון</th>
                        <th>תאריך לידה</th>
                        <th>שכבה</th>
                        <th>קבוצה</th>
                        <th>נרשם בתאריך</th>
                        <th>סטטוס</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo $db->escape($teacher['email']); ?></td>
                            <td><?php echo $db->escape($teacher['id_number']); ?></td>
                            <td><?php echo $db->escape($teacher['phone']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($teacher['birth_date'])); ?></td>
                            <td><?php echo $db->escape($teacher['grade'] ?: '-'); ?></td>
                            <td><?php echo $db->escape($teacher['user_group'] ?: '-'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($teacher['created_at'])); ?></td>
                            <td><?php echo $teacher['is_active'] ? 'פעיל' : 'לא פעיל'; ?></td>
                            <td>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $teacher['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $teacher['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $teacher['is_active'] ? 'השבת' : 'הפעל'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="links">
            <a href="dashboard.php">חזרה ללוח הבקרה</a>
        </div>
    </div>
    
    <?php include 'C:\xampp\htdocs\autocheck\includes\footer.php'; ?>
    <script src="../assets/js/script.js"></script>
</body>
</html>