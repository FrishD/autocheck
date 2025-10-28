<?php
// user.php - User management functions
require_once 'auth.php';

class User {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Get students with optional filtering by grade and group
     * 
     * @param string $grade Optional grade filter
     * @param string $group Optional group filter
     * @return array Array of student users
     */
    public function getStudents($grade = '', $group = '') {
        $sql = "SELECT * FROM users WHERE role = 'student'";
        $params = [];
        
        if (!empty($grade)) {
            $sql .= " AND grade = :grade";
            $params['grade'] = $grade;
        }
        
        if (!empty($group)) {
            $sql .= " AND user_group = :group";
            $params['group'] = $group;
        }
        
        $sql .= " ORDER BY email";
        
        $students = $this->db->getRows($sql, $params);
        
        // Remove passwords for security
        foreach ($students as &$student) {
            unset($student['password']);
        }
        
        return $students;
    }

    // Register new user
    public function register($userData, $registrationCode) {
        // Validate registration code
        $codeInfo = $this->auth->isValidRegistrationCode($registrationCode);
        
        if (!$codeInfo) {
            return [
                'success' => false,
                'message' => 'קוד ההרשמה אינו תקף או שפג תוקפו'
            ];
        }
        
        // Validate email
        if (!$this->auth->isValidEmail($userData['email'])) {
            return [
                'success' => false,
                'message' => 'כתובת האימייל אינה תקפה'
            ];
        }
        
        // Check if email already exists
        $existingUser = $this->db->getRow(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $userData['email']]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'כתובת האימייל כבר קיימת במערכת'
            ];
        }
        
        // Validate ID number
        if (!$this->auth->isValidIdNumber($userData['id_number'])) {
            return [
                'success' => false,
                'message' => 'תעודת הזהות אינה תקפה'
            ];
        }
        
        // Check if ID number already exists
        $existingUser = $this->db->getRow(
            "SELECT id FROM users WHERE id_number = :id_number",
            ['id_number' => $userData['id_number']]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'תעודת הזהות כבר קיימת במערכת'
            ];
        }
        
        // Validate phone number
        if (!$this->auth->isValidPhoneNumber($userData['phone'])) {
            return [
                'success' => false,
                'message' => 'מספר הטלפון אינו תקף'
            ];
        }
        
        // Validate password strength
        if (!$this->isPasswordStrong($userData['password'])) {
            return [
                'success' => false,
                'message' => 'הסיסמה אינה עומדת בדרישות האבטחה. הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד'
            ];
        }
        
        // Hash password
        $hashedPassword = $this->auth->hashPassword($userData['password']);
        
        // Generate account verification token
        $emailToken = $this->auth->generateRandomToken(32);
        $tokenExpiry = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRY_HOURS * 3600));
        
        // Prepare user data for insertion
        $insertData = [
            'email' => $userData['email'],
            'password' => $hashedPassword,
            'id_number' => $userData['id_number'],
            'phone' => $userData['phone'],
            'birth_date' => $userData['birth_date'],
            'role' => $codeInfo['role'],
            'grade' => $codeInfo['role'] === 'student' ? $codeInfo['grade'] : ($userData['grade'] ?? null),
            'user_group' => $codeInfo['role'] === 'student' ? $codeInfo['user_group'] : ($userData['user_group'] ?? null),
            'is_active' => 0, // Default to inactive until email verification
            'email_verified' => 0 // Mark as not verified
        ];
        
        // Insert user into database
        $userId = $this->db->insert('users', $insertData);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'שגיאה ברישום המשתמש'
            ];
        }
        
        // Store email verification token
        $this->db->insert('email_verification_tokens', [
            'user_id' => $userId,
            'token' => $emailToken,
            'expires_at' => $tokenExpiry
        ]);
        
        // Send confirmation email
        $emailUtils = new EmailUtils();
        $emailSent = $emailUtils->sendAccountConfirmation($userData['email'], $userData['email'], $emailToken);
        
        // Mark registration code as used
        $this->auth->useRegistrationCode($registrationCode, $userId);
        
        return [
            'success' => true,
            'message' => 'הרישום בוצע בהצלחה. הודעת אימות נשלחה לדוא"ל שלך. אנא בדוק את תיבת הדואר הנכנס שלך ולחץ על הקישור לאימות החשבון.'
        ];
    }

    // Check if password meets security requirements
    private function isPasswordStrong($password) {
        // At least 8 characters
        if (strlen($password) < 8) {
            return false;
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Check for digit
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Check for special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    // Get user by ID
    public function getUserById($userId) {
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        if (!$user) {
            return false;
        }
        
        // Remove password for security
        unset($user['password']);
        
        return $user;
    }
    
    // Get all users with specific role
    public function getUsersByRole($role) {
        $users = $this->db->getRows(
            "SELECT * FROM users WHERE role = :role ORDER BY email",
            ['role' => $role]
        );
        
        // Remove passwords for security
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        return $users;
    }
    
    // Update user profile
    public function updateProfile($userId, $userData) {
        // Ensure user can only update their own profile unless they're admin
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['id'] != $userId && $currentUser['role'] !== 'admin') {
            return [
                'success' => false,
                'message' => 'אין לך הרשאה לעדכן פרופיל זה'
            ];
        }
        
        // Validate phone number if provided
        if (isset($userData['phone']) && !$this->auth->isValidPhoneNumber($userData['phone'])) {
            return [
                'success' => false,
                'message' => 'מספר הטלפון אינו תקף'
            ];
        }
        
        // Change password if provided
        if (!empty($userData['new_password'])) {
            // Verify current password
            $user = $this->db->getRow(
                "SELECT password FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!$this->auth->verifyPassword($userData['current_password'], $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'הסיסמה הנוכחית שגויה'
                ];
            }
            
            // Validate password strength
            if (!$this->isPasswordStrong($userData['new_password'])) {
                return [
                    'success' => false,
                    'message' => 'הסיסמה החדשה אינה עומדת בדרישות האבטחה'
                ];
            }
            
            // Hash new password
            $userData['password'] = $this->auth->hashPassword($userData['new_password']);
            
            // Remove these fields as they're not in the database
            unset($userData['current_password']);
            unset($userData['new_password']);
        }
        
        // Remove fields that shouldn't be updated
        $protectedFields = ['id', 'email', 'id_number', 'role'];
        foreach ($protectedFields as $field) {
            unset($userData[$field]);
        }
        
        // For students, grade and user_group should not be changeable
        $user = $this->getUserById($userId);
        if ($user['role'] === 'student') {
            unset($userData['grade']);
            unset($userData['user_group']);
        }
        
        // If no data to update
        if (empty($userData)) {
            return [
                'success' => true,
                'message' => 'אין שינויים לעדכן'
            ];
        }
        
        // Update user in database
        $updated = $this->db->update(
            'users',
            $userData,
            'id = :id',
            ['id' => $userId]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'שגיאה בעדכון הפרופיל'
            ];
        }
        
        // Update session if the current user is updating their own profile
        if ($currentUser['id'] == $userId) {
            $updatedUser = $this->getUserById($userId);
            foreach ($updatedUser as $key => $value) {
                $_SESSION['user'][$key] = $value;
            }
        }
        
        return [
            'success' => true,
            'message' => 'הפרופיל עודכן בהצלחה'
        ];
    }
    
    // Change user status (active/inactive)
    public function changeUserStatus($userId, $isActive) {
        // Only admin can change user status
        if (!$this->auth->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'אין לך הרשאה לשנות סטטוס משתמש'
            ];
        }
        
        $updated = $this->db->update(
            'users',
            ['is_active' => $isActive ? 1 : 0],
            'id = :id',
            ['id' => $userId]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'שגיאה בשינוי סטטוס המשתמש'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'סטטוס המשתמש שונה בהצלחה'
        ];
    }
    
    // Get all registration codes created by user
    public function getRegistrationCodes($userId = null) {
        $sql = "SELECT rc.*, u.email as created_by_email, u2.email as used_by_email 
                FROM registration_codes rc 
                LEFT JOIN users u ON rc.created_by = u.id 
                LEFT JOIN users u2 ON rc.used_by = u2.id";
        $params = [];
        
        if ($userId !== null) {
            $sql .= " WHERE rc.created_by = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY rc.created_at DESC";
        
        return $this->db->getRows($sql, $params);
    }
    
    // Get all groups available in the system
    public function getAllGroups() {
        $result = $this->db->getRows(
            "SELECT DISTINCT user_group FROM users WHERE user_group IS NOT NULL ORDER BY user_group"
        );
        
        $groups = [];
        foreach ($result as $row) {
            $groups[] = $row['user_group'];
        }
        
        return $groups;
    }
    
    // Get all grades available in the system
    public function getAllGrades() {
        $result = $this->db->getRows(
            "SELECT DISTINCT grade FROM users WHERE grade IS NOT NULL ORDER BY grade"
        );
        
        $grades = [];
        foreach ($result as $row) {
            $grades[] = $row['grade'];
        }
        
        return $grades;
    }
}