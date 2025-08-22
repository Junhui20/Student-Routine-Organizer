<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$habit_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($habit_id <= 0 || !habit_belongs_to_user($habit_id)) {
    header("Location: index.php");
    exit();
}

$c = db();
$stmt = $c->prepare("DELETE FROM habits WHERE habit_id = ? AND user_id = ?");
$uid = current_user_id();
$stmt->bind_param("ii", $habit_id, $uid);
$stmt->execute();
$stmt->close();

header("Location: index.php?deleted=1");
exit();
