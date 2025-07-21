# Password Reset Functionality Setup

## Overview
Complete forgot password and password reset functionality has been implemented for the Student Routine Organizer diary journal application.

## Database Setup

### New Table Added: `password_reset_tokens`
```sql
CREATE TABLE password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_reset (user_id),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires (expires_at),
    INDEX idx_email (email)
);
```

**To apply this change:**
1. Run the updated `database_schema.sql` file
2. Or execute the CREATE TABLE statement manually in your MySQL database

## Files Added/Modified

### New Files:
1. **`includes/PasswordResetHandler.php`** - Core password reset logic
2. **`auth/forgot_password.php`** - Forgot password form page
3. **`auth/reset_password.php`** - Password reset form page

### Modified Files:
1. **`database_schema.sql`** - Added password_reset_tokens table
2. **`auth/login.php`** - Added "Forgot your password?" link

## Features Implemented

### Security Features:
- **Secure Token Generation**: 64-character random tokens hashed with SHA-256
- **Token Expiration**: Tokens expire after 1 hour
- **One-Time Use**: Tokens are marked as used after successful password reset
- **Password Strength Validation**: Enforces strong password requirements:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
- **Automatic Cleanup**: Old and expired tokens are automatically cleaned up
- **Rate Limiting**: Built-in protection against token flooding

### User Experience:
- **Professional UI**: Modern, responsive design matching the application theme
- **Real-time Password Validation**: Visual feedback for password strength
- **Password Match Checking**: Instant verification that passwords match
- **Loading States**: Visual feedback during form submission
- **Clear Error Messages**: User-friendly error handling
- **Account Information Display**: Shows username/email for verification

## How to Test

### Step 1: Ensure Database is Set Up
1. Make sure the `password_reset_tokens` table exists in your database
2. Ensure you have at least one user account registered

### Step 2: Test Forgot Password Flow
1. Go to `auth/login.php`
2. Click "Forgot your password?" link
3. Enter a registered email address
4. Click "Send Reset Link"

### Step 3: Development Mode - Access Reset Link
In development mode, the reset link is displayed on screen for testing:
- Copy the displayed reset link
- Open it in a new tab/window
- The link will auto-expire after 30 seconds for security

### Step 4: Reset Password
1. On the reset password page, enter a new password
2. The page provides real-time feedback on password strength
3. Confirm the password matches
4. Submit the form
5. You should see a success message

### Step 5: Verify Reset
1. Go back to login page
2. Try logging in with the new password
3. Confirm the old password no longer works

## Email Configuration (Production)

For production use, you'll need to configure actual email sending:

### Option 1: PHPMailer (Recommended)
```bash
composer require phpmailer/phpmailer
```

Then uncomment and configure the PHPMailer section in `PasswordResetHandler.php`:

```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com'; // Your SMTP server
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
```

### Option 2: System Mail Function
For basic setups, you can use PHP's `mail()` function:

```php
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: Student Routine Organizer <noreply@yourdomain.com>' . "\r\n";

return mail($email, $subject, $message, $headers);
```

## Development Email Logging

During development, all password reset emails are logged to:
- **File**: `logs/password_reset_emails.log`
- Contains: Date, recipient email, subject, and reset link
- **Purpose**: Testing and debugging without actual email sending

## Security Considerations

### Production Checklist:
1. **Remove Debug Links**: Remove the `debug_link` from production responses
2. **Configure HTTPS**: Ensure password reset links use HTTPS
3. **Email Rate Limiting**: Implement rate limiting for password reset requests
4. **IP Monitoring**: Monitor for suspicious password reset patterns
5. **Token Cleanup**: Set up automatic cleanup of expired tokens
6. **Logging**: Monitor password reset attempts and failures

### Best Practices:
- Never expose tokens in URLs logs
- Use secure SMTP connections for email sending
- Implement CAPTCHA for high-volume sites
- Consider two-factor authentication for sensitive accounts
- Regular security audits of reset functionality

## Integration with Existing System

The password reset functionality integrates seamlessly with existing components:

- **SessionManager**: Validates user sessions and prevents logged-in users from accessing reset pages
- **ErrorHandler**: Logs all password reset errors and exceptions
- **CookieManager**: Compatible with "Remember Me" functionality
- **Database**: Uses existing database connection and follows the same patterns

## Troubleshooting

### Common Issues:

1. **Token Not Found Error**
   - Check if the database table exists
   - Verify the token hasn't expired (1 hour limit)
   - Ensure the token hasn't been used already

2. **Email Not Sending** (Development)
   - Check `logs/password_reset_emails.log` for logged emails
   - Verify database connection is working
   - Confirm user email exists in the database

3. **Password Reset Fails**
   - Check password meets strength requirements
   - Verify token is still valid and unused
   - Check error logs for database issues

4. **Access Denied**
   - Ensure proper file permissions on the `logs/` directory
   - Check that all required include files exist

## Testing Checklist

- [ ] Database table created successfully
- [ ] Can access forgot password page from login
- [ ] Email validation works (valid/invalid emails)
- [ ] Reset token generates successfully
- [ ] Reset link appears in development mode
- [ ] Reset password page loads with valid token
- [ ] Invalid/expired tokens show appropriate errors
- [ ] Password strength validation works in real-time
- [ ] Password confirmation matching works
- [ ] Password reset completes successfully
- [ ] Can login with new password
- [ ] Old password no longer works
- [ ] Used tokens cannot be reused
- [ ] Expired tokens cannot be used

## University Course Topics Demonstrated

This implementation demonstrates several advanced PHP concepts:

1. **Security**: Token-based authentication, password hashing, SQL injection prevention
2. **Database Design**: Proper indexing, foreign keys, data relationships
3. **Session Management**: Integration with existing session security
4. **Error Handling**: Comprehensive error logging and user feedback
5. **User Experience**: Real-time validation, progressive enhancement
6. **Email Systems**: SMTP configuration and HTML email templates
7. **File Handling**: Logging systems and directory management

The forgot password functionality is now fully integrated and ready for demonstration in your university project. 