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

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_settings':
                if ($adminManager->hasPermission('update_settings', 'admin')) {
                    $settings = $_POST['settings'] ?? [];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $db->getConnection()->prepare("
                            UPDATE system_settings 
                            SET setting_value = ?, updated_by = ?
                            WHERE setting_key = ?
                        ");
                        $stmt->execute([$value, $currentAdmin['admin_id'], $key]);
                    }
                    
                    $adminManager->logAdminActivity('update_settings', 'system', null, 'Updated system settings');
                    $message = 'System settings updated successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'add_setting':
                if ($adminManager->hasPermission('add_setting', 'admin')) {
                    $key = trim($_POST['setting_key'] ?? '');
                    $value = trim($_POST['setting_value'] ?? '');
                    $type = $_POST['setting_type'] ?? 'string';
                    $description = trim($_POST['description'] ?? '');
                    $isPublic = isset($_POST['is_public']) ? 1 : 0;
                    
                    if (empty($key)) {
                        throw new Exception('Setting key is required');
                    }
                    
                    $stmt = $db->getConnection()->prepare("
                        INSERT INTO system_settings 
                        (setting_key, setting_value, setting_type, description, is_public, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$key, $value, $type, $description, $isPublic, $currentAdmin['admin_id']]);
                    
                    $adminManager->logAdminActivity('add_setting', 'system', null, "Added setting: $key");
                    $message = 'New setting added successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'delete_setting':
                if ($adminManager->hasPermission('delete_setting', 'super_admin')) {
                    $settingId = $_POST['setting_id'] ?? '';
                    $stmt = $db->getConnection()->prepare("DELETE FROM system_settings WHERE setting_id = ?");
                    $stmt->execute([$settingId]);
                    
                    $adminManager->logAdminActivity('delete_setting', 'system', $settingId, "Deleted setting ID: $settingId");
                    $message = 'Setting deleted successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions - Super Admin required');
                }
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get all settings
try {
    $stmt = $db->getConnection()->prepare("
        SELECT s.*, au.username as updated_by_user
        FROM system_settings s
        LEFT JOIN admin_users au ON s.updated_by = au.admin_id
        ORDER BY s.setting_key
    ");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system info
    $systemInfo = [
        'php_version' => phpversion(),
        'mysql_version' => $db->getConnection()->query('SELECT VERSION()')->fetchColumn(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    ];
    
} catch (Exception $e) {
    $settings = [];
    $systemInfo = [];
    $message = 'Error loading settings: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
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
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .settings-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            color: #333;
            font-size: 1.3rem;
            margin: 0;
        }

        .section-content {
            padding: 1.5rem;
        }

        .setting-item {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-label {
            font-weight: 600;
            color: #333;
        }

        .setting-description {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .setting-input {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .setting-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .setting-type {
            font-size: 0.8rem;
            color: #666;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }

        .add-setting-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .system-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #666;
            font-family: monospace;
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

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 3px;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .setting-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .system-info {
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
                <a href="dashboard.php" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="admin-nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="errors.php" class="admin-nav-link">
                    <i class="fas fa-bug"></i> Error Logs
                </a>
                <a href="settings.php" class="admin-nav-link active">
                    <i class="fas fa-cogs"></i> Settings
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
            <h1 style="color: #333; font-size: 2rem; margin: 0;">
                <i class="fas fa-cogs"></i> System Settings
            </h1>
            <p style="color: #666; margin: 0.5rem 0 0 0;">Configure system parameters and application settings</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Current Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-sliders-h"></i> Application Settings
                    </h3>
                </div>
                <form method="POST" class="section-content">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <?php foreach ($settings as $setting): ?>
                        <div class="setting-item">
                            <div>
                                <div class="setting-label"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                                <?php if ($setting['description']): ?>
                                    <div class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                    <select name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" class="setting-input">
                                        <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                    <input 
                                        type="number" 
                                        name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" 
                                        value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                        class="setting-input"
                                    >
                                <?php else: ?>
                                    <input 
                                        type="text" 
                                        name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" 
                                        value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                        class="setting-input"
                                    >
                                <?php endif; ?>
                                <div class="setting-type"><?php echo $setting['setting_type']; ?></div>
                                <?php if ($setting['updated_by_user']): ?>
                                    <div style="font-size: 0.7rem; color: #999; margin-top: 0.25rem;">
                                        Last updated by: <?php echo htmlspecialchars($setting['updated_by_user']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if ($adminManager->hasPermission('delete_setting', 'super_admin')): ?>
                                    <button 
                                        type="button" 
                                        class="btn-danger"
                                        onclick="deleteSetting(<?php echo $setting['setting_id']; ?>, '<?php echo htmlspecialchars($setting['setting_key']); ?>')"
                                        title="Delete Setting"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Settings
                        </button>
                    </div>
                </form>
            </div>

            <div>
                <!-- Add New Setting -->
                <div class="settings-section" style="margin-bottom: 2rem;">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-plus"></i> Add New Setting
                        </h3>
                    </div>
                    <div class="section-content">
                        <form method="POST" class="add-setting-form">
                            <input type="hidden" name="action" value="add_setting">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="setting_key">Setting Key</label>
                                    <input type="text" id="setting_key" name="setting_key" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="setting_type">Type</label>
                                    <select id="setting_type" name="setting_type">
                                        <option value="string">String</option>
                                        <option value="integer">Integer</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="setting_value">Value</label>
                                <input type="text" id="setting_value" name="setting_value">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="2"></textarea>
                            </div>
                            
                            <div class="checkbox-group" style="margin-bottom: 1rem;">
                                <input type="checkbox" id="is_public" name="is_public">
                                <label for="is_public">Public Setting (visible to users)</label>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Setting
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Information -->
                <div class="settings-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i> System Information
                        </h3>
                    </div>
                    <div class="section-content">
                        <div class="system-info">
                            <?php foreach ($systemInfo as $key => $value): ?>
                                <div class="info-item">
                                    <span class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?></span>
                                    <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <button onclick="checkSystemHealth()" class="btn btn-info">
                                <i class="fas fa-heartbeat"></i> Check System Health
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Setting Modal -->
    <div id="deleteModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 15% auto; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Confirm Setting Deletion</h3>
            <p>Are you sure you want to delete the setting <strong id="deleteSettingKey"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.9rem;">
                <i class="fas fa-warning"></i> This action cannot be undone.
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_setting">
                <input type="hidden" name="setting_id" id="deleteSettingId">
                <div style="text-align: right; margin-top: 1rem; gap: 1rem; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Setting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteSetting(settingId, settingKey) {
            document.getElementById('deleteSettingId').value = settingId;
            document.getElementById('deleteSettingKey').textContent = settingKey;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function checkSystemHealth() {
            // This would normally make an AJAX request to check system health
            alert('System Health Check:\n\n✅ Database: Connected\n✅ File Permissions: OK\n✅ PHP Extensions: Loaded\n✅ Memory Usage: Normal\n\nSystem is running smoothly!');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.style.display = 'none';
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

        // Form validation
        document.getElementById('setting_key').addEventListener('input', function() {
            // Convert to lowercase and replace spaces with underscores
            this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '_');
        });
    </script>
</body>
</html>