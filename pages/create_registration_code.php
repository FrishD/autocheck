<?php
// create_registration_code.php - Create registration codes for teachers/students
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user has permission (admin or teacher)
checkAccess(['admin', 'teacher']);

$currentUser = $auth->getCurrentUser();
$error = '';
$success = '';
$code = null;
$generatedCodes = [];

// Get all grades and groups for dropdown
$grades = $user->getAllGrades();
$groups = $user->getAllGroups();

// Define grade and group options since they're not being populated correctly
$gradeOptions = ['י', 'י"א', 'י"ב'];
$groupOptions = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $role = $auth->sanitizeInput($_POST['role']);
        $grade = isset($_POST['grade']) ? $auth->sanitizeInput($_POST['grade']) : null;
        $group = isset($_POST['group']) ? $auth->sanitizeInput($_POST['group']) : null;
        $codeCount = isset($_POST['code_count']) ? (int)$auth->sanitizeInput($_POST['code_count']) : 1;
        
        // Validate inputs
        if ($role === 'student' && (empty($grade) || empty($group))) {
            $error = 'חובה לציין שכבה וקבוצה עבור קוד הרשמה לתלמיד';
        } else {
            // Admin can create codes for teachers and students
            // Teachers can only create codes for students
            if (($role === 'teacher' && $currentUser['role'] !== 'admin') || 
                ($role === 'admin')) {
                $error = 'אין לך הרשאה ליצור קוד הרשמה לתפקיד זה';
            } else {
                // Limit the maximum number of codes to generate
                $codeCount = min($codeCount, 100);
                
                // Generate the requested number of codes
                for ($i = 0; $i < $codeCount; $i++) {
                    // Generate code
                    $result = $auth->generateRegistrationCode($role, $grade, $group);
                    
                    if ($result['success']) {
                        $generatedCodes[] = [
                            'code' => $result['code'],
                            'expires_at' => $result['expires_at']
                        ];
                    } else {
                        $error = $result['message'];
                        break;
                    }
                }
                
                if (count($generatedCodes) > 0) {
                    $success = 'נוצרו ' . count($generatedCodes) . ' קודי הרשמה בהצלחה';
                }
            }
        }
        
        // If export to Excel is requested
        if (!empty($generatedCodes) && isset($_POST['export_excel']) && $_POST['export_excel'] === '1') {
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment;filename="registration_codes_' . date('Y-m-d') . '.xls"');
            header('Cache-Control: max-age=0');
            
            // Print Excel XML
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
            echo '<Worksheet ss:Name="קודי הרשמה">';
            echo '<Table>';
            
            // Header row
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">קוד הרשמה</Data></Cell>';
            echo '<Cell><Data ss:Type="String">תפקיד</Data></Cell>';
            echo '<Cell><Data ss:Type="String">שכבה</Data></Cell>';
            echo '<Cell><Data ss:Type="String">קבוצה</Data></Cell>';
            echo '<Cell><Data ss:Type="String">תוקף עד</Data></Cell>';
            echo '<Cell><Data ss:Type="String">קישור הרשמה מלא</Data></Cell>';
            echo '</Row>';
            
            // Data rows
            foreach ($generatedCodes as $codeData) {
                $registrationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . 
                                $_SERVER['HTTP_HOST'] . 
                                dirname($_SERVER['PHP_SELF']) . 
                                "/register.php?code=" . $codeData['code'];
                
                echo '<Row>';
                echo '<Cell><Data ss:Type="String">' . $codeData['code'] . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . $role . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . ($grade ?? '') . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . ($group ?? '') . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($codeData['expires_at'])) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . $registrationUrl . '</Data></Cell>';
                echo '</Row>';
            }
            
            echo '</Table>';
            echo '</Worksheet>';
            echo '</Workbook>';
            exit;
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
    <title>יצירת קוד הרשמה | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>יצירת קוד הרשמה</h1>
                <p>צור קוד להרשמת משתמשים חדשים</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                    
                    <div class="dashboard-info">
                        <h2>קודי ההרשמה שנוצרו:</h2>
                        
                        <div class="registration-codes-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>קוד הרשמה</th>
                                        <th>תוקף עד</th>
                                        <th>קישור מלא</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($generatedCodes as $index => $codeData): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td class="code-display"><?php echo $codeData['code']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($codeData['expires_at'])); ?></td>
                                        <td class="code-display">
                                            <?php 
                                            $registrationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . 
                                                            $_SERVER['HTTP_HOST'] . 
                                                            dirname($_SERVER['PHP_SELF']) . 
                                                            "/register.php?code=" . $codeData['code'];
                                            echo $registrationUrl; 
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-group text-center">
                            <form method="post" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="role" value="<?php echo $role; ?>">
                                <input type="hidden" name="grade" value="<?php echo $grade; ?>">
                                <input type="hidden" name="group" value="<?php echo $group; ?>">
                                <input type="hidden" name="code_count" value="<?php echo count($generatedCodes); ?>">
                                <input type="hidden" name="export_excel" value="1">
                                <button type="submit" class="btn btn-success">הורד קודים לקובץ אקסל</button>
                            </form>
                        </div>
                        
                        <p>שמור מידע זה או שתף אותו עם המשתמשים המיועדים.</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="" class="registration-code-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="role">תפקיד:</label>
                            <select id="role" name="role" class="form-control" required onchange="toggleStudentFields()">
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <option value="teacher">מורה</option>
                                <?php endif; ?>
                                <option value="student">תלמיד</option>
                            </select>
                        </div>
                        
                        <div id="student-fields" <?php echo $currentUser['role'] === 'admin' && isset($_POST['role']) && $_POST['role'] === 'teacher' ? 'style="display:none;"' : ''; ?>>
                            <div class="form-group">
                                <label for="grade">שכבה:</label>
                                <select id="grade" name="grade" class="form-control" required>
                                    <option value="" disabled selected>בחר שכבה</option>
                                    <?php foreach ($gradeOptions as $g): ?>
                                        <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="group">קבוצה:</label>
                                <select id="group" name="group" class="form-control" required>
                                    <option value="" disabled selected>בחר קבוצה</option>
                                    <?php foreach ($groupOptions as $g): ?>
                                        <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="code_count">מספר קודים ליצירה:</label>
                            <input type="number" id="code_count" name="code_count" class="form-control" value="1" min="1" max="100" required>
                            <small class="form-text text-muted">ניתן ליצור עד 100 קודים בבת אחת</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">צור קוד הרשמה</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="auth-links">
                    <a href="dashboard.php">חזרה ללוח הבקרה</a>
                    <a href="my_registration_codes.php">צפה בקודי ההרשמה שלי</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'C:\xampp\htdocs\autocheck\includes\footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function toggleStudentFields() {
            var role = document.getElementById('role').value;
            var studentFields = document.getElementById('student-fields');
            
            if (role === 'student') {
                studentFields.style.display = 'block';
            } else {
                studentFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>