-- Lato Fresh Dairy Management System Database
-- Create Database
CREATE DATABASE IF NOT EXISTS lato_fresh_dairy;
USE lato_fresh_dairy;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'sales', 'warehouse', 'manager') NOT NULL,
    active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Stock Table
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
    date_added DATE NOT NULL,
    added_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_expiry (expiry_date),
    INDEX idx_quantity (quantity)
);

-- Customers Table
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
    INDEX idx_phone (phone)
);

-- Sales Table
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
    INDEX idx_customer (customer_id)
);

-- Sale Items Table
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
    INDEX idx_product (product_id)
);

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    user VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user (user),
    INDEX idx_created (created_at)
);

-- Backup History Table
CREATE TABLE IF NOT EXISTS backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_date DATE NOT NULL,
    backup_type ENUM('manual', 'automatic') NOT NULL,
    file_size INT,
    status ENUM('success', 'failed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@latofresh.com', 'admin', 1),
('sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Clerk', 'sales@latofresh.com', 'sales', 1),
('warehouse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Warehouse Staff', 'warehouse@latofresh.com', 'warehouse', 1),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager User', 'manager@latofresh.com', 'manager', 1);

-- Insert Sample Stock Data
INSERT INTO stock (name, category, quantity, price, expiry_date, location, alert_level, supplier, batch_number, date_added, added_by) VALUES
('Fresh Milk 1L', 'Milk', 50, 120.00, DATE_ADD(CURDATE(), INTERVAL 7 DAYS), 'Main Warehouse', 10, 'Dairy Farm Co.', 'BATCH-001', CURDATE(), 'admin'),
('Greek Yogurt', 'Yogurt', 30, 180.00, DATE_ADD(CURDATE(), INTERVAL 5 DAYS), 'Cold Storage', 5, 'Dairy Farm Co.', 'BATCH-002', CURDATE(), 'admin'),
('Cheddar Cheese', 'Cheese', 20, 350.00, DATE_ADD(CURDATE(), INTERVAL 14 DAYS), 'Main Warehouse', 5, 'Cheese Makers Ltd', 'BATCH-003', CURDATE(), 'admin'),
('Salted Butter 500g', 'Butter', 40, 250.00, DATE_ADD(CURDATE(), INTERVAL 21 DAYS), 'Cold Storage', 10, 'Dairy Farm Co.', 'BATCH-004', CURDATE(), 'admin'),
('Heavy Cream 250ml', 'Cream', 25, 200.00, DATE_ADD(CURDATE(), INTERVAL 10 DAYS), 'Cold Storage', 8, 'Dairy Farm Co.', 'BATCH-005', CURDATE(), 'admin');

-- Insert Sample Customers
INSERT INTO customers (name, phone, email, address, total_purchases, last_purchase, date_added) VALUES
('John Doe', '+254712345678', 'john.doe@example.com', '123 Main St, Nairobi', 1250.00, CURDATE(), CURDATE()),
('Jane Smith', '+254723456789', 'jane.smith@example.com', '456 Oak Ave, Nairobi', 850.00, CURDATE(), CURDATE()),
('Peter Kamau', '+254734567890', 'peter.kamau@example.com', '789 Kenyatta Rd, Nairobi', 2100.00, CURDATE(), CURDATE());

-- Insert Sample Sales
INSERT INTO sales (customer_id, customer_name, total, payment_method, amount_paid, balance, seller, sale_date) VALUES
(1, 'John Doe', 600.00, 'cash', 600.00, 0, 'Sales Clerk', CURDATE()),
(2, 'Jane Smith', 540.00, 'mpesa', 540.00, 0, 'Sales Clerk', CURDATE());

-- Insert Sample Sale Items
INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES
(1, 1, 'Fresh Milk 1L', 5, 120.00, 600.00),
(2, 2, 'Greek Yogurt', 3, 180.00, 540.00);

-- Insert Initial Audit Log
INSERT INTO audit_logs (action, description, user) VALUES
('create', 'System initialized with sample data', 'admin');