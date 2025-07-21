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

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify the entry belongs to the current user before deleting
    $stmt = $conn->prepare("SELECT entry_id FROM diary_entries WHERE entry_id = ? AND user_id = ?");
    $stmt->execute([$entry_id, $_SESSION['user_id']]);
    
    if($stmt->rowCount() > 0) {
        // Entry exists and belongs to user, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM diary_entries WHERE entry_id = ? AND user_id = ?");
        
        if($delete_stmt->execute([$entry_id, $_SESSION['user_id']])) {
            header("Location: index.php?deleted=1");
            exit();
        } else {
            header("Location: index.php?error=delete_failed");
            exit();
        }
    } else {
        // Entry doesn't exist or doesn't belong to user
        header("Location: index.php");
        exit();
    }
    
} catch(PDOException $e) {
    header("Location: index.php?error=database");
    exit();
}
?> 