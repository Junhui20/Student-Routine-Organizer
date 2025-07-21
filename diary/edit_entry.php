<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if entry ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$entry_id = $_GET['id'];
$error = '';
$entry = null;

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get the entry and verify it belongs to the current user
    $stmt = $conn->prepare("SELECT * FROM diary_entries WHERE entry_id = ? AND user_id = ?");
    $stmt->execute([$entry_id, $_SESSION['user_id']]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$entry) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        try {
            $stmt = $conn->prepare("UPDATE diary_entries SET title = ?, content = ?, mood = ?, entry_date = ? WHERE entry_id = ? AND user_id = ?");
            
            if($stmt->execute([$title, $content, $mood, $entry_date, $entry_id, $_SESSION['user_id']])) {
                header("Location: index.php?updated=1");
                exit();
            } else {
                $error = "Failed to update entry. Please try again.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-edit"></i> Edit Entry</h2>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Entries
        </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if($entry): ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Entry Title</label>
            <input type="text" id="title" name="title" 
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($entry['title']); ?>" 
                   placeholder="Give your entry a title..." 
                   maxlength="200" required>
            <small class="text-muted">Maximum 200 characters</small>
        </div>

        <div class="form-group">
            <label for="entry_date"><i class="fas fa-calendar"></i> Date</label>
            <input type="date" id="entry_date" name="entry_date" 
                   value="<?php echo isset($_POST['entry_date']) ? $_POST['entry_date'] : $entry['entry_date']; ?>" required>
        </div>

        <div class="form-group">
            <label for="mood"><i class="fas fa-heart"></i> How are you feeling?</label>
            <select id="mood" name="mood" required>
                <option value="">Select your mood...</option>
                <?php
                $moods = ['Happy', 'Sad', 'Excited', 'Stressed', 'Calm', 'Angry', 'Grateful', 'Tired'];
                $mood_emojis = ['😊', '😢', '🎉', '😰', '😌', '😠', '🙏', '😴'];
                $current_mood = isset($_POST['mood']) ? $_POST['mood'] : $entry['mood'];
                
                for($i = 0; $i < count($moods); $i++) {
                    $selected = ($current_mood == $moods[$i]) ? 'selected' : '';
                    echo "<option value='{$moods[$i]}' {$selected}>{$mood_emojis[$i]} {$moods[$i]}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="content"><i class="fas fa-edit"></i> What's on your mind?</label>
            <?php include 'includes/rich_text_editor.php'; ?>
            <small class="text-muted">Express yourself freely - this is your personal space. Use the rich text editor above to format your thoughts beautifully!</small>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Update Entry
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Load existing content into rich text editor -->
<script>
// Load existing content for editing
document.addEventListener('DOMContentLoaded', function() {
    const existingContent = <?php echo json_encode($_POST['content'] ?? $entry['content'] ?? ''); ?>;
    
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

// Set initial textarea height
window.addEventListener('load', function() {
    const textarea = document.getElementById('content');
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
});
</script>

 