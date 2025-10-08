<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'date_asc';

// Build query
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as user_registered
          FROM events e 
          WHERE e.is_published = 1 AND e.event_date >= CURDATE()";

$params = [$user_id];
$types = "i";

if ($search) {
    $query .= " AND (e.event_title LIKE ? OR e.event_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category) {
    $query .= " AND e.event_category = ?";
    $params[] = $category;
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'date_desc':
        $query .= " ORDER BY e.event_date DESC";
        break;
    case 'title':
        $query .= " ORDER BY e.event_title ASC";
        break;
    case 'popular':
        $query .= " ORDER BY registered_count DESC";
        break;
    default:
        $query .= " ORDER BY e.event_date ASC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result();

// Get categories
$categories_query = "SELECT DISTINCT event_category FROM events WHERE event_category IS NOT NULL AND event_category != ''";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events - SAO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
        }

        .top-header {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }

        .school-title h1 {
            font-size: 1.3rem;
            color: #1e3a8a;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #1e3a8a;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e3a8a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
        }

        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #111827;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        /* Search & Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .filter-input, .filter-select {
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.3s;
        }

        .filter-input:focus, .filter-select:focus {
            border-color: #1e3a8a;
        }

        .btn-search {
            padding: 12px 30px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-search:hover {
            background: #1e40af;
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: #6b7280;
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }

        .event-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
            color: #1e3a8a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .event-body {
            padding: 20px;
        }

        .event-category {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .event-title {
            font-size: 1.3rem;
            color: #111827;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .event-description {
            color: #4b5563;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .attendees-info {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .btn-view {
            padding: 10px 20px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-view:hover {
            background: #1e40af;
        }

        .btn-registered {
            background: #10b981;
        }

        .btn-registered:hover {
            background: #059669;
        }

        .no-events {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .no-events h3 {
            font-size: 1.5rem;
            color: #6b7280;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/images/logo.png" alt="Logo" class="school-logo">
                <div class="school-title">
                    <h1>SAO Events</h1>
                </div>
            </div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="view-events.php" class="active">Events</a>
                <a href="my-events.php">My Events</a>
                <a href="announcements.php">Announcements</a>
            </nav>
            <div class="user-avatar" onclick="window.location.href='profile.php'">
                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-header">
            <h1>Browse Events 🎉</h1>
            <p>Discover and join exciting campus events</p>
        </div>

        <!-- Search & Filter -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Search Events</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['event_category']); ?>"
                                        <?php echo $category == $cat['event_category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['event_category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Sort By</label>
                        <select name="sort" class="filter-select">
                            <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Date (Earliest)</option>
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Date (Latest)</option>
                            <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-search">🔍 Search</button>
                </div>
            </form>
        </div>

        <div class="results-info">
            <span><?php echo $events->num_rows; ?> events found</span>
            <?php if ($search || $category): ?>
                <a href="view-events.php" style="color: #1e3a8a; text-decoration: none; font-weight: 600;">Clear Filters</a>
            <?php endif; ?>
        </div>

        <?php if ($events->num_rows > 0): ?>
            <div class="events-grid">
                <?php while ($event = $events->fetch_assoc()): ?>
                    <div class="event-card">
                        <div style="position: relative;">
                            <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                                 alt="Event" class="event-image"
                                 onerror="this.style.display='block'">
                            <?php if ($event['user_registered'] > 0): ?>
                                <span class="event-badge">✓ Registered</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-body">
                            <?php if ($event['event_category']): ?>
                                <span class="event-category"><?php echo htmlspecialchars($event['event_category']); ?></span>
                            <?php endif; ?>
                            
                            <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                            
                            <div class="event-meta">
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span><?php echo format_date($event['event_date']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>⏰</span>
                                    <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>📍</span>
                                    <span><?php echo htmlspecialchars($event['event_venue']); ?></span>
                                </div>
                            </div>

                            <p class="event-description">
                                <?php echo htmlspecialchars($event['event_description']); ?>
                            </p>

                            <div class="event-footer">
                                <div class="attendees-info">
                                    <span>👥</span>
                                    <span><?php echo $event['registered_count']; ?> attending</span>
                                </div>
                                <a href="event-details.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn-view <?php echo $event['user_registered'] > 0 ? 'btn-registered' : ''; ?>">
                                    <?php echo $event['user_registered'] > 0 ? '✓ Registered' : 'View Details'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-events">
                <h3>📅 No events found</h3>
                <p>Try adjusting your search or filters</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>