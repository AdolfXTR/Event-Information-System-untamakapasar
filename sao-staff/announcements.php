<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_sao_staff()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$errors = [];
$success = false;
$edit_mode = false;
$edit_data = null;

// Handle Edit Mode
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $announcement_id = intval($_GET['edit']);
    
    $edit_stmt = $conn->prepare("SELECT * FROM announcements WHERE announcement_id = ? AND created_by = ?");
    $edit_stmt->bind_param("ii", $announcement_id, $user_id);
    $edit_stmt->execute();
    $edit_data = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
    
    if (!$edit_data) {
        set_message('danger', 'Announcement not found');
        header("Location: announcements.php");
        exit();
    }
}

// Handle Create Announcement
if (isset($_POST['create_announcement'])) {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $announcement_type = sanitize_input($_POST['announcement_type']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $image_name = null;
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    // Handle image upload
    if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['announcement_image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } elseif ($_FILES['announcement_image']['size'] > $max_size) {
            $errors[] = "Image size should not exceed 5MB";
        } else {
            $file_extension = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
            $image_name = 'announcement_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = '../assets/images/announcements/' . $image_name;
            
            if (!move_uploaded_file($_FILES['announcement_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
                $image_name = null;
            }
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, announcement_type, announcement_image, is_published, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $title, $content, $announcement_type, $image_name, $is_published, $user_id);
        
        if ($stmt->execute()) {
            $announcement_id = $stmt->insert_id;
            
            // 🔔 SEND NOTIFICATIONS TO ALL STUDENTS IF PUBLISHED
            if ($is_published) {
                $notification_title = "New Announcement: " . $title;
                $notification_message = "A new announcement has been posted. Check it out!";
                
                // Get all active students
                $students_query = "SELECT user_id FROM users WHERE user_type = 'student' AND status = 'active'";
                $students_result = $conn->query($students_query);
                
                if ($students_result->num_rows > 0) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, notification_title, notification_message, is_read) VALUES (?, 'general', ?, ?, 0)");
                    
                    while ($student = $students_result->fetch_assoc()) {
                        $notif_stmt->bind_param("iss", $student['user_id'], $notification_title, $notification_message);
                        $notif_stmt->execute();
                    }
                    
                    $notif_stmt->close();
                }
            }
            
            log_activity($conn, $user_id, 'Announcement Created', 'Created announcement: ' . $title);
            set_message('success', 'Announcement posted successfully!');
            header("Location: announcements.php");
            exit();
        } else {
            $errors[] = "Failed to create announcement";
        }
        $stmt->close();
    }
}

// Handle Update Announcement
if (isset($_POST['update_announcement'])) {
    $announcement_id = intval($_POST['announcement_id']);
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $announcement_type = sanitize_input($_POST['announcement_type']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $image_name = $_POST['existing_image'];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    // Handle new image upload
    if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024;
        
        if (!in_array($_FILES['announcement_image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } elseif ($_FILES['announcement_image']['size'] > $max_size) {
            $errors[] = "Image size should not exceed 5MB";
        } else {
            // Delete old image
            if (!empty($image_name) && file_exists('../assets/images/announcements/' . $image_name)) {
                unlink('../assets/images/announcements/' . $image_name);
            }
            
            $file_extension = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
            $image_name = 'announcement_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = '../assets/images/announcements/' . $image_name;
            
            if (!move_uploaded_file($_FILES['announcement_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
                $image_name = $_POST['existing_image'];
            }
        }
    }
    
    // Handle remove image
    if (isset($_POST['remove_image']) && !empty($image_name)) {
        if (file_exists('../assets/images/announcements/' . $image_name)) {
            unlink('../assets/images/announcements/' . $image_name);
        }
        $image_name = null;
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, announcement_type = ?, announcement_image = ?, is_published = ? WHERE announcement_id = ? AND created_by = ?");
        $stmt->bind_param("ssssiii", $title, $content, $announcement_type, $image_name, $is_published, $announcement_id, $user_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'Announcement Updated', 'Updated announcement: ' . $title);
            set_message('success', 'Announcement updated successfully!');
            header("Location: announcements.php");
            exit();
        } else {
            $errors[] = "Failed to update announcement";
        }
        $stmt->close();
    }
}

// Handle Delete Announcement
if (isset($_POST['delete_announcement'])) {
    $announcement_id = intval($_POST['announcement_id']);
    
    // Get image name to delete file
    $img_stmt = $conn->prepare("SELECT announcement_image FROM announcements WHERE announcement_id = ? AND created_by = ?");
    $img_stmt->bind_param("ii", $announcement_id, $user_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result()->fetch_assoc();
    $img_stmt->close();
    
    if ($img_result && !empty($img_result['announcement_image'])) {
        $img_path = '../assets/images/announcements/' . $img_result['announcement_image'];
        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }
    
    $delete_stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ? AND created_by = ?");
    $delete_stmt->bind_param("ii", $announcement_id, $user_id);
    
    if ($delete_stmt->execute()) {
        set_message('success', 'Announcement deleted successfully');
        log_activity($conn, $user_id, 'Announcement Deleted', 'Deleted announcement ID: ' . $announcement_id);
    } else {
        set_message('danger', 'Failed to delete announcement');
    }
    $delete_stmt->close();
    
    header("Location: announcements.php");
    exit();
}

// Handle Toggle Publish
if (isset($_POST['toggle_publish'])) {
    $announcement_id = intval($_POST['announcement_id']);
    $is_published = intval($_POST['is_published']);
    
    $update_stmt = $conn->prepare("UPDATE announcements SET is_published = ? WHERE announcement_id = ? AND created_by = ?");
    $update_stmt->bind_param("iii", $is_published, $announcement_id, $user_id);
    
    if ($update_stmt->execute()) {
        $status = $is_published ? 'published' : 'unpublished';
        set_message('success', 'Announcement ' . $status);
    }
    $update_stmt->close();
    
    header("Location: announcements.php");
    exit();
}

// Get all announcements
$announcements_query = "SELECT * FROM announcements WHERE created_by = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($announcements_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$announcements = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - SAO Staff</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 16px 48px;
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
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .page-title-nav {
            font-size: 18px;
            font-weight: 600;
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 48px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 15px;
            color: #6b7280;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #e5e7eb;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }

        .file-upload-wrapper {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: #1e3a8a;
            background: #f9fafb;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            cursor: pointer;
            color: #6b7280;
            font-size: 14px;
        }

        .file-upload-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .image-preview {
            margin-top: 12px;
            max-width: 100%;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            display: block;
        }

        .current-image {
            position: relative;
            margin-bottom: 12px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .checkbox-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-label {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background: #1e40af;
        }

        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 8px;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .announcements-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .announcement-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }

        .announcement-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .announcement-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .announcement-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-general {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-reminder {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-published {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-draft {
            background: #f3f4f6;
            color: #6b7280;
        }

        .announcement-image {
            margin: 12px 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .announcement-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .announcement-content {
            color: #374151;
            line-height: 1.7;
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .announcement-actions {
            display: flex;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }

        .btn-action {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-edit:hover {
            background: #bfdbfe;
        }

        .btn-toggle {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-toggle:hover {
            background: #e5e7eb;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
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
            <span class="page-title-nav">Announcements</span>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Announcements</h1>
            <p class="page-subtitle">Post and manage announcements for students (Students will be notified 🔔)</p>
        </div>

        <?php display_message(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Create/Edit Announcement Form -->
            <div class="card">
                <h2 class="card-title">
                    <?php echo $edit_mode ? 'Edit Announcement' : 'Post New Announcement'; ?>
                </h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="announcement_id" value="<?php echo $edit_data['announcement_id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $edit_data['announcement_image']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" 
                               placeholder="Announcement title" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_data['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-textarea" 
                                  placeholder="Write your announcement here..." required><?php echo $edit_mode ? htmlspecialchars($edit_data['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="announcement_type" class="form-select">
                            <option value="general" <?php echo ($edit_mode && $edit_data['announcement_type'] == 'general') ? 'selected' : ''; ?>>General</option>
                            <option value="urgent" <?php echo ($edit_mode && $edit_data['announcement_type'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            <option value="reminder" <?php echo ($edit_mode && $edit_data['announcement_type'] == 'reminder') ? 'selected' : ''; ?>>Reminder</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Image (Optional)</label>
                        
                        <?php if ($edit_mode && !empty($edit_data['announcement_image'])): ?>
                            <div class="current-image">
                                <img src="../assets/images/announcements/<?php echo htmlspecialchars($edit_data['announcement_image']); ?>" 
                                     alt="Current" style="max-width: 100%; border-radius: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 13px;">
                                    <input type="checkbox" name="remove_image" value="1">
                                    Remove current image
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="file-upload-wrapper" onclick="document.getElementById('announcement_image').click()">
                            <div class="file-upload-icon">📸</div>
                            <label class="file-upload-label">
                                Click to upload <?php echo ($edit_mode && !empty($edit_data['announcement_image'])) ? 'new' : 'an'; ?> image
                                <br><small>JPG, PNG or GIF (Max 5MB)</small>
                            </label>
                            <input type="file" name="announcement_image" id="announcement_image" 
                                   class="file-upload-input" accept="image/*" 
                                   onchange="previewImage(this)">
                        </div>
                        <div id="imagePreview" class="image-preview" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_published" id="is_published" 
                                   class="checkbox-input" value="1" 
                                   <?php echo (!$edit_mode || ($edit_mode && $edit_data['is_published'])) ? 'checked' : ''; ?>>
                            <label for="is_published" class="checkbox-label">
                                <?php echo $edit_mode ? 'Published' : 'Publish immediately (Notify all students 🔔)'; ?>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="<?php echo $edit_mode ? 'update_announcement' : 'create_announcement'; ?>" class="btn-submit">
                        <?php echo $edit_mode ? 'Update Announcement' : 'Post Announcement'; ?>
                    </button>

                    <?php if ($edit_mode): ?>
                        <a href="announcements.php" class="btn-cancel">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Announcements List -->
            <div>
                <?php if ($announcements->num_rows > 0): ?>
                    <div class="announcements-list">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="announcement-card">
                                <div class="announcement-header">
                                    <div>
                                        <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <div class="announcement-meta">
                                            <span class="badge badge-<?php echo $announcement['announcement_type']; ?>">
                                                <?php echo ucfirst($announcement['announcement_type']); ?>
                                            </span>
                                            <span class="badge <?php echo $announcement['is_published'] ? 'badge-published' : 'badge-draft'; ?>">
                                                <?php echo $announcement['is_published'] ? 'Published' : 'Draft'; ?>
                                            </span>
                                            • Posted <?php echo time_ago($announcement['created_at']); ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($announcement['announcement_image'])): ?>
                                    <div class="announcement-image">
                                        <img src="../assets/images/announcements/<?php echo htmlspecialchars($announcement['announcement_image']); ?>" 
                                             alt="Announcement">
                                    </div>
                                <?php endif; ?>

                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>

                                <div class="announcement-actions">
                                    <a href="?edit=<?php echo $announcement['announcement_id']; ?>" class="btn-action btn-edit">
                                        Edit
                                    </a>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                                        <input type="hidden" name="is_published" value="<?php echo $announcement['is_published'] ? 0 : 1; ?>">
                                        <button type="submit" name="toggle_publish" class="btn-action btn-toggle">
                                            <?php echo $announcement['is_published'] ? 'Unpublish' : 'Publish'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                                        <button type="submit" name="delete_announcement" class="btn-action btn-delete">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="empty-state">
                            <h3>No announcements yet</h3>
                            <p>Create your first announcement using the form</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100%; border-radius: 8px;">';
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>