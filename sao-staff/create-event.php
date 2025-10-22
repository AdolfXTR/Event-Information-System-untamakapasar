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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_title = sanitize_input($_POST['event_title']);
    $event_description = sanitize_input($_POST['event_description']);
    $event_category = sanitize_input($_POST['event_category']);
    $event_venue = sanitize_input($_POST['event_venue']);
    $event_date = sanitize_input($_POST['event_date']);
    $event_time = sanitize_input($_POST['event_time']);
    $event_end_time = sanitize_input($_POST['event_end_time']);
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : null;
    $registration_deadline = !empty($_POST['registration_deadline']) ? sanitize_input($_POST['registration_deadline']) : null;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // Validation
    if (empty($event_title)) {
        $errors[] = "Event title is required";
    }
    
    if (empty($event_description)) {
        $errors[] = "Event description is required";
    }
    
    if (empty($event_venue)) {
        $errors[] = "Event venue is required";
    }
    
    if (empty($event_date)) {
        $errors[] = "Event date is required";
    }
    
    if (empty($event_time)) {
        $errors[] = "Event time is required";
    }
    
    // Handle Image Upload
    $event_image = 'default-event.jpg';
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
        $upload_result = upload_file($_FILES['event_image'], '../assets/images/', ['jpg', 'jpeg', 'png', 'gif']);
        
        if ($upload_result['success']) {
            $event_image = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // If no errors, insert event
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO events (event_title, event_description, event_category, event_venue, event_date, event_time, event_end_time, event_image, max_participants, registration_deadline, is_published, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssisii", $event_title, $event_description, $event_category, $event_venue, $event_date, $event_time, $event_end_time, $event_image, $max_participants, $registration_deadline, $is_published, $user_id);
        
        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;
            
            // 🔔 SEND NOTIFICATIONS TO ALL STUDENTS IF PUBLISHED
            if ($is_published) {
                $notification_title = "New Event: " . $event_title;
                $notification_message = "A new event has been published! Check it out and register now.";
                
                // Get all active students
                $students_query = "SELECT user_id FROM users WHERE user_type = 'student' AND status = 'active'";
                $students_result = $conn->query($students_query);
                
                if ($students_result->num_rows > 0) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, notification_title, notification_message, event_id, is_read) VALUES (?, 'general', ?, ?, ?, 0)");
                    
                    while ($student = $students_result->fetch_assoc()) {
                        $notif_stmt->bind_param("issi", $student['user_id'], $notification_title, $notification_message, $event_id);
                        $notif_stmt->execute();
                    }
                    
                    $notif_stmt->close();
                }
            }
            
            log_activity($conn, $user_id, 'Event Created', 'Created event: ' . $event_title);
            set_message('success', 'Event created successfully!');
            header("Location: manage-events.php");
            exit();
        } else {
            $errors[] = "Failed to create event. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - SAO Staff</title>
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
            max-width: 1000px;
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

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e5e7eb;
        }

        .form-section {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #f3f4f6;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            color: #374151;
            margin-bottom: 8px;
        }

        .form-label.required::after {
            content: " *";
            color: #dc2626;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        .file-upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            background: #fafafa;
            transition: all 0.2s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #1e3a8a;
            background: #f3f4f6;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .upload-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: inline-block;
            padding: 10px 20px;
            background: #1e3a8a;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .file-label:hover {
            background: #1e40af;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
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

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #1e3a8a;
            color: white;
        }

        .btn-primary:hover {
            background: #1e40af;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 24px;
            }

            .main-container {
                padding: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <span class="page-title-nav">Create New Event</span>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Create New Event</h1>
            <p class="page-subtitle">Fill in the details to create a new event for students (Students will be notified 🔔)</p>
        </div>

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

        <form method="POST" enctype="multipart/form-data">
            <div class="form-card">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2 class="section-title">Basic Information</h2>
                    
                    <div class="form-group">
                        <label class="form-label required">Event Title</label>
                        <input type="text" name="event_title" class="form-input" 
                               placeholder="Enter event title" required
                               value="<?php echo isset($event_title) ? htmlspecialchars($event_title) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Event Description</label>
                        <textarea name="event_description" class="form-textarea" 
                                  placeholder="Describe the event in detail" required><?php echo isset($event_description) ? htmlspecialchars($event_description) : ''; ?></textarea>
                        <p class="form-hint">Provide a detailed description of what the event is about</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Event Category</label>
                        <input type="text" name="event_category" class="form-input" 
                               placeholder="e.g., Workshop, Seminar, Sports"
                               value="<?php echo isset($event_category) ? htmlspecialchars($event_category) : ''; ?>">
                        <p class="form-hint">Optional: Categorize your event for better organization</p>
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="form-section">
                    <h2 class="section-title">Date & Time</h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Event Date</label>
                            <input type="date" name="event_date" class="form-input" required
                                   value="<?php echo isset($event_date) ? $event_date : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Start Time</label>
                            <input type="time" name="event_time" class="form-input" required
                                   value="<?php echo isset($event_time) ? $event_time : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="event_end_time" class="form-input"
                                   value="<?php echo isset($event_end_time) ? $event_end_time : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Registration Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-input"
                                   value="<?php echo isset($registration_deadline) ? $registration_deadline : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Location & Capacity -->
                <div class="form-section">
                    <h2 class="section-title">Location & Capacity</h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Venue</label>
                            <input type="text" name="event_venue" class="form-input" 
                                   placeholder="Enter event venue" required
                                   value="<?php echo isset($event_venue) ? htmlspecialchars($event_venue) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Maximum Participants</label>
                            <input type="number" name="max_participants" class="form-input" 
                                   placeholder="Leave empty for unlimited" min="1"
                                   value="<?php echo isset($max_participants) ? $max_participants : ''; ?>">
                            <p class="form-hint">Optional: Set a maximum number of attendees</p>
                        </div>
                    </div>
                </div>

                <!-- Event Image -->
                <div class="form-section">
                    <h2 class="section-title">Event Image</h2>
                    
                    <div class="form-group">
                        <div class="file-upload-area" onclick="document.getElementById('event_image').click()">
                            <div class="upload-icon">📸</div>
                            <p class="upload-text">Click to upload event image</p>
                            <p class="upload-text" style="font-size: 12px;">Max 5MB • JPG, PNG, GIF</p>
                            <input type="file" name="event_image" id="event_image" class="file-input" accept="image/*">
                            <label for="event_image" class="file-label">Choose File</label>
                        </div>
                    </div>
                </div>

                <!-- Publishing Options -->
                <div class="form-section">
                    <h2 class="section-title">Publishing Options</h2>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_published" id="is_published" class="checkbox-input" value="1">
                        <label for="is_published" class="checkbox-label">
                            Publish this event immediately (All students will be notified 🔔)
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Show selected filename
        document.getElementById('event_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-label').textContent = fileName;
            }
        });
    </script>
</body>
</html>