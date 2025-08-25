<?php
require_once '../config/database.php';
require_once '../includes/AdminManager.php';

// Initialize admin manager
$db = new Database();
$adminManager = new AdminManager($db->getConnection());

// Redirect if already logged in
if ($adminManager->isAuthenticated()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'You have been successfully logged out!';
}

// Create CheeseStar admin user if it doesn't exist
try {
    $stmt = $db->getConnection()->prepare("SELECT user_id FROM users WHERE username = 'CheeseStar'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // Create CheeseStar admin user
        $hashedPassword = password_hash('CheeseStar_0427', PASSWORD_DEFAULT);
        $stmt = $db->getConnection()->prepare("
            INSERT INTO users (username, email, password) 
            VALUES ('CheeseStar', 'wilsontan0427@1utar.my', ?)
        ");
        $stmt->execute([$hashedPassword]);
        $success = '🎉 CheeseStar admin account created successfully! You can now login.';
    }
} catch (Exception $e) {
    // Also try creating a fallback admin user
    try {
        $stmt = $db->getConnection()->prepare("SELECT user_id FROM users WHERE username = 'admin'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->getConnection()->prepare("
                INSERT INTO users (username, email, password) 
                VALUES ('admin', 'admin@studentorganizer.com', ?)
            ");
            $stmt->execute([$hashedPassword]);
        }
    } catch (Exception $e2) {
        $error = 'Database setup error: ' . $e2->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Both username and password are required';
    } else {
        $result = $adminManager->login($username, $password);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Student Routine Organizer</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .admin-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            font-size: 2rem;
            color: white;
        }

        .admin-title {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .admin-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-admin-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .back-link {
            margin-top: 1rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .credentials-info {
            background: #f0f8ff;
            border: 1px solid #b0d4f1;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #1976d2;
        }

        .credentials-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .credential-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.75rem;
            text-align: center;
        }

        .credential-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .credential-value {
            font-weight: bold;
            font-family: monospace;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-icon">🛡️</div>
        
        <h1 class="admin-title">Admin Panel</h1>
        <p class="admin-subtitle">Student Routine Organizer</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">👤 Username or Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Enter your admin username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">🔒 Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your admin password"
                    required
                >
            </div>

            <button type="submit" class="btn-admin-login">
                🚀 Access Admin Panel
            </button>
        </form>

        <div class="credentials-info">
            <strong>🔑 Available Admin Accounts:</strong>
            <div class="credentials-grid">
                <div class="credential-item">
                    <div class="credential-label">Primary Admin</div>
                    <div class="credential-value">CheeseStar</div>
                    <div class="credential-value">CheeseStar_0427</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Fallback Admin</div>
                    <div class="credential-value">admin</div>
                    <div class="credential-value">admin123</div>
                </div>
            </div>
        </div>

        <div class="back-link">
            <a href="../index.php">← Back to Main Site</a>
        </div>
    </div>

    <script>
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Auto-fill CheeseStar credentials on double-click
        document.getElementById('username').addEventListener('dblclick', function() {
            this.value = 'CheeseStar';
            document.getElementById('password').value = 'CheeseStar_0427';
            document.getElementById('password').focus();
        });
    </script>
</body>
</html>