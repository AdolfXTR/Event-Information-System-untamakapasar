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

// Handle delete event
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    
    $delete_query = "DELETE FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        log_activity($conn, $user_id, 'Event Deleted', "Admin deleted event ID: $event_id");
        $success_msg = "Event deleted successfully!";
    } else {
        $error_msg = "Failed to delete event.";
    }
    $stmt->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';
$filter_creator = isset($_GET['creator']) ? intval($_GET['creator']) : 0;

// Build query
$query = "SELECT e.*, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
          FROM events e
          JOIN users u ON e.created_by = u.user_id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (e.event_title LIKE '%$search%' OR e.event_description LIKE '%$search%' OR e.event_venue LIKE '%$search%')";
}

if ($filter_status === 'published') {
    $query .= " AND e.is_published = 1";
} elseif ($filter_status === 'draft') {
    $query .= " AND e.is_published = 0";
} elseif ($filter_status === 'upcoming') {
    $query .= " AND e.event_date >= CURDATE() AND e.is_published = 1";
} elseif ($filter_status === 'past') {
    $query .= " AND e.event_date < CURDATE()";
}

if ($filter_creator > 0) {
    $query .= " AND e.created_by = $filter_creator";
}

$query .= " ORDER BY e.event_date DESC";

$events = $conn->query($query);

// Get statistics
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$published_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE is_published = 1")->fetch_assoc()['count'];
$draft_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE is_published = 0")->fetch_assoc()['count'];
$upcoming_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE() AND is_published = 1")->fetch_assoc()['count'];
$past_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date < CURDATE()")->fetch_assoc()['count'];

// Get all SAO staff for filter
$staff_query = "SELECT user_id, first_name, last_name FROM users WHERE user_type = 'sao_staff' ORDER BY first_name, last_name";
$staff_list = $conn->query($staff_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Events - Admin Dashboard</title>
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
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
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

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .event-content {
            padding: 1.5rem;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .event-title {
            font-size: 1.2rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .meta-item i {
            color: #667eea;
            width: 16px;
        }

        .event-description {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .event-creator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge.published {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.draft {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge.past {
            background: #fafafa;
            color: #757575;
        }

        .event-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #667eea;
            font-size: 1rem;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .action-btn:hover {
            background: #f0f0f0;
            transform: scale(1.1);
        }

        .action-btn.danger {
            color: #f5576c;
        }

        .registration-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #7f8c8d;
            cursor: pointer;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #2c3e50;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
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

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 968px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .stats-grid {
                grid-template-columns: 1fr;
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
            <h1><i class="fas fa-calendar-alt"></i> All Events</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_msg; ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_events; ?></h3>
                <p>Total Events</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $published_events; ?></h3>
                <p>Published</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $draft_events; ?></h3>
                <p>Drafts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $upcoming_events; ?></h3>
                <p>Upcoming</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $past_events; ?></h3>
                <p>Past Events</p>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Search Events</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title, description, or venue..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Events</option>
                            <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Drafts</option>
                            <option value="upcoming" <?php echo $filter_status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="past" <?php echo $filter_status === 'past' ? 'selected' : ''; ?>>Past</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Created By</label>
                        <select name="creator" class="form-control">
                            <option value="0">All Creators</option>
                            <?php while ($staff = $staff_list->fetch_assoc()): ?>
                            <option value="<?php echo $staff['user_id']; ?>" 
                                    <?php echo $filter_creator == $staff['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <?php if ($events->num_rows > 0): ?>
        <div class="events-grid">
            <?php while ($event = $events->fetch_assoc()): 
                $event_image = !empty($event['event_image']) 
                    ? '../assets/images/events/' . $event['event_image']
                    : '../assets/images/default-event.jpg';
                
                $is_past = strtotime($event['event_date']) < time();
                $status_badge = $is_past ? 'past' : ($event['is_published'] ? 'published' : 'draft');
                $status_text = $is_past ? 'Past Event' : ($event['is_published'] ? 'Published' : 'Draft');
            ?>
            <div class="event-card">
                <img src="<?php echo htmlspecialchars($event_image); ?>" alt="Event" class="event-image">
                <div class="event-content">
                    <div class="event-header">
                        <div style="flex: 1;">
                            <div class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></div>
                            <span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
                        </div>
                    </div>

                    <div class="event-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($event['event_venue']); ?></span>
                        </div>
                    </div>

                    <div class="event-description">
                        <?php echo htmlspecialchars($event['event_description']); ?>
                    </div>

                    <div class="event-footer">
                        <div>
                            <div class="event-creator">
                                <i class="fas fa-user"></i>
                                Created by <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                            </div>
                            <div class="registration-info">
                                <i class="fas fa-users"></i>
                                <?php echo $event['registration_count']; ?> 
                                <?php if ($event['max_participants']): ?>
                                    / <?php echo $event['max_participants']; ?>
                                <?php endif; ?>
                                registered
                            </div>
                        </div>
                        <div class="event-actions">
                            <button class="action-btn" onclick="window.location.href='../sao-staff/view-registrations.php?id=<?php echo $event['event_id']; ?>'" title="View Registrations">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn danger" onclick="openDeleteModal(<?php echo $event['event_id']; ?>, '<?php echo addslashes($event['event_title']); ?>')" title="Delete Event">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>No Events Found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Event</h2>
                <button class="close-modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <p>Are you sure you want to delete "<strong id="deleteEventTitle"></strong>"?</p>
                    <p style="color: #c62828; margin-top: 1rem;">⚠️ This will also delete all registrations and cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_event" class="btn btn-danger">Delete Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(eventId, eventTitle) {
            document.getElementById('deleteEventId').value = eventId;
            document.getElementById('deleteEventTitle').textContent = eventTitle;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

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