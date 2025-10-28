<?php
// auth.php - Authentication and security functions (Updated with email verification)
require_once 'db.php';
require_once 'email_utils.php';

class Auth {
    private $db;
    private $emailUtils;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->emailUtils = new EmailUtils();
    }
    
    // Create a secure password hash
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    }
    
    // Verify password against hash
    public function verifyPassword($password, $hash) {
        // Add detailed logging
        $result = password_verify($password, $hash);
        
        error_log("Password verification:");
        error_log("Input password: $password");
        error_log("Stored hash: $hash");
        error_log("Verification result: " . ($result ? 'TRUE' : 'FALSE'));
        
        return $result;
    }    
    
    // Generate a CSRF token
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Generate a secure random token
    public function generateRandomToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    // Check if email is valid
    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Check if Israeli ID number is valid (using the check digit verification)
    public function isValidIdNumber($id) {
        // Remove any non-digit characters
        $id = preg_replace('/\D/', '', $id);
        
        // Check if ID is 9 digits
        if (strlen($id) != 9) {
            return false;
        }
        
        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $val = (int)$id[$i] * (($i % 2) + 1);
            $sum += $val > 9 ? $val - 9 : $val;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        // Verify check digit
        return $checkDigit == (int)$id[8];
    }
    
    // Check if phone number is valid (Israeli format)
    public function isValidPhoneNumber($phone) {
        return preg_match('/^(0[2-9]\d{7}|05\d{8})$/', preg_replace('/\D/', '', $phone));
    }
    
    // Sanitize input
    public function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value);
            }
        } else {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
    
    // Start secure session
    public function startSecureSession() {
        // Set secure session parameters
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        // Set session name
        session_name(SESSION_NAME);
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => SESSION_LIFETIME,
                'gc_maxlifetime' => SESSION_LIFETIME
            ]);
        }
        
        // Regenerate session ID occasionally to prevent session fixation
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSessionId();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            $this->regenerateSessionId();
        }
    }
    
    // Regenerate session ID
    public function regenerateSessionId() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Create email verification token for user
    public function createEmailVerificationToken($userId) {
        $token = $this->generateRandomToken(32);
        $expiry = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRY_HOURS * 3600));
        
        // Save token to database
        $inserted = $this->db->insert('email_verification_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiry
        ]);
        
        return $inserted ? $token : false;
    }
    
    // Verify email token
    public function verifyEmailToken($token) {
        // Sanitize token
        $token = $this->sanitizeInput($token);
        
        // Check if token exists and is not expired
        $tokenData = $this->db->getRow(
            "SELECT * FROM email_verification_tokens WHERE token = :token AND expires_at > NOW()",
            ['token' => $token]
        );
        
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'קישור האימות אינו תקף או שפג תוקפו'
            ];
        }
        
        // Get user
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE id = :user_id",
            ['user_id' => $tokenData['user_id']]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'לא נמצא משתמש מקושר לטוקן זה'
            ];
        }
        
        // Check if user is already verified
        if ($user['email_verified'] == 1) {
            return [
                'success' => true,
                'message' => 'כתובת האימייל שלך כבר אומתה. תוכל להתחבר למערכת.'
            ];
        }
        
        // Update user
        $updated = $this->db->update(
            'users',
            [
                'email_verified' => 1,
                'is_active' => 1
            ],
            'id = :id',
            ['id' => $user['id']]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'שגיאה באימות כתובת האימייל'
            ];
        }
        
        // Delete token
        $this->db->delete(
            'email_verification_tokens',
            'user_id = :user_id',
            ['user_id' => $user['id']]
        );
        
        return [
            'success' => true,
            'message' => 'כתובת האימייל אומתה בהצלחה. כעת תוכל להתחבר למערכת.'
        ];
    }
    
    // Check if reset token is valid
    public function isValidResetToken($token) {
        $tokenInfo = $this->db->getRow(
            "SELECT * FROM password_reset_tokens 
             WHERE token = :token AND expires_at > NOW()",
            ['token' => $token]
        );
        
        return $tokenInfo !== false;
    }
    
    // Reset password with token
    public function resetPassword($token, $newPassword) {
        $tokenInfo = $this->db->getRow(
            "SELECT * FROM password_reset_tokens 
             WHERE token = :token AND expires_at > NOW()",
            ['token' => $token]
        );
        
        if (!$tokenInfo) {
            return [
                'success' => false,
                'message' => 'קישור איפוס הסיסמה אינו תקף או שפג תוקפו'
            ];
        }
        
        // Hash new password
        $passwordHash = $this->hashPassword($newPassword);
        
        // Update user password
        $updated = $this->db->update(
            'users',
            ['password' => $passwordHash],
            'id = :id',
            ['id' => $tokenInfo['user_id']]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'שגיאה בעדכון הסיסמה'
            ];
        }
        
        // Delete used token
        $this->db->delete(
            'password_reset_tokens',
            'token = :token',
            ['token' => $token]
        );
        
        return [
            'success' => true,
            'message' => 'הסיסמה עודכנה בהצלחה! כעת תוכל להתחבר עם הסיסמה החדשה.'
        ];
    }
    
    // Perform login
    public function login($email, $password) {
        // Get all users with this email (for debugging)
        $allUsers = $this->db->getRows(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        // Debug logging
        error_log("Login attempt for email: $email");
        error_log("Total users found: " . count($allUsers));
        
        // Detailed user information logging
        foreach ($allUsers as $userInfo) {
            error_log("User details: " . print_r([
                'id' => $userInfo['id'],
                'email' => $userInfo['email'],
                'is_active' => $userInfo['is_active'],
                'role' => $userInfo['role']
            ], true));
        }
        
        // Get user from database with active status
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE email = :email AND is_active = 1",
            ['email' => $email]
        );
        
        // Log login attempt
        $this->logLoginAttempt($email, $user ? true : false);
        
        // If user doesn't exist or password is incorrect
        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            // If user exists but password is wrong, increment failed attempts
            if ($user) {
                $this->db->update(
                    'users',
                    [
                        'failed_login_attempts' => $user['failed_login_attempts'] + 1,
                        'last_login' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    ['id' => $user['id']]
                );
            }
            
            return [
                'success' => false,
                'message' => 'אימייל או סיסמה שגויים'
            ];
        }
        
        // Check if email is verified
        if (!$user['email_verified']) {
            return [
                'success' => false,
                'message' => 'אנא אמת את כתובת האימייל שלך לפני התחברות. בדוק את תיבת הדואר שלך.'
            ];
        }
        
        // Reset failed login attempts
        $this->db->update(
            'users',
            [
                'failed_login_attempts' => 0,
                'last_login' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $user['id']]
        );
        
        // Remove password from session data
        unset($user['password']);
        
        // Set user session
        $_SESSION['user'] = $user;
        $_SESSION['is_logged_in'] = true;
        
        return [
            'success' => true,
            'user' => $user
        ];
    }    

    // Log login attempt for security monitoring
    private function logLoginAttempt($email, $success) {
        $this->db->insert('login_attempts', [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'success' => $success ? 1 : 0
        ]);
    }
    
    // Logout user
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }
    
    // Get current user
    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    // Check if current user has required role
    public function hasRole($requiredRoles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        
        if (is_array($requiredRoles)) {
            return in_array($user['role'], $requiredRoles);
        } else {
            return $user['role'] === $requiredRoles;
        }
    }
    
    // Check if registration code is valid
    public function isValidRegistrationCode($code) {
        $codeInfo = $this->db->getRow(
            "SELECT * FROM registration_codes 
             WHERE code = :code AND used = 0 AND expires_at > NOW()",
            ['code' => $code]
        );
        
        return $codeInfo !== false ? $codeInfo : false;
    }
    
    // Generate a registration code
    public function generateRegistrationCode($role, $grade = null, $group = null) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        
        // Check if user has permission to generate code for this role
        if (($role === 'teacher' && $user['role'] !== 'admin') || 
            ($role === 'student' && !in_array($user['role'], ['admin', 'teacher']))) {
            return [
                'success' => false,
                'message' => 'אין לך הרשאה ליצור קוד הרשמה לתפקיד זה'
            ];
        }
        
        // For student registration codes, require grade and group
        if ($role === 'student' && (empty($grade) || empty($group))) {
            return [
                'success' => false,
                'message' => 'חובה לציין שכבה וקבוצה עבור קוד הרשמה לתלמיד'
            ];
        }
        
        // Generate code
        $code = $this->generateRandomToken(16);
        
        // Calculate expiry date
        $expiryDate = date('Y-m-d H:i:s', time() + REGISTRATION_CODE_EXPIRY);
        
        // Insert code into database
        $codeId = $this->db->insert('registration_codes', [
            'code' => $code,
            'role' => $role,
            'grade' => $grade,
            'user_group' => $group,
            'created_by' => $user['id'],
            'expires_at' => $expiryDate
        ]);
        
        if (!$codeId) {
            return [
                'success' => false,
                'message' => 'שגיאה ביצירת קוד ההרשמה'
            ];
        }
        
        return [
            'success' => true,
            'code' => $code,
            'expires_at' => $expiryDate
        ];
    }
    
    // Mark registration code as used
    public function useRegistrationCode($code, $userId) {
        return $this->db->update(
            'registration_codes',
            [
                'used' => 1,
                'used_by' => $userId
            ],
            'code = :code',
            ['code' => $code]
        );
    }
}