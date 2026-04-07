-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Apr 06, 2026 at 02:43 PM
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
-- Database: `it_hub_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_lockouts`
--

CREATE TABLE `account_lockouts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL,
  `locked_until` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 12:32:57'),
(2, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 12:35:25'),
(3, 1, 'login', 'User logged in from IP: 127.0.0.1', NULL, NULL, '2026-01-31 14:53:37'),
(4, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 14:54:16'),
(5, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 14:54:53'),
(6, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 14:55:21'),
(7, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 14:59:06'),
(8, 5, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 15:17:18'),
(9, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 15:23:22'),
(10, 1, 'course_updated', 'Updated course ID: 7', NULL, NULL, '2026-01-31 15:24:19'),
(11, 1, 'user_deleted', 'Deleted user ID: 4', NULL, NULL, '2026-01-31 15:24:51'),
(12, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 15:26:18'),
(13, 3, 'enroll_course', 'Enrolled in course: Web Development Bootcamp (ID: 7) via trial', NULL, NULL, '2026-01-31 15:28:26'),
(14, 3, 'enroll_course', 'Enrolled in course: Cybersecurity Essentials (ID: 10) via trial', NULL, NULL, '2026-01-31 15:28:32'),
(15, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 15:29:43'),
(16, 3, 'enroll_course', 'Enrolled in course: Ethical Hacking & Penetration Testing (ID: 13) via trial', NULL, NULL, '2026-01-31 15:33:44'),
(17, 3, 'lesson_completed', 'Completed lesson: Introduction to Databases', NULL, NULL, '2026-01-31 15:34:31'),
(18, 3, 'lesson_completed', 'Completed lesson: Database Design Principles', NULL, NULL, '2026-01-31 15:36:07'),
(19, 3, 'lesson_completed', 'Completed lesson: SQL Basics - SELECT and Queries', NULL, NULL, '2026-01-31 15:36:13'),
(20, 3, 'lesson_completed', 'Completed lesson: SQL Data Manipulation', NULL, NULL, '2026-01-31 15:59:24'),
(21, 3, 'lesson_completed', 'Completed lesson: Advanced SQL and Joins', NULL, NULL, '2026-01-31 16:01:05'),
(22, 3, 'lesson_completed', 'Completed lesson: Advanced SQL Queries and Subqueries', NULL, NULL, '2026-01-31 16:01:09'),
(23, 3, 'lesson_completed', 'Completed lesson: Database Indexing and Performance', NULL, NULL, '2026-01-31 16:01:12'),
(24, 3, 'lesson_completed', 'Completed lesson: Database Security and Permissions', NULL, NULL, '2026-01-31 16:01:14'),
(25, 3, 'lesson_completed', 'Completed lesson: NoSQL Databases', NULL, NULL, '2026-01-31 16:01:17'),
(26, 3, 'lesson_completed', 'Completed lesson: Database Backup and Recovery', NULL, NULL, '2026-01-31 16:01:21'),
(27, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 16:06:33'),
(28, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 16:09:09'),
(29, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 16:32:08'),
(30, 1, 'course_updated', 'Updated course ID: 13', NULL, NULL, '2026-01-31 16:57:36'),
(31, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 17:25:15'),
(32, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 18:04:41'),
(33, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-31 18:07:02'),
(34, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 10:29:10'),
(35, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 12:52:05'),
(36, 3, 'profile_updated', 'Updated profile information', NULL, NULL, '2026-02-01 13:05:14'),
(37, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 13:09:11'),
(38, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 13:10:12'),
(39, 2, 'course_created', 'Created course: test for web', NULL, NULL, '2026-02-01 13:15:06'),
(40, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 13:48:19'),
(41, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 14:58:22'),
(42, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 15:18:38'),
(43, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 15:48:00'),
(44, 3, 'enroll_course', 'Enrolled in course: UI/UX Design Bootcamp (ID: 15) via trial', NULL, NULL, '2026-02-01 15:48:18'),
(45, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 15:49:07'),
(46, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 15:50:51'),
(47, 6, 'enroll_course', 'Enrolled in course: Ethical Hacking &amp; Penetration Testing (ID: 13) via trial', NULL, NULL, '2026-02-01 15:51:00'),
(48, 6, 'enroll_course', 'Enrolled in course: Cybersecurity Fundamentals (ID: 14) via trial', NULL, NULL, '2026-02-01 15:53:05'),
(49, 6, 'enroll_course', 'Enrolled in course: UI/UX Design Bootcamp (ID: 15) via trial', NULL, NULL, '2026-02-01 15:53:16'),
(50, 6, 'enroll_course', 'Enrolled in course: Advanced UX Research & Strategy (ID: 16) via trial', NULL, NULL, '2026-02-01 15:55:57'),
(51, 6, 'enroll_course', 'Enrolled in course: Unreal Engine 5 Game Development (ID: 18) via trial', NULL, NULL, '2026-02-01 16:01:33'),
(52, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 16:07:47'),
(53, 6, 'enroll_course', 'Enrolled in course: Unity Game Development Complete Course (ID: 17) via trial', NULL, NULL, '2026-02-01 16:08:01'),
(54, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 16:12:16'),
(55, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 16:12:30'),
(56, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 16:12:54'),
(57, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-01 16:15:42'),
(58, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-05 11:44:23'),
(59, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 13:26:34'),
(60, 6, 'trial_activity', '{\"activity\":\"reminders_scheduled\",\"course_id\":6,\"description\":\"Trial reminders scheduled\"}', NULL, NULL, '2026-02-12 13:47:24'),
(61, 6, 'trial_activity', '{\"activity\":\"trial_started\",\"course_id\":6,\"description\":\"User enrolled in free trial\"}', NULL, NULL, '2026-02-12 13:47:24'),
(62, 6, 'free_enrollment', 'Free enrollment in course: Full-Stack JavaScript with MERN (ID: 6)', NULL, NULL, '2026-02-12 13:47:24'),
(63, 6, 'trial_activity', '{\"activity\":\"reminders_scheduled\",\"course_id\":10,\"description\":\"Trial reminders scheduled\"}', NULL, NULL, '2026-02-12 13:49:41'),
(64, 6, 'trial_activity', '{\"activity\":\"trial_started\",\"course_id\":10,\"description\":\"User enrolled in free trial\"}', NULL, NULL, '2026-02-12 13:49:41'),
(65, 6, 'free_enrollment', 'Free enrollment in course: Deep Learning with TensorFlow (ID: 10)', NULL, NULL, '2026-02-12 13:49:41'),
(66, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 14:02:45'),
(67, 6, 'trial_activity', '{\"activity\":\"reminders_scheduled\",\"course_id\":19,\"description\":\"Trial reminders scheduled\"}', NULL, NULL, '2026-02-12 14:05:29'),
(68, 6, 'trial_activity', '{\"activity\":\"trial_started\",\"course_id\":19,\"description\":\"User enrolled in free trial\"}', NULL, NULL, '2026-02-12 14:05:29'),
(69, 6, 'free_enrollment', 'Free enrollment in course: Blockchain & Cryptocurrency Complete Course (ID: 19)', NULL, NULL, '2026-02-12 14:05:29'),
(70, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 14:32:05'),
(71, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 14:33:14'),
(72, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 14:37:59'),
(73, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-12 14:38:26'),
(74, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 05:45:05'),
(75, 3, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 05:49:56'),
(76, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 06:02:17'),
(77, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 06:02:55'),
(78, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 06:41:21'),
(79, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 06:42:01'),
(80, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 07:07:00'),
(81, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 07:38:40'),
(82, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 08:01:53'),
(83, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 12:35:47'),
(84, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 15:56:14'),
(85, 6, 'certificate_issued', 'Certificate issued for course: test for web', NULL, NULL, '2026-02-13 17:05:28'),
(86, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 17:18:10'),
(87, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 17:19:00'),
(88, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 17:53:57'),
(89, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-13 17:54:28'),
(90, 2, 'course_deleted', 'Deleted course ID: 8', NULL, NULL, '2026-02-13 17:55:04'),
(91, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 02:58:23'),
(92, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 02:59:04'),
(93, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:00:22'),
(94, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:14:23'),
(95, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:15:46'),
(96, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:19:32'),
(97, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:20:09'),
(98, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-19 17:19:57'),
(99, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 10:28:19'),
(100, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 10:29:09'),
(101, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 10:41:03'),
(102, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 10:43:32'),
(103, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 10:51:33'),
(104, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 11:45:10'),
(105, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 11:49:36'),
(106, 5, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 11:51:18'),
(107, 5, 'profile_updated', 'Updated instructor profile', NULL, NULL, '2026-03-20 11:52:43'),
(108, 5, 'course_created', 'Created course: Example', NULL, NULL, '2026-03-20 11:56:25'),
(109, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 12:00:48'),
(110, 5, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 12:14:57'),
(111, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 12:19:09'),
(112, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 12:20:27'),
(113, 2, 'course_updated', 'Updated course ID: 4', NULL, NULL, '2026-03-20 12:35:01'),
(114, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:36:17'),
(115, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:36:50'),
(116, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:41:12'),
(117, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:47:50'),
(118, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:47:54'),
(119, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:48:02'),
(120, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:51:06'),
(121, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 12:56:32'),
(122, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 13:00:37'),
(123, 2, 'course_updated', 'Updated course ID: 1', NULL, NULL, '2026-03-20 13:01:12'),
(124, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-20 13:40:38'),
(125, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-22 13:33:41'),
(126, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-22 13:34:12'),
(127, 2, 'course_deleted', 'Deleted course ID: 5', NULL, NULL, '2026-03-22 16:34:30'),
(128, 2, 'course_deleted', 'Deleted course ID: 3', NULL, NULL, '2026-03-22 16:34:58'),
(129, 2, 'course_deleted', 'Deleted course ID: 2', NULL, NULL, '2026-03-22 16:35:21'),
(130, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 13:13:53'),
(131, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 13:14:14'),
(132, 1, 'course_deleted', 'Deleted course ID: 14', NULL, NULL, '2026-03-23 13:14:38'),
(133, 1, 'course_deleted', 'Deleted course ID: 14', NULL, NULL, '2026-03-23 13:14:42'),
(134, 1, 'course_deleted', 'Deleted course ID: 14', NULL, NULL, '2026-03-23 13:14:47'),
(135, 1, 'course_deleted', 'Deleted course ID: 14', NULL, NULL, '2026-03-23 13:14:51'),
(136, 1, 'course_deleted', 'Deleted course ID: 14', NULL, NULL, '2026-03-23 13:14:56'),
(137, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 13:18:49'),
(138, 2, 'course_created', 'Created course: Basic Website Development Tranning', NULL, NULL, '2026-03-23 13:22:16'),
(139, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 13:26:19'),
(140, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 13:27:59'),
(141, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 14:51:14'),
(142, 2, 'course_deleted', 'Deleted course: Complete Web Development Bootcamp 2024', NULL, NULL, '2026-03-23 14:51:39'),
(143, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 15:23:05'),
(144, 2, 'course_updated', 'Updated course ID: 7', NULL, NULL, '2026-03-23 15:55:21'),
(145, 2, 'course_updated', 'Updated course ID: 7', NULL, NULL, '2026-03-23 15:55:32'),
(146, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 15:56:20'),
(147, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 16:03:30'),
(148, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 16:56:11'),
(149, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 16:58:48'),
(150, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:05:36'),
(151, 2, 'quiz_created', 'Created quiz: Hello Test', NULL, NULL, '2026-03-23 17:10:47'),
(152, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:15:43'),
(153, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:16:22'),
(154, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:37:11'),
(155, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:38:27'),
(156, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:49:12'),
(157, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 17:54:00'),
(158, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-23 18:02:56'),
(159, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-24 03:19:58'),
(160, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-24 03:20:44'),
(161, 2, 'quiz_created', 'Created quiz: AAAAAAAAAAAAAAAAAAAXXXXXXXXXXXXXXXXXXXXAAAAAAAAAAAAAAAA (ID: 5)', NULL, NULL, '2026-03-24 03:49:29'),
(162, 2, 'quiz_question_created', 'Added question to quiz 8', NULL, NULL, '2026-03-24 04:03:50'),
(163, 2, 'quiz_deleted', 'Deleted quiz ID: 5', NULL, NULL, '2026-03-24 04:04:11'),
(164, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-24 04:04:32'),
(165, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-24 14:26:05'),
(166, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-24 16:02:28'),
(167, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 02:49:06'),
(168, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 03:25:20'),
(169, 2, 'course_created', 'Created course: Data Analysis', NULL, NULL, '2026-03-25 03:27:22'),
(170, 2, 'course_updated', 'Updated course ID: 7', NULL, NULL, '2026-03-25 03:27:42'),
(171, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 03:28:16'),
(172, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 03:32:36'),
(173, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 04:28:58'),
(174, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 05:10:56'),
(175, 8, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 05:11:48'),
(176, 8, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 06:45:50'),
(177, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 16:09:31'),
(178, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 16:11:34'),
(179, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 16:14:39'),
(180, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-25 16:15:00'),
(181, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 12:22:08'),
(182, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 12:24:19'),
(183, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 12:25:12'),
(184, 2, 'course_updated', 'Updated course ID: 8', NULL, NULL, '2026-03-28 12:43:12'),
(185, 2, 'course_updated', 'Updated course ID: 7', NULL, NULL, '2026-03-28 12:43:32'),
(186, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 12:44:10'),
(187, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 12:46:14'),
(188, 2, 'profile_updated', 'Updated instructor profile', NULL, NULL, '2026-03-28 12:46:31'),
(189, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 13:50:34'),
(190, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 14:03:04'),
(191, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 14:56:26'),
(192, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 16:15:58'),
(193, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 16:16:19'),
(194, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 16:20:04'),
(195, 7, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 16:56:07'),
(196, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-28 16:56:30'),
(197, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-29 01:52:20'),
(198, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-29 07:18:05'),
(199, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-29 07:42:21'),
(200, 6, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-29 07:50:32'),
(201, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-03-29 08:18:43'),
(202, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:02:51'),
(203, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:39:48'),
(204, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:45:57'),
(205, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:47:20'),
(206, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:48:23'),
(207, 13, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 15:49:27'),
(208, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:01:00'),
(209, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:06:47'),
(210, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:12:06'),
(211, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:12:51'),
(212, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:14:16'),
(213, 14, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:22:16'),
(214, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:27:53'),
(215, 13, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:46:24'),
(216, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 16:47:07'),
(217, 14, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-01 17:02:32'),
(218, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-06 06:22:38'),
(219, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-06 06:22:46'),
(220, 9, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-06 06:56:14'),
(221, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-06 07:02:45'),
(222, 1, 'category_updated', 'Updated category ID: 5', NULL, NULL, '2026-04-06 08:18:47'),
(223, 1, 'category_updated', 'Updated category ID: 5', NULL, NULL, '2026-04-06 08:18:52'),
(224, 1, 'category_deleted', 'Deleted category ID: 2', NULL, NULL, '2026-04-06 08:18:59'),
(225, 2, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-04-06 09:27:15');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_type` enum('file_upload','text_submission','link') DEFAULT 'file_upload',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `text_content` text DEFAULT NULL,
  `submission_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`submission_data`)),
  `is_late` tinyint(1) DEFAULT 0,
  `attempt_number` int(11) DEFAULT 1,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `points_possible` decimal(5,2) DEFAULT 100.00,
  `percentage_score` decimal(5,2) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded','returned','late') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories_new`
--

CREATE TABLE `categories_new` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories_new`
--

INSERT INTO `categories_new` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 'All about building websites and web apps.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(3, 'Mobile App Dev', 'iOS and Android development.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(4, 'Cyber Security', 'Network security, ethical hacking, and more.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(5, 'Cloud Computing', 'AWS, Azure, and Google Cloud platform skills.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(6, 'Mobile Development', 'iOS and Android app development', '2026-01-31 12:27:08', '2026-01-31 12:27:08'),
(7, 'Cloud Computing', 'Cloud platforms and services', '2026-01-31 12:27:08', '2026-01-31 12:27:08'),
(8, 'Data Science', 'Data analysis, machine learning, and AI', '2026-01-31 12:27:08', '2026-01-31 12:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(100) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `certificate_id` varchar(50) NOT NULL,
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('issued','revoked') DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `course_id`, `certificate_code`, `issued_at`, `certificate_id`, `issued_date`, `file_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, 13, 'ITHUB-698F59D8320C0-2026', '2026-02-13 17:05:28', '', '2026-02-13 17:05:28', NULL, 'issued', '2026-02-13 17:05:28', '2026-03-29 07:29:36'),
(10, 9, 7, NULL, '2026-03-28 15:08:12', 'CERT_69C7EEDC8DC50_2026', '2026-03-27 18:15:00', 'certificates/CERT_69C7EEDC8DC50_2026.html', 'issued', '2026-03-28 15:08:12', '2026-03-29 07:29:36'),
(11, 6, 7, NULL, '2026-03-29 07:51:44', 'CERT_69C8DA10C0B98_2026', '2026-03-28 18:15:00', 'certificates/CERT_69C8DA10C0B98_2026.html', 'issued', '2026-03-29 07:51:44', '2026-03-29 07:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `completed_lessons`
--

CREATE TABLE `completed_lessons` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `completed_lessons`
--

INSERT INTO `completed_lessons` (`id`, `student_id`, `lesson_id`, `completed_at`) VALUES
(13, 6, 26, '2026-02-13 16:38:35'),
(24, 6, 29, '2026-03-23 17:53:36'),
(25, 6, 30, '2026-03-23 17:53:39'),
(26, 6, 31, '2026-03-23 18:07:38'),
(28, 6, 33, '2026-03-23 18:07:59'),
(31, 7, 29, '2026-03-25 16:16:19'),
(32, 7, 30, '2026-03-25 16:16:22'),
(33, 7, 31, '2026-03-25 16:16:24'),
(35, 7, 33, '2026-03-25 16:16:32'),
(38, 9, 29, '2026-03-28 14:59:25'),
(39, 9, 30, '2026-03-28 14:59:28'),
(40, 9, 31, '2026-03-28 14:59:31'),
(41, 9, 33, '2026-03-28 14:59:33');

-- --------------------------------------------------------

--
-- Table structure for table `courses_backup_20260329`
--

CREATE TABLE `courses_backup_20260329` (
  `id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` int(11) DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses_new`
--

CREATE TABLE `courses_new` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` int(11) DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `max_students` int(11) DEFAULT 0,
  `prerequisites` text DEFAULT NULL,
  `enrollment_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses_new`
--

INSERT INTO `courses_new` (`id`, `title`, `description`, `category_id`, `instructor_id`, `thumbnail`, `price`, `duration_hours`, `difficulty_level`, `status`, `max_students`, `prerequisites`, `enrollment_count`, `created_at`, `updated_at`) VALUES
(7, 'Basic Website Development Tranning', 'This course is designed for beginners who want to start their journey in web development. \r\nYou will learn how to build modern, responsive, and user-friendly websites from scratch.\r\n\r\nThroughout this course, you will gain hands-on experience with:\r\n- HTML for structuring web pages\r\n- CSS for styling and responsive design\r\n- JavaScript for interactivity\r\n\r\nBy the end of this course, you will be able to create your own fully functional website and understand the core concepts of frontend development.\r\n\r\nPrerequisites:\r\n- Basic computer knowledge\r\n- No prior coding experience required', 1, 2, '', 2000.00, 30, 'beginner', 'published', 0, NULL, 0, '2026-03-23 13:22:16', '2026-04-01 16:01:27'),
(8, 'Advanced Web Development', 'test a new aba the kajsjiajia  sjajsjakjsa  siajsisai wijsiajsiajsia s ais ai sai sia sia ssia si ais sai shai hsia hsia hsiahsi hai shaihsaisiasiasaisaisjaij s isaijja siajsia sjiajsia', 1, 2, '', 5000.00, 30, 'beginner', 'published', 0, NULL, 0, '2026-03-25 03:27:22', '2026-04-01 16:01:27');

-- --------------------------------------------------------

--
-- Table structure for table `course_meta`
--

CREATE TABLE `course_meta` (
  `course_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_meta`
--

INSERT INTO `course_meta` (`course_id`, `meta_key`, `meta_value`, `updated_at`) VALUES
(1, 'faqs', '[]', '2026-03-22 13:34:44'),
(1, 'requirements', '[]', '2026-03-22 13:34:44'),
(1, 'target_audience', '[]', '2026-03-22 13:34:44'),
(1, 'what_you_learn', '[\"hello\"]', '2026-03-22 13:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `course_recommendations`
--

CREATE TABLE `course_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `recommendation_type` enum('knn','cold_start','popular','collaborative') NOT NULL DEFAULT 'knn',
  `recommendation_score` decimal(5,4) DEFAULT 0.0000,
  `recommendation_reason` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_reviews`
--

CREATE TABLE `course_reviews` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_reviews`
--

INSERT INTO `course_reviews` (`id`, `course_id`, `student_id`, `rating`, `review`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 5, 'Excellent course! Very well explained.', 1, '2026-01-31 12:33:25', '2026-01-31 12:33:25'),
(2, 2, 3, 4, 'Good content but could use more examples.', 1, '2026-01-31 12:33:25', '2026-01-31 12:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

CREATE TABLE `discussions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `pinned` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `locked` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `replies_count` int(11) DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `course_id`, `lesson_id`, `parent_id`, `student_id`, `title`, `content`, `pinned`, `is_resolved`, `locked`, `views_count`, `replies_count`, `last_reply_at`, `created_at`, `updated_at`) VALUES
(1, 7, NULL, NULL, 6, 'hii', 'hello', 0, 0, 0, 2, 6, '2026-04-01 16:28:55', '2026-03-20 10:31:23', '2026-04-01 16:28:55'),
(2, 7, NULL, NULL, 6, 'hii', 'hello', 0, 0, 0, 0, 1, '2026-04-01 16:32:40', '2026-03-20 10:31:23', '2026-04-01 16:32:40'),
(3, 7, NULL, 1, 6, '', 'hiie', 0, 1, 0, 0, 0, NULL, '2026-03-20 10:41:20', '2026-04-01 16:06:03'),
(4, 1, NULL, NULL, 1, 'Test Discussion', 'This is a test discussion created by the testing script.', 0, 0, 0, 1, 1, '2026-03-20 10:47:41', '2026-03-20 10:47:41', '2026-03-20 10:47:41'),
(5, 1, NULL, 4, 1, '', 'This is a test reply.', 0, 0, 0, 0, 0, NULL, '2026-03-20 10:47:41', '2026-03-20 10:47:41'),
(8, 7, NULL, 1, 9, '', 'hlo', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:23:41', '2026-04-01 16:23:41'),
(9, 7, NULL, 1, 9, '', 'who are you', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:23:53', '2026-04-01 16:23:53'),
(10, 7, NULL, 1, 9, '', 'who are you', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:26:41', '2026-04-01 16:26:41'),
(11, 7, NULL, 1, 9, '', 'who are you', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:28:33', '2026-04-01 16:28:33'),
(12, 7, NULL, 1, 9, '', 'hehe', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:28:55', '2026-04-01 16:28:55'),
(13, 7, NULL, 2, 9, '', 'haha', 0, 0, 0, 0, 0, NULL, '2026-04-01 16:32:40', '2026-04-01 16:32:40');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments_backup_20260329`
--

CREATE TABLE `enrollments_backup_20260329` (
  `id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments_backup_20260329`
--

INSERT INTO `enrollments_backup_20260329` (`id`, `student_id`, `course_id`, `enrolled_at`, `completed_at`, `progress_percentage`, `status`) VALUES
(10, 6, 7, '2026-03-23 13:26:33', NULL, 100.00, 'active'),
(11, 6, 8, '2026-03-25 03:28:28', NULL, 0.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments_new`
--

CREATE TABLE `enrollments_new` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `enrollment_type` enum('paid','free_trial','complimentary') NOT NULL DEFAULT 'paid',
  `status` enum('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments_new`
--

INSERT INTO `enrollments_new` (`id`, `user_id`, `course_id`, `payment_id`, `enrollment_type`, `status`, `progress_percentage`, `enrolled_at`, `completed_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(15, 6, 10, NULL, 'free_trial', 'active', 0.00, '2026-02-12 13:49:41', NULL, '2026-03-14 13:49:41', '2026-02-12 13:49:41', '2026-02-12 13:49:41'),
(16, 6, 19, NULL, 'free_trial', 'active', 0.00, '2026-02-12 14:05:29', NULL, '2026-03-14 14:05:29', '2026-02-12 14:05:29', '2026-02-12 14:05:29'),
(17, 8, 7, NULL, 'paid', 'active', 0.00, '2026-03-18 17:27:24', NULL, NULL, '2026-03-23 17:27:24', '2026-03-28 14:15:00'),
(18, 9, 7, NULL, 'paid', 'active', 100.00, '2026-03-13 17:27:24', NULL, NULL, '2026-03-23 17:27:24', '2026-04-01 15:29:48'),
(19, 10, 7, NULL, 'paid', 'active', 90.00, '2026-03-20 17:27:24', NULL, NULL, '2026-03-23 17:27:24', '2026-03-23 17:27:24'),
(20, 11, 7, NULL, 'paid', 'active', 30.00, '2026-03-16 17:27:24', NULL, NULL, '2026-03-23 17:27:24', '2026-03-23 17:27:24'),
(21, 12, 7, NULL, 'paid', 'active', 60.00, '2026-03-21 17:27:24', NULL, NULL, '2026-03-23 17:27:24', '2026-03-23 17:27:24'),
(22, 7, 7, 34, 'paid', 'active', 0.00, '2026-03-25 04:35:44', NULL, NULL, '2026-03-25 04:35:44', '2026-03-28 14:15:00'),
(23, 7, 8, 35, 'paid', 'active', 0.00, '2026-03-25 04:56:25', NULL, NULL, '2026-03-25 04:56:25', '2026-03-25 04:56:25'),
(24, 6, 7, 37, 'paid', 'active', 100.00, '2026-03-28 14:03:46', NULL, NULL, '2026-03-28 14:03:46', '2026-03-28 16:22:33'),
(25, 6, 8, 38, 'paid', 'active', 0.00, '2026-03-28 16:44:40', NULL, NULL, '2026-03-28 16:44:40', '2026-03-28 16:44:40'),
(27, 9, 8, 39, 'paid', 'active', 0.00, '2026-04-01 15:22:48', NULL, NULL, '2026-04-01 15:22:48', '2026-04-01 15:22:48');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_activity_log`
--

CREATE TABLE `instructor_activity_log` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_activity_log`
--

INSERT INTO `instructor_activity_log` (`id`, `instructor_id`, `action`, `details`, `course_id`, `created_at`) VALUES
(1, 2, 'course_created', 'Created course: test for web', 13, '2026-02-01 13:15:06'),
(4, 2, 'course_updated', 'Updated course ID: 4', NULL, '2026-03-20 12:35:01'),
(5, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:36:17'),
(6, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:36:50'),
(7, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:41:12'),
(8, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:47:50'),
(9, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:47:54'),
(10, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:48:02'),
(11, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:51:06'),
(12, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 12:56:32'),
(13, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 13:00:37'),
(14, 2, 'course_updated', 'Updated course ID: 1', NULL, '2026-03-20 13:01:12'),
(18, 2, 'course_created', 'Created course: Test Course 1774198602', NULL, '2026-03-22 16:56:42'),
(19, 2, 'course_created', 'Created course: Basic Website Development Tranning', 7, '2026-03-23 13:22:16'),
(21, 2, 'course_updated', 'Updated course ID: 7', 7, '2026-03-23 15:55:21'),
(22, 2, 'course_updated', 'Updated course ID: 7', 7, '2026-03-23 15:55:32'),
(23, 2, 'course_created', 'Created course: Data Analysis', 8, '2026-03-25 03:27:22'),
(24, 2, 'course_updated', 'Updated course ID: 7', 7, '2026-03-25 03:27:42'),
(25, 2, 'course_updated', 'Updated course ID: 8', 8, '2026-03-28 12:43:12'),
(26, 2, 'course_updated', 'Updated course ID: 7', 7, '2026-03-28 12:43:32');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_meta`
--

CREATE TABLE `instructor_meta` (
  `instructor_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_meta`
--

INSERT INTO `instructor_meta` (`instructor_id`, `meta_key`, `meta_value`, `updated_at`) VALUES
(2, 'qualifications', '[\"[]\"]', '2026-03-28 12:46:31'),
(2, 'social_links', '{\"linkedin\":\"\",\"twitter\":\"\",\"github\":\"\",\"website\":\"\"}', '2026-03-28 12:46:31'),
(2, 'specialties', '[\"[]\"]', '2026-03-28 12:46:31'),
(5, 'qualifications', '[\"[]\"]', '2026-03-20 11:52:43'),
(5, 'social_links', '{\"linkedin\":\"\",\"twitter\":\"\",\"github\":\"\",\"website\":\"\"}', '2026-03-20 11:52:43'),
(5, 'specialties', '[\"[]\"]', '2026-03-20 11:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `learning_progress_dp`
--

CREATE TABLE `learning_progress_dp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `progress_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`progress_state`)),
  `optimal_path_score` decimal(5,2) DEFAULT 0.00,
  `completion_probability` decimal(5,4) DEFAULT 0.0000,
  `estimated_completion_time` int(11) DEFAULT 0,
  `last_calculated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_progress_dp`
--

INSERT INTO `learning_progress_dp` (`id`, `user_id`, `course_id`, `lesson_id`, `progress_state`, `optimal_path_score`, `completion_probability`, `estimated_completion_time`, `last_calculated`) VALUES
(1, 3, 4, NULL, '0', 0.00, 0.0000, -1, '2026-02-01 15:48:37'),
(3, 3, 13, NULL, '0', 105.00, 0.0000, -1, '2026-02-01 15:48:37'),
(5, 3, 5, NULL, '0', 0.00, 0.0000, -1, '2026-02-01 15:48:00'),
(133, 1, 1, NULL, '0', 0.00, 0.0000, -1, '2026-01-31 16:08:23'),
(587, 3, 15, NULL, '0', 0.00, 0.0000, -1, '2026-02-01 15:48:37'),
(593, 6, 18, NULL, '0', 0.00, 0.0000, -1, '2026-02-12 14:12:35'),
(595, 6, 16, NULL, '0', 0.00, 0.0000, -1, '2026-02-12 14:12:35'),
(597, 6, 15, NULL, '0', 0.00, 0.0000, -1, '2026-02-01 16:07:47'),
(599, 6, 17, NULL, '0', 0.00, 0.0000, -1, '2026-02-12 14:12:35');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `lesson_order` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `lesson_type` enum('video','text','quiz') DEFAULT 'text',
  `duration_minutes` int(11) DEFAULT 0,
  `is_free` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `video_file_path` varchar(500) DEFAULT NULL COMMENT 'Path to uploaded video file',
  `google_drive_url` varchar(1000) DEFAULT NULL COMMENT 'Google Drive video URL',
  `video_source` enum('upload','google_drive','external_url','none') DEFAULT 'none' COMMENT 'Source of video content',
  `video_file_size` bigint(20) DEFAULT NULL COMMENT 'Size of uploaded video file in bytes',
  `video_duration` varchar(20) DEFAULT NULL COMMENT 'Duration of video in HH:MM:SS format',
  `video_thumbnail` varchar(500) DEFAULT NULL COMMENT 'Path to video thumbnail image',
  `video_processing_status` enum('pending','processing','completed','failed','none') DEFAULT 'none' COMMENT 'Video processing status',
  `video_mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME type of uploaded video',
  `video_quality` enum('360p','480p','720p','1080p','4k') DEFAULT '720p' COMMENT 'Video quality preference',
  `is_downloadable` tinyint(1) DEFAULT 0 COMMENT 'Whether video can be downloaded by students',
  `auto_generate_thumbnail` tinyint(1) DEFAULT 1 COMMENT 'Auto-generate thumbnail from video',
  `content_type` varchar(50) DEFAULT 'video',
  `video_path` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `video_url`, `lesson_order`, `sort_order`, `lesson_type`, `duration_minutes`, `is_free`, `created_at`, `updated_at`, `video_file_path`, `google_drive_url`, `video_source`, `video_file_size`, `video_duration`, `video_thumbnail`, `video_processing_status`, `video_mime_type`, `video_quality`, `is_downloadable`, `auto_generate_thumbnail`, `content_type`, `video_path`, `description`, `duration`, `is_published`) VALUES
(26, 13, 'Intro', 'inrodunctoiopn', '', 1, 0, 'text', 5, 0, '2026-02-13 06:31:22', '2026-02-13 06:31:22', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1, 'video', NULL, NULL, NULL, 0),
(29, 7, 'Installing Code editor', 'Video lesson', 'uploads/videos/69c7c864e75d4.mp4', 0, 1, 'video', 20, 1, '2026-03-23 15:38:35', '2026-03-28 12:24:04', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1, 'video', NULL, 'Welcome to the course! In this lesson, we will cover the basics.', '5:30', 1),
(30, 7, 'Basic Structure of an HTML Website _ Sigma Web Development Course', 'Video lesson. Thumbnail: uploads/thumbnails/thumbnails/69c7c8e515ce2.png', 'uploads/videos/69c7c8e51567c.mp4', 0, 2, 'video', 12, 1, '2026-03-23 15:38:35', '2026-03-28 12:26:13', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1, 'video', NULL, 'Learn how to set up your development environment.', '15:00', 1),
(31, 7, 'Heading, Paragraphs and Links _ Sigma Web Development Course', 'Video lesson', 'uploads/videos/69c7c913f16a2.mp4', 0, 3, 'video', 20, 1, '2026-03-23 15:38:35', '2026-03-28 12:26:59', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1, 'text', NULL, 'Understanding the fundamental concepts.', '10:00', 1),
(33, 7, 'Final Project', NULL, NULL, 0, 5, 'text', 0, 1, '2026-03-23 15:38:35', '2026-03-23 15:38:35', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1, 'project', NULL, 'Complete your final project to earn the certificate.', '60:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lesson_assignments`
--

CREATE TABLE `lesson_assignments` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `assignment_type` enum('file_upload','text_submission','quiz','external') DEFAULT 'file_upload',
  `max_points` int(11) DEFAULT 100,
  `due_date` datetime DEFAULT NULL,
  `allow_late_submission` tinyint(1) DEFAULT 1,
  `late_penalty_percent` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 1,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_assignments`
--

INSERT INTO `lesson_assignments` (`id`, `lesson_id`, `instructor_id`, `title`, `description`, `instructions`, `assignment_type`, `max_points`, `due_date`, `allow_late_submission`, `late_penalty_percent`, `max_attempts`, `time_limit_minutes`, `is_published`, `created_at`) VALUES
(4, 31, 1, 'Practice Exercise 1', 'Complete the first practice exercise', 'Follow the steps in the tutorial video', 'file_upload', 100, '2026-03-30 21:23:35', 1, 0, 1, NULL, 1, '2026-03-23 15:38:35'),
(5, 31, 1, 'Practice Exercise 2', 'Complete the second practice exercise', 'Apply what you learned in the previous exercises', 'file_upload', 100, '2026-04-06 21:23:35', 1, 0, 1, NULL, 1, '2026-03-23 15:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_materials`
--

CREATE TABLE `lesson_materials` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT 'file',
  `file_size` bigint(20) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_notes`
--

CREATE TABLE `lesson_notes` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `note_type` enum('markdown','text','html') DEFAULT 'markdown',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `is_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `video_watch_time_seconds` int(11) DEFAULT 0,
  `video_completion_percentage` decimal(5,2) DEFAULT 0.00,
  `notes_viewed` tinyint(1) DEFAULT 0,
  `assignments_completed` int(11) DEFAULT 0,
  `assignments_total` int(11) DEFAULT 0,
  `resources_viewed` int(11) DEFAULT 0,
  `resources_total` int(11) DEFAULT 0,
  `time_spent_minutes` int(11) DEFAULT 0,
  `last_accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `lesson_id`, `student_id`, `completed`, `video_watch_time_seconds`, `video_completion_percentage`, `notes_viewed`, `assignments_completed`, `assignments_total`, `resources_viewed`, `resources_total`, `time_spent_minutes`, `last_accessed_at`, `completed_at`, `status`) VALUES
(5, 29, 6, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 16:22:25', NULL, 'not_started'),
(6, 30, 6, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 16:22:27', NULL, 'not_started'),
(7, 31, 6, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 16:22:31', NULL, 'not_started'),
(8, 33, 6, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 16:22:33', NULL, 'not_started'),
(9, 29, 7, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(10, 30, 7, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(11, 31, 7, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(12, 33, 7, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(13, 29, 8, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(14, 30, 8, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(15, 31, 8, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(16, 33, 8, 0, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 14:15:00', NULL, 'not_started'),
(22, 29, 9, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-03-28 15:17:24', NULL, 'not_started'),
(23, 30, 9, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-04-01 15:29:30', '2026-04-01 15:29:30', 'not_started'),
(24, 31, 9, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-04-01 15:29:45', '2026-04-01 15:29:45', 'not_started'),
(25, 33, 9, 1, 0, 0.00, 0, 0, 0, 0, 0, 0, '2026-04-01 15:29:48', '2026-04-01 15:29:48', 'not_started');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_resources`
--

CREATE TABLE `lesson_resources` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `resource_type` enum('document','presentation','video','audio','link','image','other') DEFAULT 'document',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `external_url` varchar(1000) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_downloadable` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `attempt_type` enum('login','register','reset_password') NOT NULL DEFAULT 'login',
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `email`, `attempt_type`, `success`, `created_at`) VALUES
(149, '::1', 'govindarana@ithub.com', 'login', 1, '2026-04-01 15:45:57'),
(150, '::1', 'admin@ithub.com', 'login', 0, '2026-04-01 15:46:15'),
(151, '::1', 'admin@ithub.com', 'login', 1, '2026-04-01 15:47:20'),
(152, '::1', 'instructor@ithub.com', 'login', 0, '2026-04-01 15:47:54'),
(153, '::1', 'govindarana@ithub.com', 'login', 1, '2026-04-01 15:48:23'),
(154, '::1', 'instructor@ithub.com', 'login', 1, '2026-04-01 15:49:27'),
(155, '::1', 'instructor1@ithub.com', 'login', 0, '2026-04-01 16:00:53'),
(156, '::1', 'instructor1@ithub.com', 'login', 1, '2026-04-01 16:01:00'),
(157, '::1', 'admin@ithub.com', 'login', 1, '2026-04-01 16:06:47'),
(158, '::1', 'instructor1@ithub.com', 'login', 1, '2026-04-01 16:12:06'),
(159, '::1', 'admin@ithub.com', 'login', 1, '2026-04-01 16:12:51'),
(160, '::1', 'instructor1@ithub.com', 'login', 1, '2026-04-01 16:14:16'),
(161, '::1', 'sabina@ithub.com', 'login', 1, '2026-04-01 16:22:16'),
(162, '::1', 'admin@ithub.com', 'login', 1, '2026-04-01 16:27:53'),
(163, '::1', 'instructor@ithub.com', 'login', 1, '2026-04-01 16:46:24'),
(164, '::1', 'instructor1@ithub.com', 'login', 0, '2026-04-01 16:47:02'),
(165, '::1', 'instructor1@ithub.com', 'login', 1, '2026-04-01 16:47:07'),
(166, '::1', 'sabina@ithub.com', 'login', 1, '2026-04-01 17:02:32'),
(167, '::1', 'govindarana@ithub.com', 'login', 1, '2026-04-06 06:22:38'),
(168, '::1', 'govindarana@ithub.com', 'login', 1, '2026-04-06 06:22:46'),
(169, '::1', 'govindarana@ithub.com', 'login', 1, '2026-04-06 06:56:14'),
(170, '::1', 'admin@ithub.com', 'login', 1, '2026-04-06 07:02:45'),
(171, '::1', 'instructor1@ithub.com', 'login', 0, '2026-04-06 09:27:05'),
(172, '::1', 'instructor1@ithub.com', 'login', 1, '2026-04-06 09:27:15');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `transaction_uuid` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `payment_method` enum('esewa','khalti','free','other') NOT NULL DEFAULT 'esewa',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NPR',
  `status` enum('pending','processing','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `gateway_status` varchar(50) DEFAULT NULL,
  `gateway_transaction_id` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `signed_field_names` varchar(255) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT 'EPAYTEST',
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `transaction_uuid`, `user_id`, `course_id`, `payment_method`, `amount`, `currency`, `status`, `gateway_status`, `gateway_transaction_id`, `gateway_response`, `signature`, `signed_field_names`, `product_code`, `failure_reason`, `created_at`, `updated_at`) VALUES
(4, '697f84134fb57-1769964563-U3-C19', 3, 19, '', 169.00, 'NPR', 'pending', NULL, NULL, NULL, '5Lu4LdQYUJUkOBfnscpECKYOYqnvqm1JxO7sIhg6c74=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 16:49:23', '2026-02-01 16:49:23'),
(8, '697f85ccbc852-1769965004-U3-C16', 3, 16, '', 119.00, 'NPR', 'pending', NULL, NULL, NULL, 'UKyVYEPr7u0tPJWxwNhuDP9ZTRL97xwq+KhqjKUR50E=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 16:56:44', '2026-02-01 16:56:44'),
(11, '697f8b84ee5e9-1769966468-U3-C14', 3, 14, '', 89.00, 'NPR', 'pending', NULL, NULL, NULL, 'rlboBS10CcG/wU2/JlKI9pExSIA2l1JAIiYhBAyZ4Lk=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 17:21:08', '2026-02-01 17:21:08'),
(13, '697f8d4ab4d2c-1769966922-U3-C16', 3, 16, '', 119.00, 'NPR', 'pending', NULL, NULL, NULL, 'JIUfLFY6TeIPL4r7bIdPvx/IzY+94QDZWiTHJ5xCex8=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 17:28:42', '2026-02-01 17:28:42'),
(15, 'TXN-697f9025c2214', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"83HRf6oL5YS5hzGXk7oFV+UYahq1nY\\/TR1oXRbFa5Hg=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:40:53', '2026-02-01 17:40:53'),
(20, 'TXN-697f910b2bc00', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"8c7KpjrhcdH8M9kYx\\/W7mkeX4F9KVD06oMzT99TQhp8=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:44:43', '2026-02-01 17:44:43'),
(21, 'TXN-697f91140debe', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"nrVaNNY1WS9etTfJnosuLuC3pUpQSXFKc7+A9ZqMxoU=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:44:52', '2026-02-01 17:44:52'),
(23, 'TXN-697f9385d14ea', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"l7+KJw72FGztGsfayKO3UjtZAmPJqpBkcrSCc5tgg8k=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:55:17', '2026-02-01 17:55:17'),
(24, 'TXN-697f942a6f184', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"XpbsQ7l+R5yDLvmKIXks6aSgINKxDnueIJgJCMq8MqA=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:58:02', '2026-02-01 17:58:02'),
(25, 'TXN-697f94b0b2116', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"9q\\/lXklWoqULDnK2pZ8eFNHqdq2Uex\\/Gyq+KOq8KVcA=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 18:00:16', '2026-02-01 18:00:16'),
(27, 'TXN-697f9618c1ec8', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"UDIoekCTvBndHGnVV26DEvEwqKg51rxZGbR2Wgdofg0=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 18:06:16', '2026-02-01 18:06:16'),
(30, 'KHLTI-69c35e7a07c60-1774411386', 7, 7, 'khalti', 200000.00, 'NPR', 'pending', NULL, NULL, '{\"integration\":\"khalti\",\"amount_paisa\":200000,\"product_identity\":\"course_7_1774411386\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:03:06', '2026-03-25 04:03:06'),
(31, 'KHLTI-69c35e883a904-1774411400', 7, 7, 'khalti', 200000.00, 'NPR', 'pending', NULL, NULL, '{\"integration\":\"khalti\",\"amount_paisa\":200000,\"product_identity\":\"course_7_1774411400\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:03:20', '2026-03-25 04:03:20'),
(32, 'TXN-69c36158894ca', 7, 7, 'esewa', 2000.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"XIvWqw6BNqEvSi4DPjlGgXU0y\\/MyXXkgBHKmWgh4lyA=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:15:20', '2026-03-25 04:15:20'),
(33, 'TXN-69c362b248150', 7, 7, 'esewa', 2000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69c362b248150\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"PdWga\\/QLM9UbgiJeZkRQynN8sYIwit3vh2UrGsZntO0=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKM3\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:21:06', '2026-03-25 04:28:27'),
(34, 'TXN-69c364a56e789', 7, 7, 'esewa', 2000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69c364a56e789\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"fGGe1VR05qJB5k82c1Bjfnt1w6jhVYwcrUFXqTpDd+c=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKME\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:29:25', '2026-03-25 04:29:57'),
(35, 'TXN-69c36add970ee', 7, 8, 'esewa', 5000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69c36add970ee\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"8DbX7VtzQZKZWkOgpg\\/rWUsII5D+oJWfzaOPf6fZF0k=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKMV\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 04:55:57', '2026-03-25 04:56:24'),
(36, 'TXN-69c36ea4a474f', 8, 8, 'esewa', 5000.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"WD6eDM\\/gAz13m7GGkyk4v0LAKxLJgHIFGdYadO06C2c=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-25 05:12:04', '2026-03-25 05:12:04'),
(37, 'TXN-69c7dfa6c6c2a', 6, 7, 'esewa', 2000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69c7dfa6c6c2a\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"0YsPamRrD9lazRHPuGfz+9Lv0Ekge41ZfVC8zP6vFbI=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EM2A\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-28 14:03:18', '2026-03-28 14:03:46'),
(38, 'TXN-69c8055c66e16', 6, 8, 'esewa', 5000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69c8055c66e16\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"pnw3\\/DlT4QJqMANHLV9wNLmDaafL7d2XshQDN2qRBdk=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EM5M\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-03-28 16:44:12', '2026-03-28 16:44:40'),
(39, 'TXN-69cd37e3a68ea', 9, 8, 'esewa', 5000.00, 'NPR', 'completed', NULL, NULL, '{\"transaction_uuid\":\"TXN-69cd37e3a68ea\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"6N0BxwI0eteFSUyL1RX3yFuDy8SYxJ2HLD1oB+Ijdzs=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EPTO\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-04-01 15:21:07', '2026-04-01 15:21:43');

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `before_payment_status_update` BEFORE UPDATE ON `payments` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        -- Log status change
        INSERT INTO payment_verification_logs (payment_id, verification_type, status, request_data, response_data)
        VALUES (NEW.id, 'status_change', 'success', 
                CONCAT('Status changed from ', OLD.status, ' to ', NEW.status),
                CONCAT('Payment ', NEW.id, ' status updated'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_analytics`
-- (See below for the actual view)
--
CREATE TABLE `payment_analytics` (
`payment_date` date
,`payment_method` enum('esewa','khalti','free','other')
,`status` enum('pending','processing','completed','failed','refunded','cancelled')
,`transaction_count` bigint(21)
,`total_amount` decimal(32,2)
,`average_amount` decimal(14,6)
,`successful_payments` bigint(21)
,`failed_payments` bigint(21)
,`success_rate` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','boolean','integer','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_encrypted`, `created_at`, `updated_at`) VALUES
(1, 'esewa_secret_key', '8gBm/:&EnhH.1/q', 'string', 'eSewa secret key for HMAC signature', 0, '2026-02-01 16:32:31', '2026-03-25 04:18:49'),
(2, 'esewa_product_code', 'EPAYTEST', 'string', 'eSewa product code for testing', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(3, 'esewa_merchant_id', '', 'string', 'eSewa merchant ID', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(4, 'esewa_test_mode', 'true', 'boolean', 'Enable eSewa test mode', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(5, 'esewa_success_url', 'payments/esewa_success.php', 'string', 'eSewa success callback URL', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(6, 'esewa_failure_url', 'payments/esewa_failure.php', 'string', 'eSewa failure callback URL', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(7, 'payment_timeout_minutes', '30', 'integer', 'Payment session timeout in minutes', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(8, 'enable_payment_logging', 'true', 'boolean', 'Enable detailed payment logging', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
(9, 'max_payment_attempts', '3', 'integer', 'Maximum payment attempts per transaction', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31');

-- --------------------------------------------------------

--
-- Table structure for table `payment_verification_logs`
--

CREATE TABLE `payment_verification_logs` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `verification_type` enum('signature','status_check','amount_validation','product_code_validation') NOT NULL,
  `status` enum('success','failed','error') NOT NULL,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_verification_logs`
--

INSERT INTO `payment_verification_logs` (`id`, `payment_id`, `verification_type`, `status`, `request_data`, `response_data`, `error_message`, `ip_address`, `user_agent`, `created_at`) VALUES
(3, 4, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f84134fb57-1769964563-U3-C19\",\"amount\":\"169.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 16:49:23'),
(7, 8, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f85ccbc852-1769965004-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 16:56:44'),
(11, 11, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f8b84ee5e9-1769966468-U3-C14\",\"amount\":\"89.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 17:21:08'),
(13, 13, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f8d4ab4d2c-1769966922-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 17:28:42'),
(15, 15, '', 'success', '{\"activity\":\"free_enrollment_created\",\"description\":\"User enrolled for free\"}', '{\"user_id\":6,\"course_id\":10,\"enrollment_type\":\"free_trial\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 13:49:41'),
(17, 33, '', 'success', 'Status changed from pending to completed', 'Payment 33 status updated', NULL, NULL, NULL, '2026-03-25 04:28:27'),
(18, 34, '', 'success', 'Status changed from pending to completed', 'Payment 34 status updated', NULL, NULL, NULL, '2026-03-25 04:29:57'),
(20, 35, '', 'success', 'Status changed from pending to completed', 'Payment 35 status updated', NULL, NULL, NULL, '2026-03-25 04:56:24'),
(21, 23, '', 'success', '{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}', '{\"user_id\":7,\"course_id\":8,\"payment_id\":35,\"enrollment_type\":\"paid\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 04:56:25'),
(22, 37, '', 'success', 'Status changed from pending to completed', 'Payment 37 status updated', NULL, NULL, NULL, '2026-03-28 14:03:46'),
(23, 24, '', 'success', '{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}', '{\"user_id\":6,\"course_id\":7,\"payment_id\":37,\"enrollment_type\":\"paid\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:03:46'),
(24, 38, '', 'success', 'Status changed from pending to completed', 'Payment 38 status updated', NULL, NULL, NULL, '2026-03-28 16:44:40'),
(25, 25, '', 'success', '{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}', '{\"user_id\":6,\"course_id\":8,\"payment_id\":38,\"enrollment_type\":\"paid\"}', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-28 16:44:40'),
(26, 39, '', 'success', 'Status changed from pending to completed', 'Payment 39 status updated', NULL, NULL, NULL, '2026-04-01 15:21:43'),
(27, 27, '', 'success', '{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}', '{\"user_id\":9,\"course_id\":8,\"payment_id\":39,\"enrollment_type\":\"paid\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 15:22:48');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `attempts_allowed` int(11) DEFAULT 3,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `randomize_questions` tinyint(1) DEFAULT 0,
  `show_correct_answers` tinyint(1) DEFAULT 1,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `max_attempts` int(11) DEFAULT 3,
  `time_limit_minutes` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `lesson_id`, `title`, `description`, `time_limit`, `attempts_allowed`, `passing_score`, `randomize_questions`, `show_correct_answers`, `status`, `created_at`, `updated_at`, `max_attempts`, `time_limit_minutes`) VALUES
(7, 7, NULL, 'HTML Basics Quiz', 'Test your knowledge of HTML fundamentals', NULL, 3, 70.00, 0, 1, 'published', '2026-03-24 03:57:41', '2026-03-24 03:57:41', 3, 15),
(8, 7, NULL, 'HTML Basics Quiz', 'Test your knowledge of HTML fundamentals', NULL, 3, 70.00, 0, 1, 'published', '2026-03-24 03:59:08', '2026-03-24 03:59:08', 3, 15),
(9, 7, NULL, 'CSS Fundamentals Quiz', 'Test your CSS styling knowledge', NULL, 3, 75.00, 0, 1, 'published', '2026-03-24 03:59:08', '2026-03-24 03:59:08', 3, 20);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `answer_text`, `is_correct`, `points_earned`, `created_at`) VALUES
(6, 6, 17, NULL, '0', 0, 0.00, '2026-03-24 04:05:16'),
(7, 7, 17, NULL, '0', 0, 0.00, '2026-03-24 04:18:14'),
(8, 8, 17, NULL, '0', 1, 1.00, '2026-03-24 04:27:00'),
(9, 10, 22, NULL, '0', 1, 1.00, '2026-03-24 14:26:41'),
(10, 12, 17, NULL, '0', 0, 0.00, '2026-04-01 16:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_points` decimal(5,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `passed` tinyint(1) DEFAULT 0,
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `student_id`, `quiz_id`, `attempt_number`, `started_at`, `completed_at`, `score`, `total_points`, `percentage`, `passed`, `status`) VALUES
(4, 3, 6, 1, '2026-03-24 03:55:50', '2026-03-24 03:55:50', 10.00, 10.00, 100.00, 1, 'completed'),
(5, 3, 10, 1, '2026-03-24 04:03:01', '2026-03-24 04:03:01', 10.00, 10.00, 100.00, 1, 'completed'),
(6, 6, 8, 1, '2026-03-24 04:04:39', '2026-03-24 04:05:16', 0.00, 1.00, 0.00, 0, 'completed'),
(7, 6, 8, 2, '2026-03-24 04:17:52', '2026-03-24 04:18:14', 0.00, 1.00, 0.00, 0, 'completed'),
(8, 6, 8, 3, '2026-03-24 04:27:00', '2026-03-24 04:27:00', 1.00, 1.00, 100.00, 1, 'completed'),
(9, 6, 7, 1, '2026-03-24 04:28:08', '2026-03-24 04:28:08', 0.00, 0.00, 0.00, 0, 'completed'),
(10, 6, 9, 1, '2026-03-24 14:26:28', '2026-03-24 14:26:41', 1.00, 1.00, 100.00, 1, 'completed'),
(11, 6, 7, 2, '2026-03-25 02:49:48', NULL, 0.00, 0.00, 0.00, 0, 'in_progress'),
(12, 9, 8, 1, '2026-04-01 15:59:57', '2026-04-01 16:07:52', 0.00, 1.00, 0.00, 0, 'completed'),
(13, 9, 8, 2, '2026-04-01 16:19:21', '2026-04-01 16:19:26', 0.00, 0.00, 0.00, 0, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_options`
--

INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `is_correct`, `option_order`, `created_at`) VALUES
(99, 10, 'Paris', 1, 0, '2026-03-24 03:55:50'),
(100, 10, 'London', 0, 1, '2026-03-24 03:55:50'),
(101, 10, 'Berlin', 0, 2, '2026-03-24 03:55:50'),
(102, 10, 'Madrid', 0, 3, '2026-03-24 03:55:50'),
(103, 11, 'True', 0, 1, '2026-03-24 03:55:50'),
(104, 11, 'False', 1, 2, '2026-03-24 03:55:50'),
(105, 15, 'Hyper Text Markup Language', 1, 1, '2026-03-24 03:59:08'),
(106, 15, 'High Tech Modern Language', 0, 2, '2026-03-24 03:59:08'),
(107, 15, 'Home Tool Markup Language', 0, 3, '2026-03-24 03:59:08'),
(108, 15, 'Hyperlinks and Text Markup Language', 0, 4, '2026-03-24 03:59:08'),
(109, 16, '<h1>', 1, 1, '2026-03-24 03:59:08'),
(110, 16, '<h6>', 0, 2, '2026-03-24 03:59:08'),
(111, 16, '<heading>', 0, 3, '2026-03-24 03:59:08'),
(112, 16, '<head>', 0, 4, '2026-03-24 03:59:08'),
(113, 17, 'True', 0, 1, '2026-03-24 03:59:08'),
(114, 17, 'False', 1, 2, '2026-03-24 03:59:08'),
(115, 18, '<br>', 1, 1, '2026-03-24 03:59:08'),
(116, 18, '<break>', 0, 2, '2026-03-24 03:59:08'),
(117, 18, '<lb>', 0, 3, '2026-03-24 03:59:08'),
(118, 18, '<newline>', 0, 4, '2026-03-24 03:59:08'),
(119, 19, 'alt', 1, 1, '2026-03-24 03:59:08'),
(120, 19, 'src', 0, 2, '2026-03-24 03:59:08'),
(121, 19, 'title', 0, 3, '2026-03-24 03:59:08'),
(122, 19, 'longdesc', 0, 4, '2026-03-24 03:59:08'),
(123, 20, 'Cascading Style Sheets', 1, 1, '2026-03-24 03:59:08'),
(124, 20, 'Computer Style Sheets', 0, 2, '2026-03-24 03:59:08'),
(125, 20, 'Creative Style Sheets', 0, 3, '2026-03-24 03:59:08'),
(126, 20, 'Colorful Style Sheets', 0, 4, '2026-03-24 03:59:09'),
(127, 21, 'background-color', 1, 1, '2026-03-24 03:59:09'),
(128, 21, 'color', 0, 2, '2026-03-24 03:59:09'),
(129, 21, 'bgcolor', 0, 3, '2026-03-24 03:59:09'),
(130, 21, 'background', 0, 4, '2026-03-24 03:59:09'),
(131, 22, 'True', 1, 1, '2026-03-24 03:59:09'),
(132, 22, 'False', 0, 2, '2026-03-24 03:59:09'),
(133, 23, 'body {color: black;}', 1, 1, '2026-03-24 03:59:09'),
(134, 23, 'body:color=black;', 0, 2, '2026-03-24 03:59:09'),
(135, 23, '{body;color:black;}', 0, 3, '2026-03-24 03:59:09'),
(136, 23, '{body:color=black}', 0, 4, '2026-03-24 03:59:09'),
(137, 25, 'Paris', 1, 0, '2026-03-24 04:03:01'),
(138, 25, 'London', 0, 1, '2026-03-24 04:03:01'),
(139, 25, 'Berlin', 0, 2, '2026-03-24 04:03:01'),
(140, 25, 'Madrid', 0, 3, '2026-03-24 04:03:01'),
(141, 26, 'True', 0, 1, '2026-03-24 04:03:01'),
(142, 26, 'False', 1, 2, '2026-03-24 04:03:01'),
(143, 28, 'me', 1, 0, '2026-03-24 04:03:50'),
(144, 28, 'you', 0, 1, '2026-03-24 04:03:50');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  `points` decimal(5,2) DEFAULT 1.00,
  `question_order` int(11) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `question_order`, `explanation`, `sort_order`, `created_at`) VALUES
(10, 6, 'What is the capital of France? (Updated)', 'multiple_choice', 1.00, 1, NULL, 0, '2026-03-24 03:55:50'),
(11, 6, 'The Earth is flat.', 'true_false', 1.00, 2, NULL, 0, '2026-03-24 03:55:50'),
(15, 8, 'What does HTML stand for?', 'multiple_choice', 1.00, 1, NULL, 0, '2026-03-24 03:59:08'),
(16, 8, 'Which tag is used for the largest heading?', 'multiple_choice', 1.00, 2, NULL, 0, '2026-03-24 03:59:08'),
(17, 8, 'HTML elements are case-sensitive.', 'true_false', 1.00, 3, NULL, 0, '2026-03-24 03:59:08'),
(18, 8, 'What is the correct HTML element for inserting a line break?', 'multiple_choice', 1.00, 4, NULL, 0, '2026-03-24 03:59:08'),
(19, 8, 'Which attribute specifies an alternate text for an image?', 'multiple_choice', 2.00, 5, NULL, 0, '2026-03-24 03:59:08'),
(20, 9, 'What does CSS stand for?', 'multiple_choice', 1.00, 1, NULL, 0, '2026-03-24 03:59:08'),
(21, 9, 'Which property is used to change the background color?', 'multiple_choice', 1.00, 2, NULL, 0, '2026-03-24 03:59:09'),
(22, 9, 'The z-index property works on positioned elements.', 'true_false', 1.00, 3, NULL, 0, '2026-03-24 03:59:09'),
(23, 9, 'Which is the correct CSS syntax?', 'multiple_choice', 2.00, 4, NULL, 0, '2026-03-24 03:59:09'),
(25, 10, 'What is the capital of France? (Updated)', 'multiple_choice', 1.00, 1, NULL, 0, '2026-03-24 04:03:01'),
(26, 10, 'The Earth is flat.', 'true_false', 1.00, 2, NULL, 0, '2026-03-24 04:03:01'),
(28, 8, 'Who are you ??', 'multiple_choice', 1.00, 6, NULL, 0, '2026-03-24 04:03:50');

-- --------------------------------------------------------

--
-- Table structure for table `student_notes`
--

CREATE TABLE `student_notes` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_new`
--

CREATE TABLE `users_new` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','instructor','student') DEFAULT 'student',
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_new`
--

INSERT INTO `users_new` (`id`, `username`, `email`, `password`, `full_name`, `role`, `profile_image`, `bio`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@ithub.com', '$2y$10$jViOhSTdsPM/cbpXk/w7V.WUKeAy3DJu8b5ywqrcWMt6FTAgiW/me', 'Admin User', 'admin', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-04-01 15:46:59'),
(2, 'instructor1', 'instructor1@ithub.com', '$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK', 'John Instructor', 'instructor', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-01-31 15:31:50'),
(3, 'student1', 'student1@ithub.com', '$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK', 'Test Student', 'student', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-01-31 15:31:50'),
(4, 'demo_student_1', 'demo1@example.com', '$2y$10$XADdhdwb2e7kNYpTecuEte9aOS07V95wR2G/P4NUgiToSeuoHs1ri', 'Alice Johnson', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:24:22', '2026-03-23 17:24:22'),
(5, 'demo_student_2', 'demo2@example.com', '$2y$10$btlUNqX/vbXIer3didANaO6xPMl0RqMqj2.JR6OaVa5r7yTA6PHqK', 'Bob Smith', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:24:22', '2026-03-23 17:24:22'),
(6, 'demo_student_3', 'demo3@example.com', '$2y$10$a9EXW0lHmX39BEv7km5J2OBr3ROCJlRgYs0FFsSVyw/AvrjRUWaEe', 'Charlie Brown', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:24:22', '2026-03-23 17:24:22'),
(7, 'demo_student_4', 'demo4@example.com', '$2y$10$LuLwNMmv7/hAgh0l6je4pu8N/0GSsbSuqwtixKKHxQdloNDsOck0.', 'Diana Prince', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:24:22', '2026-03-23 17:24:22'),
(8, 'demo_student_5', 'demo5@example.com', '$2y$10$mAzrapP23JBpVUDQX1QTtOA8Yj3sA6k6XWVpjH.Hq3EAhJ6mPZxCy', 'Edward Norton', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:24:22', '2026-03-23 17:24:22'),
(9, 'govindarana', 'govindarana@ithub.com', '$2y$10$RK4veyJAtRPmrDpCLiP/eepe6p5NHKDczo5vKS4tQGJhH7ARRsuri', 'Govinda Rana', 'student', NULL, NULL, NULL, 'active', '2026-03-28 14:55:19', '2026-03-28 14:55:19'),
(10, 'student_charlie', 'charlie@demo.com', '$2y$10$RdNxHJRT1z.1QKlxOCRil.imr0ZNLcJIJA4qawrqr3IL5vZ47jo3m', 'Charlie Brown', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:26:35', '2026-03-23 17:26:35'),
(11, 'student_diana', 'diana@demo.com', '$2y$10$vj26/vmXMafAoB6e2f6rKeeI88RVBXm7Xe.SCgrQwDJ8fzGvluHDu', 'Diana Prince', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:26:35', '2026-03-23 17:26:35'),
(12, 'student_edward', 'edward@demo.com', '$2y$10$AHUJSpiszWWVjz.X9.WTvuKeo.eLjfT6c38u4u2OniSB7ka6Nn9G6', 'Edward Norton', 'student', NULL, NULL, NULL, 'active', '2026-03-23 17:26:35', '2026-03-23 17:26:35'),
(13, 'instructor', 'instructor@ithub.com', '$2y$10$RNM4p6DOyy/gneVbLrMR.O3TtJtvJunxW.lo6RFeDdPL3jlzjrppe', 'Instructor User', 'instructor', NULL, NULL, '1234567890', 'active', '2026-04-01 15:48:51', '2026-04-01 15:48:51'),
(14, 'sabina', 'sabina@ithub.com', '$2y$10$al7JgkirUo6m2i1kc3D0D.fEKk4Pll6eZ9qpf9kV5eHh5YTVOULpO', 'Sabina Poudel', 'student', NULL, NULL, '0000000000', 'active', '2026-04-01 16:22:02', '2026-04-01 16:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_interactions`
--

CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `interaction_type` enum('view','enroll','lesson_complete','quiz_attempt','discussion_post') NOT NULL,
  `interaction_value` decimal(5,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_analytics`
--

CREATE TABLE `video_analytics` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `watch_time_seconds` int(11) DEFAULT 0,
  `total_video_duration` int(11) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_watched_position` int(11) DEFAULT 0,
  `watch_count` int(11) DEFAULT 0,
  `completed_watching` tinyint(1) DEFAULT 0,
  `first_watched_at` timestamp NULL DEFAULT NULL,
  `last_watched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_processing_queue`
--

CREATE TABLE `video_processing_queue` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `video_file_path` varchar(500) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `thumbnail_generated` tinyint(1) DEFAULT 0,
  `duration_extracted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `payment_analytics`
--
DROP TABLE IF EXISTS `payment_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_analytics`  AS SELECT cast(`p`.`created_at` as date) AS `payment_date`, `p`.`payment_method` AS `payment_method`, `p`.`status` AS `status`, count(0) AS `transaction_count`, sum(`p`.`amount`) AS `total_amount`, avg(`p`.`amount`) AS `average_amount`, count(case when `p`.`status` = 'completed' then 1 end) AS `successful_payments`, count(case when `p`.`status` = 'failed' then 1 end) AS `failed_payments`, round(count(case when `p`.`status` = 'completed' then 1 end) * 100.0 / count(0),2) AS `success_rate` FROM `payments` AS `p` GROUP BY cast(`p`.`created_at` as date), `p`.`payment_method`, `p`.`status` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_lockouts`
--
ALTER TABLE `account_lockouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_locked_until` (`locked_until`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Indexes for table `categories_new`
--
ALTER TABLE `categories_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_id` (`certificate_id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`);

--
-- Indexes for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_lesson` (`student_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `courses_new`
--
ALTER TABLE `courses_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_meta`
--
ALTER TABLE `course_meta`
  ADD PRIMARY KEY (`course_id`,`meta_key`);

--
-- Indexes for table `course_recommendations`
--
ALTER TABLE `course_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_course_type` (`user_id`,`course_id`,`recommendation_type`),
  ADD KEY `idx_user_recommendations` (`user_id`,`recommendation_type`),
  ADD KEY `idx_course_recommendations` (`course_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`course_id`,`student_id`),
  ADD KEY `fk_course_reviews_student` (`student_id`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_pinned` (`pinned`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`verification_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `enrollments_new`
--
ALTER TABLE `enrollments_new`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_enrolled_at` (`enrolled_at`);

--
-- Indexes for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `instructor_meta`
--
ALTER TABLE `instructor_meta`
  ADD PRIMARY KEY (`instructor_id`,`meta_key`);

--
-- Indexes for table `learning_progress_dp`
--
ALTER TABLE `learning_progress_dp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_user_progress` (`user_id`),
  ADD KEY `idx_course_progress` (`course_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_video_source` (`video_source`),
  ADD KEY `idx_lessons_video_processing` (`video_processing_status`);

--
-- Indexes for table `lesson_assignments`
--
ALTER TABLE `lesson_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `lesson_notes`
--
ALTER TABLE `lesson_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lesson_progress` (`lesson_id`,`student_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_student_lesson_completion` (`student_id`,`completed`,`lesson_id`);

--
-- Indexes for table `lesson_resources`
--
ALTER TABLE `lesson_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_attempt_type` (`attempt_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_uuid` (`transaction_uuid`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_transaction_uuid` (`transaction_uuid`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  ADD KEY `idx_user_course_status` (`user_id`,`course_id`,`status`),
  ADD KEY `idx_payment_method_status` (`payment_method`,`status`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `payment_verification_logs`
--
ALTER TABLE `payment_verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_verification_type` (`verification_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payment_created` (`payment_id`,`created_at`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_quizzes_course` (`course_id`),
  ADD KEY `idx_quizzes_status` (`status`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `fk_quiz_answers_option` (`selected_option_id`),
  ADD KEY `idx_quiz_answers_attempt` (`attempt_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_attempts_quiz` (`quiz_id`),
  ADD KEY `idx_quiz_attempts_user` (`student_id`),
  ADD KEY `idx_student_quiz_status` (`student_id`,`quiz_id`,`status`);

--
-- Indexes for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_order` (`question_id`,`option_order`),
  ADD KEY `idx_quiz_options_question` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_id` (`quiz_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_question_order` (`question_order`),
  ADD KEY `idx_quiz_questions_quiz` (`quiz_id`);

--
-- Indexes for table `student_notes`
--
ALTER TABLE `student_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_note` (`lesson_id`,`student_id`),
  ADD KEY `student_notes_ibfk_2_new` (`student_id`);

--
-- Indexes for table `users_new`
--
ALTER TABLE `users_new`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_interaction_type` (`interaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `video_analytics`
--
ALTER TABLE `video_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lesson_student` (`lesson_id`,`student_id`),
  ADD KEY `idx_video_analytics_lesson` (`lesson_id`),
  ADD KEY `idx_video_analytics_student` (`student_id`),
  ADD KEY `idx_student_completion` (`student_id`,`completed_watching`,`lesson_id`);

--
-- Indexes for table `video_processing_queue`
--
ALTER TABLE `video_processing_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_video_queue_status` (`status`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`student_id`,`course_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_lockouts`
--
ALTER TABLE `account_lockouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories_new`
--
ALTER TABLE `categories_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `courses_new`
--
ALTER TABLE `courses_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `course_recommendations`
--
ALTER TABLE `course_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_reviews`
--
ALTER TABLE `course_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments_new`
--
ALTER TABLE `enrollments_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `learning_progress_dp`
--
ALTER TABLE `learning_progress_dp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=773;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `lesson_assignments`
--
ALTER TABLE `lesson_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_notes`
--
ALTER TABLE `lesson_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `lesson_resources`
--
ALTER TABLE `lesson_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_verification_logs`
--
ALTER TABLE `payment_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `student_notes`
--
ALTER TABLE `student_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users_new`
--
ALTER TABLE `users_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_interactions`
--
ALTER TABLE `user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_analytics`
--
ALTER TABLE `video_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_processing_queue`
--
ALTER TABLE `video_processing_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `lesson_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  ADD CONSTRAINT `completed_lessons_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `completed_lessons_ibfk_1_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `completed_lessons_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_meta`
--
ALTER TABLE `course_meta`
  ADD CONSTRAINT `fk_course_meta_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD CONSTRAINT `fk_course_reviews_student` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discussion_parent` FOREIGN KEY (`parent_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments_new`
--
ALTER TABLE `enrollments_new`
  ADD CONSTRAINT `enrollments_new_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollments_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  ADD CONSTRAINT `instructor_activity_log_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructor_activity_log_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `instructor_meta`
--
ALTER TABLE `instructor_meta`
  ADD CONSTRAINT `fk_instructor_meta_user` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_instructor_meta_user_new` FOREIGN KEY (`instructor_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_assignments`
--
ALTER TABLE `lesson_assignments`
  ADD CONSTRAINT `lesson_assignments_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_assignments_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD CONSTRAINT `lesson_materials_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_notes`
--
ALTER TABLE `lesson_notes`
  ADD CONSTRAINT `lesson_notes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_notes_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_resources`
--
ALTER TABLE `lesson_resources`
  ADD CONSTRAINT `lesson_resources_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_resources_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_verification_logs`
--
ALTER TABLE `payment_verification_logs`
  ADD CONSTRAINT `payment_verification_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `fk_quiz_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_quiz_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `quiz_options` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_quiz_answers_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `quiz_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_notes`
--
ALTER TABLE `student_notes`
  ADD CONSTRAINT `student_notes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_notes_ibfk_1_new` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_notes_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_analytics`
--
ALTER TABLE `video_analytics`
  ADD CONSTRAINT `video_analytics_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_analytics_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_analytics_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_processing_queue`
--
ALTER TABLE `video_processing_queue`
  ADD CONSTRAINT `video_processing_queue_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlist_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_course_new` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_student_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
