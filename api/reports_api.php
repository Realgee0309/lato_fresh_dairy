<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'daily_sales':
            $date = sanitize_input($_GET['date'] ?? date('Y-m-d'));
            
            $result = $db->query("SELECT SUM(total) as total FROM sales WHERE sale_date = '$date'");
            $daily_sales = $result->fetch_assoc()['total'] ?? 0;
            
            json_response([
                'success' => true,
                'date' => $date,
                'total' => $daily_sales
            ]);
            break;
            
        case 'monthly_sales':
            $month = sanitize_input($_GET['month'] ?? date('Y-m'));
            
            $result = $db->query("SELECT SUM(total) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$month'");
            $monthly_sales = $result->fetch_assoc()['total'] ?? 0;
            
            json_response([
                'success' => true,
                'month' => $month,
                'total' => $monthly_sales
            ]);
            break;
            
        case 'yearly_sales':
            $year = sanitize_input($_GET['year'] ?? date('Y'));
            
            $result = $db->query("SELECT SUM(total) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y') = '$year'");
            $yearly_sales = $result->fetch_assoc()['total'] ?? 0;
            
            json_response([
                'success' => true,
                'year' => $year,
                'total' => $yearly_sales
            ]);
            break;
            
        case 'top_products':
            $limit = intval($_GET['limit'] ?? 5);
            
            $result = $db->query("SELECT si.product_name, SUM(si.quantity) as total_sold, SUM(si.total) as revenue 
                FROM sale_items si 
                GROUP BY si.product_name 
                ORDER BY total_sold DESC 
                LIMIT $limit");
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $products
            ]);
            break;
            
        case 'category_breakdown':
            $result = $db->query("SELECT category, SUM(quantity) as total_qty, SUM(quantity * price) as total_value FROM stock GROUP BY category");
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $categories
            ]);
            break;
            
        case 'low_stock':
            $result = $db->query("SELECT * FROM stock WHERE quantity < alert_level ORDER BY quantity ASC");
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
            break;
            
        case 'expiring_soon':
            $days = intval($_GET['days'] ?? 7);
            
            $result = $db->query("SELECT * FROM stock 
                WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) 
                ORDER BY expiry_date ASC");
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
            break;
            
        case 'sales_by_date_range':
            $date_from = sanitize_input($_GET['date_from'] ?? date('Y-m-01'));
            $date_to = sanitize_input($_GET['date_to'] ?? date('Y-m-d'));
            
            $result = $db->query("SELECT sale_date, COUNT(*) as transactions, SUM(total) as total 
                FROM sales 
                WHERE sale_date BETWEEN '$date_from' AND '$date_to' 
                GROUP BY sale_date 
                ORDER BY sale_date ASC");
            
            $sales = [];
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $sales,
                'date_from' => $date_from,
                'date_to' => $date_to
            ]);
            break;
            
        case 'sales_by_payment':
            $result = $db->query("SELECT payment_method, COUNT(*) as count, SUM(total) as total 
                FROM sales 
                GROUP BY payment_method");
            
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
            
            json_response([
                'success' => true,
                'data' => $payments
            ]);
            break;
            
        case 'customer_stats':
            $result = $db->query("SELECT COUNT(*) as total_customers, 
                SUM(total_purchases) as total_revenue,
                AVG(total_purchases) as avg_purchase
                FROM customers");
            
            $stats = $result->fetch_assoc();
            
            json_response([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'inventory_value':
            $result = $db->query("SELECT SUM(quantity * price) as total_value, 
                SUM(quantity) as total_units,
                COUNT(*) as total_products
                FROM stock");
            
            $inventory = $result->fetch_assoc();
            
            json_response([
                'success' => true,
                'data' => $inventory
            ]);
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>