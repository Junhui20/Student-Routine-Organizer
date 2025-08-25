<?php
// Quick fix version that handles the foreign key issue
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
$debugInfo = [];

// Ensure database connection
$conn = null;
try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    $debugInfo[] = "✅ Database connection successful";
} catch (Exception $e) {
    $debugInfo[] = "❌ Database connection failed: " . $e->getMessage();
    $message = "Database connection error: " . $e->getMessage();
    $messageType = "error";
}

// Get default type from URL parameter
$defaultType = isset($_GET['type']) && $_GET['type'] === 'expense' ? 'expense' : 'income';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugInfo[] = "📝 Form submitted via POST";
    
    $type = trim($_POST['type'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $transaction_date = trim($_POST['transaction_date'] ?? '');
    
    $debugInfo[] = "Form data: type='{$type}', amount='{$amount}', category='{$category}', date='{$transaction_date}'";
    
    // Validation
    if (empty($type) || empty($amount) || empty($category) || empty($transaction_date)) {
        $missingFields = [];
        if (empty($type)) $missingFields[] = 'type';
        if (empty($amount)) $missingFields[] = 'amount';
        if (empty($category)) $missingFields[] = 'category';
        if (empty($transaction_date)) $missingFields[] = 'date';
        
        $message = "Please fill in all required fields. Missing: " . implode(', ', $missingFields);
        $messageType = "error";
        $debugInfo[] = "❌ Validation failed: missing fields - " . implode(', ', $missingFields);
    } elseif (!in_array($type, ['income', 'expense'])) {
        $message = "Invalid transaction type: '$type'";
        $messageType = "error";
        $debugInfo[] = "❌ Invalid transaction type: '$type'";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $message = "Amount must be a valid positive number. Got: '$amount'";
        $messageType = "error";
        $debugInfo[] = "❌ Invalid amount: '$amount'";
    } elseif ((float)$amount > 999999.99) {
        $message = "Amount cannot exceed RM 999,999.99";
        $messageType = "error";
        $debugInfo[] = "❌ Amount too large: '$amount'";
    } else {
        if ($conn) {
            try {
                $debugInfo[] = "✅ Validation passed, attempting to insert into database";
                
                // First, ensure the user exists in this database
                $userCheckStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
                $userCheckStmt->execute([$user_id]);
                
                if ($userCheckStmt->rowCount() == 0) {
                    // User doesn't exist, let's create them
                    $debugInfo[] = "⚠️ User doesn't exist, creating user record";
                    
                    // Get user info from session
                    $username = SessionManager::getCurrentUsername() ?: 'Unknown';
                    $email = $username . '@example.com'; // Temporary email
                    $password = password_hash('temporary', PASSWORD_DEFAULT); // Temporary password
                    
                    $createUserStmt = $conn->prepare("INSERT INTO users (user_id, username, email, password) VALUES (?, ?, ?, ?)");
                    $createUserStmt->execute([$user_id, $username, $email, $password]);
                    $debugInfo[] = "✅ User created successfully";
                } else {
                    $debugInfo[] = "✅ User exists in database";
                }
                
                // Now insert the transaction
                $stmt = $conn->prepare("
                    INSERT INTO money_transactions (user_id, type, amount, category, description, transaction_date) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $executeResult = $stmt->execute([$user_id, $type, (float)$amount, $category, $description, $transaction_date]);
                
                if ($executeResult) {
                    $message = ucfirst($type) . " of RM " . number_format((float)$amount, 2) . " added successfully!";
                    $messageType = "success";
                    $debugInfo[] = "✅ Transaction inserted successfully";
                    
                    // Clear form data on success
                    $type = $defaultType;
                    $amount = '';
                    $category = '';
                    $description = '';
                    $transaction_date = date('Y-m-d');
                } else {
                    $message = "Failed to add transaction. Database execute returned false.";
                    $messageType = "error";
                    $debugInfo[] = "❌ Database execute returned false";
                }
            } catch (Exception $e) {
                $message = "Database error: " . $e->getMessage();
                $messageType = "error";
                $debugInfo[] = "❌ Database exception: " . $e->getMessage();
                ErrorHandler::logApplicationError("Money Tracker add error: " . $e->getMessage(), 'MONEY_TRACKER');
            }
        } else {
            $message = "No database connection available";
            $messageType = "error";
            $debugInfo[] = "❌ No database connection for insert";
        }
    }
} else {
    // Initialize form data
    $type = $defaultType;
    $amount = '';
    $category = '';
    $description = '';
    $transaction_date = date('Y-m-d');
    $debugInfo[] = "📋 Form initialized with default values";
}

// Get categories from database - FIXED to not use is_active column
$incomeCategories = [];
$expenseCategories = [];

if ($conn) {
    try {
        // Simplified query without is_active column
        $categoriesStmt = $conn->prepare("SELECT * FROM money_categories ORDER BY type, category_name");
        $categoriesStmt->execute();
        $allCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debugInfo[] = "📊 Found " . count($allCategories) . " categories in database";
        
        foreach ($allCategories as $cat) {
            if ($cat['type'] === 'income') {
                $incomeCategories[] = $cat;
            } else {
                $expenseCategories[] = $cat;
            }
        }
        
        $debugInfo[] = "📊 Income categories: " . count($incomeCategories);
        $debugInfo[] = "📊 Expense categories: " . count($expenseCategories);
        
    } catch (Exception $e) {
        $debugInfo[] = "❌ Categories fetch error: " . $e->getMessage();
        ErrorHandler::logApplicationError("Money Tracker categories fetch error: " . $e->getMessage(), 'MONEY_TRACKER');
        
        // Provide fallback categories
        $incomeCategories = [
            ['category_name' => 'Salary', 'icon' => 'fas fa-briefcase', 'color' => '#28a745'],
            ['category_name' => 'Freelance', 'icon' => 'fas fa-laptop', 'color' => '#17a2b8'],
            ['category_name' => 'Other Income', 'icon' => 'fas fa-plus-circle', 'color' => '#6f42c1']
        ];
        $expenseCategories = [
            ['category_name' => 'Food & Dining', 'icon' => 'fas fa-utensils', 'color' => '#dc3545'],
            ['category_name' => 'Transportation', 'icon' => 'fas fa-car', 'color' => '#fd7e14'],
            ['category_name' => 'Shopping', 'icon' => 'fas fa-shopping-bag', 'color' => '#e83e8c'],
            ['category_name' => 'Other Expense', 'icon' => 'fas fa-minus-circle', 'color' => '#adb5bd']
        ];
        $debugInfo[] = "📊 Using fallback categories";
    }
} else {
    $debugInfo[] = "❌ No database connection for categories";
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

.debug-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 1rem;
    margin-bottom: 1rem;
    font-family: monospace;
    font-size: 0.8rem;
    max-height: 300px;
    overflow-y: auto;
}

.debug-info h4 {
    margin: 0 0 0.5rem 0;
    color: #495057;
}

.debug-info ul {
    margin: 0;
    padding-left: 1.5rem;
}

.debug-info li {
    margin-bottom: 0.25rem;
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
                        <?php if (!empty($incomeCategories)): ?>
                            <?php foreach ($incomeCategories as $cat): ?>
                                <div class="category-option <?php echo $category === $cat['category_name'] ? 'selected' : ''; ?>" 
                                     onclick="selectCategory('<?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES); ?>')">
                                    <div class="category-icon" style="color: <?php echo htmlspecialchars($cat['color']); ?>">
                                        <i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                    </div>
                                    <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; text-align: center; grid-column: 1/-1;">No income categories available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Expense Categories -->
                <div id="expenseCategories" style="<?php echo $type === 'expense' ? '' : 'display: none;'; ?>">
                    <div class="category-grid">
                        <?php if (!empty($expenseCategories)): ?>
                            <?php foreach ($expenseCategories as $cat): ?>
                                <div class="category-option <?php echo $category === $cat['category_name'] ? 'selected' : ''; ?>" 
                                     onclick="selectCategory('<?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES); ?>')">
                                    <div class="category-icon" style="color: <?php echo htmlspecialchars($cat['color']); ?>">
                                        <i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                    </div>
                                    <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; text-align: center; grid-column: 1/-1;">No expense categories available</p>
                        <?php endif; ?>
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
    console.log('Selecting type:', type);
    
    // Update visual selection
    document.querySelectorAll('.type-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.querySelector('.type-option.' + type).classList.add('selected');
    
    // Update hidden field
    document.getElementById('typeField').value = type;
    
    // Show/hide appropriate categories
    const incomeDiv = document.getElementById('incomeCategories');
    const expenseDiv = document.getElementById('expenseCategories');
    
    if (type === 'income') {
        incomeDiv.style.display = 'block';
        expenseDiv.style.display = 'none';
    } else {
        incomeDiv.style.display = 'none';
        expenseDiv.style.display = 'block';
    }
    
    // Clear category selection
    document.querySelectorAll('.category-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.getElementById('categoryField').value = '';
}

function selectCategory(categoryName) {
    console.log('Selecting category:', categoryName);
    
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
    
    console.log('Form submission validation:', {type, amount, category, date});
    
    if (!type || !amount || !category || !date) {
        e.preventDefault();
        const missing = [];
        if (!type) missing.push('type');
        if (!amount) missing.push('amount');
        if (!category) missing.push('category');
        if (!date) missing.push('date');
        
        alert('Please fill in all required fields. Missing: ' + missing.join(', '));
        return false;
    }
    
    if (parseFloat(amount) <= 0) {
        e.preventDefault();
        alert('Amount must be greater than 0.');
        return false;
    }
});

// Initialize the correct category display on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('typeField').value;
    console.log('Initializing with type:', currentType);
    selectType(currentType);
});
</script>

<?php include '../includes/footer.php'; ?>