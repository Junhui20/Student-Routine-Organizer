<?php
require_once 'ErrorHandler.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PasswordResetHandler {
    private $pdo;
    private $errorHandler;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->errorHandler = ErrorHandler::getInstance();
    }

    /**
     * Generate and send password reset token
     */
    public function generateResetToken($email) {
        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT user_id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'If this email exists in our system, you will receive a password reset link.'];
            }

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

            // Clean up old tokens for this user
            $this->cleanupOldTokens($user['user_id']);

            // Store token in database
            $stmt = $this->pdo->prepare("
                INSERT INTO password_reset_tokens 
                (user_id, email, token_hash, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user['user_id'],
                $email,
                $tokenHash,
                $expiresAt,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            // Send email (in production, this would send an actual email)
            $resetLink = $this->generateResetLink($token);
            $emailSent = $this->sendResetEmail($email, $user['username'], $resetLink);

            if ($emailSent) {
                return [
                    'success' => true, 
                    'message' => 'If this email exists in our system, you will receive a password reset link.',
                    'debug_link' => $resetLink // For development only - remove in production
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to send reset email. Please try again later.'];
            }

        } catch (Exception $e) {
            ErrorHandler::logApplicationError($e->getMessage(), 'Password Reset', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'email' => $email ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
        }
    }

    /**
     * Validate reset token
     */
    public function validateResetToken($token) {
        try {
            $tokenHash = hash('sha256', $token);
            
            $stmt = $this->pdo->prepare("
                SELECT prt.*, u.username, u.email 
                FROM password_reset_tokens prt 
                JOIN users u ON prt.user_id = u.user_id 
                WHERE prt.token_hash = ? AND prt.expires_at > NOW() AND prt.used = FALSE
            ");
            
            $stmt->execute([$tokenHash]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                return ['valid' => false, 'message' => 'Invalid or expired reset token.'];
            }

            return [
                'valid' => true,
                'user_id' => $tokenData['user_id'],
                'email' => $tokenData['email'],
                'username' => $tokenData['username']
            ];

        } catch (Exception $e) {
            ErrorHandler::logApplicationError($e->getMessage(), 'Token Validation', [
                'token' => substr($token ?? '', 0, 10) . '...',
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ['valid' => false, 'message' => 'An error occurred while validating the token.'];
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Validate token first
            $validation = $this->validateResetToken($token);
            if (!$validation['valid']) {
                return $validation;
            }

            // Validate password strength
            if (!$this->validatePasswordStrength($newPassword)) {
                return [
                    'success' => false, 
                    'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.'
                ];
            }

            $tokenHash = hash('sha256', $token);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Start transaction
            $this->pdo->beginTransaction();

            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashedPassword, $validation['user_id']]);

            // Mark token as used
            $stmt = $this->pdo->prepare("
                UPDATE password_reset_tokens 
                SET used = TRUE, used_at = NOW() 
                WHERE token_hash = ?
            ");
            $stmt->execute([$tokenHash]);

            // Clean up old tokens for this user
            $this->cleanupOldTokens($validation['user_id']);

            $this->pdo->commit();

            return [
                'success' => true, 
                'message' => 'Password has been reset successfully. You can now log in with your new password.'
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            ErrorHandler::logApplicationError($e->getMessage(), 'Password Reset', [
                'user_id' => $validation['user_id'] ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ['success' => false, 'message' => 'An error occurred while resetting the password.'];
        }
    }

    /**
     * Validate password strength
     */
    private function validatePasswordStrength($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }

    /**
     * Clean up old tokens for a user
     */
    private function cleanupOldTokens($userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM password_reset_tokens 
                WHERE user_id = ? AND (expires_at < NOW() OR used = TRUE)
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Log but don't fail the main operation
            ErrorHandler::logApplicationError($e->getMessage(), 'Token Cleanup', [
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Generate reset link
     */
    private function generateResetLink($token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Get the base path by removing the current script path
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        // Extract the base path (e.g., /Student-Routine-Organizer)
        $basePath = '';
        if (strpos($requestUri, '/auth/') !== false) {
            $basePath = substr($requestUri, 0, strpos($requestUri, '/auth/'));
        } else {
            $basePath = dirname($scriptName);
        }

        $baseUrl = $protocol . '://' . $host . $basePath;
        return $baseUrl . '/auth/reset_password.php?token=' . urlencode($token);
    }

    /**
     * Send reset email using SMTP
     */
    private function sendResetEmail($email, $username, $resetLink) {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.mailersend.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'MS_5Mhkm1@test-q3enl6kvyx542vwr.mlsender.net';
            $mail->Password = 'mssp.cPtfhgE.pxkjn41qqyplz781.TCltvTn';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('MS_5Mhkm1@test-q3enl6kvyx542vwr.mlsender.net', 'Student Routine Organizer');
            $mail->addAddress($email, $username);
            $mail->addReplyTo('MS_5Mhkm1@test-q3enl6kvyx542vwr.mlsender.net', 'Student Routine Organizer');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Student Routine Organizer';

            $htmlMessage = "
            <html>
            <head>
                <title>Password Reset Request</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                    .button { background-color: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 20px 0; font-weight: bold; }
                    .link-box { background-color: #e9ecef; padding: 15px; border-radius: 4px; word-break: break-all; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
                    .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1 style='margin: 0; font-size: 24px;'>🔐 Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p>Hello <strong>{$username}</strong>,</p>
                        <p>You have requested to reset your password for your Student Routine Organizer account.</p>
                        <p>Click the button below to reset your password:</p>
                        <p style='text-align: center;'>
                            <a href='{$resetLink}' class='button'>Reset My Password</a>
                        </p>
                        <p>Or copy and paste this URL into your browser:</p>
                        <div class='link-box'>{$resetLink}</div>
                        <div class='warning'>
                            <strong>⚠️ Important:</strong> This link will expire in 1 hour for security reasons.
                        </div>
                        <p>If you did not request this password reset, please ignore this email. Your password will remain unchanged.</p>
                        <p>For security reasons, this email was sent from an automated system. Please do not reply to this email.</p>
                    </div>
                    <div class='footer'>
                        <p>Student Routine Organizer<br>
                        Helping students organize their academic life</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $textMessage = "
Password Reset Request - Student Routine Organizer

Hello {$username},

You have requested to reset your password for your Student Routine Organizer account.

Please visit the following link to reset your password:
{$resetLink}

This link will expire in 1 hour for security reasons.

If you did not request this password reset, please ignore this email. Your password will remain unchanged.

---
Student Routine Organizer
Helping students organize their academic life
            ";

            $mail->Body = $htmlMessage;
            $mail->AltBody = $textMessage;

            // Send the email
            $result = $mail->send();

            // Log successful email for development tracking
            $this->logEmailSent($email, $resetLink, true);

            return $result;

        } catch (Exception $e) {
            // Log the error
            ErrorHandler::logApplicationError($e->getMessage(), 'Email Sending', [
                'email' => $email,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Log failed email attempt with specific error handling
            $errorMessage = $e->getMessage();
            $this->logEmailSent($email, $resetLink, false, $errorMessage);

            // Check if it's a trial account limitation (check for common SMTP trial errors)
            if (strpos($errorMessage, 'Trial accounts can only send emails') !== false ||
                strpos($errorMessage, 'data not accepted') !== false) {
                // For trial accounts, we'll log the reset link for manual testing
                $this->logEmailSent($email, $resetLink, false, 'TRIAL_ACCOUNT_LIMITATION - Reset link logged for manual testing');
                return true; // Return true to show success message with debug link
            }

            return false;
        }
    }

    /**
     * Log email sending attempts for debugging
     */
    private function logEmailSent($email, $resetLink, $success, $error = null) {
        $logFile = __DIR__ . '/../logs/password_reset_emails.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $status = $success ? 'SUCCESS' : 'FAILED';
        $logEntry = "
=== PASSWORD RESET EMAIL {$status} ===
Date: " . date('Y-m-d H:i:s') . "
To: {$email}
Reset Link: {$resetLink}
Status: {$status}";

        if (!$success && $error) {
            $logEntry .= "
Error: {$error}";
        }

        $logEntry .= "
=============================

";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get reset attempt statistics (for monitoring)
     */
    public function getResetStats($email = null) {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN used = TRUE THEN 1 END) as successful_resets,
                    COUNT(CASE WHEN expires_at < NOW() AND used = FALSE THEN 1 END) as expired_tokens
                FROM password_reset_tokens
            ";
            
            if ($email) {
                $query .= " WHERE email = ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$email]);
            } else {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }
            
            return $stmt->fetch();
        } catch (Exception $e) {
            ErrorHandler::logApplicationError($e->getMessage(), 'Reset Stats', [
                'email' => $email ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }
}
?> 