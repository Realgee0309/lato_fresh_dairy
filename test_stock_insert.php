<?php
require_once 'config.php';
require_login();

// Check if image_path column exists
$columns = $db->query("SHOW COLUMNS FROM stock LIKE 'image_path'");

echo "<h2>Database Column Check</h2>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #1976d2; color: white; }
</style>";

if ($columns->num_rows > 0) {
    echo "<div class='success'>✓ Column 'image_path' EXISTS in stock table</div>";
    
    $column = $columns->fetch_assoc();
    echo "<pre>";
    print_r($column);
    echo "</pre>";
} else {
    echo "<div class='error'>✗ Column 'image_path' DOES NOT EXIST in stock table</div>";
    echo "<div class='info'>Run this SQL to add it:<br><br>";
    echo "<code>ALTER TABLE stock ADD COLUMN image_path VARCHAR(255) NULL AFTER batch_number;</code>";
    echo "</div>";
}

// Show all columns
echo "<h3>All Columns in Stock Table</h3>";
$all_columns = $db->query("SHOW COLUMNS FROM stock");
echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($col = $all_columns->fetch_assoc()) {
    $highlight = $col['Field'] === 'image_path' ? "style='background: #d4edda;'" : "";
    echo "<tr $highlight>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check recent stock records
echo "<h3>Recent Stock Records (showing image_path)</h3>";
$recent = $db->query("SELECT id, name, batch_number, image_path, created_at FROM stock ORDER BY created_at DESC LIMIT 10");

if ($recent->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Batch</th><th>Image Path</th><th>Created</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        $img_status = !empty($row['image_path']) ? "✓ {$row['image_path']}" : "<span style='color: #999;'>No image</span>";
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['batch_number']}</td>";
        echo "<td>{$img_status}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No stock records found</div>";
}

// Test INSERT statement (dry run)
echo "<h3>Test INSERT Statement (Dry Run)</h3>";
echo "<div class='info'>";
echo "<strong>This will show what the INSERT statement would look like:</strong><br><br>";

$test_data = [
    'name' => 'Test Product',
    'category' => 'Milk',
    'quantity' => 10,
    'price' => 100.00,
    'expiry_date' => date('Y-m-d', strtotime('+30 days')),
    'location' => 'Test Location',
    'alert_level' => 5,
    'supplier' => 'Test Supplier',
    'batch_number' => 'TEST-' . date('Ymd') . '-0001',
    'image_path' => 'uploads/products/test_image.jpg',
    'date_added' => date('Y-m-d'),
    'added_by' => $_SESSION['username']
];

echo "<pre>";
echo "INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, image_path, date_added, added_by)\n";
echo "VALUES (\n";
echo "  '{$test_data['name']}',\n";
echo "  '{$test_data['category']}',\n";
echo "  {$test_data['quantity']},\n";
echo "  {$test_data['price']},\n";
echo "  '{$test_data['expiry_date']}',\n";
echo "  '{$test_data['location']}',\n";
echo "  {$test_data['alert_level']},\n";
echo "  '{$test_data['supplier']}',\n";
echo "  '{$test_data['batch_number']}',\n";
echo "  '{$test_data['image_path']}',  ← IMAGE PATH HERE\n";
echo "  '{$test_data['date_added']}',\n";
echo "  '{$test_data['added_by']}'\n";
echo ");\n";
echo "</pre>";
echo "</div>";

?>

<br><br>
<a href="?page=stock" style="display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;">Back to Stock Management</a>