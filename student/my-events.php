<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get registered events
$sql = "SELECT e.*, er.registered_at, er.status,
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'confirmed') as attendees_count
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.user_id = ? AND er.status IN ('pending', 'confirmed')
        ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$registered_events = $stmt->get_result();

// Count upcoming vs past events
$upcoming_count = 0;
$past_count = 0;
$events_array = [];

while($event = $registered_events->fetch_assoc()) {
    $events_array[] = $event;
    if(strtotime($event['event_date']) >= strtotime('today')) {
        $upcoming_count++;
    } else {
        $past_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Events</title>
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
            max-width: 1200px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
        }

        .events-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .event-card {
            background: #f7fafc;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .event-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .event-image-container {
            flex-shrink: 0;
            width: 200px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-header-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .event-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .event-category {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .event-meta-row {
            display: flex;
            gap: 25px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        .meta-item span {
            font-weight: 600;
        }

        .event-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .registered-badge {
            padding: 8px 16px;
            background: #48bb78;
            color: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .view-details-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            margin-bottom: 25px;
        }

        .browse-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .past-event {
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .event-card {
                flex-direction: column;
            }

            .event-image-container {
                width: 100%;
                height: 200px;
            }

            .event-footer {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .view-details-btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1 class="page-title">My Registered Events</h1>
            <p class="page-subtitle">Manage all your event registrations in one place</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?php echo $upcoming_count; ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo count($events_array); ?></div>
                <div class="stat-label">Total Registered</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?php echo $past_count; ?></div>
                <div class="stat-label">Past Events</div>
            </div>
        </div>

        <div class="events-section">
            <?php if(count($events_array) > 0): ?>
                
                <?php if($upcoming_count > 0): ?>
                    <h2 class="section-title">Upcoming Events</h2>
                    <?php foreach($events_array as $event): ?>
                        <?php if(strtotime($event['event_date']) >= strtotime('today')): ?>
                            <div class="event-card">
                                <div class="event-image-container">
                                    <?php if($event['event_image']): ?>
                                        <img src="../uploads/events/<?php echo htmlspecialchars($event['event_image']); ?>" alt="Event" class="event-image">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:white;font-size:48px;">📅</div>
                                    <?php endif; ?>
                                </div>

                                <div class="event-details">
                                    <div class="event-header-row">
                                        <div>
                                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                                        </div>
                                    </div>

                                    <div class="event-meta-row">
                                        <div class="meta-item">
                                            📅 <span><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            ⏰ <span><?php echo date('h:i A', strtotime($event['start_time'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            📍 <span><?php echo htmlspecialchars($event['venue']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            👥 <span><?php echo $event['attendees_count']; ?> attending</span>
                                        </div>
                                    </div>

                                    <div class="event-description">
                                        <?php echo substr(htmlspecialchars($event['description']), 0, 150) . '...'; ?>
                                    </div>

                                    <div class="event-footer">
                                        <span class="registered-badge">✓ Registered on <?php echo date('M d', strtotime($event['registered_at'])); ?></span>
                                        <a href="event-details.php?id=<?php echo $event['event_id']; ?>" class="view-details-btn">View Details →</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if($past_count > 0): ?>
                    <h2 class="section-title" style="margin-top: 40px;">Past Events</h2>
                    <?php foreach($events_array as $event): ?>
                        <?php if(strtotime($event['event_date']) < strtotime('today')): ?>
                            <div class="event-card past-event">
                                <div class="event-image-container">
                                    <?php if($event['event_image']): ?>
                                        <img src="../uploads/events/<?php echo htmlspecialchars($event['event_image']); ?>" alt="Event" class="event-image">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:white;font-size:48px;">📅</div>
                                    <?php endif; ?>
                                </div>

                                <div class="event-details">
                                    <div class="event-header-row">
                                        <div>
                                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                                        </div>
                                    </div>

                                    <div class="event-meta-row">
                                        <div class="meta-item">
                                            📅 <span><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            ⏰ <span><?php echo date('h:i A', strtotime($event['start_time'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            📍 <span><?php echo htmlspecialchars($event['venue']); ?></span>
                                        </div>
                                    </div>

                                    <div class="event-footer">
                                        <span class="registered-badge" style="background: #a0aec0;">✓ Attended</span>
                                        <a href="event-details.php?id=<?php echo $event['event_id']; ?>" class="view-details-btn">View Details →</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h2 class="empty-title">No Events Yet</h2>
                    <p class="empty-text">You haven't registered for any events yet. Browse available events and join something exciting!</p>
                    <a href="view-events.php" class="browse-btn">Browse Events</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>