<?php
// email_utils.php - Email sending utilities
require_once 'db.php';
require_once 'C:\xampp\htdocs\autocheck\config\config.php';

// Require PHPMailer
require_once 'C:\xampp\htdocs\autocheck\vendor\autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailUtils {
    private $db;
    private $mail;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // PHPMailer setup with Gmail SMTP
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = EMAIL_USERNAME; // From config.php
        $this->mail->Password = EMAIL_PASSWORD; // From config.php
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);
        $this->mail->setFrom(EMAIL_USERNAME, SITE_NAME);
    }
    
    /**
     * Send account confirmation email
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Confirmation token
     * @return bool True if email was sent successfully
     */
    public function sendAccountConfirmation($email, $name, $token) {
        try {
            $confirmUrl = SITE_URL . 'pages/confirm_account.php?token=' . $token;
            
            $this->mail->addAddress($email, $name);
            $this->mail->Subject = 'אימות חשבון במערכת ' . SITE_NAME;
            
            // Beautiful HTML email template
            $body = $this->getEmailTemplate('account_confirmation', [
                'name' => $name,
                'confirm_url' => $confirmUrl,
                'site_name' => SITE_NAME,
                'expiry_hours' => TOKEN_EXPIRY_HOURS
            ]);
            
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Failed to send confirmation email: ' . $e->getMessage());
            return false;
        } finally {
            $this->mail->clearAddresses();
        }
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Reset token
     * @return bool True if email was sent successfully
     */
    public function sendPasswordReset($email, $name, $token) {
        try {
            $resetUrl = SITE_URL . 'reset_password.php?token=' . $token;
            
            $this->mail->addAddress($email, $name);
            $this->mail->Subject = 'איפוס סיסמה במערכת ' . SITE_NAME;
            
            // Beautiful HTML email template
            $body = $this->getEmailTemplate('password_reset', [
                'name' => $name,
                'reset_url' => $resetUrl,
                'site_name' => SITE_NAME,
                'expiry_hours' => TOKEN_EXPIRY_HOURS
            ]);
            
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Failed to send password reset email: ' . $e->getMessage());
            return false;
        } finally {
            $this->mail->clearAddresses();
        }
    }
    
    /**
     * Get HTML email template with variables replaced
     * 
     * @param string $templateName Template name
     * @param array $vars Variables to replace in template
     * @return string HTML email content
     */
    private function getEmailTemplate($templateName, $vars = []) {
        $templatePath = __DIR__ . '/../templates/emails/' . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            return $this->getDefaultTemplate($templateName, $vars);
        }
        
        $template = file_get_contents($templatePath);
        
        // Replace variables
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }
    /**
     * Get default email template if custom template doesn't exist
     * 
     * @param string $templateName Template name
     * @param array $vars Variables to replace in template
     * @return string HTML email content
     */
    private function getDefaultTemplate($templateName, $vars) {
        if ($templateName === 'account_confirmation') {
            return '
            <!DOCTYPE html>
            <html dir="rtl" lang="he">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                        border-radius: 8px;
                    }
                    .header {
                        background-color: #4a90e2;
                        color: white;
                        padding: 15px;
                        text-align: center;
                        border-radius: 6px 6px 0 0;
                    }
                    .content {
                        padding: 20px;
                        background-color: #f9f9f9;
                    }
                    .button {
                        display: inline-block;
                        background-color: #4CAF50;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 4px;
                        margin: 20px 0;
                        font-weight: bold;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        font-size: 12px;
                        color: #777;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>' . $vars['site_name'] . '</h1>
                    </div>
                    <div class="content">
                        <h2>שלום ' . $vars['name'] . ',</h2>
                        <p>תודה על הרשמתך למערכת ' . $vars['site_name'] . '!</p>
                        <p>כדי להשלים את תהליך ההרשמה ולאמת את כתובת האימייל שלך, אנא לחץ על הכפתור למטה:</p>
                        <div style="text-align: center;">
                            <a href="' . $vars['confirm_url'] . '" class="button">אמת את החשבון שלי</a>
                        </div>
                        <p>אם לא נרשמת למערכת, אנא התעלם מהודעה זו.</p>
                        <p>קישור זה יפוג בתוך ' . $vars['expiry_hours'] . ' שעות.</p>
                    </div>
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' ' . $vars['site_name'] . '. כל הזכויות שמורות.</p>
                    </div>
                </div>
            </body>
            </html>';
        } else if ($templateName === 'password_reset') {
            return '
            <!DOCTYPE html>
            <html dir="rtl" lang="he">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                        border-radius: 8px;
                    }
                    .header {
                        background-color: #4a90e2;
                        color: white;
                        padding: 15px;
                        text-align: center;
                        border-radius: 6px 6px 0 0;
                    }
                    .content {
                        padding: 20px;
                        background-color: #f9f9f9;
                    }
                    .button {
                        display: inline-block;
                        background-color: #ff9800;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 4px;
                        margin: 20px 0;
                        font-weight: bold;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        font-size: 12px;
                        color: #777;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>' . $vars['site_name'] . '</h1>
                    </div>
                    <div class="content">
                        <h2>שלום ' . $vars['name'] . ',</h2>
                        <p>קיבלנו בקשה לאיפוס הסיסמה שלך במערכת ' . $vars['site_name'] . '.</p>
                        <p>לאיפוס הסיסמה שלך, אנא לחץ על הכפתור למטה:</p>
                        <div style="text-align: center;">
                            <a href="' . $vars['reset_url'] . '" class="button">איפוס סיסמה</a>
                        </div>
                        <p>אם לא ביקשת לאפס את הסיסמה שלך, אנא התעלם מהודעה זו.</p>
                        <p>קישור זה יפוג בתוך ' . $vars['expiry_hours'] . ' שעות.</p>
                    </div>
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' ' . $vars['site_name'] . '. כל הזכויות שמורות.</p>
                    </div>
                </div>
            </body>
            </html>';
        }
        
        return '';
    }
}