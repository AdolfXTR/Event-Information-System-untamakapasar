<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password
define('DB_NAME', 'event_system_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure session is active
if (!isset($_SESSION)) {
    session_start();
}

// Base URL Configuration
define('BASE_URL', 'http://localhost/event-information-system/');

// Email Configuration (for future use with PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_PORT', 587);

// Timezone
date_default_timezone_set('Asia/Manila');
?>