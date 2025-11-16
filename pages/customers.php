<?php
// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $date_added = date('Y-m-d');
    
    $stmt = $db->prepare("INSERT INTO customers (name, phone, email, address, date_added) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $phone, $email, $address, $date_added);
    
    if ($stmt->execute()) {
        log_audit('create', "Added new customer: $name");
        $_SESSION['notification'] = ['message' => 'Customer added successfully!', 'type' => 'success'];
        echo '<script>window.location.href = "?page=customers";</script>';
        exit();
    }
}

// Handle delete BEFORE any output
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $db->query("SELECT name FROM customers WHERE id = $id");
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        $db->query("DELETE FROM customers WHERE id = $id");
        log_audit('delete', "Deleted customer: {$customer['name']}");
        $_SESSION['notification'] = ['message' => 'Customer deleted successfully!', 'type' => 'success'];
        echo '<script>window.location.href = "?page=customers";</script>';
        exit();
    }
}

// Fetch customers
$search = sanitize_input($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%'" : "";
$customers_result = $db->query("SELECT * FROM customers $where ORDER BY created_at DESC");
?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-user-plus"></i> Add New Customer</div>
    </div>
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" name="name" placeholder="Enter customer name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+254712345678" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="customer@example.com">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" placeholder="Customer address">
            </div>
        </div>
        <button type="submit" name="add_customer" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-users"></i> Customer List</div>
    </div>
    
    <div class="search-filter-container">
        <form method="GET" action="" style="display: contents;">
            <input type="hidden" name="page" value="customers">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-small">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
    
    <?php if ($customers_result->num_rows > 0): ?>
        <?php while ($customer = $customers_result->fetch_assoc()): ?>
            <div class="customer-card">
                <div class="customer-avatar">
                    <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                </div>
                <div class="customer-info">
                    <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                    <div class="customer-contact">
                        <?php echo htmlspecialchars($customer['phone']); ?>
                        <?php if ($customer['email']): ?>
                            | <?php echo htmlspecialchars($customer['email']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($customer['address']): ?>
                        <div class="customer-contact">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($customer['address']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="customer-stats">
                    <div class="customer-stat">
                        <div class="customer-stat-value"><?php echo format_currency($customer['total_purchases']); ?></div>
                        <div class="customer-stat-label">Total Purchases</div>
                    </div>
                    <div class="customer-stat">
                        <div class="customer-stat-value">
                            <?php echo $customer['last_purchase'] ? date('M d, Y', strtotime($customer['last_purchase'])) : 'Never'; ?>
                        </div>
                        <div class="customer-stat-label">Last Purchase</div>
                    </div>
                </div>
                <div>
                    <a href="?page=customers&delete=<?php echo $customer['id']; ?>" 
                       class="btn btn-danger btn-small" 
                       onclick="return confirm('Delete this customer?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h3>No customers found</h3>
            <p>Add your first customer using the form above</p>
        </div>
    <?php endif; ?>
</div>