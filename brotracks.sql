-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 07:51 AM
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
-- Database: `brotracks`
--

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `school_name` varchar(150) DEFAULT NULL,
  `class` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `parent_id`, `name`, `school_name`, `class`) VALUES
(1, 1, 'Shiva', 'Christ Nagar School,Tvlm', '5'),
(2, 3, 'Julie', 'Christ Nagar', '7');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `user_id`, `license_no`, `vehicle_no`, `status`) VALUES
(1, 3, '', '', 'approved'),
(2, 3, '', '', ''),
(3, 5, '', '', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `driver_locations`
--

CREATE TABLE `driver_locations` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_locations`
--

INSERT INTO `driver_locations` (`id`, `driver_id`, `latitude`, `longitude`, `updated_at`) VALUES
(1, 1, '9.2029412', '76.4505243', '2025-12-07 12:29:23'),
(2, 1, '9.2029412', '76.4505243', '2025-12-07 16:51:54'),
(3, 1, '9.2029412', '76.4505243', '2025-12-07 16:51:59'),
(4, 1, '8.4521832', '76.9594728', '2025-12-07 16:58:01'),
(5, 1, '8.4521832', '76.9594728', '2025-12-07 16:58:12'),
(6, 1, '8.4521832', '76.9594728', '2025-12-07 16:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_role` enum('all','parent','driver') DEFAULT 'all',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `target_role`, `created_at`) VALUES
(1, 'School Bus delayed', 'There is a break trouble issue', 'all', '2025-12-07 22:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `user_id`, `address`) VALUES
(1, 1, 'Trivandrum'),
(2, 2, 'Kollam'),
(3, 4, 'Trivandrum');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `plan`, `amount`, `transaction_id`, `status`, `created_at`) VALUES
(1, 1, 'basic', 0.00, 'TXN-B316D133-20251207', 'success', '2025-12-07 11:44:00'),
(2, 1, 'basic', 0.00, 'TXN-E19AC115-20251207', 'success', '2025-12-07 11:44:00'),
(3, 1, 'basic', 0.00, 'TXN-2831B212-20251207', 'success', '2025-12-07 11:44:01'),
(4, 1, 'basic', 0.00, 'TXN-56E1FAAC-20251207', 'success', '2025-12-07 11:44:01'),
(5, 1, 'basic', 0.00, 'TXN-DF949126-20251207', 'success', '2025-12-07 11:44:01'),
(6, 1, 'basic', 0.00, 'TXN-731AFC8F-20251207', 'success', '2025-12-07 11:44:04'),
(7, 1, 'basic', 0.00, 'TXN-452D092C-20251207', 'success', '2025-12-07 11:44:06'),
(8, 1, 'basic', 0.00, 'TXN-D5A61D78-20251207', 'success', '2025-12-07 11:44:17'),
(9, 1, 'basic', 0.00, 'TXN-B4CA176A-20251207', 'success', '2025-12-07 11:44:19'),
(10, 1, 'basic', 0.00, 'TXN-2F8CCE9C-20251207', 'success', '2025-12-07 11:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `recurring_rides`
--

CREATE TABLE `recurring_rides` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `days_of_week` varchar(50) NOT NULL,
  `time` time NOT NULL,
  `status` enum('active','paused') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `drop_location` text NOT NULL,
  `ride_date` date NOT NULL,
  `ride_time` time NOT NULL,
  `status` enum('requested','accepted','on_going','completed','cancelled') DEFAULT 'requested',
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`id`, `child_id`, `parent_id`, `driver_id`, `pickup_location`, `drop_location`, `ride_date`, `ride_time`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 'Attukal', 'Christ Nagar School,Tvlm', '2025-12-08', '07:30:00', 'completed', '2025-12-06 19:30:16'),
(2, 1, 1, 1, 'Tvlm', 'Eastfort', '2025-12-07', '08:00:00', 'completed', '2025-12-06 20:00:59'),
(3, 2, 3, 3, 'Manacaud', 'Thiruvallam', '2025-12-08', '08:30:00', 'accepted', '2025-12-06 22:30:22'),
(4, 1, 1, 1, 'tvlm', 'manacaud', '2025-12-09', '09:00:00', 'completed', '2025-12-07 17:55:32'),
(5, 1, 1, 1, 'Tvlm', 'Attukal', '2025-12-08', '10:29:00', 'completed', '2025-12-07 22:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `tracking`
--

CREATE TABLE `tracking` (
  `id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `recorded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking`
--

INSERT INTO `tracking` (`id`, `ride_id`, `latitude`, `longitude`, `recorded_at`) VALUES
(1, 2, 9.2029410, 76.4505240, '2025-12-06 22:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('parent','driver','admin') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `created_at`) VALUES
(1, 'Satheesh', 'satheesh123@gmail.com', '$2y$10$TqGoU5YL3Z4l1B9MuY936u/TohuI9zBSBr2TUuvjyRo4uAT5bKpmO', 'parent', '9840358375', '2025-12-06 19:21:16'),
(2, 'Rahul', 'rahul123@gmail.com', '$2y$10$tr2Kx7KkLqoZYGzVvgCgdOfTYDxTs6pPu4mQZsnakX8CKBqtscNDG', 'parent', '9034950345', '2025-12-06 19:35:21'),
(3, 'Sumesh', 'sumesh123@gmail.com', '$2y$10$yS58glYJCa4L3K4MmcfD5OLz0aplKeLKf7te6byNCtMdn7kcuh4Ty', 'driver', '9067098678', '2025-12-06 19:36:46'),
(4, 'JacKy', 'jacky123@gmail.com', '$2y$10$k8AzUW.2RgEwKtX90CN9L.52Rvr5NbZAtcIHCDkQsGg3X5JLQiZsG', 'parent', '9083476583', '2025-12-06 22:28:36'),
(5, 'Shibhu', 'shibu123@gmail.com', '$2y$10$tvJius1IrDSErWqJLThwqe8acjSGU.rc5N375BV4ikR0V1GDkWkmW', 'driver', '8239587264', '2025-12-06 22:31:41'),
(6, 'SuperAdmin', 'admin@brotracks.com', '$2y$10$T3eMqFhFdThdS4C3HS2XeORybgFuwVtU1JHYmjx5r3cQo41NTpxj2', 'admin', '0000000000', '2025-12-07 17:34:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recurring_rides`
--
ALTER TABLE `recurring_rides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `tracking`
--
ALTER TABLE `tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ride_id` (`ride_id`);

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
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `driver_locations`
--
ALTER TABLE `driver_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `recurring_rides`
--
ALTER TABLE `recurring_rides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tracking`
--
ALTER TABLE `tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD CONSTRAINT `driver_locations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recurring_rides`
--
ALTER TABLE `recurring_rides`
  ADD CONSTRAINT `recurring_rides_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_rides_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_rides_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rides_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tracking`
--
ALTER TABLE `tracking`
  ADD CONSTRAINT `tracking_ibfk_1` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
