-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 08:25 AM
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
-- Database: `waste_sorting`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `account_status` enum('active','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `username`, `password`, `role`, `account_status`) VALUES
(1, 'ทดสอบ', 'test01', '$2y$10$EhrYxUAImBH/H7xTRQNhlutDKA93sEpJCpezKTfyvS3WNrLlAyfHC', 'student', 'active'),
(2, 'เจ้าหน้าที่ระบบ', 'admin', '$2y$10$/YVr8st22OISF6GaihDonOCiMPZQLlggCOTdrucG.x4q1TZ4O4gPK', 'admin', 'active'),
(4, 'วุฒิชัย', 'test02', '$2y$10$a5CgOoL2QmMaqCekKJk/A.E/mp55UOzKZfD8vbSzDr6CXoO2bftY2', 'student', 'active'),
(5, 'ศิริชัย', 'admin2', '$2y$10$fsKLMQb/hyPTxQgK3IqPl.RlaDfnzgm06qNDVfmnzIoC8nEwd.6mO', 'admin', 'active'),
(6, 'วันชัย', 'test03', '$2y$10$xjWqDBGc3Fryv0htTof09uX7c6AiS1Wzsb329JnYzgsn3CArvuV2O', 'student', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `user_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_points`
--

INSERT INTO `user_points` (`user_id`, `total_points`, `updated_at`) VALUES
(1, 4, '2026-08-25 03:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `waste_approvals`
--

CREATE TABLE `waste_approvals` (
  `approval_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `points_awarded` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waste_approvals`
--

INSERT INTO `waste_approvals` (`approval_id`, `record_id`, `admin_id`, `status`, `points_awarded`, `remark`, `approved_at`) VALUES
(1, 7, 2, 'approved', 4, 'อนุมัติรายการ', '2026-08-25 03:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `waste_records`
--

CREATE TABLE `waste_records` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waste_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waste_records`
--

INSERT INTO `waste_records` (`record_id`, `user_id`, `waste_id`, `quantity`, `image`, `status`, `created_at`, `points_earned`, `reviewed_at`) VALUES
(1, 1, 5, 5, NULL, 'approved', '2026-08-02 06:42:08', 5, '2026-08-03 17:09:29'),
(2, 1, 5, 5, NULL, 'pending', '2026-08-03 08:51:13', 0, NULL),
(4, 1, 4, 3, NULL, 'pending', '2026-08-03 09:30:51', 0, NULL),
(5, 1, 4, 2, 'e691bb456c3c31dd0270de72c9a258af.jpg', 'approved', '2026-08-24 16:17:19', 2, '2026-08-24 23:18:06'),
(6, 1, 4, 5, NULL, 'pending', '2026-08-25 02:55:57', 0, NULL),
(7, 1, 5, 2, NULL, 'approved', '2026-08-25 03:32:43', 4, '2026-08-25 10:37:45'),
(8, 1, 3, 5, 'f28d3e6dda65f9ad3812a57c4efac523.jpg', 'pending', '2026-08-25 04:52:02', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `waste_types`
--

CREATE TABLE `waste_types` (
  `waste_id` int(11) NOT NULL,
  `waste_name` varchar(100) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waste_types`
--

INSERT INTO `waste_types` (`waste_id`, `waste_name`, `points`) VALUES
(1, 'พลาสติก', 1),
(2, 'กระดาษ', 1),
(3, 'แก้ว', 1),
(4, 'โลหะและอลูมิเนียม', 3),
(5, 'กล่องเครื่องดื่ม', 5),
(7, 'กล่อง', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `waste_approvals`
--
ALTER TABLE `waste_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD UNIQUE KEY `record_id` (`record_id`),
  ADD KEY `fk_approval_admin` (`admin_id`);

--
-- Indexes for table `waste_records`
--
ALTER TABLE `waste_records`
  ADD PRIMARY KEY (`record_id`);

--
-- Indexes for table `waste_types`
--
ALTER TABLE `waste_types`
  ADD PRIMARY KEY (`waste_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `waste_approvals`
--
ALTER TABLE `waste_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `waste_records`
--
ALTER TABLE `waste_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `waste_types`
--
ALTER TABLE `waste_types`
  MODIFY `waste_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `fk_user_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `waste_approvals`
--
ALTER TABLE `waste_approvals`
  ADD CONSTRAINT `fk_approval_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_approval_record` FOREIGN KEY (`record_id`) REFERENCES `waste_records` (`record_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
