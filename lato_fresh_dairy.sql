-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 17, 2025 at 08:00 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lato_fresh_dairy`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `user` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `description`, `user`, `created_at`) VALUES
(1, 'login', 'User admin logged in', 'admin', '2025-11-12 15:46:17'),
(2, 'create', 'Added new user: new', 'admin', '2025-11-12 15:47:59'),
(3, 'logout', 'User admin logged out', 'admin', '2025-11-12 15:50:10'),
(4, 'login', 'User new logged in', 'new', '2025-11-12 15:50:21'),
(5, 'login', 'User admin logged in', 'admin', '2025-11-14 04:21:11'),
(6, 'create', 'Added new product: lato fresh', 'admin', '2025-11-14 04:24:33'),
(7, 'create', 'Added new product: lato fresh', 'admin', '2025-11-14 04:31:20'),
(8, 'create', 'Added new user: test', 'admin', '2025-11-14 04:33:22'),
(9, 'create', 'Added new customer: test customer', 'admin', '2025-11-14 04:36:14'),
(10, 'create', 'Recorded sale: KES 585 for test customer', 'admin', '2025-11-14 04:37:28'),
(11, 'logout', 'User admin logged out', 'admin', '2025-11-14 04:38:50'),
(12, 'login', 'User test logged in', 'test', '2025-11-14 04:39:00'),
(13, 'logout', 'User test logged out', 'test', '2025-11-14 04:42:35'),
(14, 'login', 'User sales logged in', 'sales', '2025-11-14 04:42:57'),
(15, 'create', 'Recorded sale: KES 130 for Walk-in Customer', 'sales', '2025-11-14 04:43:52'),
(16, 'logout', 'User sales logged out', 'sales', '2025-11-14 04:45:28'),
(17, 'login', 'User manager logged in', 'manager', '2025-11-14 04:45:46'),
(18, 'logout', 'User manager logged out', 'manager', '2025-11-14 04:47:29'),
(19, 'login', 'User admin logged in', 'admin', '2025-11-14 04:47:42'),
(20, 'login', 'User admin logged in', 'admin', '2025-11-14 08:10:34'),
(21, 'create', 'Recorded sale: KES 325 for Walk-in Customer', 'admin', '2025-11-14 08:11:23'),
(22, 'logout', 'User admin logged out', 'admin', '2025-11-14 08:18:00'),
(23, 'login', 'User admin logged in', 'admin', '2025-11-14 08:18:09'),
(24, 'create', 'Recorded sale: KES 130 for Walk-in Customer', 'admin', '2025-11-14 08:23:44'),
(25, 'create', 'Added new product: lato premium', 'admin', '2025-11-14 08:31:32'),
(26, 'create', 'Recorded sale: KES 8000 for Walk-in Customer', 'admin', '2025-11-14 08:32:09'),
(27, 'create', 'Recorded sale: KES 3200 for Walk-in Customer', 'admin', '2025-11-14 08:35:53'),
(28, 'create', 'Added new product: lato vanilla', 'admin', '2025-11-14 08:40:58'),
(29, 'create', 'Added new product: lato vanilla', 'admin', '2025-11-14 18:14:34'),
(30, 'create', 'Added new customer: new', 'admin', '2025-11-14 18:16:17'),
(31, 'create', 'Added new user: user', 'admin', '2025-11-15 07:27:18'),
(32, 'delete', 'Deleted user: user', 'admin', '2025-11-15 07:30:06'),
(33, 'create', 'Added new user: user with role sales', 'admin', '2025-11-15 07:30:31'),
(34, 'delete', 'Deleted user: user', 'admin', '2025-11-15 07:32:47'),
(35, 'create', 'Added new user: user with role sales', 'admin', '2025-11-15 07:33:21'),
(36, 'create', 'Added new product: lato premium', 'admin', '2025-11-15 07:35:56'),
(37, 'create', 'Added new product: lato fresh', 'admin', '2025-11-15 07:56:47'),
(38, 'create', 'Added new product: lato fresh (Batch: MIL-20251115-3ZEF)', 'admin', '2025-11-15 08:28:44'),
(39, 'create', 'Added new product: fresh milk (Batch: MIL-20251115-9KGK)', 'admin', '2025-11-15 08:48:06'),
(40, 'logout', 'User admin logged out', 'admin', '2025-11-15 08:49:47'),
(41, 'login', 'User sales logged in', 'sales', '2025-11-15 08:50:10'),
(42, 'create', 'Recorded sale: KES 825 for new', 'sales', '2025-11-15 08:50:55'),
(43, 'create', 'Recorded sale: KES 12090 for new', 'sales', '2025-11-15 08:52:24'),
(44, 'create', 'Recorded sale: KES 6045 for Walk-in Customer', 'sales', '2025-11-15 08:53:28'),
(45, 'logout', 'User sales logged out', 'sales', '2025-11-15 08:54:20'),
(46, 'login', 'User admin logged in', 'admin', '2025-11-15 08:54:37'),
(47, 'delete', 'Deleted user: user', 'admin', '2025-11-15 08:55:52'),
(48, 'create', 'Added new product: lato yogurt (Batch: YOG-20251115-QD9D)', 'admin', '2025-11-15 09:01:42'),
(49, 'create', 'Added new product: lato fresh (Batch: MIL-20251115-PDO5)', 'admin', '2025-11-15 09:04:12'),
(50, 'create', 'Added new product: fresh milk (Batch: MIL-20251115-NWWW)', 'admin', '2025-11-15 09:07:07'),
(51, 'create', 'Added new product: lato fresh (Batch: MIL-20251115-XLOK)', 'admin', '2025-11-15 20:05:10'),
(52, 'create', 'Added new product: fresh milk (Batch: MIL-20251115-Q0MA)', 'admin', '2025-11-15 20:08:04'),
(53, 'update', 'Linked image to product ID: 1', 'admin', '2025-11-15 20:28:28'),
(54, 'update', 'Linked image to product ID: 2', 'admin', '2025-11-15 20:29:52'),
(55, 'create', 'Added new product: lato fresh (Batch: MIL-20251115-TXNG)', 'admin', '2025-11-15 20:31:17'),
(56, 'upload', 'Uploaded product image: uploads/products/product_1763239305_6918e58990aff.jpg', 'admin', '2025-11-15 20:41:45'),
(57, 'create', 'Added new product: fresh milk (Batch: MIL-20251115-I6AX)', 'admin', '2025-11-15 20:41:45'),
(58, 'create', 'Added new customer: m', 'admin', '2025-11-15 21:04:12'),
(59, 'delete', 'Deleted customer: m', 'admin', '2025-11-16 03:06:29'),
(60, 'create', 'Added new customer: m', 'admin', '2025-11-16 03:09:38'),
(61, 'create', 'Added new user: w with role manager', 'admin', '2025-11-16 03:10:56'),
(62, 'update', 'Updated profile image for user: admin', 'admin', '2025-11-16 03:31:58'),
(63, 'update', 'Changed password', 'admin', '2025-11-16 03:33:44'),
(64, 'update', 'Updated profile information', 'admin', '2025-11-16 03:34:17'),
(65, 'update', 'Updated profile image for user: admin', 'admin', '2025-11-16 03:34:30'),
(66, 'logout', 'User admin logged out', 'admin', '2025-11-16 03:34:54'),
(67, 'login', 'User sales logged in', 'sales', '2025-11-16 03:35:09'),
(68, 'logout', 'User sales logged out', 'sales', '2025-11-16 03:53:53'),
(69, 'login', 'User manager logged in', 'manager', '2025-11-16 03:55:14'),
(70, 'logout', 'User manager logged out', 'manager', '2025-11-16 03:56:22'),
(71, 'login', 'User admin logged in', 'admin', '2025-11-16 03:56:37'),
(72, 'create', 'Admin added new user: mm with role sales', 'admin', '2025-11-16 04:31:15'),
(73, 'update', 'Admin updated profile for user: mm', 'admin', '2025-11-16 04:31:51'),
(74, 'update', 'Admin updated profile for user: admin', 'admin', '2025-11-16 04:34:10'),
(75, 'update', 'Admin reset password for user ID: 17', 'admin', '2025-11-16 04:35:44'),
(76, 'create', 'Admin added new user: administrator with role admin', 'admin', '2025-11-16 04:37:12'),
(77, 'logout', 'User admin logged out', 'admin', '2025-11-16 04:37:36'),
(78, 'login', 'User admin logged in', 'admin', '2025-11-16 04:37:48'),
(79, 'upload', 'Uploaded product image: uploads/products/product_1763268762_6919589a64f36.png', 'admin', '2025-11-16 04:52:42'),
(80, 'create', 'Added new product: grinder (Batch: YOG-20251116-2HCI)', 'admin', '2025-11-16 04:52:42'),
(81, 'create', 'Recorded sale: KES 2816 for m', 'admin', '2025-11-16 04:54:15'),
(82, 'logout', 'User admin logged out', 'admin', '2025-11-16 05:24:18'),
(83, 'login', 'User sales logged in', 'sales', '2025-11-16 05:24:28'),
(84, 'logout', 'User sales logged out', 'sales', '2025-11-16 05:25:00'),
(85, 'login', 'User manager logged in', 'manager', '2025-11-16 05:25:09'),
(86, 'logout', 'User manager logged out', 'manager', '2025-11-16 05:27:06'),
(87, 'login', 'User warehouse logged in', 'warehouse', '2025-11-16 05:27:18'),
(88, 'logout', 'User warehouse logged out', 'warehouse', '2025-11-16 05:27:52'),
(89, 'login', 'User admin logged in', 'admin', '2025-11-16 05:28:01'),
(90, 'logout', 'User admin logged out', 'admin', '2025-11-16 15:22:50'),
(91, 'login', 'User admin logged in', 'admin', '2025-11-16 15:25:33'),
(92, 'create', 'Admin added new user: Ivy with role manager', 'admin', '2025-11-16 15:30:47'),
(93, 'logout', 'User admin logged out', 'admin', '2025-11-16 15:31:31'),
(94, 'login', 'User Ivy logged in', 'Ivy', '2025-11-16 15:31:58'),
(95, 'logout', 'User Ivy logged out', 'Ivy', '2025-11-16 15:32:38'),
(96, 'login', 'User admin logged in', 'admin', '2025-11-16 15:32:51'),
(97, 'upload', 'Uploaded product image: uploads/products/product_1763307352_6919ef58d094b.jpeg', 'admin', '2025-11-16 15:35:52'),
(98, 'create', 'Added new product: yeyo (Batch: MIL-20251116-1X3I)', 'admin', '2025-11-16 15:35:53'),
(99, 'create', 'Recorded sale: KES 21450 for test customer', 'admin', '2025-11-16 15:41:57'),
(100, 'logout', 'User admin logged out', 'admin', '2025-11-17 03:22:13');

-- --------------------------------------------------------

--
-- Table structure for table `backup_history`
--

CREATE TABLE `backup_history` (
  `id` int(11) NOT NULL,
  `backup_date` date NOT NULL,
  `backup_type` enum('manual','automatic') NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `total_purchases` decimal(12,2) DEFAULT 0.00,
  `last_purchase` date DEFAULT NULL,
  `date_added` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `total_purchases`, `last_purchase`, `date_added`, `created_at`, `updated_at`) VALUES
(1, 'test customer', '+25412345678', 'test@customer.com', 'test location', 22035.00, '2025-11-16', '2025-11-14', '2025-11-14 04:36:14', '2025-11-16 15:41:57'),
(2, 'new', '+254712345678', 'new@customer.com', '', 12915.00, '2025-11-15', '2025-11-14', '2025-11-14 18:16:16', '2025-11-15 08:52:24'),
(4, 'm', '+254712345679', 'm@latofreshdairy.com', 'm', 2816.00, '2025-11-16', '2025-11-16', '2025-11-16 03:09:38', '2025-11-16 04:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','mpesa','card','credit') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `seller` varchar(100) NOT NULL,
  `sale_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `customer_name`, `total`, `payment_method`, `amount_paid`, `balance`, `seller`, `sale_date`, `created_at`) VALUES
(1, 1, 'test customer', 585.00, 'mpesa', 585.00, 0.00, 'Admin User', '2025-11-14', '2025-11-14 04:37:28'),
(2, NULL, 'Walk-in Customer', 130.00, 'cash', 130.00, 0.00, 'Sales Clerk', '2025-11-14', '2025-11-14 04:43:51'),
(3, NULL, 'Walk-in Customer', 325.00, 'cash', 325.00, 0.00, 'Admin User', '2025-11-14', '2025-11-14 08:11:23'),
(4, NULL, 'Walk-in Customer', 130.00, 'cash', 130.00, 0.00, 'Admin User', '2025-11-14', '2025-11-14 08:23:43'),
(5, NULL, 'Walk-in Customer', 8000.00, 'cash', 8000.00, 0.00, 'Admin User', '2025-11-14', '2025-11-14 08:32:09'),
(6, NULL, 'Walk-in Customer', 3200.00, 'cash', 3200.00, 0.00, 'Admin User', '2025-11-14', '2025-11-14 08:35:53'),
(7, 2, 'new', 825.00, 'mpesa', 825.00, 0.00, 'Sales Clerk', '2025-11-15', '2025-11-15 08:50:55'),
(8, 2, 'new', 12090.00, 'mpesa', 13000.00, -910.00, 'Sales Clerk', '2025-11-15', '2025-11-15 08:52:24'),
(9, NULL, 'Walk-in Customer', 6045.00, 'cash', 6500.00, -455.00, 'Sales Clerk', '2025-11-15', '2025-11-15 08:53:27'),
(10, 4, 'm', 2816.00, 'cash', 2816.00, 0.00, 'admin', '2025-11-16', '2025-11-16 04:54:15'),
(11, 1, 'test customer', 21450.00, 'cash', 22000.00, -550.00, 'admin', '2025-11-16', '2025-11-16 15:41:57');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`) VALUES
(1, 1, 1, 'lato fresh', 9, 65.00, 585.00, '2025-11-14 04:37:28'),
(2, 2, 1, 'lato fresh', 2, 65.00, 130.00, '2025-11-14 04:43:51'),
(3, 3, 2, 'lato fresh', 5, 65.00, 325.00, '2025-11-14 08:11:23'),
(4, 4, 2, 'lato fresh', 2, 65.00, 130.00, '2025-11-14 08:23:43'),
(5, 5, 3, 'lato premium', 50, 160.00, 8000.00, '2025-11-14 08:32:09'),
(6, 6, 3, 'lato premium', 20, 160.00, 3200.00, '2025-11-14 08:35:53'),
(7, 7, 9, 'fresh milk', 15, 55.00, 825.00, '2025-11-15 08:50:55'),
(8, 8, 8, 'lato fresh', 186, 65.00, 12090.00, '2025-11-15 08:52:24'),
(9, 9, 7, 'lato fresh', 93, 65.00, 6045.00, '2025-11-15 08:53:27'),
(10, 10, 18, 'grinder', 11, 256.00, 2816.00, '2025-11-16 04:54:15'),
(11, 11, 19, 'yeyo', 429, 50.00, 21450.00, '2025-11-16 15:41:57');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('Milk','Yogurt','Cheese','Butter','Cream') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `alert_level` int(11) DEFAULT 10,
  `supplier` varchar(100) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `date_added` date NOT NULL,
  `added_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `name`, `category`, `quantity`, `price`, `expiry_date`, `location`, `alert_level`, `supplier`, `batch_number`, `image_path`, `date_added`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 'lato fresh', 'Milk', 0, 65.00, '2025-11-16', 'Main warehouse', 10, 'Lato supplier', '', 'uploads/products/b5be3f33-2660-4abf-866d-609ca0fa18d5.jpg', '2025-11-14', 'admin', '2025-11-14 04:24:33', '2025-11-15 20:28:28'),
(2, 'lato fresh', 'Milk', 4, 65.00, '2025-11-16', 'Main warehouse', 10, 'Lato supplier', '', 'uploads/products/illustration-milk-carton-cup-260nw-2422290685.webp', '2025-11-14', 'admin', '2025-11-14 04:31:20', '2025-11-15 20:29:52'),
(3, 'lato premium', 'Yogurt', 180, 160.00, '2026-03-01', 'Main Warehouse', 20, 'main', 'batch 002', NULL, '2025-11-14', 'admin', '2025-11-14 08:31:32', '2025-11-14 08:35:53'),
(4, 'lato vanilla', 'Yogurt', 600, 170.00, '2026-02-13', 'Main Warehouse', 10, 'lato supplier', 'batch 003', NULL, '2025-11-14', 'admin', '2025-11-14 08:40:57', '2025-11-14 08:40:57'),
(5, 'lato vanilla', 'Yogurt', 600, 170.00, '2026-02-13', 'Main Warehouse', 10, 'lato supplier', 'batch 003', NULL, '2025-11-14', 'admin', '2025-11-14 18:14:34', '2025-11-14 18:14:34'),
(6, 'lato premium', 'Yogurt', 600, 160.00, '2025-12-27', 'Main Warehouse', 10, 'lato supplier', 'batch 003', NULL, '2025-11-15', 'admin', '2025-11-15 07:35:56', '2025-11-15 07:35:56'),
(7, 'lato fresh', 'Milk', 57, 65.00, '2026-01-12', 'Main Warehouse', 10, 'main', 'batch 004', NULL, '2025-11-15', 'admin', '2025-11-15 07:56:47', '2025-11-15 08:53:27'),
(8, 'lato fresh', 'Milk', 14, 65.00, '2026-03-01', 'Main Warehouse', 10, 'main', 'MIL-20251115-3ZEF', NULL, '2025-11-15', 'admin', '2025-11-15 08:28:44', '2025-11-15 08:52:24'),
(9, 'fresh milk', 'Milk', 5, 55.00, '2025-12-25', 'Main Warehouse', 10, 'lato supplier', 'MIL-20251115-9KGK', NULL, '2025-11-15', 'admin', '2025-11-15 08:48:05', '2025-11-15 08:50:55'),
(10, 'lato yogurt', 'Yogurt', 12, 170.00, '2026-02-17', 'Main Warehouse', 10, 'lato supplier', 'YOG-20251115-QD9D', NULL, '2025-11-15', 'admin', '2025-11-15 09:01:42', '2025-11-15 09:01:42'),
(11, 'lato fresh', 'Milk', 17, 65.00, '2026-03-12', 'Main Warehouse', 10, 'lato supplier', 'MIL-20251115-PDO5', NULL, '2025-11-15', 'admin', '2025-11-15 09:04:12', '2025-11-15 09:04:12'),
(12, 'fresh milk', 'Milk', 24, 55.00, '2025-12-31', 'Main Warehouse', 10, 'lato supplier', 'MIL-20251115-NWWW', NULL, '2025-11-15', 'admin', '2025-11-15 09:07:07', '2025-11-15 09:07:07'),
(13, 'lato fresh', 'Milk', 177, 65.00, '2026-01-20', 'Main Warehouse', 10, 'lato supplier', 'MIL-20251115-XLOK', NULL, '2025-11-15', 'admin', '2025-11-15 20:05:09', '2025-11-15 20:05:09'),
(14, 'fresh milk', 'Milk', 167, 55.00, '2026-01-09', 'Main Warehouse', 10, 'lato supplier', 'MIL-20251115-Q0MA', NULL, '2025-11-15', 'admin', '2025-11-15 20:08:04', '2025-11-15 20:08:04'),
(15, 'lato fresh', 'Milk', 456, 55.00, '2026-02-12', 'Main Warehouse', 10, 'main', 'MIL-20251115-TXNG', NULL, '2025-11-15', 'admin', '2025-11-15 20:31:17', '2025-11-15 20:31:17'),
(16, 'Test Upload Product', 'Milk', 10, 100.00, '2025-12-15', 'Test', 5, 'Test', 'TEST-1763239215', 'uploads/products/test_1763239215.webp', '2025-11-15', 'admin', '2025-11-15 20:40:15', '2025-11-15 20:40:15'),
(17, 'fresh milk', 'Milk', 356, 65.00, '2026-02-12', 'Main Warehouse', 10, 'main', 'MIL-20251115-I6AX', 'uploads/products/product_1763239305_6918e58990aff.jpg', '2025-11-15', 'admin', '2025-11-15 20:41:45', '2025-11-15 20:41:45'),
(18, 'grinder', 'Yogurt', 4, 256.00, '2026-12-28', 'Main Warehouse', 10, 'main', 'YOG-20251116-2HCI', 'uploads/products/product_1763268762_6919589a64f36.png', '2025-11-16', 'admin', '2025-11-16 04:52:42', '2025-11-16 04:54:15'),
(19, 'yeyo', 'Milk', 71, 50.00, '2026-12-06', 'Main Warehouse', 10, 'main', 'MIL-20251116-1X3I', 'uploads/products/product_1763307352_6919ef58d094b.jpeg', '2025-11-16', 'admin', '2025-11-16 15:35:53', '2025-11-16 15:41:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('admin','sales','warehouse','manager') NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `profile_image`, `role`, `active`, `last_login`, `created_at`, `updated_at`) VALUES
(17, 'admin', '$2y$10$KccykJcC.SYxTMkXfTKmzeQyGRY6/AXZ5byoKN2et4741nI1TojjC', 'admin', 'jerald@latofresh.com', 'uploads/profiles/user_17_1763264070.jpg', 'admin', 1, '2025-11-16 18:32:51', '2025-11-12 15:45:07', '2025-11-16 15:32:51'),
(18, 'sales', '$2y$10$zyZBBC3mtq.TMPxVd2Wkqu93X6/HdRcs9PAUep87oppR601uGY30.', 'Sales Clerk', 'sales@latofresh.com', NULL, 'sales', 1, '2025-11-16 08:24:27', '2025-11-12 15:45:07', '2025-11-16 05:24:27'),
(19, 'warehouse', '$2y$10$zyIuBMzATnoJkG0cQyauRuXGAEm1T72figMqZrlCoQkCjL3OEej5a', 'Warehouse Staff', 'warehouse@latofresh.com', NULL, 'warehouse', 1, '2025-11-16 08:27:18', '2025-11-12 15:45:07', '2025-11-16 05:27:18'),
(20, 'manager', '$2y$10$8ojEwUmUeV8xFG3DhsMdiOj7M6Uw7AMglRb4QovBrEKoz9XPQWhYG', 'Manager User', 'manager@latofresh.com', NULL, 'manager', 1, '2025-11-16 08:25:09', '2025-11-12 15:45:07', '2025-11-16 05:25:09'),
(21, 'new', '$2y$10$hO3Y82oeyAnEi3QloUbtx.eQ8kC6slSI./Z5.Dz1dhkr9uj/Oj4sm', 'new', 'new@latofreshdairy.com', NULL, 'sales', 1, '2025-11-12 18:50:21', '2025-11-12 15:47:59', '2025-11-12 15:50:21'),
(22, 'test', '$2y$10$7REby9Mbv/WPCA1bfTUSbuFDeDAKg5tWANKEwoFgzQXp5IQzJk1Mq', 'test', 'test@latofresh.com', NULL, 'warehouse', 1, '2025-11-14 07:39:00', '2025-11-14 04:33:22', '2025-11-14 04:39:00'),
(26, 'w', '$2y$10$VzRdUzK8zJWE5n8Iibl.JeIVSpfiEwXpC/hn6bhuP02vBMKctreDC', 'w', 'w@latofreshdairy.com', NULL, 'manager', 1, NULL, '2025-11-16 03:10:55', '2025-11-16 03:10:55'),
(27, 'mm', '$2y$10$mGErFow96i58L49ZBRKECudzPB5tuT0jof09b7miapHgRoCSeAIJ.', 'mm', 'mm@esample.com', NULL, 'sales', 1, NULL, '2025-11-16 04:31:15', '2025-11-16 04:31:15'),
(28, 'administrator', '$2y$10$3LXx4Srzu5z4qonvG/Y9cO0Xw9C/X66Uy70NZtFi/fvfJzpTSKac6', 'administrator', 'administrator@latofresh.com', NULL, 'admin', 1, NULL, '2025-11-16 04:37:12', '2025-11-16 04:37:12'),
(29, 'Ivy', '$2y$10$T42kmMaMgVfAFevWDr8aJO0nk35FFhYP1.0ZUcP0zeRs4.dpTKGlS', 'Ivyeen', 'ivywalubi@gmail.com', NULL, 'manager', 1, '2025-11-16 18:31:58', '2025-11-16 15:30:47', '2025-11-16 15:31:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_user` (`user`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `backup_history`
--
ALTER TABLE `backup_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_date` (`sale_date`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale` (`sale_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_expiry` (`expiry_date`),
  ADD KEY `idx_quantity` (`quantity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `backup_history`
--
ALTER TABLE `backup_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
