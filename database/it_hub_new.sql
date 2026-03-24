-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Feb 14, 2026 at 04:39 AM
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
(97, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-02-14 03:20:09');

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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'IT Fundamentals', 'Basic computer and IT concepts for beginners', '2026-01-31 12:27:08'),
(2, 'Programming', 'Learn various programming languages and concepts', '2026-01-31 12:27:08'),
(3, 'Web Development', 'Frontend and backend web development technologies', '2026-01-31 12:27:08'),
(4, 'Database', 'Database design and management systems', '2026-01-31 12:27:08'),
(5, 'Cybersecurity', 'Information security and ethical hacking', '2026-01-31 12:27:08'),
(6, 'Mobile Development', 'iOS and Android app development', '2026-01-31 12:27:08'),
(7, 'Cloud Computing', 'Cloud platforms and services', '2026-01-31 12:27:08'),
(8, 'Data Science', 'Data analysis, machine learning, and AI', '2026-01-31 12:27:08');

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
(2, 'Data Science', 'Data analysis, visualization, and machine learning.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(3, 'Mobile App Dev', 'iOS and Android development.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(4, 'Cyber Security', 'Network security, ethical hacking, and more.', '2026-02-12 14:41:42', '2026-02-12 14:41:42'),
(5, 'Cloud Computing', 'AWS, Azure, and Google Cloud platform skills.', '2026-02-12 14:41:42', '2026-02-12 14:41:42');

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
(1, 6, 13, 'ITHUB-698F59D8320C0-2026', '2026-02-13 17:05:28', '', '2026-02-13 17:05:28', NULL, 'issued', '2026-02-13 17:05:28', '2026-02-13 17:05:28');

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
(1, 3, 11, '2026-01-31 15:34:31'),
(2, 3, 12, '2026-01-31 15:36:07'),
(3, 3, 13, '2026-01-31 15:36:13'),
(4, 3, 14, '2026-01-31 15:59:24'),
(5, 3, 15, '2026-01-31 16:01:05'),
(6, 3, 36, '2026-01-31 16:01:09'),
(7, 3, 37, '2026-01-31 16:01:12'),
(8, 3, 38, '2026-01-31 16:01:14'),
(9, 3, 39, '2026-01-31 16:01:17'),
(10, 3, 40, '2026-01-31 16:01:21'),
(11, 3, 52, '2026-02-01 14:59:02'),
(12, 3, 51, '2026-02-01 15:04:43'),
(13, 6, 26, '2026-02-13 16:38:35'),
(14, 6, 11, '2026-02-13 16:50:17'),
(15, 6, 12, '2026-02-13 16:50:19'),
(16, 6, 13, '2026-02-13 16:50:22'),
(17, 6, 14, '2026-02-13 16:50:31'),
(18, 6, 15, '2026-02-13 16:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
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

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `instructor_id`, `thumbnail`, `price`, `duration_hours`, `difficulty_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Introduction to Web Development', 'Learn HTML, CSS, and JavaScript basics', 3, 2, NULL, 99.99, 0, 'beginner', 'published', '2026-01-31 12:27:08', '2026-01-31 12:27:08'),
(2, 'Advanced PHP Programming', 'Master PHP with real-world projects', 3, 2, NULL, 149.99, 0, 'advanced', 'published', '2026-01-31 12:27:08', '2026-01-31 12:27:08'),
(3, 'Database Design Fundamentals', 'Learn relational database design principles', 4, 2, NULL, 79.99, 0, 'intermediate', 'draft', '2026-01-31 12:27:08', '2026-01-31 12:27:08'),
(4, 'Complete Web Development Bootcamp', 'Learn HTML, CSS, JavaScript, React, Node.js and more', 0, 2, NULL, 89.99, 0, 'beginner', 'published', '2026-01-31 14:59:06', '2026-01-31 14:59:06'),
(5, 'Python for Data Science', 'Master Python programming for data analysis and machine learning', 0, 2, NULL, 79.99, 0, 'beginner', 'published', '2026-01-31 14:59:06', '2026-01-31 14:59:06'),
(6, 'UI/UX Design Fundamentals', 'Learn the principles of user interface and user experience design', 0, 2, NULL, 69.99, 0, 'beginner', 'published', '2026-01-31 14:59:06', '2026-01-31 14:59:06'),
(7, 'Web Development Bootcamp', 'Learn modern web development from scratch with HTML, CSS, JavaScript, and popular frameworks. Build real-world projects and launch your career as a web developer.', 3, 5, 'course_thumbnails/697e1ea32def9.png', 89.00, 40, 'beginner', 'published', '2026-01-31 15:20:54', '2026-01-31 15:24:19'),
(9, 'Database Design & SQL Fundamentals', 'Learn database design principles, SQL programming, and work with MySQL, PostgreSQL, and NoSQL databases. Master data modeling and optimization techniques.', 4, 2, NULL, 69.00, 25, 'intermediate', 'published', '2026-01-31 15:20:54', '2026-01-31 15:20:54'),
(10, 'Cybersecurity Essentials', 'Learn fundamental cybersecurity concepts, network security, ethical hacking, and protection strategies. Prepare for CompTIA Security+ certification.', 5, 1, NULL, 99.00, 30, 'advanced', 'published', '2026-01-31 15:20:54', '2026-01-31 15:20:54'),
(11, 'JavaScript Frameworks & React', 'Deep dive into modern JavaScript including ES6+, React, Redux, and Node.js. Build full-stack applications with the MERN stack.', 3, 5, NULL, 94.00, 45, 'advanced', 'published', '2026-01-31 15:20:54', '2026-01-31 15:20:54'),
(12, 'test for web', 'Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114', 1, 2, 'course_thumbnails/697f513d01a38.png', 5000.00, 11, 'beginner', 'published', '2026-02-01 13:12:29', '2026-02-01 13:12:29'),
(13, 'test for web', 'Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstorestudentprofile.php:114 Stack trace: #0 {main} thrown in C:xampphtdocsstorestudentprofile.php on line 114', 1, 2, 'course_thumbnails/697f51da07b0d.png', 5000.00, 11, 'beginner', 'published', '2026-02-01 13:15:06', '2026-02-01 13:15:06');

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
(1, 'Complete Web Development Bootcamp 2024', 'Become a full-stack web developer with just one course. HTML, CSS, Javascript, Node, React, MongoDB and more!', 1, 2, '0', 49.99, 40, 'intermediate', 'published', 0, NULL, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(2, 'Python for Data Science and Machine Learning', 'Learn how to use NumPy, Pandas, Seaborn, Matplotlib, Plotly, Scikit-Learn, Machine Learning, Tensorflow, and more!', 2, 2, '0', 89.99, 25, 'advanced', 'published', 0, NULL, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(3, 'Flutter & Dart - The Complete Guide', 'A Complete Guide to the Flutter SDK & Flutter Framework for building native iOS and Android apps.', 3, 2, '0', 39.99, 32, 'beginner', 'published', 0, NULL, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(4, 'Ethical Hacking: Zero to Hero', 'Learn ethical hacking, penetration testing, and network security skills from scratch.', 4, 2, '0', 59.99, 18, 'advanced', 'published', 0, NULL, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(5, 'AWS Certified Solutions Architect', 'Master Amazon Web Services (AWS) and pass the Solutions Architect Associate exam.', 5, 2, '0', 99.99, 22, 'intermediate', 'published', 0, NULL, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('video','text','quiz','assignment') DEFAULT 'video',
  `video_url` varchar(255) DEFAULT NULL,
  `video_duration` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_preview` tinyint(1) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

CREATE TABLE `discussions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `pinned` tinyint(1) DEFAULT 0,
  `locked` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `replies_count` int(11) DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

CREATE TABLE `discussion_replies` (
  `id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `parent_reply_id` int(11) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrolled_at`, `completed_at`, `progress_percentage`, `status`) VALUES
(1, 3, 1, '2026-02-12 14:41:48', NULL, 15.00, 'active'),
(2, 3, 2, '2026-02-12 14:41:48', NULL, 60.00, 'active'),
(3, 3, 3, '2026-02-12 14:41:48', NULL, 0.00, 'active'),
(4, 6, 13, '2026-02-13 16:37:07', NULL, 100.00, 'active'),
(5, 6, 3, '2026-02-13 16:41:33', NULL, 100.00, 'active'),
(6, 6, 12, '2026-02-13 17:24:10', NULL, 0.00, 'active'),
(7, 7, 7, '2026-02-14 03:14:36', NULL, 0.00, 'active');

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
(1, 3, 4, NULL, 'free_trial', 'active', 0.00, '2026-02-01 17:08:18', NULL, '2026-03-03 17:08:18', '2026-02-01 17:08:18', '2026-02-01 17:08:18'),
(14, 6, 6, NULL, 'free_trial', 'active', 0.00, '2026-02-12 13:47:24', NULL, '2026-03-14 13:47:24', '2026-02-12 13:47:24', '2026-02-12 13:47:24'),
(15, 6, 10, NULL, 'free_trial', 'active', 0.00, '2026-02-12 13:49:41', NULL, '2026-03-14 13:49:41', '2026-02-12 13:49:41', '2026-02-12 13:49:41'),
(16, 6, 19, NULL, 'free_trial', 'active', 0.00, '2026-02-12 14:05:29', NULL, '2026-03-14 14:05:29', '2026-02-12 14:05:29', '2026-02-12 14:05:29');

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
(1, 2, 'course_created', 'Created course: test for web', 13, '2026-02-01 13:15:06');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_earnings`
--

CREATE TABLE `instructor_earnings` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `course_price` decimal(10,2) NOT NULL,
  `instructor_share` decimal(5,2) DEFAULT 70.00,
  `platform_fee` decimal(5,2) DEFAULT 30.00,
  `earning_amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','paid','processed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `auto_generate_thumbnail` tinyint(1) DEFAULT 1 COMMENT 'Auto-generate thumbnail from video'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `video_url`, `lesson_order`, `lesson_type`, `duration_minutes`, `is_free`, `created_at`, `updated_at`, `video_file_path`, `google_drive_url`, `video_source`, `video_file_size`, `video_duration`, `video_thumbnail`, `video_processing_status`, `video_mime_type`, `video_quality`, `is_downloadable`, `auto_generate_thumbnail`) VALUES
(1, 1, 'Lesson 1: Introduction to Module 1', 'This is the content for lesson 1. It covers important concepts.', NULL, 1, '', 10, 1, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(2, 1, 'Lesson 2: Introduction to Module 2', 'This is the content for lesson 2. It covers important concepts.', NULL, 2, '', 20, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(3, 1, 'Lesson 3: Introduction to Module 3', 'This is the content for lesson 3. It covers important concepts.', NULL, 3, '', 30, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(4, 1, 'Lesson 4: Introduction to Module 4', 'This is the content for lesson 4. It covers important concepts.', NULL, 4, '', 40, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(5, 1, 'Lesson 5: Introduction to Module 5', 'This is the content for lesson 5. It covers important concepts.', NULL, 5, '', 50, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(6, 2, 'Lesson 1: Introduction to Module 1', 'This is the content for lesson 1. It covers important concepts.', NULL, 1, '', 10, 1, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(7, 2, 'Lesson 2: Introduction to Module 2', 'This is the content for lesson 2. It covers important concepts.', NULL, 2, '', 20, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(8, 2, 'Lesson 3: Introduction to Module 3', 'This is the content for lesson 3. It covers important concepts.', NULL, 3, '', 30, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(9, 2, 'Lesson 4: Introduction to Module 4', 'This is the content for lesson 4. It covers important concepts.', NULL, 4, '', 40, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(10, 2, 'Lesson 5: Introduction to Module 5', 'This is the content for lesson 5. It covers important concepts.', NULL, 5, '', 50, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(11, 3, 'Lesson 1: Introduction to Module 1', 'This is the content for lesson 1. It covers important concepts.', NULL, 1, '', 10, 1, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(12, 3, 'Lesson 2: Introduction to Module 2', 'This is the content for lesson 2. It covers important concepts.', NULL, 2, '', 20, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(13, 3, 'Lesson 3: Introduction to Module 3', 'This is the content for lesson 3. It covers important concepts.', NULL, 3, '', 30, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(14, 3, 'Lesson 4: Introduction to Module 4', 'This is the content for lesson 4. It covers important concepts.', NULL, 4, '', 40, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(15, 3, 'Lesson 5: Introduction to Module 5', 'This is the content for lesson 5. It covers important concepts.', NULL, 5, '', 50, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(16, 4, 'Lesson 1: Introduction to Module 1', 'This is the content for lesson 1. It covers important concepts.', NULL, 1, '', 10, 1, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(17, 4, 'Lesson 2: Introduction to Module 2', 'This is the content for lesson 2. It covers important concepts.', NULL, 2, '', 20, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(18, 4, 'Lesson 3: Introduction to Module 3', 'This is the content for lesson 3. It covers important concepts.', NULL, 3, '', 30, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(19, 4, 'Lesson 4: Introduction to Module 4', 'This is the content for lesson 4. It covers important concepts.', NULL, 4, '', 40, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(20, 4, 'Lesson 5: Introduction to Module 5', 'This is the content for lesson 5. It covers important concepts.', NULL, 5, '', 50, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(21, 5, 'Lesson 1: Introduction to Module 1', 'This is the content for lesson 1. It covers important concepts.', NULL, 1, '', 10, 1, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(22, 5, 'Lesson 2: Introduction to Module 2', 'This is the content for lesson 2. It covers important concepts.', NULL, 2, '', 20, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(23, 5, 'Lesson 3: Introduction to Module 3', 'This is the content for lesson 3. It covers important concepts.', NULL, 3, '', 30, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(24, 5, 'Lesson 4: Introduction to Module 4', 'This is the content for lesson 4. It covers important concepts.', NULL, 4, '', 40, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(25, 5, 'Lesson 5: Introduction to Module 5', 'This is the content for lesson 5. It covers important concepts.', NULL, 5, '', 50, 0, '2026-02-12 14:41:48', '2026-02-12 14:41:48', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1),
(26, 13, 'Intro', 'inrodunctoiopn', '', 1, 'text', 5, 0, '2026-02-13 06:31:22', '2026-02-13 06:31:22', NULL, NULL, 'none', NULL, NULL, NULL, 'none', NULL, '720p', 0, 1);

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
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `lesson_id`, `student_id`, `completed`, `video_watch_time_seconds`, `video_completion_percentage`, `notes_viewed`, `assignments_completed`, `assignments_total`, `resources_viewed`, `resources_total`, `time_spent_minutes`, `last_accessed_at`, `completed_at`) VALUES
(1, 1, 3, 1, 0, 0.00, 0, 0, 0, 0, 0, 15, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(2, 2, 3, 1, 0, 0.00, 0, 0, 0, 0, 0, 15, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(3, 6, 3, 1, 0, 0.00, 0, 0, 0, 0, 0, 15, '2026-02-12 14:41:48', '2026-02-12 14:41:48'),
(4, 7, 3, 1, 0, 0.00, 0, 0, 0, 0, 0, 15, '2026-02-12 14:41:48', '2026-02-12 14:41:48');

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
(1, '::1', 'instructor@ithub.com', 'login', 0, '2026-01-31 15:07:53'),
(2, '::1', 'teach@ithub.com', 'login', 0, '2026-01-31 15:13:34'),
(3, '::1', 'teach@ithub.com', 'login', 0, '2026-01-31 15:17:10'),
(4, '::1', 'teach@ithub.com', 'login', 1, '2026-01-31 15:17:18'),
(5, '::1', 'admin@ithub.com', 'login', 1, '2026-01-31 15:23:22'),
(6, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 15:26:18'),
(7, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 15:29:43'),
(8, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 16:06:33'),
(9, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 16:09:09'),
(10, '::1', 'admin@ithub.com', 'login', 1, '2026-01-31 16:32:08'),
(11, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 17:25:15'),
(12, '::1', 'admin@ithub.com', 'login', 1, '2026-01-31 18:04:41'),
(13, '::1', 'student@ithub.com', 'login', 1, '2026-01-31 18:07:02'),
(14, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 10:29:10'),
(15, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 12:52:05'),
(16, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 13:09:11'),
(17, '::1', 'teacher@ithub.com', 'login', 0, '2026-02-01 13:09:46'),
(18, '::1', 'teacher@ithub.com', 'login', 0, '2026-02-01 13:09:55'),
(19, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-01 13:10:12'),
(20, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 13:48:19'),
(21, '::1', 'student@ithub.com', 'login', 0, '2026-02-01 14:58:17'),
(22, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 14:58:22'),
(23, '::1', 'admin@ithub.com', 'login', 1, '2026-02-01 15:18:38'),
(24, '::1', 'student@ithub.com', 'login', 1, '2026-02-01 15:48:00'),
(25, '::1', 'admin@ithub.com', 'login', 1, '2026-02-01 15:49:07'),
(26, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 15:50:51'),
(27, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 16:07:47'),
(28, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 16:12:16'),
(29, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 16:12:30'),
(30, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 16:12:54'),
(31, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-01 16:15:42'),
(32, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-05 11:44:23'),
(33, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-12 13:26:34'),
(34, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-12 14:02:45'),
(35, '::1', 'admin@ithub.com', 'login', 1, '2026-02-12 14:32:05'),
(36, '::1', 'teach@test.com', 'login', 0, '2026-02-12 14:33:02'),
(37, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-12 14:33:14'),
(38, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-12 14:37:59'),
(39, '::1', 'student@ithub.com', 'login', 1, '2026-02-12 14:38:26'),
(40, '::1', 'student@ithub.com', 'login', 1, '2026-02-13 05:45:05'),
(41, '::1', 'student@ithub.com', 'login', 1, '2026-02-13 05:49:56'),
(42, '::1', 'admin@ithub.com', 'login', 1, '2026-02-13 06:02:17'),
(43, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-13 06:02:55'),
(44, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-13 06:41:21'),
(45, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 06:42:01'),
(46, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 07:07:00'),
(47, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 07:38:40'),
(48, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 08:01:53'),
(49, '::1', 'admin@ithub.com', 'login', 1, '2026-02-13 12:35:47'),
(50, '::1', 'govindarana@gmail.com', 'login', 0, '2026-02-13 12:39:10'),
(51, '::1', 'instructor@ithub.com', 'login', 0, '2026-02-13 12:39:25'),
(52, '::1', 'student@ithub.com', 'login', 0, '2026-02-13 12:39:37'),
(53, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 15:56:14'),
(54, '::1', 'admin@ithub.com', 'login', 1, '2026-02-13 17:18:10'),
(55, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 17:19:00'),
(56, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-13 17:53:57'),
(57, '::1', 'emma.johnson@student.com', 'login', 0, '2026-02-13 17:54:19'),
(58, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-13 17:54:28'),
(59, '::1', 'admin@ithub.com', 'login', 1, '2026-02-14 02:58:23'),
(60, '::1', 'govindarana@ithub.com', 'login', 1, '2026-02-14 02:59:04'),
(61, '::1', 'instructor@ithub.com', 'login', 1, '2026-02-14 03:00:22'),
(62, '::1', 'samiksha@ithub.com', 'login', 1, '2026-02-14 03:14:23'),
(63, '::1', 'admin@ithub.com', 'login', 1, '2026-02-14 03:15:46'),
(64, '::1', 'admin@ithub.com', 'login', 1, '2026-02-14 03:19:32'),
(65, '::1', 'admin@ithub.com', 'login', 1, '2026-02-14 03:20:09');

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
(5, '697f84675c317-1769964647-U1-C4', 1, 4, '', 89.00, 'NPR', 'pending', NULL, NULL, NULL, 'Jh5sIVTM9/+a6Sj0ohObGHOxi6ofQ579NgBzDU8XknU=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 16:50:47', '2026-02-01 16:50:47'),
(8, '697f85ccbc852-1769965004-U3-C16', 3, 16, '', 119.00, 'NPR', 'pending', NULL, NULL, NULL, 'UKyVYEPr7u0tPJWxwNhuDP9ZTRL97xwq+KhqjKUR50E=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 16:56:44', '2026-02-01 16:56:44'),
(11, '697f8b84ee5e9-1769966468-U3-C14', 3, 14, '', 89.00, 'NPR', 'pending', NULL, NULL, NULL, 'rlboBS10CcG/wU2/JlKI9pExSIA2l1JAIiYhBAyZ4Lk=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 17:21:08', '2026-02-01 17:21:08'),
(13, '697f8d4ab4d2c-1769966922-U3-C16', 3, 16, '', 119.00, 'NPR', 'pending', NULL, NULL, NULL, 'JIUfLFY6TeIPL4r7bIdPvx/IzY+94QDZWiTHJ5xCex8=', 'total_amount,transaction_uuid,product_code', 'EPAYTEST', NULL, '2026-02-01 17:28:42', '2026-02-01 17:28:42'),
(15, 'TXN-697f9025c2214', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"83HRf6oL5YS5hzGXk7oFV+UYahq1nY\\/TR1oXRbFa5Hg=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:40:53', '2026-02-01 17:40:53'),
(20, 'TXN-697f910b2bc00', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"8c7KpjrhcdH8M9kYx\\/W7mkeX4F9KVD06oMzT99TQhp8=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:44:43', '2026-02-01 17:44:43'),
(21, 'TXN-697f91140debe', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"nrVaNNY1WS9etTfJnosuLuC3pUpQSXFKc7+A9ZqMxoU=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:44:52', '2026-02-01 17:44:52'),
(22, 'TXN-697f932343007', 3, 4, 'esewa', 89.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"9jpJjTEjLsg0dKwYRFbpWew+LXKlneBtbwE4Fhdn7oM=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:53:39', '2026-02-01 17:53:39'),
(23, 'TXN-697f9385d14ea', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"l7+KJw72FGztGsfayKO3UjtZAmPJqpBkcrSCc5tgg8k=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:55:17', '2026-02-01 17:55:17'),
(24, 'TXN-697f942a6f184', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"XpbsQ7l+R5yDLvmKIXks6aSgINKxDnueIJgJCMq8MqA=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 17:58:02', '2026-02-01 17:58:02'),
(25, 'TXN-697f94b0b2116', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"9q\\/lXklWoqULDnK2pZ8eFNHqdq2Uex\\/Gyq+KOq8KVcA=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 18:00:16', '2026-02-01 18:00:16'),
(26, 'TXN-697f94f84f430', 3, 4, 'esewa', 100.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"oz+ZQmckhLkSbkta\\/YHiSDblUKev3eg5Qv6RHofuAVo=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 18:01:28', '2026-02-01 18:01:28'),
(27, 'TXN-697f9618c1ec8', 3, 16, 'esewa', 119.00, 'NPR', 'pending', NULL, NULL, '{\"signature\":\"UDIoekCTvBndHGnVV26DEvEwqKg51rxZGbR2Wgdofg0=\"}', NULL, NULL, 'EPAYTEST', NULL, '2026-02-01 18:06:16', '2026-02-01 18:06:16');

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
(1, 'esewa_secret_key', '8gBm/:&EnhH.1/q(', 'string', 'eSewa secret key for HMAC signature', 0, '2026-02-01 16:32:31', '2026-02-01 16:32:31'),
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
(4, 5, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f84675c317-1769964647-U1-C4\",\"amount\":\"89.00\",\"payment_method\":\"esewa\"}', NULL, 'unknown', 'unknown', '2026-02-01 16:50:47'),
(7, 8, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f85ccbc852-1769965004-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 16:56:44'),
(11, 11, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f8b84ee5e9-1769966468-U3-C14\",\"amount\":\"89.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 17:21:08'),
(13, 13, '', 'success', '{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}', '{\"transaction_uuid\":\"697f8d4ab4d2c-1769966922-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 17:28:42'),
(15, 15, '', 'success', '{\"activity\":\"free_enrollment_created\",\"description\":\"User enrolled for free\"}', '{\"user_id\":6,\"course_id\":10,\"enrollment_type\":\"free_trial\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 13:49:41');

-- --------------------------------------------------------

--
-- Table structure for table `platform_stats`
--

CREATE TABLE `platform_stats` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `total_users` int(11) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `total_instructors` int(11) DEFAULT 0,
  `total_courses` int(11) DEFAULT 0,
  `total_enrollments` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `active_users` int(11) DEFAULT 0,
  `new_users` int(11) DEFAULT 0,
  `new_enrollments` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 3, 10, '', 1, 1.00, '2026-01-31 17:19:44'),
(2, 1, 4, 15, '', 1, 2.00, '2026-01-31 17:19:59'),
(3, 1, 5, 18, '', 1, 2.00, '2026-01-31 17:19:59'),
(4, 1, 6, 22, '', 0, 0.00, '2026-01-31 17:19:59'),
(5, 1, 7, NULL, 'true', 1, 1.00, '2026-01-31 17:19:59');

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
(9, 3, '.python', 0, 1, '2026-01-31 16:35:18'),
(10, 3, '.py', 1, 2, '2026-01-31 16:35:18'),
(11, 3, '.pt', 0, 3, '2026-01-31 16:35:18'),
(12, 3, '.pyth', 0, 4, '2026-01-31 16:35:18'),
(13, 4, 'Tuple', 0, 1, '2026-01-31 16:35:18'),
(14, 4, 'String', 0, 2, '2026-01-31 16:35:18'),
(15, 4, 'List', 1, 3, '2026-01-31 16:35:18'),
(16, 4, 'Integer', 0, 4, '2026-01-31 16:35:18'),
(17, 5, 'function myFunction():', 0, 1, '2026-01-31 16:35:18'),
(18, 5, 'def myFunction():', 1, 2, '2026-01-31 16:35:18'),
(19, 5, 'create myFunction():', 0, 3, '2026-01-31 16:35:18'),
(20, 5, 'func myFunction():', 0, 4, '2026-01-31 16:35:18'),
(21, 6, '5', 0, 1, '2026-01-31 16:35:18'),
(22, 6, '6', 0, 2, '2026-01-31 16:35:18'),
(23, 6, '8', 1, 3, '2026-01-31 16:35:18'),
(24, 6, '9', 0, 4, '2026-01-31 16:35:18'),
(25, 7, 'True', 1, 1, '2026-01-31 16:35:18'),
(26, 7, 'False', 0, 2, '2026-01-31 16:35:18'),
(27, 8, 'Array', 0, 1, '2026-01-31 16:35:18'),
(28, 8, 'DataFrame', 1, 2, '2026-01-31 16:35:18'),
(29, 8, 'List', 0, 3, '2026-01-31 16:35:18'),
(30, 8, 'Dictionary', 0, 4, '2026-01-31 16:35:18'),
(31, 9, 'np.arange()', 0, 1, '2026-01-31 16:35:18'),
(32, 9, 'np.linspace()', 1, 2, '2026-01-31 16:35:18'),
(33, 9, 'np.array()', 0, 3, '2026-01-31 16:35:18'),
(34, 9, 'np.range()', 0, 4, '2026-01-31 16:35:18'),
(35, 10, 'df.get(\"age\")', 0, 1, '2026-01-31 16:35:18'),
(36, 10, 'df[\"age\"]', 1, 2, '2026-01-31 16:35:18'),
(37, 10, 'df.column(\"age\")', 0, 3, '2026-01-31 16:35:18'),
(38, 10, 'df.select(\"age\")', 0, 4, '2026-01-31 16:35:18'),
(39, 11, 'Drops duplicate rows', 0, 1, '2026-01-31 16:35:18'),
(40, 11, 'Removes missing values', 1, 2, '2026-01-31 16:35:18'),
(41, 11, 'Drops the DataFrame', 0, 3, '2026-01-31 16:35:18'),
(42, 11, 'Resets the index', 0, 4, '2026-01-31 16:35:18'),
(43, 12, 'True', 1, 1, '2026-01-31 16:35:18'),
(44, 12, 'False', 0, 2, '2026-01-31 16:35:18'),
(45, 13, 'plt.line()', 0, 1, '2026-01-31 16:35:18'),
(46, 13, 'plt.plot()', 1, 2, '2026-01-31 16:35:18'),
(47, 13, 'plt.graph()', 0, 3, '2026-01-31 16:35:18'),
(48, 13, 'plt.draw()', 0, 4, '2026-01-31 16:35:18'),
(49, 14, 'Saves the plot', 0, 1, '2026-01-31 16:35:18'),
(50, 14, 'Displays the plot', 1, 2, '2026-01-31 16:35:18'),
(51, 14, 'Creates a new plot', 0, 3, '2026-01-31 16:35:18'),
(52, 14, 'Clears the plot', 0, 4, '2026-01-31 16:35:18'),
(53, 15, 'Plotly', 0, 1, '2026-01-31 16:35:18'),
(54, 15, 'Seaborn', 1, 2, '2026-01-31 16:35:18'),
(55, 15, 'Bokeh', 0, 3, '2026-01-31 16:35:18'),
(56, 15, 'Altair', 0, 4, '2026-01-31 16:35:18'),
(57, 16, 'sns.scatter()', 0, 1, '2026-01-31 16:35:18'),
(58, 16, 'sns.scatterplot()', 1, 2, '2026-01-31 16:35:18'),
(59, 16, 'sns.plot_scatter()', 0, 3, '2026-01-31 16:35:18'),
(60, 16, 'sns.dots()', 0, 4, '2026-01-31 16:35:18'),
(61, 17, 'True', 1, 1, '2026-01-31 16:35:18'),
(62, 17, 'False', 0, 2, '2026-01-31 16:35:18'),
(63, 18, 'Unsupervised learning', 0, 1, '2026-01-31 16:35:18'),
(64, 18, 'Supervised learning', 1, 2, '2026-01-31 16:35:18'),
(65, 18, 'Reinforcement learning', 0, 3, '2026-01-31 16:35:18'),
(66, 18, 'Semi-supervised learning', 0, 4, '2026-01-31 16:35:18'),
(67, 19, 'To train the model', 0, 1, '2026-01-31 16:35:18'),
(68, 19, 'To split data into training and testing sets', 1, 2, '2026-01-31 16:35:18'),
(69, 19, 'To test the model', 0, 3, '2026-01-31 16:35:18'),
(70, 19, 'To validate the model', 0, 4, '2026-01-31 16:35:18'),
(71, 20, 'Linear Regression', 0, 1, '2026-01-31 16:35:18'),
(72, 20, 'Logistic Regression', 1, 2, '2026-01-31 16:35:18'),
(73, 20, 'K-Means', 0, 3, '2026-01-31 16:35:18'),
(74, 20, 'PCA', 0, 4, '2026-01-31 16:35:18'),
(75, 21, 'When model performs poorly on training data', 0, 1, '2026-01-31 16:35:18'),
(76, 21, 'When model is too simple', 0, 2, '2026-01-31 16:35:18'),
(77, 21, 'When model learns training data too well', 1, 3, '2026-01-31 16:35:18'),
(78, 21, 'When model has high bias', 0, 4, '2026-01-31 16:35:18'),
(79, 22, 'True', 1, 1, '2026-01-31 16:35:18'),
(80, 22, 'False', 0, 2, '2026-01-31 16:35:18');

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
  `explanation` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review_text` text DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `student_notes`
--

INSERT INTO `student_notes` (`id`, `lesson_id`, `student_id`, `title`, `content`, `is_private`, `created_at`, `updated_at`) VALUES
(1, 51, 3, 'Personal Notes', 'test\nhekllo', 1, '2026-02-01 15:07:50', '2026-02-01 15:11:02'),
(8, 52, 3, 'Personal Notes', 'hii', 1, '2026-02-01 15:08:44', '2026-02-01 15:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 1,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `profile_image`, `bio`, `phone`, `status`, `created_at`, `updated_at`, `email_verified`, `verification_token`, `verification_expires_at`) VALUES
(1, 'admin', 'admin@ithub.com', '$2y$10$pRuG5CZ38p0nCMpgzL7/zuPWSWynuaL6TbL5ff4zwPh/mEKPS/IwO', 'System Administrator', 'admin', NULL, NULL, NULL, 'active', '2026-01-31 12:23:27', '2026-01-31 12:23:27', 1, NULL, NULL),
(2, 'instructor', 'instructor@ithub.com', '$2y$10$X2ceLzfRh./Y0HrZpFYIs.gEGObeXvW8jms7UG4feNi.UIARNVT.i', 'John Instructor', 'instructor', NULL, 'Experienced web developer with 10+ years in industry', NULL, 'active', '2026-01-31 12:23:27', '2026-01-31 12:23:27', 1, NULL, NULL),
(3, 'student', 'student@ithub.com', '$2y$10$AVLZZrkZsZ8ADL5uebjIZ.kADLRBft2V.qyiT9LQ1C2dsIb56Ccj.', 'Alice Student', 'student', NULL, '', '9766655262', 'active', '2026-01-31 12:23:27', '2026-02-01 14:07:23', 1, NULL, NULL),
(5, 'govinda', 'teach@ithub.com', '$2y$10$KnuWVNg75.mcmuGbD5ddFerEp2bR/ANHA.ZCo3Uryx1t1X7D3Oije', 'Teacher', 'instructor', NULL, NULL, '0000000000', 'active', '2026-01-31 15:13:15', '2026-01-31 15:14:30', 1, NULL, NULL),
(6, 'govinda_rana', 'govindarana@ithub.com', '$2y$10$7rnhgb9o5QceFxxxGl6F..FTHrcPjBSOb4Grgv2Wk9ShLnOhZKpS.', 'Govinda Rana', 'student', NULL, NULL, NULL, 'active', '2026-02-01 15:50:22', '2026-02-01 15:50:22', 1, NULL, NULL),
(7, 'samiksha', 'samiksha@ithub.com', '$2y$10$3SCUtxWzmuwZ43V5BBdDt.pyGSbiIhWKlrLrdWMEJ3.meL/afjC8u', 'samiksha', 'student', NULL, NULL, '9744265707', 'active', '2026-02-14 03:13:29', '2026-02-14 03:13:29', 1, NULL, NULL);

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
(1, 'admin', 'admin@ithub.com', '$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK', 'Admin User', 'admin', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-01-31 15:31:50'),
(2, 'instructor1', 'instructor1@ithub.com', '$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK', 'John Instructor', 'instructor', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-01-31 15:31:50'),
(3, 'student1', 'student1@ithub.com', '$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK', 'Test Student', 'student', NULL, NULL, NULL, 'active', '2026-01-31 15:31:50', '2026-01-31 15:31:50');

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses_new`
--
ALTER TABLE `courses_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_section_id` (`section_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_status` (`status`);

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
  ADD UNIQUE KEY `unique_review` (`course_id`,`student_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_pinned` (`pinned`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_discussion_id` (`discussion_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_parent_reply_id` (`parent_reply_id`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`);

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
-- Indexes for table `instructor_earnings`
--
ALTER TABLE `instructor_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `student_id` (`student_id`);

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
-- Indexes for table `platform_stats`
--
ALTER TABLE `platform_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stat_date` (`stat_date`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `fk_quiz_answers_attempt` (`attempt_id`),
  ADD KEY `fk_quiz_answers_option` (`selected_option_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_order` (`question_id`,`option_order`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_id` (`quiz_id`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`course_id`,`student_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_notes`
--
ALTER TABLE `student_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_note` (`lesson_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

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
  ADD KEY `idx_video_analytics_student` (`student_id`);

--
-- Indexes for table `video_processing_queue`
--
ALTER TABLE `video_processing_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_video_queue_status` (`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories_new`
--
ALTER TABLE `categories_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `courses_new`
--
ALTER TABLE `courses_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments_new`
--
ALTER TABLE `enrollments_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `instructor_earnings`
--
ALTER TABLE `instructor_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_progress_dp`
--
ALTER TABLE `learning_progress_dp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=773;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `lesson_assignments`
--
ALTER TABLE `lesson_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_notes`
--
ALTER TABLE `lesson_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lesson_resources`
--
ALTER TABLE `lesson_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_verification_logs`
--
ALTER TABLE `payment_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `platform_stats`
--
ALTER TABLE `platform_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_notes`
--
ALTER TABLE `student_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users_new`
--
ALTER TABLE `users_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `completed_lessons_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_meta`
--
ALTER TABLE `course_meta`
  ADD CONSTRAINT `fk_course_meta_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments_new`
--
ALTER TABLE `enrollments_new`
  ADD CONSTRAINT `enrollments_new_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollments_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_instructor_meta_user` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `student_notes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_analytics`
--
ALTER TABLE `video_analytics`
  ADD CONSTRAINT `video_analytics_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_analytics_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_processing_queue`
--
ALTER TABLE `video_processing_queue`
  ADD CONSTRAINT `video_processing_queue_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
