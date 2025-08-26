<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$habit_id = isset($_POST['habit_id']) ? (int) $_POST['habit_id'] : 0;
$action = $_POST['action'] ?? 'mark';
$status = $_POST['status'] ?? 'done';
$duration_minutes = isset($_POST['duration_minutes']) && $_POST['duration_minutes'] !== ''
    ? (int) $_POST['duration_minutes']
    : null;

if ($habit_id <= 0 || !in_array($action, ['mark', 'unmark'], true)) {
    header("Location: index.php");
    exit();
}

// Ownership check
if (!habit_belongs_to_user($habit_id)) {
    header("Location: index.php");
    exit();
}

// Active & end_date check
if (!habit_is_active($habit_id)) {
    header("Location: index.php?error=inactive_or_ended");
    exit();
}

$c = db();

if ($action === 'mark') {
    // Check if log exists for today
    $stmtCheck = $c->prepare("SELECT duration_minutes FROM habit_logs WHERE habit_id=? AND log_date=CURDATE()");
    $stmtCheck->bind_param("i", $habit_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $existing = $res->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        // Update existing
        $new_duration = (int)$existing['duration_minutes'];
        if ($duration_minutes !== null) {
            $new_duration += $duration_minutes;
        }
        $stmt = $c->prepare("UPDATE habit_logs SET status=?, duration_minutes=? WHERE habit_id=? AND log_date=CURDATE()");
        $stmt->bind_param("sii", $status, $new_duration, $habit_id);
    } else {
        // Insert new
        $dur = $duration_minutes ?? 0;
        $stmt = $c->prepare("INSERT INTO habit_logs (habit_id, log_date, status, duration_minutes) VALUES (?, CURDATE(), ?, ?)");
        $stmt->bind_param("isi", $habit_id, $status, $dur);
    }

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: index.php?marked=1");
        exit();
    } else {
        error_log("toggle_today.php: stmt->execute() failed: " . $stmt->error);
        $stmt->close();
        header("Location: index.php?error=logfail");
        exit();
    }
}

if ($action === 'unmark') {
    $stmt = $c->prepare("DELETE FROM habit_logs WHERE habit_id=? AND log_date=CURDATE()");
    $stmt->bind_param("i", $habit_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?unmarked=1");
    exit();
}
