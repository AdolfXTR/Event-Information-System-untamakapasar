<?php
// Start session first!
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

if (!is_student()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle Reaction AJAX
if (isset($_POST['action']) && $_POST['action'] == 'react') {
    $event_id = $_POST['event_id'];
    $reaction_type = $_POST['reaction_type'];
    
    // Check if user already reacted
    $check_stmt = $conn->prepare("SELECT reaction_id FROM event_reactions WHERE event_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $event_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing reaction
        $update_stmt = $conn->prepare("UPDATE event_reactions SET reaction_type = ? WHERE event_id = ? AND user_id = ?");
        $update_stmt->bind_param("sii", $reaction_type, $event_id, $user_id);
        $update_stmt->execute();
    } else {
        // Insert new reaction
        $insert_stmt = $conn->prepare("INSERT INTO event_reactions (event_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $event_id, $user_id, $reaction_type);
        $insert_stmt->execute();
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Handle Comment AJAX
if (isset($_POST['action']) && $_POST['action'] == 'comment') {
    $event_id = $_POST['event_id'];
    $comment_text = sanitize_input($_POST['comment_text']);
    
    $stmt = $conn->prepare("INSERT INTO event_comments (event_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $event_id, $user_id, $comment_text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    exit();
}

// Get all upcoming events with reactions and comments count
$events_query = "SELECT e.*, 
                 (SELECT COUNT(*) FROM event_reactions WHERE event_id = e.event_id) as total_reactions,
                 (SELECT COUNT(*) FROM event_comments WHERE event_id = e.event_id) as total_comments,
                 (SELECT reaction_type FROM event_reactions WHERE event_id = e.event_id AND user_id = ?) as user_reaction
                 FROM events e 
                 WHERE e.is_published = 1 AND e.event_date >= CURDATE() 
                 ORDER BY e.created_at DESC";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Newsfeed - SAO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 12px 0;
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
            padding: 0 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }

        .school-title h1 {
            font-size: 1.3rem;
            color: #1877f2;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1877f2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
        }

        /* Main Container */
        .main-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* Post Card (Event Card) */
        .post-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .post-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .post-author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #42b0ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .post-author-info h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #050505;
        }

        .post-author-info p {
            font-size: 0.8rem;
            color: #65676b;
        }

        .post-content {
            padding: 0 16px 16px;
        }

        .event-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #050505;
            margin-bottom: 8px;
        }

        .event-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #65676b;
            font-size: 0.9rem;
        }

        .event-description {
            color: #050505;
            line-height: 1.5;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            background: linear-gradient(135deg, #1877f2, #42b0ff);
        }

        /* Reactions Bar */
        .reactions-bar {
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e4e6eb;
            font-size: 0.9rem;
            color: #65676b;
        }

        .reactions-count {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .reaction-icons {
            display: flex;
            margin-left: -2px;
        }

        .reaction-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            border: 2px solid white;
            margin-left: -4px;
        }

        .comments-count {
            cursor: pointer;
        }

        /* Actions Bar */
        .actions-bar {
            padding: 4px 16px;
            display: flex;
            border-top: 1px solid #e4e6eb;
            gap: 4px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            background: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: #65676b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s;
            position: relative;
        }

        .action-btn:hover {
            background: #f2f3f5;
        }

        .action-btn.active {
            color: #1877f2;
        }

        /* Reaction Picker */
        .reaction-picker {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 30px;
            padding: 8px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 8px;
        }

        .reaction-picker.show {
            display: flex;
            gap: 8px;
            animation: popIn 0.2s;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: translateX(-50%) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) scale(1);
            }
        }

        .reaction-option {
            font-size: 1.8rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .reaction-option:hover {
            transform: scale(1.3);
        }

        /* Comments Section */
        .comments-section {
            padding: 16px;
            border-top: 1px solid #e4e6eb;
            display: none;
        }

        .comments-section.show {
            display: block;
        }

        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e4e6eb;
            color: #65676b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
        }

        .comment-bubble {
            background: #f0f2f5;
            border-radius: 18px;
            padding: 8px 12px;
            display: inline-block;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.85rem;
            color: #050505;
            margin-bottom: 2px;
        }

        .comment-text {
            font-size: 0.9rem;
            color: #050505;
        }

        .comment-time {
            font-size: 0.75rem;
            color: #65676b;
            margin-top: 4px;
            margin-left: 12px;
        }

        /* Comment Input */
        .comment-input-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 12px;
        }

        .comment-input {
            flex: 1;
            background: #f0f2f5;
            border: none;
            border-radius: 20px;
            padding: 10px 16px;
            font-size: 0.9rem;
            resize: none;
            outline: none;
        }

        .comment-submit {
            background: #1877f2;
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .comment-submit:hover {
            background: #166fe5;
        }

        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 8px;
            }

            .post-card {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="top-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/images/logo.png" alt="Logo" class="school-logo">
                <div class="school-title">
                    <h1>SAO Events</h1>
                </div>
            </div>
            <div class="user-menu">
                <div class="user-avatar" onclick="window.location.href='dashboard.php'">
                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Feed -->
    <div class="main-container">
        <?php if ($events->num_rows > 0): ?>
            <?php while ($event = $events->fetch_assoc()): ?>
                <div class="post-card" data-event-id="<?php echo $event['event_id']; ?>">
                    <!-- Post Header -->
                    <div class="post-header">
                        <div class="post-author-avatar">📢</div>
                        <div class="post-author-info">
                            <h3>Student Affairs Office</h3>
                            <p><?php echo time_ago($event['created_at']); ?></p>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="post-content">
                        <h2 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h2>
                        
                        <div class="event-meta">
                            <span class="meta-item">📅 <?php echo format_date($event['event_date']); ?></span>
                            <span class="meta-item">⏰ <?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                            <span class="meta-item">📍 <?php echo htmlspecialchars($event['event_venue']); ?></span>
                        </div>

                        <p class="event-description">
                            <?php echo nl2br(htmlspecialchars($event['event_description'])); ?>
                        </p>
                    </div>

                    <!-- Post Image -->
                    <img src="../assets/images/<?php echo htmlspecialchars($event['event_image']); ?>" 
                         alt="Event" class="post-image"
                         onerror="this.style.display='block'">

                    <!-- Reactions Bar -->
                    <div class="reactions-bar">
                        <div class="reactions-count">
                            <?php if ($event['total_reactions'] > 0): ?>
                                <div class="reaction-icons">
                                    <span class="reaction-icon" style="background: #1877f2;">👍</span>
                                    <span class="reaction-icon" style="background: #f33e58;">❤️</span>
                                </div>
                                <span><?php echo $event['total_reactions']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="comments-count" onclick="toggleComments(<?php echo $event['event_id']; ?>)">
                            <?php echo $event['total_comments']; ?> Comments
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="actions-bar">
                        <button class="action-btn react-btn <?php echo $event['user_reaction'] ? 'active' : ''; ?>" 
                                onclick="showReactionPicker(this)" 
                                data-event-id="<?php echo $event['event_id']; ?>">
                            <span><?php 
                                $reactions = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢'];
                                echo $event['user_reaction'] ? $reactions[$event['user_reaction']] : '👍';
                            ?></span>
                            <span><?php echo $event['user_reaction'] ? ucfirst($event['user_reaction']) : 'Like'; ?></span>
                            
                            <div class="reaction-picker">
                                <span class="reaction-option" data-reaction="like" title="Like">👍</span>
                                <span class="reaction-option" data-reaction="love" title="Love">❤️</span>
                                <span class="reaction-option" data-reaction="haha" title="Haha">😂</span>
                                <span class="reaction-option" data-reaction="wow" title="Wow">😮</span>
                                <span class="reaction-option" data-reaction="sad" title="Sad">😢</span>
                            </div>
                        </button>
                        <button class="action-btn" onclick="toggleComments(<?php echo $event['event_id']; ?>)">
                            <span>💬</span>
                            <span>Comment</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='event-details.php?id=<?php echo $event['event_id']; ?>'">
                            <span>ℹ️</span>
                            <span>Details</span>
                        </button>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section" id="comments-<?php echo $event['event_id']; ?>">
                        <div class="comments-list">
                            <?php
                            $comments_query = "SELECT c.*, u.first_name, u.last_name 
                                             FROM event_comments c 
                                             JOIN users u ON c.user_id = u.user_id 
                                             WHERE c.event_id = ? 
                                             ORDER BY c.created_at DESC LIMIT 5";
                            $comments_stmt = $conn->prepare($comments_query);
                            $comments_stmt->bind_param("i", $event['event_id']);
                            $comments_stmt->execute();
                            $comments = $comments_stmt->get_result();
                            
                            while ($comment = $comments->fetch_assoc()):
                            ?>
                                <div class="comment-item">
                                    <div class="comment-avatar">
                                        <?php echo strtoupper(substr($comment['first_name'], 0, 1)); ?>
                                    </div>
                                    <div class="comment-content">
                                        <div class="comment-bubble">
                                            <div class="comment-author">
                                                <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                            </div>
                                            <div class="comment-text">
                                                <?php echo htmlspecialchars($comment['comment_text']); ?>
                                            </div>
                                        </div>
                                        <div class="comment-time"><?php echo time_ago($comment['created_at']); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Comment Input -->
                        <div class="comment-input-wrapper">
                            <div class="comment-avatar">
                                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                            </div>
                            <input type="text" class="comment-input" 
                                   placeholder="Write a comment..." 
                                   onkeypress="if(event.key==='Enter') postComment(<?php echo $event['event_id']; ?>, this)">
                            <button class="comment-submit" onclick="postComment(<?php echo $event['event_id']; ?>, this.previousElementSibling)">
                                ➤
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-events">
                <h2>📅 No upcoming events</h2>
                <p>Check back later for new events!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showReactionPicker(btn) {
            const picker = btn.querySelector('.reaction-picker');
            const eventId = btn.dataset.eventId;
            
            // Toggle picker
            picker.classList.toggle('show');
            
            // Add click listeners to reactions
            picker.querySelectorAll('.reaction-option').forEach(option => {
                option.onclick = function(e) {
                    e.stopPropagation();
                    const reactionType = this.dataset.reaction;
                    reactToEvent(eventId, reactionType, btn);
                    picker.classList.remove('show');
                };
            });
            
            // Close picker when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closePickerfunction() {
                    picker.classList.remove('show');
                    document.removeEventListener('click', closePicker);
                }, {once: true});
            }, 10);
        }

        function reactToEvent(eventId, reactionType, btn) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=react&event_id=${eventId}&reaction_type=${reactionType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function toggleComments(eventId) {
            const commentsSection = document.getElementById(`comments-${eventId}`);
            commentsSection.classList.toggle('show');
            if (commentsSection.classList.contains('show')) {
                commentsSection.querySelector('.comment-input').focus();
            }
        }

        function postComment(eventId, input) {
            const commentText = input.value.trim();
            if (!commentText) return;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=comment&event_id=${eventId}&comment_text=${encodeURIComponent(commentText)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>