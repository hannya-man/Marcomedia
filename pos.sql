-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 02:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos`
--
CREATE DATABASE IF NOT EXISTS `pos` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `pos`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_admin_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `user_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', '2025-09-09 23:49:10'),
(9, 2, 'admin1', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', '2025-09-18 02:29:31'),
(10, NULL, 'admin2', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', '2025-09-20 10:57:53');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_user` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_user`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:34:04'),
(2, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:46:54'),
(3, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:46:59'),
(4, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:47:02'),
(5, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:52:19'),
(6, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 01:52:20'),
(7, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 02:08:19'),
(8, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 05:30:47'),
(9, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 05:31:42'),
(10, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 05:32:09'),
(11, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-26 23:52:28'),
(12, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:19:59'),
(13, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:24:39'),
(14, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:24:40'),
(15, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:24:41'),
(16, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:24:41'),
(17, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 03:24:42'),
(18, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-27 06:32:26'),
(19, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 01:01:09'),
(20, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 01:02:17'),
(21, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 02:17:42'),
(22, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 02:20:08'),
(23, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 02:35:44'),
(24, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 02:35:57'),
(25, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 02:35:58'),
(26, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 03:22:51'),
(27, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:12:20'),
(28, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:12:25'),
(29, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:13:46'),
(30, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:17:43'),
(31, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:24:14'),
(32, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 04:47:58'),
(33, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:38'),
(34, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:42'),
(35, 1, 'TOGGLE_STATUS_enabled', 'admin1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:46'),
(36, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:46'),
(37, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:49'),
(38, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:49'),
(39, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:51'),
(40, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:02:51'),
(41, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:26:17'),
(42, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:36:52'),
(43, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:36:54'),
(44, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:36:56'),
(45, 1, 'TOGGLE_STATUS_enabled', 'Jhon Bien Lordson', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:36:59'),
(46, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:36:59'),
(47, 1, 'TOGGLE_STATUS_enabled', 'Mark Rebyn Ilaya', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:02'),
(48, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:02'),
(49, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:04'),
(50, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:04'),
(51, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:09'),
(52, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:09'),
(53, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(54, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(55, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(56, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(57, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(58, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:10'),
(59, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:11'),
(60, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:11'),
(61, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:37:13'),
(62, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-30 02:18:39'),
(63, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-30 02:18:41'),
(64, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-30 02:18:44'),
(65, 1, 'TOGGLE_STATUS_enabled', 'Mark Rebyn Ilaya', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-30 02:18:48'),
(66, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-30 02:18:48'),
(67, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:18:47'),
(68, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:22:01'),
(69, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:39:46'),
(70, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:39:49'),
(71, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:39:54'),
(72, 1, 'TOGGLE_STATUS_enabled', 'admin1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:39:57'),
(73, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:39:57'),
(74, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:40:00'),
(75, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:40:00'),
(76, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 17:58:47'),
(77, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 22:49:46'),
(78, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 22:53:49'),
(79, 1, 'TOGGLE_STATUS_enabled', 'Jhon Bien Lordson', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 22:53:51'),
(80, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-31 22:53:51'),
(81, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:20:37'),
(82, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:20:46'),
(83, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:20:53'),
(84, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:21:04'),
(85, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:29:11'),
(86, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:43:58'),
(87, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:01'),
(88, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:17'),
(89, 1, 'TOGGLE_STATUS_disabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:24'),
(90, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:24'),
(91, 1, 'TOGGLE_STATUS_enabled', 'Mark Lloyd Clave', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:37'),
(92, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 00:44:37'),
(93, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 06:39:31'),
(94, 1, 'TOGGLE_STATUS_disabled', 'admin1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 09:11:04'),
(95, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 09:11:04'),
(96, 1, 'TOGGLE_STATUS_enabled', 'admin1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 09:11:10'),
(97, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 09:11:10'),
(98, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:26:52'),
(99, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:27:31'),
(100, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:27:33'),
(101, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:29:04'),
(102, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:29:05'),
(103, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-02 23:29:06'),
(104, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 06:51:12'),
(105, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-06 07:29:08'),
(106, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-07 06:27:39'),
(107, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-07 07:47:10'),
(108, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-07 07:47:21'),
(109, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-08 00:33:57'),
(110, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-08 01:46:19'),
(111, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-08 01:48:00'),
(112, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 03:03:54'),
(113, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 03:06:52'),
(114, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 03:20:55'),
(115, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 03:21:06'),
(116, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 04:41:56'),
(117, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 07:05:33'),
(118, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 07:05:54'),
(119, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 07:06:29'),
(120, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 07:06:41'),
(121, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 10:45:00'),
(122, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 15:57:19'),
(123, 24, 'TOGGLE_STATUS_disabled', 'Jhon Bien Lordson', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 15:57:26'),
(124, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 15:57:26'),
(125, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 23:18:16'),
(126, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 23:35:19'),
(127, 24, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-13 08:58:57'),
(128, 1, 'ACCESS_USER_MANAGEMENT', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-13 23:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Approved','Cancelled') DEFAULT 'Pending',
  `cameraman_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `photographer_id` int(11) DEFAULT NULL,
  `photographer_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `customer`, `user_id`, `service`, `date`, `start_time`, `end_time`, `location`, `note`, `admin_notes`, `event_type`, `notes`, `status`, `cameraman_id`, `created_at`, `deleted_at`, `photographer_id`, `photographer_name`) VALUES
(8, 'Kent Medina', NULL, 'Photography', '2025-09-13', '20:00:00', '21:00:00', 'Street 123', NULL, NULL, NULL, NULL, 'Cancelled', NULL, '2025-09-13 11:53:17', NULL, NULL, NULL),
(9, 'Christian Robaro Narvaez', NULL, 'Photography', '2025-09-13', '21:00:00', '22:30:00', 'Banadero P-1', NULL, NULL, NULL, NULL, 'Cancelled', NULL, '2025-09-13 11:54:10', NULL, NULL, NULL),
(10, 'Chan-chan', NULL, 'Photography', '2025-09-15', '13:40:00', '13:50:00', 'Banadero P-1', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-15 05:37:37', NULL, NULL, NULL),
(11, 'Rodson Amisola', NULL, 'Photography', '2025-09-15', '13:40:00', '13:50:00', 'Banadero P-1', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-15 05:38:36', NULL, NULL, NULL),
(12, 'Kent Medina', NULL, 'Photography', '2025-09-18', '16:00:00', '16:10:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-18 05:11:23', NULL, NULL, NULL),
(13, 'Jarren Chesterson Paccaro', NULL, 'Photography', '2025-09-20', '22:50:00', '22:52:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-19 02:23:02', NULL, NULL, NULL),
(14, 'Neil Vincent', NULL, 'Photography', '2025-09-19', '14:30:00', '15:00:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-19 02:35:15', NULL, NULL, NULL),
(15, 'Neil Kevin', NULL, 'Photography', '2025-09-19', '11:16:00', '11:17:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-19 03:15:41', NULL, NULL, NULL),
(16, 'Kris Louise Luna', NULL, 'Photography', '2025-09-30', '10:40:00', '16:40:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-09-29 02:40:26', NULL, NULL, NULL),
(19, 'Lei Francine Biot', NULL, 'Photography', '2025-10-04', '10:59:00', '11:59:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-02 02:59:19', NULL, NULL, NULL),
(20, 'Kris Louise Luna', NULL, 'Photography', '2025-10-04', '16:43:00', '17:43:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-02 07:43:31', NULL, NULL, NULL),
(21, 'Kris Louise Luna', NULL, 'Photography', '2025-10-04', '20:07:00', '21:07:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-03 11:07:39', NULL, NULL, NULL),
(22, 'Kent Medina', NULL, 'Photography', '2025-10-06', '10:49:00', '11:49:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-04 02:49:49', NULL, NULL, NULL),
(23, 'Kris Louise Luna', NULL, 'Photography', '2025-10-06', '10:49:00', '11:49:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-04 02:50:21', NULL, NULL, NULL),
(24, 'Neil Kevin', NULL, 'Photography', '2025-10-10', '21:12:00', '23:12:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-07 12:12:32', NULL, NULL, NULL),
(25, 'Mark Lloyd Clave', NULL, 'Photography', '2025-10-11', '10:34:00', '11:34:00', 'Street 123', NULL, NULL, NULL, NULL, 'Approved', NULL, '2025-10-09 01:34:48', NULL, NULL, NULL),
(26, 'Kent Medina', 1, 'Photography', '2025-10-14', '09:00:00', '11:00:00', 'Street 123', 'Rinax Osmena Antonio test', NULL, 'Other', 'N/A', 'Cancelled', 17, '2025-10-12 12:46:20', NULL, 16, NULL),
(30, 'Christian Robrado Narvaez', 10, 'Photography Service - Portrait', '2025-10-20', '08:59:00', '10:59:00', 'Street 123', 'Tester', '', 'Portrait', 'RinaxTest123', 'Approved', 16, '2025-10-18 18:00:10', NULL, 18, NULL),
(31, 'Riofel Rohan Subsuban Augusto', 13, 'Photography Service - Event', '2025-10-30', '10:43:00', '11:43:00', 'Street 123', NULL, '', 'Event', 'Test Rohan', 'Approved', NULL, '2025-10-28 02:43:37', NULL, 16, NULL),
(32, 'Christian Robrado Narvaez', 10, 'Photography Service - Corporate', '2025-10-30', '15:51:00', '16:51:00', 'Street 123', NULL, NULL, 'Corporate', 'N/A', 'Cancelled', NULL, '2025-10-30 07:51:57', '2025-11-12 02:11:35', 18, NULL),
(33, 'Forice Mae Batoctoy', 23, 'Photography Service - Real Estate', '2025-11-14', '15:43:00', '16:43:00', 'Street 123', NULL, '', 'Real Estate', '', 'Approved', NULL, '2025-11-07 07:44:38', '2025-11-12 02:11:26', 16, NULL),
(34, 'Forice Mae Batoctoy', 23, 'Photography Service - Wedding', '2025-11-13', '09:38:00', '21:37:00', 'Street 123', NULL, NULL, 'Wedding', 'Nigga', 'Approved', NULL, '2025-11-08 00:37:56', NULL, 16, NULL),
(35, 'Forice Mae Batoctoy', 23, 'Photography Service - Corporate', '2025-11-13', '10:15:00', '11:15:00', 'Street 123', NULL, NULL, 'Corporate', '', 'Approved', NULL, '2025-11-12 02:15:15', NULL, 18, NULL),
(36, 'Forice Mae Batoctoy', 23, 'Photography Service - Product', '2025-11-15', '14:48:00', '19:48:00', 'Street 123', NULL, NULL, 'Product', '', 'Pending', NULL, '2025-11-12 15:51:18', NULL, 18, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE IF NOT EXISTS `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `design_type` varchar(50) NOT NULL,
  `design_data` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `status` enum('Pending','Confirmed','Received','Cancelled','Completed') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_type` enum('purchase','physical','online') DEFAULT 'purchase',
  `order_number` varchar(50) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `payment_method` enum('cash','card','gcash','bank_transfer') DEFAULT 'cash',
  `created_by` varchar(100) DEFAULT 'System',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `role`, `customer_id`, `customer_name`, `status`, `created_at`, `quantity`, `total_amount`, `order_type`, `order_number`, `customer_phone`, `payment_method`, `created_by`) VALUES
(12, 0, '', NULL, 'admin', 'Received', '2025-09-22 07:17:38', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(13, 0, '', NULL, 'admin', 'Received', '2025-09-22 09:57:34', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(14, 0, '', NULL, 'admin', 'Received', '2025-09-22 11:10:13', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(15, 0, '', NULL, 'admin', 'Received', '2025-09-24 13:36:09', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(16, 0, '', NULL, 'admin', 'Received', '2025-09-24 13:37:47', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(17, 0, '', NULL, 'admin', 'Received', '2025-09-26 16:23:10', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(18, 0, '', NULL, 'admin', 'Received', '2025-09-27 14:39:26', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(31, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 14:34:43', 20, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(32, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 14:43:48', 2, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(33, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 15:11:22', 20, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(34, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 16:06:14', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(35, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 21:01:55', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(36, 1, 'admin', NULL, 'admin', 'Received', '2025-10-03 21:54:48', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(37, 1, 'admin', NULL, 'admin', 'Received', '2025-10-04 08:42:51', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(38, 1, 'admin', NULL, 'admin', 'Received', '2025-10-06 11:22:03', 2, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(39, 1, 'admin', NULL, 'admin', 'Received', '2025-10-06 11:26:47', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(40, 10, 'customer', NULL, 'Guest', 'Received', '2025-10-07 14:15:42', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(41, 12, 'customer', NULL, 'Guest', 'Received', '2025-10-07 15:30:50', 3, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(42, 10, 'customer', NULL, 'Guest', 'Received', '2025-10-07 20:15:15', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(43, 10, 'customer', NULL, 'Guest', 'Received', '2025-10-08 15:50:01', 1, NULL, 'purchase', NULL, NULL, 'cash', 'System'),
(45, 10, 'customer', NULL, 'Mark Lloyd Clave', 'Completed', '2025-10-08 20:00:17', 1, NULL, 'physical', 'PO-20251008140070', '09391872554', 'cash', '0'),
(47, 10, 'customer', NULL, 'Christian Narvaez', 'Completed', '2025-10-08 21:45:46', 1, NULL, 'physical', 'PO-20251008154552', '099876543223', 'gcash', '0'),
(48, 10, 'customer', NULL, 'Jhon Eric Miguel Emoria', 'Completed', '2025-10-09 07:16:29', 10, NULL, 'physical', 'PO-20251009011677', '0929255647', 'card', '0'),
(49, 14, 'customer', NULL, 'Gwen Domine Aninao', 'Completed', '2025-10-09 09:33:46', 10, NULL, 'physical', 'PO-20251009033396', '098765432123', 'bank_transfer', '0'),
(50, 10, 'customer', NULL, 'Christian Narvaez', 'Completed', '2025-10-10 19:35:31', 10, NULL, 'physical', 'PO-20251010133579', '09292556477', 'bank_transfer', '0'),
(51, 10, 'customer', NULL, 'Christian Robrado Narvaez', 'Received', '2025-10-10 22:15:45', 1, 40.00, 'online', 'CO-20251010161545765', '', 'cash', 'Online Store'),
(52, 10, 'customer', NULL, 'Christian Robrado Narvaez', 'Received', '2025-10-10 22:32:09', 1, 40.00, 'online', 'CO-20251010163209217', '', 'cash', 'Online Store'),
(53, 10, 'customer', NULL, 'Christian Robrado Narvaez', 'Received', '2025-10-10 23:05:50', 1, 40.00, 'online', 'CO-20251010170550471', '', 'cash', 'Online Store'),
(55, 1, 'admin', NULL, 'admin', 'Received', '2025-10-19 11:01:56', 1, 40.00, 'online', 'CO-20251019050156427', '', 'cash', 'Online Store'),
(58, 1, '0', NULL, 'Mark Lloyd Clave', 'Completed', '2025-10-19 23:46:42', 3, 150.00, 'physical', 'PO-20251019174687', '09391872554', 'gcash', '0'),
(59, 13, 'customer', NULL, 'Riofel Rohan Subsuban Augusto', 'Received', '2025-10-28 11:11:34', 1, 40.00, 'online', 'CO-20251028041134295', '', 'cash', 'Online Store'),
(60, 13, 'customer', NULL, 'Riofel Rohan Subsuban Augusto', 'Received', '2025-10-28 12:55:27', 1, 40.00, 'online', 'CO-20251028055527758', '', 'cash', 'Online Store'),
(61, 13, 'customer', NULL, 'Riofel Rohan Subsuban Augusto', 'Received', '2025-10-28 13:08:24', 1, 40.00, 'online', 'CO-20251028060824529', '', 'cash', 'Online Store'),
(62, 1, '0', NULL, 'Christian Robrado Narvaez', 'Completed', '2025-11-01 00:51:34', 8, 1400.00, 'physical', 'PO-20251031175118', '1234567890', 'card', '0'),
(63, 10, 'customer', NULL, 'Christian Robrado Narvaez', 'Received', '2025-11-01 08:31:09', 2, 120.00, 'online', 'CO-20251101013109657', '', 'cash', 'Online Store'),
(64, 10, 'customer', NULL, 'Christian Robrado Narvaez', 'Received', '2025-11-02 08:30:59', 2, 150.00, 'online', 'CO-20251102013059350', '', 'cash', 'Online Store'),
(65, 1, '0', NULL, 'Mark Lloyd Clave', 'Completed', '2025-11-08 08:35:28', 98, 29400.00, 'physical', 'PO-20251108013584', '1234567890', 'cash', '0'),
(66, 23, 'customer', NULL, 'Forice Mae Batoctoy', 'Confirmed', '2025-11-08 08:41:01', 2, 330.00, 'online', 'CO-20251108014101966', '', 'cash', 'Online Store'),
(67, 23, 'customer', NULL, 'Forice Mae Batoctoy', 'Pending', '2025-11-13 00:03:23', 1, 40.00, 'online', 'CO-20251112170323456', '', 'cash', 'Online Store'),
(68, 23, 'customer', NULL, 'Forice Mae Batoctoy', 'Pending', '2025-11-13 00:31:35', 1, 30.00, 'online', 'CO-20251112173135980', '', 'cash', 'Online Store');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product`, `quantity`, `unit_price`, `subtotal`) VALUES
(9, 12, 702, NULL, NULL, 10, 0.00, 0.00),
(10, 13, 753, NULL, NULL, 20, 0.00, 0.00),
(11, 14, 705, NULL, NULL, 1, 0.00, 0.00),
(12, 15, 702, NULL, NULL, 10, 0.00, 0.00),
(13, 16, 703, NULL, NULL, 50, 0.00, 0.00),
(14, 17, 706, NULL, NULL, 50, 0.00, 0.00),
(15, 18, 704, NULL, NULL, 1, 0.00, 0.00),
(28, 31, 770, NULL, NULL, 20, 0.00, 0.00),
(29, 32, 770, NULL, NULL, 2, 0.00, 0.00),
(30, 33, 770, NULL, NULL, 20, 0.00, 0.00),
(31, 34, 770, NULL, NULL, 1, 0.00, 0.00),
(32, 35, 770, NULL, NULL, 1, 0.00, 0.00),
(33, 36, 769, NULL, NULL, 1, 0.00, 0.00),
(34, 37, 770, NULL, NULL, 1, 0.00, 0.00),
(35, 38, 770, NULL, NULL, 2, 0.00, 0.00),
(36, 39, 704, NULL, NULL, 1, 0.00, 0.00),
(37, 40, 771, NULL, NULL, 1, 0.00, 0.00),
(38, 41, 771, NULL, NULL, 1, 0.00, 0.00),
(39, 41, 762, NULL, NULL, 1, 0.00, 0.00),
(40, 41, 763, NULL, NULL, 1, 0.00, 0.00),
(41, 42, 703, NULL, NULL, 1, 0.00, 0.00),
(42, 43, 762, NULL, NULL, 1, 0.00, 0.00),
(43, 45, 758, NULL, NULL, 1, 100.00, 100.00),
(47, 47, 758, NULL, NULL, 1, 100.00, 100.00),
(48, 48, 706, NULL, NULL, 5, 300.00, 1500.00),
(49, 48, 725, NULL, NULL, 5, 30.00, 150.00),
(51, 49, 724, NULL, NULL, 10, 50.00, 500.00),
(59, 50, 733, NULL, NULL, 10, 150.00, 1500.00),
(60, 51, 771, NULL, NULL, 1, 40.00, 40.00),
(61, 52, 771, NULL, NULL, 1, 40.00, 40.00),
(62, 53, 771, NULL, NULL, 1, 40.00, 40.00),
(64, 55, 771, NULL, NULL, 1, 40.00, 40.00),
(65, 58, 751, NULL, 'Frame', 3, 50.00, 150.00),
(66, 59, 771, NULL, NULL, 1, 40.00, 40.00),
(67, 60, 771, NULL, NULL, 1, 40.00, 40.00),
(68, 61, 771, NULL, NULL, 1, 40.00, 40.00),
(69, 62, 758, NULL, '3D Frame', 1, 100.00, 100.00),
(70, 62, 733, NULL, 'Apron', 2, 150.00, 300.00),
(71, 62, 754, NULL, 'Arcrylic', 5, 200.00, 1000.00),
(72, 63, 712, NULL, NULL, 1, 100.00, 100.00),
(73, 63, 768, NULL, NULL, 1, 20.00, 20.00),
(74, 64, 750, NULL, NULL, 1, 100.00, 100.00),
(75, 64, 761, NULL, NULL, 1, 50.00, 50.00),
(76, 65, 703, NULL, 'Magic Mug', 98, 300.00, 29400.00),
(77, 66, 706, NULL, NULL, 1, 300.00, 300.00),
(78, 66, 769, NULL, NULL, 1, 30.00, 30.00),
(79, 67, 771, NULL, NULL, 1, 40.00, 40.00),
(80, 68, 769, NULL, NULL, 1, 30.00, 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `product` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `order_type` enum('purchase','physical') DEFAULT 'purchase',
  `order_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `payment_method` enum('cash','card','gcash','bank_transfer') DEFAULT 'cash',
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sales_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `sale_date`, `product`, `quantity`, `total`, `status`, `order_type`, `order_number`, `customer_name`, `customer_phone`, `payment_method`, `order_id`) VALUES
(61, NULL, '2025-10-08 20:00:17', '3D Frame', 1, 100.00, 'pending', 'physical', '0', 'Mark Lloyd Clave', NULL, 'cash', 45),
(62, NULL, '2025-10-08 21:45:46', '3D Frame', 1, 100.00, 'pending', 'physical', '0', 'Christian Narvaez', NULL, 'gcash', 47),
(63, NULL, '2025-10-09 07:16:29', 'Beer Mug', 5, 1500.00, 'pending', 'physical', '0', 'Jhon Eric Miguel Emoria', NULL, 'card', 48),
(64, NULL, '2025-10-09 07:16:29', 'Eco Bag', 5, 150.00, 'pending', 'physical', '0', 'Jhon Eric Miguel Emoria', NULL, 'card', 48),
(65, NULL, '2025-10-09 09:33:46', 'Tote Bag', 10, 500.00, 'pending', 'physical', '0', 'Gwen Domine Aninao', NULL, 'bank_transfer', 49),
(66, NULL, '2025-10-10 19:35:32', 'Apron', 0, 0.00, 'pending', 'physical', '0', 'Christian Narvaez', NULL, 'bank_transfer', 50),
(67, NULL, '2025-10-19 23:46:42', 'Frame', 3, 150.00, 'pending', 'physical', '0', 'Mark Lloyd Clave', NULL, 'gcash', 58),
(68, NULL, '2025-11-01 00:51:34', '3D Frame', 1, 100.00, 'pending', 'physical', '0', 'Christian Robrado Narvaez', NULL, 'card', 62),
(69, NULL, '2025-11-01 00:51:34', 'Apron', 2, 300.00, 'pending', 'physical', '0', 'Christian Robrado Narvaez', NULL, 'card', 62),
(70, NULL, '2025-11-01 00:51:34', 'Arcrylic', 5, 1000.00, 'pending', 'physical', '0', 'Christian Robrado Narvaez', NULL, 'card', 62),
(71, NULL, '2025-11-08 08:35:28', 'Magic Mug', 98, 29400.00, 'pending', 'physical', '0', 'Mark Lloyd Clave', NULL, 'cash', 65);

-- --------------------------------------------------------

--
-- Table structure for table `saved_designs`
--

CREATE TABLE IF NOT EXISTS `saved_designs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `design_name` varchar(255) NOT NULL,
  `design_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `saved_designs`
--

INSERT INTO `saved_designs` (`id`, `user_id`, `item_id`, `design_name`, `design_data`, `created_at`, `updated_at`) VALUES
(2, 1, 764, 'Rinax', '{\"texts\":[{\"type\":\"text\",\"text\":\"Rinax Osmena Antonio\",\"x\":227.9999951171872,\"y\":353,\"color\":\"#000000\",\"font\":\"Arial\",\"size\":24,\"bold\":false,\"italic\":false,\"id\":1759734950808}],\"shapes\":[{\"type\":\"shape\",\"shapeType\":\"square\",\"x\":416.0000010850696,\"y\":264,\"size\":82.03411865234375,\"color\":\"#2596be\",\"id\":1759734964270}],\"productColor\":\"#ecc94b\",\"zoomLevel\":1,\"images\":[{\"base64\":\"data:image/jpeg;base64,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\",\"x\":226.99999504937028,\"y\":180,\"width\":122.63639645971654,\"height\":127.01705932617188}]}', '2025-10-06 07:16:32', '2025-10-06 07:16:32'),
(4, 10, 703, 'Test', '{\"texts\":[],\"shapes\":[{\"type\":\"shape\",\"shapeType\":\"heart\",\"x\":330.0000047471785,\"y\":252,\"size\":50,\"color\":\"#2596be\",\"id\":1759839240026}],\"productColor\":\"#000000\",\"zoomLevel\":1,\"images\":[]}', '2025-10-07 12:14:30', '2025-10-07 12:14:30');
INSERT INTO `saved_designs` (`id`, `user_id`, `item_id`, `design_name`, `design_data`, `created_at`, `updated_at`) VALUES
(5, 14, 771, 'Diddy Card', '{\"texts\":[{\"type\":\"text\",\"text\":\"welcome to diddy party\",\"x\":245,\"y\":400,\"color\":\"#000000\",\"font\":\"Arial\",\"size\":24,\"bold\":false,\"italic\":false,\"underline\":false,\"rotation\":0,\"opacity\":1,\"id\":1759973553022}],\"shapes\":[],\"productColor\":\"#ffffff\",\"zoomLevel\":1,\"images\":[{\"base64\":\"data:image/jpeg;base64,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\",\"x\":241,\"y\":193,\"width\":200,\"height\":150,\"rotation\":0,\"opacity\":1}]}', '2025-10-09 01:32:42', '2025-10-09 01:32:42'),
(6, 1, 771, 'test2', '{\"texts\":[],\"shapes\":[{\"type\":\"shape\",\"shapeType\":\"circle\",\"x\":400,\"y\":300,\"size\":50,\"color\":\"#2596be\",\"rotation\":0,\"opacity\":1,\"id\":1760163328674,\"radius\":25}],\"productColor\":\"#000000\",\"zoomLevel\":1,\"images\":[]}', '2025-10-11 06:15:37', '2025-10-11 06:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE IF NOT EXISTS `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) DEFAULT NULL,
  `product` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=773 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `category`, `product`, `name`, `price`, `qty`, `image_path`, `created_at`, `status`) VALUES
(702, 'Drinkware', NULL, 'Regular', 150.00, 100, '', '2025-09-21 14:45:13', 'inactive'),
(703, 'Drinkware', NULL, 'Magic Mug', 300.00, 100, 'uploads/1760341450_61NwujclU4L._UF894_1000_QL80_.jpg', '2025-09-21 14:45:13', 'active'),
(704, 'Drinkware', NULL, 'Frosted Mug', 200.00, 98, 'uploads/1760341570_Frpsted-Mug-2.jpg', '2025-09-21 14:45:13', 'active'),
(705, 'Drinkware', NULL, 'Transparent Mug', 200.00, 99, '', '2025-09-21 14:45:13', 'active'),
(706, 'Drinkware', NULL, 'Beer Mug', 300.00, 144, '', '2025-09-21 14:45:13', 'active'),
(707, 'Drinkware', NULL, 'Tumbler', 500.00, 100, '', '2025-09-21 14:45:13', 'active'),
(708, 'Accessories & Small Items', NULL, 'Foldable Fan', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(709, 'Accessories & Small Items', NULL, 'Ref Magnet', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(710, 'Accessories & Small Items', NULL, 'Keychain', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(711, 'Accessories & Small Items', NULL, 'Bottle Operner', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(712, 'Accessories & Small Items', NULL, 'Magic Mirror', 100.00, 98, '', '2025-09-21 14:45:13', 'active'),
(713, 'Accessories & Small Items', NULL, 'Cellphone Case', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(714, 'Accessories & Small Items', NULL, 'Wallet', 300.00, 100, '', '2025-09-21 14:45:13', 'active'),
(715, 'Accessories & Small Items', NULL, 'Notebook', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(716, 'Accessories & Small Items', NULL, 'Pen', 40.00, 100, '', '2025-09-21 14:45:13', 'active'),
(717, 'Accessories & Small Items', NULL, 'Pencil', 30.00, 100, '', '2025-09-21 14:45:13', 'active'),
(718, 'Accessories & Small Items', NULL, 'Planner', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(719, 'Accessories & Small Items', NULL, 'Cap', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(720, 'Accessories & Small Items', NULL, 'Handkerchief', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(721, 'Accessories & Small Items', NULL, 'Umbrella', 150.00, 100, '', '2025-09-21 14:45:13', 'active'),
(722, 'Accessories & Small Items', NULL, 'Gaming Mousepad', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(723, 'Accessories & Small Items', NULL, 'Mousepad', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(724, 'Accessories & Small Items', NULL, 'Tote Bag', 50.00, 90, '', '2025-09-21 14:45:13', 'active'),
(725, 'Accessories & Small Items', NULL, 'Eco Bag', 30.00, 95, '', '2025-09-21 14:45:13', 'active'),
(726, 'Apparel', NULL, 'Tshirt', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(727, 'Apparel', NULL, 'Jacket', 300.00, 100, '', '2025-09-21 14:45:13', 'active'),
(728, 'Apparel', NULL, 'Vest', 150.00, 100, '', '2025-09-21 14:45:13', 'active'),
(729, 'Apparel', NULL, 'Jersey', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(730, 'Apparel', NULL, 'Polo Shirt', 250.00, 100, '', '2025-09-21 14:45:13', 'active'),
(731, 'Apparel', NULL, 'Sweatshirt', 300.00, 100, '', '2025-09-21 14:45:13', 'active'),
(732, 'Apparel', NULL, 'PE Uniform', 250.00, 100, '', '2025-09-21 14:45:13', 'active'),
(733, 'Apparel', NULL, 'Apron', 150.00, 98, 'uploads/1760341419_81NC_V_pmLL._AC_SL1500_.jpg', '2025-09-21 14:45:13', 'active'),
(734, 'Apparel', NULL, 'Sablay', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(735, 'Awards & Corporate Items', NULL, 'Table Nametag', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(736, 'Awards & Corporate Items', NULL, 'Name Plate', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(737, 'Awards & Corporate Items', NULL, 'Badge Pin', 25.00, 100, '', '2025-09-21 14:45:13', 'active'),
(738, 'Awards & Corporate Items', NULL, 'Button Pin', 25.00, 100, '', '2025-09-21 14:45:13', 'active'),
(739, 'Awards & Corporate Items', NULL, 'Lanyard', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(740, 'Awards & Corporate Items', NULL, 'PVC ID', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(741, 'Awards & Corporate Items', NULL, 'Lamination', 20.00, 100, '', '2025-09-21 14:45:13', 'active'),
(742, 'Awards & Corporate Items', NULL, 'Trophy', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(743, 'Awards & Corporate Items', NULL, 'Plaque', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(744, 'Awards & Corporate Items', NULL, 'Medal', 250.00, 100, '', '2025-09-21 14:45:13', 'active'),
(745, 'Awards & Corporate Items', NULL, 'Sash', 150.00, 99, '', '2025-09-21 14:45:13', 'active'),
(746, 'Awards & Corporate Items', NULL, 'Lei', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(747, 'Home & Office Decor', NULL, 'Plate', 300.00, 100, '', '2025-09-21 14:45:13', 'active'),
(748, 'Home & Office Decor', NULL, 'Rock Photo', 200.00, 100, '', '2025-09-21 14:45:13', 'active'),
(749, 'Home & Office Decor', NULL, 'Glass Clock', 250.00, 100, '', '2025-09-21 14:45:13', 'active'),
(750, 'Home & Office Decor', NULL, 'Pillow', 100.00, 98, '', '2025-09-21 14:45:13', 'active'),
(751, 'Home & Office Decor', NULL, 'Frame', 50.00, 97, '', '2025-09-21 14:45:13', 'active'),
(752, 'Home & Office Decor', NULL, 'Sintra Board', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(753, 'Home & Office Decor', NULL, 'Glass', 300.00, 100, '', '2025-09-21 14:45:13', 'active'),
(754, 'Home & Office Decor', NULL, 'Arcrylic', 200.00, 95, '', '2025-09-21 14:45:13', 'active'),
(755, 'Home & Office Decor', NULL, 'Directory', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(756, 'Home & Office Decor', NULL, 'Wall Frame', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(757, 'Home & Office Decor', NULL, 'Stand Frame', 75.00, 100, '', '2025-09-21 14:45:13', 'active'),
(758, 'Home & Office Decor', NULL, '3D Frame', 100.00, 97, '', '2025-09-21 14:45:13', 'active'),
(759, 'Printing & Stationery', NULL, 'Document Printing', 20.00, 100, '', '2025-09-21 14:45:13', 'active'),
(760, 'Printing & Stationery', NULL, 'Magazine', 50.00, 99, '', '2025-09-21 14:45:13', 'active'),
(761, 'Printing & Stationery', NULL, 'Brochure', 50.00, 97, '', '2025-09-21 14:45:13', 'active'),
(762, 'Printing & Stationery', NULL, 'Bookbinding', 300.00, 97, '', '2025-09-21 14:45:13', 'active'),
(763, 'Printing & Stationery', NULL, 'Yearbook', 300.00, 99, '', '2025-09-21 14:45:13', 'active'),
(764, 'Printing & Stationery', NULL, 'Programme', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(765, 'Printing & Stationery', NULL, 'Booklet', 50.00, 100, '', '2025-09-21 14:45:13', 'active'),
(766, 'Printing & Stationery', NULL, 'Photo Printing', 100.00, 100, '', '2025-09-21 14:45:13', 'active'),
(767, 'Printing & Stationery', NULL, 'Banner', 75.00, 99, '', '2025-09-21 14:45:13', 'active'),
(768, 'Printing & Stationery', NULL, 'Sticker', 20.00, 97, '', '2025-09-21 14:45:13', 'active'),
(769, 'Printing & Stationery', NULL, 'Calling Card', 30.00, 98, '', '2025-09-21 14:45:13', 'active'),
(770, 'Printing & Stationery', NULL, 'Invitation Card', 40.00, 55, '', '2025-09-21 14:45:13', 'inactive'),
(771, 'Printing & Stationery', NULL, 'Invitation Card', 40.00, 129, '', '2025-10-06 09:12:46', 'active'),
(772, 'Human', NULL, 'Lorenxon Nic', 500.00, 1, '', '2025-11-11 17:11:06', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `summary`
--

CREATE TABLE IF NOT EXISTS `summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_sales` double DEFAULT 0,
  `total_revenue` double DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `summary`
--

INSERT INTO `summary` (`id`, `total_sales`, `total_revenue`) VALUES
(1, 5, 3850);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','photographer','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username_2` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `fullname`, `username`, `email`, `password_hash`, `role`, `created_at`, `status`, `profile_image`) VALUES
(1, NULL, 'Alvin Joseph Gumapac', 'admin', 'admin@example.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '2025-09-20 10:07:29', 'active', NULL),
(2, NULL, 'admin1', 'admin1', 'admin1@example.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '2025-09-20 10:07:29', 'active', NULL),
(9, NULL, 'Kris Louis Gabriel Luna', 'krisloi', 'krisloi@gmail.com', '$2y$10$orCJTTtlJXKVgswSRXSPhOYG4RUsp7TMMmRhoLfRl45ZIZSv2XKyy', 'customer', '2025-10-02 20:46:15', 'active', NULL),
(10, NULL, 'Christian Robrado Narvaez', 'narvaezgwapo@gmail.com', 'narvaezgwapo@gmail.com', '$2y$10$h1K6BsLqNMu2RwYpVdnjkerwG8QDRobeX7dTUI4tn/JF3RBqU7E8K', 'customer', '2025-10-07 04:50:42', 'active', NULL),
(11, NULL, 'Lorenxon Nic C. Batoctoy', 'rinaxlovesoysoy123@gmail.com', 'rinaxlovesoysoy123@gmail.com', '$2y$10$eyaU6W2FKMN/zfRgSMc7cu31SIzYkafZFoFwNLA19rZCRVLOJyd02', 'customer', '2025-10-07 06:19:28', 'active', NULL),
(12, NULL, 'Gaby Luna', 'gabyluna@gmail.com', 'gabyluna@gmail.com', '$2y$10$OGSzquAOsSvzxpI0Jq1yTOVF37jU4nUgrrLxZ0jM7CXACoNgQ3jA6', 'customer', '2025-10-07 07:29:42', 'active', NULL),
(13, NULL, 'Riofel Rohan Subsuban Augusto', 'rohangay@gmail.com', 'rohangay@gmail.com', '$2y$10$TSn1zTHAV7UwBxpbWwdoQ.5rNxAGp.M5odorFX6crv41TTgm3rntS', 'customer', '2025-10-08 23:20:53', 'active', NULL),
(14, NULL, 'Gwen Domine Aninao', 'gwengay@gmail.com', 'gwengay@gmail.com', '$2y$10$UtUR18XNwqgBqPRiWEpieu3.5pcOy9IJ0KwlfAqcC6tN9NSXG6HL6', 'customer', '2025-10-09 01:31:52', 'active', NULL),
(15, NULL, 'Lorenxon Nic C. Batoctoy', 'gwapo123@gmail.com', 'gwapo123@gmail.com', '$2y$10$n7/gWWNR4XMnRSXm.3zSdu88p557.ValQV0ORXSanQXZw8bVOJ5ZC', 'customer', '2025-10-09 02:01:17', 'active', NULL),
(16, NULL, 'Mark Lloyd Clave', 'marklloyd', 'marklloyd@marcomedia.com', '3669e236ef5af6d2b5a68fd544bc33726e6e2477e3abc883dc94209d1d0ce373', 'photographer', '2025-10-12 13:44:04', 'active', NULL),
(17, NULL, 'Mark Rebyn Ilaya', 'markrebyn', 'markrebyn@marcomedia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'photographer', '2025-10-12 13:44:04', 'active', NULL),
(18, NULL, 'Jhon Bien Lordson', 'lordson', 'lordson@marcomedia.com', '3669e236ef5af6d2b5a68fd544bc33726e6e2477e3abc883dc94209d1d0ce373', 'photographer', '2025-10-12 13:44:04', 'inactive', NULL),
(19, NULL, 'Mel Adrian Rondina', 'meladrianrondina@gmail.com', 'meladrianrondina@gmail.com', '$2y$10$lYCsPY.2c3KVOfrgkcsO0ux1NaxkJI6Sb/YL0hn.X2GUFxa5Pc0Cm', 'customer', '2025-10-13 01:02:17', 'active', NULL),
(20, NULL, 'Jabez John Betet', 'jjbetet', 'jjbetet@gmail.com', '$2y$10$1h8mlUulXo8/CGJsaZkMwuWdmRCLmyTHAMz58FnWFd.xSqIXUQXYe', 'customer', '2025-10-27 10:29:29', 'active', NULL),
(21, NULL, 'Jabez John Betett', 'jjbetet_1', 'jjbetet_1@gmail.com', '$2y$10$pBWyNsaaCgbQtVxWwgMZzeqAd7UvonQzxQj3NF7pBmmyjd44Q76Da', 'customer', '2025-10-28 00:38:45', 'active', NULL),
(22, NULL, 'Jabez John Betettt', 'jjbetet_2', 'jjbetet_2@gmail.com', '$2y$10$yZMiwWIkMDyEgxt7YSOFqeV37qUIbR0hyaA4.fa3JqisSr1gPOWd2', 'customer', '2025-10-28 01:00:37', 'active', NULL),
(23, NULL, 'Forice Mae Batoctoy', 'test', 'test@example.com', '$2y$10$s.SU1S7LIqs7Gm0bacXuJ.uoiQ3N4I03lo2/1.o9QsII3wyRWw3ie', 'customer', '2025-11-07 07:42:46', 'active', NULL),
(24, NULL, 'Rinax Antonio', 'admin3', 'rinax@admin.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '2025-11-12 07:06:29', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `appointment_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 10, 30, 'Your booking #30 has been approved!', 'success', 1, '2025-10-19 06:18:03'),
(2, 13, 31, 'Your booking #31 has been approved!', 'success', 0, '2025-10-28 05:54:29'),
(3, 23, 34, 'Your appointment #34 status has been updated to: Cancelled', 'info', 1, '2025-11-08 01:34:30'),
(4, 23, 33, 'Your booking #33 has been approved!', 'success', 1, '2025-11-08 01:34:31'),
(5, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-08 01:43:21'),
(6, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-08 01:43:31'),
(7, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-08 01:43:41'),
(8, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-11 02:54:25'),
(9, 23, 34, 'Your appointment #34 status has been updated to: Pending', 'info', 1, '2025-11-11 02:54:39'),
(10, 23, 34, 'Your appointment #34 status has been updated to: Pending', 'info', 1, '2025-11-11 02:54:42'),
(11, 23, 34, 'Your appointment #34 status has been updated to: Cancelled', 'info', 1, '2025-11-11 02:54:50'),
(12, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-11 02:55:04'),
(13, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-12 02:08:02'),
(14, 23, 34, 'Your appointment #34 status has been updated to: Approved', 'info', 1, '2025-11-12 02:08:06'),
(15, 23, 35, 'Your appointment #35 status has been updated to: Cancelled', 'info', 1, '2025-11-12 02:18:10'),
(16, 23, 35, 'Your appointment #35 status has been updated to: Pending', 'info', 1, '2025-11-12 02:18:48'),
(17, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 02:18:51'),
(18, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 02:18:57'),
(19, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 04:42:15'),
(20, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 04:51:14'),
(21, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 04:51:17'),
(22, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 04:52:07'),
(23, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 07:03:00'),
(24, 23, 35, 'Your appointment #35 status has been updated to: Approved', 'info', 1, '2025-11-12 10:45:10');

-- --------------------------------------------------------

--
-- Table structure for table `website_orders`
--

CREATE TABLE IF NOT EXISTS `website_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `website_orders`
--

INSERT INTO `website_orders` (`id`, `user_id`, `product_id`, `quantity`, `status`, `created_at`) VALUES
(1, 1, 770, 1, 'Pending', '2025-09-30 18:57:42'),
(2, 1, 770, 1, 'Pending', '2025-09-30 19:02:04'),
(3, 1, 770, 1, 'Pending', '2025-09-30 19:09:08'),
(4, 1, 768, 17, 'Pending', '2025-09-30 19:18:28'),
(5, 1, 769, 1, 'Pending', '2025-09-30 19:19:00'),
(6, 1, 769, 1, 'Pending', '2025-10-02 05:03:56'),
(7, 1, 770, 1, 'Pending', '2025-10-02 05:03:56'),
(8, 1, 770, 1, 'Pending', '2025-10-02 05:05:04'),
(9, 1, 770, 1, 'Pending', '2025-10-02 05:15:31'),
(10, 1, 763, 1, 'Pending', '2025-10-02 07:42:58'),
(11, 9, 770, 1, 'Pending', '2025-10-03 01:36:37'),
(12, 9, 770, 1, 'Pending', '2025-10-03 05:24:38');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bill_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_product` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `saved_designs`
--
ALTER TABLE `saved_designs`
  ADD CONSTRAINT `saved_designs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_designs_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `stock` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `website_orders`
--
ALTER TABLE `website_orders`
  ADD CONSTRAINT `website_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `website_orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
