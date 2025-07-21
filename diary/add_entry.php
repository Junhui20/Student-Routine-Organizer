<?php
require_once '../includes/SessionManager.php';
require_once '../includes/ErrorHandler.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    ErrorHandler::logApplicationError('Session initialization failed in add_entry.php', 'SESSION', ['error' => $e->getMessage()]);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
if(!SessionManager::isAuthenticated()) {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';
    
    try {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $mood = $_POST['mood'];
        $entry_date = $_POST['entry_date'];
        
        // Validation
        if(empty($title) || empty($content) || empty($mood) || empty($entry_date)) {
            $error = "All fields are required";
        } elseif(strlen($title) > 200) {
            $error = "Title must be 200 characters or less";
        } else {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Insert diary entry
            $stmt = $conn->prepare("INSERT INTO diary_entries (user_id, title, content, mood, entry_date) VALUES (?, ?, ?, ?, ?)");
            
            if($stmt->execute([SessionManager::getCurrentUserId(), $title, $content, $mood, $entry_date])) {
                $success = "Entry added successfully!";
                
                // Redirect on success
                header("Location: index.php?added=1");
                exit();
            } else {
                $error = "Failed to add entry. Please try again.";
                ErrorHandler::logDatabaseError('Failed to insert diary entry', 
                    'INSERT INTO diary_entries (user_id, title, content, mood, entry_date) VALUES (?, ?, ?, ?, ?)', 
                    [SessionManager::getCurrentUserId(), $title, $content, $mood, $entry_date]
                );
            }
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        ErrorHandler::logDatabaseError('Database error in add_entry.php', '', ['error' => $e->getMessage()]);
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
        ErrorHandler::logApplicationError('General error in add_entry.php', 'APPLICATION', ['error' => $e->getMessage()]);
    }
}

// Set default date to today
$default_date = date('Y-m-d');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-plus"></i> Write New Entry</h2>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Entries
        </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- University Features Notice -->
    <div class="alert" style="background: #e3f2fd; border: 1px solid #2196f3; color: #1976d2; margin-bottom: 2rem;">
        <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-graduation-cap"></i> University Course Features</h4>
        <p style="margin: 0; font-size: 0.9rem;">This form demonstrates: <strong>Rich Text Editing</strong>, <strong>Advanced Error Handling</strong>, <strong>Session Management</strong>, and <strong>Cookie Integration</strong></p>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Entry Title</label>
            <input type="text" id="title" name="title" 
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                   placeholder="Give your entry a title..." 
                   maxlength="200" required>
            <small class="text-muted">Maximum 200 characters</small>
        </div>

        <div class="form-group">
            <label for="entry_date"><i class="fas fa-calendar"></i> Date</label>
            <input type="date" id="entry_date" name="entry_date" 
                   value="<?php echo isset($_POST['entry_date']) ? $_POST['entry_date'] : $default_date; ?>" required>
        </div>

        <div class="form-group">
            <label for="mood"><i class="fas fa-heart"></i> How are you feeling?</label>
            <select id="mood" name="mood" required>
                <option value="">Select your mood...</option>
                <option value="Happy" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Happy') ? 'selected' : ''; ?>>😊 Happy</option>
                <option value="Sad" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Sad') ? 'selected' : ''; ?>>😢 Sad</option>
                <option value="Excited" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Excited') ? 'selected' : ''; ?>>🎉 Excited</option>
                <option value="Stressed" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Stressed') ? 'selected' : ''; ?>>😰 Stressed</option>
                <option value="Calm" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Calm') ? 'selected' : ''; ?>>😌 Calm</option>
                <option value="Angry" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Angry') ? 'selected' : ''; ?>>😠 Angry</option>
                <option value="Grateful" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Grateful') ? 'selected' : ''; ?>>🙏 Grateful</option>
                <option value="Tired" <?php echo (isset($_POST['mood']) && $_POST['mood'] == 'Tired') ? 'selected' : ''; ?>>😴 Tired</option>
            </select>
        </div>

        <div class="form-group">
            <label for="content"><i class="fas fa-edit"></i> What's on your mind?</label>
            <?php include 'includes/rich_text_editor.php'; ?>
            <small class="text-muted">Express yourself freely - this is your personal space. Use the rich text editor above to format your thoughts beautifully!</small>
        </div>



        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save Entry
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>



<!-- Load existing content into rich text editor -->
<script>
// Load existing content if present (for error handling or edit mode)
document.addEventListener('DOMContentLoaded', function() {
    const existingContent = <?php echo json_encode($_POST['content'] ?? ''); ?>;
    
    if (existingContent && richTextEditor) {
        richTextEditor.editor.innerHTML = existingContent;
        richTextEditor.syncContent();
        richTextEditor.updateWordCount();
    }
});

// Character counter for title
document.getElementById('title').addEventListener('input', function() {
    const remaining = 200 - this.value.length;
    const small = this.parentNode.querySelector('small');
    small.textContent = `${remaining} characters remaining`;
    if (remaining < 20) {
        small.style.color = '#e74c3c';
    } else {
        small.style.color = '#6c757d';
    }
});

</script>

    </main>
</body>
</html> 