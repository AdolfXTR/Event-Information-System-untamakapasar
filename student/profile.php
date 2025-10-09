<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle Profile Picture Upload
if (isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed";
        } elseif ($file_size > 5242880) {
            $error = "File size must be less than 5MB";
        } else {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = '../assets/images/profiles/';
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_path . $new_filename)) {
                if ($user['profile_picture'] != 'default.jpg' && file_exists($upload_path . $user['profile_picture'])) {
                    unlink($upload_path . $user['profile_picture']);
                }
                
                $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $new_filename, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $success = "Profile picture updated successfully!";
                $user['profile_picture'] = $new_filename;
                
                log_activity($conn, $user_id, 'Profile Picture Updated', 'User updated profile picture');
            } else {
                $error = "Failed to upload file";
            }
        }
    } else {
        $error = "Please select a file to upload";
    }
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $student_id = sanitize_input($_POST['student_id']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "All fields are required";
    } elseif (!validate_email($email)) {
        $error = "Invalid email format";
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already taken by another user";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, student_id = ? WHERE user_id = ?");
            $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $student_id, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['email'] = $email;
                $user['student_id'] = $student_id;
                
                $success = "Profile updated successfully!";
                log_activity($conn, $user_id, 'Profile Updated', 'User updated profile information');
            } else {
                $error = "Failed to update profile";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } elseif (!verify_password($current_password, $user['password'])) {
        $error = "Current password is incorrect";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        $hashed_password = hash_password($new_password);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            $success = "Password changed successfully!";
            log_activity($conn, $user_id, 'Password Changed', 'User changed password');
        } else {
            $error = "Failed to change password";
        }
        $update_stmt->close();
    }
}

$profile_pic_path = '../assets/images/profiles/' . $user['profile_picture'];
$has_custom_pic = $user['profile_picture'] != 'default.jpg' && file_exists($profile_pic_path);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SAO Events</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 48px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            padding: 8px 16px;
            background: #f3f4f6;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: #e5e7eb;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            width: 36px;
            height: 36px;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 600;
        }

        .main-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 48px;
        }

        .profile-header {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .profile-pic-large {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: #1a1a1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            font-weight: 600;
            object-fit: cover;
            border: 4px solid #f3f4f6;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .profile-meta {
            display: flex;
            gap: 24px;
            font-size: 14px;
            color: #6b7280;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e5e7eb;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.05);
        }

        .form-input:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #1a1a1a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #2d2d2d;
        }

        .upload-section {
            text-align: center;
            padding: 32px;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .upload-section:hover {
            border-color: #d1d5db;
            background: #f3f4f6;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .upload-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: inline-block;
            padding: 10px 24px;
            background: #1a1a1a;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .file-label:hover {
            background: #2d2d2d;
        }

        .info-grid {
            display: grid;
            gap: 16px;
        }

        .info-item {
            padding: 16px;
            background: #fafafa;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 500;
        }

        @media (max-width: 968px) {
            .grid-container {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .navbar {
                padding: 16px 24px;
            }

            .main-container {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <div class="logo-area">
                <img src="../assets/images/logo.png" alt="Logo" class="logo-img">
                <span class="brand-name">My Profile</span>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <?php if ($has_custom_pic): ?>
                <img src="<?php echo $profile_pic_path; ?>" alt="Profile" class="profile-pic-large">
            <?php else: ?>
                <div class="profile-pic-large">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <div class="profile-meta">
                    <div class="meta-item">
                        <span>📚</span>
                        <span><?php echo htmlspecialchars($user['student_id']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>✉️</span>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        <span>Member since <?php echo format_date($user['created_at']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            <!-- Left Column -->
            <div>
                <!-- Update Profile Picture -->
                <div class="card">
                    <h2 class="card-title">Profile Picture</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-section">
                            <div class="upload-icon">📸</div>
                            <p class="upload-text">Upload a new profile picture<br>Max 5MB • JPG, PNG, GIF</p>
                            <input type="file" name="profile_picture" id="profile_picture" class="file-input" accept="image/*" onchange="this.form.querySelector('.file-label').textContent = this.files[0].name">
                            <label for="profile_picture" class="file-label">Choose Photo</label>
                        </div>
                        <button type="submit" name="upload_picture" class="btn-submit">Upload Picture</button>
                    </form>
                </div>

                <!-- Edit Profile -->
                <div class="card" style="margin-top: 24px;">
                    <h2 class="card-title">Personal Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['student_id']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <button type="submit" name="update_profile" class="btn-submit">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="card" style="margin-top: 24px;">
                    <h2 class="card-title">Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" minlength="6" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-input" minlength="6" required>
                        </div>

                        <button type="submit" name="change_password" class="btn-submit">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Right Column - Account Info -->
            <div>
                <div class="card">
                    <h2 class="card-title">Account Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">Student</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value" style="color: #059669;">● Active</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Email Verified</div>
                            <div class="info-value"><?php echo $user['email_verified'] ? '✓ Verified' : '✗ Not Verified'; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value"><?php echo time_ago($user['updated_at']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>