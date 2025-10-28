<?php
// manage_students.php - Admin page to manage students
require_once 'C:\xampp\htdocs\autocheck\core\init.php';
require_once 'C:\xampp\htdocs\autocheck\core\user.php';
require_once 'C:\xampp\htdocs\autocheck\core\email_utils.php';

// Check if user is admin or teacher
checkAccess(['admin', 'teacher']);

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance();
$error = '';
$success = '';

// Get filter parameters
$grade = isset($_GET['grade']) ? $auth->sanitizeInput($_GET['grade']) : '';
$group = isset($_GET['group']) ? $auth->sanitizeInput($_GET['group']) : '';

// Get all available grades and groups for filters
$grades = $user->getAllGrades();
$groups = $user->getAllGroups();

// Get students based on filters
$students = $user->getStudents($grade, $group);

// Handle student status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $studentId = intval($_POST['student_id']);
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        
        // Check permissions - only admin can change status
        if ($currentUser['role'] !== 'admin') {
            $error = 'אין לך הרשאה לשנות סטטוס של תלמידים';
        } else {
            // Change student status
            $result = $user->changeUserStatus($studentId, $isActive);
            
            if ($result['success']) {
                $success = $result['message'];
                // Refresh students list
                $students = $user->getStudents($grade, $group);
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get student ID
        $studentId = intval($_POST['student_id']);
        
        // Get student info
        $student = $user->getUserById($studentId);
        
        if (!$student) {
            $error = 'לא נמצא תלמיד עם המזהה הזה';
        } else {
            // Generate password reset token
            $emailUtils = new EmailUtils();
            $token = $auth->generateRandomToken(32);
            $expiry = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRY_HOURS * 3600));
            
            // Save token to database
            $result = $db->insert('password_reset_tokens', [
                'user_id' => $studentId,
                'token' => $token,
                'expires_at' => $expiry
            ]);
            
            if (!$result) {
                $error = 'שגיאה ביצירת טוקן לאיפוס סיסמה';
            } else {
                // Send reset email
                $sent = $emailUtils->sendPasswordReset($student['email'], $student['email'], $token);
                
                if ($sent) {
                    $success = 'הודעת איפוס סיסמה נשלחה בהצלחה לאימייל ' . $student['email'];
                } else {
                    $error = 'שגיאה בשליחת הודעת איפוס סיסמה';
                }
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
    <title>ניהול תלמידים | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <h1>ניהול תלמידים</h1>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <div class="filter-section">
            <h2>סינון תלמידים</h2>
            <form method="get" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="grade">שכבה:</label>
                        <select id="grade" name="grade" class="form-control">
                            <option value="">כל השכבות</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo $grade === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="group">קבוצה:</label>
                        <select id="group" name="group" class="form-control">
                            <option value="">כל הקבוצות</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo $group === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">סנן</button>
                        <a href="manage_students.php" class="btn btn-secondary">נקה סינון</a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="action-buttons">
            <a href="create_registration_code.php" class="btn btn-primary">צור קוד הרשמה לתלמיד חדש</a>
        </div>
        
        <?php if (empty($students)): ?>
            <p>לא נמצאו תלמידים התואמים את הסינון שבחרת.</p>
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
                        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'teacher'): ?>
                            <th>פעולות</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $db->escape($student['email']); ?></td>
                            <td><?php echo $db->escape($student['id_number']); ?></td>
                            <td><?php echo $db->escape($student['phone']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($student['birth_date'])); ?></td>
                            <td><?php echo $db->escape($student['grade'] ?: '-'); ?></td>
                            <td><?php echo $db->escape($student['user_group'] ?: '-'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                            <td><?php echo $student['is_active'] ? 'פעיל' : 'לא פעיל'; ?></td>
                            <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'teacher'): ?>
                                <td class="action-buttons">
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <form method="post" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $student['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $student['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                                <?php echo $student['is_active'] ? 'השבת' : 'הפעל'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Password Reset Button - Available to both admin and teachers -->
                                    <form method="post" action="" style="display: inline; margin-right: 5px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('האם אתה בטוח שברצונך לשלוח הודעת איפוס סיסמה לתלמיד זה?');">
                                            איפוס סיסמה
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
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