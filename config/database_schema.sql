-- Database schema
CREATE DATABASE IF NOT EXISTS secure_login_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE secure_login_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    id_number VARCHAR(50) NOT NULL UNIQUE, -- National ID
    phone VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    grade VARCHAR(50), -- Grade level (שכבת לימוד)
    user_group VARCHAR(100), -- Group (קבוצה)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    account_locked BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0
);

-- Registration codes table
CREATE TABLE IF NOT EXISTS registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL UNIQUE,
    role ENUM('teacher', 'student') NOT NULL,
    grade VARCHAR(50), -- Optional, for students
    user_group VARCHAR(100), -- Optional, for students
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Login attempts log (for security monitoring)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE
);

-- AutoCheck C# Assignment Testing System - Database Extensions

-- Assignments table
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    due_date DATETIME NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    grade VARCHAR(50) NOT NULL,        -- שכבת לימוד
    user_group VARCHAR(100) NOT NULL,  -- קבוצה
    max_attempts INT DEFAULT 5,        -- מספר נסיונות הגשה מותרים
    base_code TEXT,                    -- קוד התחלתי (אופציונלי)
    max_score INT DEFAULT 100,         -- ניקוד מקסימלי
    show_test_results BOOLEAN DEFAULT TRUE, -- האם להציג לתלמיד את תוצאות הבדיקות המפורטות
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Submission Tests table
CREATE TABLE IF NOT EXISTS submission_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    test_name VARCHAR(255) NOT NULL,
    test_type ENUM('compilation', 'execution', 'output', 'unit_test') NOT NULL,
    test_order INT NOT NULL DEFAULT 0,
    points INT NOT NULL DEFAULT 10,     -- ניקוד לבדיקה זו
    input_data TEXT,                    -- קלט לבדיקה
    expected_output TEXT,               -- פלט צפוי
    timeout_seconds INT DEFAULT 5,      -- הגבלת זמן לריצה בשניות
    memory_limit_mb INT DEFAULT 256,    -- הגבלת זיכרון במגה-בייטים
    custom_test_code TEXT,              -- קוד בדיקה מותאם אישית (למשל, יחידות בדיקה)
    is_hidden BOOLEAN DEFAULT FALSE,    -- האם להסתיר מהתלמיד
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Submissions table
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_code TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_score INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
    error_message TEXT,
    attempt_number INT NOT NULL DEFAULT 1,
    time_spent_seconds INT,              -- זמן שנדרש לסטודנט להגשה
    execution_time_ms INT,               -- זמן ריצה בפועל במילישניות
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Test Results table
CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    test_id INT NOT NULL,
    passed BOOLEAN DEFAULT FALSE,
    score INT DEFAULT 0,
    execution_time_ms INT,              -- זמן ריצה במילישניות
    memory_used_mb FLOAT,               -- זיכרון בשימוש במגה-בייטים
    output TEXT,                        -- פלט בפועל
    error_message TEXT,                 -- הודעת שגיאה אם קיימת
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES submission_tests(id)
);

-- Indexes for performance
CREATE INDEX idx_assignments_grade_group ON assignments(grade, user_group);
CREATE INDEX idx_submissions_student ON submissions(student_id);
CREATE INDEX idx_submissions_assignment ON submissions(assignment_id);
CREATE INDEX idx_test_results_submission ON test_results(submission_id);

-- Insert initial admin
INSERT INTO users (
    email, 
    password, 
    id_number, 
    phone, 
    birth_date, 
    role, 
    grade, 
    user_group, 
    created_at
) VALUES (
    'admin@school.com', 
    '$2y$10$8mnOFRtQVJIVfZ5jSSTkT.ZhgCaOK.V5i7oNxqFUOr8xV5f/JRHMW', -- password: Admin123!
    '000000000', 
    '0501234567', 
    '1980-01-01', 
    'admin', 
    NULL, 
    NULL, 
    NOW()
) ON DUPLICATE KEY UPDATE id=id;