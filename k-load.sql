-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 19, 2020 at 03:24 AM
-- Server version: 10.3.10-MariaDB
-- PHP Version: 7.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `k-load-tmp`
--

-- --------------------------------------------------------

--
-- Table structure for table `kload_sessions`
--

CREATE TABLE `kload_sessions` (
  `steamid` bigint(20) NOT NULL COMMENT 'steamid, e.g. 76561198152390718',
  `token` varchar(64) NOT NULL COMMENT 'csrf token',
  `expires` timestamp NULL DEFAULT NULL COMMENT 'date at which token is invalid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Triggers `kload_sessions`
--
DELIMITER $$
CREATE TRIGGER `csrf_fix_insert` BEFORE INSERT ON `kload_sessions` FOR EACH ROW SET NEW.expires = TIMESTAMPADD(DAY,1,CURRENT_TIMESTAMP)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `csrf_fix_update` BEFORE UPDATE ON `kload_sessions` FOR EACH ROW SET NEW.expires = TIMESTAMPADD(DAY,1,CURRENT_TIMESTAMP)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kload_settings`
--

CREATE TABLE `kload_settings` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kload_settings`
--

INSERT INTO `kload_settings` (`name`, `value`) VALUES
('backgrounds', '{\"duration\":8000,\"fade\":750,\"enable\":0,\"random\":0}'),
('community_name', 'K-Load'),
('description', 'Sample description'),
('messages', '{\"list\":[]}'),
('music', '{\"enable\":0,\"random\":0,\"volume\":15,\"order\":[]}'),
('rules', '{\"list\":[]}'),
('staff', '{\"list\":[]}'),
('test', '76561198152390718'),
('version', '2.5.4'),
('youtube', '{\"list\":[]}');

-- --------------------------------------------------------

--
-- Table structure for table `kload_users`
--

CREATE TABLE `kload_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'steam name',
  `steamid` bigint(20) NOT NULL COMMENT 'steamid, e.g. 76561198152390718',
  `steamid2` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'steam2 id, e.g. STEAM_...',
  `steamid3` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'steam3 id, e.g. [U:1:...',
  `admin` tinyint(1) DEFAULT 0 COMMENT 'is the user an admin?',
  `perms` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'list of perms, inactive when admin = 0',
  `settings` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'user settings in JSON',
  `custom_css` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'user css for styling',
  `banned` tinyint(1) DEFAULT 0 COMMENT 'is the user banned?',
  `registered` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'date when joined'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kload_sessions`
--
ALTER TABLE `kload_sessions`
  ADD UNIQUE KEY `steamid` (`steamid`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `kload_settings`
--
ALTER TABLE `kload_settings`
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `kload_users`
--
ALTER TABLE `kload_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kload_users_index` (`steamid`,`steamid2`,`steamid3`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kload_users`
--
ALTER TABLE `kload_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
