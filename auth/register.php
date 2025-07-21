<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                if($stmt->execute([$username, $email, $hashed_password])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Routine Organizer</title>
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
            <h2><i class="fas fa-user-plus"></i> Join Student Routine Organizer</h2>
            <p class="text-muted text-center" style="margin-bottom: 2rem;">Create your account to access all four productivity modules</p>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <br><a href="login.php">Click here to login</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="text-center mt-2">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <div class="text-center mt-2">
                <small class="text-muted">
                    Access to: Exercise Tracker | Diary Journal | Money Tracker | Habit Tracker
                </small>
            </div>
        </div>
    </main>

     