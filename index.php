<?php
require_once 'config.php';
require_login();

// Fetch dashboard statistics
$total_products = $db->query("SELECT COUNT(*) as count FROM stock")->fetch_assoc()['count'];
$today = date('Y-m-d');
$todays_sales_result = $db->query("SELECT SUM(total) as total FROM sales WHERE sale_date = '$today'");
$todays_sales = $todays_sales_result->fetch_assoc()['total'] ?? 0;

$low_stock_count = $db->query("SELECT COUNT(*) as count FROM stock WHERE quantity < alert_level")->fetch_assoc()['count'];

$expiring_count = $db->query("SELECT COUNT(*) as count FROM stock 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Fetch recent sales for activity table
$recent_sales_query = "SELECT s.sale_date, si.product_name, si.quantity, si.total, s.seller 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    ORDER BY s.created_at DESC LIMIT 10";
$recent_sales = $db->query($recent_sales_query);

// Fetch sales data for last 7 days for chart
$sales_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sales_result = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = '$date'");
    $total = $sales_result->fetch_assoc()['total'];
    $sales_chart_data[] = ['date' => $date, 'total' => floatval($total)];
}

// Determine page to display
$page = isset($_GET['page']) ? sanitize_input($_GET['page']) : 'dashboard';

// Role-based page access - ADD THIS CHECK
if (!isset($_SESSION['role'])) {
    redirect('login.php');
    exit;
}

$role_permissions = [
    'admin' => ['dashboard', 'barcode', 'stock', 'sales', 'customers', 'reports', 'backup', 'audit', 'alerts', 'users'],
    'sales' => ['dashboard', 'barcode', 'sales', 'customers'],
    'warehouse' => ['dashboard', 'stock', 'barcode'],
    'manager' => ['dashboard', 'reports', 'alerts', 'audit']
];

$allowed_pages = $role_permissions[$_SESSION['role']] ?? ['dashboard'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container active">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Content Area -->
            <div class="content">
                <?php
                // Include the requested page
                $page_file = "pages/{$page}.php";
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    include 'pages/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    
    <!-- Notification Toast -->
    <div class="notification-toast" id="notification-toast">
        <div class="notification-icon success" id="notification-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title" id="notification-title">Success</div>
            <div class="notification-message" id="notification-message"></div>
        </div>
        <div class="notification-close" onclick="hideNotification()">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <?php if (isset($_SESSION['notification'])): ?>
    <script>
        showNotification('<?php echo addslashes($_SESSION['notification']['message']); ?>', '<?php echo $_SESSION['notification']['type']; ?>');
    </script>
    <?php unset($_SESSION['notification']); endif; ?>
</body>
</html>