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

// Get user profile picture
$user_query = "SELECT profile_picture FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_pic = isset($user_data['profile_picture']) ? $user_data['profile_picture'] : 'default.jpg';
$profile_pic_path = '../assets/images/profiles/' . $profile_pic;
$has_custom_pic = $profile_pic != 'default.jpg' && file_exists($profile_pic_path);

// Get all announcements
$announcements_query = "SELECT a.*, u.first_name as author_first, u.last_name as author_last 
                        FROM announcements a 
                        JOIN users u ON a.created_by = u.user_id 
                        WHERE a.is_published = 1 
                        ORDER BY a.created_at DESC";
$announcements = $conn->query($announcements_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - SAO</title>
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
            max-width: 1200px;
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
            max-width: 900px;
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

        .announcement-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #1e3a8a;
            transition: transform 0.3s;
        }

        .announcement-card:hover {
            transform: translateX(5px);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .announcement-title {
            font-size: 1.5rem;
            color: #111827;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .announcement-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .announcement-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .announcement-content {
            color: #374151;
            font-size: 1.05rem;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .no-announcements {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .no-announcements h3 {
            font-size: 1.5rem;
            color: #6b7280;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .announcement-card {
                padding: 20px;
            }

            .announcement-title {
                font-size: 1.2rem;
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
                <a href="view-events.php">Events</a>
                <a href="my-events.php">My Events</a>
                <a href="announcements.php" class="active">Announcements</a>
            </nav>
            <div class="user-avatar" onclick="window.location.href='profile.php'">
                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-header">
            <h1>📢 Announcements</h1>
            <p>Stay updated with the latest news from Student Affairs Office</p>
        </div>

        <?php if ($announcements->num_rows > 0): ?>
            <?php while ($announcement = $announcements->fetch_assoc()): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div>
                            <h2 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                            <div class="announcement-meta">
                                <div class="meta-item">
                                    <span>👤</span>
                                    <span>Posted by <?php echo htmlspecialchars($announcement['author_first'] . ' ' . $announcement['author_last']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span><?php echo time_ago($announcement['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                        <span class="announcement-badge badge-<?php echo $announcement['announcement_type']; ?>">
                            <?php echo ucfirst($announcement['announcement_type']); ?>
                        </span>
                    </div>

                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-announcements">
                <h3>📢 No announcements yet</h3>
                <p>Check back later for updates from the Student Affairs Office</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>