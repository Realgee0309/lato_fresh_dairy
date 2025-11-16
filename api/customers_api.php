<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            if (!has_permission('sales')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $name = sanitize_input($_POST['name']);
            $phone = sanitize_input($_POST['phone']);
            $email = sanitize_input($_POST['email'] ?? '');
            $address = sanitize_input($_POST['address'] ?? '');
            $date_added = date('Y-m-d');
            
            $stmt = $db->prepare("INSERT INTO customers (name, phone, email, address, date_added) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $phone, $email, $address, $date_added);
            
            if ($stmt->execute()) {
                log_audit('create', "Added new customer: $name");
                json_response(['success' => true, 'message' => 'Customer added successfully', 'id' => $db->lastInsertId()]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to add customer'], 500);
            }
            break;
            
        case 'update':
            if (!has_permission('sales')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $id = intval($_POST['id']);
            $name = sanitize_input($_POST['name']);
            $phone = sanitize_input($_POST['phone']);
            $email = sanitize_input($_POST['email'] ?? '');
            $address = sanitize_input($_POST['address'] ?? '');
            
            $stmt = $db->prepare("UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
            
            if ($stmt->execute()) {
                log_audit('update', "Updated customer: $name (ID: $id)");
                json_response(['success' => true, 'message' => 'Customer updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update customer'], 500);
            }
            break;
            
        case 'delete':
            if (!has_permission('admin')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $id = intval($_POST['id']);
            
            $result = $db->query("SELECT name FROM customers WHERE id = $id");
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                
                $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    log_audit('delete', "Deleted customer: {$customer['name']} (ID: $id)");
                    json_response(['success' => true, 'message' => 'Customer deleted successfully']);
                } else {
                    json_response(['success' => false, 'message' => 'Failed to delete customer'], 500);
                }
            } else {
                json_response(['success' => false, 'message' => 'Customer not found'], 404);
            }
            break;
            
        case 'get':
            $id = intval($_GET['id']);
            $result = $db->query("SELECT * FROM customers WHERE id = $id");
            
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                json_response(['success' => true, 'data' => $customer]);
            } else {
                json_response(['success' => false, 'message' => 'Customer not found'], 404);
            }
            break;
            
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $search = sanitize_input($_GET['search'] ?? '');
            
            $where = "";
            if ($search) {
                $where = "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%'";
            }
            
            $count_result = $db->query("SELECT COUNT(*) as total FROM customers $where");
            $total = $count_result->fetch_assoc()['total'];
            
            $result = $db->query("SELECT * FROM customers $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            
            $customers = [];
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $customers,
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