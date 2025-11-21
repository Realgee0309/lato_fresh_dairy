<?php
// Get user profile image
$user_id = $_SESSION['user_id'];
$user_profile = $db->query("SELECT profile_image FROM users WHERE id = $user_id")->fetch_assoc();

$profile_image = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name']) . '&size=45&background=1976d2&color=fff&bold=true';
if (!empty($user_profile['profile_image']) && file_exists($user_profile['profile_image'])) {
    $profile_image = $user_profile['profile_image'];
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <div class="logo">
                <img src="assets/images/logo.png" alt="Logo" 
                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode(substr(APP_NAME, 0, 2)); ?>&size=50&background=ffffff&color=1976d2&bold=true';">
                <div class="logo-text">
                    <h2><?php echo APP_NAME; ?></h2>
                    <p><?php echo APP_TAGLINE; ?></p>
                </div>
            </div>
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                     alt="Profile" 
                     class="user-avatar" 
                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; background: white;"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&size=45&background=1976d2&color=fff&bold=true'">
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                    <p><?php echo get_role_display($_SESSION['role']); ?></p>
                </div>
            </div>
        </div>
        <ul class="nav-menu">
        <?php
        $pages = [
            'dashboard' => ['icon' => 'chart-line', 'label' => 'Dashboard'],
            'barcode' => ['icon' => 'barcode', 'label' => 'Barcode Scan'],
            'stock' => ['icon' => 'boxes', 'label' => 'Stock Management'],
            'sales' => ['icon' => 'shopping-cart', 'label' => 'Sales'],
            'customers' => ['icon' => 'users', 'label' => 'Customers'],
            'reports' => ['icon' => 'file-alt', 'label' => 'Reports'],
            'backup' => ['icon' => 'database', 'label' => 'Backup'],
            'audit' => ['icon' => 'history', 'label' => 'Audit Trail'],
            'alerts' => ['icon' => 'bell', 'label' => 'Alerts']
        ];
        
        foreach ($pages as $page_key => $page_info) {
            if (in_array($page_key, $allowed_pages)) {
                $active = ($page === $page_key) ? 'active' : '';
                echo "<li class='nav-item'>
                    <a href='?page={$page_key}' class='nav-link {$active}'>
                        <i class='fas fa-{$page_info['icon']}'></i>
                        <span>{$page_info['label']}</span>
                    </a>
                </li>";
            }
        }
        
        // Add User Management link only for admins
        if (has_permission('admin')) {
            echo "<li class='nav-item'>
                <a href='admin_users.php' class='nav-link'>
                    <i class='fas fa-users-cog'></i>
                    <span>User Management</span>
                </a>
            </li>";
        }
        ?>
    </ul>
    <div style="padding: 0 15px 20px 15px;">
        <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
