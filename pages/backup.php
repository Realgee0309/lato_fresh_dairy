<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-database"></i> Database Backup</div>
    </div>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <span>Regular backups are essential for data safety. Export your database regularly.</span>
    </div>
    <p>To backup your database:</p>
    <ol>
        <li>Go to phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
        <li>Select database: <strong>lato_fresh_dairy</strong></li>
        <li>Click "Export" tab</li>
        <li>Click "Go" to download backup</li>
    </ol>
    <p>Store backup files in a safe location outside your web directory.</p>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-history"></i> System Information</div>
    </div>
    <table class="table-container">
        <tr>
            <td><strong>Database Name:</strong></td>
            <td>lato_fresh_dairy</td>
        </tr>
        <tr>
            <td><strong>PHP Version:</strong></td>
            <td><?php echo phpversion(); ?></td>
        </tr>
        <tr>
            <td><strong>MySQL Version:</strong></td>
            <td><?php echo $db->getConnection()->server_info; ?></td>
        </tr>
        <tr>
            <td><strong>Total Products:</strong></td>
            <td><?php echo $db->query("SELECT COUNT(*) as c FROM stock")->fetch_assoc()['c']; ?></td>
        </tr>
        <tr>
            <td><strong>Total Sales:</strong></td>
            <td><?php echo $db->query("SELECT COUNT(*) as c FROM sales")->fetch_assoc()['c']; ?></td>
        </tr>
    </table>
</div>