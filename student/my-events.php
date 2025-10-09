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

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';

// Get registered events
$query = "SELECT e.*, er.registration_date, er.attendance_status,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as total_attendees
          FROM event_registrations er
          JOIN events e ON er.event_id = e.event_id
          WHERE er.user_id = ?";

if ($filter == 'upcoming') {
    $query .= " AND e.event_date >= CURDATE()";
} elseif ($filter == 'past') {
    $query .= " AND e.event_date < CURDATE()";
}

$query .= " ORDER BY e.event_date " . ($filter == 'past' ? 'DESC' : 'ASC');

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

// Get statistics
$total_query = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_registered = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$upcoming_query = "SELECT COUNT(*) as total FROM event_registrations er 
                   JOIN events e ON er.event_id = e.event_id 
                   WHERE er.user_id = ? AND e.event_date >= CURDATE()";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$attended_query = "SELECT COUNT(*) as total FROM event_registrations 
                   WHERE user_id = ? AND attendance_status = 'attended'";
$stmt = $conn->prepare($attended_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$attended_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Events - SAO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
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
        }

        .brand-name {
            font-size: 18px;
            font-weight: 600;
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

        .nav-link:hover, .nav-link.active {
            background: #f3f4f6;
            color: #1a1a1a;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1a1a1a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            object-fit: cover;
        }

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

        /* Stats */
        .metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .metric-card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
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
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e5e7eb;
        }

        .filter-tab {
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            color: #1a1a1a;
        }

        .filter-tab.active {
            color: #1a1a1a;
            border-bottom-color: #1a1a1a;
        }

        /* Events List */
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .event-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            gap: 24px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .event-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .event-image-small {
            width: 140px;
            height: 140px;
            border-radius: 10px;
            object-fit: cover;
            background: #f3f4f6;
            flex-shrink: 0;
        }

        .event-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-header-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .event-title-main {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .event-badges {
            display: flex;
            gap: 8px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-registered {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-attended {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-past {
            background: #f3f4f6;
            color: #6b7280;
        }

        .event-meta-row {
            display: flex;
            gap: 24px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #6b7280;
        }

        .meta-item-inline {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .event-description-short {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .registered-info {
            font-size: 13px;
            color: #6b7280;
        }

        .btn-view-small {
            padding: 8px 16px;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-view-small:hover {
            background: #2d2d2d;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #fafafa;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 24px;
        }

        .btn-browse {
            display: inline-block;
            padding: 12px 24px;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-browse:hover {
            background: #2d2d2d;
        }

        @media (max-width: 968px) {
            .event-item {
                flex-direction: column;
            }

            .event-image-small {
                width: 100%;
                height: 180px;
            }

            .metrics {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .navbar {
                padding: 16px 24px;
            }

            .main-wrapper {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo-area">
                <img src="../assets/images/logo.png" alt="Logo" class="logo-img">
                <span class="brand-name">Event Information System</span>
            </div>

            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="view-events.php" class="nav-link">Events</a>
                <a href="my-events.php" class="nav-link active">My Events</a>
                <a href="announcements.php" class="nav-link">Announcements</a>
            </div>

            <div class="user-avatar" onclick="window.location.href='profile.php'">
                <?php if ($has_custom_pic): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-wrapper">
        <div class="page-header">
            <h1 class="greeting">My Registered Events</h1>
            <p class="subtext">Manage and view all your event registrations</p>
        </div>

        <!-- Stats -->
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">Total Registered</div>
                <div class="metric-value"><?php echo $total_registered; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Upcoming</div>
                <div class="metric-value"><?php echo $upcoming_count; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Attended</div>
                <div class="metric-value"><?php echo $attended_count; ?></div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=upcoming" class="filter-tab <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">
                Upcoming Events
            </a>
            <a href="?filter=past" class="filter-tab <?php echo $filter == 'past' ? 'active' : ''; ?>">
                Past Events
            </a>
            <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                All Events
            </a>
        </div>

        <!-- Events List -->
        <?php if ($events->num_rows > 0): ?>
            <div class="events-list">
                <?php while ($event = $events->fetch_assoc()): ?>
                    <div class="event-item" onclick="window.location.href='event-details.php?id=<?php echo $event['event_id']; ?>'">
                        <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                             alt="Event" class="event-image-small"
                             onerror="this.style.display='block'">
                        
                        <div class="event-details">
                            <div class="event-header-row">
                                <div>
                                    <h3 class="event-title-main"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                                    <div class="event-meta-row">
                                        <div class="meta-item-inline">
                                            <span>📅</span>
                                            <span><?php echo format_date($event['event_date']); ?></span>
                                        </div>
                                        <div class="meta-item-inline">
                                            <span>⏰</span>
                                            <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                        </div>
                                        <div class="meta-item-inline">
                                            <span>📍</span>
                                            <span><?php echo htmlspecialchars($event['event_venue']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-badges">
                                    <?php if ($event['attendance_status'] == 'attended'): ?>
                                        <span class="badge badge-attended">Attended</span>
                                    <?php elseif (strtotime($event['event_date']) < time()): ?>
                                        <span class="badge badge-past">Past Event</span>
                                    <?php else: ?>
                                        <span class="badge badge-registered">Registered</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="event-description-short">
                                <?php echo htmlspecialchars($event['event_description']); ?>
                            </p>

                            <div class="event-footer-row">
                                <div class="registered-info">
                                    Registered <?php echo time_ago($event['registration_date']); ?> • 
                                    <?php echo $event['total_attendees']; ?> attendees
                                </div>
                                <a href="event-details.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn-view-small" onclick="event.stopPropagation()">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No events found</h3>
                <p>
                    <?php if ($filter == 'upcoming'): ?>
                        You haven't registered for any upcoming events yet.
                    <?php elseif ($filter == 'past'): ?>
                        You don't have any past events.
                    <?php else: ?>
                        You haven't registered for any events yet.
                    <?php endif; ?>
                </p>
                <a href="view-events.php" class="btn-browse">Browse Events</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>