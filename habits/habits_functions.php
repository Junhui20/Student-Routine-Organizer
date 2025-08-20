<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DB connection (mysqli) ---
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "student_routine_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// ensure utf8mb4
$conn->set_charset('utf8mb4');

// central login guard
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}


function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function db(): mysqli {
    global $conn;
    return $conn;
}

// Ownership check 
function habit_belongs_to_user(int $habit_id): bool {
    $c = db();
    $uid = current_user_id();
    $stmt = $c->prepare("SELECT habit_id FROM habits WHERE habit_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $habit_id, $uid);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok;
}
