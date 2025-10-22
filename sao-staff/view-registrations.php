<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_sao_staff()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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
    header("Location: manage-events.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle attendance update
if (isset($_POST['update_attendance'])) {
    $registration_id = intval($_POST['registration_id']);
    $new_status = sanitize_input($_POST['attendance_status']);
    $student_user_id = intval($_POST['student_user_id']);
    
    // Update attendance
    $update_query = "UPDATE event_registrations SET attendance_status = ? WHERE registration_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $registration_id);
    
    if ($stmt->execute()) {
        // Create notification for the student
        $notification_title = "";
        $notification_message = "";
        
        if ($new_status === 'attended') {
            $notification_title = "Attendance Confirmed! ✅";
            $notification_message = "Your attendance for '{$event['event_title']}' has been marked as ATTENDED. Thank you for participating!";
        } elseif ($new_status === 'absent') {
            $notification_title = "Attendance Marked Absent ❌";
            $notification_message = "You were marked as ABSENT for '{$event['event_title']}'. If you believe this is an error, please contact SAO staff.";
        }
        
        // Insert notification
        $notif_query = "INSERT INTO notifications (user_id, notification_type, notification_title, notification_message, event_id, is_read) 
                        VALUES (?, 'attendance', ?, ?, ?, 0)";
        $notif_stmt = $conn->prepare($notif_query);
        $notif_stmt->bind_param("issi", $student_user_id, $notification_title, $notification_message, $event_id);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        log_activity($conn, $user_id, 'Attendance Updated', "Updated attendance for registration ID: $registration_id to $new_status");
        $success_msg = "Attendance updated successfully! Student has been notified.";
    } else {
        $error_msg = "Failed to update attendance.";
    }
    $stmt->close();
}

// Get all registrations
$registrations_query = "SELECT er.*, u.first_name, u.last_name, u.email, u.student_id, u.profile_picture, u.user_id
                        FROM event_registrations er
                        JOIN users u ON er.user_id = u.user_id
                        WHERE er.event_id = ?
                        ORDER BY er.registration_date DESC";
$stmt = $conn->prepare($registrations_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$registrations = $stmt->get_result();
$stmt->close();

// Get statistics
$total_registered = $registrations->num_rows;
$attended_count = $conn->query("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = $event_id AND attendance_status = 'attended'")->fetch_assoc()['count'];
$absent_count = $conn->query("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = $event_id AND attendance_status = 'absent'")->fetch_assoc()['count'];
$pending_count = $total_registered - $attended_count - $absent_count;
$attendance_rate = $total_registered > 0 ? round(($attended_count / $total_registered) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations - SAO Staff</title>
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

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .event-info {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .event-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .event-meta {
            display: flex;
            gap: 2rem;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            color: #667eea;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .stat-card.total h3 { color: #667eea; }
        .stat-card.attended h3 { color: #2e7d32; }
        .stat-card.absent h3 { color: #c62828; }
        .stat-card.pending h3 { color: #ffa726; }

        .registrations-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .search-box {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-icon {
            position: absolute;
            left: 2.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }

        .registrations-table {
            width: 100%;
        }

        .registrations-table thead {
            background: #f8f9fa;
        }

        .registrations-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .registrations-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .registrations-table tr:hover {
            background: #f8f9fa;
        }

        .student-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-info h4 {
            color: #2c3e50;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .student-info p {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge.attended {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.absent {
            background: #ffebee;
            color: #c62828;
        }

        .badge.registered {
            background: #fff3e0;
            color: #f57c00;
        }

        .attendance-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .attendance-select {
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .update-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .registrations-table {
                font-size: 0.85rem;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-clipboard-check"></i> Event Registrations</h1>
            <a href="manage-events.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Events
            </a>
        </div>
    </div>

    <div class="container">
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

        <div class="event-info">
            <div class="event-title">
                🌟 <?php echo htmlspecialchars($event['event_title']); ?>
            </div>
            <div class="event-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                </div>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($event['event_venue']); ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <h3><?php echo $total_registered; ?></h3>
                <p>Total Registrations</p>
            </div>
            <div class="stat-card attended">
                <h3><?php echo $attended_count; ?></h3>
                <p>Attended</p>
            </div>
            <div class="stat-card absent">
                <h3><?php echo $absent_count; ?></h3>
                <p>Absent</p>
            </div>
            <div class="stat-card pending">
                <h3><?php echo $attendance_rate; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>

        <div class="registrations-card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Registered Students</h2>
                <a href="export-registrations.php?id=<?php echo $event_id; ?>" class="export-btn">
                    <i class="fas fa-file-pdf"></i>
                    View Report
                </a>
            </div>

            <div class="search-box" style="position: relative;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search students...">
            </div>

            <?php if ($total_registered > 0): ?>
            <table class="registrations-table">
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
                <tbody id="registrationsTable">
                    <?php 
                    mysqli_data_seek($registrations, 0);
                    while ($reg = $registrations->fetch_assoc()): 
                        $avatar = $reg['profile_picture'] != 'default.jpg' 
                            ? '../assets/images/profiles/' . $reg['profile_picture']
                            : '../assets/images/default-avatar.png';
                    ?>
                    <tr>
                        <td>
                            <div class="student-cell">
                                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Student" class="student-avatar">
                                <div class="student-info">
                                    <h4><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></h4>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo htmlspecialchars($reg['student_id']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($reg['registration_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo $reg['attendance_status']; ?>">
                                <?php echo ucfirst($reg['attendance_status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="attendance-form">
                                <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
                                <input type="hidden" name="student_user_id" value="<?php echo $reg['user_id']; ?>">
                                <select name="attendance_status" class="attendance-select">
                                    <option value="registered" <?php echo $reg['attendance_status'] == 'registered' ? 'selected' : ''; ?>>Registered</option>
                                    <option value="attended" <?php echo $reg['attendance_status'] == 'attended' ? 'selected' : ''; ?>>Attended</option>
                                    <option value="absent" <?php echo $reg['attendance_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                </select>
                                <button type="submit" name="update_attendance" class="update-btn">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Registrations Yet</h3>
                <p>No students have registered for this event</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#registrationsTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Auto-hide alerts
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