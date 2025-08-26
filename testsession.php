<?php
session_start();

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = time();
    echo "✅ Session started. First time visit. Value set: " . $_SESSION['test'];
} else {
    echo "✅ Session is working! Stored value: " . $_SESSION['test'];
}
