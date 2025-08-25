<?php
require_once '../includes/SessionManager.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect if not logged in
if (!SessionManager::isAuthenticated()) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/ErrorHandler.php';

$user_id = SessionManager::getCurrentUserId();
$message = '';
$messageType = '';

// Initialize variables
$balance = ['total_income' => 0, 'total_expenses' => 0, 'current_balance' => 0];
$monthly = ['month_income' => 0, 'month_expenses' => 0];
$transactions = [];
$userCategories = [];
$totalPages = 1;
$totalTransactions = 0;

// Get database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Handle transaction deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $transaction_id = (int)$_GET['delete'];
        
        // Verify transaction belongs to user
        $stmt = $conn->prepare("SELECT transaction_id FROM money_transactions WHERE transaction_id = ? AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $deleteStmt = $conn->prepare("DELETE FROM money_transactions WHERE transaction_id = ? AND user_id = ?");
            if ($deleteStmt->execute([$transaction_id, $user_id])) {
                $message = "Transaction deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to delete transaction.";
                $messageType = "error";
            }
        } else {
            $message = "Transaction not found or access denied.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "Error deleting transaction: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get filter parameters
$filterMonth = isset($_GET['month']) && !empty($_GET['month']) ? $_GET['month'] : '';
$filterCategory = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : '';
$filterType = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : '';

// Get balance summary (always show all transactions for totals)
$balanceStmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expenses,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as current_balance
    FROM money_transactions 
    WHERE user_id = ?
");
$balanceStmt->execute([$user_id]);
$balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);

// Get monthly summary
$summaryMonth = $filterMonth ?: date('Y-m');
$monthlyStmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as month_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as month_expenses
    FROM money_transactions 
    WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
");
$monthlyStmt->execute([$user_id, $summaryMonth]);
$monthly = $monthlyStmt->fetch(PDO::FETCH_ASSOC);

// Build transaction query with filters
$whereConditions = ["user_id = ?"];
$queryParams = [$user_id];

if (!empty($filterMonth)) {
    $whereConditions[] = "DATE_FORMAT(transaction_date, '%Y-%m') = ?";
    $queryParams[] = $filterMonth;
}

if (!empty($filterCategory)) {
    $whereConditions[] = "category = ?";
    $queryParams[] = $filterCategory;
}

if (!empty($filterType)) {
    $whereConditions[] = "type = ?";
    $queryParams[] = $filterType;
}

$whereClause = implode(' AND ', $whereConditions);

// Get pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total transactions
$countQuery = "SELECT COUNT(*) FROM money_transactions WHERE $whereClause";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($queryParams);
$totalTransactions = $countStmt->fetchColumn();
$totalPages = ceil($totalTransactions / $perPage);

// Get transactions
$transactionQuery = "
    SELECT * FROM money_transactions
    WHERE $whereClause
    ORDER BY transaction_date DESC, created_at DESC
    LIMIT $perPage OFFSET $offset
";
$transactionStmt = $conn->prepare($transactionQuery);
$transactionStmt->execute($queryParams);
$transactions = $transactionStmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categoriesStmt = $conn->prepare("SELECT DISTINCT category FROM money_transactions WHERE user_id = ? ORDER BY category");
$categoriesStmt->execute([$user_id]);
$userCategories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<style>
.balance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.balance-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid;
}

.balance-card.income { border-left-color: #28a745; }
.balance-card.expense { border-left-color: #dc3545; }
.balance-card.balance { border-left-color: #667eea; }

.balance-amount {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.balance-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.filters {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.transaction-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
    transition: transform 0.2s;
}

.transaction-card:hover {
    transform: translateY(-2px);
}

.transaction-card.income { border-left-color: #28a745; }
.transaction-card.expense { border-left-color: #dc3545; }

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.transaction-amount {
    font-size: 1.4rem;
    font-weight: bold;
}

.transaction-amount.income { color: #28a745; }
.transaction-amount.expense { color: #dc3545; }

.transaction-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.category-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    background: #f8f9fa;
    color: #495057;
    font-weight: 500;
}

.transaction-actions {
    display: flex;
    gap: 0.5rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
}

.pagination a {
    background: #f8f9fa;
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

@media (max-width: 768px) {
    .balance-cards {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .transaction-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .transaction-meta {
        flex-wrap: wrap;
    }
}
</style>

<div class="container">
    <h2><i class="fas fa-wallet"></i> Money Tracker</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Balance Summary -->
    <div class="balance-cards">
        <div class="balance-card income">
            <div class="balance-amount">RM <?php echo number_format($balance['total_income'], 2); ?></div>
            <div class="balance-label">Total Income</div>
        </div>
        <div class="balance-card expense">
            <div class="balance-amount">RM <?php echo number_format($balance['total_expenses'], 2); ?></div>
            <div class="balance-label">Total Expenses</div>
        </div>
        <div class="balance-card balance">
            <div class="balance-amount" style="color: <?php echo $balance['current_balance'] >= 0 ? '#28a745' : '#dc3545'; ?>">
                RM <?php echo number_format($balance['current_balance'], 2); ?>
            </div>
            <div class="balance-label">Current Balance</div>
        </div>
    </div>
    
    <!-- Monthly Summary -->
    <div class="balance-cards">
        <div class="balance-card income">
            <div class="balance-amount">RM <?php echo number_format($monthly['month_income'], 2); ?></div>
            <div class="balance-label">This Month Income</div>
        </div>
        <div class="balance-card expense">
            <div class="balance-amount">RM <?php echo number_format($monthly['month_expenses'], 2); ?></div>
            <div class="balance-label">This Month Expenses</div>
        </div>
        <div class="balance-card balance">
            <div class="balance-amount" style="color: <?php echo ($monthly['month_income'] - $monthly['month_expenses']) >= 0 ? '#28a745' : '#dc3545'; ?>">
                RM <?php echo number_format($monthly['month_income'] - $monthly['month_expenses'], 2); ?>
            </div>
            <div class="balance-label">This Month Net</div>
        </div>
    </div>
    
    <!-- Add Transaction Button -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <a href="add_transaction.php" class="btn btn-primary" style="margin-right: 1rem;">
            <i class="fas fa-plus"></i> Add Income
        </a>
        <a href="add_transaction.php?type=expense" class="btn btn-danger">
            <i class="fas fa-minus"></i> Add Expense
        </a>
    </div>
    
    <!-- Filters -->
    <div class="filters">
        <h4><i class="fas fa-filter"></i> Filter Transactions</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="form-group">
                    <label>Month</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($filterMonth); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="income" <?php echo $filterType === 'income' ? 'selected' : ''; ?>>Income</option>
                        <option value="expense" <?php echo $filterType === 'expense' ? 'selected' : ''; ?>>Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($userCategories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $filterCategory === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="index.php" class="btn" style="background: #6c757d; color: white; margin-left: 0.5rem;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <h3><i class="fas fa-history"></i> Transaction History (<?php echo number_format($totalTransactions); ?> records)</h3>
    
    <?php if (empty($transactions)): ?>
        <div style="text-align: center; padding: 3rem; color: #6c757d;">
            <i class="fas fa-money-bill-wave" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <h4>No transactions found</h4>
            <?php if ($filterMonth || $filterCategory || $filterType): ?>
                <p>No transactions match your current filters.</p>
                <p><strong>Current filters:</strong> 
                    <?php if ($filterMonth): ?>Month: <?php echo date('F Y', strtotime($filterMonth . '-01')); ?> | <?php endif; ?>
                    <?php if ($filterType): ?>Type: <?php echo ucfirst($filterType); ?> | <?php endif; ?>
                    <?php if ($filterCategory): ?>Category: <?php echo $filterCategory; ?><?php endif; ?>
                </p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-times"></i> Clear All Filters
                </a>
            <?php else: ?>
                <p>Start tracking your finances by adding your first transaction!</p>
                <a href="add_transaction.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Transaction
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($transactions as $transaction): ?>
            <div class="transaction-card <?php echo $transaction['type']; ?>">
                <div class="transaction-header">
                    <div>
                        <div class="transaction-amount <?php echo $transaction['type']; ?>">
                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>RM <?php echo number_format($transaction['amount'], 2); ?>
                        </div>
                        <div class="transaction-meta">
                            <span class="category-badge">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($transaction['category']); ?>
                            </span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($transaction['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="transaction-actions">
                        <a href="edit_transaction.php?id=<?php echo $transaction['transaction_id']; ?>" class="btn btn-warning btn-small">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="?delete=<?php echo $transaction['transaction_id']; ?><?php 
                            // Preserve current filters in delete URL
                            $filterParams = [];
                            if ($filterMonth) $filterParams[] = 'month=' . urlencode($filterMonth);
                            if ($filterType) $filterParams[] = 'type=' . urlencode($filterType);
                            if ($filterCategory) $filterParams[] = 'category=' . urlencode($filterCategory);
                            if (!empty($filterParams)) echo '&' . implode('&', $filterParams);
                        ?>" 
                           class="btn btn-danger btn-small"
                           onclick="return confirm('Are you sure you want to delete this transaction?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
                
                <?php if ($transaction['description']): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef;">
                        <strong>Description:</strong> <?php echo nl2br(htmlspecialchars($transaction['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&month=<?php echo urlencode($filterMonth); ?>&category=<?php echo urlencode($filterCategory); ?>&type=<?php echo urlencode($filterType); ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&month=<?php echo urlencode($filterMonth); ?>&category=<?php echo urlencode($filterCategory); ?>&type=<?php echo urlencode($filterType); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&month=<?php echo urlencode($filterMonth); ?>&category=<?php echo urlencode($filterCategory); ?>&type=<?php echo urlencode($filterType); ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>