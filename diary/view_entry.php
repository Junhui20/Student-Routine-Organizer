<?php
require_once '../includes/SessionManager.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
if(!SessionManager::isAuthenticated()) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if entry ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$entry_id = $_GET['id'];
$entry = null;

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get the entry and verify it belongs to the current user
    $stmt = $conn->prepare("SELECT * FROM diary_entries WHERE entry_id = ? AND user_id = ?");
    $stmt->execute([$entry_id, SessionManager::getCurrentUserId()]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$entry) {
        header("Location: index.php");
        exit();
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>

<style>
.entry-view-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.entry-view-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.entry-view-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.entry-view-meta {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    font-size: 1.1rem;
    opacity: 0.95;
}

.entry-view-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.entry-view-mood {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.mood-emoji {
    font-size: 1.2rem;
}

.mood-text {
    font-weight: 500;
}

.entry-view-content {
    padding: 2.5rem;
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
}

.entry-view-content h1,
.entry-view-content h2,
.entry-view-content h3 {
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.entry-view-content blockquote {
    margin: 1.5rem 0;
    padding: 1.5rem 2rem;
    border-left: 4px solid #667eea;
    background: #f8f9fa;
    font-style: italic;
    border-radius: 0 10px 10px 0;
}

.entry-view-content .highlight-box,
.entry-view-content .quote-block {
    margin: 1.5rem 0;
}



.entry-view-actions {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.action-group {
    display: flex;
    gap: 0.5rem;
}

.btn-view {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary-view {
    background: #667eea;
    color: white;
    border: 1px solid #667eea;
}

.btn-primary-view:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary-view {
    background: white;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.btn-secondary-view:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
}

.btn-warning-view {
    background: #ffc107;
    color: #212529;
    border: 1px solid #ffc107;
}

.btn-warning-view:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .entry-view-container {
        margin: 1rem;
        border-radius: 10px;
    }
    
    .entry-view-header {
        padding: 1.5rem;
    }
    
    .entry-view-title {
        font-size: 2rem;
    }
    
    .entry-view-meta {
        flex-direction: column;
        gap: 1rem;
    }
    
    .entry-view-content {
        padding: 1.5rem;
        font-size: 1rem;
    }
    
    .entry-view-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-group {
        justify-content: center;
    }
    

}

/* Import rich text styles for content display */
.entry-view-content .emotion-happy { background-color: #fffacd; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-sad { background-color: #e6f3ff; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-excited { background-color: #fff0f5; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-stressed { background-color: #ffe4e1; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-grateful { background-color: #f0fff0; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-achievement { background-color: #fdf5e6; padding: 2px 4px; border-radius: 3px; }
.entry-view-content .emotion-reflection { background-color: #f5f5dc; padding: 2px 4px; border-radius: 3px; }
</style>

<div class="container" style="padding: 2rem 1rem;">
    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if($entry): ?>
        <div class="entry-view-container">
            <!-- Header -->
            <div class="entry-view-header">
                <h1 class="entry-view-title"><?php echo htmlspecialchars($entry['title']); ?></h1>
                <div class="entry-view-meta">
                    <div class="entry-view-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('l, F j, Y', strtotime($entry['entry_date'])); ?>
                    </div>
                    <div class="entry-view-mood">
                        <?php
                        $mood_emojis = [
                            'Happy' => '😊', 'Sad' => '😢', 'Excited' => '🎉', 'Stressed' => '😰',
                            'Calm' => '😌', 'Angry' => '😠', 'Grateful' => '🙏', 'Tired' => '😴'
                        ];
                        $mood_emoji = $mood_emojis[$entry['mood']] ?? '😊';
                        ?>
                        <span class="mood-emoji"><?php echo $mood_emoji; ?></span>
                        <span class="mood-text"><?php echo htmlspecialchars($entry['mood']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="entry-view-content">
                <?php 
                // Display rich text content with proper HTML rendering
                echo $entry['content']; 
                ?>
            </div>



            <!-- Actions -->
            <div class="entry-view-actions">
                <div class="action-group">
                    <a href="index.php" class="btn-view btn-secondary-view">
                        <i class="fas fa-arrow-left"></i> Back to Entries
                    </a>
                </div>
                
                <div class="action-group">
                    <a href="edit_entry.php?id=<?php echo $entry['entry_id']; ?>" class="btn-view btn-warning-view">
                        <i class="fas fa-edit"></i> Edit Entry
                    </a>
                    <a href="delete_entry.php?id=<?php echo $entry['entry_id']; ?>" 
                       class="btn-view btn-secondary-view"
                       onclick="return confirm('Are you sure you want to delete this entry?')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Image Modal for full-size viewing -->
<div id="imageModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%;">
        <img id="modalImage" style="width: 100%; height: auto; border-radius: 10px;">
        <button onclick="closeImageModal()" style="position: absolute; top: -40px; right: 0; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'block';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Close modal when clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>

 