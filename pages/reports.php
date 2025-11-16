<?php
// Sales summary
$today = date('Y-m-d');
$this_month = date('Y-m');
$this_year = date('Y');

$daily_sales = $db->query("SELECT SUM(total) as total FROM sales WHERE sale_date = '$today'")->fetch_assoc()['total'] ?? 0;
$monthly_sales = $db->query("SELECT SUM(total) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$this_month'")->fetch_assoc()['total'] ?? 0;
$yearly_sales = $db->query("SELECT SUM(total) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y') = '$this_year'")->fetch_assoc()['total'] ?? 0;

// Category breakdown
$category_data = $db->query("SELECT category, SUM(quantity) as total_qty, SUM(quantity * price) as total_value FROM stock GROUP BY category");

// Top products
$top_products = $db->query("SELECT si.product_name, SUM(si.quantity) as total_sold, SUM(si.total) as revenue 
    FROM sale_items si 
    GROUP BY si.product_name 
    ORDER BY total_sold DESC 
    LIMIT 10");

// Low stock products
$low_stock_products = $db->query("SELECT * FROM stock WHERE quantity < alert_level ORDER BY quantity ASC LIMIT 10");

// Expiring products
$expiring_products = $db->query("SELECT * FROM stock 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    ORDER BY expiry_date ASC 
    LIMIT 10");

// Sales by payment method
$payment_methods = $db->query("SELECT payment_method, COUNT(*) as count, SUM(total) as total 
    FROM sales 
    GROUP BY payment_method");

// Recent sales
$recent_sales = $db->query("SELECT s.*, GROUP_CONCAT(si.product_name SEPARATOR ', ') as products 
    FROM sales s 
    LEFT JOIN sale_items si ON s.id = si.sale_id 
    GROUP BY s.id 
    ORDER BY s.created_at DESC 
    LIMIT 20");
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        </div>
        <div class="stat-value"><?php echo format_currency($daily_sales); ?></div>
        <div class="stat-label">Today's Sales</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
        </div>
        <div class="stat-value"><?php echo format_currency($monthly_sales); ?></div>
        <div class="stat-label">This Month</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
        <div class="stat-value"><?php echo format_currency($yearly_sales); ?></div>
        <div class="stat-label">This Year</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
        </div>
        <div class="stat-value"><?php echo $db->query("SELECT SUM(quantity) as total FROM stock")->fetch_assoc()['total']; ?></div>
        <div class="stat-label">Total Stock Units</div>
    </div>
</div>

<!-- Export Options -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-download"></i> Export Reports</div>
    </div>
    <p style="margin-bottom: 20px;">Generate and download professional reports in multiple formats</p>
    <div class="download-section" style="display: flex; gap: 15px; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="exportToPDF()">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </button>
        <button class="btn btn-success" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
        <button class="btn btn-secondary" onclick="exportToCSV()">
            <i class="fas fa-file-csv"></i> Export to CSV
        </button>
    </div>
</div>

<!-- Report Content -->
<div id="reportContent">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-chart-pie"></i> Stock by Category</div>
        </div>
        <div class="table-container">
            <table id="categoryTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Quantity</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $categories = [];
                    if ($category_data->num_rows > 0): 
                        while ($cat = $category_data->fetch_assoc()): 
                            $categories[] = $cat;
                    ?>
                    <tr>
                        <td><strong><?php echo $cat['category']; ?></strong></td>
                        <td><?php echo $cat['total_qty']; ?> units</td>
                        <td><?php echo format_currency($cat['total_value']); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="3"><div class="empty-state"><i class="fas fa-chart-pie"></i><h3>No data available</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-trophy"></i> Top Selling Products</div>
        </div>
        <div class="table-container">
            <table id="topProductsTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $top_prods = [];
                    if ($top_products->num_rows > 0): 
                        $rank = 1;
                        while ($product = $top_products->fetch_assoc()): 
                            $top_prods[] = $product;
                    ?>
                    <tr>
                        <td><strong>#<?php echo $rank++; ?></strong></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo $product['total_sold']; ?> units</td>
                        <td><?php echo format_currency($product['revenue']); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="4"><div class="empty-state"><i class="fas fa-shopping-cart"></i><h3>No sales data</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-credit-card"></i> Sales by Payment Method</div>
        </div>
        <div class="table-container">
            <table id="paymentTable">
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Transactions</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $payments = [];
                    if ($payment_methods->num_rows > 0): 
                        while ($method = $payment_methods->fetch_assoc()): 
                            $payments[] = $method;
                    ?>
                    <tr>
                        <td><strong><?php echo ucfirst($method['payment_method']); ?></strong></td>
                        <td><?php echo $method['count']; ?> transactions</td>
                        <td><?php echo format_currency($method['total']); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="3"><div class="empty-state"><i class="fas fa-credit-card"></i><h3>No data</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Items</div>
        </div>
        <div class="table-container">
            <table id="lowStockTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Qty</th>
                        <th>Alert Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $low_stock = [];
                    if ($low_stock_products->num_rows > 0): 
                        while ($product = $low_stock_products->fetch_assoc()): 
                            $low_stock[] = $product;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                        <td><?php echo $product['category']; ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td><?php echo $product['alert_level']; ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="4"><div class="empty-state"><i class="fas fa-check-circle"></i><h3>All stock levels healthy</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-hourglass-half"></i> Expiring Products (Next 30 Days)</div>
        </div>
        <div class="table-container">
            <table id="expiringTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $expiring = [];
                    if ($expiring_products->num_rows > 0): 
                        while ($product = $expiring_products->fetch_assoc()): 
                            $days_left = get_days_until_expiry($product['expiry_date']);
                            $expiring[] = array_merge($product, ['days_left' => $days_left]);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                        <td><?php echo $product['category']; ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($product['expiry_date'])); ?></td>
                        <td><?php echo $days_left; ?> days</td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-check-circle"></i><h3>No products expiring soon</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden data for export -->
<script>
// Store data as JavaScript variables for export
const reportData = {
    dateGenerated: '<?php echo date('F d, Y H:i:s'); ?>',
    dailySales: <?php echo $daily_sales; ?>,
    monthlySales: <?php echo $monthly_sales; ?>,
    yearlySales: <?php echo $yearly_sales; ?>,
    categories: <?php echo json_encode($categories ?? []); ?>,
    topProducts: <?php echo json_encode($top_prods ?? []); ?>,
    paymentMethods: <?php echo json_encode($payments ?? []); ?>,
    lowStock: <?php echo json_encode($low_stock ?? []); ?>,
    expiring: <?php echo json_encode($expiring ?? []); ?>
};

// Export to CSV
function exportToCSV() {
    let csv = 'LATO FRESH DAIRY - BUSINESS REPORT\n';
    csv += 'Generated: ' + reportData.dateGenerated + '\n\n';
    
    csv += 'SALES SUMMARY\n';
    csv += 'Period,Amount\n';
    csv += 'Today,KES ' + reportData.dailySales.toFixed(2) + '\n';
    csv += 'This Month,KES ' + reportData.monthlySales.toFixed(2) + '\n';
    csv += 'This Year,KES ' + reportData.yearlySales.toFixed(2) + '\n\n';
    
    csv += 'STOCK BY CATEGORY\n';
    csv += 'Category,Total Quantity,Total Value\n';
    reportData.categories.forEach(cat => {
        csv += cat.category + ',' + cat.total_qty + ',KES ' + cat.total_value + '\n';
    });
    
    csv += '\nTOP SELLING PRODUCTS\n';
    csv += 'Rank,Product,Units Sold,Revenue\n';
    reportData.topProducts.forEach((prod, idx) => {
        csv += (idx + 1) + ',' + prod.product_name + ',' + prod.total_sold + ',KES ' + prod.revenue + '\n';
    });
    
    csv += '\nSALES BY PAYMENT METHOD\n';
    csv += 'Payment Method,Transactions,Total Amount\n';
    reportData.paymentMethods.forEach(method => {
        csv += method.payment_method + ',' + method.count + ',KES ' + method.total + '\n';
    });
    
    csv += '\nLOW STOCK ITEMS\n';
    csv += 'Product,Category,Current Qty,Alert Level\n';
    reportData.lowStock.forEach(item => {
        csv += item.name + ',' + item.category + ',' + item.quantity + ',' + item.alert_level + '\n';
    });
    
    csv += '\nEXPIRING PRODUCTS\n';
    csv += 'Product,Category,Quantity,Expiry Date,Days Left\n';
    reportData.expiring.forEach(item => {
        csv += item.name + ',' + item.category + ',' + item.quantity + ',' + item.expiry_date + ',' + item.days_left + '\n';
    });
    
    downloadFile(csv, 'Lato_Fresh_Report_' + getDateString() + '.csv', 'text/csv');
    showNotification('Report exported to CSV successfully!', 'success');
}

// Export to Excel (using HTML table method)
function exportToExcel() {
    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta charset="UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #1976d2; color: white; font-weight: bold; }
                .header { font-size: 18px; font-weight: bold; margin: 20px 0; }
                .summary { font-size: 14px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="header">LATO FRESH DAIRY - BUSINESS REPORT</div>
            <div class="summary">Generated: ${reportData.dateGenerated}</div>
            
            <h3>Sales Summary</h3>
            <table>
                <tr><th>Period</th><th>Amount (KES)</th></tr>
                <tr><td>Today</td><td>${reportData.dailySales.toFixed(2)}</td></tr>
                <tr><td>This Month</td><td>${reportData.monthlySales.toFixed(2)}</td></tr>
                <tr><td>This Year</td><td>${reportData.yearlySales.toFixed(2)}</td></tr>
            </table>
            <br>
            
            <h3>Stock by Category</h3>
            <table>
                <tr><th>Category</th><th>Total Quantity</th><th>Total Value (KES)</th></tr>
                ${reportData.categories.map(cat => 
                    `<tr><td>${cat.category}</td><td>${cat.total_qty}</td><td>${cat.total_value}</td></tr>`
                ).join('')}
            </table>
            <br>
            
            <h3>Top Selling Products</h3>
            <table>
                <tr><th>Rank</th><th>Product</th><th>Units Sold</th><th>Revenue (KES)</th></tr>
                ${reportData.topProducts.map((prod, idx) => 
                    `<tr><td>${idx + 1}</td><td>${prod.product_name}</td><td>${prod.total_sold}</td><td>${prod.revenue}</td></tr>`
                ).join('')}
            </table>
            <br>
            
            <h3>Sales by Payment Method</h3>
            <table>
                <tr><th>Payment Method</th><th>Transactions</th><th>Total Amount (KES)</th></tr>
                ${reportData.paymentMethods.map(method => 
                    `<tr><td>${method.payment_method}</td><td>${method.count}</td><td>${method.total}</td></tr>`
                ).join('')}
            </table>
            <br>
            
            <h3>Low Stock Items</h3>
            <table>
                <tr><th>Product</th><th>Category</th><th>Current Qty</th><th>Alert Level</th></tr>
                ${reportData.lowStock.map(item => 
                    `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td><td>${item.alert_level}</td></tr>`
                ).join('')}
            </table>
            <br>
            
            <h3>Expiring Products (Next 30 Days)</h3>
            <table>
                <tr><th>Product</th><th>Category</th><th>Quantity</th><th>Expiry Date</th><th>Days Left</th></tr>
                ${reportData.expiring.map(item => 
                    `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td><td>${item.expiry_date}</td><td>${item.days_left}</td></tr>`
                ).join('')}
            </table>
        </body>
        </html>
    `;
    
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Lato_Fresh_Report_' + getDateString() + '.xls';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Report exported to Excel successfully!', 'success');
}

// Export to PDF (using browser print with custom styling)
function exportToPDF() {
    const printWindow = window.open('', '', 'height=800,width=1000');
    printWindow.document.write(`
        <html>
        <head>
            <title>Lato Fresh Dairy Report</title>
            <style>
                @page { margin: 20mm; }
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1976d2; padding-bottom: 10px; }
                .header h1 { color: #1976d2; margin: 0; font-size: 24px; }
                .header p { margin: 5px 0; color: #666; }
                .section { margin-bottom: 30px; page-break-inside: avoid; }
                .section h2 { color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 5px; margin-bottom: 15px; font-size: 16px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #1976d2; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .summary-box { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .summary-item { display: inline-block; margin-right: 30px; }
                .summary-label { font-weight: bold; color: #1976d2; }
                .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>LATO FRESH DAIRY</h1>
                <p>Business Report</p>
                <p>Generated: ${reportData.dateGenerated}</p>
            </div>
            
            <div class="summary-box">
                <div class="summary-item"><span class="summary-label">Today's Sales:</span> KES ${reportData.dailySales.toFixed(2)}</div>
                <div class="summary-item"><span class="summary-label">Monthly Sales:</span> KES ${reportData.monthlySales.toFixed(2)}</div>
                <div class="summary-item"><span class="summary-label">Yearly Sales:</span> KES ${reportData.yearlySales.toFixed(2)}</div>
            </div>
            
            <div class="section">
                <h2>Stock by Category</h2>
                <table>
                    <thead>
                        <tr><th>Category</th><th>Total Quantity</th><th>Total Value (KES)</th></tr>
                    </thead>
                    <tbody>
                        ${reportData.categories.map(cat => 
                            `<tr><td>${cat.category}</td><td>${cat.total_qty}</td><td>${parseFloat(cat.total_value).toFixed(2)}</td></tr>`
                        ).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Top Selling Products</h2>
                <table>
                    <thead>
                        <tr><th>Rank</th><th>Product</th><th>Units Sold</th><th>Revenue (KES)</th></tr>
                    </thead>
                    <tbody>
                        ${reportData.topProducts.map((prod, idx) => 
                            `<tr><td>#${idx + 1}</td><td>${prod.product_name}</td><td>${prod.total_sold}</td><td>${parseFloat(prod.revenue).toFixed(2)}</td></tr>`
                        ).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Sales by Payment Method</h2>
                <table>
                    <thead>
                        <tr><th>Payment Method</th><th>Transactions</th><th>Total Amount (KES)</th></tr>
                    </thead>
                    <tbody>
                        ${reportData.paymentMethods.map(method => 
                            `<tr><td>${method.payment_method.toUpperCase()}</td><td>${method.count}</td><td>${parseFloat(method.total).toFixed(2)}</td></tr>`
                        ).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Low Stock Items</h2>
                <table>
                    <thead>
                        <tr><th>Product</th><th>Category</th><th>Current Qty</th><th>Alert Level</th></tr>
                    </thead>
                    <tbody>
                        ${reportData.lowStock.length > 0 ? reportData.lowStock.map(item => 
                            `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td><td>${item.alert_level}</td></tr>`
                        ).join('') : '<tr><td colspan="4" style="text-align: center;">All stock levels are healthy</td></tr>'}
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Expiring Products (Next 30 Days)</h2>
                <table>
                    <thead>
                        <tr><th>Product</th><th>Category</th><th>Quantity</th><th>Expiry Date</th><th>Days Left</th></tr>
                    </thead>
                    <tbody>
                        ${reportData.expiring.length > 0 ? reportData.expiring.map(item => 
                            `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td><td>${item.expiry_date}</td><td>${item.days_left}</td></tr>`
                        ).join('') : '<tr><td colspan="5" style="text-align: center;">No products expiring soon</td></tr>'}
                    </tbody>
                </table>
            </div>
            
            <div class="footer">
                <p>Lato Fresh Dairy Management System | Pure Dairy, Smart Control</p>
                <p>Report generated on ${reportData.dateGenerated}</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
        showNotification('PDF report ready to print/save!', 'success');
    }, 500);
}

// Helper functions
function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function getDateString() {
    const now = new Date();
    return now.getFullYear() + '-' + 
           String(now.getMonth() + 1).padStart(2, '0') + '-' + 
           String(now.getDate()).padStart(2, '0');
}
</script>

<style>
.download-section {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-success {
    background: #107c41;
    color: white;
}

.btn-success:hover {
    background: #0e6636;
    transform: translateY(-2px);
}

@media print {
    .panel-header, .download-section, .btn, button {
        display: none !important;
    }
    
    .panel {
        page-break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>