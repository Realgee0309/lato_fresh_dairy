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
            
            $customer_id = $_POST['customer_id'] === 'walk-in' ? null : intval($_POST['customer_id']);
            $customer_name = sanitize_input($_POST['customer_name']);
            $payment_method = sanitize_input($_POST['payment_method']);
            $amount_paid = floatval($_POST['amount_paid']);
            $total = floatval($_POST['total']);
            $balance = $total - $amount_paid;
            $seller = $_SESSION['full_name'];
            $sale_date = date('Y-m-d');
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                json_response(['success' => false, 'message' => 'No items in sale'], 400);
            }
            
            // Start transaction
            $db->getConnection()->begin_transaction();
            
            try {
                // Insert sale
                $stmt = $db->prepare("INSERT INTO sales (customer_id, customer_name, total, payment_method, amount_paid, balance, seller, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdsddss", $customer_id, $customer_name, $total, $payment_method, $amount_paid, $balance, $seller, $sale_date);
                $stmt->execute();
                $sale_id = $db->lastInsertId();
                
                // Insert sale items and update stock
                $item_stmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                $stock_stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE id = ?");
                
                foreach ($items as $item) {
                    $product_id = intval($item['product_id']);
                    $product_name = sanitize_input($item['product_name']);
                    $quantity = intval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $item_total = $quantity * $unit_price;
                    
                    // Check stock availability
                    $check_result = $db->query("SELECT quantity FROM stock WHERE id = $product_id");
                    if ($check_result->num_rows === 0) {
                        throw new Exception("Product not found: $product_name");
                    }
                    
                    $stock = $check_result->fetch_assoc();
                    if ($stock['quantity'] < $quantity) {
                        throw new Exception("Insufficient stock for: $product_name");
                    }
                    
                    // Insert sale item
                    $item_stmt->bind_param("iisidd", $sale_id, $product_id, $product_name, $quantity, $unit_price, $item_total);
                    $item_stmt->execute();
                    
                    // Update stock
                    $stock_stmt->bind_param("ii", $quantity, $product_id);
                    $stock_stmt->execute();
                }
                
                // Update customer if not walk-in
                if ($customer_id) {
                    $update_customer = $db->prepare("UPDATE customers SET total_purchases = total_purchases + ?, last_purchase = ? WHERE id = ?");
                    $update_customer->bind_param("dsi", $total, $sale_date, $customer_id);
                    $update_customer->execute();
                }
                
                // Commit transaction
                $db->getConnection()->commit();
                
                log_audit('create', "Recorded sale: KES $total for $customer_name");
                json_response(['success' => true, 'message' => 'Sale recorded successfully', 'sale_id' => $sale_id]);
                
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                throw $e;
            }
            break;
            
        case 'get':
            $id = intval($_GET['id']);
            
            // Get sale details
            $sale_result = $db->query("SELECT * FROM sales WHERE id = $id");
            if ($sale_result->num_rows === 0) {
                json_response(['success' => false, 'message' => 'Sale not found'], 404);
            }
            
            $sale = $sale_result->fetch_assoc();
            
            // Get sale items
            $items_result = $db->query("SELECT * FROM sale_items WHERE sale_id = $id");
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
            
            $sale['items'] = $items;
            json_response(['success' => true, 'data' => $sale]);
            break;
            
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $search = sanitize_input($_GET['search'] ?? '');
            $date_from = sanitize_input($_GET['date_from'] ?? '');
            $date_to = sanitize_input($_GET['date_to'] ?? '');
            $payment_method = sanitize_input($_GET['payment_method'] ?? '');
            
            $where = [];
            if ($search) {
                $where[] = "(customer_name LIKE '%$search%' OR seller LIKE '%$search%')";
            }
            if ($date_from) {
                $where[] = "sale_date >= '$date_from'";
            }
            if ($date_to) {
                $where[] = "sale_date <= '$date_to'";
            }
            if ($payment_method) {
                $where[] = "payment_method = '$payment_method'";
            }
            
            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $count_result = $db->query("SELECT COUNT(*) as total FROM sales $where_clause");
            $total = $count_result->fetch_assoc()['total'];
            
            $result = $db->query("SELECT s.*, GROUP_CONCAT(si.product_name SEPARATOR ', ') as products 
                FROM sales s 
                LEFT JOIN sale_items si ON s.id = si.sale_id 
                $where_clause 
                GROUP BY s.id 
                ORDER BY s.created_at DESC 
                LIMIT $limit OFFSET $offset");
            
            $sales = [];
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $sales,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
            break;
            
        case 'delete':
            if (!has_permission('admin')) {
                json_response(['success' => false, 'message' => 'Permission denied'], 403);
            }
            
            $id = intval($_POST['id']);
            
            // Get sale info for audit
            $result = $db->query("SELECT customer_name, total FROM sales WHERE id = $id");
            if ($result->num_rows > 0) {
                $sale = $result->fetch_assoc();
                
                $stmt = $db->prepare("DELETE FROM sales WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    log_audit('delete', "Deleted sale: {$sale['customer_name']} - KES {$sale['total']}");
                    json_response(['success' => true, 'message' => 'Sale deleted successfully']);
                } else {
                    json_response(['success' => false, 'message' => 'Failed to delete sale'], 500);
                }
            } else {
                json_response(['success' => false, 'message' => 'Sale not found'], 404);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>