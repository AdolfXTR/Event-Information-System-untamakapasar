<?php
session_start();
echo "<h1>Session Test</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if(isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ You are logged in!</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Name: " . $_SESSION['first_name'] . "</p>";
    echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ You are NOT logged in</p>";
}
?>