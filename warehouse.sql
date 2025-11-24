-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 24, 2025 at 08:21 PM
-- Server version: 8.0.35
-- PHP Version: 8.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warehouse`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:00:03'),
(2, 1, 'UPDATE', 'user', 'Updated user:  (Vidha Kurniawati)', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:05:05'),
(3, 1, 'UPDATE', 'user', 'Updated user:  (Vidha Kurniawati)', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:05:14'),
(4, 1, 'UPDATE', 'user', 'Updated user: vidha (Vidha Kurniawati)', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:21:35'),
(5, 1, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:21:39'),
(6, 2, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:21:45'),
(7, 2, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:22:28'),
(8, 1, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 02:22:33'),
(9, 1, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 04:14:47'),
(10, 2, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 04:14:53'),
(11, 2, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 04:15:14'),
(12, 1, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 04:15:18'),
(13, 1, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-24 04:15:43'),
(14, 1, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-24 04:16:30'),
(15, 2, 'LOGIN', 'auth', 'User logged in successfully', '157.85.212.14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-24 04:16:35'),
(16, 2, 'LOGOUT', 'auth', 'User logged out', '157.85.212.14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-24 04:17:18'),
(17, 1, 'LOGIN', 'auth', 'User logged in successfully', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 09:42:48'),
(18, 1, 'CREATE', 'stock_out', 'Created stock out transaction: SO202511243536', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 09:56:04'),
(19, 1, 'CREATE', 'user', 'Created user: baiiput (Bayu Putra Pratama) with role: admin', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:47:54'),
(20, 1, 'LOGOUT', 'auth', 'User logged out', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:47:59'),
(21, 3, 'LOGIN', 'auth', 'User logged in successfully', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:48:03'),
(22, 3, 'UPDATE', 'user', 'Updated user: admin (Administrator)', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:48:39'),
(23, 3, 'UPDATE', 'user', 'Updated user: baiiput (Bayu Putra Pratama)', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:48:56'),
(24, 3, 'UPDATE', 'user', 'Updated user: baiiput (Bayu Putra Pratama)', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:49:09'),
(25, 3, 'LOGOUT', 'auth', 'User logged out', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:49:11'),
(26, 3, 'LOGIN', 'auth', 'User logged in successfully', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 10:49:24'),
(27, 3, 'LOGIN', 'auth', 'User logged in successfully', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 11:24:15'),
(28, 3, 'UPDATE', 'user', 'Updated user: admin (Administrator)', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 11:24:53'),
(29, 3, 'UPDATE', 'user', 'Updated user: admin (Administrator)', '182.2.51.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-24 11:25:01');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `address` text,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `address`, `contact_person`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'Outlet Unnes', 'Jalan Taman Siswa', 'Nor Indah Pratiwi', '085640443131', '2025-11-12 16:25:22', '2025-11-16 19:03:47');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Alat', 'Peralatan dan tools', '2025-11-12 10:47:11'),
(2, 'Bahan Baku', 'Bahan baku produksi', '2025-11-12 10:47:11'),
(3, 'Packaging', 'Bahan kemasan', '2025-11-12 10:47:11'),
(4, 'Consumables', 'Barang habis pakai', '2025-11-12 10:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `transaction_id` int NOT NULL,
  `transaction_date` datetime NOT NULL,
  `transaction_type` enum('modal','stock_in','stock_out','adjustment') NOT NULL,
  `reference_code` varchar(50) DEFAULT NULL,
  `description` text,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `balance_after` decimal(15,2) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `financial_transactions`
--

INSERT INTO `financial_transactions` (`transaction_id`, `transaction_date`, `transaction_type`, `reference_code`, `description`, `debit`, `credit`, `balance_after`, `created_by`, `created_at`) VALUES
(2, '2025-11-17 00:36:07', 'modal', 'MODAL-20251117003607', 'Modal Warehouse', 20000000.00, 0.00, 20000000.00, 1, '2025-11-16 17:36:07'),
(4, '2025-11-17 03:55:00', 'stock_in', 'SI202511174439', 'Pembelian dari supplier (Updated) - SI202511174439', 0.00, 7560000.00, 12440000.00, 1, '2025-11-16 17:37:13'),
(7, '2025-11-21 05:02:00', 'stock_out', 'SO202511229631', 'Distribusi ke cabang - SO202511229631', 3360000.00, 0.00, 15800000.00, 1, '2025-11-21 22:02:39'),
(8, '2025-11-19 07:11:00', 'stock_out', 'SO202511221610', 'Distribusi ke cabang - SO202511221610', 84000.00, 0.00, 15884000.00, 1, '2025-11-22 00:12:19'),
(9, '2025-11-23 16:00:00', 'stock_out', 'SO202511243536', 'Distribusi ke cabang - SO202511243536', 42000.00, 0.00, 15926000.00, 1, '2025-11-24 09:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category_id` int DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `min_stock` int DEFAULT '0',
  `current_stock` decimal(15,2) DEFAULT '0.00',
  `average_cost` decimal(15,2) DEFAULT '0.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `item_code`, `item_name`, `category_id`, `unit`, `min_stock`, `current_stock`, `average_cost`, `description`, `created_at`, `updated_at`) VALUES
(2, '001', 'Dimsum Wortel / Jamur', 4, 'pack', 40, 180.00, 42000.00, 'Dimsum Wortel / Jamur', '2025-11-15 22:18:04', '2025-11-16 19:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment`
--

CREATE TABLE `stock_adjustment` (
  `adjustment_id` int NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `adjustment_date` datetime NOT NULL,
  `item_id` int NOT NULL,
  `warehouse_id` int DEFAULT NULL,
  `old_stock` decimal(15,2) NOT NULL,
  `new_stock` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) NOT NULL,
  `reason` text NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `stock_in_id` int NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `supplier_id` int NOT NULL,
  `warehouse_id` int DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_in`
--

INSERT INTO `stock_in` (`stock_in_id`, `transaction_code`, `transaction_date`, `supplier_id`, `warehouse_id`, `total_amount`, `notes`, `created_by`, `created_at`) VALUES
(2, 'SI202511174439', '2025-11-17 03:55:00', 1, 1, 7560000.00, '', 1, '2025-11-16 17:36:42');

-- --------------------------------------------------------

--
-- Table structure for table `stock_in_detail`
--

CREATE TABLE `stock_in_detail` (
  `detail_id` int NOT NULL,
  `stock_in_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_in_detail`
--

INSERT INTO `stock_in_detail` (`detail_id`, `stock_in_id`, `item_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(10, 2, 2, 180.00, 42000.00, 7560000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_out`
--

CREATE TABLE `stock_out` (
  `stock_out_id` int NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `branch_id` int NOT NULL,
  `warehouse_id` int DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_out`
--

INSERT INTO `stock_out` (`stock_out_id`, `transaction_code`, `transaction_date`, `branch_id`, `warehouse_id`, `total_amount`, `notes`, `created_by`, `created_at`) VALUES
(2, 'SO202511229631', '2025-11-21 05:02:00', 1, 1, 3360000.00, '', 1, '2025-11-21 22:02:39'),
(3, 'SO202511221610', '2025-11-19 07:11:00', 1, 2, 84000.00, '', 1, '2025-11-22 00:12:19'),
(4, 'SO202511243536', '2025-11-23 16:00:00', 1, 1, 42000.00, '', 1, '2025-11-24 09:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `stock_out_detail`
--

CREATE TABLE `stock_out_detail` (
  `detail_id` int NOT NULL,
  `stock_out_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_out_detail`
--

INSERT INTO `stock_out_detail` (`detail_id`, `stock_out_id`, `item_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(7, 2, 2, 80.00, 42000.00, 3360000.00),
(8, 3, 2, 2.00, 42000.00, 84000.00),
(9, 4, 2, 1.00, 42000.00, 42000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `transfer_id` int NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `transfer_date` datetime NOT NULL,
  `from_warehouse_id` int NOT NULL,
  `to_warehouse_id` int NOT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_detail`
--

CREATE TABLE `stock_transfer_detail` (
  `transfer_detail_id` int NOT NULL,
  `transfer_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Dimsum Koe', 'Endang Suyatmi', '0888888888', '', 'Purwodadi', '2025-11-12 12:37:58', '2025-11-12 16:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','staff_warehouse','staff_keuangan','cabang') NOT NULL,
  `cabang_id` int DEFAULT NULL,
  `warehouse_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `cabang_id`, `warehouse_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$jYEDh1u.J9rz5IdHInFBN.wpXLwUrHTsRmZjcVs1XWMi.bNB7dmaq', 'Administrator', 'admin@warehouse.com', '', 'admin', NULL, NULL, 0, '2025-11-12 10:47:11', '2025-11-24 11:25:01'),
(2, 'vidha', '$2y$10$yKa2Um6J.ZFx8mTArvrcyuF827pWsHAQDn8zRESmJ4MAvbDudx8sC', 'Vidha Kurniawati', 'vidhakurnia21@gmail.com', '00000', 'manager', NULL, NULL, 1, '2025-11-14 23:54:07', '2025-11-24 02:21:35'),
(3, 'baiiput', '$2y$10$QmyNW3U.r24QGAPFxkFkfeT3pUffzyjP5lpLo0jV9DoNxQhbcAPD2', 'Bayu Putra Pratama', 'baiiput@gmail.com', '089668713135', 'admin', NULL, NULL, 1, '2025-11-24 10:47:54', '2025-11-24 10:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `warehouse_id` int NOT NULL,
  `warehouse_code` varchar(20) NOT NULL,
  `warehouse_name` varchar(100) NOT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`warehouse_id`, `warehouse_code`, `warehouse_name`, `address`, `phone`, `is_active`, `created_at`) VALUES
(1, 'WH-001', 'Warehouse Utama', 'Alamat Warehouse Utama', NULL, 1, '2025-11-22 00:00:00'),
(2, 'WH-002', 'Warehouse Bongsari', 'Jalan Taman Condrokusumo VII No 53', '085640443131', 1, '2025-11-21 21:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_balance`
--

CREATE TABLE `warehouse_balance` (
  `balance_id` int NOT NULL,
  `balance_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouse_balance`
--

INSERT INTO `warehouse_balance` (`balance_id`, `balance_amount`, `last_updated`) VALUES
(1, 15926000.00, '2025-11-24 09:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_items`
--

CREATE TABLE `warehouse_items` (
  `warehouse_item_id` int NOT NULL,
  `warehouse_id` int NOT NULL,
  `item_id` int NOT NULL,
  `current_stock` decimal(10,2) DEFAULT '0.00',
  `average_cost` decimal(15,2) DEFAULT '0.00',
  `min_stock` decimal(10,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouse_items`
--

INSERT INTO `warehouse_items` (`warehouse_item_id`, `warehouse_id`, `item_id`, `current_stock`, `average_cost`, `min_stock`, `last_updated`) VALUES
(1, 1, 2, 99.00, 42000.00, 40.00, '2025-11-24 09:56:04'),
(2, 2, 2, 0.00, 42000.00, 40.00, '2025-11-24 09:51:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_financial_date` (`transaction_date`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_items_code` (`item_code`);

--
-- Indexes for table `stock_adjustment`
--
ALTER TABLE `stock_adjustment`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_adjustment_date` (`adjustment_date`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`stock_in_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_stock_in_date` (`transaction_date`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- Indexes for table `stock_in_detail`
--
ALTER TABLE `stock_in_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `stock_in_id` (`stock_in_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD PRIMARY KEY (`stock_out_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_stock_out_date` (`transaction_date`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- Indexes for table `stock_out_detail`
--
ALTER TABLE `stock_out_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `stock_out_id` (`stock_out_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`transfer_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `idx_from_warehouse` (`from_warehouse_id`),
  ADD KEY `idx_to_warehouse` (`to_warehouse_id`),
  ADD KEY `idx_transfer_date` (`transfer_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_transfer_detail`
--
ALTER TABLE `stock_transfer_detail`
  ADD PRIMARY KEY (`transfer_detail_id`),
  ADD KEY `idx_transfer` (`transfer_id`),
  ADD KEY `idx_item` (`item_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`warehouse_id`),
  ADD UNIQUE KEY `warehouse_code` (`warehouse_code`),
  ADD KEY `idx_warehouse_code` (`warehouse_code`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `warehouse_balance`
--
ALTER TABLE `warehouse_balance`
  ADD PRIMARY KEY (`balance_id`);

--
-- Indexes for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD PRIMARY KEY (`warehouse_item_id`),
  ADD UNIQUE KEY `unique_warehouse_item` (`warehouse_id`,`item_id`),
  ADD KEY `idx_warehouse` (`warehouse_id`),
  ADD KEY `idx_item` (`item_id`),
  ADD KEY `idx_low_stock` (`warehouse_id`,`current_stock`,`min_stock`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `transaction_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_adjustment`
--
ALTER TABLE `stock_adjustment`
  MODIFY `adjustment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `stock_in_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_in_detail`
--
ALTER TABLE `stock_in_detail`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_out`
--
ALTER TABLE `stock_out`
  MODIFY `stock_out_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_out_detail`
--
ALTER TABLE `stock_out_detail`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `transfer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_transfer_detail`
--
ALTER TABLE `stock_transfer_detail`
  MODIFY `transfer_detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `warehouse_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `warehouse_balance`
--
ALTER TABLE `warehouse_balance`
  MODIFY `balance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  MODIFY `warehouse_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_adjustment`
--
ALTER TABLE `stock_adjustment`
  ADD CONSTRAINT `stock_adjustment_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  ADD CONSTRAINT `stock_adjustment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD CONSTRAINT `stock_in_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `stock_in_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `stock_in_detail`
--
ALTER TABLE `stock_in_detail`
  ADD CONSTRAINT `stock_in_detail_ibfk_1` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in` (`stock_in_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_in_detail_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`);

--
-- Constraints for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD CONSTRAINT `stock_out_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `stock_out_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `stock_out_detail`
--
ALTER TABLE `stock_out_detail`
  ADD CONSTRAINT `stock_out_detail_ibfk_1` FOREIGN KEY (`stock_out_id`) REFERENCES `stock_out` (`stock_out_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_out_detail_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`);

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `stock_transfer_detail`
--
ALTER TABLE `stock_transfer_detail`
  ADD CONSTRAINT `stock_transfer_detail_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`transfer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
