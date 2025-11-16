<?php
// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $name = sanitize_input($_POST['name']);
    $category = sanitize_input($_POST['category']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $expiry_date = sanitize_input($_POST['expiry_date']);
    $location = sanitize_input($_POST['location']);
    $alert_level = intval($_POST['alert_level']);
    $supplier = sanitize_input($_POST['supplier'] ?? '');
    $batch_number = sanitize_input($_POST['batch_number'] ?? '');
    
    // Auto-generate batch number if not provided
    if (empty($batch_number)) {
        $category_code = strtoupper(substr($category, 0, 3));
        $date_code = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        $batch_number = "{$category_code}-{$date_code}-{$random}";
    }
    
    $date_added = date('Y-m-d');
    $added_by = $_SESSION['username'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($_FILES['product_image']['size'] <= 5000000) {
                $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                    log_audit('upload', "Uploaded product image: $target_file");
                } else {
                    $_SESSION['notification'] = ['message' => 'Failed to upload image', 'type' => 'error'];
                }
            } else {
                $_SESSION['notification'] = ['message' => 'Image size must be less than 5MB', 'type' => 'error'];
            }
        } else {
            $_SESSION['notification'] = ['message' => 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP', 'type' => 'error'];
        }
    }
    
    $stmt = $db->prepare("INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, image_path, date_added, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssisssss", $name, $category, $quantity, $price, $expiry_date, $location, $alert_level, $supplier, $batch_number, $image_path, $date_added, $added_by);
    
    if ($stmt->execute()) {
        log_audit('create', "Added new product: $name (Batch: $batch_number)");
        $_SESSION['notification'] = ['message' => "Product added successfully! Batch Number: $batch_number", 'type' => 'success'];
        echo '<script>window.location.href = "?page=stock";</script>';
        exit();
    } else {
        $error = 'Failed to add product';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $db->query("SELECT name, image_path FROM stock WHERE id = $id");
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        if ($product['image_path'] && file_exists($product['image_path'])) {
            unlink($product['image_path']);
        }
        
        $db->query("DELETE FROM stock WHERE id = $id");
        log_audit('delete', "Deleted product: {$product['name']}");
        $_SESSION['notification'] = ['message' => 'Product deleted successfully!', 'type' => 'success'];
        echo '<script>window.location.href = "?page=stock";</script>';
        exit();
    }
}

// Fetch stock data
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = sanitize_input($_GET['category_filter'] ?? '');
$status_filter = sanitize_input($_GET['status_filter'] ?? '');

$where = [];
if ($search) {
    $where[] = "(name LIKE '%$search%' OR category LIKE '%$search%' OR batch_number LIKE '%$search%')";
}
if ($category_filter) {
    $where[] = "category = '$category_filter'";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stock_result = $db->query("SELECT * FROM stock $where_clause ORDER BY created_at DESC");
$stock_items = [];
while ($row = $stock_result->fetch_assoc()) {
    if ($status_filter === 'low' && $row['quantity'] >= $row['alert_level']) continue;
    if ($status_filter === 'expiring' && get_days_until_expiry($row['expiry_date']) > 7) continue;
    if ($status_filter === 'expired' && get_days_until_expiry($row['expiry_date']) >= 0) continue;
    $stock_items[] = $row;
}

// Get batch statistics
$batch_stats = $db->query("SELECT 
    COUNT(DISTINCT batch_number) as total_batches,
    COUNT(*) as total_products,
    SUM(quantity) as total_units
    FROM stock 
    WHERE batch_number IS NOT NULL AND batch_number != ''")->fetch_assoc();
?>

<!-- Batch Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        </div>
        <div class="stat-value"><?php echo $batch_stats['total_batches'] ?? 0; ?></div>
        <div class="stat-label">Active Batches</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-barcode"></i></div>
        </div>
        <div class="stat-value"><?php echo $batch_stats['total_products'] ?? 0; ?></div>
        <div class="stat-label">Products with Batches</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
        </div>
        <div class="stat-value"><?php echo $batch_stats['total_units'] ?? 0; ?></div>
        <div class="stat-label">Total Units in Batches</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-plus-circle"></i> Add New Stock</div>
    </div>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <span>Batch numbers will be auto-generated if left empty. Format: CATEGORY-DATE-CODE</span>
    </div>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" placeholder="e.g., Fresh Milk 1L" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="category" id="categorySelect" required onchange="previewBatchNumber()">
                    <option value="">Select Category</option>
                    <option value="Milk">Milk</option>
                    <option value="Yogurt">Yogurt</option>
                    <option value="Cheese">Cheese</option>
                    <option value="Butter">Butter</option>
                    <option value="Cream">Cream</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Product Image</label>
            <div class="image-upload-container">
                <input type="file" name="product_image" id="productImage" accept="image/*" onchange="previewImage(this)">
                <div class="image-preview" id="imagePreview">
                    <i class="fas fa-image"></i>
                    <p>Click to upload image</p>
                    <small>JPG, PNG, GIF, WEBP (Max 5MB)</small>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" placeholder="0" min="0" required>
            </div>
            <div class="form-group">
                <label>Unit Price (KES) *</label>
                <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Expiry Date *</label>
                <input type="date" name="expiry_date" required>
            </div>
            <div class="form-group">
                <label>Location *</label>
                <input type="text" name="location" placeholder="e.g., Main Warehouse" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Low Stock Alert Level *</label>
                <input type="number" name="alert_level" value="10" min="1" required>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" name="supplier" placeholder="e.g., Dairy Farm Co.">
            </div>
        </div>
        
        <div class="form-group">
            <label>Batch/Lot Number</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" 
                       name="batch_number" 
                       id="batchNumber" 
                       placeholder="Leave empty for auto-generation"
                       style="flex: 1;">
                <button type="button" class="btn btn-secondary" onclick="generateBatchNumber()">
                    <i class="fas fa-sync-alt"></i> Generate
                </button>
            </div>
            <small id="batchPreview" style="color: var(--text-light); display: block; margin-top: 5px;"></small>
        </div>
        
        <button type="submit" name="add_stock" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Stock
        </button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-inventory"></i> Stock Inventory</div>
    </div>
    
    <div class="search-filter-container">
        <form method="GET" action="" style="display: contents;">
            <input type="hidden" name="page" value="stock">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by name, category, or batch..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select name="category_filter" class="filter-select">
                <option value="">All Categories</option>
                <option value="Milk" <?php echo $category_filter === 'Milk' ? 'selected' : ''; ?>>Milk</option>
                <option value="Yogurt" <?php echo $category_filter === 'Yogurt' ? 'selected' : ''; ?>>Yogurt</option>
                <option value="Cheese" <?php echo $category_filter === 'Cheese' ? 'selected' : ''; ?>>Cheese</option>
                <option value="Butter" <?php echo $category_filter === 'Butter' ? 'selected' : ''; ?>>Butter</option>
                <option value="Cream" <?php echo $category_filter === 'Cream' ? 'selected' : ''; ?>>Cream</option>
            </select>
            <select name="status_filter" class="filter-select">
                <option value="">All Status</option>
                <option value="low" <?php echo $status_filter === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="expiring" <?php echo $status_filter === 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
            </select>
            <button type="submit" class="btn btn-primary btn-small">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Batch Number</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($stock_items) > 0): ?>
                    <?php foreach ($stock_items as $item): ?>
                        <?php 
                        $status = get_stock_status($item);
                        $default_images = [
                            'Milk' => 'https://picsum.photos/seed/milk/60/60.jpg',
                            'Yogurt' => 'https://picsum.photos/seed/yogurt/60/60.jpg',
                            'Cheese' => 'https://picsum.photos/seed/cheese/60/60.jpg',
                            'Butter' => 'https://picsum.photos/seed/butter/60/60.jpg',
                            'Cream' => 'https://picsum.photos/seed/cream/60/60.jpg'
                        ];
                        
                        $image_src = ($item['image_path'] && file_exists($item['image_path'])) 
                            ? $item['image_path'] 
                            : ($default_images[$item['category']] ?? 'https://picsum.photos/seed/product/60/60.jpg');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                     class="product-image" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://picsum.photos/seed/product/60/60.jpg'">
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td>
                                <?php if ($item['batch_number']): ?>
                                    <span class="status-badge status-ok" style="font-family: monospace; cursor: pointer;" 
                                          onclick="copyToClipboard('<?php echo htmlspecialchars($item['batch_number']); ?>')"
                                          title="Click to copy">
                                        <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($item['batch_number']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">No batch</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo format_currency($item['price']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                            <td><span class="status-badge <?php echo $status['class']; ?>"><?php echo $status['text']; ?></span></td>
                            <td>
                                <a href="?page=barcode&scan=<?php echo $item['id']; ?>" 
                                   class="btn btn-secondary btn-small" 
                                   title="Scan/View">
                                    <i class="fas fa-barcode"></i>
                                </a>
                                <a href="?page=stock&delete=<?php echo $item['id']; ?>" 
                                   class="btn btn-danger btn-small" 
                                   onclick="return confirm('Delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <h3>No products found</h3>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.image-upload-container {
    position: relative;
    margin-top: 10px;
}

.image-upload-container input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.image-preview {
    border: 2px dashed #e1e8ed;
    border-radius: var(--border-radius);
    padding: 40px 20px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s;
    cursor: pointer;
}

.image-preview:hover {
    border-color: var(--primary);
    background: #e3f2fd;
}

.image-preview i {
    font-size: 48px;
    color: var(--primary);
    margin-bottom: 15px;
    display: block;
}

.image-preview p {
    color: var(--text-dark);
    font-weight: 600;
    margin-bottom: 5px;
}

.image-preview small {
    color: var(--text-light);
    font-size: 12px;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 10px;
}

.image-preview.has-image {
    border-color: var(--success);
    background: #e8f5e9;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 10px;
    vertical-align: middle;
    border: 2px solid #f0f3f7;
}
</style>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <p style="margin-top: 10px; color: var(--success);">
                    <i class="fas fa-check-circle"></i> Image selected
                </p>
                <small>Click to change</small>
            `;
            preview.classList.add('has-image');
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function generateBatchNumber() {
    const category = document.getElementById('categorySelect').value;
    if (!category) {
        showNotification('Please select a category first', 'warning');
        return;
    }
    
    const categoryCode = category.substring(0, 3).toUpperCase();
    const dateCode = new Date().toISOString().split('T')[0].replace(/-/g, '');
    const randomCode = Math.random().toString(36).substring(2, 6).toUpperCase();
    
    const batchNumber = `${categoryCode}-${dateCode}-${randomCode}`;
    document.getElementById('batchNumber').value = batchNumber;
    
    showNotification('Batch number generated!', 'success');
}

function previewBatchNumber() {
    const category = document.getElementById('categorySelect').value;
    const preview = document.getElementById('batchPreview');
    
    if (category) {
        const categoryCode = category.substring(0, 3).toUpperCase();
        const dateCode = new Date().toISOString().split('T')[0].replace(/-/g, '');
        preview.textContent = `Example: ${categoryCode}-${dateCode}-XXXX (auto-generated if empty)`;
    } else {
        preview.textContent = '';
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Batch number copied to clipboard!', 'success');
    }).catch(err => {
        showNotification('Failed to copy', 'error');
    });
}
</script>