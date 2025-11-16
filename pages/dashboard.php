<?php
// Fetch comprehensive dashboard statistics
$today = date('Y-m-d');
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_month = date('Y-m');

// Products Statistics
$total_products = $db->query("SELECT COUNT(*) as count FROM stock")->fetch_assoc()['count'];
$total_stock_value = $db->query("SELECT SUM(quantity * price) as value FROM stock")->fetch_assoc()['value'] ?? 0;
$low_stock_count = $db->query("SELECT COUNT(*) as count FROM stock WHERE quantity < alert_level")->fetch_assoc()['count'];
$expiring_count = $db->query("SELECT COUNT(*) as count FROM stock 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Sales Statistics
$todays_sales_result = $db->query("SELECT SUM(total) as total, COUNT(*) as count FROM sales WHERE sale_date = '$today'");
$todays_sales_data = $todays_sales_result->fetch_assoc();
$todays_sales = $todays_sales_data['total'] ?? 0;
$todays_transactions = $todays_sales_data['count'] ?? 0;

$weekly_sales = $db->query("SELECT SUM(total) as total FROM sales WHERE sale_date >= '$this_week_start'")->fetch_assoc()['total'] ?? 0;
$monthly_sales = $db->query("SELECT SUM(total) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$this_month'")->fetch_assoc()['total'] ?? 0;

// Customer Statistics
$total_customers = $db->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$active_customers = $db->query("SELECT COUNT(*) as count FROM customers WHERE last_purchase >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'];

// Recent activity
$recent_sales = $db->query("SELECT s.sale_date, si.product_name, si.quantity, si.total, s.seller, s.created_at 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    ORDER BY s.created_at DESC LIMIT 8");

// Sales chart data (last 7 days)
$sales_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sales_result = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = '$date'");
    $total = $sales_result->fetch_assoc()['total'];
    $sales_chart_data[] = [
        'date' => date('D, M j', strtotime($date)),
        'total' => floatval($total)
    ];
}

// Top selling products
$top_products = $db->query("SELECT si.product_name, SUM(si.quantity) as total_sold, SUM(si.total) as revenue 
    FROM sale_items si 
    GROUP BY si.product_name 
    ORDER BY total_sold DESC 
    LIMIT 5");

// Category breakdown
$category_data = $db->query("SELECT category, SUM(quantity) as total_qty, SUM(quantity * price) as total_value 
    FROM stock 
    GROUP BY category 
    ORDER BY total_value DESC");

$categories = [];
while ($cat = $category_data->fetch_assoc()) {
    $categories[] = $cat;
}
?>

<!-- Welcome Banner -->
<?php
// Get user profile image
$user_id = $_SESSION['user_id'];
$user_profile = $db->query("SELECT profile_image FROM users WHERE id = $user_id")->fetch_assoc();

$welcome_image = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name']) . '&size=120&background=1976d2&color=fff&bold=true';
if (!empty($user_profile['profile_image']) && file_exists($user_profile['profile_image'])) {
    $welcome_image = $user_profile['profile_image'];
}
?>
<div class="dashboard-welcome">
    <div>
        <h2>👋 Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
        <p>Here's your business overview for <?php echo date('l, F j, Y'); ?></p>
    </div>
    <img src="<?php echo htmlspecialchars($welcome_image); ?>" 
         alt="Profile" 
         style="border-radius: 50%; width: 120px; height: 120px; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.2);"
         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&size=120&background=fff&color=1976d2&bold=true'">
</div>

<!-- Key Performance Indicators -->
<div class="stats-grid">
    <div class="stat-card" style="border-left-color: #1976d2;">
        <div class="stat-header">
            <div class="stat-icon" style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);">
                <i class="fas fa-coins"></i>
            </div>
            <span class="stat-trend" style="color: #66bb6a;">
                <i class="fas fa-arrow-up"></i> Today
            </span>
        </div>
        <div class="stat-value"><?php echo format_currency($todays_sales); ?></div>
        <div class="stat-label">Today's Revenue</div>
        <div class="stat-footer"><?php echo $todays_transactions; ?> transactions</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #00897b;">
        <div class="stat-header">
            <div class="stat-icon" style="background: linear-gradient(135deg, #00897b 0%, #00796b 100%);">
                <i class="fas fa-calendar-week"></i>
            </div>
            <span class="stat-trend" style="color: #66bb6a;">
                <i class="fas fa-arrow-up"></i> This Week
            </span>
        </div>
        <div class="stat-value"><?php echo format_currency($weekly_sales); ?></div>
        <div class="stat-label">Weekly Revenue</div>
        <div class="stat-footer">Target: KES 50,000</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #7b1fa2;">
        <div class="stat-header">
            <div class="stat-icon" style="background: linear-gradient(135deg, #7b1fa2 0%, #6a1b9a 100%);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span class="stat-trend" style="color: #66bb6a;">
                <i class="fas fa-arrow-up"></i> This Month
            </span>
        </div>
        <div class="stat-value"><?php echo format_currency($monthly_sales); ?></div>
        <div class="stat-label">Monthly Revenue</div>
        <div class="stat-footer">Target: KES 200,000</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #f57c00;">
        <div class="stat-header">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f57c00 0%, #ef6c00 100%);">
                <i class="fas fa-warehouse"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo format_currency($total_stock_value); ?></div>
        <div class="stat-label">Inventory Value</div>
        <div class="stat-footer"><?php echo $total_products; ?> products in stock</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <?php if (in_array('sales', $allowed_pages)): ?>
    <a href="?page=sales" class="quick-action">
        <i class="fas fa-shopping-cart"></i>
        <p>New Sale</p>
    </a>
    <?php endif; ?>
    
    <?php if (in_array('stock', $allowed_pages)): ?>
    <a href="?page=stock" class="quick-action">
        <i class="fas fa-boxes"></i>
        <p>Add Stock</p>
    </a>
    <?php endif; ?>
    
    <?php if (in_array('barcode', $allowed_pages)): ?>
    <a href="?page=barcode" class="quick-action">
        <i class="fas fa-barcode"></i>
        <p>Scan Product</p>
    </a>
    <?php endif; ?>
    
    <?php if (in_array('customers', $allowed_pages)): ?>
    <a href="?page=customers" class="quick-action">
        <i class="fas fa-user-plus"></i>
        <p>Add Customer</p>
    </a>
    <?php endif; ?>
    
    <?php if (in_array('reports', $allowed_pages)): ?>
    <a href="?page=reports" class="quick-action">
        <i class="fas fa-chart-line"></i>
        <p>View Reports</p>
    </a>
    <?php endif; ?>
</div>

<!-- Alerts Section -->
<?php if ($low_stock_count > 0 || $expiring_count > 0): ?>
<div class="alerts-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php if ($low_stock_count > 0): ?>
    <div class="alert-card" style="background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%); border-left: 4px solid #ffa726; padding: 20px; border-radius: 12px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: #ffa726; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="color: #f57f17; margin-bottom: 5px;">Low Stock Alert</h3>
                <p style="color: #856404; margin-bottom: 10px;"><?php echo $low_stock_count; ?> product(s) running low</p>
                <a href="?page=stock&status_filter=low" style="color: #f57f17; font-weight: bold;">View Items →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($expiring_count > 0): ?>
    <div class="alert-card" style="background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); border-left: 4px solid #ef5350; padding: 20px; border-radius: 12px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: #ef5350; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                <i class="fas fa-clock"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="color: #c62828; margin-bottom: 5px;">Expiring Soon</h3>
                <p style="color: #721c24; margin-bottom: 10px;"><?php echo $expiring_count; ?> product(s) expiring in 7 days</p>
                <a href="?page=stock&status_filter=expiring" style="color: #c62828; font-weight: bold;">View Items →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Charts and Analytics -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Sales Chart -->
    <div class="chart-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="panel-title"><i class="fas fa-chart-line"></i> Sales Trend (Last 7 Days)</div>
            <select id="chartPeriod" style="padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 3 Months</option>
            </select>
        </div>
        <canvas id="salesChart" height="80"></canvas>
    </div>
    
    <!-- Customer Stats -->
    <div class="chart-container">
        <div class="panel-title"><i class="fas fa-users"></i> Customer Overview</div>
        <div style="text-align: center; padding: 30px 0;">
            <div style="font-size: 48px; font-weight: bold; color: #1976d2; margin-bottom: 10px;">
                <?php echo $total_customers; ?>
            </div>
            <div style="font-size: 14px; color: #666; margin-bottom: 20px;">Total Customers</div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px;">
                <div style="background: #e8f5e9; padding: 15px; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #2e7d32;"><?php echo $active_customers; ?></div>
                    <div style="font-size: 12px; color: #2e7d32;">Active (30d)</div>
                </div>
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #e65100;"><?php echo $total_customers - $active_customers; ?></div>
                    <div style="font-size: 12px; color: #e65100;">Inactive</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Products and Category Breakdown -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Top Products -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-trophy"></i> Top Selling Products</div>
        </div>
        <div style="padding: 10px 0;">
            <?php 
            $rank = 1;
            while ($product = $top_products->fetch_assoc()): 
                $percentage = ($product['total_sold'] / ($product['total_sold'] + 100)) * 100;
            ?>
            <div style="padding: 15px; border-bottom: 1px solid #f0f3f7; display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #ccc; width: 30px;">
                    <?php echo $rank++; ?>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-bottom: 5px;">
                        <span><?php echo $product['total_sold']; ?> units sold</span>
                        <span><?php echo format_currency($product['revenue']); ?></span>
                    </div>
                    <div style="background: #e0e0e0; height: 6px; border-radius: 3px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #1976d2 0%, #42a5f5 100%); height: 100%; width: <?php echo $percentage; ?>%;"></div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Category Breakdown -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-layer-group"></i> Stock by Category</div>
        </div>
        <div style="padding: 10px 0;">
            <?php 
            $colors = ['#1976d2', '#00897b', '#f57c00', '#7b1fa2', '#c62828'];
            $index = 0;
            foreach ($categories as $cat): 
                $color = $colors[$index % count($colors)];
                $index++;
            ?>
            <div style="padding: 15px; border-bottom: 1px solid #f0f3f7;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <div>
                        <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo $color; ?>; border-radius: 50%; margin-right: 8px;"></span>
                        <strong><?php echo $cat['category']; ?></strong>
                    </div>
                    <span style="color: #666;"><?php echo $cat['total_qty']; ?> units</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 14px; color: #666;">
                    <span>Value</span>
                    <strong style="color: #333;"><?php echo format_currency($cat['total_value']); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-history"></i> Recent Transactions</div>
        <a href="?page=sales" class="btn btn-primary btn-small">View All</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Seller</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_sales->num_rows > 0): ?>
                    <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo time_ago($sale['created_at']); ?></td>
                        <td><strong><?php echo htmlspecialchars($sale['product_name']); ?></strong></td>
                        <td><span class="status-badge status-ok"><?php echo $sale['quantity']; ?></span></td>
                        <td><strong><?php echo format_currency($sale['total']); ?></strong></td>
                        <td><?php echo htmlspecialchars($sale['seller']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No recent transactions</h3>
                                <p>Sales will appear here once you start recording transactions</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Sales Chart
const salesData = <?php echo json_encode($sales_chart_data); ?>;
const ctx = document.getElementById('salesChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.date),
            datasets: [{
                label: 'Daily Revenue (KES)',
                data: salesData.map(d => d.total),
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1976d2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { 
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: KES ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}
</script>

<style>
.stat-footer {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f3f7;
}

.stat-trend {
    font-size: 12px;
    font-weight: 600;
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
</style>