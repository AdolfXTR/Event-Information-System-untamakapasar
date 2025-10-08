<?php
/**
 * EISA System - Database Connection Utility
 * This file handles the connection to the MySQL database and makes the $conn object available.
 */

// 1. Include configuration file containing credentials (in the same directory)
require_once 'config.php';

// 2. Attempt to establish a connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 3. Check connection status
if ($conn->connect_error) {
    // Stop execution and display a user-friendly error message if connection fails
    die("ERROR: Could not connect to the database. Check credentials in config.php. " . $conn->connect_error);
}

// Optional: Set the character set
$conn->set_charset("utf8mb4");

// 4. The $conn object is now ready to use in any file that includes this one.
?>
