<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    
    // Check if email already exists (for other users)
    $check_email = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $check_stmt = $conn->prepare($check_email);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error_message = "Email already exists!";
    } else {
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, student_id = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $student_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = "UPDATE users SET password = ? WHERE user_id = ?";
                $pass_stmt = $conn->prepare($update_pass);
                $pass_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($pass_stmt->execute()) {
                    $success_message = "Password changed successfully!";
                }
            } else {
                $error_message = "Password must be at least 6 characters!";
            }
        } else {
            $error_message = "New passwords do not match!";
        }
    } else {
        $error_message = "Current password is incorrect!";
    }
}

// Get activity stats
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status = 'confirmed') as total_events,
    (SELECT COUNT(*) FROM event_registrations er JOIN events e ON er.event_id = e.event_id WHERE er.user_id = ? AND e.event_date >= CURDATE()) as upcoming_events";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ii", $user_id, $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
        }

        .profile-sidebar {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: 700;
            margin: 0 auto 15px;
            border: 5px solid #f7fafc;
        }

        .user-name {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .user-email {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .user-badge {
            display: inline-block;
            padding: 6px 16px;
            background: #48bb78;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }

        .stat-box {
            text-align: center;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f7fafc;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .form-input:disabled {
            background: #edf2f7;
            cursor: not-allowed;
        }

        .btn-primary {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #718096;
        }

        @media (max-width: 968px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="avatar-section">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                    <h2 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="user-badge">Student</span>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                </div>

                <div style="margin-top: 25px; padding-top: 25px; border-top: 2px solid #f0f0f0;">
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px;">
                        <strong>Student ID:</strong> <?php echo htmlspecialchars($user['student_id']); ?>
                    </div>
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px;">
                        <strong>Status:</strong> <span style="color: #48bb78;">Active</span>
                    </div>
                    <div style="font-size: 13px; color: #718096;">
                        <strong>Joined:</strong> <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-main">
                <!-- Edit Profile -->
                <div class="profile-card">
                    <h3 class="card-title">
                        <span class="card-icon">👤</span>
                        Personal Information
                    </h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Student ID</label>
                                <input type="text" name="student_id" class="form-input" value="<?php echo htmlspecialchars($user['student_id']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary">💾 Save Changes</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="profile-card">
                    <h3 class="card-title">
                        <span class="card-icon">🔒</span>
                        Change Password
                    </h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <div class="password-toggle">
                                <input type="password" name="current_password" class="form-input" id="current_password" required>
                                <span class="toggle-password" onclick="togglePassword('current_password')">👁️</span>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group"
                                <label class="form-label">New Password</label>
                                <div class="password-toggle">
                                    <input type="password" name="new_password" class="form-input" id="new_password" required>
                                    <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <div class="password-toggle">
                                    <input type="password" name="confirm_password" class="form-input" id="confirm_password" required>
                                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary">🔐 Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>