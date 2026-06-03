-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 18, 2026 at 03:17 PM
-- Server version: 5.7.44
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-
--

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `qrname` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `finalurl` varchar(500) NOT NULL,
  `qrimage` text,
  `count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `qrname`, `url`, `finalurl`, `qrimage`, `count`, `created_at`, `updated_at`) VALUES
(12, 'Attendance', 'https://igipess.du.ac.in/QR-Code/qr-page.php?data=QznvczZ04nzotWouQ7ZETpKqnle917rCEl9tmQ9clTs', 'https://igipess.du.ac.in/Student-Attendance-Report', 'assets/qrcodes/qr_12_69b7e4619bdf6_1773659233.png', 7, '2026-03-16 11:07:13', '2026-03-18 07:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `id` int(11) NOT NULL,
  `qr_id` int(11) NOT NULL,
  `qr_name` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `device_os` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `device_model` varchar(100) DEFAULT NULL,
  `screen_resolution` varchar(20) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `isp` varchar(200) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `referer` text,
  `scan_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `day_of_week` varchar(20) DEFAULT NULL,
  `hour_of_day` int(11) DEFAULT NULL,
  `is_mobile` tinyint(1) DEFAULT '0',
  `is_tablet` tinyint(1) DEFAULT '0',
  `is_desktop` tinyint(1) DEFAULT '0',
  `is_robot` tinyint(1) DEFAULT '0',
  `scan_duration` float DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`id`, `qr_id`, `qr_name`, `ip_address`, `device_type`, `device_os`, `os_version`, `browser`, `browser_version`, `user_agent`, `device_model`, `screen_resolution`, `language`, `country`, `city`, `region`, `isp`, `timezone`, `referer`, `scan_time`, `day_of_week`, `hour_of_day`, `is_mobile`, `is_tablet`, `is_desktop`, `is_robot`, `scan_duration`, `session_id`) VALUES
(14, 14, 'Result', '14.139.227.83', 'Mobile', 'Android', '10', 'Chrome', '145.0.0.0', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', 'Unknown', '389x863', 'en-US', 'India', 'New Delhi', 'National Capital Territory of Delhi', 'National Knowledge Network', 'Asia/Kolkata', NULL, '2026-03-16 05:38:06', 'Monday', 11, 1, 0, 0, 0, 0.001, 'ec6b2b273c4a83cfbca9c97a57a02343');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_qrname` (`qrname`);

--
-- Indexes for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qr_id` (`qr_id`),
  ADD KEY `scan_time` (`scan_time`),
  ADD KEY `device_type` (`device_type`),
  ADD KEY `country` (`country`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD CONSTRAINT `qr_scan_logs_ibfk_1` FOREIGN KEY (`qr_id`) REFERENCES `qr_codes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
