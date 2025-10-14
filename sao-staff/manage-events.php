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

// Handle Delete Event
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    
    // Delete event image if exists
    $img_query = "SELECT event_image FROM events WHERE event_id = ? AND created_by = ?";
    $stmt = $conn->prepare($img_query);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $img_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($img_result && !empty($img_result['event_image'])) {
        $img_path = '../assets/images/' . $img_result['event_image'];
        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }
    
    // Delete event
    $delete_stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND created_by = ?");
    $delete_stmt->bind_param("ii", $event_id, $user_id);
    
    if ($delete_stmt->execute()) {
        set_message('success', 'Event deleted successfully');
        log_activity($conn, $user_id, 'Event Deleted', 'Deleted event ID: ' . $event_id);
    } else {
        set_message('danger', 'Failed to delete event');
    }
    $delete_stmt->close();
    
    header("Location: manage-events.php");
    exit();
}

// Handle Toggle Publish
if (isset($_POST['toggle_publish'])) {
    $event_id = intval($_POST['event_id']);
    $is_published = intval($_POST['is_published']);
    
    $update_stmt = $conn->prepare("UPDATE events SET is_published = ? WHERE event_id = ? AND created_by = ?");
    $update_stmt->bind_param("iii", $is_published, $event_id, $user_id);
    
    if ($update_stmt->execute()) {
        $status = $is_published ? 'published' : 'unpublished';
        set_message('success', 'Event ' . $status);
        log_activity($conn, $user_id, 'Event Status Changed', 'Event ID ' . $event_id . ' ' . $status);
    }
    $update_stmt->close();
    
    header("Location: manage-events.php");
    exit();
}

// Get filter and search params
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query based on filters
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
          FROM events e 
          WHERE e.created_by = ?";

$params = [$user_id];
$types = "i";

if ($search != '') {
    $query .= " AND (e.event_title LIKE ? OR e.event_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($filter == 'published') {
    $query .= " AND e.is_published = 1";
} elseif ($filter == 'draft') {
    $query .= " AND e.is_published = 0";
} elseif ($filter == 'upcoming') {
    $query .= " AND e.event_date >= CURDATE()";
} elseif ($filter == 'past') {
    $query .= " AND e.event_date < CURDATE()";
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - SAO Staff</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .create-btn {
            padding: 12px 24px;
            background: #1e3a8a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }

        .create-btn:hover {
            background: #1e40af;
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

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .search-filter-bar {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .search-filter-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .search-input {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            padding: 10px 20px;
            background: #f3f4f6;
            color: #6b7280;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .filter-btn:hover {
            background: #e5e7eb;
        }

        .filter-btn.active {
            background: #1e3a8a;
            color: white;
        }

        .search-btn {
            padding: 10px 24px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .search-btn:hover {
            background: #1e40af;
        }

        .events-table {
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
            font-size: 12px;
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

        .event-title-col {
            font-weight: 600;
            color: #1a1a1a;
        }

        .event-category {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .event-created {
            font-size: 12px;
            color: #9ca3af;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
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

        .event-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .view-btn {
            background: #dbeafe;
            color: #1e40af;
        }

        .view-btn:hover {
            background: #bfdbfe;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1e40af;
        }

        .edit-btn:hover {
            background: #bfdbfe;
        }

        .toggle-btn {
            background: #f3f4f6;
            color: #374151;
        }

        .toggle-btn:hover {
            background: #e5e7eb;
        }

        .delete-btn {
            background: #fee2e2;
            color: #991b1b;
        }

        .delete-btn:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        /* Delete Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            max-width: 400px;
            width: 90%;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #1a1a1a;
        }

        .modal-text {
            color: #6b7280;
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-btn-delete {
            background: #dc2626;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 24px;
            }

            .main-container {
                padding: 24px;
            }

            .search-filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 12px 8px;
            }

            .event-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <span class="page-title-nav">Manage Events</span>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Manage Events</h1>
            <a href="create-event.php" class="create-btn">+ Create New Event</a>
        </div>

        <?php display_message(); ?>

        <div class="search-filter-bar">
            <form method="GET" class="search-filter-grid">
                <div class="form-group">
                    <label class="form-label">Search Events</label>
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search by title or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Filter By</label>
                    <div class="filter-tabs">
                        <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?filter=published" class="filter-btn <?php echo $filter == 'published' ? 'active' : ''; ?>">Published</a>
                        <a href="?filter=draft" class="filter-btn <?php echo $filter == 'draft' ? 'active' : ''; ?>">Draft</a>
                        <a href="?filter=upcoming" class="filter-btn <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
                        <a href="?filter=past" class="filter-btn <?php echo $filter == 'past' ? 'active' : ''; ?>">Past</a>
                    </div>
                </div>

                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <div class="events-table">
            <?php if ($events->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>EVENT DETAILS</th>
                            <th>DATE & TIME</th>
                            <th>VENUE</th>
                            <th>REGISTRATIONS</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="event-title-col">
                                        <?php echo htmlspecialchars($event['event_title']); ?>
                                    </div>
                                    <?php if (!empty($event['event_category'])): ?>
                                        <div class="event-category"><?php echo htmlspecialchars($event['event_category']); ?></div>
                                    <?php endif; ?>
                                    <div class="event-created">Created <?php echo time_ago($event['created_at']); ?></div>
                                </td>
                                <td>
                                    <?php echo format_date($event['event_date']); ?><br>
                                    <span style="font-size: 12px; color: #6b7280;">
                                        <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($event['event_venue']); ?></td>
                                <td>
                                    <?php echo $event['registration_count']; ?> 
                                    <?php if ($event['max_participants']): ?>
                                        / <?php echo $event['max_participants']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $event['is_published'] ? 'badge-published' : 'badge-draft'; ?>">
                                        <?php echo $event['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="event-actions">
                                        <a href="view-registrations.php?id=<?php echo $event['event_id']; ?>" class="action-btn view-btn">View</a>
                                        <a href="edit-event.php?id=<?php echo $event['event_id']; ?>" class="action-btn edit-btn">Edit</a>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <input type="hidden" name="is_published" value="<?php echo $event['is_published'] ? 0 : 1; ?>">
                                            <button type="submit" name="toggle_publish" class="action-btn toggle-btn">
                                                <?php echo $event['is_published'] ? 'Unpublish' : 'Publish'; ?>
                                            </button>
                                        </form>

                                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $event['event_id']; ?>, '<?php echo htmlspecialchars(addslashes($event['event_title'])); ?>')">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No events found</h3>
                    <p>Create your first event to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Delete Event?</h2>
            <p class="modal-text">Are you sure you want to delete "<span id="eventName"></span>"? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="event_id" id="deleteEventId">
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="delete_event" class="modal-btn modal-btn-delete">Delete Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(eventId, eventTitle) {
            document.getElementById('deleteEventId').value = eventId;
            document.getElementById('eventName').textContent = eventTitle;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>