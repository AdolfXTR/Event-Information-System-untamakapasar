<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['mark_read']);
    $update_query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit();
    }
    
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Delete notification
if (isset($_POST['delete_notification'])) {
    $notification_id = intval($_POST['delete_notification']);
    $delete_query = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Get all notifications
$notifications_query = "SELECT n.*, e.event_title 
                        FROM notifications n
                        LEFT JOIN events e ON n.event_id = e.event_id
                        WHERE n.user_id = ?
                        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// Get unread count
$unread_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafafa;
            color: #1a1a1a;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Top Navigation */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.8);
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: -0.02em;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        /* Main Container */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 2rem;
        }

        /* Header Section */
        .header-section {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .header-left h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.025em;
            margin-bottom: 0.25rem;
        }

        .header-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        .unread-count {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #1a1a1a;
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .mark-all-button {
            background: #1a1a1a;
            color: #fff;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mark-all-button:hover {
            background: #2d2d2d;
            transform: translateY(-1px);
        }

        .mark-all-button:active {
            transform: translateY(0);
        }

        /* Notifications List */
        .notifications-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .notification-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.25rem;
            display: flex;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }

        .notification-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .notification-item.unread {
            background: #f9fafb;
            border-left: 3px solid #1a1a1a;
        }

        .notification-item.unread::after {
            content: '';
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            width: 8px;
            height: 8px;
            background: #1a1a1a;
            border-radius: 50%;
        }

        /* Icon Styles */
        .notif-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .notif-icon.attended {
            background: #f0fdf4;
            color: #15803d;
        }

        .notif-icon.absent {
            background: #fef2f2;
            color: #dc2626;
        }

        .notif-icon.general {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        /* Content */
        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .notif-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: -0.01em;
            line-height: 1.4;
        }

        .notif-time {
            font-size: 0.8125rem;
            color: #9ca3af;
            white-space: nowrap;
            font-weight: 400;
        }

        .notif-event-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f3f4f6;
            color: #4b5563;
            padding: 0.25rem 0.625rem;
            border-radius: 4px;
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .notif-message {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        /* Actions */
        .notif-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .action-btn {
            background: transparent;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            padding: 0.4rem 0.875rem;
            border-radius: 5px;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .action-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #1a1a1a;
        }

        .action-btn.delete:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .action-btn.mark-read {
            background: #1a1a1a;
            color: #fff;
            border-color: #1a1a1a;
        }

        .action-btn.mark-read:hover {
            background: #2d2d2d;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: #9ca3af;
        }

        /* Click Hint */
        .click-hint {
            position: absolute;
            top: 0.875rem;
            right: 2.5rem;
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .notification-item:hover .click-hint {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem 1rem;
            }

            .nav-container {
                padding: 0 1rem;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left h1 {
                font-size: 1.5rem;
            }

            .notification-item {
                flex-direction: column;
                padding: 1rem;
            }

            .notif-header {
                flex-direction: column;
                gap: 0.25rem;
            }

            .notif-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }

            .click-hint {
                display: none;
            }

            .notification-item.unread::after {
                top: 1rem;
                right: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-title">Notifications</div>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <div class="header-left">
                <h1>
                    All Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-count">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <p class="header-subtitle">Stay updated with your events and announcements</p>
            </div>
            <?php if ($unread_count > 0): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="mark-all-button">
                        <i class="fas fa-check"></i>
                        Mark all as read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="notifications-container">
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notif = $notifications->fetch_assoc()): 
                    $link = '';
                    $is_clickable = false;
                    
                    if (stripos($notif['notification_title'], 'Announcement') !== false) {
                        $link = 'announcements.php';
                        $is_clickable = true;
                    } elseif ($notif['event_id']) {
                        $link = 'event-details.php?id=' . $notif['event_id'];
                        $is_clickable = true;
                    }
                ?>
                    <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" 
                         <?php if ($is_clickable): ?>
                         onclick="handleNotificationClick(<?php echo $notif['notification_id']; ?>, '<?php echo $link; ?>')"
                         <?php endif; ?>>
                        
                        <?php if ($is_clickable): ?>
                            <span class="click-hint">Click to view</span>
                        <?php endif; ?>
                        
                        <div class="notif-icon <?php echo strtolower($notif['notification_type']); ?>">
                            <?php
                            $icon = 'fa-bell';
                            if ($notif['notification_type'] == 'attended') {
                                $icon = 'fa-check-circle';
                            } elseif ($notif['notification_type'] == 'absent') {
                                $icon = 'fa-times-circle';
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        
                        <div class="notif-content">
                            <div class="notif-header">
                                <h3 class="notif-title"><?php echo htmlspecialchars($notif['notification_title']); ?></h3>
                                <span class="notif-time"><?php echo time_elapsed_string($notif['created_at']); ?></span>
                            </div>
                            
                            <?php if ($notif['event_title']): ?>
                                <div class="notif-event-tag">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo htmlspecialchars($notif['event_title']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <p class="notif-message">
                                <?php echo nl2br(htmlspecialchars($notif['notification_message'])); ?>
                            </p>
                            
                            <div class="notif-actions" onclick="event.stopPropagation();">
                                <?php if (!$notif['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="mark_read" value="<?php echo $notif['notification_id']; ?>" class="action-btn mark-read">
                                            <i class="fas fa-check"></i>
                                            Mark as read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="delete_notification" value="<?php echo $notif['notification_id']; ?>" class="action-btn delete" 
                                            onclick="return confirm('Delete this notification?')">
                                        <i class="fas fa-trash-alt"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>You'll see notifications here when SAO posts announcements or events</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function handleNotificationClick(notificationId, link) {
            const formData = new FormData();
            formData.append('mark_read', notificationId);
            formData.append('ajax', '1');

            fetch('notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = link;
                }
            })
            .catch(error => {
                window.location.href = link;
            });
        }
    </script>
</body>
</html>