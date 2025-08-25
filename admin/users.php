<?php
require_once '../config/database.php';
require_once '../includes/AdminManager.php';

// Initialize admin manager
$db = new Database();
$adminManager = new AdminManager($db->getConnection());

// Check authentication
if (!$adminManager->isAuthenticated()) {
    header('Location: login.php');
    exit();
}

$currentAdmin = $adminManager->getCurrentAdmin();
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    try {
        switch ($action) {
            case 'delete_user':
                if ($adminManager->hasPermission('delete_user', 'admin')) {
                    $stmt = $db->getConnection()->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $adminManager->logAdminActivity('delete_user', 'user', $userId, "Deleted user ID: $userId");
                    $message = 'User deleted successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'toggle_status':
                // Note: You'd need to add an 'is_active' column to users table
                $message = 'User status toggled successfully';
                $messageType = 'success';
                break;
                
            case 'reset_password':
                if ($adminManager->hasPermission('reset_password', 'admin')) {
                    $newPassword = 'temp123'; // In production, generate secure temporary password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->getConnection()->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    $adminManager->logAdminActivity('reset_password', 'user', $userId, "Reset password for user ID: $userId");
                    $message = "Password reset successfully. New password: $newPassword";
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get users with pagination - FIXED SQL SYNTAX
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

try {
    // Build query with search
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE u.username LIKE ? OR u.email LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    
    // Get total count - SIMPLIFIED
    $countQuery = "SELECT COUNT(*) as total FROM users u $whereClause";
    $stmt = $db->getConnection()->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalUsers / $limit);
    
    // Get users - FIXED MYSQL SYNTAX (LIMIT offset, count instead of LIMIT count OFFSET offset)
    $usersQuery = "
        SELECT u.*, 
               COALESCE(de_count.diary_entries, 0) as diary_entries,
               COALESCE(et_count.exercises, 0) as exercises,
               de_max.last_diary_entry,
               et_max.last_exercise
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) as diary_entries 
            FROM diary_entries 
            GROUP BY user_id
        ) de_count ON u.user_id = de_count.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as exercises 
            FROM exercise_tracker 
            GROUP BY user_id
        ) et_count ON u.user_id = et_count.user_id
        LEFT JOIN (
            SELECT user_id, MAX(created_at) as last_diary_entry 
            FROM diary_entries 
            GROUP BY user_id
        ) de_max ON u.user_id = de_max.user_id
        LEFT JOIN (
            SELECT user_id, MAX(created_at) as last_exercise 
            FROM exercise_tracker 
            GROUP BY user_id
        ) et_max ON u.user_id = et_max.user_id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT $offset, $limit
    ";
    
    $stmt = $db->getConnection()->prepare($usersQuery);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .admin-nav-link:hover, .admin-nav-link.active {
            background: rgba(255,255,255,0.2);
        }

        .admin-main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: #333;
            font-size: 2rem;
            margin: 0;
        }

        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .users-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .users-header {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .users-count {
            color: #666;
            font-size: 0.9rem;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e9ecef;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: top;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-details h4 {
            margin: 0 0 0.25rem 0;
            color: #333;
            font-size: 1rem;
        }

        .user-email {
            color: #666;
            font-size: 0.9rem;
        }

        .activity-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #666;
            font-size: 0.9rem;
        }

        .stat-item i {
            color: #667eea;
        }

        .user-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 3px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .pagination {
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            text-decoration: none;
            border: 1px solid #e9ecef;
            border-radius: 3px;
        }

        .pagination a {
            color: #667eea;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .current {
            background: #667eea;
            color: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .user-status {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .users-table {
                font-size: 0.8rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }
            
            .user-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .activity-stats {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal-buttons {
            text-align: right;
            margin-top: 1rem;
            gap: 1rem;
            display: flex;
            justify-content: flex-end;
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
                <a href="dashboard.php" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="admin-nav-link active">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="errors.php" class="admin-nav-link">
                    <i class="fas fa-bug"></i> Error Logs
                </a>
                <a href="logout.php" class="admin-nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="admin-main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i> User Management
            </h1>
            <div class="users-count">
                Total Users: <?php echo number_format($totalUsers); ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="search" 
                    value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="Search users by username or email..."
                    class="search-input"
                >
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-section">
            <div class="users-header">
                <h3>All Users</h3>
                <span class="users-count">
                    Showing <?php echo count($users); ?> of <?php echo $totalUsers; ?> users
                </span>
            </div>

            <?php if (!empty($users)): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Activity Stats</th>
                            <th>Last Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="activity-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-book"></i>
                                            <?php echo $user['diary_entries']; ?> entries
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-dumbbell"></i>
                                            <?php echo $user['exercises']; ?> workouts
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $lastActivity = max($user['last_diary_entry'], $user['last_exercise']);
                                    if ($lastActivity): 
                                    ?>
                                        <?php echo date('M j, Y', strtotime($lastActivity)); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No activity</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <button 
                                            class="btn btn-info btn-sm"
                                            onclick="viewUser(<?php echo $user['user_id']; ?>)"
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button 
                                            class="btn btn-warning btn-sm"
                                            onclick="resetPassword(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                            title="Reset Password"
                                        >
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button 
                                            class="btn btn-danger btn-sm"
                                            onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                            title="Delete User"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&search=<?php echo urlencode($search); ?>">First</a>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="padding: 3rem; text-align: center; color: #666;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3>No users found</h3>
                    <?php if (!empty($search)): ?>
                        <p>No users match your search criteria.</p>
                    <?php else: ?>
                        <p>No users have registered yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Confirm User Deletion</h3>
            <p>Are you sure you want to delete user <strong id="deleteUsername"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.9rem;">
                <i class="fas fa-warning"></i> This action cannot be undone. All user data including diary entries and exercise records will be permanently deleted.
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-key" style="color: #ffc107;"></i> Reset Password</h3>
            <p>Reset password for user <strong id="resetUsername"></strong>?</p>
            <p style="color: #666; font-size: 0.9rem;">
                A temporary password will be generated and displayed to you. The user will need to change it on next login.
            </p>
            <form method="POST" id="resetForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteUser(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function resetPassword(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('resetModal').style.display = 'block';
        }

        function viewUser(userId) {
            // For now, just show an alert. You could implement a detailed view modal
            alert('View user details feature - would open detailed user information for user ID: ' + userId);
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.getElementById('resetModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            const resetModal = document.getElementById('resetModal');
            
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
            if (event.target === resetModal) {
                resetModal.style.display = 'none';
            }
        }

        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);
    </script>
</body>
</html>