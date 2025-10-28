<?php
// login.php - Modern clean design
require_once 'C:\xampp\htdocs\autocheck\core\init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$auth->verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'חלה שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.';
    } else {
        // Get form data
        $email = $auth->sanitizeInput($_POST['email']);
        $password = $_POST['password']; // Don't sanitize password
        
        // Attempt login
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
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
    <title>התחברות | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/script.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <nav>
                <ul>
                    <li><a href="index.php">ראשי</a></li>
                    <li><a href="about.php">אודות</a></li>
                    <li><a href="contact.php">צור קשר</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>ברוך שובך!</h1>
                <p>התחבר לחשבון שלך כדי להמשיך</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="message error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="message success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="email">כתובת אימייל</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">סיסמה</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="הזן את הסיסמה שלך">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">התחבר</button>
                    </div>
                </form>
                
                <div class="auth-links">
                    <a href="register.php">אין לך חשבון? הירשם עכשיו</a>
                    <br>
                    <a href="forgot-password.php">שכחת סיסמה?</a>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. כל הזכויות שמורות.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create and inject loader elements
            const loaderContainer = document.createElement('div');
            loaderContainer.className = 'loader-container';
            
            const wordElement = document.createElement('div');
            wordElement.className = 'word';
            wordElement.textContent = 'AUTOCHECK';
            loaderContainer.appendChild(wordElement);
            
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            loaderContainer.appendChild(overlay);
            
            document.body.prepend(loaderContainer);
            
            // Initialize lettering (split text into spans)
            function lettering(elem) {
                const text = elem.textContent;
                elem.textContent = '';
                
                for (let i = 0; i < text.length; i++) {
                const span = document.createElement('span');
                span.textContent = text[i];
                span.setAttribute('data-orig', text[i]);
                span.textContent = '-';
                elem.appendChild(span);
                }
                return elem.querySelectorAll('span');
            }
            
            // Ticker class implementation
            class Ticker {
                constructor(elem) {
                this.done = false;
                this.cycleCount = 6;
                this.cycleCurrent = 0;
                this.chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-_=+{}|[]\\;\':"<>?,./`~'.split('');
                this.charsCount = this.chars.length;
                this.letters = lettering(elem);
                this.letterCount = this.letters.length;
                this.letterCurrent = 0;
                }
                
                getChar() {
                return this.chars[Math.floor(Math.random() * this.charsCount)];
                }
                
                reset() {
                this.done = false;
                this.cycleCurrent = 0;
                this.letterCurrent = 0;
                
                for (let i = 0; i < this.letters.length; i++) {
                    const letter = this.letters[i];
                    letter.textContent = letter.getAttribute('data-orig');
                    letter.classList.remove('done');
                }
                
                this.loop();
                return this;
                }
                
                loop() {
                for (let i = 0; i < this.letters.length; i++) {
                    const letter = this.letters[i];
                    if (i >= this.letterCurrent) {
                    if (letter.textContent !== ' ') {
                        letter.textContent = this.getChar();
                        letter.style.opacity = Math.random();
                    }
                    }
                }
                
                if (this.cycleCurrent < this.cycleCount) {
                    this.cycleCurrent++;
                    requestAnimationFrame(() => this.loop());
                } else if (this.letterCurrent < this.letterCount) {
                    const currLetter = this.letters[this.letterCurrent];
                    this.cycleCurrent = 0;
                    currLetter.textContent = currLetter.getAttribute('data-orig');
                    currLetter.style.opacity = 1;
                    currLetter.classList.add('done');
                    this.letterCurrent++;
                    requestAnimationFrame(() => this.loop());
                } else {
                    this.done = true;
                    
                    if (!this.done) {
                    requestAnimationFrame(() => this.loop());
                    }
                }
                }
            }
            
            // Initialize ticker
            const wordEl = document.querySelector('.word');
            const ticker = new Ticker(wordEl);
            ticker.reset();
            
            // Hide loader after 1.2 seconds
            setTimeout(function() {
                loaderContainer.classList.add('hidden');
                setTimeout(() => {
                loaderContainer.remove();
                }, 900); // Remove after fade out
            }, 2100);
            });
    </script>

</body>
</html>