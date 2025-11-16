<?php
$page_titles = [
    'dashboard' => 'Dashboard Overview',
    'barcode' => 'Barcode Scanner',
    'stock' => 'Stock Management',
    'sales' => 'Sales Management',
    'customers' => 'Customer Management',
    'reports' => 'Reports & Analytics',
    'backup' => 'Backup & Restore',
    'audit' => 'Audit Trail',
    'alerts' => 'Alerts & Notifications',
    'users' => 'User Management'
];
$current_title = $page_titles[$page] ?? 'Dashboard';
?>
<header class="header">
    <h1><?php echo $current_title; ?></h1>
    <div class="header-actions">
        <div class="status-indicator online">
            <i class="fas fa-circle"></i>
            <span>Online</span>
        </div>
        <button class="btn btn-primary btn-small" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</header>