-- =====================================================
-- Lato Fresh Dairy Management System - Complete Database
-- =====================================================
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS lato_fresh_dairy;
CREATE DATABASE lato_fresh_dairy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lato_fresh_dairy;

-- =====================================================
-- 1. USERS TABLE (with profile images)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'sales', 'warehouse', 'manager') NOT NULL,
    active TINYINT(1) DEFAULT 1,
    profile_image VARCHAR(255) NULL,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. STOCK TABLE (with image support and batch numbers)
-- =====================================================
CREATE TABLE IF NOT EXISTS stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('Milk', 'Yogurt', 'Cheese', 'Butter', 'Cream') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL,
    expiry_date DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    alert_level INT DEFAULT 10,
    supplier VARCHAR(100),
    batch_number VARCHAR(50),
    image_path VARCHAR(255) NULL,
    date_added DATE NOT NULL,
    added_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_expiry (expiry_date),
    INDEX idx_quantity (quantity),
    INDEX idx_batch_number (batch_number),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CUSTOMERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    total_purchases DECIMAL(12, 2) DEFAULT 0,
    last_purchase DATE,
    date_added DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_name (name),
    INDEX idx_last_purchase (last_purchase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. SALES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_name VARCHAR(100) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'mpesa', 'card', 'credit') NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0,
    seller VARCHAR(100) NOT NULL,
    sale_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_sale_date (sale_date),
    INDEX idx_customer (customer_id),
    INDEX idx_seller (seller),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. SALE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES stock(id) ON DELETE SET NULL,
    INDEX idx_sale (sale_id),
    INDEX idx_product (product_id),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. AUDIT LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    user VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user (user),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. BACKUP HISTORY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_date DATE NOT NULL,
    backup_type ENUM('manual', 'automatic') NOT NULL,
    file_size INT,
    status ENUM('success', 'failed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backup_date (backup_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT USERS
-- =====================================================
-- Password for all default users: admin123 (for testing)
INSERT INTO users (username, password, full_name, email, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@latofresh.com', 'admin', 1),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Manager', 'manager@latofresh.com', 'manager', 1),
('sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Clerk', 'sales@latofresh.com', 'sales', 1),
('warehouse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Warehouse Manager', 'warehouse@latofresh.com', 'warehouse', 1);

-- =====================================================
-- INSERT SAMPLE STOCK DATA (with batch numbers)
-- =====================================================
INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, date_added, added_by) VALUES
('Fresh Milk 1L', 'Milk', 150, 120.00, DATE_ADD(CURDATE(), INTERVAL 7 DAYS), 'Main Warehouse - Fridge A1', 20, 'Dairy Farm Co.', 'MIL-20250101-A1B2', CURDATE(), 'admin'),
('Full Cream Milk 500ml', 'Milk', 200, 65.00, DATE_ADD(CURDATE(), INTERVAL 5 DAYS), 'Main Warehouse - Fridge A2', 30, 'Dairy Farm Co.', 'MIL-20250101-C3D4', CURDATE(), 'admin'),
('Skimmed Milk 1L', 'Milk', 100, 110.00, DATE_ADD(CURDATE(), INTERVAL 6 DAYS), 'Main Warehouse - Fridge A1', 15, 'Dairy Farm Co.', 'MIL-20250101-E5F6', CURDATE(), 'admin'),
('Greek Yogurt 500g', 'Yogurt', 80, 180.00, DATE_ADD(CURDATE(), INTERVAL 10 DAYS), 'Cold Storage - Section B', 10, 'Yogurt Masters Ltd', 'YOG-20250102-G7H8', CURDATE(), 'admin'),
('Strawberry Yogurt 200g', 'Yogurt', 120, 95.00, DATE_ADD(CURDATE(), INTERVAL 8 DAYS), 'Cold Storage - Section B', 20, 'Yogurt Masters Ltd', 'YOG-20250102-I9J0', CURDATE(), 'admin'),
('Natural Yogurt 1kg', 'Yogurt', 60, 250.00, DATE_ADD(CURDATE(), INTERVAL 12 DAYS), 'Cold Storage - Section B', 10, 'Yogurt Masters Ltd', 'YOG-20250102-K1L2', CURDATE(), 'admin'),
('Cheddar Cheese 200g', 'Cheese', 50, 350.00, DATE_ADD(CURDATE(), INTERVAL 30 DAYS), 'Main Warehouse - Shelf C', 8, 'Cheese Makers Ltd', 'CHE-20250103-M3N4', CURDATE(), 'admin'),
('Mozzarella Cheese 250g', 'Cheese', 45, 420.00, DATE_ADD(CURDATE(), INTERVAL 25 DAYS), 'Main Warehouse - Shelf C', 8, 'Cheese Makers Ltd', 'CHE-20250103-O5P6', CURDATE(), 'admin'),
('Gouda Cheese 300g', 'Cheese', 35, 480.00, DATE_ADD(CURDATE(), INTERVAL 35 DAYS), 'Main Warehouse - Shelf C', 5, 'Cheese Makers Ltd', 'CHE-20250103-Q7R8', CURDATE(), 'admin'),
('Salted Butter 500g', 'Butter', 90, 250.00, DATE_ADD(CURDATE(), INTERVAL 45 DAYS), 'Cold Storage - Section D', 15, 'Dairy Farm Co.', 'BUT-20250104-S9T0', CURDATE(), 'admin'),
('Unsalted Butter 250g', 'Butter', 75, 140.00, DATE_ADD(CURDATE(), INTERVAL 40 DAYS), 'Cold Storage - Section D', 12, 'Dairy Farm Co.', 'BUT-20250104-U1V2', CURDATE(), 'admin'),
('Heavy Cream 250ml', 'Cream', 65, 200.00, DATE_ADD(CURDATE(), INTERVAL 14 DAYS), 'Cold Storage - Section E', 10, 'Dairy Farm Co.', 'CRE-20250105-W3X4', CURDATE(), 'admin'),
('Whipping Cream 500ml', 'Cream', 55, 380.00, DATE_ADD(CURDATE(), INTERVAL 12 DAYS), 'Cold Storage - Section E', 8, 'Dairy Farm Co.', 'CRE-20250105-Y5Z6', CURDATE(), 'admin'),
('Cooking Cream 1L', 'Cream', 40, 450.00, DATE_ADD(CURDATE(), INTERVAL 15 DAYS), 'Cold Storage - Section E', 8, 'Dairy Farm Co.', 'CRE-20250105-A7B8', CURDATE(), 'admin'),
('Organic Milk 1L', 'Milk', 70, 180.00, DATE_ADD(CURDATE(), INTERVAL 5 DAYS), 'Main Warehouse - Fridge A3', 10, 'Organic Farms Ltd', 'MIL-20250106-C9D0', CURDATE(), 'admin');

-- =====================================================
-- INSERT SAMPLE CUSTOMERS
-- =====================================================
INSERT INTO customers (name, phone, email, address, total_purchases, last_purchase, date_added) VALUES
('John Kamau', '+254712345678', 'john.kamau@email.com', '123 Kenyatta Avenue, Nairobi', 5200.00, CURDATE(), DATE_SUB(CURDATE(), INTERVAL 30 DAYS)),
('Mary Wanjiku', '+254723456789', 'mary.wanjiku@email.com', '456 Moi Avenue, Nairobi', 3800.00, DATE_SUB(CURDATE(), INTERVAL 2 DAYS), DATE_SUB(CURDATE(), INTERVAL 60 DAYS)),
('Peter Omondi', '+254734567890', 'peter.omondi@email.com', '789 Uhuru Highway, Nairobi', 6500.00, DATE_SUB(CURDATE(), INTERVAL 1 DAYS), DATE_SUB(CURDATE(), INTERVAL 45 DAYS)),
('Grace Akinyi', '+254745678901', 'grace.akinyi@email.com', '321 Tom Mboya Street, Nairobi', 2400.00, DATE_SUB(CURDATE(), INTERVAL 5 DAYS), DATE_SUB(CURDATE(), INTERVAL 20 DAYS)),
('David Mwangi', '+254756789012', 'david.mwangi@email.com', '654 Haile Selassie Avenue, Nairobi', 4100.00, DATE_SUB(CURDATE(), INTERVAL 3 DAYS), DATE_SUB(CURDATE(), INTERVAL 90 DAYS)),
('Sarah Njeri', '+254767890123', 'sarah.njeri@email.com', '987 Kimathi Street, Nairobi', 1800.00, DATE_SUB(CURDATE(), INTERVAL 10 DAYS), DATE_SUB(CURDATE(), INTERVAL 15 DAYS)),
('James Otieno', '+254778901234', 'james.otieno@email.com', '147 Muindi Mbingu Street, Nairobi', 7200.00, CURDATE(), DATE_SUB(CURDATE(), INTERVAL 120 DAYS)),
('Lucy Wambui', '+254789012345', 'lucy.wambui@email.com', '258 University Way, Nairobi', 3300.00, DATE_SUB(CURDATE(), INTERVAL 7 DAYS), DATE_SUB(CURDATE(), INTERVAL 25 DAYS));

-- =====================================================
-- INSERT SAMPLE SALES (Recent transactions)
-- =====================================================
INSERT INTO sales (customer_id, customer_name, total, payment_method, amount_paid, balance, seller, sale_date) VALUES
-- Today's sales
(1, 'John Kamau', 720.00, 'mpesa', 720.00, 0, 'Sales Clerk', CURDATE()),
(3, 'Peter Omondi', 1450.00, 'cash', 1500.00, -50.00, 'Sales Clerk', CURDATE()),
(7, 'James Otieno', 890.00, 'card', 890.00, 0, 'Sales Clerk', CURDATE()),
-- Yesterday's sales
(2, 'Mary Wanjiku', 540.00, 'mpesa', 540.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(5, 'David Mwangi', 1120.00, 'cash', 1120.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
-- 2 days ago
(4, 'Grace Akinyi', 380.00, 'mpesa', 380.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 2 DAYS)),
(6, 'Sarah Njeri', 670.00, 'cash', 700.00, -30.00, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 2 DAYS)),
-- 3 days ago
(5, 'David Mwangi', 920.00, 'card', 920.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 3 DAYS)),
(8, 'Lucy Wambui', 560.00, 'mpesa', 560.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 3 DAYS)),
-- Last week
(1, 'John Kamau', 1240.00, 'cash', 1240.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 7 DAYS)),
(3, 'Peter Omondi', 1850.00, 'mpesa', 1850.00, 0, 'Sales Clerk', DATE_SUB(CURDATE(), INTERVAL 7 DAYS));

-- =====================================================
-- INSERT SAMPLE SALE ITEMS
-- =====================================================
INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES
-- Sale 1 (John Kamau - Today)
(1, 1, 'Fresh Milk 1L', 3, 120.00, 360.00),
(1, 4, 'Greek Yogurt 500g', 2, 180.00, 360.00),
-- Sale 2 (Peter Omondi - Today)
(2, 7, 'Cheddar Cheese 200g', 2, 350.00, 700.00),
(2, 10, 'Salted Butter 500g', 3, 250.00, 750.00),
-- Sale 3 (James Otieno - Today)
(3, 2, 'Full Cream Milk 500ml', 5, 65.00, 325.00),
(3, 5, 'Strawberry Yogurt 200g', 3, 95.00, 285.00),
(3, 12, 'Heavy Cream 250ml', 1, 200.00, 200.00),
(3, 11, 'Unsalted Butter 250g', 1, 140.00, 140.00),
-- Sale 4 (Mary Wanjiku - Yesterday)
(4, 4, 'Greek Yogurt 500g', 3, 180.00, 540.00),
-- Sale 5 (David Mwangi - Yesterday)
(5, 1, 'Fresh Milk 1L', 4, 120.00, 480.00),
(5, 7, 'Cheddar Cheese 200g', 1, 350.00, 350.00),
(5, 11, 'Unsalted Butter 250g', 2, 140.00, 280.00),
-- Sale 6 (Grace Akinyi - 2 days ago)
(6, 5, 'Strawberry Yogurt 200g', 4, 95.00, 380.00),
-- Sale 7 (Sarah Njeri - 2 days ago)
(7, 2, 'Full Cream Milk 500ml', 6, 65.00, 390.00),
(7, 12, 'Heavy Cream 250ml', 1, 200.00, 200.00),
(7, 11, 'Unsalted Butter 250g', 1, 140.00, 140.00),
-- Sale 8 (David Mwangi - 3 days ago)
(8, 8, 'Mozzarella Cheese 250g', 2, 420.00, 840.00),
(8, 11, 'Unsalted Butter 250g', 1, 140.00, 140.00),
-- Sale 9 (Lucy Wambui - 3 days ago)
(9, 4, 'Greek Yogurt 500g', 2, 180.00, 360.00),
(9, 12, 'Heavy Cream 250ml', 1, 200.00, 200.00),
-- Sale 10 (John Kamau - Last week)
(10, 1, 'Fresh Milk 1L', 6, 120.00, 720.00),
(10, 10, 'Salted Butter 500g', 2, 250.00, 500.00),
(10, 12, 'Heavy Cream 250ml', 1, 200.00, 200.00),
-- Sale 11 (Peter Omondi - Last week)
(11, 7, 'Cheddar Cheese 200g', 3, 350.00, 1050.00),
(11, 4, 'Greek Yogurt 500g', 2, 180.00, 360.00),
(11, 13, 'Whipping Cream 500ml', 1, 380.00, 380.00),
(11, 11, 'Unsalted Butter 250g', 1, 140.00, 140.00);

-- =====================================================
-- INSERT AUDIT LOGS (System activities)
-- =====================================================
INSERT INTO audit_logs (action, description, user) VALUES
('login', 'User admin logged in', 'admin'),
('create', 'Added new product: Fresh Milk 1L (Batch: MIL-20250101-A1B2)', 'admin'),
('create', 'Added new product: Greek Yogurt 500g (Batch: YOG-20250102-G7H8)', 'admin'),
('login', 'User sales logged in', 'sales'),
('create', 'Recorded sale: KES 720.00 for John Kamau', 'sales'),
('create', 'Recorded sale: KES 1450.00 for Peter Omondi', 'sales'),
('login', 'User warehouse logged in', 'warehouse'),
('update', 'Updated product: Fresh Milk 1L (ID: 1)', 'warehouse'),
('login', 'User manager logged in', 'manager'),
('view', 'Viewed sales report for current month', 'manager'),
('login', 'User admin logged in', 'admin'),
('create', 'Admin added new user: sales with role sales', 'admin'),
('update', 'Admin updated profile for user: sales', 'admin');

-- =====================================================
-- INSERT SYSTEM SETTINGS
-- =====================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('app_version', '1.0.0'),
('currency', 'KES'),
('tax_rate', '16'),
('low_stock_alert_enabled', '1'),
('expiry_alert_days', '7'),
('backup_enabled', '1'),
('backup_frequency', 'daily');

-- =====================================================
-- CREATE DATABASE VIEWS FOR REPORTING
-- =====================================================

-- View: Daily Sales Summary
CREATE OR REPLACE VIEW v_daily_sales AS
SELECT 
    sale_date,
    COUNT(*) as total_transactions,
    SUM(total) as total_sales,
    SUM(amount_paid) as total_paid,
    AVG(total) as average_sale,
    COUNT(DISTINCT customer_id) as unique_customers
FROM sales
GROUP BY sale_date
ORDER BY sale_date DESC;

-- View: Product Performance
CREATE OR REPLACE VIEW v_product_performance AS
SELECT 
    s.id,
    s.name,
    s.category,
    s.quantity as current_stock,
    s.price,
    COALESCE(SUM(si.quantity), 0) as total_sold,
    COALESCE(SUM(si.total), 0) as total_revenue,
    s.alert_level,
    CASE 
        WHEN s.quantity < s.alert_level THEN 'Low Stock'
        WHEN DATEDIFF(s.expiry_date, CURDATE()) < 7 THEN 'Expiring Soon'
        WHEN s.expiry_date < CURDATE() THEN 'Expired'
        ELSE 'OK'
    END as status
FROM stock s
LEFT JOIN sale_items si ON s.id = si.product_id
GROUP BY s.id, s.name, s.category, s.quantity, s.price, s.alert_level, s.expiry_date
ORDER BY total_sold DESC;

-- View: Customer Analytics
CREATE OR REPLACE VIEW v_customer_analytics AS
SELECT 
    c.id,
    c.name,
    c.phone,
    c.email,
    c.total_purchases,
    c.last_purchase,
    COUNT(s.id) as total_orders,
    DATEDIFF(CURDATE(), c.last_purchase) as days_since_last_purchase,
    CASE 
        WHEN DATEDIFF(CURDATE(), c.last_purchase) <= 7 THEN 'Active'
        WHEN DATEDIFF(CURDATE(), c.last_purchase) <= 30 THEN 'Regular'
        WHEN DATEDIFF(CURDATE(), c.last_purchase) <= 90 THEN 'Inactive'
        ELSE 'Dormant'
    END as customer_status
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id
GROUP BY c.id, c.name, c.phone, c.email, c.total_purchases, c.last_purchase;

-- View: Low Stock Alert
CREATE OR REPLACE VIEW v_low_stock_alert AS
SELECT 
    id,
    name,
    category,
    quantity,
    alert_level,
    (alert_level - quantity) as shortage,
    location,
    batch_number,
    supplier
FROM stock
WHERE quantity < alert_level
ORDER BY shortage DESC;

-- View: Expiring Products
CREATE OR REPLACE VIEW v_expiring_products AS
SELECT 
    id,
    name,
    category,
    quantity,
    expiry_date,
    DATEDIFF(expiry_date, CURDATE()) as days_until_expiry,
    location,
    batch_number,
    CASE 
        WHEN expiry_date < CURDATE() THEN 'Expired'
        WHEN DATEDIFF(expiry_date, CURDATE()) <= 3 THEN 'Critical'
        WHEN DATEDIFF(expiry_date, CURDATE()) <= 7 THEN 'Warning'
        ELSE 'Normal'
    END as urgency_level
FROM stock
WHERE DATEDIFF(expiry_date, CURDATE()) <= 30
ORDER BY days_until_expiry ASC;

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

-- Procedure: Get Sales Summary by Date Range
DELIMITER //
CREATE PROCEDURE sp_get_sales_summary(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total) as total_sales,
        SUM(amount_paid) as total_paid,
        SUM(balance) as total_balance,
        AVG(total) as average_sale,
        MIN(total) as min_sale,
        MAX(total) as max_sale
    FROM sales
    WHERE sale_date BETWEEN start_date AND end_date;
END //
DELIMITER ;

-- Procedure: Get Top Selling Products
DELIMITER //
CREATE PROCEDURE sp_get_top_products(
    IN limit_count INT
)
BEGIN
    SELECT 
        si.product_name,
        SUM(si.quantity) as total_sold,
        SUM(si.total) as total_revenue,
        COUNT(DISTINCT si.sale_id) as number_of_sales
    FROM sale_items si
    GROUP BY si.product_name
    ORDER BY total_sold DESC
    LIMIT limit_count;
END //
DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

-- Trigger: Update stock quantity after sale
DELIMITER //
CREATE TRIGGER tr_after_sale_item_insert
AFTER INSERT ON sale_items
FOR EACH ROW
BEGIN
    UPDATE stock 
    SET quantity = quantity - NEW.quantity
    WHERE id = NEW.product_id;
END //
DELIMITER ;

-- Trigger: Log user activity
DELIMITER //
CREATE TRIGGER tr_after_user_login_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO audit_logs (action, description, user)
        VALUES ('login', CONCAT('User ', NEW.username, ' logged in'), NEW.username);
    END IF;
END //
DELIMITER ;

-- =====================================================
-- GRANT PERMISSIONS (Optional - for production)
-- =====================================================
-- GRANT ALL PRIVILEGES ON lato_fresh_dairy.* TO 'lato_admin'@'localhost' IDENTIFIED BY 'secure_password';
-- FLUSH PRIVILEGES;

-- =====================================================
-- DATABASE SUMMARY
-- =====================================================
SELECT 
    'Database Setup Complete!' as Status,
    (SELECT COUNT(*) FROM users) as Total_Users,
    (SELECT COUNT(*) FROM stock) as Total_Products,
    (SELECT COUNT(*) FROM customers) as Total_Customers,
    (SELECT COUNT(*) FROM sales) as Total_Sales,
    (SELECT SUM(total) FROM sales) as Total_Revenue;

-- Show all tables
SHOW TABLES;

-- =====================================================
-- END OF SCRIPT
-- =====================================================