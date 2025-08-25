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

// Get default type from URL parameter
$defaultType = isset($_GET['type']) && $_GET['type'] === 'expense' ? 'expense' : 'income';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type']);
    $amount = trim($_POST['amount']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $transaction_date = trim($_POST['transaction_date']);
    
    // Validation
    if (empty($type) || empty($amount) || empty($category) || empty($transaction_date)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif (!in_array($type, ['income', 'expense'])) {
        $message = "Invalid transaction type.";
        $messageType = "error";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $message = "Amount must be a valid positive number.";
        $messageType = "error";
    } elseif ((float)$amount > 999999.99) {
        $message = "Amount cannot exceed RM 999,999.99.";
        $messageType = "error";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO money_transactions (user_id, type, amount, category, description, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $type, (float)$amount, $category, $description, $transaction_date])) {
                $message = ucfirst($type) . " of RM " . number_format((float)$amount, 2) . " added successfully!";
                $messageType = "success";
                
                // Clear form data on success
                $type = $defaultType;
                $amount = '';
                $category = '';
                $description = '';
                $transaction_date = date('Y-m-d');
            } else {
                $message = "Failed to add transaction. Please try again.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            ErrorHandler::logApplicationError("Money Tracker add error: " . $e->getMessage(), 'MONEY_TRACKER');
            $message = "An error occurred while adding the transaction.";
            $messageType = "error";
        }
    }
} else {
    // Initialize form data
    $type = $defaultType;
    $amount = '';
    $category = '';
    $description = '';
    $transaction_date = date('Y-m-d');
}

// Get categories from database
try {
    $categoriesStmt = $conn->prepare("SELECT * FROM money_categories ORDER BY type, category_name");
    $categoriesStmt->execute();
    $allCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $incomeCategories = array_filter($allCategories, function($cat) { return $cat['type'] === 'income'; });
    $expenseCategories = array_filter($allCategories, function($cat) { return $cat['type'] === 'expense'; });
} catch (Exception $e) {
    ErrorHandler::logApplicationError("Money Tracker categories fetch error: " . $e->getMessage(), 'MONEY_TRACKER');
    $incomeCategories = [];
    $expenseCategories = [];
}

include '../includes/header.php';
?>

<style>
.form-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.type-option {
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}

.type-option:hover {
    border-color: #667eea;
}

.type-option.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.type-option.income.selected {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.type-option.expense.selected {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
}

.type-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.category-option {
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}

.category-option:hover {
    border-color: #667eea;
    transform: translateY(-2px);
}

.category-option.selected {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.category-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.amount-input {
    position: relative;
}

.currency-symbol {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-weight: bold;
}

.amount-field {
    padding-left: 3rem !important;
    font-size: 1.2rem;
    font-weight: bold;
}

.quick-amounts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.5rem;
    margin-top: 1rem;
}

.quick-amount {
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    font-size: 0.9rem;
}

.quick-amount:hover {
    border-color: #667eea;
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .type-selector {
        grid-template-columns: 1fr;
    }
    
    .category-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    
    .quick-amounts {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="container">
    <div class="form-container">
        <h2><i class="fas fa-plus-circle"></i> Add Transaction</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="transactionForm">
            <!-- Transaction Type Selector -->
            <div class="form-group">
                <label>Transaction Type *</label>
                <div class="type-selector">
                    <div class="type-option income <?php echo $type === 'income' ? 'selected' : ''; ?>" onclick="selectType('income')">
                        <div class="type-icon"><i class="fas fa-plus-circle"></i></div>
                        <div><strong>Income</strong></div>
                        <div style="font-size: 0.9rem;">Money you receive</div>
                    </div>
                    <div class="type-option expense <?php echo $type === 'expense' ? 'selected' : ''; ?>" onclick="selectType('expense')">
                        <div class="type-icon"><i class="fas fa-minus-circle"></i></div>
                        <div><strong>Expense</strong></div>
                        <div style="font-size: 0.9rem;">Money you spend</div>
                    </div>
                </div>
                <input type="hidden" name="type" id="typeField" value="<?php echo htmlspecialchars($type); ?>" required>
            </div>
            
            <!-- Amount -->
            <div class="form-group">
                <label for="amount">Amount (RM) *</label>
                <div class="amount-input">
                    <div class="currency-symbol">RM</div>
                    <input 
                        type="number" 
                        id="amount" 
                        name="amount" 
                        class="form-control amount-field"
                        step="0.01" 
                        min="0.01" 
                        max="999999.99"
                        placeholder="0.00"
                        value="<?php echo htmlspecialchars($amount); ?>"
                        required
                    >
                </div>
                
                <!-- Quick Amount Buttons -->
                <div class="quick-amounts">
                    <div class="quick-amount" onclick="setAmount(5)">RM 5</div>
                    <div class="quick-amount" onclick="setAmount(10)">RM 10</div>
                    <div class="quick-amount" onclick="setAmount(20)">RM 20</div>
                    <div class="quick-amount" onclick="setAmount(50)">RM 50</div>
                    <div class="quick-amount" onclick="setAmount(100)">RM 100</div>
                    <div class="quick-amount" onclick="setAmount(500)">RM 500</div>
                </div>
            </div>
            
            <!-- Category Selection -->
            <div class="form-group">
                <label>Category *</label>
                
                <!-- Income Categories -->
                <div id="incomeCategories" style="<?php echo $type === 'income' ? '' : 'display: none;'; ?>">
                    <div class="category-grid">
                        <?php foreach ($incomeCategories as $cat): ?>
                            <div class="category-option <?php echo $category === $cat['category_name'] ? 'selected' : ''; ?>" 
                                 onclick="selectCategory('<?php echo htmlspecialchars($cat['category_name']); ?>')">
                                <div class="category-icon" style="color: <?php echo $cat['color']; ?>">
                                    <i class="<?php echo $cat['icon']; ?>"></i>
                                </div>
                                <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Expense Categories -->
                <div id="expenseCategories" style="<?php echo $type === 'expense' ? '' : 'display: none;'; ?>">
                    <div class="category-grid">
                        <?php foreach ($expenseCategories as $cat): ?>
                            <div class="category-option <?php echo $category === $cat['category_name'] ? 'selected' : ''; ?>" 
                                 onclick="selectCategory('<?php echo htmlspecialchars($cat['category_name']); ?>')">
                                <div class="category-icon" style="color: <?php echo $cat['color']; ?>">
                                    <i class="<?php echo $cat['icon']; ?>"></i>
                                </div>
                                <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <input type="hidden" name="category" id="categoryField" value="<?php echo htmlspecialchars($category); ?>" required>
            </div>
            
            <!-- Date -->
            <div class="form-group">
                <label for="transaction_date">Date *</label>
                <input 
                    type="date" 
                    id="transaction_date" 
                    name="transaction_date" 
                    class="form-control"
                    value="<?php echo htmlspecialchars($transaction_date); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    required
                >
            </div>
            
            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-control"
                    rows="3"
                    placeholder="Optional: Add notes about this transaction..."
                ><?php echo htmlspecialchars($description); ?></textarea>
                <small class="text-muted">Optional: Add details about this transaction</small>
            </div>
            
            <!-- Submit Buttons -->
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="margin-right: 1rem;">
                    <i class="fas fa-save"></i> Add Transaction
                </button>
                <a href="index.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function selectType(type) {
    // Update visual selection
    document.querySelectorAll('.type-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.querySelector('.type-option.' + type).classList.add('selected');
    
    // Update hidden field
    document.getElementById('typeField').value = type;
    
    // Show/hide appropriate categories
    document.getElementById('incomeCategories').style.display = type === 'income' ? 'block' : 'none';
    document.getElementById('expenseCategories').style.display = type === 'expense' ? 'block' : 'none';
    
    // Clear category selection
    document.querySelectorAll('.category-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.getElementById('categoryField').value = '';
}

function selectCategory(categoryName) {
    // Update visual selection
    document.querySelectorAll('.category-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    
    // Update hidden field
    document.getElementById('categoryField').value = categoryName;
}

function setAmount(amount) {
    document.getElementById('amount').value = amount.toFixed(2);
}

// Form validation
document.getElementById('transactionForm').addEventListener('submit', function(e) {
    const type = document.getElementById('typeField').value;
    const amount = document.getElementById('amount').value;
    const category = document.getElementById('categoryField').value;
    const date = document.getElementById('transaction_date').value;
    
    if (!type || !amount || !category || !date) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (parseFloat(amount) <= 0) {
        e.preventDefault();
        alert('Amount must be greater than 0.');
        return false;
    }
    
    if (parseFloat(amount) > 999999.99) {
        e.preventDefault();
        alert('Amount cannot exceed RM 999,999.99.');
        return false;
    }
});

// Auto-focus amount field
document.getElementById('amount').focus();
</script>

<?php include '../includes/footer.php'; ?>