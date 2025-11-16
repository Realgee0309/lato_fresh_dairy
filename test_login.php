<?php
require_once 'config.php';

// Test password verification
$test_password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h3>Password Hash Test</h3>";
echo "Testing password: " . $test_password . "<br>";
echo "Against hash: " . $hash . "<br><br>";

if (password_verify($test_password, $hash)) {
    echo "✅ <strong style='color: green;'>Password verification WORKS!</strong><br><br>";
} else {
    echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br><br>";
}

// Check what's in the database
echo "<h3>Database Check</h3>";
$result = $db->query("SELECT username, password, role, active FROM users");

while ($user = $result->fetch_assoc()) {
    echo "<strong>Username:</strong> " . $user['username'] . "<br>";
    echo "<strong>Password Hash:</strong> " . $user['password'] . "<br>";
    echo "<strong>Role:</strong> " . $user['role'] . "<br>";
    echo "<strong>Active:</strong> " . ($user['active'] ? 'Yes' : 'No') . "<br>";
    
    // Test if password works for this user
    if (password_verify('admin123', $user['password'])) {
        echo "<strong style='color: green;'>✅ Password 'admin123' works for this user</strong><br>";
    }
    if (password_verify('sales123', $user['password'])) {
        echo "<strong style='color: green;'>✅ Password 'sales123' works for this user</strong><br>";
    }
    if (password_verify('warehouse123', $user['password'])) {
        echo "<strong style='color: green;'>✅ Password 'warehouse123' works for this user</strong><br>";
    }
    if (password_verify('manager123', $user['password'])) {
        echo "<strong style='color: green;'>✅ Password 'manager123' works for this user</strong><br>";
    }
    echo "<hr>";
}

// Test the login query directly
echo "<h3>Direct Login Test</h3>";
$username = 'admin';
$password = 'admin123';

$stmt = $db->prepare("SELECT id, username, password, full_name, email, role, active FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "✅ User found in database!<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Active: " . ($user['active'] ? 'Yes' : 'No') . "<br>";
    echo "Stored password hash: " . $user['password'] . "<br><br>";
    
    if (password_verify($password, $user['password'])) {
        echo "<strong style='color: green; font-size: 20px;'>✅✅✅ LOGIN SHOULD WORK!</strong><br>";
        echo "Full Name: " . $user['full_name'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
    } else {
        echo "<strong style='color: red; font-size: 18px;'>❌ Password verification failed</strong><br>";
    }
} else {
    echo "<strong style='color: red;'>❌ User not found!</strong><br>";
}
?>