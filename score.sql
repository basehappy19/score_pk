-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 29, 2024 at 05:43 PM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `score`
--

-- --------------------------------------------------------

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
CREATE TABLE IF NOT EXISTS `round` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `label_sec` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `des` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT NULL,
  `is_new` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `round`
--

INSERT INTO `round` (`id`, `label`, `label_sec`, `des`, `code`, `year`, `is_enabled`, `is_new`, `created_at`, `updated_at`) VALUES
(1, 'ห้องเรียนพิเศษ ม.4', 'ประเภทห้องเรียนพิเศษ ม.4', 'ตรวจสอบคะแนนสอบการรับนักเรียนห้องเรียนพิเศษ ม.4 ปีการศึกษา 2567', 'gifted4', '2567', 1, 1, NULL, NULL),
(3, 'ห้องเรียนพิเศษ ม.1', 'ประเภทห้องเรียนพิเศษ ม.4', 'ตรวจสอบคะแนนสอบการรับนักเรียนห้องเรียนพิเศษ ม.1 ปีการศึกษา 2567', 'gifted1', '2567', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `round_attr`
--

DROP TABLE IF EXISTS `round_attr`;
CREATE TABLE IF NOT EXISTS `round_attr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `round_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `round_year` int DEFAULT NULL,
  `label_attr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_score` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `round_attr`
--

INSERT INTO `round_attr` (`id`, `key`, `round_code`, `round_year`, `label_attr`, `max_score`, `created_at`, `updated_at`) VALUES
(1, 'sci', 'gifted4', 2567, 'วิทยศาสตร์', 100, NULL, NULL),
(2, 'math', 'gifted4', 2567, 'คณิตศาสตร์', 100, NULL, NULL),
(3, 'eng', 'gifted4', 2567, 'อังกฤษ', 100, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `id` int NOT NULL AUTO_INCREMENT,
  `round_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `round_year` int DEFAULT NULL,
  `cid` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fullname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reg_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reg_type_label` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_score`
--

DROP TABLE IF EXISTS `student_score`;
CREATE TABLE IF NOT EXISTS `student_score` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT '0',
  `round_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `round_attr_key` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score` float DEFAULT '0',
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
