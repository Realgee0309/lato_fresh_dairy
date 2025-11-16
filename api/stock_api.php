<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            if (!has_permission('warehouse')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $name = sanitize_input($_POST['name']);
            $category = sanitize_input($_POST['category']);
            $quantity = intval($_POST['quantity']);
            $price = floatval($_POST['price']);
            $expiry_date = sanitize_input($_POST['expiry_date']);
            $location = sanitize_input($_POST['location']);
            $alert_level = intval($_POST['alert_level']);
            $supplier = sanitize_input($_POST['supplier'] ?? '');
            $batch_number = sanitize_input($_POST['batch_number'] ?? '');
            $date_added = date('Y-m-d');
            $added_by = $_SESSION['username'];
            
            $stmt = $db->prepare("INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, date_added, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisssissss", $name, $category, $quantity, $price, $expiry_date, $location, $alert_level, $supplier, $batch_number, $date_added, $added_by);
            
            if ($stmt->execute()) {
                log_audit('create', "Added new product: $name");
                json_response(['success' => true, 'message' => 'Product added successfully', 'id' => $db->lastInsertId()]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to add product'], 500);
            }
            break;
            
        case 'update':
            if (!has_permission('warehouse')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $id = intval($_POST['id']);
            $name = sanitize_input($_POST['name']);
            $category = sanitize_input($_POST['category']);
            $quantity = intval($_POST['quantity']);
            $price = floatval($_POST['price']);
            $expiry_date = sanitize_input($_POST['expiry_date']);
            $location = sanitize_input($_POST['location']);
            $alert_level = intval($_POST['alert_level']);
            $supplier = sanitize_input($_POST['supplier'] ?? '');
            $batch_number = sanitize_input($_POST['batch_number'] ?? '');
            
            $stmt = $db->prepare("UPDATE stock SET name=?, category=?, quantity=?, price=?, expiry_date=?, location=?, alert_level=?, supplier=?, batch_number=? WHERE id=?");
            $stmt->bind_param("ssisssissi", $name, $category, $quantity, $price, $expiry_date, $location, $alert_level, $supplier, $batch_number, $id);
            
            if ($stmt->execute()) {
                log_audit('update', "Updated product: $name (ID: $id)");
                json_response(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update product'], 500);
            }
            break;
            
        case 'delete':
            if (!has_permission('admin')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $id = intval($_POST['id']);
            
            // Get product name for audit log
            $result = $db->query("SELECT name FROM stock WHERE id = $id");
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                $stmt = $db->prepare("DELETE FROM stock WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    log_audit('delete', "Deleted product: {$product['name']} (ID: $id)");
                    json_response(['success' => true, 'message' => 'Product deleted successfully']);
                } else {
                    json_response(['success' => false, 'message' => 'Failed to delete product'], 500);
                }
            } else {
                json_response(['success' => false, 'message' => 'Product not found'], 404);
            }
            break;
            
        case 'get':
            $id = intval($_GET['id']);
            $result = $db->query("SELECT * FROM stock WHERE id = $id");
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                json_response(['success' => true, 'data' => $product]);
            } else {
                json_response(['success' => false, 'message' => 'Product not found'], 404);
            }
            break;
            
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $search = sanitize_input($_GET['search'] ?? '');
            $category = sanitize_input($_GET['category'] ?? '');
            $status = sanitize_input($_GET['status'] ?? '');
            
            $where = [];
            if ($search) {
                $where[] = "(name LIKE '%$search%' OR category LIKE '%$search%')";
            }
            if ($category) {
                $where[] = "category = '$category'";
            }
            if ($status === 'low') {
                $where[] = "quantity < alert_level";
            } elseif ($status === 'expiring') {
                $where[] = "expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($status === 'expired') {
                $where[] = "expiry_date < CURDATE()";
            }
            
            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $count_result = $db->query("SELECT COUNT(*) as total FROM stock $where_clause");
            $total = $count_result->fetch_assoc()['total'];
            
            $result = $db->query("SELECT * FROM stock $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>