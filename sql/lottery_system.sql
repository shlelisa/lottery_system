-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 04, 2026 at 01:33 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lottery_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$./C3Hu3nM6DAlOsCwsHQMOckBaow0oT4LFl0zVpQ3OTzHljj9daKK', '2026-03-04 08:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

DROP TABLE IF EXISTS `participants`;
CREATE TABLE IF NOT EXISTS `participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `round_id` int NOT NULL,
  `added_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_verified` int NOT NULL,
  `is_winner` tinyint(1) DEFAULT '0',
  `winner_position` enum('first','second') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prize_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_round` (`round_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `round_id`, `added_by`, `name`, `phone`, `amount_paid`, `payment_verified`, `is_winner`, `winner_position`, `prize_amount`, `created_at`) VALUES
(1, 1, '1', 'Leellisaa Shaaashuraa', '', 100.00, 1, 0, NULL, NULL, '2026-03-04 12:45:13'),
(2, 1, '1', 'Leellisaa', '', 100.00, 1, 0, NULL, NULL, '2026-03-04 12:48:33'),
(3, 1, '1', 'jaarraa', '', 100.00, 1, 1, 'first', 450.00, '2026-03-04 12:48:39'),
(4, 1, '1', 'fiqiruu', '', 100.00, 1, 0, NULL, NULL, '2026-03-04 12:48:50'),
(5, 1, '1', 'guutuu', '', 100.00, 1, 0, NULL, NULL, '2026-03-04 12:48:55'),
(6, 1, '1', 'baayisaa', '', 100.00, 0, 0, NULL, NULL, '2026-03-04 12:49:40'),
(7, 1, '1', 'abarraa', '', 100.00, 0, 0, NULL, NULL, '2026-03-04 12:49:59'),
(8, 2, '1', 'Leellisaa Shaaashuraa', '', 500.00, 1, 0, NULL, NULL, '2026-03-04 13:09:51'),
(9, 2, '1', 'dhaabaa', '', 500.00, 1, 0, NULL, NULL, '2026-03-04 13:09:56'),
(10, 2, '1', 'jaarraa', '', 500.00, 1, 1, 'first', 2250.00, '2026-03-04 13:10:06'),
(11, 2, '1', 'guddataa', '', 500.00, 1, 0, NULL, NULL, '2026-03-04 13:10:12'),
(12, 2, '1', 'tafarraa', '', 500.00, 1, 0, NULL, NULL, '2026-03-04 13:10:17'),
(13, 2, '1', 'elemoo', '', 500.00, 0, 0, NULL, NULL, '2026-03-04 13:10:25'),
(14, 3, '1', 'Leellisaa', '', 1000.00, 0, 0, NULL, NULL, '2026-03-04 13:19:56'),
(15, 3, '1', 'guddataa', '', 1000.00, 1, 0, NULL, NULL, '2026-03-04 13:19:59'),
(16, 3, '1', 'elemoo', '', 1000.00, 1, 1, 'first', 2700.00, '2026-03-04 13:20:02'),
(17, 3, '1', 'jaarraa', '', 1000.00, 1, 0, NULL, NULL, '2026-03-04 13:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `rounds`
--

DROP TABLE IF EXISTS `rounds`;
CREATE TABLE IF NOT EXISTS `rounds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `round_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `round_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_fee` decimal(10,2) NOT NULL,
  `max_participants` int NOT NULL,
  `current_participants` int DEFAULT '0',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `admin_profit` decimal(10,2) DEFAULT '0.00',
  `first_draw_done` tinyint(1) DEFAULT '0',
  `second_draw_done` tinyint(1) DEFAULT '0',
  `status` enum('open','locked','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rounds`
--

INSERT INTO `rounds` (`id`, `round_code`, `created_by`, `round_name`, `entry_fee`, `max_participants`, `current_participants`, `total_amount`, `admin_profit`, `first_draw_done`, `second_draw_done`, `status`, `created_at`) VALUES
(1, 'ROUND-171B5F8B', '1', '', 100.00, 5, 5, 500.00, 50.00, 0, 0, 'completed', '2026-03-04 10:30:34'),
(2, 'ROUND-E7B887E8', '1', '', 500.00, 5, 5, 2500.00, 250.00, 0, 0, 'completed', '2026-03-04 13:09:43'),
(3, 'ROUND-8AD2D236', '1', '', 1000.00, 3, 3, 3000.00, 300.00, 0, 0, 'completed', '2026-03-04 13:19:49');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `round_id` int DEFAULT NULL,
  `participant_id` int DEFAULT NULL,
  `type` enum('entry','first_prize','second_prize','admin_profit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `round_id` (`round_id`),
  KEY `participant_id` (`participant_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `round_id`, `participant_id`, `type`, `amount`, `created_at`) VALUES
(1, 1, 3, '', 450.00, '2026-03-04 12:55:35'),
(2, 1, NULL, 'admin_profit', 50.00, '2026-03-04 12:55:35'),
(3, 2, 10, '', 2250.00, '2026-03-04 13:12:48'),
(4, 2, NULL, 'admin_profit', 250.00, '2026-03-04 13:12:48'),
(5, 3, 16, '', 2700.00, '2026-03-04 13:20:41'),
(6, 3, NULL, 'admin_profit', 300.00, '2026-03-04 13:20:41');

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

DROP TABLE IF EXISTS `winners`;
CREATE TABLE IF NOT EXISTS `winners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `round_id` int NOT NULL,
  `participant_id` int NOT NULL,
  `position` int NOT NULL,
  `prize_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
