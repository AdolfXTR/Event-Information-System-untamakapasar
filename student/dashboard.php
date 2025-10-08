<?php
// Start session first!
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

if (!is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

// Get student info
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

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

// Get upcoming events (limit 6)
$upcoming_events_query = "SELECT * FROM events 
                         WHERE is_published = 1 AND event_date >= CURDATE() 
                         ORDER BY event_date ASC LIMIT 6";
$upcoming_events = $conn->query($upcoming_events_query);

// Get recent announcements (limit 3)
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
    <title>Student Dashboard - SAO Events</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f8f9fa;
        }

        /* Top Header Bar */
        .top-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .school-logo {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            object-fit: contain;
        }

        .school-title {
            display: flex;
            flex-direction: column;
        }

        .school-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }

        .school-title p {
            font-size: 0.85rem;
            opacity: 0.95;
            font-weight: 400;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar-top {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            color: #1e3a8a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .user-info-top {
            text-align: right;
        }

        .user-info-top h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-info-top p {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 100px);
            max-width: 100%;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            position: sticky;
            top: 100px;
            height: calc(100vh - 100px);
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            border-right: 1px solid #e5e7eb;
        }

        .sidebar-content {
            padding: 25px 0;
        }

        .nav-section-title {
            padding: 0 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .nav-menu {
            padding: 0;
        }

        .nav-item {
            padding: 14px 25px;
            color: #4b5563;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f3f4f6;
            color: #1e3a8a;
            border-left-color: #3b82f6;
        }

        .nav-item.active {
            background: #eff6ff;
            color: #1e3a8a;
            border-left-color: #1e3a8a;
            font-weight: 600;
        }

        .nav-item span:first-child {
            font-size: 1.3rem;
        }

        .logout-section {
            padding: 20px 25px;
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background: #fee2e2;
            color: #dc2626;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            border: 1px solid #fecaca;
        }

        .logout-btn:hover {
            background: #dc2626;
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px 40px;
            background: #f8f9fa;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #111827;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
        }

        .stat-info h3 {
            font-size: 2.2rem;
            color: #111827;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Section */
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .section-title {
            font-size: 1.4rem;
            color: #111827;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .view-all-link:hover {
            color: #1e3a8a;
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        .event-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-4px);
        }

        .event-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }

        .event-content {
            padding: 20px;
        }

        .event-date {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .event-title {
            font-size: 1.15rem;
            color: #111827;
            margin-bottom: 10px;
            font-weight: 700;
            line-height: 1.4;
        }

        .event-venue {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .event-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #1e3a8a;
            color: white;
        }

        .btn-primary:hover {
            background: #1e40af;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .btn-outline {
            background: white;
            color: #1e3a8a;
            border: 2px solid #1e3a8a;
        }

        .btn-outline:hover {
            background: #1e3a8a;
            color: white;
        }

        /* Announcements */
        .announcement-item {
            padding: 20px;
            border-bottom: 1px solid #f3f4f6;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .announcement-title {
            font-size: 1.1rem;
            color: #111827;
            font-weight: 700;
        }

        .announcement-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-urgent {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-general {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-reminder {
            background: #fef3c7;
            color: #d97706;
        }

        .announcement-content {
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .announcement-footer {
            font-size: 0.85rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .no-data h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            color: #6b7280;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .logo-section {
                flex-direction: column;
                gap: 10px;
            }

            .school-title h1 {
                font-size: 1.2rem;
            }

            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header Bar with Logo -->
    <header class="top-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/images/logo.png" alt="School Logo" class="school-logo">
                <div class="school-title">
                    <h1>Event Information System</h1>
                    <p>Student Affairs Office</p>
                </div>
            </div>
            <div class="user-section">
                <div class="user-info-top">
                    <h3><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h3>
                    <p>Student Account</p>
                </div>
                <div class="user-avatar-top">
                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-content">
                <p class="nav-section-title">Navigation</p>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-item active">
                        <span>🏠</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="view-events.php" class="nav-item">
                        <span>📅</span>
                        <span>Browse Events</span>
                    </a>
                    <a href="my-events.php" class="nav-item">
                        <span>✅</span>
                        <span>My Registered Events</span>
                    </a>
                    <a href="announcements.php" class="nav-item">
                        <span>📢</span>
                        <span>Announcements</span>
                    </a>
                    <a href="profile.php" class="nav-item">
                        <span>👤</span>
                        <span>My Profile</span>
                    </a>
                </nav>

                <div class="logout-section">
                    <a href="../auth/logout.php" class="logout-btn">
                        <span>🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="breadcrumb">
                <a href="dashboard.php">Home</a>
                <span>/</span>
                <span>Dashboard</span>
            </div>

            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
                <p><?php echo date('l, F j, Y'); ?> • Here's what's happening with your events today</p>
            </div>

            <?php display_message(); ?>-item">
                    <span>📢</span>
                    <span>Announcements</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <span>👤</span>
                    <span>Profile</span>
                </a>
            </nav>

            <a href="../auth/logout.php" class="logout-btn">
                🚪 Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
                    <p class="welcome-text"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>

            <?php display_message(); ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        📅
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_events; ?></h3>
                        <p>Upcoming Events</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        ✅
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $registered_events; ?></h3>
                        <p>Registered Events</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        📢
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_announcements; ?></h3>
                        <p>Announcements</p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <h2 class="section-title">🔥 Upcoming Events</h2>
            
            <?php if ($upcoming_events->num_rows > 0): ?>
                <div class="events-grid">
                    <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                        <div class="event-card">
                            <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                                 alt="Event" class="event-image" 
                                 onerror="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'">
                            <div class="event-content">
                                <span class="event-date">
                                    📅 <?php echo format_date($event['event_date']); ?>
                                </span>
                                <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                                <p class="event-venue">
                                    📍 <?php echo htmlspecialchars($event['event_venue']); ?>
                                </p>
                                <div class="event-actions">
                                    <a href="event-details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="view-events.php" class="btn btn-secondary" style="padding: 12px 30px;">
                        View All Events →
                    </a>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📅 No upcoming events at the moment</h3>
                    <p>Check back later for new events!</p>
                </div>
            <?php endif; ?>

            <!-- Recent Announcements -->
            <h2 class="section-title" style="margin-top: 40px;">📢 Recent Announcements</h2>
            
            <?php if ($recent_announcements->num_rows > 0): ?>
                <div class="announcements-list">
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                        <div class="announcement-item">
                            <div class="announcement-header">
                                <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <span class="announcement-badge badge-<?php echo $announcement['announcement_type']; ?>">
                                    <?php echo ucfirst($announcement['announcement_type']); ?>
                                </span>
                            </div>
                            <p class="announcement-content">
                                <?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200))); ?>
                                <?php if (strlen($announcement['content']) > 200) echo '...'; ?>
                            </p>
                            <div class="announcement-footer">
                                Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?> 
                                • <?php echo time_ago($announcement['created_at']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📢 No announcements yet</h3>
                    <p>Stay tuned for updates!</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>