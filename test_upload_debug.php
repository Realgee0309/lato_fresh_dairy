<?php
require_once 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>📤 Upload Test Results</h2>";
    echo "<style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 8px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .box { background: white; padding: 20px; border-radius: 12px; margin: 20px 0; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
    </style>";
    
    echo "<div class='box'>";
    echo "<h3>1. Form Data Received (\$_POST)</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>2. Files Data Received (\$_FILES)</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    echo "</div>";
    
    if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
        echo "<div class='success'>✓ File upload detected successfully!</div>";
        
        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            echo "<div class='info'>Created upload directory: $upload_dir</div>";
        }
        
        $file_extension = strtolower(pathinfo($_FILES['test_image']['name'], PATHINFO_EXTENSION));
        $new_filename = 'test_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['test_image']['tmp_name'], $target_file)) {
            echo "<div class='success'>✓ File uploaded successfully to: $target_file</div>";
            echo "<div class='box'>";
            echo "<h3>Uploaded Image Preview:</h3>";
            echo "<img src='$target_file' style='max-width: 300px; border-radius: 8px;'>";
            echo "</div>";
            
            // Test database insert
            $test_name = 'Test Upload Product';
            $test_category = 'Milk';
            $test_image_path = $target_file;
            
            $stmt = $db->prepare("INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, image_path, date_added, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $quantity = 10;
            $price = 100.00;
            $expiry = date('Y-m-d', strtotime('+30 days'));
            $location = 'Test';
            $alert = 5;
            $supplier = 'Test';
            $batch = 'TEST-' . time();
            $date_added = date('Y-m-d');
            $added_by = $_SESSION['username'];
            
            $stmt->bind_param("ssisssisssss", $test_name, $test_category, $quantity, $price, $expiry, $location, $alert, $supplier, $batch, $test_image_path, $date_added, $added_by);
            
            if ($stmt->execute()) {
                $insert_id = $db->lastInsertId();
                echo "<div class='success'>✓ Database insert successful! Product ID: $insert_id</div>";
                echo "<div class='info'>Check in database: SELECT * FROM stock WHERE id = $insert_id</div>";
            } else {
                echo "<div class='error'>✗ Database insert failed: " . $stmt->error . "</div>";
            }
            
        } else {
            echo "<div class='error'>✗ Failed to move uploaded file</div>";
        }
    } else {
        echo "<div class='error'>✗ No file uploaded or upload error occurred</div>";
        if (isset($_FILES['test_image'])) {
            echo "<div class='info'>Error code: " . $_FILES['test_image']['error'] . "</div>";
            
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload'
            ];
            
            if (isset($upload_errors[$_FILES['test_image']['error']])) {
                echo "<div class='error'>Error: " . $upload_errors[$_FILES['test_image']['error']] . "</div>";
            }
        }
    }
    
    echo "<br><a href='test_upload_debug.php' style='display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;'>Test Again</a>";
    echo " <a href='?page=stock' style='display: inline-block; padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 5px;'>Back to Stock</a>";
    
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        h2 { color: #1976d2; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 12px 30px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #1565c0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🧪 Test Image Upload</h2>
        
        <div class="info">
            <strong>This will test if image uploads work correctly.</strong><br>
            Upload a test image and we'll show you exactly what happens.
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name (test)</label>
                <input type="text" name="product_name" value="Test Product" required>
            </div>
            
            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" name="test_image" accept="image/*" required>
            </div>
            
            <button type="submit">🚀 Test Upload</button>
        </form>
        
        <br>
        <a href="?page=stock" style="display: inline-block; padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 5px;">Back to Stock</a>
    </div>
</body>
</html>
<?php
}
?>