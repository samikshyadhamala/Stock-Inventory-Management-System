-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 28, 2026 at 04:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `25123833_SMS`
--

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `batch_id` int(11) NOT NULL,
  `batch_no` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `manufacture_date` date NOT NULL,
  `expire_date` date NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `quantity_remaining` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch`
--

INSERT INTO `batch` (`batch_id`, `batch_no`, `product_id`, `purchase_id`, `manufacture_date`, `expire_date`, `quantity_received`, `quantity_remaining`) VALUES
(2, '1', 1, 3, '2026-01-29', '2028-06-19', 10, 0),
(3, '2', 2, 7, '2026-01-08', '2027-06-19', 10, 0),
(4, '3', 3, 8, '2026-01-20', '2028-10-19', 100, 10);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_category` varchar(50) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `product_category`, `unit`, `selling_price`, `reorder_level`, `status`, `quantity`) VALUES
(1, 'biscuit', 'food', 'box', 10.00, 10, 'active', 120),
(2, 'pasta', 'food', 'kg', 100.00, 8, 'active', 30),
(3, 'coke', 'drink', 'pack', 450.00, 10, 'active', 200);

-- --------------------------------------------------------

--
-- Table structure for table `purchased_item`
--

CREATE TABLE `purchased_item` (
  `purchase_item_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_order` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchased_item`
--

INSERT INTO `purchased_item` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity_order`, `unit_price`) VALUES
(3, 3, 1, 10, 10.00),
(6, 6, 1, 10, 10.00),
(7, 7, 2, 10, 300.00),
(8, 8, 3, 100, 500.00),
(9, 9, 3, 10, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `purchase_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order`
--

INSERT INTO `purchase_order` (`purchase_id`, `supplier_id`, `user_id`, `order_date`, `expected_delivery`, `status`) VALUES
(3, 1, 13, '2026-01-19', '2026-01-29', 'completed'),
(6, 1, 13, '2026-01-19', '2026-01-20', 'completed'),
(7, 2, 13, '2026-01-19', '2026-01-30', 'completed'),
(8, 4, 13, '2026-01-19', '2026-01-20', 'completed'),
(9, 4, 25, '2026-01-28', '2026-01-29', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `sale`
--

CREATE TABLE `sale` (
  `sale_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment` varchar(30) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale`
--

INSERT INTO `sale` (`sale_id`, `sale_date`, `total_amount`, `payment`, `status`) VALUES
(5, '2026-01-19', 100.00, 'mobile', 'completed'),
(9, '2026-01-19', 1000.00, 'cash', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `sale_item`
--

CREATE TABLE `sale_item` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_item`
--

INSERT INTO `sale_item` (`sale_item_id`, `sale_id`, `product_id`, `batch_id`, `quantity_sold`, `sale_price`) VALUES
(1, 5, 1, 2, 10, 10.00),
(2, 9, 2, 3, 10, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `supplier_address` text NOT NULL,
  `supplier_phone` varchar(20) NOT NULL,
  `supplier_email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `supplier_name`, `supplier_address`, `supplier_phone`, `supplier_email`, `contact_person`, `status`, `created_at`) VALUES
(1, 'Goodlife', 'Bidur,Nuwakot', '9848867688', 'samikshya.dhamala88@gmail.com', 'Samikshya Dhamala', 'active', '2026-01-16'),
(2, 'nepoli pasta', 'Bidur,Nuwakot', '1234567890', 'ram@gmail.com', 'ram shah', 'active', '2026-01-18'),
(3, 'haldiram', 'Bidur,Nuwakot', '9848867688', 'dhi@gmail.com', 'Sa Dhi', 'active', '2026-01-19'),
(4, 'bottlers nepal', 'janakpur', '984112345672333', 'she@gmail.com', 'shiva yadav', 'active', '2026-01-19');

-- --------------------------------------------------------

--
-- Table structure for table `user_otp`
--

CREATE TABLE `user_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `no_of_attempts` int(11) DEFAULT NULL,
  `purpose` enum('register','reset') DEFAULT 'register'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_otp`
--

INSERT INTO `user_otp` (`id`, `user_id`, `otp`, `expires_at`, `is_used`, `no_of_attempts`, `purpose`) VALUES
(12, 13, '872517', '2026-01-09 10:35:07', 1, NULL, 'register'),
(13, 14, '121777', '2026-01-09 10:36:53', 1, NULL, 'register'),
(14, 16, '968169', '2026-01-13 04:20:35', 1, NULL, 'register'),
(15, 17, '239750', '2026-01-16 03:33:56', 0, NULL, 'register'),
(16, 18, '801466', '2026-01-16 03:34:41', 0, NULL, 'register'),
(17, 19, '894326', '2026-01-16 03:35:22', 0, NULL, 'register'),
(18, 20, '384254', '2026-01-16 03:40:22', 1, NULL, 'register'),
(31, 25, '474547', '2026-01-20 10:19:04', 0, NULL, 'register'),
(32, 25, '793131', '2026-01-20 10:19:06', 1, 0, 'register'),
(33, 26, '188704', '2026-01-20 10:23:57', 0, NULL, 'register'),
(34, 25, '308127', '2026-01-28 07:28:45', 1, 0, 'reset'),
(35, 25, '199718', '2026-01-28 07:36:18', 1, 1, 'reset'),
(36, 25, '140881', '2026-01-28 07:41:06', 1, 0, 'reset'),
(37, 25, '458495', '2026-01-28 07:43:22', 1, 0, 'reset'),
(38, 13, '288913', '2026-01-28 14:18:37', 1, 0, 'reset');

-- --------------------------------------------------------

--
-- Table structure for table `user_owner`
--

CREATE TABLE `user_owner` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_owner`
--

INSERT INTO `user_owner` (`user_id`, `username`, `password`, `email`, `role`, `status`) VALUES
(13, 'samikshya123', '$2y$10$Xfk8R.Vfr7bAEHegtUBFhOofynzk2tGY4CQPIfTwWYTc5UTeuniMG', 'samikshya.dhamala88@gmail.com', 'user', 'active'),
(14, 'jack123', '$2y$10$mx1bedczDZCApITBCjhDu.VmISUm/zv5IvNL.LB1pG0dnxi7ClEY2', 'jack@gmail.com', 'user', 'active'),
(16, 'shreya123', '$2y$10$XHzIs/hihs33Sn8vFn8ZpOdDZpULLPtX.mYHWfbJyJ8tFvLNI.xey', 'shreyabhatta17@gmail.com', 'user', 'active'),
(17, 'john', '$2y$10$WIrIriPxmAgk6fB2g2u46Og2JtKzWgTwZALCcB9maY1zOcuJnZB02', 'john@gmail.com', 'user', 'inactive'),
(18, 'john123', '$2y$10$WPqCfnj.TXtzQublsv4c7e6PH8/sqSLHvB.PaIJrGkVgBw/dKV6bO', 'j@gmail.com', 'user', 'inactive'),
(19, 'ye', '$2y$10$j.X4WGzF7RBFvIFckAP7GOAGl/O0B2TtSLQ/gehSGxGhY86L84026', 'ye@gmail.com', 'user', 'inactive'),
(20, 'rose', '$2y$10$0K1dQmMEF3x7/evEJSNC6Os33vLde2/IzRyx6OeG617rbqL2BkZYa', 'rose@gmail.com', 'user', 'active'),
(25, 'sanjaya123', '$2y$10$rmpFbZByZ2KpMzERC92ECefmzOXVlRrthL1A22GDjrIqu6Vxky/VO', 'forcolabuse372@gmail.com', 'user', 'active'),
(26, 'johnnyyy', '$2y$10$dFWPNlxoBokvvm.P083SFOh5oxiyFUlB6CHWLlx96oDCdurCq/d6m', 'asda@gmail.com', 'user', 'inactive');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD UNIQUE KEY `batch_no` (`batch_no`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `purchased_item`
--
ALTER TABLE `purchased_item`
  ADD PRIMARY KEY (`purchase_item_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sale`
--
ALTER TABLE `sale`
  ADD PRIMARY KEY (`sale_id`);

--
-- Indexes for table `sale_item`
--
ALTER TABLE `sale_item`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `user_otp`
--
ALTER TABLE `user_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_owner`
--
ALTER TABLE `user_owner`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchased_item`
--
ALTER TABLE `purchased_item`
  MODIFY `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_order`
--
ALTER TABLE `purchase_order`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sale`
--
ALTER TABLE `sale`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sale_item`
--
ALTER TABLE `sale_item`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_otp`
--
ALTER TABLE `user_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user_owner`
--
ALTER TABLE `user_owner`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batch`
--
ALTER TABLE `batch`
  ADD CONSTRAINT `batch_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `batch_ibfk_2` FOREIGN KEY (`purchase_id`) REFERENCES `purchase_order` (`purchase_id`);

--
-- Constraints for table `purchased_item`
--
ALTER TABLE `purchased_item`
  ADD CONSTRAINT `purchased_item_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchase_order` (`purchase_id`),
  ADD CONSTRAINT `purchased_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `purchase_order_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`),
  ADD CONSTRAINT `purchase_order_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_owner` (`user_id`);

--
-- Constraints for table `sale_item`
--
ALTER TABLE `sale_item`
  ADD CONSTRAINT `sale_item_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sale` (`sale_id`),
  ADD CONSTRAINT `sale_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `sale_item_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batch` (`batch_id`);

--
-- Constraints for table `user_otp`
--
ALTER TABLE `user_otp`
  ADD CONSTRAINT `user_otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_owner` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
