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

// Handle user actions
$message = '';
$error = '';

// Suspend/Activate User
if (isset($_POST['toggle_status'])) {
    $target_user_id = intval($_POST['user_id']);
    $current_status = $_POST['current_status'];
    $new_status = $current_status === 'active' ? 'suspended' : 'active';
    
    $update_query = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $target_user_id);
    
    if ($stmt->execute()) {
        log_activity($conn, $user_id, 'User Status Changed', "Changed user ID $target_user_id status to $new_status");
        $message = "User status updated successfully!";
    } else {
        $error = "Failed to update user status.";
    }
    $stmt->close();
}

// Delete User
if (isset($_POST['delete_user'])) {
    $target_user_id = intval($_POST['user_id']);
    
    // Prevent admin from deleting themselves
    if ($target_user_id == $user_id) {
        $error = "You cannot delete your own account!";
    } else {
        $delete_query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $target_user_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'User Deleted', "Deleted user ID $target_user_id");
            $message = "User deleted successfully!";
        } else {
            $error = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// Change User Role
if (isset($_POST['change_role'])) {
    $target_user_id = intval($_POST['user_id']);
    $new_role = sanitize_input($_POST['new_role']);
    
    $update_query = "UPDATE users SET user_type = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_role, $target_user_id);
    
    if ($stmt->execute()) {
        log_activity($conn, $user_id, 'User Role Changed', "Changed user ID $target_user_id role to $new_role");
        $message = "User role updated successfully!";
    } else {
        $error = "Failed to update user role.";
    }
    $stmt->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Build query
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.user_type, u.student_id, u.status, u.profile_picture, u.created_at,
          (SELECT MAX(activity_date) FROM activity_logs WHERE user_id = u.user_id) as last_activity
          FROM users u WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%')";
}

if ($filter_type !== 'all') {
    $query .= " AND user_type = '$filter_type'";
}

if ($filter_status !== 'all') {
    $query .= " AND status = '$filter_status'";
}

$query .= " ORDER BY created_at DESC";

$users = $conn->query($query);

// Get counts
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$students_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
$staff_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'sao_staff'")->fetch_assoc()['count'];
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")->fetch_assoc()['count'];
$active_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$suspended_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'suspended'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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

        /* Navigation Bar */
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

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Page Header */
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

        /* Alert Messages */
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

        /* Stats Cards */
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
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Filter Section */
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

        /* Users Table */
        .users-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: #f8f9fa;
        }

        .users-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-cell img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info h4 {
            color: #2c3e50;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .user-info p {
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

        .badge.student { background: #e3f2fd; color: #1976d2; }
        .badge.sao_staff { background: #f3e5f5; color: #7b1fa2; }
        .badge.admin { background: #ffebee; color: #c62828; }
        .badge.active { background: #e8f5e9; color: #2e7d32; }
        .badge.suspended { background: #ffebee; color: #c62828; }
        .badge.inactive { background: #fafafa; color: #757575; }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #667eea;
            font-size: 1.1rem;
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

        .action-btn.warning {
            color: #ffa726;
        }

        /* Modal */
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

        .btn-warning {
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .users-table {
                font-size: 0.85rem;
            }

            .action-btns {
                flex-direction: column;
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
    <!-- Navigation -->
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
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $students_count; ?></h3>
                <p>Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $staff_count; ?></h3>
                <p>SAO Staff</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $admin_count; ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $active_count; ?></h3>
                <p>Active</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $suspended_count; ?></h3>
                <p>Suspended</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Search Users</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name, email, or student ID..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="type" class="form-control">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="student" <?php echo $filter_type === 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="sao_staff" <?php echo $filter_type === 'sao_staff' ? 'selected' : ''; ?>>SAO Staff</option>
                            <option value="admin" <?php echo $filter_type === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-card">
            <?php if ($users->num_rows > 0): ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>User Type</th>
                        <th>Student ID</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): 
                        $user_pic = $user['profile_picture'] != 'default.jpg' 
                            ? '../assets/images/profiles/' . $user['profile_picture']
                            : '../assets/images/default-avatar.png';
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo htmlspecialchars($user_pic); ?>" alt="User">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $user['user_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn" onclick="openRoleModal(<?php echo $user['user_id']; ?>, '<?php echo $user['user_type']; ?>')" title="Change Role">
                                    <i class="fas fa-user-tag"></i>
                                </button>
                                <button class="action-btn warning" onclick="openStatusModal(<?php echo $user['user_id']; ?>, '<?php echo $user['status']; ?>')" title="<?php echo $user['status'] === 'active' ? 'Suspend' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                </button>
                                <?php if ($user['user_id'] != $user_id): ?>
                                <button class="action-btn danger" onclick="openDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Users Found</h3>
                <p>Try adjusting your search or filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change User Role</h2>
                <button class="close-modal" onclick="closeModal('roleModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="roleUserId">
                    <div class="form-group">
                        <label>Select New Role</label>
                        <select name="new_role" id="newRole" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="sao_staff">SAO Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('roleModal')">Cancel</button>
                    <button type="submit" name="change_role" class="btn btn-primary">Change Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="statusModalTitle">Change User Status</h2>
                <button class="close-modal" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <input type="hidden" name="current_status" id="currentStatus">
                    <p id="statusModalText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" name="toggle_status" class="btn btn-warning" id="statusConfirmBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete User</h2>
                <button class="close-modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    <p style="color: #c62828; margin-top: 1rem;">⚠️ This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRoleModal(userId, currentRole) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('newRole').value = currentRole;
            document.getElementById('roleModal').classList.add('active');
        }

        function openStatusModal(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.getElementById('currentStatus').value = currentStatus;
            
            const title = currentStatus === 'active' ? 'Suspend User' : 'Activate User';
            const text = currentStatus === 'active' 
                ? 'Are you sure you want to suspend this user? They will not be able to access the system.'
                : 'Are you sure you want to activate this user? They will regain access to the system.';
            const btnText = currentStatus === 'active' ? 'Suspend' : 'Activate';
            
            document.getElementById('statusModalTitle').textContent = title;
            document.getElementById('statusModalText').textContent = text;
            document.getElementById('statusConfirmBtn').textContent = btnText;
            document.getElementById('statusModal').classList.add('active');
        }

        function openDeleteModal(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // Auto-hide alerts after 5 seconds
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