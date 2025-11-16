<?php
// Get low stock items
$low_stock = $db->query("SELECT * FROM stock WHERE quantity < alert_level ORDER BY quantity ASC");

// Get expiring items
$expiring = $db->query("SELECT * FROM stock WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY expiry_date ASC");
?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</div>
    </div>
    <?php if ($low_stock->num_rows > 0): ?>
        <?php while ($item = $low_stock->fetch_assoc()): ?>
        <div class="alert alert-warning">
            <i class="fas fa-box"></i>
            <div>
                <strong><?php echo htmlspecialchars($item['name']); ?></strong> is running low 
                (<?php echo $item['quantity']; ?> remaining, alert at <?php echo $item['alert_level']; ?>)
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>All stock levels are healthy</h3>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-calendar-times"></i> Expiring Soon</div>
    </div>
    <?php if ($expiring->num_rows > 0): ?>
        <?php while ($item = $expiring->fetch_assoc()): ?>
        <div class="alert alert-error">
            <i class="fas fa-clock"></i>
            <div>
                <strong><?php echo htmlspecialchars($item['name']); ?></strong> expires on 
                <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?> 
                (<?php echo get_days_until_expiry($item['expiry_date']); ?> days)
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>No products expiring soon</h3>
        </div>
    <?php endif; ?>
</div>