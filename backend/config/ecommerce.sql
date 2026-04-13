-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 11:22 PM
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
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `created_at`, `parent_id`, `description`, `status`) VALUES
(1, 'Laptop', '2026-04-12 19:54:54', 0, 'This is laptop', 'active'),
(2, 'headphone', '2026-04-12 20:35:03', 0, 'headphone', 'active'),
(3, 'Hawaiian shirt', '2026-04-12 20:46:15', 0, 'Premium Fabric: This Hawaiian shirt is made of quality fabric. ', 'active'),
(4, 'Necklace', '2026-04-12 20:50:58', 0, 'Necklace', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `product_name`, `price`, `description`, `image`, `created_at`) VALUES
(1, 1, 'Laptop', 23000.00, 'HP 250R G10 Notebook PC - Intel Core 5 120U Processor 8-GB 512-GB SSD Intel Integrated Graphics 15.6\" FHD 1080P IPS 300nits AG Display Backlit KB FP Reader (Turbo Silver, NEW)', 'hp-laptop-1000x1000.webp', '2026-04-12 19:56:59'),
(2, 2, ' Headphone', 2500.00, 'Headphones Glitter Bear Ear Volume Limiting Adjustable Cute Anime Wired Headphones for Girls (Purple-Bear Ear): Home Audio & Theater', 'a75237ba39cbe3f7fe635558c0baff2b (1).jpg', '2026-04-12 20:38:39'),
(3, 3, 'Hawaiian shirt ', 3200.00, 'Premium Fabric: This Hawaiian shirt is made of quality fabric. The fabric is light,soft and comfortable,skin-friendly and breathable to keep you cool on hot summer days.', '648a77170c2b1f00bdbf190262d1ee28.jpg', '2026-04-12 20:47:23'),
(4, 4, 'Women\'s Flower Necklace ', 1200.00, 'Women\'s Flower Necklace Earrings Set', 'cd0f0e9525291e4ec5fc973842f136f1.webp', '2026-04-12 20:51:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
