<?php
require_once '../config/database.php';
require_once '../includes/AdminManager.php';
require_once '../includes/ErrorHandler.php';

// Initialize admin manager
$db = new Database();
$adminManager = new AdminManager($db->getConnection());

// Check authentication
if (!$adminManager->isAuthenticated()) {
    header('Location: login.php');
    exit();
}

$currentAdmin = $adminManager->getCurrentAdmin();
$systemStats = $adminManager->getSystemStats();

// Get recent activity
try {
    $stmt = $db->getConnection()->prepare("
        SELECT * FROM admin_activity_log 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent errors
    $stmt = $db->getConnection()->prepare("
        SELECT * FROM error_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentErrors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active users (logged in within last 24 hours)
    $stmt = $db->getConnection()->prepare("
        SELECT COUNT(DISTINCT user_id) as active_users 
        FROM diary_entries 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        UNION ALL
        SELECT COUNT(DISTINCT user_id) 
        FROM exercise_tracker 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $activeUsersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $activeUsers = array_sum(array_column($activeUsersData, 'active_users'));
    
} catch (Exception $e) {
    $recentActivity = [];
    $recentErrors = [];
    $activeUsers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Routine Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Admin-specific styles */
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
            margin-bottom: 2rem;
        }

        .admin-nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .admin-logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .admin-nav-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .admin-nav-link:hover {
            background: rgba(255,255,255,0.2);
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .admin-role-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: bold;
        }

        .admin-main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .admin-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            text-align: center;
        }

        .admin-welcome {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .admin-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.users { border-left-color: #007bff; }
        .stat-card.entries { border-left-color: #28a745; }
        .stat-card.exercises { border-left-color: #fd7e14; }
        .stat-card.errors { border-left-color: #dc3545; }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-card.users .stat-icon { color: #007bff; }
        .stat-card.entries .stat-icon { color: #28a745; }
        .stat-card.exercises .stat-icon { color: #fd7e14; }
        .stat-card.errors .stat-icon { color: #dc3545; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.9rem;
            color: #28a745;
        }

        .admin-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .admin-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .action-card:hover {
            transform: translateY(-3px);
            color: white;
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
        }

        .activity-content {
            flex: 1;
        }

        .activity-action {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        .error-item {
            padding: 1rem;
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .error-type {
            font-weight: bold;
            color: #e53e3e;
            margin-bottom: 0.5rem;
        }

        .error-message {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .error-time {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .admin-sections {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-navbar">
        <div class="admin-nav-container">
            <div class="admin-logo">
                <a href="dashboard.php">
                    <i class="fas fa-shield-alt"></i> Admin Panel
                </a>
            </div>
            
            <div class="admin-nav-menu">
                <a href="users.php" class="admin-nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="errors.php" class="admin-nav-link">
                    <i class="fas fa-bug"></i> Error Logs
                </a>
                <a href="settings.php" class="admin-nav-link">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <div class="admin-user-info">
                    <span class="admin-role-badge"><?php echo strtoupper($currentAdmin['role']); ?></span>
                    <span><?php echo htmlspecialchars($currentAdmin['username']); ?></span>
                    <a href="logout.php" class="admin-nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="admin-main-content">
        <!-- Welcome Header -->
        <div class="admin-header">
            <h1 class="admin-welcome">
                <i class="fas fa-tachometer-alt"></i>
                Welcome back, <?php echo htmlspecialchars($currentAdmin['username']); ?>!
            </h1>
            <p class="admin-subtitle">
                System Overview - Student Routine Organizer Admin Panel
            </p>
        </div>

        <!-- System Statistics -->
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($systemStats['total_users'] ?? 0); ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-change">
                    +<?php echo $systemStats['new_users_week'] ?? 0; ?> this week
                </div>
            </div>

            <div class="stat-card entries">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-number"><?php echo number_format($systemStats['total_diary_entries'] ?? 0); ?></div>
                <div class="stat-label">Diary Entries</div>
                <div class="stat-change">
                    <?php echo $activeUsers; ?> active users today
                </div>
            </div>

            <div class="stat-card exercises">
                <div class="stat-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-number"><?php echo number_format($systemStats['total_exercises'] ?? 0); ?></div>
                <div class="stat-label">Exercise Records</div>
                <div class="stat-change">
                    Health tracking active
                </div>
            </div>

            <div class="stat-card errors">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($systemStats['recent_errors'] ?? 0); ?></div>
                <div class="stat-label">Recent Errors</div>
                <div class="stat-change">
                    Last 24 hours
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div class="quick-actions">
                <a href="users.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>Manage Users</div>
                </a>
                <a href="errors.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div>View Error Logs</div>
                </a>
                <a href="backup.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>Database Backup</div>
                </a>
                <a href="../index.php" target="_blank" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-external-link-alt"></i>
                    </div>
                    <div>Visit Site</div>
                </a>
            </div>
        </div>

        <!-- Activity & Errors Section -->
        <div class="admin-sections">
            <div class="admin-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Recent Admin Activity
                </h2>
                <div class="activity-list">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-action">
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                        <?php if ($activity['description']): ?>
                                            - <?php echo htmlspecialchars($activity['description']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div class="activity-content">
                                <div class="activity-action">No recent activity</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-section">
                <h2 class="section-title">
                    <i class="fas fa-exclamation-circle"></i> Recent Errors
                </h2>
                <?php if (!empty($recentErrors)): ?>
                    <?php foreach ($recentErrors as $error): ?>
                        <div class="error-item">
                            <div class="error-type">
                                <?php echo htmlspecialchars($error['error_type']); ?>
                            </div>
                            <div class="error-message">
                                <?php echo htmlspecialchars(substr($error['error_message'], 0, 100)); ?>
                                <?php if (strlen($error['error_message']) > 100) echo '...'; ?>
                            </div>
                            <div class="error-time">
                                <?php echo date('M j, Y g:i A', strtotime($error['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="error-item" style="background: #f0f8f0; border-color: #c3e6c3;">
                        <div class="error-type" style="color: #28a745;">
                            No Recent Errors
                        </div>
                        <div class="error-message">
                            System is running smoothly!
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="errors.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Error Logs
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Auto-refresh statistics every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Add click animations to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            
            // You could add a clock element to display current time
            // document.getElementById('current-time').textContent = `${dateString} ${timeString}`;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>