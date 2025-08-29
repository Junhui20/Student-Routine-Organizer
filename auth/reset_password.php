<?php
require_once '../config/database.php';
require_once '../includes/SessionManager.php';
require_once '../includes/PasswordResetHandler.php';
require_once '../includes/ErrorHandler.php';

// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$sessionManager = new SessionManager($pdo);
$passwordResetHandler = new PasswordResetHandler($pdo);
$errorHandler = ErrorHandler::getInstance();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $sessionManager->validateSession()) {
    header('Location: ../index.php');
    exit();
}

// Get token from URL
$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$showForm = true;
$userInfo = null;

// Validate token first
if (empty($token)) {
    $message = 'Invalid or missing reset token.';
    $messageType = 'error';
    $showForm = false;
} else {
    $validation = $passwordResetHandler->validateResetToken($token);
    if (!$validation['valid']) {
        $message = $validation['message'];
        $messageType = 'error';
        $showForm = false;
    } else {
        $userInfo = $validation;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $result = $passwordResetHandler->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
            $showForm = false;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Routine Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .reset-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .reset-form .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .reset-form .icon.success {
            color: #28a745;
        }

        .reset-form .icon.error {
            color: #dc3545;
        }

        .reset-form .icon.reset {
            color: #667eea;
        }

        .reset-form h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .reset-form .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .user-info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: left;
        }

        .user-info strong {
            color: #1976d2;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .password-strength {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .strength-indicator {
            height: 4px;
            background-color: #e1e5e9;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }

        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #ffc107; width: 50%; }
        .strength-good { background-color: #17a2b8; width: 75%; }
        .strength-strong { background-color: #28a745; width: 100%; }

        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 10px;
            text-align: left;
        }

        .requirement {
            padding: 2px 0;
        }

        .requirement.met {
            color: #28a745;
        }

        .requirement.not-met {
            color: #dc3545;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 20px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            display: inline-block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .links a:hover {
            background-color: #f8f9fa;
            text-decoration: none;
        }

        .links a i {
            margin-right: 5px;
        }

        @media (max-width: 480px) {
            .reset-form {
                padding: 30px 20px;
                margin: 10px;
            }

            .reset-form h2 {
                font-size: 1.5rem;
            }

            .reset-form .icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-form">
            <?php if ($showForm): ?>
                <div class="icon reset">
                    <i class="fas fa-lock"></i>
                </div>
                
                <h2>Reset Password</h2>
                <p class="subtitle">
                    Create a new secure password for your account.
                </p>

                <?php if ($userInfo): ?>
                    <div class="user-info">
                        <strong>Account:</strong> <?php echo htmlspecialchars($userInfo['username']); ?> 
                        (<?php echo htmlspecialchars($userInfo['email']); ?>)
                    </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="message <?php echo htmlspecialchars($messageType); ?>">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your new password"
                            required
                            autocomplete="new-password"
                        >
                        <div class="password-strength">
                            <div class="strength-indicator">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div id="strengthText">Password strength: Weak</div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement not-met" id="req-length">
                                <i class="fas fa-times"></i> At least 8 characters
                            </div>
                            <div class="requirement not-met" id="req-uppercase">
                                <i class="fas fa-times"></i> At least one uppercase letter
                            </div>
                            <div class="requirement not-met" id="req-lowercase">
                                <i class="fas fa-times"></i> At least one lowercase letter
                            </div>
                            <div class="requirement not-met" id="req-number">
                                <i class="fas fa-times"></i> At least one number
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Confirm your new password"
                            required
                            autocomplete="new-password"
                        >
                        <div id="passwordMatch" style="margin-top: 5px; font-size: 0.9rem;"></div>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn" disabled>
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>

            <?php elseif ($messageType === 'success'): ?>
                <div class="icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2>Password Reset Successful!</h2>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php else: ?>
                <div class="icon error">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h2>Reset Link Invalid</h2>
                <div class="message error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="links">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Back to Login
                </a>
                <?php if (!$showForm && $messageType !== 'success'): ?>
                    <a href="forgot_password.php">
                        <i class="fas fa-key"></i> Request New Reset
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const submitBtn = document.getElementById('submitBtn');
        const passwordMatch = document.getElementById('passwordMatch');

        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = [];

            // Length check
            const lengthReq = document.getElementById('req-length');
            if (password.length >= 8) {
                score += 25;
                lengthReq.className = 'requirement met';
                lengthReq.innerHTML = '<i class="fas fa-check"></i> At least 8 characters';
            } else {
                lengthReq.className = 'requirement not-met';
                lengthReq.innerHTML = '<i class="fas fa-times"></i> At least 8 characters';
            }

            // Uppercase check
            const uppercaseReq = document.getElementById('req-uppercase');
            if (/[A-Z]/.test(password)) {
                score += 25;
                uppercaseReq.className = 'requirement met';
                uppercaseReq.innerHTML = '<i class="fas fa-check"></i> At least one uppercase letter';
            } else {
                uppercaseReq.className = 'requirement not-met';
                uppercaseReq.innerHTML = '<i class="fas fa-times"></i> At least one uppercase letter';
            }

            // Lowercase check
            const lowercaseReq = document.getElementById('req-lowercase');
            if (/[a-z]/.test(password)) {
                score += 25;
                lowercaseReq.className = 'requirement met';
                lowercaseReq.innerHTML = '<i class="fas fa-check"></i> At least one lowercase letter';
            } else {
                lowercaseReq.className = 'requirement not-met';
                lowercaseReq.innerHTML = '<i class="fas fa-times"></i> At least one lowercase letter';
            }

            // Number check
            const numberReq = document.getElementById('req-number');
            if (/[0-9]/.test(password)) {
                score += 25;
                numberReq.className = 'requirement met';
                numberReq.innerHTML = '<i class="fas fa-check"></i> At least one number';
            } else {
                numberReq.className = 'requirement not-met';
                numberReq.innerHTML = '<i class="fas fa-times"></i> At least one number';
            }

            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (score <= 25) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Password strength: Weak';
            } else if (score <= 50) {
                strengthBar.classList.add('strength-fair');
                strengthText.textContent = 'Password strength: Fair';
            } else if (score <= 75) {
                strengthBar.classList.add('strength-good');
                strengthText.textContent = 'Password strength: Good';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Password strength: Strong';
            }

            return score === 100;
        }

        // Check password match
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmInput.value;

            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                return false;
            }

            if (password === confirmPassword) {
                passwordMatch.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check"></i> Passwords match</span>';
                return true;
            } else {
                passwordMatch.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times"></i> Passwords do not match</span>';
                return false;
            }
        }

        // Enable/disable submit button
        function updateSubmitButton() {
            const strongPassword = checkPasswordStrength(passwordInput.value);
            const passwordsMatch = checkPasswordMatch();
            
            submitBtn.disabled = !(strongPassword && passwordsMatch && passwordInput.value.length > 0);
        }

        // Event listeners
        if (passwordInput) {
            passwordInput.addEventListener('input', updateSubmitButton);
            passwordInput.focus();
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', updateSubmitButton);
        }

        // Form submission with loading state
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const button = document.getElementById('submitBtn');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            button.disabled = true;
        });
    </script>
</body>
</html> 