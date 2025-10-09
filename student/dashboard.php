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

// ✅ Get user profile picture safely
$user_query = "SELECT profile_picture FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$has_custom_pic = isset($user_data['profile_picture']) && !empty($user_data['profile_picture']);
$profile_pic_path = $has_custom_pic
    ? "../assets/images/profiles/" . htmlspecialchars($user_data['profile_picture'])
    : "../assets/images/default.jpg";

$stmt->close();

// Get statistics
$total_events_query = "SELECT COUNT(*) as total FROM events WHERE is_published = 1 AND event_date >= CURDATE()";
$total_events_result = $conn->query($total_events_query);
$total_events = $total_events_result->fetch_assoc()['total'];

$registered_events_query = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = ?";
$stmt = $conn->prepare($registered_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$registered_events = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$announcements_query = "SELECT COUNT(*) as total FROM announcements WHERE is_published = 1";
$announcements_result = $conn->query($announcements_query);
$total_announcements = $announcements_result->fetch_assoc()['total'];

// Get upcoming events
$upcoming_events_query = "SELECT e.*, 
                         (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count,
                         (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as user_registered
                         FROM events e
                         WHERE is_published = 1 AND event_date >= CURDATE() 
                         ORDER BY event_date ASC LIMIT 6";
$stmt = $conn->prepare($upcoming_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_events = $stmt->get_result();

// Get recent announcements
$recent_announcements_query = "SELECT a.*, u.first_name, u.last_name 
                              FROM announcements a 
                              JOIN users u ON a.created_by = u.user_id 
                              WHERE a.is_published = 1 
                              ORDER BY a.created_at DESC LIMIT 3";
$recent_announcements = $conn->query($recent_announcements_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Event Information System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Top Navigation */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(8px);
        }

        .nav-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 16px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: -0.3px;
        }

        .nav-links {
            display: flex;
            gap: 8px;
        }

        .nav-link {
            padding: 8px 16px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        .nav-link.active {
            color: #1a1a1a;
            background: #f3f4f6;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 16px;
            border-radius: 24px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-section:hover {
            background: #f3f4f6;
        }

        .avatar {
            width: 48px; /* Increased size */
            height: 48px; /* Increased size */
            border-radius: 50%;
            background: #1a1a1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px; /* Adjusted size for larger avatar */
            font-weight: 600;
            object-fit: cover;
        }

        .user-name {
            font-size: 16px; /* Slightly larger text */
            font-weight: 500;
            color: #1a1a1a;
        }

        /* Main Content */
        .main-wrapper {
            max-width: 1440px;
            margin: 0 auto;
            padding: 48px 48px 80px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .greeting {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .subtext {
            font-size: 15px;
            color: #6b7280;
        }

        /* Stats Grid */
        .metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 48px;
        }

        .metric-card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s;
        }

        .metric-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .metric-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 36px;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: -0.3px;
        }

        .link-text {
            font-size: 14px;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .link-text:hover {
            color: #1a1a1a;
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 56px;
        }

        .event-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
            cursor: pointer;
        }

        .event-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }

        .event-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .event-content {
            padding: 20px;
        }

        .event-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #6b7280;
        }

        .event-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .event-location {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .attendee-count {
            font-size: 13px;
            color: #6b7280;
        }

        .btn-details {
            padding: 6px 14px;
            background: #1a1a1a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-details:hover {
            background: #2d2d2d;
        }

        .btn-registered {
            background: #059669;
        }

        .btn-registered:hover {
            background: #047857;
        }

        /* Announcements */
        .announcements-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .announcement-item {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }

        .announcement-item:hover {
            border-color: #d1d5db;
        }

        .announcement-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .announcement-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-urgent {
            background: #fef2f2;
            color: #991b1b;
        }

        .badge-general {
            background: #f0f9ff;
            color: #075985;
        }

        .badge-reminder {
            background: #fef3c7;
            color: #92400e;
        }

        .announcement-text {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .announcement-meta {
            font-size: 12px;
            color: #9ca3af;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* Logout Button */
        .logout-btn {
            position: fixed;
            bottom: 32px;
            right: 32px;
            padding: 10px 20px;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #2d2d2d;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        @media (max-width: 1024px) {
            .events-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .metrics {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 16px 24px;
            }

            .main-wrapper {
                padding: 32px 24px;
            }

            .nav-links {
                display: none;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .metrics {
                grid-template-columns: 1fr;
            }

            .greeting {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo-area">
                <img src="../assets/images/logo.png" alt="Logo" class="logo-img">
                <span class="brand-name">Event Information System</span>
            </div>

            <div class="nav-links">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="view-events.php" class="nav-link">Events</a>
                <a href="my-events.php" class="nav-link">My Events</a>
                <a href="announcements.php" class="nav-link">Announcements</a>
            </div>

            <div class="user-section" onclick="window.location.href='profile.php'">
                <?php if ($has_custom_pic): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" class="avatar" style="object-fit: cover;">
                <?php else: ?>
                    <div class="avatar">
                        <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-wrapper">
        <div class="page-header">
            <h1 class="greeting">Good <?php echo (date('H') < 12) ? 'morning' : ((date('H') < 18) ? 'afternoon' : 'evening'); ?>, <?php echo htmlspecialchars($first_name); ?></h1>
            <p class="subtext"><?php echo date('l, F j, Y'); ?></p>
        </div>

        <?php display_message(); ?>

        <!-- Metrics -->
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">Upcoming Events</div>
                <div class="metric-value"><?php echo $total_events; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Registered</div>
                <div class="metric-value"><?php echo $registered_events; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Announcements</div>
                <div class="metric-value"><?php echo $total_announcements; ?></div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="section-header">
            <h2 class="section-title">Upcoming Events</h2>
            <a href="view-events.php" class="link-text">View all →</a>
        </div>

        <?php if ($upcoming_events->num_rows > 0): ?>
            <div class="events-grid">
                <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                    <div class="event-item" onclick="window.location.href='event-details.php?id=<?php echo $event['event_id']; ?>'">
                        <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                             alt="Event" class="event-img"
                             onerror="this.style.display='block'">
                        <div class="event-content">
                            <div class="event-meta">
                                <span><?php echo format_date($event['event_date']); ?></span>
                                <span>•</span>
                                <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                            </div>
                            <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                            <p class="event-location"><?php echo htmlspecialchars($event['event_venue']); ?></p>
                            <div class="event-footer">
                                <span class="attendee-count"><?php echo $event['registered_count']; ?> attending</span>
                                <a href="event-details.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn-details <?php echo $event['user_registered'] ? 'btn-registered' : ''; ?>"
                                   onclick="event.stopPropagation()">
                                    <?php echo $event['user_registered'] ? 'Registered' : 'View Details'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No upcoming events</h3>
                <p>Stay tuned for upcoming announcements!</p>
            </div>
        <?php endif; ?>

        <!-- Announcements Section -->
        <div class="section-header">
            <h2 class="section-title">Recent Announcements</h2>
            <a href="announcements.php" class="link-text">View all →</a>
        </div>

        <?php if ($recent_announcements->num_rows > 0): ?>
            <div class="announcements-list">
                <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                    <div class="announcement-item">
                        <div class="announcement-top">
                            <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <span class="badge 
                                <?php 
                                    echo match(strtolower($announcement['category'])) {
                                        'urgent' => 'badge-urgent',
                                        'reminder' => 'badge-reminder',
                                        default => 'badge-general'
                                    };
                                ?>">
                                <?php echo strtoupper($announcement['category']); ?>
                            </span>
                        </div>
                        <p class="announcement-text"><?php echo nl2br(htmlspecialchars(shorten_text($announcement['content'], 150))); ?></p>
                        <div class="announcement-meta">
                            Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                            • <?php echo time_ago($announcement['created_at']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No announcements yet</h3>
                <p>Check back later for updates.</p>
            </div>
        <?php endif; ?>
    </main>

    <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
</body>
</html>
