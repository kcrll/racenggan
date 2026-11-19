-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 05:25 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `racel_sash`
--

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `description` varchar(200) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Materials',
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `description`, `category`, `amount`, `expense_date`, `created_by`, `created_at`) VALUES
(2, 'Glass Panel', 'Materials', 1300.00, '2026-11-13', 1, '2026-11-13 05:28:30');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_code` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `item_code`, `name`, `unit`, `quantity`, `reorder_level`, `unit_cost`, `updated_at`) VALUES
(1, 'MHG-001', 'Mahogany Wood', 'board ft', 300.00, 100.00, 95.00, '2026-11-13 06:20:33'),
(2, 'NRA-001', 'Narra Wood', 'board ft', 250.00, 80.00, 140.00, '2026-11-13 03:44:23'),
(3, 'PIN-001', 'Pine Wood', 'board ft', 600.00, 150.00, 45.00, '2026-11-13 03:44:23'),
(4, 'NAL-001', 'Nails (box)', 'box', 37.00, 10.00, 120.00, '2026-11-13 06:20:28'),
(5, 'HNG-001', 'Hinges', 'pcs', 150.00, 40.00, 35.00, '2026-11-13 03:44:23'),
(6, 'VRN-001', 'Varnish', 'liter', 30.00, 10.00, 320.00, '2026-11-13 03:44:23'),
(7, 'GLU-001', 'Wood Glue', 'liter', 20.00, 6.00, 210.00, '2026-11-13 03:44:23'),
(8, 'SND-001', 'Sandpaper Sheets', 'pcs', 200.00, 50.00, 8.00, '2026-11-13 03:44:23'),
(9, 'GLS-001', 'Glass Panel', 'pcs', 50.00, 15.00, 260.00, '2026-11-15 16:20:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_no` int(11) DEFAULT NULL,
  `order_code` varchar(20) DEFAULT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `width_cm` decimal(8,2) DEFAULT NULL,
  `height_cm` decimal(8,2) DEFAULT NULL,
  `wood_type` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `agreed_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','in_production','finishing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `est_days` int(11) DEFAULT NULL,
  `est_completion` date DEFAULT NULL,
  `admin_seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `order_code`, `customer_id`, `product_id`, `manager_id`, `quantity`, `width_cm`, `height_cm`, `wood_type`, `notes`, `agreed_price`, `status`, `progress`, `est_days`, `est_completion`, `admin_seen`, `created_at`, `confirmed_at`, `completed_at`) VALUES
(1, 1001, 'ORD-1001', 2, 5, 1, 1, NULL, NULL, NULL, NULL, 8500.00, 'completed', 100, 20, '2026-12-03', 1, '2026-11-13 04:23:01', '2026-11-13 12:23:03', '2026-11-13 06:29:05'),
(2, 1002, 'ORD-1002', 13, 4, 1, 1, NULL, NULL, NULL, NULL, 2800.00, 'in_production', 10, 7, '2026-11-20', 1, '2026-11-13 05:30:27', '2026-11-13 13:31:34', NULL),
(3, 1003, 'ORD-1003', 2, 2, 1, 1, NULL, NULL, NULL, NULL, 3800.00, 'confirmed', 0, 8, '2026-11-21', 1, '2026-11-13 05:30:47', '2026-11-13 13:31:11', NULL),
(4, 1004, 'ORD-1004', 13, 6, 1, 1, 120.00, 80.00, NULL, NULL, 5000.00, 'ready', 100, 18, '2026-12-01', 1, '2026-11-13 06:17:57', '2026-11-13 14:19:11', NULL),
(5, 1005, 'ORD-1005', 2, 2, 1, 1, NULL, NULL, NULL, NULL, 3800.00, 'pending', 0, 8, '2026-11-21', 1, '2026-11-13 06:18:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_assignments`
--

CREATE TABLE `order_assignments` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_assignments`
--

INSERT INTO `order_assignments` (`order_id`, `employee_id`, `assigned_at`) VALUES
(1, 3, '2026-11-13 05:29:05'),
(2, 3, '2026-11-13 05:31:42'),
(2, 5, '2026-11-13 05:31:42'),
(2, 10, '2026-11-13 05:31:42'),
(3, 4, '2026-11-13 05:31:24'),
(3, 9, '2026-11-13 05:31:24'),
(3, 11, '2026-11-13 05:31:24'),
(4, 3, '2026-11-13 06:19:28'),
(4, 10, '2026-11-13 06:19:28'),
(4, 11, '2026-11-13 06:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `order_materials`
--

CREATE TABLE `order_materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `quantity_used` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_updates`
--

CREATE TABLE `order_updates` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL,
  `message` varchar(500) NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_updates`
--

INSERT INTO `order_updates` (`id`, `order_id`, `status`, `message`, `created_by`, `created_at`) VALUES
(1, 1, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', 1, '2026-11-13 04:23:01'),
(2, 1, 'confirmed', 'Your order has been confirmed. Estimated completion in 20 days.', 1, '2026-11-13 04:23:03'),
(3, 1, 'confirmed', 'Confirmed · 20% complete · 1 craftsmen assigned · estimated 20 days remaining.', 1, '2026-11-13 04:23:44'),
(4, 1, 'in_production', 'Production update: In Production (45% complete).', 3, '2026-11-13 05:20:47'),
(5, 1, 'in_production', 'Payment of ₱8,500.00 received. Thank you!', 1, '2026-11-13 05:28:56'),
(6, 1, 'completed', 'Completed · 100% complete · 1 craftsmen assigned · estimated 20 days remaining.', 1, '2026-11-13 05:29:05'),
(7, 2, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', 1, '2026-11-13 05:30:27'),
(8, 3, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', 1, '2026-11-13 05:30:47'),
(9, 3, 'confirmed', 'Your order has been confirmed. Estimated completion in 8 days.', 1, '2026-11-13 05:31:11'),
(10, 3, 'confirmed', 'Confirmed · 0% complete · 3 craftsmen assigned · estimated 8 days remaining.', 1, '2026-11-13 05:31:24'),
(11, 2, 'confirmed', 'Your order has been confirmed. Estimated completion in 7 days.', 1, '2026-11-13 05:31:34'),
(12, 2, 'confirmed', 'Confirmed · 0% complete · 3 craftsmen assigned · estimated 7 days remaining.', 1, '2026-11-13 05:31:42'),
(13, 2, 'in_production', 'Production update: In Production (10% complete).', 3, '2026-11-13 05:51:20'),
(14, 4, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', 1, '2026-11-13 06:17:57'),
(15, 5, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', 1, '2026-11-13 06:18:44'),
(16, 4, 'confirmed', 'Your order has been confirmed. Estimated completion in 18 days.', 1, '2026-11-13 06:19:11'),
(17, 4, 'ready', 'Ready for Pickup · 0% complete · 0 craftsmen assigned · estimated 18 days remaining.', 1, '2026-11-13 06:19:17'),
(18, 4, 'ready', 'Ready for Pickup · 100% complete · 3 craftsmen assigned · estimated 18 days remaining.', 1, '2026-11-13 06:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('down_payment','partial','full') NOT NULL DEFAULT 'partial',
  `method` varchar(30) NOT NULL DEFAULT 'Cash',
  `note` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `type`, `method`, `note`, `paid_at`, `recorded_by`) VALUES
(1, 1, 8500.00, 'full', 'Cash', '', '2026-11-13 05:28:56', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `category` varchar(60) NOT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `default_days` int(11) NOT NULL DEFAULT 7,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `base_price`, `default_days`, `description`, `is_active`) VALUES
(1, 'Wooden Window Sash', 'Windows', 4500.00, 10, 'Custom-fit sliding or casement sash in solid wood.', 1),
(2, 'Louver Window', 'Windows', 3800.00, 8, 'Adjustable wooden louver panels for airflow and privacy.', 1),
(3, 'Wooden Door', 'Doors', 6500.00, 14, 'Solid panel or flush door finished to your specification.', 1),
(4, 'Door Frame (Jamb)', 'Doors', 2800.00, 7, 'Measured door jamb and trim set.', 1),
(5, 'Wooden Cabinet', 'Cabinets', 8500.00, 20, 'Built-in or standing cabinets with custom dimensions.', 1),
(6, 'Custom Wood Furniture', 'Furniture', 5000.00, 18, 'Tables, chairs, and shelving made to your design.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_tracking`
--

CREATE TABLE `product_tracking` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `production_status` varchar(30) NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_tracking`
--

INSERT INTO `product_tracking` (`id`, `order_id`, `production_status`, `date_updated`, `remarks`) VALUES
(1, 1, 'pending', '2026-11-13 04:23:01', 'Order recorded by the RACEL manager. Awaiting confirmation.'),
(2, 1, 'confirmed', '2026-11-13 04:23:03', 'Your order has been confirmed. Estimated completion in 20 days.'),
(3, 1, 'confirmed', '2026-11-13 04:23:44', 'Confirmed · 20% complete · 1 craftsmen assigned · estimated 20 days remaining.'),
(4, 1, 'in_production', '2026-11-13 05:20:47', 'Production update: In Production (45% complete).'),
(5, 1, 'in_production', '2026-11-13 05:28:56', 'Payment of ₱8,500.00 received. Thank you!'),
(6, 1, 'completed', '2026-11-13 05:29:05', 'Completed · 100% complete · 1 craftsmen assigned · estimated 20 days remaining.'),
(7, 2, 'pending', '2026-11-13 05:30:27', 'Order recorded by the RACEL manager. Awaiting confirmation.'),
(8, 3, 'pending', '2026-11-13 05:30:47', 'Order recorded by the RACEL manager. Awaiting confirmation.'),
(9, 3, 'confirmed', '2026-11-13 05:31:11', 'Your order has been confirmed. Estimated completion in 8 days.'),
(10, 3, 'confirmed', '2026-11-13 05:31:24', 'Confirmed · 0% complete · 3 craftsmen assigned · estimated 8 days remaining.'),
(11, 2, 'confirmed', '2026-11-13 05:31:34', 'Your order has been confirmed. Estimated completion in 7 days.'),
(12, 2, 'confirmed', '2026-11-13 05:31:42', 'Confirmed · 0% complete · 3 craftsmen assigned · estimated 7 days remaining.'),
(13, 2, 'in_production', '2026-11-13 05:51:20', 'Production update: In Production (10% complete).'),
(14, 4, 'pending', '2026-11-13 06:17:57', 'Order recorded by the RACEL manager. Awaiting confirmation.'),
(15, 5, 'pending', '2026-11-13 06:18:44', 'Order recorded by the RACEL manager. Awaiting confirmation.'),
(16, 4, 'confirmed', '2026-11-13 06:19:11', 'Your order has been confirmed. Estimated completion in 18 days.'),
(17, 4, 'ready', '2026-11-13 06:19:17', 'Ready for Pickup · 0% complete · 0 craftsmen assigned · estimated 18 days remaining.'),
(18, 4, 'ready', '2026-11-13 06:19:28', 'Ready for Pickup · 100% complete · 3 craftsmen assigned · estimated 18 days remaining.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employee','customer') NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `skills` varchar(255) DEFAULT NULL,
  `experience` varchar(120) DEFAULT NULL,
  `assigned_responsibilities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `is_active`, `skills`, `experience`, `assigned_responsibilities`, `created_at`) VALUES
(1, 'Zugie Martinez', 'martinez@gmail.com', NULL, '$2y$12$hbtDG8UQLgjhlGajK9nsa.YHIquJVzuhzycTUF1QhltVXhPOkwORq', 'admin', 1, NULL, NULL, NULL, '2026-11-13 03:45:29'),
(2, 'Jesusa Siapno', 'jesusa@gmail.com', '0943 534 2552', '$2y$12$1r4WNBqGAp9suqZ7Cs2Qsef6Mg7XduMhFKloZiiF9Z9VRzKxEtHHa', 'customer', 1, NULL, NULL, NULL, '2026-11-13 04:13:12'),
(3, 'Melissa Kalbo', 'melissa@gmail.com', '0943 534 2552', '$2y$12$fk4/sm3Z2.MYwPdox0/rxufV6yKZ6B12HHyPPkt3TyDPOiWJVOdgm', 'employee', 1, NULL, NULL, NULL, '2026-11-13 04:23:33'),
(4, 'Joyceanne Torio', 'ceanne@gmail.com', '0987 654 3210', '$2y$12$5LrODFV4VQQQjCp9jHIMbuL2EcuGDjohDFgfb10YJANTITAA2RdPK', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:22:34'),
(5, 'Raul Jr.', 'raul@gmail.com', '0912 345 6789', '$2y$12$5wDgXiKGKc6.MNZtFs4ys.CknEOiIGq7J/w68elXfy0jUoK3vjADO', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:23:56'),
(6, 'poging employee', 'pogi@gmail.com', '0945 345 3453', '$2y$12$eDw51M0ovkoF6THI1q8f8eHn/LVTXOtwwYeihZhrDfFibIT5B21LK', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:24:21'),
(7, 'magandang employee', 'maganda@gmail.com', '0986 545 6546', '$2y$12$AJWhFOiHrzms38456GCzYeiv2YqAkOnV1.JglwoHbspgy28ZQ.gDi', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:24:46'),
(8, 'pangit na employee', 'pangit@gmail.com', '0985 545 6563', '$2y$12$yLn9Gykg6APlXGd8XHD8P.qToRBGaF5DTdS.CDe/cJlDNIJBwU4VW', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:25:06'),
(9, 'Juan Dela Cruz', 'juan@gmail.com', '0985 654 7676', '$2y$12$Um/lQYGa/vXHOqVsfHreHOwexgvd4wyNf0PeMOzHtufCmor4rRWmW', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:25:34'),
(10, 'Penpen Soy', 'penpen@gmail.com', '0987 878 9898', '$2y$12$gWhqrNF9POh5iMrpwnt2quG2Yjeaiv52TtKfO/50mcLCkWMUVYyJ.', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:26:05'),
(11, 'Gerald Soy', 'gerald@gmail.com', '0983 434 6453', '$2y$12$LXSibf06b44tz7wdDc7vVeBhC9GjJ6GgbV1XydNKd1DZDYavd6kpy', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:26:19'),
(12, 'Gheanne Soy', 'gheanne@gmail.com', '0987 821 3231', '$2y$12$6FcrfEj01B16vKZkXuFJAOIZQVn/k0710loe16lFJGUUqFINygmMi', 'employee', 1, NULL, NULL, NULL, '2026-11-13 05:26:37'),
(13, 'Louie Jay Dalo', 'luwi@gmail.com', '0998 765 4321', '$2y$12$.n9GGkPtSKbwnKF5WjdklerxecdgyfWnMBL/fyuJT4niM1b8DQpDK', 'customer', 1, NULL, NULL, NULL, '2026-11-13 05:30:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD PRIMARY KEY (`order_id`,`employee_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `order_materials`
--
ALTER TABLE `order_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `order_updates`
--
ALTER TABLE `order_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_tracking`
--
ALTER TABLE `product_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_materials`
--
ALTER TABLE `order_materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_updates`
--
ALTER TABLE `order_updates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_tracking`
--
ALTER TABLE `product_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD CONSTRAINT `order_assignments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_materials`
--
ALTER TABLE `order_materials`
  ADD CONSTRAINT `order_materials_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_materials_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Constraints for table `order_updates`
--
ALTER TABLE `order_updates`
  ADD CONSTRAINT `order_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_tracking`
--
ALTER TABLE `product_tracking`
  ADD CONSTRAINT `product_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
