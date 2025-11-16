<?php
/**
 * Lato Fresh Dairy Management System
 * Configuration File
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lato_fresh_dairy');

// Application Configuration
define('APP_NAME', 'Lato Fresh Dairy');
define('APP_TAGLINE', 'Pure Dairy, Smart Control');
define('APP_URL', 'http://localhost/lato_fresh_dairy');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
}

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function has_permission($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['role'];
    $role_hierarchy = ['admin' => 4, 'manager' => 3, 'warehouse' => 2, 'sales' => 1];
    
    return $role_hierarchy[$user_role] >= $role_hierarchy[$required_role];
}

function log_audit($action, $description, $user = null) {
    $db = Database::getInstance();
    $user = $user ?? $_SESSION['username'] ?? 'system';
    
    $stmt = $db->prepare("INSERT INTO audit_logs (action, description, user) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $action, $description, $user);
    $stmt->execute();
    $stmt->close();
}

function format_currency($amount) {
    return 'KES ' . number_format($amount, 2);
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' seconds ago';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' minutes ago';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' hours ago';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

function get_role_display($role) {
    $roles = [
        'admin' => 'Administrator',
        'sales' => 'Sales Clerk',
        'warehouse' => 'Warehouse Staff',
        'manager' => 'Manager'
    ];
    return $roles[$role] ?? $role;
}

function get_days_until_expiry($expiry_date) {
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    $interval = $today->diff($expiry);
    return $interval->invert ? -$interval->days : $interval->days;
}

function get_stock_status($product) {
    $days_left = get_days_until_expiry($product['expiry_date']);
    
    if ($product['quantity'] < $product['alert_level']) {
        return ['class' => 'status-low', 'text' => 'Low Stock'];
    } elseif ($days_left < 0) {
        return ['class' => 'status-expiring', 'text' => 'Expired'];
    } elseif ($days_left <= 7) {
        return ['class' => 'status-expiring', 'text' => 'Expiring Soon'];
    } else {
        return ['class' => 'status-ok', 'text' => 'In Stock'];
    }
}

// JSON Response Helper
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Initialize database connection
$db = Database::getInstance();
?>