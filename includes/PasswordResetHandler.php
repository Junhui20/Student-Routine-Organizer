<?php
require_once 'ErrorHandler.php';

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
            $this->errorHandler->logError($e, 'Password Reset', $_SESSION['user_id'] ?? null);
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
            $this->errorHandler->logError($e, 'Token Validation', null);
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
            $this->errorHandler->logError($e, 'Password Reset', $validation['user_id'] ?? null);
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
            $this->errorHandler->logError($e, 'Token Cleanup', $userId);
        }
    }

    /**
     * Generate reset link
     */
    private function generateResetLink($token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . dirname($_SERVER['REQUEST_URI']);
        return $baseUrl . '/auth/reset_password.php?token=' . urlencode($token);
    }

    /**
     * Send reset email (mock implementation for development)
     */
    private function sendResetEmail($email, $username, $resetLink) {
        // In production, implement actual email sending using PHPMailer or similar
        // For now, we'll simulate email sending and log for development
        
        $subject = "Password Reset Request - Student Routine Organizer";
        $message = "
        <html>
        <head>
            <title>Password Reset Request</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #007bff;'>Password Reset Request</h2>
                <p>Hello <strong>{$username}</strong>,</p>
                <p>You have requested to reset your password for your Student Routine Organizer account.</p>
                <p>Click the link below to reset your password:</p>
                <p style='margin: 20px 0;'>
                    <a href='{$resetLink}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;'>Reset Password</a>
                </p>
                <p>Or copy and paste this URL into your browser:</p>
                <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px;'>{$resetLink}</p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #666;'>Student Routine Organizer - Diary Journal Module</p>
            </div>
        </body>
        </html>
        ";

        // For development: Log email content to file
        $logFile = __DIR__ . '/../logs/password_reset_emails.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = "
=== PASSWORD RESET EMAIL ===
Date: " . date('Y-m-d H:i:s') . "
To: {$email}
Subject: {$subject}
Reset Link: {$resetLink}
=============================

";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // TODO: Implement actual email sending
        // Example with PHPMailer:
        /*
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('noreply@yourdomain.com', 'Student Routine Organizer');
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        return $mail->send();
        */

        // For development, always return true
        return true;
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
            $this->errorHandler->logError($e, 'Reset Stats', null);
            return null;
        }
    }
}
?> 