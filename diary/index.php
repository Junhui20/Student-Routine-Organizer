<?php
// Check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-journal-whills"></i> My Diary Entries</h2>
        <div style="display: flex; gap: 1rem;">
            <a href="calendar_view.php" class="btn btn-secondary">
                <i class="fas fa-calendar-alt"></i> Calendar View
            </a>
            <a href="add_entry.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Entry
            </a>
        </div>
    </div>

    <!-- Search and Filter Component -->
    <?php include 'includes/search_filter_component.php'; ?>

    <?php
    // Handle delete success message
    if(isset($_GET['deleted']) && $_GET['deleted'] == '1') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Entry deleted successfully!</div>';
    }
    
    // Handle update success message
    if(isset($_GET['updated']) && $_GET['updated'] == '1') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Entry updated successfully!</div>';
    }
    
    // Handle add success message
    if(isset($_GET['added']) && $_GET['added'] == '1') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Entry added successfully!</div>';
    }
    ?>

    <!-- Entries Container for Search/Filter Results -->
    <div class="entries-container" id="entries-container">
    <?php
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get all entries for the current user, ordered by date (newest first)
        $stmt = $conn->prepare("
            SELECT entry_id, title, content, mood, entry_date, created_at 
            FROM diary_entries 
            WHERE user_id = ? 
            ORDER BY entry_date DESC, created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if(count($entries) == 0) {
            echo '<div class="container text-center">';
            echo '<div class="feature-icon" style="margin: 3rem 0;"><i class="fas fa-book-open" style="font-size: 4rem; color: #ccc;"></i></div>';
            echo '<h3 style="color: #999;">No entries yet</h3>';
            echo '<p style="color: #999; margin-bottom: 2rem;">Start your journaling journey by writing your first entry!</p>';
            echo '<a href="add_entry.php" class="btn btn-primary"><i class="fas fa-plus"></i> Write First Entry</a>';
            echo '</div>';
        } else {
            foreach($entries as $entry) {
                $mood_class = 'mood-' . strtolower($entry['mood']);
                
                // Handle rich text content - strip HTML for preview, but preserve basic formatting
                $content_preview = strip_tags($entry['content'], '<b><i><u><strong><em>');
                $short_content = strlen($content_preview) > 200 ? substr($content_preview, 0, 200) . '...' : $content_preview;
                
                echo '<div class="entry-card">';
                echo '<div class="entry-header">';
                echo '<h3 class="entry-title">' . htmlspecialchars($entry['title']) . '</h3>';
                echo '<div class="entry-meta">';
                echo '<span><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($entry['entry_date'])) . '</span>';
                echo '<span class="mood-badge ' . $mood_class . '">' . htmlspecialchars($entry['mood']) . '</span>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="entry-content">';
                echo $short_content; // Display rich text preview
                echo '</div>';
                
                echo '<div class="entry-actions">';
                echo '<a href="view_entry.php?id=' . $entry['entry_id'] . '" class="btn btn-primary btn-small">';
                echo '<i class="fas fa-eye"></i> Read More';
                echo '</a>';
                echo '<a href="edit_entry.php?id=' . $entry['entry_id'] . '" class="btn btn-warning btn-small">';
                echo '<i class="fas fa-edit"></i> Edit';
                echo '</a>';
                echo '<a href="delete_entry.php?id=' . $entry['entry_id'] . '" class="btn btn-danger btn-small" onclick="return confirm(\'Are you sure you want to delete this entry?\')">';
                echo '<i class="fas fa-trash"></i> Delete';
                echo '</a>';
                echo '</div>';
                
                echo '</div>'; // Close entry-card
            }
        }
        
    } catch(PDOException $e) {
        echo '<div class="alert alert-error">Error loading entries: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    </div> <!-- End entries-container -->
</div>

 