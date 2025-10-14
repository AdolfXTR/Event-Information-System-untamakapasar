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

// Get statistics
$total_events_query = "SELECT COUNT(*) as total FROM events WHERE created_by = ?";
$stmt = $conn->prepare($total_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_events = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$published_events_query = "SELECT COUNT(*) as total FROM events WHERE created_by = ? AND is_published = 1";
$stmt = $conn->prepare($published_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$published_events = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$upcoming_events_query = "SELECT COUNT(*) as total FROM events WHERE created_by = ? AND event_date >= CURDATE()";
$stmt = $conn->prepare($upcoming_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_events = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_registrations_query = "SELECT COUNT(*) as total FROM event_registrations er 
                              JOIN events e ON er.event_id = e.event_id 
                              WHERE e.created_by = ?";
$stmt = $conn->prepare($total_registrations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_registrations = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get recent events
$recent_events_query = "SELECT e.*, 
                       (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count
                       FROM events e
                       WHERE e.created_by = ?
                       ORDER BY e.created_at DESC
                       LIMIT 5";
$stmt = $conn->prepare($recent_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_events = $stmt->get_result();
$stmt->close();

// Get recent announcements
$announcements_query = "SELECT * FROM announcements WHERE created_by = ? ORDER BY created_at DESC LIMIT 3";
$stmt = $conn->prepare($announcements_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_announcements = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAO Staff Dashboard</title>
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
            padding: 20px 48px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            width: 48px;
            height: 48px;
            background: white;
            padding: 8px;
            border-radius: 10px;
        }

        .brand-info h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .brand-info p {
            font-size: 13px;
            opacity: 0.9;
        }

        .nav-links {
            display: flex;
            gap: 8px;
        }

        .nav-link {
            padding: 10px 18px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.2);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.15);
            border-radius: 50px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-section:hover {
            background: rgba(255,255,255,0.25);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            color: #1e3a8a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .main-wrapper {
            max-width: 1440px;
            margin: 0 auto;
            padding: 40px 48px 100px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .greeting {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .subtext {
            font-size: 15px;
            color: #6b7280;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #e9d5ff, #d8b4fe);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #fed7aa, #fdba74);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 40px;
        }

        .action-btn {
            padding: 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .action-btn:hover {
            border-color: #1e3a8a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .action-text {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #e5e7eb;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .view-all-link {
            font-size: 13px;
            color: #1e3a8a;
            text-decoration: none;
            font-weight: 600;
        }

        .view-all-link:hover {
            text-decoration: underline;
        }

        /* Event List */
        .event-list-item {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 12px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .event-list-item:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .event-list-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .event-list-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .event-list-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .event-badge-small {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-published {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-draft {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        /* Logout Button */
        .logout-btn {
            position: fixed;
            bottom: 32px;
            right: 32px;
            padding: 12px 24px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 999;
        }

        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 24px;
            }

            .main-wrapper {
                padding: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
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
                <div class="brand-info">
                    <h1>SAO Staff Panel</h1>
                    <p>Event Management System</p>
                </div>
            </div>

            <div class="nav-links">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="manage-events.php" class="nav-link">Events</a>
                <a href="manage-registrations.php" class="nav-link">Registrations</a>
                <a href="announcements.php" class="nav-link">Announcements</a>
                <a href="reports.php" class="nav-link">Reports</a>
            </div>

            <div class="user-section">
                <?php if ($has_custom_pic): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($first_name); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-wrapper">
        <div class="page-header">
            <h1 class="greeting">Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h1>
            <p class="subtext"><?php echo date('l, F j, Y'); ?></p>
        </div>

        <?php display_message(); ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon blue">📅</div>
                </div>
                <div class="stat-value"><?php echo $total_events; ?></div>
                <div class="stat-label">Total Events</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon green">✅</div>
                </div>
                <div class="stat-value"><?php echo $published_events; ?></div>
                <div class="stat-label">Published Events</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon purple">🔮</div>
                </div>
                <div class="stat-value"><?php echo $upcoming_events; ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon orange">👥</div>
                </div>
                <div class="stat-value"><?php echo $total_registrations; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="create-event.php" class="action-btn">
                <div class="action-icon">➕</div>
                <div class="action-text">Create Event</div>
            </a>

            <a href="manage-events.php" class="action-btn">
                <div class="action-icon">📋</div>
                <div class="action-text">Manage Events</div>
            </a>

            <a href="announcements.php" class="action-btn">
                <div class="action-icon">📢</div>
                <div class="action-text">Post Announcement</div>
            </a>

            <a href="reports.php" class="action-btn">
                <div class="action-icon">📊</div>
                <div class="action-text">Generate Reports</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Events -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Events</h2>
                    <a href="manage-events.php" class="view-all-link">View All →</a>
                </div>

                <?php if ($recent_events->num_rows > 0): ?>
                    <?php while ($event = $recent_events->fetch_assoc()): ?>
                        <div class="event-list-item" onclick="window.location.href='edit-event.php?id=<?php echo $event['event_id']; ?>'">
                            <div class="event-list-header">
                                <div>
                                    <div class="event-list-title"><?php echo htmlspecialchars($event['event_title']); ?></div>
                                    <div class="event-list-meta">
                                        <?php echo format_date($event['event_date']); ?> • 
                                        <?php echo $event['registered_count']; ?> registered
                                    </div>
                                </div>
                                <span class="event-badge-small <?php echo $event['is_published'] ? 'badge-published' : 'badge-draft'; ?>">
                                    <?php echo $event['is_published'] ? 'Published' : 'Draft'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No events yet. Create your first event!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Announcements -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Announcements</h2>
                    <a href="announcements.php" class="view-all-link">View All →</a>
                </div>

                <?php if ($recent_announcements->num_rows > 0): ?>
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                        <div class="event-list-item">
                            <div class="event-list-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <div class="event-list-meta">
                                <?php echo time_ago($announcement['created_at']); ?> • 
                                <?php echo ucfirst($announcement['announcement_type']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No announcements posted yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Logout Button -->
    <a href="../auth/logout.php" class="logout-btn">
        🚪 Logout
    </a>
</body>
</html>