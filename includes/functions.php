<?php
// Helper Functions for Event Information System

// Sanitize input data
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}


function shorten_text($text, $max_length = 150) {
    if (strlen($text) <= $max_length) {
        return $text;
    }
    
    $text = substr($text, 0, $max_length);
    $last_space = strrpos($text, ' ');
    
    if ($last_space !== false) {
        $text = substr($text, 0, $last_space);
    }
    
    return $text . '...';
}




// Validate email
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

// Generate random token
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// Hash password
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

// Verify password
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// Check user role
if (!function_exists('is_student')) {
    function is_student() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
    }
}

if (!function_exists('is_sao_staff')) {
    function is_sao_staff() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'sao_staff';
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

// Redirect function
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

// Set flash message
if (!function_exists('set_message')) {
    function set_message($type, $message) {
        $_SESSION['message_type'] = $type;
        $_SESSION['message'] = $message;
    }
}

// Display flash message
if (!function_exists('display_message')) {
    function display_message() {
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'];
            $message = $_SESSION['message'];
            
            echo '<div class="alert alert-' . $type . '" role="alert">';
            echo $message;
            echo '</div>';
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
    }
}

// Format date
if (!function_exists('format_date')) {
    function format_date($date) {
        return date('F j, Y', strtotime($date));
    }
}

// Format date and time
if (!function_exists('format_datetime')) {
    function format_datetime($datetime) {
        return date('F j, Y g:i A', strtotime($datetime));
    }
}

// Get time ago (e.g., "2 hours ago")
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $time_diff = time() - $time;
        
        if ($time_diff < 60) {
            return 'just now';
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 604800) {
            $days = floor($time_diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return format_date($datetime);
        }
    }
}

// Upload file function
if (!function_exists('upload_file')) {
    function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
        $file_name = basename($file['name']);
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $new_filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        if ($file_size > 5242880) {
            return ['success' => false, 'message' => 'File is too large (max 5MB)'];
        }
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            return ['success' => true, 'filename' => $new_filename];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
    }
}

// Log system activity - FIXED VERSION!
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action, $description = null) {
        // Insert into activity_logs table (not system_logs!)
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $action, $description);
        $stmt->execute();
        $stmt->close();
    }
}

// Send email notification
if (!function_exists('send_email')) {
    function send_email($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: SAO Events <noreply@sao.edu>" . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}

// Generate email template
if (!function_exists('email_template')) {
    function email_template($title, $content, $button_text = null, $button_link = null) {
        $button_html = '';
        if ($button_text && $button_link) {
            $button_html = '
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $button_link . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    ' . $button_text . '
                </a>
            </div>';
        }
        
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $title . '</h1>
                </div>
                <div class="content">
                    ' . $content . '
                    ' . $button_html . '
                </div>
                <div class="footer">
                    <p>&copy; 2024 Event Information System - Student Affairs Office</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
    }
}

// Check if event registration is still open
if (!function_exists('is_registration_open')) {
    function is_registration_open($event_date, $registration_deadline) {
        $now = date('Y-m-d H:i:s');
        $event = strtotime($event_date);
        $deadline = strtotime($registration_deadline);
        
        if ($deadline && strtotime($now) > $deadline) {
            return false;
        }
        
        if (strtotime($now) > $event) {
            return false;
        }
        
        return true;
    }
}

// Count days until event
if (!function_exists('days_until_event')) {
    function days_until_event($event_date) {
        $now = time();
        $event = strtotime($event_date);
        $diff = $event - $now;
        
        return floor($diff / (60 * 60 * 24));
    }
}
?>