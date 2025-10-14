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

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Get overall statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM events WHERE created_by = $user_id) as total_events,
    (SELECT COUNT(*) FROM events WHERE created_by = $user_id AND is_published = 1) as published_events,
    (SELECT COUNT(DISTINCT user_id) FROM event_registrations er 
     JOIN events e ON er.event_id = e.event_id 
     WHERE e.created_by = $user_id) as total_students,
    (SELECT COUNT(*) FROM event_registrations er 
     JOIN events e ON er.event_id = e.event_id 
     WHERE e.created_by = $user_id) as total_registrations";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get events with registration counts
$events_query = "SELECT 
    e.event_id,
    e.event_title,
    e.event_date,
    e.event_venue,
    e.max_participants,
    COUNT(er.registration_id) as registration_count,
    SUM(CASE WHEN er.attendance_status = 'attended' THEN 1 ELSE 0 END) as attended_count
FROM events e
LEFT JOIN event_registrations er ON e.event_id = er.event_id
WHERE e.created_by = $user_id 
AND e.event_date BETWEEN '$date_from' AND '$date_to'
GROUP BY e.event_id
ORDER BY e.event_date DESC";
$events_result = $conn->query($events_query);

// Get monthly registration trends
$trends_query = "SELECT 
    DATE_FORMAT(er.registration_date, '%Y-%m') as month,
    COUNT(*) as count
FROM event_registrations er
JOIN events e ON er.event_id = e.event_id
WHERE e.created_by = $user_id
AND er.registration_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY month
ORDER BY month ASC";
$trends_result = $conn->query($trends_query);
$trends_data = [];
while ($row = $trends_result->fetch_assoc()) {
    $trends_data[] = $row;
}

// Get top 5 events by registrations
$top_events_query = "SELECT 
    e.event_title as title,
    COUNT(er.registration_id) as registration_count
FROM events e
LEFT JOIN event_registrations er ON e.event_id = er.event_id
WHERE e.created_by = $user_id
GROUP BY e.event_id, e.event_title
ORDER BY registration_count DESC
LIMIT 5";
$top_events_result = $conn->query($top_events_query);
$top_events_data = [];
while ($row = $top_events_result->fetch_assoc()) {
    $top_events_data[] = $row;
}

// Calculate attendance rate
$attendance_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN er.attendance_status = 'attended' THEN 1 ELSE 0 END) as attended
FROM event_registrations er
JOIN events e ON er.event_id = e.event_id
WHERE e.created_by = $user_id";
$attendance_result = $conn->query($attendance_query);
$attendance = $attendance_result->fetch_assoc();
$attendance_rate = $attendance['total'] > 0 ? round(($attendance['attended'] / $attendance['total']) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Dashboard - SAO Staff</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6366f1;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #6366f1;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #6b7280;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }

        .btn-filter {
            padding: 0.75rem 2rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: #4f46e5;
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue { background: #dbeafe; }
        .stat-icon.green { background: #d1fae5; }
        .stat-icon.purple { background: #e9d5ff; }
        .stat-icon.orange { background: #fed7aa; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .stat-description {
            font-size: 0.85rem;
            color: #6b7280;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            font-size: 1.2rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Events Table */
        .events-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .events-section h3 {
            font-size: 1.2rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
        }

        .events-table thead {
            background: #f9fafb;
        }

        .events-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .events-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        .events-table tr:hover {
            background: #f9fafb;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
        }

        /* Logout Button */
        .logout-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #ef4444;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            transition: all 0.3s;
            z-index: 1000;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            .events-table {
                font-size: 0.85rem;
            }

            .events-table th,
            .events-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">📊 Reports Dashboard</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-events.php">Events</a>
            <a href="announcements.php">Announcements</a>
            <a href="reports.php" style="color: #6366f1;">Reports</a>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($first_name); ?></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📈 Reports & Analytics</h1>
            <p>View comprehensive statistics and insights about your events</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
            </form>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Events</div>
                        <div class="stat-value"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-description"><?php echo $stats['published_events']; ?> published</div>
                    </div>
                    <div class="stat-icon blue">📅</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Registrations</div>
                        <div class="stat-value"><?php echo $stats['total_registrations']; ?></div>
                        <div class="stat-description">All time registrations</div>
                    </div>
                    <div class="stat-icon green">✅</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Unique Students</div>
                        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-description">Registered students</div>
                    </div>
                    <div class="stat-icon purple">👥</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Attendance Rate</div>
                        <div class="stat-value"><?php echo $attendance_rate; ?>%</div>
                        <div class="stat-description"><?php echo $attendance['attended']; ?> of <?php echo $attendance['total']; ?> attended</div>
                    </div>
                    <div class="stat-icon orange">📊</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Registration Trends Chart -->
            <div class="chart-card">
                <h3>📈 Registration Trends (Last 6 Months)</h3>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Top Events Chart -->
            <div class="chart-card">
                <h3>🏆 Top 5 Events by Registrations</h3>
                <div class="chart-container">
                    <canvas id="topEventsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <div class="events-section">
            <h3>📋 Event Performance (Selected Period)</h3>
            <?php if ($events_result->num_rows > 0): ?>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Registrations</th>
                            <th>Attendance</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $events_result->fetch_assoc()): 
                            $reg_count = $event['registration_count'];
                            $attended = $event['attended_count'];
                            $rate = $reg_count > 0 ? round(($attended / $reg_count) * 100) : 0;
                            $capacity = $event['max_participants'] ?: 'Unlimited';
                            $fill_percentage = ($capacity !== 'Unlimited' && $capacity > 0) ? ($reg_count / $capacity) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['event_venue']); ?></td>
                                <td>
                                    <div><?php echo $reg_count; ?> / <?php echo $capacity; ?></div>
                                    <?php if ($fill_percentage > 0): ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min($fill_percentage, 100); ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo $attended; ?> attended</span>
                                </td>
                                <td>
                                    <strong style="color: <?php echo $rate >= 70 ? '#059669' : ($rate >= 50 ? '#f59e0b' : '#ef4444'); ?>">
                                        <?php echo $rate; ?>%
                                    </strong>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No events found</h3>
                    <p>No events found in the selected date range</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>

    <script>
        // Registration Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        const trendsData = <?php echo json_encode($trends_data); ?>;
        
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendsData.map(d => {
                    const date = new Date(d.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Registrations',
                    data: trendsData.map(d => d.count),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Top Events Chart
        const topEventsCtx = document.getElementById('topEventsChart').getContext('2d');
        const topEventsData = <?php echo json_encode($top_events_data); ?>;
        
        new Chart(topEventsCtx, {
            type: 'bar',
            data: {
                labels: topEventsData.map(d => d.event_title.length > 20 ? d.event_title.substring(0, 20) + '...' : d.event_title),
                datasets: [{
                    label: 'Registrations',
                    data: topEventsData.map(d => d.registration_count),
                    backgroundColor: [
                        '#6366f1',
                        '#8b5cf6',
                        '#a78bfa',
                        '#c4b5fd',
                        '#ddd6fe'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>