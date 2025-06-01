<?php
// logout.php - Logout handler
require_once 'config/database.php';
require_once 'includes/auth.php';

// Logout the user
logout_user();

// Redirect to login page with success message
header('Location: login.php?logout=1');
exit;
?>