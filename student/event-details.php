<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get user profile picture
$user_query = "SELECT profile_picture FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_pic = isset($user_data['profile_picture']) ? $user_data['profile_picture'] : 'default.jpg';
$profile_pic_path = '../assets/images/profiles/' . $profile_pic;
$has_custom_pic = $profile_pic != 'default.jpg' && file_exists($profile_pic_path);

// Get event ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-events.php");
    exit();
}

$event_id = intval($_GET['id']);

// Handle Registration
if (isset($_POST['register'])) {
    // Check if already registered
    $check_stmt = $conn->prepare("SELECT registration_id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $event_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        set_message('info', 'You are already registered for this event');
    } else {
        $register_stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)");
        $register_stmt->bind_param("ii", $event_id, $user_id);
        
        if ($register_stmt->execute()) {
            set_message('success', 'Successfully registered for this event!');
            log_activity($conn, $user_id, 'Event Registration', 'Registered for event ID: ' . $event_id);
        } else {
            set_message('danger', 'Failed to register. Please try again.');
        }
        $register_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: event-details.php?id=" . $event_id);
    exit();
}

// Handle Unregister
if (isset($_POST['unregister'])) {
    $delete_stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $event_id, $user_id);
    
    if ($delete_stmt->execute()) {
        set_message('success', 'Successfully unregistered from this event');
        log_activity($conn, $user_id, 'Event Unregistration', 'Unregistered from event ID: ' . $event_id);
    } else {
        set_message('danger', 'Failed to unregister. Please try again.');
    }
    $delete_stmt->close();
    
    header("Location: event-details.php?id=" . $event_id);
    exit();
}

// Get event details
$event_query = "SELECT e.*, 
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count,
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as user_registered,
                u.first_name, u.last_name
                FROM events e
                LEFT JOIN users u ON e.created_by = u.user_id
                WHERE e.event_id = ? AND e.is_published = 1";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view-events.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Get registered users
$attendees_query = "SELECT u.first_name, u.last_name, u.profile_picture, er.registration_date
                    FROM event_registrations er
                    JOIN users u ON er.user_id = u.user_id
                    WHERE er.event_id = ?
                    ORDER BY er.registration_date DESC
                    LIMIT 10";
$attendees_stmt = $conn->prepare($attendees_query);
$attendees_stmt->bind_param("i", $event_id);
$attendees_stmt->execute();
$attendees = $attendees_stmt->get_result();
$attendees_stmt->close();

// Check if registration is still open
$registration_open = strtotime($event['event_date']) > time();
if ($event['registration_deadline']) {
    $registration_open = $registration_open && strtotime($event['registration_deadline']) > time();
}

// Check if event is full
$is_full = $event['max_participants'] && $event['registered_count'] >= $event['max_participants'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_title']); ?> - SAO Events</title>
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
            background: white;
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 48px;
        }

        .event-header {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }

        .event-hero {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        }

        .event-content {
            padding: 40px;
        }

        .event-meta-top {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .badge-category {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-status {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-full {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-title {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .event-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .meta-icon {
            font-size: 24px;
        }

        .meta-text {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }

        .meta-value {
            font-size: 15px;
            color: #111827;
            font-weight: 600;
        }

        .content-grid {
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
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .description {
            color: #374151;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .register-section {
            position: sticky;
            top: 100px;
        }

        .register-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .register-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .register-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .register-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: #1a1a1a;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-register:hover {
            background: #2d2d2d;
            transform: translateY(-2px);
        }

        .btn-registered {
            background: #059669;
        }

        .btn-registered:hover {
            background: #047857;
        }

        .btn-unregister {
            background: #dc2626;
            margin-top: 12px;
        }

        .btn-unregister:hover {
            background: #b91c1c;
        }

        .btn-disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            background: #9ca3af;
            transform: none;
        }

        .attendees-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .attendee-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .attendee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1a1a1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            object-fit: cover;
        }

        .attendee-info {
            flex: 1;
        }

        .attendee-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .attendee-time {
            font-size: 12px;
            color: #6b7280;
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

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .alert-info {
            background: #f0f9ff;
            color: #075985;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .register-section {
                position: static;
            }

            .event-title {
                font-size: 28px;
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
            <a href="view-events.php" class="back-btn">← Back to Events</a>
            <div class="logo-area">
                <img src="../assets/images/logo.png" alt="Logo" class="logo-img">
                <span class="brand-name">Event Details</span>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <?php display_message(); ?>

        <div class="event-header">
            <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                 alt="Event" class="event-hero"
                 onerror="this.style.display='block'">
        </div>

        <div class="event-content">
            <div class="event-meta-top">
                <?php if ($event['event_category']): ?>
                    <span class="badge badge-category"><?php echo htmlspecialchars($event['event_category']); ?></span>
                <?php endif; ?>
                
                <?php if ($event['user_registered']): ?>
                    <span class="badge badge-status">✓ You're registered</span>
                <?php endif; ?>
                
                <?php if ($is_full): ?>
                    <span class="badge badge-full">Event Full</span>
                <?php endif; ?>
            </div>

            <h1 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h1>

            <div class="event-meta-grid">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <div class="meta-text">
                        <span class="meta-label">Date</span>
                        <span class="meta-value"><?php echo format_date($event['event_date']); ?></span>
                    </div>
                </div>

                <div class="meta-item">
                    <span class="meta-icon">⏰</span>
                    <div class="meta-text">
                        <span class="meta-label">Time</span>
                        <span class="meta-value"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                    </div>
                </div>

                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <div class="meta-text">
                        <span class="meta-label">Venue</span>
                        <span class="meta-value"><?php echo htmlspecialchars($event['event_venue']); ?></span>
                    </div>
                </div>

                <div class="meta-item">
                    <span class="meta-icon">👥</span>
                    <div class="meta-text">
                        <span class="meta-label">Attendees</span>
                        <span class="meta-value">
                            <?php echo $event['registered_count']; ?>
                            <?php if ($event['max_participants']): ?>
                                / <?php echo $event['max_participants']; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Left Column -->
            <div>
                <div class="card">
                    <h2 class="card-title">About This Event</h2>
                    <div class="description">
                        <?php echo nl2br(htmlspecialchars($event['event_description'])); ?>
                    </div>
                </div>

                <?php if ($attendees->num_rows > 0): ?>
                    <div class="card" style="margin-top: 24px;">
                        <h2 class="card-title">Attendees (<?php echo $event['registered_count']; ?>)</h2>
                        <div class="attendees-list">
                            <?php while ($attendee = $attendees->fetch_assoc()): ?>
                                <div class="attendee-item">
                                    <?php 
                                    $att_pic_path = '../assets/images/profiles/' . $attendee['profile_picture'];
                                    $att_has_pic = $attendee['profile_picture'] != 'default.jpg' && file_exists($att_pic_path);
                                    ?>
                                    <?php if ($att_has_pic): ?>
                                        <img src="<?php echo $att_pic_path; ?>" alt="Profile" class="attendee-avatar">
                                    <?php else: ?>
                                        <div class="attendee-avatar">
                                            <?php echo strtoupper(substr($attendee['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="attendee-info">
                                        <div class="attendee-name">
                                            <?php echo htmlspecialchars($attendee['first_name'] . ' ' . $attendee['last_name']); ?>
                                        </div>
                                        <div class="attendee-time">
                                            Registered <?php echo time_ago($attendee['registration_date']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Registration -->
            <div class="register-section">
                <div class="register-card">
                    <?php if ($event['user_registered']): ?>
                        <div class="register-icon">✅</div>
                        <h3 class="register-title">You're Registered!</h3>
                        <p class="register-text">You're all set for this event. See you there!</p>
                        
                        <form method="POST">
                            <button type="submit" name="unregister" class="btn-register btn-unregister">
                                Unregister
                            </button>
                        </form>
                    <?php elseif (!$registration_open): ?>
                        <div class="register-icon">🔒</div>
                        <h3 class="register-title">Registration Closed</h3>
                        <p class="register-text">Registration for this event is no longer available.</p>
                        <button class="btn-register btn-disabled" disabled>Registration Closed</button>
                    <?php elseif ($is_full): ?>
                        <div class="register-icon">😔</div>
                        <h3 class="register-title">Event Full</h3>
                        <p class="register-text">This event has reached maximum capacity.</p>
                        <button class="btn-register btn-disabled" disabled>Event Full</button>
                    <?php else: ?>
                        <div class="register-icon">🎉</div>
                        <h3 class="register-title">Join This Event</h3>
                        <p class="register-text">Register now to secure your spot at this event!</p>
                        
                        <form method="POST">
                            <button type="submit" name="register" class="btn-register">
                                Register Now
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($event['registration_deadline']): ?>
                        <p style="margin-top: 16px; font-size: 12px; color: #6b7280;">
                            Registration deadline: <?php echo format_datetime($event['registration_deadline']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>