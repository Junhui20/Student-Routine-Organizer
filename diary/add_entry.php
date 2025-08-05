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
            <button type="submit" class="btn btn-success" id="submit-btn">
                <i class="fas fa-save"></i> Save Entry
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<!-- Load existing content into rich text editor -->
<script type="text/javascript" src="?v=<?php echo time(); ?>">
// Cache busting - force reload
console.log('Script loaded at:', new Date().toISOString());

// Wait for rich text editor to be initialized
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for the rich text editor to initialize
    setTimeout(function() {
        const existingContent = <?php echo json_encode($_POST['content'] ?? ''); ?>;
        
        if (existingContent && typeof richTextEditor !== 'undefined' && richTextEditor) {
            richTextEditor.editor.innerHTML = existingContent;
            richTextEditor.syncContent();
            richTextEditor.updateWordCount();
        }
    }, 100);
});

// Character counter for title
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            const remaining = 200 - this.value.length;
            const small = this.parentNode.querySelector('small');
            if (small) {
                small.textContent = `${remaining} characters remaining`;
                if (remaining < 20) {
                    small.style.color = '#e74c3c';
                } else {
                    small.style.color = '#6c757d';
                }
            }
        });
    }
});

// Form submission handler to ensure content is synced
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Ensure rich text editor content is synced before submission
            if (typeof richTextEditor !== 'undefined' && richTextEditor) {
                richTextEditor.syncContent();
                
                // Check if content is empty
                const contentTextarea = document.getElementById('content-textarea');
                if (contentTextarea && !contentTextarea.value.trim()) {
                    e.preventDefault();
                    alert('Please enter some content in your diary entry.');
                    // Focus on the rich text editor
                    if (richTextEditor.editor) {
                        richTextEditor.editor.focus();
                    }
                    return false;
                }
                
                console.log('Form submitted with content:', contentTextarea ? contentTextarea.value : '');
            } else {
                console.error('Rich text editor not initialized');
                e.preventDefault();
                alert('Rich text editor is not properly initialized. Please refresh the page and try again.');
                return false;
            }
            
            // Additional validation for other required fields
            const title = document.getElementById('title');
            const mood = document.getElementById('mood');
            const entryDate = document.getElementById('entry_date');
            
            if (title && !title.value.trim()) {
                e.preventDefault();
                alert('Please enter a title for your diary entry.');
                title.focus();
                return false;
            }
            
            if (mood && !mood.value) {
                e.preventDefault();
                alert('Please select your mood.');
                mood.focus();
                return false;
            }
            
            if (entryDate && !entryDate.value) {
                e.preventDefault();
                alert('Please select a date for your entry.');
                entryDate.focus();
                return false;
            }
        });
    }
});

// Debug function to check rich text editor status
function debugRichTextEditor() {
    console.log('Rich text editor status:', {
        initialized: typeof richTextEditor !== 'undefined',
        editor: richTextEditor ? richTextEditor.editor : null,
        textarea: document.getElementById('content-textarea'),
        content: document.getElementById('content-textarea') ? document.getElementById('content-textarea').value : 'N/A'
    });
}

// Add debug button (remove in production)
document.addEventListener('DOMContentLoaded', function() {
    const debugBtn = document.createElement('button');
    debugBtn.type = 'button';
    debugBtn.textContent = 'Debug Editor';
    debugBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; background: red; color: white; padding: 5px;';
    debugBtn.onclick = debugRichTextEditor;
    document.body.appendChild(debugBtn);
});
</script>

    </main>
</body>
</html> 

<style>
/* Ensure submit button is clickable */
.btn {
    position: relative;
    z-index: 10;
}

#submit-btn {
    position: relative;
    z-index: 100;
    pointer-events: auto;
}

/* Ensure rich text editor doesn't overlap buttons */
.rich-text-editor {
    position: relative;
    z-index: 1;
}

/* Debug styles - remove in production */
.debug-info {
    position: fixed;
    top: 50px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px;
    border-radius: 5px;
    font-size: 12px;
    z-index: 10000;
}
</style> 