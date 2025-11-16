<?php
require_once 'config.php';
require_login();

// ADMIN ONLY - Redirect non-admins
if (!has_permission('admin')) {
    $_SESSION['notification'] = ['message' => 'Access denied. Admin only.', 'type' => 'error'];
    echo '<script>window.location.href = "index.php";</script>';
    exit();
}

// Get user to edit (default to first user or from URL)
$edit_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// If no user selected, get first user
if ($edit_user_id === 0) {
    $first_user = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $edit_user_id = $first_user['id'];
    echo '<script>window.location.href = "admin_users.php?user_id=' . $edit_user_id . '";</script>';
    exit();
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_profile_image'])) {
    $target_user_id = intval($_POST['user_id']);
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($_FILES['profile_image']['size'] <= 5000000) {
                // Delete old profile image if exists
                $old_image_result = $db->query("SELECT profile_image FROM users WHERE id = $target_user_id");
                if ($old_image_result && $old_image_result->num_rows > 0) {
                    $old_image = $old_image_result->fetch_assoc()['profile_image'];
                    if ($old_image && file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
                
                $new_filename = 'user_' . $target_user_id . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->bind_param("si", $target_file, $target_user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['notification'] = ['message' => 'Profile image updated successfully!', 'type' => 'success'];
                        log_audit('update', "Admin updated profile image for user ID: $target_user_id");
                    }
                }
            } else {
                $_SESSION['notification'] = ['message' => 'Image size must be less than 5MB', 'type' => 'error'];
            }
        } else {
            $_SESSION['notification'] = ['message' => 'Invalid image format', 'type' => 'error'];
        }
    }
    
    echo '<script>window.location.href = "admin_users.php?user_id=' . $target_user_id . '";</script>';
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $target_user_id = intval($_POST['user_id']);
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $role = sanitize_input($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Check if username is already taken by another user
    $check_username = $db->query("SELECT id FROM users WHERE username = '$username' AND id != $target_user_id");
    if ($check_username->num_rows > 0) {
        $_SESSION['notification'] = ['message' => 'Username already taken!', 'type' => 'error'];
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, role = ?, active = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $full_name, $email, $username, $role, $active, $target_user_id);
        
        if ($stmt->execute()) {
            $_SESSION['notification'] = ['message' => 'User profile updated successfully!', 'type' => 'success'];
            log_audit('update', "Admin updated profile for user: $username");
            
            // Update session if editing own profile
            if ($target_user_id == $_SESSION['user_id']) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
            }
        }
    }
    
    echo '<script>window.location.href = "admin_users.php?user_id=' . $target_user_id . '";</script>';
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $target_user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) >= 6) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $target_user_id);
        
        if ($stmt->execute()) {
            $_SESSION['notification'] = ['message' => 'Password reset successfully!', 'type' => 'success'];
            log_audit('update', "Admin reset password for user ID: $target_user_id");
        }
    } else {
        $_SESSION['notification'] = ['message' => 'Password must be at least 6 characters', 'type' => 'error'];
    }
    
    echo '<script>window.location.href = "admin_users.php?user_id=' . $target_user_id . '";</script>';
    exit();
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $target_user_id = intval($_GET['delete_user']);
    
    // Cannot delete yourself
    if ($target_user_id == $_SESSION['user_id']) {
        $_SESSION['notification'] = ['message' => 'You cannot delete your own account!', 'type' => 'error'];
    } else {
        $user = $db->query("SELECT username, profile_image FROM users WHERE id = $target_user_id")->fetch_assoc();
        if ($user) {
            // Delete profile image
            if ($user['profile_image'] && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }
            
            $db->query("DELETE FROM users WHERE id = $target_user_id");
            log_audit('delete', "Admin deleted user: {$user['username']}");
            $_SESSION['notification'] = ['message' => 'User deleted successfully!', 'type' => 'success'];
        }
    }
    
    echo '<script>window.location.href = "admin_users.php";</script>';
    exit();
}

// Handle add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = sanitize_input($_POST['new_username']);
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $full_name = sanitize_input($_POST['new_full_name']);
    $email = sanitize_input($_POST['new_email']);
    $role = sanitize_input($_POST['new_role']);
    
    // Check if username already exists
    $check = $db->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $_SESSION['notification'] = ['message' => 'Username already exists', 'type' => 'error'];
    } else {
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $full_name, $email, $role);
        
        if ($stmt->execute()) {
            $new_user_id = $db->lastInsertId();
            log_audit('create', "Admin added new user: $username with role $role");
            $_SESSION['notification'] = ['message' => 'User added successfully!', 'type' => 'success'];
            echo '<script>window.location.href = "admin_users.php?user_id=' . $new_user_id . '";</script>';
            exit();
        }
    }
}

// Fetch user data
$user_data = $db->query("SELECT * FROM users WHERE id = $edit_user_id")->fetch_assoc();

if (!$user_data) {
    echo '<script>window.location.href = "admin_users.php";</script>';
    exit();
}

// Get profile image
$profile_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_data['full_name']) . '&size=150&background=1976d2&color=fff&bold=true';
if (!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])) {
    $profile_image = $user_data['profile_image'];
}

// Get all users for sidebar list
$all_users = $db->query("SELECT id, username, full_name, role, active, profile_image FROM users ORDER BY username");

// Activity stats for selected user
$activity_stats = $db->query("SELECT 
    COUNT(DISTINCT DATE(created_at)) as days_active,
    COUNT(*) as total_actions,
    MAX(created_at) as last_activity
    FROM audit_logs 
    WHERE user = '{$user_data['username']}'")->fetch_assoc();

// Get recent activity
$recent_activity = $db->query("SELECT action, description, created_at 
    FROM audit_logs 
    WHERE user = '{$user_data['username']}' 
    ORDER BY created_at DESC 
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container {
            display: flex;
            height: 100vh;
            background: #f5f5f5;
        }
        
        .users-sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
            flex-shrink: 0;
        }
        
        .users-sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .user-list-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-list-item:hover {
            background: #f5f5f5;
        }
        
        .user-list-item.active {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
        }
        
        .user-list-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-list-info {
            flex: 1;
        }
        
        .user-list-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .user-list-role {
            font-size: 11px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 3px;
        }
        
        .user-content {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }
        
        .profile-header-admin {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .profile-image-container {
            position: relative;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }
        
        .profile-image-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .profile-info h1 {
            margin-bottom: 8px;
            font-size: 28px;
        }
        
        .profile-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 10px;
        }
        
        .danger-zone {
            background: #ffebee;
            border: 2px solid #ef5350;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-mini {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .stat-mini-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .stat-mini-label {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .add-user-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00897b 0%, #00796b 100%);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .add-user-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
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
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            background: #1976d2;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #1976d2;
        }
        
        .activity-action {
            font-weight: 600;
            color: #1976d2;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .activity-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .activity-time {
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Users Sidebar -->
        <div class="users-sidebar">
            <div class="users-sidebar-header">
                <h3><i class="fas fa-users-cog"></i> All Users</h3>
                <p style="font-size: 12px; opacity: 0.9; margin-top: 5px;">Click to edit</p>
                <a href="index.php" class="btn btn-secondary btn-small" style="margin-top: 10px; width: 100%;">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
            
            <?php while ($user = $all_users->fetch_assoc()): 
                $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&size=40&background=1976d2&color=fff';
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    $user_avatar = $user['profile_image'];
                }
                $is_active = ($user['id'] == $edit_user_id) ? 'active' : '';
            ?>
            <div class="user-list-item <?php echo $is_active; ?>" onclick="window.location.href='admin_users.php?user_id=<?php echo $user['id']; ?>'">
                <img src="<?php echo $user_avatar; ?>" class="user-list-avatar" alt="Avatar">
                <div class="user-list-info">
                    <div class="user-list-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-list-role">
                        <i class="fas fa-circle" style="font-size: 6px; color: <?php echo $user['active'] ? '#4caf50' : '#999'; ?>;"></i>
                        <?php echo get_role_display($user['role']); ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- User Content -->
        <div class="user-content">
            
            <?php if (isset($_SESSION['notification'])): ?>
                <div class="alert alert-<?php echo $_SESSION['notification']['type'] === 'success' ? 'success' : 'error'; ?>">
                    <i class="fas fa-<?php echo $_SESSION['notification']['type'] === 'success' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <span><?php echo $_SESSION['notification']['message']; ?></span>
                </div>
                <?php unset($_SESSION['notification']); ?>
                                <?php endif; ?>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <?php if ($edit_user_id != $_SESSION['user_id']): ?>
            <div class="danger-zone">
                <h3 style="color: #c62828; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> Danger Zone
                </h3>
                <p style="color: #721c24; margin-bottom: 20px;">
                    Deleting this user will permanently remove their account and all associated data. This action cannot be undone.
                </p>
                <button onclick="deleteUser(<?php echo $edit_user_id; ?>)" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete User
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add User Button -->
    <button class="add-user-btn" onclick="openAddUserModal()">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <span class="modal-close" onclick="closeAddUserModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="new_full_name" placeholder="Enter full name" required>
                </div>
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="new_username" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="new_email" placeholder="user@example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="new_password" placeholder="Enter password" required minlength="6">
                    <small style="color: #666;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="new_role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator - Full system access</option>
                        <option value="manager">Manager - Reports and analytics</option>
                        <option value="sales">Sales Clerk - Sales and customers</option>
                        <option value="warehouse">Warehouse Staff - Stock management</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Profile image upload preview and submit
        document.getElementById('imageInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                document.getElementById('imageForm').submit();
            }
        });
        
        // Delete user confirmation
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
                window.location.href = 'admin_users.php?delete_user=' + userId;
            }
        }
        
        // Add user modal
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('active');
        }
        
        // Close modal on outside click
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('addUserModal');
            if (e.target === modal) {
                closeAddUserModal();
            }
        });
    </script>
</body>
</html>
            
            <!-- Profile Header -->
            <div class="profile-header-admin">
                <div class="profile-image-container">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image" id="profilePreview">
                    <form method="POST" enctype="multipart/form-data" id="imageForm" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user_id; ?>">
                        <input type="hidden" name="upload_profile_image" value="1">
                        <label for="imageInput" class="profile-image-upload">
                            <i class="fas fa-camera" style="color: #1976d2;"></i>
                        </label>
                        <input type="file" id="imageInput" name="profile_image" style="display: none;" accept="image/*">
                    </form>
                </div>
                <div class="profile-info" style="flex: 1;">
                    <h1><?php echo htmlspecialchars($user_data['full_name']); ?></h1>
                    <p style="font-size: 16px; opacity: 0.9;">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p style="font-size: 14px; opacity: 0.85;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <span class="profile-badge">
                            <i class="fas fa-shield-alt"></i> <?php echo get_role_display($user_data['role']); ?>
                        </span>
                        <span class="profile-badge" style="background: <?php echo $user_data['active'] ? 'rgba(76,175,80,0.3)' : 'rgba(239,83,80,0.3)'; ?>">
                            <i class="fas fa-circle" style="font-size: 8px;"></i> 
                            <?php echo $user_data['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <div class="stats-mini">
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $activity_stats['days_active']; ?></div>
                            <div class="stat-mini-label">Days Active</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $activity_stats['total_actions']; ?></div>
                            <div class="stat-mini-label">Actions</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value">
                                <?php 
                                if ($activity_stats['last_activity']) {
                                    $days = floor((time() - strtotime($activity_stats['last_activity'])) / 86400);
                                    echo $days . 'd';
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </div>
                            <div class="stat-mini-label">Last Seen</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Edit Profile -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-user-edit"></i> Edit User Profile</div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user_id; ?>">
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="admin" <?php echo $user_data['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="manager" <?php echo $user_data['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                <option value="sales" <?php echo $user_data['role'] == 'sales' ? 'selected' : ''; ?>>Sales Clerk</option>
                                <option value="warehouse" <?php echo $user_data['role'] == 'warehouse' ? 'selected' : ''; ?>>Warehouse Staff</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="active" <?php echo $user_data['active'] ? 'checked' : ''; ?>>
                                Account Active
                            </label>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- Reset Password -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-key"></i> Reset Password</div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user_id; ?>">
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                            <small style="color: #666;">Minimum 6 characters</small>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Reset Password
                        </button>
                    </form>
                    
                    <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
                        <h4 style="margin-bottom: 15px;">Account Details</h4>
                        <div style="font-size: 14px; color: #666;">
                            <p style="margin: 8px 0;">
                                <strong>Created:</strong> <?php echo date('M d, Y', strtotime($user_data['created_at'])); ?>
                            </p>
                            <p style="margin: 8px 0;">
                                <strong>Last Login:</strong> <?php echo $user_data['last_login'] ? date('M d, Y H:i', strtotime($user_data['last_login'])) : 'Never'; ?>
                            </p>
                            <p style="margin: 8px 0;">
                                <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($user_data['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-history"></i> Recent Activity</div>
                </div>
                <div class="activity-timeline">
                    <?php if ($recent_activity->num_rows > 0): ?>
                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                            <div class="activity-desc"><?php echo htmlspecialchars($activity['description']); ?></div>
                            <div class="activity-time"><?php echo time_ago($activity['created_at']); ?></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No activity yet</h3>
                            <p>User activity will appear here</p>
                        </div>
                    <?php endif;