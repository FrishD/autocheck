<?php
// submission.php - Student assignment submission page
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Check if user is logged in
checkAccess();

// Load required classes
require_once 'C:\xampp\htdocs\autocheck\core\db.php';
require_once 'C:\xampp\htdocs\autocheck\pages\assignments.php';
require_once 'C:\xampp\htdocs\autocheck\pages\compiler.php';

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance();
$assignmentObj = new Assignment();
$compiler = new Compiler();

// Check that the user is a student
if ($currentUser['role'] !== 'student') {
    redirect('unauthorized.php');
}

// Get assignment ID from URL
$assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$assignmentId) {
    redirect('dashboard.php');
}

// Get assignment details
$assignment = $assignmentObj->getAssignmentById($assignmentId);
if (!$assignment) {
    setAlert('error', 'המטלה המבוקשת לא נמצאה');
    redirect('dashboard.php');
}

// Check if assignment is for this student's grade and group
if ($assignment['grade'] != $currentUser['grade'] || $assignment['user_group'] != $currentUser['user_group']) {
    setAlert('error', 'אין לך גישה למטלה זו');
    redirect('view_assignments.php');
}

// Check if assignment is still active
if (!$assignment['is_active']) {
    setAlert('error', 'המטלה אינה פעילה');
    redirect('dashboard.php');
}

// Check if due date has passed
$now = new DateTime();
$dueDate = new DateTime($assignment['due_date']);
$isPastDue = $now > $dueDate;

// Get tests for this assignment (excluding hidden ones for students)
$tests = $assignmentObj->getTestsByAssignment($assignmentId, false);

// Get latest submission for this student
$latestSubmission = $assignmentObj->getLatestSubmissionForStudent($assignmentId, $currentUser['id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_code']) && !$isPastDue) {
    // Get submission code
    $submissionCode = $_POST['code'] ?? '';
    
    if (empty($submissionCode)) {
        setAlert('error', 'אנא הזן קוד לבדיקה');
    } else {
        // Calculate attempt number
        $attemptNumber = $latestSubmission ? ($latestSubmission['attempt_number'] + 1) : 1;
        
        // Create a unique ID for this submission
        $submissionUniqueId = 'sub_' . $currentUser['id'] . '_' . $assignmentId . '_' . time();
        
        // Compile the code
        $compilationResult = $compiler->compileCode($submissionCode, $submissionUniqueId);
        
        if (!$compilationResult['success']) {
            // Compilation error - save as failed submission
            $submissionData = [
                'assignment_id' => $assignmentId,
                'student_id' => $currentUser['id'],
                'code' => $submissionCode,
                'status' => 'compilation_error',
                'compilation_output' => $compilationResult['output'],
                'attempt_number' => $attemptNumber,
                'total_score' => 0
            ];
            
            $db->insert('submissions', $submissionData);
            
            setAlert('error', 'שגיאת קומפילציה. אנא תקן את הקוד שלך ונסה שוב.');
        } else {
            // Compilation successful - run tests
            $totalScore = 0;
            $maxPossibleScore = 0;
            $testResults = [];
            
            foreach ($tests as $test) {
                $maxPossibleScore += $test['points'];
                
                // Prepare test input
                $testInput = $test['input_data'] ?? '';
                
                // Run test
                $testResult = $compiler->executeProgram($compilationResult['executable'], $testInput, 5);
                
                // Compare expected output with actual output
                $expectedOutput = $test['expected_output'] ?? '';
                $actualOutput = $testResult['output'] ?? '';
                
                // Normalize line endings
                $expectedOutput = str_replace(["\r\n", "\r"], "\n", trim($expectedOutput));
                $actualOutput = str_replace(["\r\n", "\r"], "\n", trim($actualOutput));
                
                // Check if output matches
                $passed = false;
                $score = 0;
                
                if ($testResult['success']) {
                    if ($test['comparison_type'] === 'exact') {
                        $passed = ($expectedOutput === $actualOutput);
                    } else if ($test['comparison_type'] === 'contains') {
                        $passed = (strpos($actualOutput, $expectedOutput) !== false);
                    } else if ($test['comparison_type'] === 'regex') {
                        $passed = (preg_match('/' . preg_quote($expectedOutput, '/') . '/', $actualOutput) === 1);
                    }
                    
                    if ($passed) {
                        $score = $test['points'];
                        $totalScore += $score;
                    }
                }
                
                // Save test result
                $testResults[] = [
                    'test_id' => $test['id'],
                    'test_name' => $test['test_name'],
                    'passed' => $passed,
                    'score' => $score,
                    'max_score' => $test['points'],
                    'execution_time' => $testResult['time_ms'] ?? 0,
                    'expected_output' => $expectedOutput,
                    'actual_output' => $actualOutput,
                    'error' => $testResult['error'] ?? null
                ];
            }
            
            // Calculate final score as percentage
            $scorePercentage = $maxPossibleScore > 0 ? round(($totalScore / $maxPossibleScore) * 100) : 0;
            
            // Determine submission status
            $status = $scorePercentage >= 100 ? 'passed' : ($scorePercentage > 0 ? 'partial' : 'failed');
            
            // Save submission
            $submissionData = [
                'assignment_id' => $assignmentId,
                'student_id' => $currentUser['id'],
                'code' => $submissionCode,
                'status' => $status,
                'compilation_output' => $compilationResult['output'],
                'test_results' => json_encode($testResults),
                'attempt_number' => $attemptNumber,
                'total_score' => $scorePercentage
            ];
            
            $db->insert('submissions', $submissionData);
            
            // Clean up temporary files
            $compiler->cleanup($submissionUniqueId);
            
            setAlert('success', 'הקוד נשלח והועבר ' . count($testResults) . ' בדיקות. ציון: ' . $scorePercentage . '%');
            
            // Refresh the latest submission
            $latestSubmission = $assignmentObj->getLatestSubmissionForStudent($assignmentId, $currentUser['id']);
        }
    }
}

// Prepare submission results for display if available
$testResults = [];
if ($latestSubmission && $latestSubmission['test_results']) {
    $testResults = json_decode($latestSubmission['test_results'], true);
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגשת מטלה | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/codemirror.css">
</head>
<body>
    <?php include 'C:\xampp\htdocs\autocheck\includes\header.php'; ?>
    
    <div class="container">
        <div class="assignment-header">
            <h1><?php echo $db->escape($assignment['title']); ?></h1>
            
            <div class="assignment-meta">
                <p><strong>תאריך הגשה:</strong> <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?></p>
                <p><strong>נוצר על ידי:</strong> <?php echo $db->escape($assignment['created_by_email']); ?></p>
                <p class="<?php echo $isPastDue ? 'text-danger' : ''; ?>">
                    <strong>סטטוס:</strong> 
                    <?php echo $isPastDue ? 'מועד ההגשה עבר' : 'פתוח להגשה'; ?>
                </p>
            </div>
        </div>
        
        <div class="assignment-description">
            <h2>תיאור המטלה</h2>
            <div class="description-content">
                <?php echo nl2br($db->escape($assignment['description'])); ?>
            </div>
        </div>
        
        <?php if ($latestSubmission): ?>
            <div class="submission-info">
                <h2>הגשות קודמות</h2>
                <p><strong>הגשה אחרונה:</strong> <?php echo date('d/m/Y H:i:s', strtotime($latestSubmission['submitted_at'])); ?></p>
                <p><strong>מספר ניסיון:</strong> <?php echo $latestSubmission['attempt_number']; ?></p>
                <p><strong>סטטוס:</strong> 
                    <?php
                    switch ($latestSubmission['status']) {
                        case 'passed':
                            echo '<span class="badge success">עבר</span>';
                            break;
                        case 'partial':
                            echo '<span class="badge warning">חלקי</span>';
                            break;
                        case 'failed':
                            echo '<span class="badge danger">נכשל</span>';
                            break;
                        case 'compilation_error':
                            echo '<span class="badge danger">שגיאת קומפילציה</span>';
                            break;
                        default:
                            echo $latestSubmission['status'];
                    }
                    ?>
                </p>
                <p><strong>ציון:</strong> <?php echo $latestSubmission['total_score']; ?>%</p>
            </div>
            
            <?php if ($latestSubmission['status'] === 'compilation_error'): ?>
                <div class="compilation-error">
                    <h3>שגיאת קומפילציה</h3>
                    <pre><?php echo $db->escape($latestSubmission['compilation_output']); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($testResults)): ?>
                <div class="test-results">
                    <h3>תוצאות בדיקה</h3>
                    <table class="test-results-table">
                        <thead>
                            <tr>
                                <th>שם בדיקה</th>
                                <th>סטטוס</th>
                                <th>ניקוד</th>
                                <th>זמן ריצה</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults as $result): ?>
                                <tr>
                                    <td><?php echo $db->escape($result['test_name']); ?></td>
                                    <td class="<?php echo $result['passed'] ? 'success' : 'danger'; ?>">
                                        <?php echo $result['passed'] ? 'עבר' : 'נכשל'; ?>
                                    </td>
                                    <td><?php echo $result['score']; ?> / <?php echo $result['max_score']; ?></td>
                                    <td><?php echo $result['execution_time']; ?> ms</td>
                                    <td>
                                        <button class="btn btn-small" onclick="toggleTestDetails(<?php echo $result['test_id']; ?>)">הצג פרטים</button>
                                    </td>
                                </tr>
                                <tr id="test-details-<?php echo $result['test_id']; ?>" class="test-details" style="display: none;">
                                    <td colspan="5">
                                        <div class="details-container">
                                            <div class="detail-section">
                                                <h4>פלט מצופה</h4>
                                                <pre><?php echo $db->escape($result['expected_output']); ?></pre>
                                            </div>
                                            <div class="detail-section">
                                                <h4>פלט בפועל</h4>
                                                <pre><?php echo $db->escape($result['actual_output']); ?></pre>
                                            </div>
                                            <?php if ($result['error']): ?>
                                                <div class="detail-section error-section">
                                                    <h4>שגיאות</h4>
                                                    <pre><?php echo $db->escape($result['error']); ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="submission-form">
            <h2>הגש קוד</h2>
            
            <?php if ($isPastDue): ?>
                <div class="alert alert-danger">
                    <p>מועד ההגשה עבר. לא ניתן להגיש יותר.</p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="code">קוד #C</label>
                        <textarea id="code" name="code" class="code-editor"><?php echo $latestSubmission ? $latestSubmission['code'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit_code" class="btn btn-primary">שלח קוד</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'C:\xampp\htdocs\autocheck\includes\footer.php'; ?>

    <script src="../assets/js/codemirror.js"></script>
    <script src="../assets/js/clike.js"></script>
    <script>
        // Initialize CodeMirror
        document.addEventListener('DOMContentLoaded', function() {
            var editor = CodeMirror.fromTextArea(document.getElementById('code'), {
                lineNumbers: true,
                mode: "text/x-csharp",
                theme: "default",
                indentUnit: 4,
                indentWithTabs: true,
                rtlMoveVisually: true,
                direction: "ltr"
            });
        });
        
        // Toggle test details visibility
        function toggleTestDetails(testId) {
            var detailsRow = document.getElementById('test-details-' + testId);
            if (detailsRow.style.display === 'none' || !detailsRow.style.display) {
                detailsRow.style.display = 'table-row';
            } else {
                detailsRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>