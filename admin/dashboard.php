<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || $_SESSION['user_type'] !== 'admin') {
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

$profile_pic = isset($user_data['profile_picture']) && $user_data['profile_picture'] != 'default.jpg' 
    ? '../assets/images/profiles/' . $user_data['profile_picture'] 
    : '../assets/images/default-avatar.png';

// Get system statistics
$stats = [];

// Total users by type
$user_stats = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
$result = $conn->query($user_stats);
while ($row = $result->fetch_assoc()) {
    $stats[$row['user_type']] = $row['count'];
}

// Total events
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$stats['total_events'] = $total_events;

// Total registrations
$total_registrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];
$stats['total_registrations'] = $total_registrations;

// Total announcements
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
$stats['total_announcements'] = $total_announcements;

// Active users (logged in last 30 days)
$active_users = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM activity_logs WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
$stats['active_users'] = $active_users;

// Recent users WITH LAST ACTIVITY - FIXED!
$recent_users_query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.user_type, u.status, u.created_at, u.profile_picture,
                       (SELECT MAX(activity_date) FROM activity_logs WHERE user_id = u.user_id) as last_activity
                       FROM users u
                       ORDER BY u.created_at DESC 
                       LIMIT 10";
$recent_users = $conn->query($recent_users_query);

// Recent activity logs
$recent_activity_query = "SELECT al.*, u.first_name, u.last_name, u.user_type 
                          FROM activity_logs al
                          JOIN users u ON al.user_id = u.user_id
                          ORDER BY al.activity_date DESC
                          LIMIT 15";
$recent_activity = $conn->query($recent_activity_query);

// System health checks
$system_health = [
    'database' => $conn->ping() ? 'Healthy' : 'Error',
    'uploads' => is_writable('../assets/images/') ? 'Writable' : 'Error',
    'sessions' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Error'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Information System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        /* Navigation Bar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar-brand i {
            font-size: 2rem;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #7f8c8d;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-icon.teal { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }

        .stat-content h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .stat-content p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .quick-actions h2 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 1.5rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .card-link:hover {
            text-decoration: underline;
        }

        /* Recent Users Table */
        .users-table {
            width: 100%;
        }

        .users-table tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table tr:last-child {
            border-bottom: none;
        }

        .users-table td {
            padding: 1rem 0.5rem;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-cell img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-cell-info h4 {
            font-size: 0.95rem;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }

        .user-cell-info p {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge.student { background: #e3f2fd; color: #1976d2; }
        .badge.sao_staff { background: #f3e5f5; color: #7b1fa2; }
        .badge.admin { background: #ffebee; color: #c62828; }
        .badge.active { background: #e8f5e9; color: #2e7d32; }
        .badge.inactive { background: #fafafa; color: #757575; }
        .badge.suspended { background: #ffebee; color: #c62828; }

        /* Activity Log */
        .activity-log {
            max-height: 500px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid #667eea;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .activity-user {
            font-weight: 600;
            color: #2c3e50;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .activity-action {
            font-size: 0.9rem;
            color: #555;
        }

        .activity-details {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 0.3rem;
        }

        /* System Health */
        .health-grid {
            display: grid;
            gap: 1rem;
        }

        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .health-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .health-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .health-status.healthy {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .health-status.error {
            background: #ffebee;
            color: #c62828;
        }

        /* Logout Button */
        .logout-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.5);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
            }

            .logout-btn {
                bottom: 1rem;
                right: 1rem;
                padding: 0.8rem 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-shield-alt"></i>
            <div>
                <div>Admin Panel</div>
                <div style="font-size: 0.8rem; opacity: 0.9;">Event Information System</div>
            </div>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                <div class="user-role">System Administrator</div>
            </div>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="user-avatar">
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> System Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($first_name); ?>! Here's what's happening in your system today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($stats['student']) ? $stats['student'] : 0; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($stats['sao_staff']) ? $stats['sao_staff'] : 0; ?></h3>
                    <p>SAO Staff Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_events']; ?></h3>
                    <p>Total Events</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_registrations']; ?></h3>
                    <p>Event Registrations</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_announcements']; ?></h3>
                    <p>Announcements</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon teal">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['active_users']; ?></h3>
                    <p>Active Users (30 days)</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="manage-users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
                <a href="system-logs.php" class="action-btn">
                    <i class="fas fa-history"></i>
                    <span>View System Logs</span>
                </a>
                <a href="manage-events.php" class="action-btn">
                    <i class="fas fa-calendar-alt"></i>
                    <span>View All Events</span>
                </a>
                <a href="system-settings.php" class="action-btn">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-user-plus"></i> Recent Users</h2>
                    <a href="manage-users.php" class="card-link">View All →</a>
                </div>
                <table class="users-table">
                    <?php while ($user = $recent_users->fetch_assoc()): 
                        $user_pic = $user['profile_picture'] != 'default.jpg' 
                            ? '../assets/images/profiles/' . $user['profile_picture']
                            : '../assets/images/default-avatar.png';
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User">
                                <div class="user-cell-info">
                                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $user['user_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td style="text-align: right; color: #7f8c8d; font-size: 0.85rem;">
                            <?php 
                            // THIS IS THE FIX - Shows last activity, not created date!
                            echo !empty($user['last_activity']) ? time_ago($user['last_activity']) : 'Never active'; 
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- System Health -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-heartbeat"></i> System Health</h2>
                    </div>
                    <div class="health-grid">
                        <div class="health-item">
                            <div class="health-name">
                                <i class="fas fa-database"></i>
                                Database
                            </div>
                            <span class="health-status <?php echo strtolower($system_health['database']); ?>">
                                <?php echo $system_health['database']; ?>
                            </span>
                        </div>
                        <div class="health-item">
                            <div class="health-name">
                                <i class="fas fa-folder-open"></i>
                                File Uploads
                            </div>
                            <span class="health-status <?php echo $system_health['uploads'] == 'Writable' ? 'healthy' : 'error'; ?>">
                                <?php echo $system_health['uploads']; ?>
                            </span>
                        </div>
                        <div class="health-item">
                            <div class="health-name">
                                <i class="fas fa-key"></i>
                                Sessions
                            </div>
                            <span class="health-status <?php echo $system_health['sessions'] == 'Active' ? 'healthy' : 'error'; ?>">
                                <?php echo $system_health['sessions']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Activity</h2>
                        <a href="system-logs.php" class="card-link">View All →</a>
                    </div>
                    <div class="activity-log">
                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-header">
                                <div>
                                    <div class="activity-user">
                                        <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                    </div>
                                    <span class="badge <?php echo $activity['user_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['user_type'])); ?>
                                    </span>
                                </div>
                                <div class="activity-time">
                                    <?php echo time_ago($activity['activity_date']); ?>
                                </div>
                            </div>
                            <div class="activity-action">
                                <?php echo htmlspecialchars($activity['activity_type']); ?>
                            </div>
                            <?php if (!empty($activity['activity_details'])): ?>
                            <div class="activity-details">
                                <?php echo htmlspecialchars($activity['activity_details']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <a href="../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</body>
</html>