<?php
require_once 'config.php';
require_login();

// Fetch all stock items with images
$result = $db->query("SELECT id, name, image_path FROM stock WHERE image_path IS NOT NULL AND image_path != ''");

echo "<h2>Image Upload Debug</h2>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #1976d2; color: white; }
    .exists { color: green; font-weight: bold; }
    .missing { color: red; font-weight: bold; }
    img { max-width: 100px; max-height: 100px; }
</style>";

echo "<table>";
echo "<tr><th>ID</th><th>Product Name</th><th>Stored Path</th><th>File Exists?</th><th>Preview</th></tr>";

while ($row = $result->fetch_assoc()) {
    $exists = file_exists($row['image_path']);
    $class = $exists ? 'exists' : 'missing';
    $status = $exists ? '✓ YES' : '✗ NO';
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['image_path']}</td>";
    echo "<td class='$class'>$status</td>";
    echo "<td>";
    if ($exists) {
        echo "<img src='{$row['image_path']}' alt='Product'>";
    } else {
        echo "Image not found";
        // Check if file exists without leading slash
        $alt_path = ltrim($row['image_path'], '/');
        if (file_exists($alt_path)) {
            echo " (Found at: $alt_path)<br>";
            echo "<img src='$alt_path' alt='Product'>";
        }
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Check upload directory
echo "<h3>Upload Directory Check</h3>";
$upload_dir = 'uploads/products/';
if (is_dir($upload_dir)) {
    echo "<p class='exists'>✓ Directory exists: $upload_dir</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
    
    // List files in directory
    $files = scandir($upload_dir);
    echo "<h4>Files in directory:</h4><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p class='missing'>✗ Directory does not exist: $upload_dir</p>";
}
?>

<a href="?page=stock" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;">Back to Stock Management</a>