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
$errorHandler = new ErrorHandler($pdo);

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $sessionManager->validateSession()) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$messageType = '';
$debugLink = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $result = $passwordResetHandler->generateResetToken($email);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
            
            // In development, show the reset link
            if (isset($result['debug_link'])) {
                $debugLink = $result['debug_link'];
            }
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
    <title>Forgot Password - Student Routine Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .forgot-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .forgot-form .icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .forgot-form h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .forgot-form .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
            line-height: 1.5;
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

        .debug-link {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }

        .debug-link strong {
            color: #d63031;
        }

        .debug-link a {
            color: #0984e3;
            word-break: break-all;
            text-decoration: none;
        }

        .debug-link a:hover {
            text-decoration: underline;
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
            .forgot-form {
                padding: 30px 20px;
                margin: 10px;
            }

            .forgot-form h2 {
                font-size: 1.5rem;
            }

            .forgot-form .icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <form class="forgot-form" method="POST" action="">
            <div class="icon">
                <i class="fas fa-key"></i>
            </div>
            
            <h2>Forgot Password?</h2>
            <p class="subtitle">
                Don't worry! Enter your email address and we'll send you a link to reset your password.
            </p>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($debugLink)): ?>
                <div class="debug-link">
                    <strong>Development Mode:</strong> For testing purposes, here's your reset link:<br>
                    <a href="<?php echo htmlspecialchars($debugLink); ?>" target="_blank">
                        <?php echo htmlspecialchars($debugLink); ?>
                    </a>
                    <br><small><em>(In production, this would be sent via email)</em></small>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>

            <div class="links">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
        </form>
    </div>

    <script>
        // Auto-focus email field
        document.getElementById('email').focus();

        // Add loading state to form submission
        document.querySelector('.forgot-form').addEventListener('submit', function(e) {
            const button = document.querySelector('.btn-primary');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            button.disabled = true;
            
            // Re-enable if there's an error (form doesn't actually submit)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 5000);
        });

        // Clear debug link after 30 seconds for security
        <?php if (!empty($debugLink)): ?>
        setTimeout(function() {
            const debugElement = document.querySelector('.debug-link');
            if (debugElement) {
                debugElement.style.opacity = '0.5';
                debugElement.innerHTML = '<strong>Development Mode:</strong> Reset link expired for security. Generate a new one if needed.';
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html> 