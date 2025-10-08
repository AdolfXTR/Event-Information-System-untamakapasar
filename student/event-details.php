<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get event details
$sql = "SELECT e.*, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'confirmed') as registered_count,
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ? AND status IN ('pending', 'confirmed')) as is_registered
        FROM events e 
        LEFT JOIN users u ON e.created_by = u.user_id 
        WHERE e.event_id = ? AND e.status = 'published'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $_SESSION['user_id'], $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    header('Location: view-events.php');
    exit();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $insert_sql = "INSERT INTO event_registrations (event_id, user_id, status, registered_at) VALUES (?, ?, 'confirmed', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    
    if ($insert_stmt->execute()) {
        header("Location: event-details.php?id=" . $event_id . "&success=1");
        exit();
    }
}

// Handle unregister
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister'])) {
    $delete_sql = "DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    
    if ($delete_stmt->execute()) {
        header("Location: event-details.php?id=" . $event_id . "&unregistered=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Event Details</title>
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .event-header {
            position: relative;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.95);
            color: #667eea;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .event-body {
            padding: 40px;
        }

        .event-title {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
        }

        .event-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .meta-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .meta-info h4 {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .meta-info p {
            font-size: 15px;
            color: #1a202c;
            font-weight: 600;
        }

        .event-description {
            margin-bottom: 30px;
        }

        .event-description h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 15px;
        }

        .event-description p {
            font-size: 16px;
            line-height: 1.8;
            color: #4a5568;
        }

        .action-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .register-btn, .unregister-btn {
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .register-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .unregister-btn {
            background: #f56565;
            color: white;
        }

        .unregister-btn:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }

        .registered-badge {
            padding: 12px 24px;
            background: #48bb78;
            color: white;
            border-radius: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .alert-info {
            background: #bee3f8;
            color: #2c5282;
            border-left: 4px solid #4299e1;
        }

        @media (max-width: 768px) {
            .event-header {
                height: 250px;
            }

            .event-body {
                padding: 20px;
            }

            .event-title {
                font-size: 24px;
            }

            .event-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="view-events.php" class="back-btn">← Back to Events</a>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Successfully registered for this event!</div>
        <?php endif; ?>

        <?php if(isset($_GET['unregistered'])): ?>
            <div class="alert alert-info">ℹ️ You have been unregistered from this event.</div>
        <?php endif; ?>

        <div class="event-card">
            <div class="event-header">
                <?php if($event['event_image']): ?>
                    <img src="../uploads/events/<?php echo htmlspecialchars($event['event_image']); ?>" alt="Event" class="event-image">
                <?php endif; ?>
                <div class="event-badge"><?php echo htmlspecialchars($event['category']); ?></div>
            </div>

            <div class="event-body">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>

                <div class="event-meta">
                    <div class="meta-item">
                        <div class="meta-icon">📅</div>
                        <div class="meta-info">
                            <h4>DATE</h4>
                            <p><?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                        </div>
                    </div>

                    <div class="meta-item">
                        <div class="meta-icon">⏰</div>
                        <div class="meta-info">
                            <h4>TIME</h4>
                            <p><?php echo date('h:i A', strtotime($event['start_time'])); ?></p>
                        </div>
                    </div>

                    <div class="meta-item">
                        <div class="meta-icon">📍</div>
                        <div class="meta-info">
                            <h4>VENUE</h4>
                            <p><?php echo htmlspecialchars($event['venue']); ?></p>
                        </div>
                    </div>

                    <div class="meta-item">
                        <div class="meta-icon">👥</div>
                        <div class="meta-info">
                            <h4>ATTENDEES</h4>
                            <p><?php echo $event['registered_count']; ?> registered</p>
                        </div>
                    </div>
                </div>

                <div class="event-description">
                    <h3>About This Event</h3>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <div class="action-section">
                    <?php if($event['is_registered']): ?>
                        <span class="registered-badge">✓ You're Registered</span>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="unregister" class="unregister-btn" onclick="return confirm('Are you sure you want to unregister?')">Unregister</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="register" class="register-btn">Register for Event</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>