<?php
$audit_logs = $db->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50");
?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-history"></i> Audit Trail</div>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $audit_logs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                    <td><span class="status-badge status-ok"><?php echo strtoupper($log['action']); ?></span></td>
                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                    <td><?php echo htmlspecialchars($log['user']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>