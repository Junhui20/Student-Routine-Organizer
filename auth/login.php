<?php
require_once '../includes/SessionManager.php';
require_once '../includes/CookieManager.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    // Handle session errors gracefully
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect if already logged in
if(SessionManager::isAuthenticated()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$info = '';

// Check for remember me cookie
$cookie_data = CookieManager::getRememberMeCookie();
if ($cookie_data && !SessionManager::isAuthenticated()) {
    // Auto-login from remember me cookie
    try {
        require_once '../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Verify user exists and token is valid
        $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
        $stmt->execute([$cookie_data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            SessionManager::loginUser($user['user_id'], $user['username'], false);
            $info = "Welcome back! You were automatically logged in.";
        } else {
            CookieManager::clearRememberMeCookies();
        }
    } catch (Exception $e) {
        CookieManager::clearRememberMeCookies();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
    
    if(empty($username) || empty($password)) {
        $error = "Both username and password are required";
        SessionManager::recordLoginAttempt($username, false);
    } else {
        try {
            // Check if login is blocked
            if (SessionManager::isLoginBlocked($username)) {
                $error = "Too many failed login attempts. Please try again in 15 minutes.";
            } else {
                $db = new Database();
                $conn = $db->getConnection();
                
                // Find user by username or email
                $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user && password_verify($password, $user['password'])) {
                    // Login successful - use advanced session management
                    SessionManager::loginUser($user['user_id'], $user['username'], $remember_me);
                    SessionManager::recordLoginAttempt($username, true);
                    
                    header("Location: ../index.php");
                    exit();
                } else {
                    $error = "Invalid username or password";
                    SessionManager::recordLoginAttempt($username, false);
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Routine Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-calendar-check"></i> Student Routine Organizer</a>
            </div>
            <div class="nav-menu">
                <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="../auth/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="../auth/register.php" class="nav-link"><i class="fas fa-user-plus"></i> Register</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container" style="max-width: 500px; margin: 2rem auto;">
            <h2><i class="fas fa-sign-in-alt"></i> Welcome Back!</h2>
            <p class="text-muted text-center" style="margin-bottom: 2rem;">Sign in to access your productivity dashboard</p>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($info): ?>
                <div class="alert alert-success">
                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($info); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <!-- Remember Me Checkbox - University Topic: User Persistence Cookie -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" name="remember_me" value="1" style="margin-right: 0.5rem;">
                        <i class="fas fa-clock" style="margin-right: 0.5rem; color: #667eea;"></i>
                        Remember me for 30 days
                    </label>
                    <small class="text-muted">Keep me logged in on this device</small>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
            </form>

            <div class="text-center mt-2">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot_password.php">Forgot your password?</a></p>
            </div>
            <div class="text-center mt-2">
                <p>I am an admin <a href="../admin/login.php">Login here</a></p>
            </div>
            <div class="text-center mt-2">
                <small class="text-muted">
                    Access to: Exercise Tracker | Diary Journal | Money Tracker | Habit Tracker
                </small>
            </div>
            
            <!-- University Topics Demonstration -->
            <div class="mt-2" style="border-top: 1px solid #eee; padding-top: 1rem;">
                <small class="text-muted">
                    <strong>University Features Demonstrated:</strong><br>
                    • Advanced Session Management with timeout & regeneration<br>
                    • Cookie-based user persistence ("Remember Me")<br>
                    • Login attempt tracking & security<br>
                    • Session hijacking protection
                </small>
            </div>
        </div>
    </main>

     