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
$note = trim($_POST['note'] ?? '');
$duration_minutes = isset($_POST['duration_minutes']) && $_POST['duration_minutes'] !== '' ? (int) $_POST['duration_minutes'] : null;

if ($habit_id <= 0 || !in_array($action, ['mark', 'unmark'], true)) {
    header("Location: index.php");
    exit();
}

if (!habit_belongs_to_user($habit_id)) {
    header("Location: index.php");
    exit();
}

$c = db();

if ($action === 'mark') {
    // Check if a log already exists today
    $stmtCheck = $c->prepare("SELECT duration_minutes FROM habit_logs WHERE habit_id=? AND log_date=CURDATE()");
    $stmtCheck->bind_param("i", $habit_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $existing = $res->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        // update existing log
        $new_duration = $existing['duration_minutes'] ?? 0;
        if ($duration_minutes !== null) {
            $new_duration += $duration_minutes; // accumulate timer minutes
        }
        $stmt = $c->prepare("UPDATE habit_logs SET status=?, note=?, duration_minutes=? WHERE habit_id=? AND log_date=CURDATE()");
        $stmt->bind_param("siii", $status, $note, $new_duration, $habit_id);
    } else {
        // insert new log
        $stmt = $c->prepare("INSERT INTO habit_logs (habit_id, log_date, status, note, duration_minutes) VALUES (?, CURDATE(), ?, ?, ?)");
        // If duration is null, set to 0
        $dur = $duration_minutes ?? 0;
        $stmt->bind_param("issi", $habit_id, $status, $note, $dur);
    }

    if ($stmt->execute()) {
        header("Location: index.php?marked=1");
        exit();
    } else {
        header("Location: index.php?error=logfail");
        exit();
    }
    $stmt->close();
}

if ($action === 'unmark') {
    $stmt = $c->prepare("DELETE FROM habit_logs WHERE habit_id=? AND log_date=CURDATE()");
    $stmt->bind_param("i", $habit_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?unmarked=1");
    exit();
}
