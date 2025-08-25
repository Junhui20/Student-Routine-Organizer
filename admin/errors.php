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
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'clear_old_logs':
                if ($adminManager->hasPermission('clear_logs', 'admin')) {
                    $daysOld = intval($_POST['days_old'] ?? 30);
                    
                    // Clear old logs manually instead of using ErrorHandler class
                    $stmt = $db->getConnection()->prepare("DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                    $stmt->execute([$daysOld]);
                    $cleared = $stmt->rowCount();
                    
                    $adminManager->logAdminActivity('clear_logs', 'system', null, "Cleared $cleared old error logs");
                    $message = "Successfully cleared $cleared old error logs";
                    $messageType = 'success';
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'clear_all_logs':
                if ($adminManager->hasPermission('clear_logs', 'super_admin')) {
                    $stmt = $db->getConnection()->prepare("DELETE FROM error_logs");
                    $stmt->execute();
                    $cleared = $stmt->rowCount();
                    $adminManager->logAdminActivity('clear_all_logs', 'system', null, "Cleared all error logs");
                    $message = "Successfully cleared all error logs";
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

// Get filters
$errorType = $_GET['type'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    // Build query with filters
    $whereConditions = [];
    $params = [];
    
    if (!empty($errorType)) {
        $whereConditions[] = "error_type LIKE ?";
        $params[] = "%$errorType%";
    }
    
    if (!empty($dateFilter)) {
        $whereConditions[] = "DATE(created_at) = ?";
        $params[] = $dateFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM error_logs $whereClause";
    $stmt = $db->getConnection()->prepare($countQuery);
    $stmt->execute($params);
    $totalErrors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalErrors / $limit);
    
    // Get error logs - FIXED SQL SYNTAX (LIMIT offset, count)
    $logsQuery = "
    SELECT el.*, u.username 
    FROM error_logs el
    LEFT JOIN users u ON el.user_id = u.user_id
    $whereClause
    ORDER BY el.created_at DESC
    LIMIT $offset, $limit
";

    // DON'T add limit and offset to params array
    $stmt = $db->getConnection()->prepare($logsQuery);
    $stmt->execute($params);
        
    // Get error statistics - SIMPLIFIED
    try {
    $errorStats = [
        'total' => $totalErrors,
        'recent' => 0,
        'by_type' => $errorTypes
    ];
    
    // Get recent errors count safely
        $stmt = $db->getConnection()->query("SELECT COUNT(*) as recent FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $errorStats['recent'] = $result ? $result['recent'] : 0;
    } catch (Exception $e) {
        $errorStats = ['total' => 0, 'recent' => 0, 'by_type' => []];
    }
    
    // Get recent errors count
    $stmt = $db->getConnection()->query("SELECT COUNT(*) as recent FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $errorStats['recent'] = $result['recent'] ?? 0;
    
    // Get error types for filter dropdown
    $stmt = $db->getConnection()->query("
        SELECT DISTINCT error_type, COUNT(*) as count 
        FROM error_logs 
        GROUP BY error_type 
        ORDER BY count DESC 
        LIMIT 20
    ");
    $errorTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $errorStats['by_type'] = $errorTypes;
    
} catch (Exception $e) {
    $errorLogs = [];
    $totalErrors = 0;
    $totalPages = 0;
    $errorStats = ['total' => 0, 'recent' => 0, 'by_type' => []];
    $errorTypes = [];
    $message = 'Error loading logs: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Logs - Admin Panel</title>
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
            max-width: 1400px;
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

        .error-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid;
        }

        .stat-card.total { border-left-color: #6c757d; }
        .stat-card.recent { border-left-color: #ffc107; }
        .stat-card.critical { border-left-color: #dc3545; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-card.total .stat-number { color: #6c757d; }
        .stat-card.recent .stat-number { color: #ffc107; }
        .stat-card.critical .stat-number { color: #dc3545; }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .logs-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .logs-header {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .log-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.3s;
        }

        .log-item:hover {
            background: #f8f9fa;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .log-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .log-type.php-error { background: #fee; color: #c33; }
        .log-type.exception { background: #ffeaa7; color: #d63031; }
        .log-type.fatal-error { background: #fab1a0; color: #e17055; }
        .log-type.database { background: #a29bfe; color: #6c5ce7; }
        .log-type.authentication { background: #fd79a8; color: #e84393; }

        .log-time {
            color: #666;
            font-size: 0.9rem;
        }

        .log-message {
            color: #333;
            margin-bottom: 1rem;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            word-wrap: break-word;
        }

        .log-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .log-detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .log-detail-item i {
            color: #667eea;
            width: 16px;
        }

        .log-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 3px;
        }

        .management-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .management-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
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

        @media (max-width: 768px) {
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .log-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .log-details {
                grid-template-columns: 1fr;
            }
            
            .management-actions {
                flex-direction: column;
                align-items: stretch;
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
                <a href="errors.php" class="admin-nav-link active">
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
            <h1 style="color: #333; font-size: 2rem; margin: 0;">
                <i class="fas fa-bug"></i> System Error Logs
            </h1>
            <p style="color: #666; margin: 0.5rem 0 0 0;">Monitor and manage system errors and exceptions</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Error Statistics -->
        <div class="error-stats">
            <div class="stat-card total">
                <div class="stat-number"><?php echo number_format($errorStats['total']); ?></div>
                <div>Total Errors</div>
            </div>
            <div class="stat-card recent">
                <div class="stat-number"><?php echo number_format($errorStats['recent']); ?></div>
                <div>Last 24 Hours</div>
            </div>
            <div class="stat-card critical">
                <div class="stat-number">
                    <?php 
                    $criticalCount = 0;
                    foreach ($errorStats['by_type'] as $typeStats) {
                        if (strpos(strtolower($typeStats['error_type']), 'fatal') !== false || 
                            strpos(strtolower($typeStats['error_type']), 'exception') !== false) {
                            $criticalCount += $typeStats['count'];
                        }
                    }
                    echo number_format($criticalCount);
                    ?>
                </div>
                <div>Critical Errors</div>
            </div>
        </div>

        <!-- Log Management -->
        <div class="management-section">
            <h3><i class="fas fa-tools"></i> Log Management</h3>
            <div class="management-actions">
                <form method="POST" style="display: inline-flex; gap: 1rem; align-items: center;">
                    <input type="hidden" name="action" value="clear_old_logs">
                    <label>Clear logs older than:</label>
                    <select name="days_old">
                        <option value="7">7 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="90">90 days</option>
                    </select>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-broom"></i> Clear Old Logs
                    </button>
                </form>
                
                <?php if ($adminManager->hasPermission('clear_logs', 'super_admin')): ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear ALL error logs? This cannot be undone.')">
                    <input type="hidden" name="action" value="clear_all_logs">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Clear All Logs
                    </button>
                </form>
                <?php endif; ?>
                
                <button onclick="exportLogs()" class="btn btn-info btn-sm">
                    <i class="fas fa-download"></i> Export Logs
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="type">Error Type</label>
                    <select name="type" id="type">
                        <option value="">All Types</option>
                        <?php foreach ($errorTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['error_type']); ?>" 
                                    <?php echo $errorType === $type['error_type'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['error_type']); ?> (<?php echo $type['count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="errors.php" class="btn btn-secondary" style="margin-top: 0.5rem;">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Error Logs -->
        <div class="logs-section">
            <div class="logs-header">
                <h3>Error Logs</h3>
                <span>Showing <?php echo count($errorLogs); ?> of <?php echo $totalErrors; ?> errors</span>
            </div>

            <?php if (!empty($errorLogs)): ?>
                <?php foreach ($errorLogs as $log): ?>
                    <div class="log-item">
                        <div class="log-header">
                            <span class="log-type <?php echo str_replace([':', '_'], '-', strtolower($log['error_type'])); ?>">
                                <?php echo htmlspecialchars($log['error_type']); ?>
                            </span>
                            <span class="log-time">
                                <?php echo date('M j, Y g:i:s A', strtotime($log['created_at'])); ?>
                            </span>
                        </div>

                        <div class="log-message">
                            <?php echo htmlspecialchars($log['error_message']); ?>
                        </div>

                        <div class="log-details">
                            <?php if ($log['error_file']): ?>
                                <div class="log-detail-item">
                                    <i class="fas fa-file"></i>
                                    <span>File: <?php echo htmlspecialchars(basename($log['error_file'])); ?>:<?php echo $log['error_line']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($log['username']): ?>
                                <div class="log-detail-item">
                                    <i class="fas fa-user"></i>
                                    <span>User: <?php echo htmlspecialchars($log['username']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($log['request_uri']): ?>
                                <div class="log-detail-item">
                                    <i class="fas fa-link"></i>
                                    <span>URI: <?php echo htmlspecialchars($log['request_uri']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($log['ip_address']): ?>
                                <div class="log-detail-item">
                                    <i class="fas fa-globe"></i>
                                    <span>IP: <?php echo htmlspecialchars($log['ip_address']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($log['stack_trace']): ?>
                            <div class="log-actions">
                                <button onclick="toggleStackTrace(<?php echo $log['log_id']; ?>)" class="btn btn-sm btn-info">
                                    <i class="fas fa-code"></i> Toggle Stack Trace
                                </button>
                            </div>
                            
                            <div id="stack-<?php echo $log['log_id']; ?>" style="display: none; margin-top: 1rem;">
                                <div style="background: #f1f3f4; padding: 1rem; border-radius: 5px; font-family: monospace; font-size: 0.8rem; overflow-x: auto;">
                                    <?php 
                                    $stackTrace = json_decode($log['stack_trace'], true);
                                    if (is_array($stackTrace)) {
                                        foreach ($stackTrace as $i => $trace) {
                                            echo "#$i " . ($trace['file'] ?? '[internal]') . '(' . ($trace['line'] ?? '0') . '): ';
                                            echo ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '') . "()\n";
                                        }
                                    } else {
                                        echo htmlspecialchars($log['stack_trace']);
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&type=<?php echo urlencode($errorType); ?>&date=<?php echo urlencode($dateFilter); ?>">First</a>
                            <a href="?page=<?php echo $page-1; ?>&type=<?php echo urlencode($errorType); ?>&date=<?php echo urlencode($dateFilter); ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&type=<?php echo urlencode($errorType); ?>&date=<?php echo urlencode($dateFilter); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?>&type=<?php echo urlencode($errorType); ?>&date=<?php echo urlencode($dateFilter); ?>">Next</a>
                            <a href="?page=<?php echo $totalPages; ?>&type=<?php echo urlencode($errorType); ?>&date=<?php echo urlencode($dateFilter); ?>">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="padding: 3rem; text-align: center; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: #28a745;"></i>
                    <h3>No Error Logs Found</h3>
                    <?php if (!empty($errorType) || !empty($dateFilter)): ?>
                        <p>No errors match your filter criteria.</p>
                    <?php else: ?>
                        <p>Great! No system errors have been recorded.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleStackTrace(logId) {
            const element = document.getElementById('stack-' + logId);
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.open('export_logs.php?' + params.toString(), '_blank');
        }

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

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