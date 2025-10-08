<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get all announcements
$sql = "SELECT a.*, u.first_name, u.last_name 
        FROM announcements a
        LEFT JOIN users u ON a.posted_by = u.user_id
        WHERE a.status = 'published'
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 30px;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .page-title {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 18px;
        }

        .announcements-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .announcement-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid;
        }

        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .announcement-card.info {
            border-left-color: #4299e1;
        }

        .announcement-card.urgent {
            border-left-color: #f56565;
        }

        .announcement-card.general {
            border-left-color: #48bb78;
        }

        .announcement-card.event {
            border-left-color: #ed8936;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .announcement-type {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-info {
            background: #bee3f8;
            color: #2c5282;
        }

        .type-urgent {
            background: #fed7d7;
            color: #742a2a;
        }

        .type-general {
            background: #c6f6d5;
            color: #22543d;
        }

        .type-event {
            background: #feebc8;
            color: #7c2d12;
        }

        .announcement-time {
            color: #718096;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .announcement-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }

        .announcement-content {
            font-size: 15px;
            line-height: 1.7;
            color: #4a5568;
            margin-bottom: 15px;
        }

        .announcement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .posted-by {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .author-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
        }

        .author-role {
            font-size: 12px;
            color: #718096;
        }

        .priority-badge {
            padding: 6px 12px;
            background: #f56565;
            color: white;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .empty-text {
            color: #718096;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .announcement-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1 class="page-title">📢 Announcements</h1>
            <p class="page-subtitle">Stay updated with the latest news and updates from SAO</p>
        </div>

        <div class="announcements-container">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card <?php echo strtolower($announcement['type']); ?>">
                        <div class="announcement-header">
                            <span class="announcement-type type-<?php echo strtolower($announcement['type']); ?>">
                                <?php 
                                    $icons = [
                                        'info' => '📘',
                                        'urgent' => '🚨',
                                        'general' => '📌',
                                        'event' => '🎉'
                                    ];
                                    echo $icons[strtolower($announcement['type'])] ?? '📌';
                                ?>
                                <?php echo htmlspecialchars($announcement['type']); ?>
                            </span>
                            <div class="announcement-time">
                                🕐 <?php 
                                    $time_ago = time_ago($announcement['created_at']);
                                    echo $time_ago;
                                ?>
                            </div>
                        </div>

                        <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>

                        <div class="announcement-footer">
                            <div class="posted-by">
                                <div class="author-avatar">
                                    <?php echo strtoupper(substr($announcement['first_name'], 0, 1)); ?>
                                </div>
                                <div class="author-info">
                                    <span class="author-name">
                                        <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                                    </span>
                                    <span class="author-role">SAO Staff</span>
                                </div>
                            </div>

                            <?php if (strtolower($announcement['type']) === 'urgent'): ?>
                                <span class="priority-badge">⚠️ HIGH PRIORITY</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h2 class="empty-title">No Announcements Yet</h2>
                    <p class="empty-text">There are no announcements at the moment. Check back later for updates!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// Helper function for time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $seconds = $current - $time;
    
    if ($seconds < 60) {
        return "Just now";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($seconds < 604800) {
        $days = floor($seconds / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date('M d, Y', $time);
    }
}
?>