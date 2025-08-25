<?php
require_once '../config/database.php';
require_once '../includes/AdminManager.php';

// Initialize admin manager
$db = new Database();
$adminManager = new AdminManager($db->getConnection());

// Perform logout
$adminManager->logoutAdmin();

// Redirect to login page with logout message
header('Location: login.php?logout=1');
exit();
?>