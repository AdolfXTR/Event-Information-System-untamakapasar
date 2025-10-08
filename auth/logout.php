<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Log the logout activity
if (is_logged_in()) {
    log_activity($conn, $_SESSION['user_id'], 'User Logout', 'User logged out');
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
redirect('login.php');
?>