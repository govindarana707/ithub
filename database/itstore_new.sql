-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Feb 14, 2026 at 04:37 AM
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
-- Database: `itstore_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin Super', 'admin@itstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(2, 'John Admin', 'john.admin@itstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(3, 'Sarah Moderator', 'sarah.mod@itstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 'web-development', 'Learn to build modern websites and web applications', 'bi-globe', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(2, 'Mobile Development', 'mobile-development', 'Create mobile apps for iOS and Android', 'bi-phone', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(3, 'Data Science', 'data-science', 'Master data analysis, machine learning and AI', 'bi-bar-chart', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(4, 'Programming Languages', 'programming-languages', 'Learn popular programming languages from scratch', 'bi-code-slash', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(5, 'Database Management', 'database-management', 'Learn SQL, NoSQL, and database design', 'bi-database', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(6, 'Cloud Computing', 'cloud-computing', 'Master AWS, Azure, Google Cloud platforms', 'bi-cloud', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(7, 'Cybersecurity', 'cybersecurity', 'Learn ethical hacking and security best practices', 'bi-shield-lock', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(8, 'DevOps', 'devops', 'Master CI/CD, Docker, Kubernetes and automation', 'bi-gear', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(9, 'UI/UX Design', 'ui-ux-design', 'Design beautiful and user-friendly interfaces', 'bi-palette', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(10, 'Digital Marketing', 'digital-marketing', 'Learn SEO, social media marketing, and analytics', 'bi-megaphone', 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(100) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','revoked','expired') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_enrollments`
--

INSERT INTO `course_enrollments` (`id`, `user_id`, `course_id`, `order_id`, `enrollment_date`, `completion_status`, `progress_percentage`, `last_accessed`, `completed_at`) VALUES
(1, 1, 1, 1, '2026-01-15 04:50:00', 'in_progress', 45.50, '2026-02-10 08:45:00', NULL),
(2, 1, 6, 6, '2026-02-05 07:40:00', 'in_progress', 15.00, '2026-02-11 03:35:00', NULL),
(3, 1, 11, 6, '2026-02-05 07:40:00', 'not_started', 0.00, NULL, NULL),
(4, 1, 18, 6, '2026-02-05 07:40:00', 'in_progress', 8.50, '2026-02-09 11:00:00', NULL),
(5, 2, 2, 2, '2026-01-18 08:40:00', 'in_progress', 30.00, '2026-02-12 05:30:00', NULL),
(6, 3, 3, 3, '2026-01-22 03:35:00', 'completed', 100.00, '2026-02-08 12:45:00', NULL),
(7, 3, 1, 3, '2026-01-22 03:35:00', 'in_progress', 65.00, '2026-02-13 04:15:00', NULL),
(8, 4, 6, 4, '2026-01-25 11:05:00', 'in_progress', 22.50, '2026-02-11 09:35:00', NULL),
(9, 5, 8, 5, '2026-02-01 05:50:00', 'in_progress', 55.00, '2026-02-13 08:00:00', NULL),
(10, 6, 11, 7, '2026-02-08 09:30:00', 'in_progress', 12.00, '2026-02-12 13:45:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_reviews`
--

CREATE TABLE `course_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_reviews`
--

INSERT INTO `course_reviews` (`id`, `user_id`, `course_id`, `rating`, `review_text`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 5, 'Excellent course! Very comprehensive and well-structured. The instructor explains everything clearly.', 'approved', '2026-02-05 04:45:00', '2026-02-13 16:21:24'),
(2, 2, 2, 5, 'Best JavaScript course I have taken. Deep dive into advanced concepts. Highly recommended!', 'approved', '2026-02-08 08:35:00', '2026-02-13 16:21:24'),
(3, 3, 3, 4, 'Great React course with lots of practical examples. Could use more real-world projects though.', 'approved', '2026-02-09 03:30:00', '2026-02-13 16:21:24'),
(4, 4, 6, 5, 'Amazing data science course! Clear explanations and hands-on projects. Worth every penny.', 'approved', '2026-02-10 11:00:00', '2026-02-13 16:21:24'),
(5, 5, 8, 5, 'Perfect Python bootcamp for beginners. Covers everything you need to get started.', 'approved', '2026-02-11 05:45:00', '2026-02-13 16:21:24'),
(6, 6, 11, 4, 'Good SQL course with practical examples. Wish there were more advanced optimization techniques.', 'approved', '2026-02-12 09:25:00', '2026-02-13 16:21:24'),
(7, 3, 1, 5, 'Outstanding web development bootcamp! Built 20 projects and learned so much.', 'approved', '2026-02-12 12:40:00', '2026-02-13 16:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_file` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `order_id`, `invoice_number`, `invoice_file`, `generated_at`) VALUES
(1, 1, 'INV-2026-00001', 'invoices/INV-2026-00001.pdf', '2026-01-15 04:50:00'),
(2, 2, 'INV-2026-00002', 'invoices/INV-2026-00002.pdf', '2026-01-18 08:40:00'),
(3, 3, 'INV-2026-00003', 'invoices/INV-2026-00003.pdf', '2026-01-22 03:35:00'),
(4, 4, 'INV-2026-00004', 'invoices/INV-2026-00004.pdf', '2026-01-25 11:05:00'),
(5, 5, 'INV-2026-00005', 'invoices/INV-2026-00005.pdf', '2026-02-01 05:50:00'),
(6, 6, 'INV-2026-00006', 'invoices/INV-2026-00006.pdf', '2026-02-05 07:40:00'),
(7, 7, 'INV-2026-00007', 'invoices/INV-2026-00007.pdf', '2026-02-08 09:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL COMMENT 'e.g., 15:30',
  `order_number` int(11) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `description`, `content`, `video_url`, `duration`, `order_number`, `is_free`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Introduction to Web Development', 'Welcome to the course! Overview of what we will learn.', 'In this lesson, we introduce the fundamentals of web development...', 'https://youtu.be/intro1', '10:30', 1, 1, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(2, 1, 'HTML Basics', 'Learn HTML tags and structure', 'HTML is the foundation of web development. In this lesson...', 'https://youtu.be/html1', '25:15', 2, 1, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(3, 1, 'HTML Forms and Input', 'Create interactive forms', 'Forms are essential for user interaction. Learn how to...', 'https://youtu.be/html2', '30:45', 3, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(4, 1, 'CSS Fundamentals', 'Style your web pages with CSS', 'CSS allows you to style HTML elements. In this lesson...', 'https://youtu.be/css1', '35:20', 4, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(5, 1, 'CSS Flexbox and Grid', 'Modern layout techniques', 'Master modern CSS layout systems including flexbox...', 'https://youtu.be/css2', '40:10', 5, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(6, 1, 'JavaScript Introduction', 'Your first JavaScript code', 'JavaScript brings interactivity to web pages...', 'https://youtu.be/js1', '28:30', 6, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(7, 1, 'JavaScript Functions', 'Write reusable code with functions', 'Functions are the building blocks of JavaScript...', 'https://youtu.be/js2', '32:15', 7, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(8, 1, 'DOM Manipulation', 'Interact with HTML using JavaScript', 'Learn how to manipulate HTML elements dynamically...', 'https://youtu.be/dom1', '38:45', 8, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(9, 1, 'React Basics', 'Introduction to React framework', 'React is a popular JavaScript library for building UIs...', 'https://youtu.be/react1', '42:20', 9, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(10, 1, 'Final Project', 'Build a complete web application', 'Apply everything you learned to build a real project...', 'https://youtu.be/final1', '60:00', 10, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(11, 2, 'Course Overview', 'What you will learn in this course', 'Welcome to Advanced JavaScript Mastery...', 'https://youtu.be/advjs1', '12:00', 1, 1, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(12, 2, 'Closures Deep Dive', 'Understanding closures in JavaScript', 'Closures are fundamental to JavaScript...', 'https://youtu.be/advjs2', '35:30', 2, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(13, 2, 'Prototypes and Inheritance', 'Master JavaScript object model', 'Learn how prototypal inheritance works...', 'https://youtu.be/advjs3', '40:15', 3, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(14, 2, 'Async/Await Mastery', 'Handle asynchronous code elegantly', 'Master promises and async/await patterns...', 'https://youtu.be/advjs4', '45:20', 4, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(15, 2, 'Design Patterns', 'Common JavaScript design patterns', 'Learn industry-standard design patterns...', 'https://youtu.be/advjs5', '50:10', 5, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(16, 6, 'Welcome to Data Science', 'Introduction to the course', 'Start your data science journey...', 'https://youtu.be/ds1', '15:00', 1, 1, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(17, 6, 'Python Basics for Data Science', 'Essential Python concepts', 'Learn Python fundamentals needed for data science...', 'https://youtu.be/ds2', '30:45', 2, 1, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(18, 6, 'NumPy Fundamentals', 'Working with arrays', 'NumPy is the foundation of data science in Python...', 'https://youtu.be/ds3', '38:20', 3, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(19, 6, 'Pandas DataFrames', 'Data manipulation with Pandas', 'Learn to work with tabular data using Pandas...', 'https://youtu.be/ds4', '45:30', 4, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(20, 6, 'Data Visualization', 'Create beautiful charts', 'Learn Matplotlib and Seaborn for data visualization...', 'https://youtu.be/ds5', '42:15', 5, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(21, 6, 'Machine Learning Basics', 'Introduction to ML', 'Start your machine learning journey...', 'https://youtu.be/ds6', '50:00', 6, 0, 'active', '2026-02-13 16:21:24', '2026-02-13 16:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `watch_time` int(11) DEFAULT 0 COMMENT 'In seconds',
  `last_position` int(11) DEFAULT 0 COMMENT 'Video position in seconds',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `user_id`, `lesson_id`, `course_id`, `is_completed`, `watch_time`, `last_position`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 630, 630, '2026-01-16 04:15:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(2, 1, 2, 1, 1, 1515, 1515, '2026-01-17 05:45:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(3, 1, 3, 1, 1, 1845, 1845, '2026-01-18 08:35:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(4, 1, 4, 1, 1, 2120, 2120, '2026-01-20 03:30:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(5, 1, 5, 1, 0, 1500, 1500, NULL, '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(6, 3, 11, 3, 1, 900, 900, '2026-01-23 04:15:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(7, 5, 16, 8, 1, 900, 900, '2026-02-02 06:15:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(8, 5, 17, 8, 1, 1230, 1230, '2026-02-05 09:45:00', '2026-02-13 16:21:24', '2026-02-13 16:21:24'),
(9, 5, 18, 8, 0, 800, 800, NULL, '2026-02-13 16:21:24', '2026-02-13 16:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(150) NOT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `user_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('esewa','khalti','cod','bank_transfer') DEFAULT 'esewa',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `order_status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `user_id`, `user_name`, `user_email`, `user_phone`, `user_address`, `total_amount`, `payment_method`, `payment_status`, `transaction_id`, `order_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-2026-00001', 1, 'Alice Johnson', 'alice@example.com', '+977-9841234567', 'Kathmandu, Nepal', 2999.00, 'esewa', 'completed', 'ESW1234567890', 'completed', NULL, '2026-01-15 04:45:00', '2026-02-13 16:21:23'),
(2, 'ORD-2026-00002', 2, 'Bob Smith', 'bob@example.com', '+977-9841234568', 'Pokhara, Nepal', 1999.00, 'khalti', 'completed', 'KHL0987654321', 'completed', NULL, '2026-01-18 08:35:00', '2026-02-13 16:21:23'),
(3, 'ORD-2026-00003', 3, 'Charlie Brown', 'charlie@example.com', '+977-9841234569', 'Lalitpur, Nepal', 4498.00, 'esewa', 'completed', 'ESW2345678901', 'completed', NULL, '2026-01-22 03:30:00', '2026-02-13 16:21:23'),
(4, 'ORD-2026-00004', 4, 'Diana Prince', 'diana@example.com', '+977-9841234570', 'Bhaktapur, Nepal', 3299.00, 'khalti', 'completed', 'KHL1234567890', 'completed', NULL, '2026-01-25 11:00:00', '2026-02-13 16:21:23'),
(5, 'ORD-2026-00005', 5, 'Ethan Hunt', 'ethan@example.com', '+977-9841234571', 'Biratnagar, Nepal', 1799.00, 'esewa', 'completed', 'ESW3456789012', 'completed', NULL, '2026-02-01 05:45:00', '2026-02-13 16:21:23'),
(6, 'ORD-2026-00006', 1, 'Alice Johnson', 'alice@example.com', '+977-9841234567', 'Kathmandu, Nepal', 5298.00, 'esewa', 'completed', 'ESW4567890123', 'completed', NULL, '2026-02-05 07:35:00', '2026-02-13 16:21:23'),
(7, 'ORD-2026-00007', 6, 'Fiona Green', 'fiona@example.com', '+977-9841234572', 'Chitwan, Nepal', 1499.00, 'khalti', 'completed', 'KHL2345678901', 'completed', NULL, '2026-02-08 09:25:00', '2026-02-13 16:21:23'),
(8, 'ORD-2026-00008', 7, 'George Miller', 'george@example.com', '+977-9841234573', 'Dharan, Nepal', 2899.00, 'esewa', 'pending', NULL, 'pending', NULL, '2026-02-12 04:15:00', '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_title`, `quantity`, `price`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 'Complete Web Development Bootcamp 2026', 1, 2999.00, 2999.00, '2026-02-13 16:21:23'),
(2, 2, 2, 'Advanced JavaScript Mastery', 1, 1999.00, 1999.00, '2026-02-13 16:21:23'),
(3, 3, 3, 'React - The Complete Guide', 1, 2499.00, 2499.00, '2026-02-13 16:21:23'),
(4, 3, 1, 'Complete Web Development Bootcamp 2026', 1, 1999.00, 1999.00, '2026-02-13 16:21:23'),
(5, 4, 6, 'Python for Data Science and Machine Learning', 1, 3299.00, 3299.00, '2026-02-13 16:21:23'),
(6, 5, 8, 'Complete Python Bootcamp', 1, 1799.00, 1799.00, '2026-02-13 16:21:23'),
(7, 6, 6, 'Python for Data Science and Machine Learning', 1, 3299.00, 3299.00, '2026-02-13 16:21:23'),
(8, 6, 11, 'SQL - Complete Bootcamp', 1, 1499.00, 1499.00, '2026-02-13 16:21:23'),
(9, 6, 18, 'Complete Web Design: UI/UX Bootcamp', 1, 2599.00, 2599.00, '2026-02-13 16:21:23'),
(10, 7, 11, 'SQL - Complete Bootcamp', 1, 1499.00, 1499.00, '2026-02-13 16:21:23'),
(11, 8, 16, 'Docker and Kubernetes: The Complete Guide', 1, 2899.00, 2899.00, '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `images` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL COMMENT 'e.g., 6 weeks, 3 months',
  `level` enum('beginner','intermediate','advanced','all_levels') DEFAULT 'all_levels',
  `instructor_name` varchar(100) DEFAULT NULL,
  `language` varchar(50) DEFAULT 'English',
  `requirements` text DEFAULT NULL,
  `what_you_learn` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `featured` tinyint(1) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `slug`, `description`, `short_description`, `category_id`, `price`, `discount_price`, `images`, `duration`, `level`, `instructor_name`, `language`, `requirements`, `what_you_learn`, `video_url`, `status`, `featured`, `total_students`, `rating`, `total_reviews`, `created_at`, `updated_at`) VALUES
(1, 'Complete Web Development Bootcamp 2026', 'complete-web-development-bootcamp-2026', 'Master full-stack web development with HTML5, CSS3, JavaScript, React, Node.js, MongoDB, and more. Build 20+ real-world projects and deploy them live. Perfect for beginners.', 'Become a full-stack web developer from scratch', 1, 4999.00, 2999.00, 'web-bootcamp.jpg', '16 weeks', 'beginner', 'Dr. Angela Yu', 'English', 'Basic computer skills|Internet connection|No prior coding experience needed', 'HTML5 and CSS3|JavaScript ES6+|React.js|Node.js and Express.js|MongoDB|RESTful APIs|Version Control with Git|Deployment on Heroku and Netlify', 'https://youtu.be/sample1', 'active', 1, 2456, 4.80, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(2, 'Advanced JavaScript Mastery', 'advanced-javascript-mastery', 'Deep dive into JavaScript concepts including closures, prototypes, async/await, design patterns, and modern ES6+ features. Build scalable applications.', 'Master advanced JavaScript concepts and patterns', 1, 3499.00, 1999.00, 'js-advanced.jpg', '8 weeks', 'advanced', 'Andrei Neagoie', 'English', 'Good understanding of JavaScript basics|Experience with at least one project', 'Advanced JavaScript patterns|Asynchronous programming|Functional programming|Object-oriented programming|Design patterns|Performance optimization|Testing with Jest', 'https://youtu.be/sample2', 'active', 1, 1523, 4.90, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(3, 'React - The Complete Guide', 'react-complete-guide', 'Master React.js including Hooks, Context API, Redux, Next.js, and testing. Build modern, reactive web applications with best practices.', 'Build powerful web apps with React', 1, 3999.00, 2499.00, 'react-guide.jpg', '12 weeks', 'intermediate', 'Maximilian Schwarzmüller', 'English', 'HTML, CSS, and JavaScript basics|Understanding of modern JavaScript (ES6+)', 'React fundamentals and Hooks|State management with Redux|Next.js for server-side rendering|Routing with React Router|Testing React apps|Performance optimization', 'https://youtu.be/sample3', 'active', 1, 3421, 4.70, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(4, 'iOS Development with Swift', 'ios-development-swift', 'Learn to build beautiful iOS apps using Swift and SwiftUI. From basics to App Store deployment. Build 10+ real iOS applications.', 'Create amazing iOS apps with Swift', 2, 4499.00, 2799.00, 'ios-swift.jpg', '14 weeks', 'beginner', 'Nick Walter', 'English', 'Mac computer with latest macOS|Xcode installed|Basic programming knowledge helpful', 'Swift programming language|SwiftUI framework|UIKit fundamentals|Core Data|Networking and APIs|App Store submission|iOS design patterns', 'https://youtu.be/sample4', 'active', 0, 856, 4.60, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(5, 'Android App Development Masterclass', 'android-development-masterclass', 'Master Android app development with Kotlin and Jetpack Compose. Build and publish apps on Google Play Store.', 'Build professional Android apps with Kotlin', 2, 4299.00, 2599.00, 'android-kotlin.jpg', '13 weeks', 'intermediate', 'Rob Percival', 'English', 'Basic programming knowledge|Android Studio installed|Willingness to learn', 'Kotlin programming|Jetpack Compose|Material Design|MVVM architecture|Room database|Retrofit for networking|Firebase integration|Google Play deployment', 'https://youtu.be/sample5', 'active', 1, 1234, 4.50, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(6, 'Python for Data Science and Machine Learning', 'python-data-science-ml', 'Complete data science bootcamp covering Python, NumPy, Pandas, Matplotlib, Scikit-learn, and TensorFlow. Work on real datasets.', 'Master data science with Python', 3, 5499.00, 3299.00, 'data-science.jpg', '16 weeks', 'beginner', 'Jose Portilla', 'English', 'Basic math knowledge|Computer with Python installed|No prior programming required', 'Python programming fundamentals|NumPy and Pandas|Data visualization with Matplotlib and Seaborn|Machine learning with Scikit-learn|Deep learning with TensorFlow|Natural language processing|Real-world projects', 'https://youtu.be/sample6', 'active', 1, 4567, 4.90, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(7, 'Machine Learning A-Z', 'machine-learning-a-z', 'Hands-on machine learning course covering regression, classification, clustering, deep learning, and more. Python and R included.', 'Master machine learning algorithms', 3, 4999.00, 2999.00, 'ml-az.jpg', '14 weeks', 'intermediate', 'Kirill Eremenko', 'English', 'High school mathematics|Basic Python or R knowledge', 'Regression algorithms|Classification techniques|Clustering methods|Deep learning|Natural language processing|Reinforcement learning|Model evaluation and selection', 'https://youtu.be/sample7', 'active', 0, 3245, 4.70, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(8, 'Complete Python Bootcamp', 'complete-python-bootcamp', 'Go from beginner to advanced Python developer. Learn Python 3, OOP, file handling, web scraping, automation, and much more.', 'Learn Python programming from zero to hero', 4, 3499.00, 1799.00, 'python-bootcamp.jpg', '10 weeks', 'beginner', 'Jose Portilla', 'English', 'Computer with internet|No programming experience needed', 'Python syntax and fundamentals|Object-oriented programming|File I/O operations|Web scraping|Automation scripts|Working with APIs|Python best practices', 'https://youtu.be/sample8', 'active', 1, 5678, 4.80, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(9, 'Java Programming Masterclass', 'java-programming-masterclass', 'Complete Java course from basics to advanced topics. Learn OOP, data structures, algorithms, JavaFX, Spring Boot, and more.', 'Master Java programming', 4, 3999.00, 2299.00, 'java-master.jpg', '15 weeks', 'intermediate', 'Tim Buchalka', 'English', 'Basic computer skills|Desire to learn programming', 'Java fundamentals|Object-oriented programming|Collections framework|Multithreading|Lambda expressions|Stream API|Spring Boot basics|Database connectivity with JDBC', 'https://youtu.be/sample9', 'active', 0, 2134, 4.60, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(10, 'C++ Complete Guide', 'cpp-complete-guide', 'Master C++ programming from basics to advanced concepts including STL, OOP, memory management, and modern C++17/20 features.', 'Learn C++ from beginner to expert', 4, 3799.00, 2199.00, 'cpp-guide.jpg', '12 weeks', 'intermediate', 'Frank Mitropoulos', 'English', 'Basic programming knowledge helpful|Computer setup', 'C++ syntax and fundamentals|Pointers and memory management|Object-oriented programming|STL containers and algorithms|Modern C++ features|Templates|Best practices', 'https://youtu.be/sample10', 'active', 0, 1567, 4.70, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(11, 'SQL - Complete Bootcamp', 'sql-complete-bootcamp', 'Master SQL from beginner to advanced. Learn queries, joins, subqueries, indexes, optimization, and database design principles.', 'Become an SQL expert', 5, 2999.00, 1499.00, 'sql-bootcamp.jpg', '6 weeks', 'beginner', 'Jose Portilla', 'English', 'Basic computer skills|No prior database knowledge required', 'SQL fundamentals|Complex queries and joins|Subqueries|Indexes and optimization|Database design|Stored procedures|Triggers|Views', 'https://youtu.be/sample11', 'active', 1, 3456, 4.80, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(12, 'MongoDB - The Complete Developer Guide', 'mongodb-complete-guide', 'Master MongoDB from scratch. Learn CRUD operations, aggregation, indexing, replication, sharding, and integration with Node.js.', 'Master MongoDB NoSQL database', 5, 3299.00, 1899.00, 'mongodb-guide.jpg', '8 weeks', 'intermediate', 'Maximilian Schwarzmüller', 'English', 'Basic JavaScript knowledge|Understanding of databases helpful', 'MongoDB fundamentals|CRUD operations|Aggregation framework|Indexing strategies|Replication and sharding|Integration with Node.js|MongoDB Atlas|Performance optimization', 'https://youtu.be/sample12', 'active', 0, 1876, 4.60, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(13, 'AWS Certified Solutions Architect', 'aws-solutions-architect', 'Complete AWS certification course covering EC2, S3, RDS, Lambda, CloudFormation, and more. Prepare for the certification exam.', 'Master AWS and get certified', 6, 5999.00, 3499.00, 'aws-architect.jpg', '12 weeks', 'intermediate', 'Stephane Maarek', 'English', 'Basic IT knowledge|Understanding of cloud concepts helpful', 'AWS core services|EC2 instances|S3 storage|VPC networking|RDS databases|Lambda functions|CloudFormation|Security best practices|Exam preparation', 'https://youtu.be/sample13', 'active', 1, 2345, 4.90, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(14, 'Microsoft Azure Fundamentals', 'azure-fundamentals', 'Learn Microsoft Azure cloud platform from scratch. Cover virtual machines, storage, networking, databases, and Azure services.', 'Master Microsoft Azure cloud', 6, 4499.00, 2699.00, 'azure-fundamentals.jpg', '10 weeks', 'beginner', 'Scott Duffy', 'English', 'Basic computer knowledge|Understanding of cloud helpful', 'Azure fundamentals|Virtual machines|Azure storage|Networking|Azure SQL Database|App Services|Azure Active Directory|Monitoring and management', 'https://youtu.be/sample14', 'active', 0, 1654, 4.50, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(15, 'Complete Ethical Hacking Bootcamp', 'ethical-hacking-bootcamp', 'Learn ethical hacking and penetration testing. Cover network security, web security, cryptography, and security tools.', 'Become an ethical hacker', 7, 5499.00, 3299.00, 'ethical-hacking.jpg', '14 weeks', 'advanced', 'Zaid Sabih', 'English', 'Basic networking knowledge|Linux fundamentals|Programming basics helpful', 'Penetration testing methodology|Network scanning|Vulnerability assessment|Web application security|Wireless security|Social engineering|Security tools (Metasploit, Burp Suite, Nmap)|Report writing', 'https://youtu.be/sample15', 'active', 1, 987, 4.80, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(16, 'Docker and Kubernetes: The Complete Guide', 'docker-kubernetes-guide', 'Master containerization with Docker and orchestration with Kubernetes. Build, deploy, and scale applications in production.', 'Master Docker and Kubernetes', 8, 4799.00, 2899.00, 'docker-kubernetes.jpg', '11 weeks', 'intermediate', 'Stephen Grider', 'English', 'Basic command line knowledge|Understanding of web applications', 'Docker fundamentals|Docker Compose|Kubernetes architecture|Deployments and services|ConfigMaps and secrets|Persistent volumes|Helm charts|CI/CD with containers', 'https://youtu.be/sample16', 'active', 1, 2134, 4.70, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(17, 'Complete CI/CD Pipeline Bootcamp', 'cicd-pipeline-bootcamp', 'Learn to build complete CI/CD pipelines using Jenkins, GitLab CI, GitHub Actions, and more. Automate your deployments.', 'Master CI/CD pipelines', 8, 3999.00, 2399.00, 'cicd-bootcamp.jpg', '9 weeks', 'intermediate', 'Tao W.', 'English', 'Basic Git knowledge|Understanding of development workflow', 'CI/CD fundamentals|Jenkins setup and configuration|GitLab CI/CD|GitHub Actions|Automated testing|Deployment strategies|Infrastructure as code|Monitoring and logging', 'https://youtu.be/sample17', 'active', 0, 1456, 4.60, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(18, 'Complete Web Design: UI/UX Bootcamp', 'web-design-ui-ux-bootcamp', 'Master UI/UX design using Figma and Adobe XD. Learn design principles, wireframing, prototyping, and responsive design.', 'Become a professional UI/UX designer', 9, 4299.00, 2599.00, 'ui-ux-bootcamp.jpg', '10 weeks', 'beginner', 'Vako Shvili', 'English', 'Basic computer skills|No design experience needed', 'Design fundamentals|Color theory|Typography|Wireframing|Prototyping with Figma|User research|Usability testing|Responsive design|Design systems', 'https://youtu.be/sample18', 'active', 1, 1876, 4.70, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(19, 'Complete Digital Marketing Course', 'complete-digital-marketing', 'Master digital marketing including SEO, social media marketing, email marketing, Google Ads, and analytics. Grow your business online.', 'Master all aspects of digital marketing', 10, 3799.00, 2199.00, 'digital-marketing.jpg', '12 weeks', 'beginner', 'Rob Percival', 'English', 'Basic internet knowledge|No marketing experience needed', 'SEO fundamentals|Social media marketing|Content marketing|Email marketing|Google Ads|Facebook Ads|Google Analytics|Conversion optimization|Marketing automation', 'https://youtu.be/sample19', 'active', 1, 2567, 4.60, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(20, 'SEO Training Masterclass', 'seo-training-masterclass', 'Complete SEO course covering on-page SEO, off-page SEO, technical SEO, local SEO, and advanced strategies to rank #1 on Google.', 'Master SEO and rank #1 on Google', 10, 3499.00, 1999.00, 'seo-masterclass.jpg', '8 weeks', 'intermediate', 'Ahrefs Academy', 'English', 'Basic website knowledge|Understanding of digital marketing helpful', 'SEO fundamentals|Keyword research|On-page optimization|Link building|Technical SEO|Local SEO|SEO tools (Ahrefs, SEMrush)|Content strategy|Analytics and reporting', 'https://youtu.be/sample20', 'active', 0, 1654, 4.80, 0, '2026-02-13 16:21:23', '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234567', 'Kathmandu, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(2, 'Bob Smith', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234568', 'Pokhara, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(3, 'Charlie Brown', 'charlie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234569', 'Lalitpur, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(4, 'Diana Prince', 'diana@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234570', 'Bhaktapur, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(5, 'Ethan Hunt', 'ethan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234571', 'Biratnagar, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(6, 'Fiona Green', 'fiona@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234572', 'Chitwan, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(7, 'George Miller', 'george@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234573', 'Dharan, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(8, 'Hannah Lee', 'hannah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234574', 'Butwal, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(9, 'Ivan Petrov', 'ivan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234575', 'Hetauda, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23'),
(10, 'Julia Roberts', 'julia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+977-9841234576', 'Janakpur, Nepal', NULL, 'active', '2026-02-13 16:21:23', '2026-02-13 16:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `course_id`, `created_at`) VALUES
(1, 1, 5, '2026-02-13 16:21:23'),
(2, 1, 7, '2026-02-13 16:21:23'),
(3, 2, 1, '2026-02-13 16:21:23'),
(4, 2, 6, '2026-02-13 16:21:23'),
(5, 3, 8, '2026-02-13 16:21:23'),
(6, 4, 13, '2026-02-13 16:21:23'),
(7, 5, 2, '2026-02-13 16:21:23'),
(8, 6, 14, '2026-02-13 16:21:23'),
(9, 7, 19, '2026-02-13 16:21:23'),
(10, 8, 20, '2026-02-13 16:21:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- Indexes for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_completion_status` (`completion_status`);

--
-- Indexes for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`course_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_invoice_number` (`invoice_number`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_price` (`price`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`course_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `course_reviews`
--
ALTER TABLE `course_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_enrollments_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD CONSTRAINT `course_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_reviews_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
