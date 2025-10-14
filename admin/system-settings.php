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

$success_msg = '';
$error_msg = '';

// Get system info
$php_version = phpversion();
$mysql_version = $conn->server_info;
$server_software = $_SERVER['SERVER_SOFTWARE'];

// Get database size
$db_size_query = "SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.TABLES 
WHERE table_schema = 'event_information_system'";
$db_size_result = $conn->query($db_size_query);
$db_size = $db_size_result ? $db_size_result->fetch_assoc()['size_mb'] : 0;

// Get table counts
$tables_query = "SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = 'event_information_system'";
$tables_count = $conn->query($tables_query)->fetch_assoc()['count'];

// Get total records
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$total_registrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
$total_logs = $conn->query("SELECT COUNT(*) as count FROM activity_logs")->fetch_assoc()['count'];

// Handle actions
if (isset($_POST['clear_logs'])) {
    $clear_query = "DELETE FROM activity_logs WHERE activity_date < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    if ($conn->query($clear_query)) {
        $deleted = $conn->affected_rows;
        log_activity($conn, $user_id, 'System Maintenance', "Cleared $deleted old activity logs");
        $success_msg = "Successfully cleared $deleted old activity logs!";
    } else {
        $error_msg = "Failed to clear logs.";
    }
}

if (isset($_POST['optimize_db'])) {
    $tables = ['users', 'events', 'event_registrations', 'announcements', 'activity_logs', 'event_reactions', 'event_comments'];
    $optimized = 0;
    foreach ($tables as $table) {
        if ($conn->query("OPTIMIZE TABLE $table")) {
            $optimized++;
        }
    }
    log_activity($conn, $user_id, 'System Maintenance', "Optimized $optimized database tables");
    $success_msg = "Successfully optimized $optimized database tables!";
}

if (isset($_POST['backup_db'])) {
    log_activity($conn, $user_id, 'System Maintenance', "Database backup initiated");
    $success_msg = "Database backup initiated! (Note: Actual backup requires server-side configuration)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Dashboard</title>
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

        .navbar-brand a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .card h2 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .info-label i {
            color: #667eea;
            width: 20px;
        }

        .info-value {
            color: #7f8c8d;
            font-weight: 600;
        }

        .action-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .action-card h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box h4 {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 0.3rem;
        }

        .stat-box p {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .warning-box {
            background: #fff3e0;
            border: 2px solid #ffa726;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .warning-box i {
            color: #fb8c00;
            font-size: 1.5rem;
        }

        .warning-box p {
            color: #f57c00;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 968px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="dashboard.php">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </a>
        </div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($first_name); ?></span>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="user-avatar">
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> System Settings</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_msg; ?>
        </div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- System Information -->
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> System Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fab fa-php"></i>
                            PHP Version
                        </div>
                        <div class="info-value"><?php echo $php_version; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-database"></i>
                            MySQL Version
                        </div>
                        <div class="info-value"><?php echo $mysql_version; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-server"></i>
                            Server Software
                        </div>
                        <div class="info-value"><?php echo explode('/', $server_software)[0]; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-hdd"></i>
                            Database Size
                        </div>
                        <div class="info-value"><?php echo $db_size; ?> MB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-table"></i>
                            Database Tables
                        </div>
                        <div class="info-value"><?php echo $tables_count; ?> tables</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-folder-open"></i>
                            Upload Directory
                        </div>
                        <div class="info-value">
                            <?php echo is_writable('../assets/images/') ? '✅ Writable' : '❌ Not Writable'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Statistics -->
            <div class="card">
                <h2><i class="fas fa-chart-pie"></i> Database Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4><?php echo number_format($total_users); ?></h4>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo number_format($total_events); ?></h4>
                        <p>Total Events</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo number_format($total_registrations); ?></h4>
                        <p>Registrations</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo number_format($total_announcements); ?></h4>
                        <p>Announcements</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo number_format($total_logs); ?></h4>
                        <p>Activity Logs</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo number_format($total_users + $total_events + $total_registrations + $total_announcements + $total_logs); ?></h4>
                        <p>Total Records</p>
                    </div>
                </div>
            </div>

            <!-- Database Maintenance -->
            <div class="card full-width">
                <h2><i class="fas fa-tools"></i> Database Maintenance</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Clear Old Logs -->
                    <div class="action-card">
                        <h3><i class="fas fa-broom"></i> Clear Old Activity Logs</h3>
                        <p>Remove activity logs older than 90 days to reduce database size and improve performance.</p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to clear old logs?');">
                            <button type="submit" name="clear_logs" class="btn btn-warning">
                                <i class="fas fa-trash-alt"></i>
                                Clear Logs (90+ days)
                            </button>
                        </form>
                    </div>

                    <!-- Optimize Database -->
                    <div class="action-card">
                        <h3><i class="fas fa-rocket"></i> Optimize Database</h3>
                        <p>Optimize all database tables to improve query performance and reduce storage overhead.</p>
                        <form method="POST" onsubmit="return confirm('This may take a few minutes. Continue?');">
                            <button type="submit" name="optimize_db" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i>
                                Optimize Tables
                            </button>
                        </form>
                    </div>

                    <!-- Backup Database -->
                    <div class="action-card">
                        <h3><i class="fas fa-download"></i> Backup Database</h3>
                        <p>Create a backup of the entire database. Backups are important for disaster recovery.</p>
                        <form method="POST">
                            <button type="submit" name="backup_db" class="btn btn-success">
                                <i class="fas fa-save"></i>
                                Create Backup
                            </button>
                        </form>
                    </div>
                </div>

                <div class="warning-box" style="margin-top: 1.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Warning:</strong> Database maintenance operations should be performed during low-traffic periods. Always ensure you have a recent backup before performing maintenance tasks.</p>
                </div>
            </div>

            <!-- System Health -->
            <div class="card">
                <h2><i class="fas fa-heartbeat"></i> System Health Check</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-database"></i>
                            Database Connection
                        </div>
                        <div class="info-value" style="color: #2e7d32;">
                            <?php echo $conn->ping() ? '✅ Healthy' : '❌ Error'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-folder"></i>
                            Upload Directory
                        </div>
                        <div class="info-value" style="color: <?php echo is_writable('../assets/images/') ? '#2e7d32' : '#c62828'; ?>;">
                            <?php echo is_writable('../assets/images/') ? '✅ Writable' : '❌ Not Writable'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-key"></i>
                            Session Status
                        </div>
                        <div class="info-value" style="color: #2e7d32;">
                            <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-shield-alt"></i>
                            Error Reporting
                        </div>
                        <div class="info-value">
                            <?php echo error_reporting() ? '⚠️ Enabled' : '✅ Disabled'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Info -->
            <div class="card">
                <h2><i class="fas fa-info"></i> Application Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-code-branch"></i>
                            System Version
                        </div>
                        <div class="info-value">1.0.0</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar"></i>
                            Last Updated
                        </div>
                        <div class="info-value"><?php echo date('F j, Y'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-university"></i>
                            Organization
                        </div>
                        <div class="info-value">SAO Affairs</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-user-shield"></i>
                            Logged In As
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>