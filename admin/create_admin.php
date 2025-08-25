<?php
/**
 * Create CheeseStar Admin User
 * Save this as: admin/create_admin.php
 * Run once, then delete this file for security
 */

require_once '../config/database.php';

// Your custom admin credentials
$username = 'CheeseStar';
$email = 'wilsontan0427@1utar.my';
$password = 'CheeseStar_0427';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; margin: 20px; border: 1px solid #ffeaa7;'>";
        echo "<h2>⚠️ User Already Exists</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($existing['username']) . "</p>";
        echo "<p>This admin user already exists in the database!</p>";
        echo "<p><a href='login.php'>→ Go to Login Page</a></p>";
        echo "</div>";
    } else {
        // Create new admin user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $success = $stmt->execute([$username, $email, $hashedPassword]);
        
        if ($success) {
            echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px; border: 1px solid #c3e6cb;'>";
            echo "<h2>✅ Admin User Created Successfully!</h2>";
            echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
            echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
            echo "<hr>";
            echo "<p><strong>🎯 You can now login with these credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> CheeseStar</li>";
            echo "<li><strong>Password:</strong> CheeseStar_0427</li>";
            echo "</ul>";
            echo "<p><a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Login to Admin Panel</a></p>";
            echo "<hr>";
            echo "<p style='font-size: 0.9em; color: #dc3545;'><strong>🔒 Security:</strong> Delete this file (create_admin.php) after use!</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px; border: 1px solid #f5c6cb;'>";
            echo "<h2>❌ Error Creating User</h2>";
            echo "<p>Failed to create admin user. Please check database connection.</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px; border: 1px solid #f5c6cb;'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin User Creation</h1>
            <p>Creating custom admin account for CheeseStar</p>
        </div>
    </div>
</body>
</html>