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

// Get filter parameters
$filter_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';
$filter_date = isset($_GET['date']) ? sanitize_input($_GET['date']) : 'all';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query
$query = "SELECT al.*, u.first_name, u.last_name, u.user_type, u.profile_picture 
          FROM activity_logs al
          JOIN users u ON al.user_id = u.user_id
          WHERE 1=1";

if ($filter_user > 0) {
    $query .= " AND al.user_id = $filter_user";
}

if ($filter_type !== 'all') {
    $query .= " AND u.user_type = '$filter_type'";
}

if (!empty($search)) {
    $query .= " AND (al.activity_type LIKE '%$search%' OR al.activity_details LIKE '%$search%')";
}

switch ($filter_date) {
    case 'today':
        $query .= " AND DATE(al.activity_date) = CURDATE()";
        break;
    case 'yesterday':
        $query .= " AND DATE(al.activity_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $query .= " AND al.activity_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $query .= " AND al.activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

$query .= " ORDER BY al.activity_date DESC LIMIT 500";

$logs = $conn->query($query);

// Get statistics
$total_logs = $conn->query("SELECT COUNT(*) as count FROM activity_logs")->fetch_assoc()['count'];
$today_logs = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(activity_date) = CURDATE()")->fetch_assoc()['count'];
$week_logs = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Get unique users for filter
$users_query = "SELECT DISTINCT u.user_id, u.first_name, u.last_name 
                FROM activity_logs al 
                JOIN users u ON al.user_id = u.user_id 
                ORDER BY u.first_name, u.last_name";
$users_list = $conn->query($users_query);

// Get activity type counts
$activity_types_query = "SELECT activity_type, COUNT(*) as count 
                         FROM activity_logs 
                         GROUP BY activity_type 
                         ORDER BY count DESC 
                         LIMIT 5";
$activity_types = $conn->query($activity_types_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Dashboard</title>
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

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Page Header */
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

        /* Stats Cards */
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

        .stat-content h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .stat-content p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 2rem;
        }

        /* Activity Timeline */
        .logs-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
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

        .logs-count {
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .timeline {
            max-height: 700px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2.5rem;
            padding-bottom: 2rem;
            border-left: 2px solid #e0e0e0;
        }

        .timeline-item:last-child {
            border-left: none;
            padding-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            left: -1.2rem;
            top: 0;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .timeline-icon.login { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .timeline-icon.logout { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .timeline-icon.create { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .timeline-icon.edit { background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%); }
        .timeline-icon.delete { background: linear-gradient(135deg, #e53935 0%, #c62828 100%); }
        .timeline-icon.register { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .timeline-icon.default { background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%); }

        .timeline-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            position: relative;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.8rem;
        }

        .timeline-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .timeline-user img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info h4 {
            font-size: 0.95rem;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.student { background: #e3f2fd; color: #1976d2; }
        .badge.sao_staff { background: #f3e5f5; color: #7b1fa2; }
        .badge.admin { background: #ffebee; color: #c62828; }

        .timeline-time {
            font-size: 0.8rem;
            color: #7f8c8d;
            white-space: nowrap;
        }

        .timeline-action {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .timeline-details {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.5;
        }

        /* Sidebar */
        .sidebar-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .sidebar-card h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.8rem;
        }

        .activity-type-item:last-child {
            margin-bottom: 0;
        }

        .activity-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .activity-count {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Custom Scrollbar */
        .timeline::-webkit-scrollbar {
            width: 8px;
        }

        .timeline::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .timeline::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .timeline::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .filter-grid {
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

            .timeline-item {
                padding-left: 2rem;
            }

            .timeline-icon {
                left: -1rem;
                width: 2rem;
                height: 2rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-history"></i> System Activity Logs</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_logs); ?></h3>
                    <p>Total Activity Logs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($today_logs); ?></h3>
                    <p>Activities Today</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($week_logs); ?></h3>
                    <p>Activities This Week</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Search Activity</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by activity type or details..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>User</label>
                        <select name="user" class="form-control">
                            <option value="0">All Users</option>
                            <?php while ($user = $users_list->fetch_assoc()): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                    <?php echo $filter_user == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="type" class="form-control">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="student" <?php echo $filter_type === 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="sao_staff" <?php echo $filter_type === 'sao_staff' ? 'selected' : ''; ?>>SAO Staff</option>
                            <option value="admin" <?php echo $filter_type === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Time Period</label>
                        <select name="date" class="form-control">
                            <option value="all" <?php echo $filter_date === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $filter_date === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo $filter_date === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="week" <?php echo $filter_date === 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $filter_date === 'month' ? 'selected' : ''; ?>>This Month</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Activity Timeline -->
            <div class="logs-card">
                <div class="card-header">
                    <h2><i class="fas fa-stream"></i> Activity Timeline</h2>
                    <span class="logs-count"><?php echo $logs->num_rows; ?> activities</span>
                </div>

                <?php if ($logs->num_rows > 0): ?>
                <div class="timeline">
                    <?php while ($log = $logs->fetch_assoc()): 
                        $log_user_pic = $log['profile_picture'] != 'default.jpg' 
                            ? '../assets/images/profiles/' . $log['profile_picture']
                            : '../assets/images/default-avatar.png';
                        
                        // Determine icon class based on activity type
                        $icon_class = 'default';
                        $icon = 'fa-circle';
                        
                        if (stripos($log['activity_type'], 'login') !== false) {
                            $icon_class = 'login';
                            $icon = 'fa-sign-in-alt';
                        } elseif (stripos($log['activity_type'], 'logout') !== false) {
                            $icon_class = 'logout';
                            $icon = 'fa-sign-out-alt';
                        } elseif (stripos($log['activity_type'], 'create') !== false) {
                            $icon_class = 'create';
                            $icon = 'fa-plus';
                        } elseif (stripos($log['activity_type'], 'edit') !== false || stripos($log['activity_type'], 'update') !== false) {
                            $icon_class = 'edit';
                            $icon = 'fa-edit';
                        } elseif (stripos($log['activity_type'], 'delete') !== false) {
                            $icon_class = 'delete';
                            $icon = 'fa-trash';
                        } elseif (stripos($log['activity_type'], 'register') !== false) {
                            $icon_class = 'register';
                            $icon = 'fa-user-plus';
                        }
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo $icon_class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <div class="timeline-user">
                                    <img src="<?php echo htmlspecialchars($log_user_pic); ?>" alt="User">
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></h4>
                                        <span class="badge <?php echo $log['user_type']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['user_type'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="timeline-time">
                                    <?php echo time_ago($log['activity_date']); ?>
                                </div>
                            </div>
                            <div class="timeline-action">
                                <?php echo htmlspecialchars($log['activity_type']); ?>
                            </div>
                            <?php if (!empty($log['activity_details'])): ?>
                            <div class="timeline-details">
                                <?php echo htmlspecialchars($log['activity_details']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Activity Logs Found</h3>
                    <p>Try adjusting your search or filters</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Top Activities -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-chart-bar"></i> Top Activities</h3>
                    <?php while ($activity = $activity_types->fetch_assoc()): ?>
                    <div class="activity-type-item">
                        <span class="activity-name"><?php echo htmlspecialchars($activity['activity_type']); ?></span>
                        <span class="activity-count"><?php echo number_format($activity['count']); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Legend -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-info-circle"></i> Activity Types</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon login" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">User Login</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon logout" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">User Logout</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon create" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-plus"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">Create Action</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon edit" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-edit"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">Edit/Update</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon delete" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-trash"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">Delete Action</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div class="timeline-icon register" style="position: static; width: 2rem; height: 2rem; font-size: 0.8rem;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <span style="font-size: 0.9rem; color: #2c3e50;">Registration</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>