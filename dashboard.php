<?php
session_start();
if (!isset($_SESSION['role_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role_id'] == 1) {
    // Admin → admin_dashboard.php
    header("Location: admin_dashboard.php");
    exit;
} elseif ($_SESSION['role_id'] == 2) {
    // Merchant → merchant.php
    header("Location: merchant.php");
    exit;
} elseif ($_SESSION['role_id'] == 3) {
    // User → user.php
    header("Location: user.php");
    exit;
} else {
    echo "<h1>Invalid Role</h1>";
}
?>