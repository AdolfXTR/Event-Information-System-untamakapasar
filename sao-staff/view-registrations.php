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

// Get event ID from URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($event_id == 0) {
    header("Location: manage-events.php");
    exit();
}

// Get event details
$event_query = "SELECT * FROM events WHERE event_id = ? AND created_by = ?";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    set_message('danger', 'Event not found');
    header("Location: manage-events.php");
    exit();
}

// Handle Mark Attendance
if (isset($_POST['mark_attendance'])) {
    $registration_id = intval($_POST['registration_id']);
    $status = sanitize_input($_POST['status']);
    
    $update_stmt = $conn->prepare("UPDATE event_registrations SET attendance_status = ? WHERE registration_id = ?");
    $update_stmt->bind_param("si", $status, $registration_id);
    
    if ($update_stmt->execute()) {
        set_message('success', 'Attendance updated successfully');
    }
    $update_stmt->close();
    
    header("Location: view-registrations.php?id=" . $event_id);
    exit();
}

// Get all registrations for this event
$registrations_query = "SELECT er.*, u.first_name, u.last_name, u.email, u.student_id, u.profile_picture
                        FROM event_registrations er
                        JOIN users u ON er.user_id = u.user_id
                        WHERE er.event_id = ?
                        ORDER BY er.registration_date DESC";
$stmt = $conn->prepare($registrations_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$registrations = $stmt->get_result();
$total_registrations = $registrations->num_rows;
$stmt->close();

// Count attendance statuses
$attended_query = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND attendance_status = 'attended'";
$stmt = $conn->prepare($attended_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$attended_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations - SAO Staff</title>
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
            gap: 20px;
        }

        .back-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .page-title-nav {
            font-size: 18px;
            font-weight: 600;
        }

        .main-container {
            max-width: 1440px;
            margin: 40px auto;
            padding: 0 48px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .event-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .event-meta {
            display: flex;
            gap: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .actions-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-input {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }

        .export-btn {
            padding: 10px 20px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }

        .export-btn:hover {
            background: #1e40af;
        }

        .registrations-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e3a8a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            object-fit: cover;
        }

        .student-details {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .student-id {
            font-size: 12px;
            color: #6b7280;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-registered {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-attended {
            background: #d1fae5;
            color: #065f46;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .attendance-select {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        .mark-btn {
            padding: 6px 12px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mark-btn:hover {
            background: #1e40af;
        }

        .no-registrations {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .no-registrations h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 24px;
            }

            .main-container {
                padding: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-bar {
                flex-direction: column;
                gap: 16px;
            }

            .search-input {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="manage-events.php" class="back-btn">← Back to Events</a>
            <span class="page-title-nav">Event Registrations</span>
        </div>
    </nav>

    <div class="main-container">
        <?php display_message(); ?>

        <div class="page-header">
            <h1 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h1>
            <div class="event-meta">
                <span>📅 <?php echo format_date($event['event_date']); ?></span>
                <span>⏰ <?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                <span>📍 <?php echo htmlspecialchars($event['event_venue']); ?></span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Registrations</div>
                <div class="stat-value"><?php echo $total_registrations; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attended</div>
                <div class="stat-value"><?php echo $attended_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attendance Rate</div>
                <div class="stat-value">
                    <?php echo $total_registrations > 0 ? round(($attended_count / $total_registrations) * 100) : 0; ?>%
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search students...">
            </div>
            <a href="export-registrations.php?id=<?php echo $event_id; ?>" class="export-btn">
                📊 Export to Excel
            </a>
        </div>

        <div class="registrations-table">
            <?php if ($total_registrations > 0): ?>
                <table id="registrationsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Student ID</th>
                            <th>Registered On</th>
                            <th>Status</th>
                            <th>Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($registrations, 0);
                        while ($reg = $registrations->fetch_assoc()): 
                            $has_pic = !empty($reg['profile_picture']) && $reg['profile_picture'] != 'default.jpg';
                            $pic_path = $has_pic ? "../assets/images/profiles/" . htmlspecialchars($reg['profile_picture']) : "";
                        ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <?php if ($has_pic): ?>
                                            <img src="<?php echo $pic_path; ?>" alt="Profile" class="student-avatar">
                                        <?php else: ?>
                                            <div class="student-avatar">
                                                <?php echo strtoupper(substr($reg['first_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="student-details">
                                            <span class="student-name"><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><span class="student-id"><?php echo htmlspecialchars($reg['student_id']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($reg['registration_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $reg['attendance_status']; ?>">
                                        <?php echo ucfirst($reg['attendance_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
                                        <select name="status" class="attendance-select">
                                            <option value="registered" <?php echo $reg['attendance_status'] == 'registered' ? 'selected' : ''; ?>>Registered</option>
                                            <option value="attended" <?php echo $reg['attendance_status'] == 'attended' ? 'selected' : ''; ?>>Attended</option>
                                            <option value="absent" <?php echo $reg['attendance_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                        </select>
                                        <button type="submit" name="mark_attendance" class="mark-btn">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-registrations">
                    <h3>No registrations yet</h3>
                    <p>Students haven't registered for this event</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('registrationsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>