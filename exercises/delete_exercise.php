<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

// Get exercise ID from URL
$exercise_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$exercise_id) {
    header('Location: index.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // First, verify the exercise belongs to the current user
    $stmt = $conn->prepare("SELECT exercise_type, exercise_date FROM exercise_tracker WHERE exercise_id = ? AND user_id = ?");
    $stmt->execute([$exercise_id, $_SESSION['user_id']]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exercise) {
        header('Location: index.php');
        exit();
    }
    
    // Delete the exercise
    $stmt = $conn->prepare("DELETE FROM exercise_tracker WHERE exercise_id = ? AND user_id = ?");
    
    if ($stmt->execute([$exercise_id, $_SESSION['user_id']])) {
        header('Location: index.php?success=deleted');
    } else {
        header('Location: index.php?error=delete_failed');
    }
    
} catch(Exception $e) {
    header('Location: index.php?error=database_error');
}

exit();
?>