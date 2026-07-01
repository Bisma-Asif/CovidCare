-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 12:26 AM
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
-- Database: `chk_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `first_name`, `last_name`, `admin_email`, `admin_password`, `created_at`) VALUES
(2, 'Admin', 'Tarfia', 'admintarfia@gmail.com', 'admin#321', '2025-09-12 13:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `test_type` enum('covid_test','vaccination') NOT NULL,
  `vaccine_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','confirmed','cancelled') DEFAULT 'pending',
  `result_status` enum('pending','positive','negative','vaccinated','not_vaccinated') DEFAULT 'pending',
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `hospital_id`, `test_type`, `vaccine_id`, `status`, `result_status`, `scheduled_date`, `scheduled_time`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'covid_test', NULL, 'confirmed', 'negative', '2025-09-17', '10:00:00', '2025-09-15 00:07:23', '2025-09-16 03:23:21'),
(2, 3, 4, 'covid_test', NULL, 'approved', 'positive', NULL, NULL, '2025-09-15 00:09:04', '2025-09-16 04:32:01'),
(5, 4, 5, 'covid_test', NULL, 'confirmed', 'positive', '2025-09-18', '11:30:00', '2025-09-15 19:39:30', '2025-09-16 21:42:24'),
(6, 3, 5, 'covid_test', NULL, 'confirmed', 'negative', '2025-09-18', '11:00:00', '2025-09-16 00:37:34', '2025-09-16 09:42:20'),
(7, 1, 4, 'vaccination', 1, 'confirmed', 'vaccinated', '2025-09-16', '02:00:00', '2025-09-16 03:27:24', '2025-09-16 18:31:44'),
(8, 1, 2, 'covid_test', NULL, 'confirmed', 'negative', '2025-09-18', '02:00:00', '2025-09-16 04:46:50', '2025-09-16 09:33:14'),
(9, 1, 3, 'covid_test', NULL, 'confirmed', 'pending', '2025-09-16', '02:36:00', '2025-09-16 04:47:06', '2025-09-16 09:36:49'),
(10, 4, 5, 'covid_test', NULL, 'approved', 'pending', NULL, NULL, '2025-09-16 06:08:48', '2025-09-16 14:26:59'),
(11, 4, 4, 'covid_test', NULL, 'rejected', 'pending', NULL, NULL, '2025-09-16 15:16:38', '2025-09-16 15:17:50'),
(12, 5, 4, 'covid_test', NULL, 'approved', 'pending', NULL, NULL, '2025-09-16 18:19:24', '2025-09-16 18:28:53'),
(13, 5, 4, 'covid_test', NULL, 'pending', 'pending', NULL, NULL, '2025-09-16 18:27:58', '2025-09-16 18:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `hospital`
--

CREATE TABLE `hospital` (
  `h_id` int(11) NOT NULL,
  `h_firstname` varchar(50) NOT NULL,
  `h_lastname` varchar(50) NOT NULL,
  `h_email` varchar(100) NOT NULL,
  `h_password` varchar(255) NOT NULL,
  `h_phone` varchar(15) NOT NULL,
  `h_license` varchar(100) NOT NULL,
  `h_address` text NOT NULL,
  `h_city` varchar(50) NOT NULL,
  `h_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital`
--

INSERT INTO `hospital` (`h_id`, `h_firstname`, `h_lastname`, `h_email`, `h_password`, `h_phone`, `h_license`, `h_address`, `h_city`, `h_status`, `created_at`) VALUES
(1, 'efedg', 'rger', 'rft@gmail.com', '789654321', '03512596346', '9865', 'erfge', 'ferf', 'rejected', '2025-09-12 02:32:49'),
(2, 'Jinnah', 'Hospital', 'jinnah@gmail.com', 'jinnah#321', '03359468692', '78963', 'Stadium Road', 'karachi', 'approved', '2025-09-12 19:58:06'),
(3, 'Liaquat', 'National', 'liaquat@gmail.com', 'liaquat#321', '03212976590', '13652', 'Stadium Road', 'karachi', 'approved', '2025-09-12 20:07:47'),
(4, 'Agha', 'Khan', 'agha@gmail.com', 'agha#321', '03176859987', '32650', 'Stadium Road', 'Karachi', 'approved', '2025-09-12 20:42:46'),
(5, 'Atia', 'Hospital', 'atia@gmail.com', 'atia#321', '03119684320', '85637', 'Kala board', 'Karachi', 'approved', '2025-09-12 21:44:03'),
(6, 'Dow', 'Medical', 'dow@gmail.com', 'dowhos#321', '03112865230', '20120', 'Landhi', 'Karachi', 'approved', '2025-09-13 15:23:42');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `p_id` int(11) NOT NULL,
  `p_firstname` varchar(50) NOT NULL,
  `p_lastname` varchar(50) NOT NULL,
  `p_email` varchar(100) NOT NULL,
  `p_password` varchar(255) NOT NULL,
  `p_phone` varchar(15) NOT NULL,
  `p_dob` date NOT NULL,
  `p_gender` enum('male','female') NOT NULL,
  `p_address` text NOT NULL,
  `p_city` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`p_id`, `p_firstname`, `p_lastname`, `p_email`, `p_password`, `p_phone`, `p_dob`, `p_gender`, `p_address`, `p_city`, `created_at`) VALUES
(1, 'Tarfia', 'Rashid', 'tarfia@gmail.com', 'tarfia#321', '03352139000', '2006-10-03', 'female', 'Landhi', 'karachi', '2025-09-12 02:29:12'),
(3, 'Aisha', 'Amin', 'aisha@gmail.com', 'aisha#321', '03176032240', '2004-09-09', 'female', 'Malir', 'Karachi', '2025-09-13 22:51:50'),
(4, 'Ali', 'khan', 'ali@gmail.com', 'ali#321', '03392001136', '2004-12-01', 'male', 'Sadar', 'Karachi', '2025-09-15 19:38:20'),
(5, 'Maha', 'Sheikh', 'maha@gmail.com', 'maha#321', '03379853010', '2005-01-15', 'female', 'Malir', 'Karachi', '2025-09-16 18:18:17');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_stock` int(11) DEFAULT 0,
  `used_stock` int(11) DEFAULT 0,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `total_stock`, `used_stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Pfizer-BioNTech', 0, 0, 'available', '2025-09-13 11:37:40', '2025-09-15 20:39:44'),
(2, 'Moderna', 20, 0, 'available', '2025-09-13 11:37:40', '2025-09-13 13:40:46'),
(3, 'AstraZeneca', 20, 0, 'available', '2025-09-13 11:37:40', '2025-09-13 13:41:06'),
(4, 'Johnson & Johnson', 20, 0, 'unavailable', '2025-09-13 11:37:40', '2025-09-13 13:52:09'),
(5, 'Sinovac', 20, 0, 'available', '2025-09-13 11:37:40', '2025-09-13 13:52:44'),
(6, 'Sputnik V', 20, 0, 'available', '2025-09-13 11:37:40', '2025-09-13 13:41:26'),
(7, 'Novavax', 20, 0, 'available', '2025-09-13 11:37:40', '2025-09-13 13:41:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointments_patient` (`patient_id`),
  ADD KEY `fk_appointments_hospital` (`hospital_id`),
  ADD KEY `fk_appointments_vaccines` (`vaccine_id`);

--
-- Indexes for table `hospital`
--
ALTER TABLE `hospital`
  ADD PRIMARY KEY (`h_id`),
  ADD UNIQUE KEY `h_email` (`h_email`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`p_id`),
  ADD UNIQUE KEY `p_email` (`p_email`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hospital`
--
ALTER TABLE `hospital`
  MODIFY `h_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`h_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`p_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_vaccines` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
