<?php
require_once 'config.php';
require_login();

if (!has_permission('admin')) {
    die('Access denied. Admin only.');
}

// Get all products without images
$products = $db->query("SELECT id, name, category FROM stock WHERE image_path IS NULL OR image_path = ''");

// Get all uploaded images
$upload_dir = 'uploads/products/';
$uploaded_files = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($upload_dir . $file)) {
            $uploaded_files[] = $file;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_image'])) {
    $product_id = intval($_POST['product_id']);
    $image_file = sanitize_input($_POST['image_file']);
    $image_path = $upload_dir . $image_file;
    
    if (file_exists($image_path)) {
        $stmt = $db->prepare("UPDATE stock SET image_path = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $product_id);
        
        if ($stmt->execute()) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px;'>
                ✓ Image linked successfully!
            </div>";
            log_audit('update', "Linked image to product ID: $product_id");
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px;'>
                ✗ Failed to link image
            </div>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Images to Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1976d2;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .image-card .filename {
            font-size: 11px;
            color: #666;
            word-break: break-all;
            margin-bottom: 10px;
        }
        .link-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #666;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #444;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔗 Link Uploaded Images to Products</h2>
        
        <?php if (count($uploaded_files) === 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No uploaded images found</strong><br>
                Upload some product images first from the Stock Management page.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ Instructions:</strong><br>
                Select a product and an image file, then click "Link Image" to associate them.
            </div>
            
            <div class="link-form">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Product</label>
                            <select name="product_id" required>
                                <option value="">-- Choose Product --</option>
                                <?php 
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        #<?php echo $product['id']; ?> - <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['category']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Image</label>
                            <select name="image_file" required onchange="previewImage(this)">
                                <option value="">-- Choose Image --</option>
                                <?php foreach ($uploaded_files as $file): ?>
                                    <option value="<?php echo htmlspecialchars($file); ?>">
                                        <?php echo htmlspecialchars($file); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="link_image" class="btn-primary">
                            🔗 Link Image
                        </button>
                    </div>
                    
                    <div id="imagePreview" style="margin-top: 20px; text-align: center;"></div>
                </form>
            </div>
            
            <div class="section">
                <h3>Available Images (<?php echo count($uploaded_files); ?>)</h3>
                <div class="image-grid">
                    <?php foreach ($uploaded_files as $file): ?>
                        <div class="image-card">
                            <img src="<?php echo $upload_dir . $file; ?>" alt="Product Image">
                            <div class="filename"><?php echo htmlspecialchars($file); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="?page=stock" class="btn-secondary">← Back to Stock Management</a>
        </div>
    </div>
    
    <script>
        function previewImage(select) {
            const filename = select.value;
            const preview = document.getElementById('imagePreview');
            
            if (filename) {
                preview.innerHTML = `
                    <div style="border: 2px solid #1976d2; border-radius: 8px; padding: 15px; display: inline-block;">
                        <strong>Preview:</strong><br>
                        <img src="<?php echo $upload_dir; ?>${filename}" style="max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 8px;">
                    </div>
                `;
            } else {
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>