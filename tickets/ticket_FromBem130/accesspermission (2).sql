-- phpMyAdmin SQL Dump
-- version 5.2.1-1.el7.remi
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 28, 2025 at 12:07 AM
-- Server version: 10.5.24-MariaDB-log
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bem130_2025festest`
--

-- --------------------------------------------------------

--
-- Table structure for table `accesspermission`
--

CREATE TABLE `accesspermission` (
  `access_id` text NOT NULL,
  `password_hash` varchar(256) NOT NULL,
  `can_ticket_sales` tinyint(1) NOT NULL DEFAULT 0,
  `can_product_exchange` tinyint(1) NOT NULL DEFAULT 0,
  `can_view` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accesspermission`
--

INSERT INTO `accesspermission` (`access_id`, `password_hash`, `can_ticket_sales`, `can_product_exchange`, `can_view`) VALUES
('bem130', '$2y$10$EXekptldDsa/bZlh7XD8NeLPdeBmSwMdg6QenAD5pdSq2QGGNE76G', 1, 1, 1),
('test', '$2y$10$BJXrXoIjcnP5JkqWOsny7.UX60IAlFSQStg.Z2voOYUr3237TBylK', 1, 1, 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
