-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 02:31 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u398852039_smartronic`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `price` varchar(20) DEFAULT NULL,
  `quantity` varchar(10) DEFAULT NULL,
  `product` varchar(100) DEFAULT NULL,
  `storage` varchar(50) DEFAULT NULL,
  `resolution` varchar(50) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `Owner` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `date` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `Map` varchar(200) DEFAULT NULL,
  `bullets` varchar(10) DEFAULT NULL,
  `dome` varchar(10) DEFAULT NULL,
  `order` varchar(10) DEFAULT NULL,
  `monitor` varchar(50) DEFAULT NULL,
  `rack` varchar(10) DEFAULT NULL,
  `technician` varchar(50) DEFAULT NULL,
  `helper` varchar(50) DEFAULT NULL,
  `time` varchar(10) DEFAULT NULL,
  `record_status` text DEFAULT NULL,
  `notes` varchar(200) NOT NULL,
  `admin_event_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `idno`, `phone`, `price`, `quantity`, `product`, `storage`, `resolution`, `name`, `area`, `note`, `Owner`, `created_at`, `date`, `location`, `Map`, `bullets`, `dome`, `order`, `monitor`, `rack`, `technician`, `helper`, `time`, `record_status`, `notes`) VALUES
(24, 'M-111', '7019246178', NULL, '4', 'DVR', '500GB', '2 MP', 'Shruti', 'K R Puram', '2 bullet and 2 dome saturay installation', 'AMR', '2025-05-15 15:34:26', '2025-05-16', 'https://maps.app.goo.gl/sGQdGFz5BaP2k6Ks5', '25.048', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ''),
(25, 'M-241', '7349269671', '16,533.00', '2', 'DVR', '500GB', '5 MP', 'Dhruva Nandana', 'Basawanagudi', 'Installation on Thursday  2 outdoor cameras', 'AMR', '2025-05-16 00:56:02', '', 'https://maps.app.goo.gl/QciyGDr2PfbcjJ3K7', '6.311', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ''),
(28, 'M-152', '7019246178', '24,000.00', '4', 'DVR', '500GB', '2 MP', 'Shruti', 'K R Puram', '2 bullet and 2 dome saturay installation', 'AMR', '2025-05-20 08:17:28', '', 'https://maps.app.goo.gl/sGQdGFz5BaP2k6Ks5', '25.048', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idno` (`idno`),
  ADD UNIQUE KEY `idno_2` (`idno`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=834;
-- Add extras column for serialized extra items (JSON string)
ALTER TABLE `orders` ADD COLUMN `extras` TEXT NULL AFTER `notes`;
ALTER TABLE `orders` ADD COLUMN `amount_paid` DECIMAL(10, 2) DEFAULT NULL AFTER `notes`, ADD COLUMN `fully_paid` TINYINT(1) DEFAULT 0 AFTER `amount_paid`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
