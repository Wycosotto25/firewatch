-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 05:25 AM
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
-- Database: `firewatch_db1`
--

-- --------------------------------------------------------

--
-- Table structure for table `actuator_state`
--

CREATE TABLE `actuator_state` (
  `id` int(10) UNSIGNED NOT NULL,
  `pump` tinyint(1) NOT NULL DEFAULT 0,
  `buzzer` tinyint(1) NOT NULL DEFAULT 0,
  `fan` tinyint(1) NOT NULL DEFAULT 0,
  `emergency` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `manual_override` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `incident_type` enum('fire','gas','manual','emergency','temp') NOT NULL,
  `temperature` decimal(5,2) NOT NULL DEFAULT 0.00,
  `humidity` decimal(5,2) NOT NULL DEFAULT 0.00,
  `gas_level` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `flame_detected` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_data`
--

CREATE TABLE `sensor_data` (
  `id` int(10) UNSIGNED NOT NULL,
  `temperature` decimal(5,2) NOT NULL DEFAULT 0.00,
  `humidity` decimal(5,2) NOT NULL DEFAULT 0.00,
  `gas_level` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `flame_detected` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `created_at`) VALUES
(1, 'Admin User', 'admin@firewatch.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-06-21 22:02:28'),
(2, 'Sotto, Wyco', 'wycosottojan25@gmail.com', '$2y$10$ua.T5Olh78fKJ.E6.gMnfeljIDmkhwAkj1O0dL3caHN2pWBto1KhC', '2026-06-21 22:26:17'),
(3, 'JM Noleal', 'jmnoleal@123.local', '$2y$10$IPM3R7WBIJTK.Ggc.3gZluRrr36EwHWcc9KO03lbku7arZKDBySiO', '2026-06-22 00:01:15'),
(4, 'Cj-win riliera', 'cjrillera@gmail.com', '$2y$10$Ht2CWjj3vcjqxumfB9ny.eaO6AN0OkUj66qvGrdrexKWW3KsSWFcW', '2026-06-25 11:46:33'),
(5, 'NiksWycsCjcsJohnsJils', 'leadernikka@firewatch.local', '$2y$10$bLwa5gnumOTzpg5I4FNvwOWn5HUSQdYzwuQfkYMU33T1dbxytupaW', '2026-06-25 14:01:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actuator_state`
--
ALTER TABLE `actuator_state`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

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
-- AUTO_INCREMENT for table `actuator_state`
--
ALTER TABLE `actuator_state`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
