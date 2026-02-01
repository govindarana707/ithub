-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Jan 31, 2026 at 01:18 PM
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
-- Database: `it_hub_clean`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--
-- Error reading structure for table it_hub_clean.admin_logs: #1932 - Table 'it_hub_clean.admin_logs' doesn't exist in engine
-- Error reading data for table it_hub_clean.admin_logs: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`admin_logs`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `categories_new`
--

CREATE TABLE `categories_new` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories_new`
--

INSERT INTO `categories_new` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Programming', 'Learn various programming languages', '2026-01-31 12:15:54'),
(2, 'Web Development', 'Modern web development technologies', '2026-01-31 12:15:54'),
(3, 'Design', 'UI/UX and graphic design', '2026-01-31 12:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--
-- Error reading structure for table it_hub_clean.certificates: #1932 - Table 'it_hub_clean.certificates' doesn't exist in engine
-- Error reading data for table it_hub_clean.certificates: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`certificates`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--
-- Error reading structure for table it_hub_clean.chat_messages: #1932 - Table 'it_hub_clean.chat_messages' doesn't exist in engine
-- Error reading data for table it_hub_clean.chat_messages: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`chat_messages`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `completed_lessons`
--
-- Error reading structure for table it_hub_clean.completed_lessons: #1932 - Table 'it_hub_clean.completed_lessons' doesn't exist in engine
-- Error reading data for table it_hub_clean.completed_lessons: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`completed_lessons`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `courses_new`
--

CREATE TABLE `courses_new` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT 1,
  `instructor_id` int(11) NOT NULL DEFAULT 1,
  `thumbnail` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` int(11) DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses_new`
--

INSERT INTO `courses_new` (`id`, `title`, `description`, `category_id`, `instructor_id`, `thumbnail`, `price`, `duration_hours`, `difficulty_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PHP Fundamentals', 'Learn the basics of PHP programming', 1, 1, 'php-course.jpg', 29.99, 20, 'beginner', 'published', '2026-01-31 12:15:22', '2026-01-31 12:15:22'),
(2, 'Advanced JavaScript', 'Master advanced JavaScript concepts', 2, 1, 'js-course.jpg', 49.99, 30, 'advanced', 'published', '2026-01-31 12:15:22', '2026-01-31 12:15:22'),
(3, 'Web Design Basics', 'Introduction to modern web design', 3, 1, 'design-course.jpg', 39.99, 15, 'beginner', 'published', '2026-01-31 12:15:22', '2026-01-31 12:15:22');

-- --------------------------------------------------------

--
-- Table structure for table `course_meta`
--
-- Error reading structure for table it_hub_clean.course_meta: #1932 - Table 'it_hub_clean.course_meta' doesn't exist in engine
-- Error reading data for table it_hub_clean.course_meta: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`course_meta`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `course_progress`
--
-- Error reading structure for table it_hub_clean.course_progress: #1932 - Table 'it_hub_clean.course_progress' doesn't exist in engine
-- Error reading data for table it_hub_clean.course_progress: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`course_progress`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `course_reviews`
--
-- Error reading structure for table it_hub_clean.course_reviews: #1932 - Table 'it_hub_clean.course_reviews' doesn't exist in engine
-- Error reading data for table it_hub_clean.course_reviews: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`course_reviews`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--
-- Error reading structure for table it_hub_clean.discussions: #1932 - Table 'it_hub_clean.discussions' doesn't exist in engine
-- Error reading data for table it_hub_clean.discussions: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`discussions`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--
-- Error reading structure for table it_hub_clean.enrollments: #1932 - Table 'it_hub_clean.enrollments' doesn't exist in engine
-- Error reading data for table it_hub_clean.enrollments: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`enrollments`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--
-- Error reading structure for table it_hub_clean.feedback: #1932 - Table 'it_hub_clean.feedback' doesn't exist in engine
-- Error reading data for table it_hub_clean.feedback: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`feedback`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `instructor_activity_log`
--
-- Error reading structure for table it_hub_clean.instructor_activity_log: #1932 - Table 'it_hub_clean.instructor_activity_log' doesn't exist in engine
-- Error reading data for table it_hub_clean.instructor_activity_log: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`instructor_activity_log`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `instructor_meta`
--
-- Error reading structure for table it_hub_clean.instructor_meta: #1932 - Table 'it_hub_clean.instructor_meta' doesn't exist in engine
-- Error reading data for table it_hub_clean.instructor_meta: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`instructor_meta`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--
-- Error reading structure for table it_hub_clean.lessons: #1932 - Table 'it_hub_clean.lessons' doesn't exist in engine
-- Error reading data for table it_hub_clean.lessons: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`lessons`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `lesson_materials`
--
-- Error reading structure for table it_hub_clean.lesson_materials: #1932 - Table 'it_hub_clean.lesson_materials' doesn't exist in engine
-- Error reading data for table it_hub_clean.lesson_materials: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`lesson_materials`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--
-- Error reading structure for table it_hub_clean.notifications: #1932 - Table 'it_hub_clean.notifications' doesn't exist in engine
-- Error reading data for table it_hub_clean.notifications: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`notifications`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--
-- Error reading structure for table it_hub_clean.quizzes: #1932 - Table 'it_hub_clean.quizzes' doesn't exist in engine
-- Error reading data for table it_hub_clean.quizzes: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`quizzes`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--
-- Error reading structure for table it_hub_clean.quiz_answers: #1932 - Table 'it_hub_clean.quiz_answers' doesn't exist in engine
-- Error reading data for table it_hub_clean.quiz_answers: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`quiz_answers`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--
-- Error reading structure for table it_hub_clean.quiz_attempts: #1932 - Table 'it_hub_clean.quiz_attempts' doesn't exist in engine
-- Error reading data for table it_hub_clean.quiz_attempts: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`quiz_attempts`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `quiz_options`
--
-- Error reading structure for table it_hub_clean.quiz_options: #1932 - Table 'it_hub_clean.quiz_options' doesn't exist in engine
-- Error reading data for table it_hub_clean.quiz_options: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`quiz_options`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--
-- Error reading structure for table it_hub_clean.quiz_questions: #1932 - Table 'it_hub_clean.quiz_questions' doesn't exist in engine
-- Error reading data for table it_hub_clean.quiz_questions: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `it_hub_clean`.`quiz_questions`' at line 1

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
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_new`
--

INSERT INTO `users_new` (`id`, `username`, `email`, `password`, `full_name`, `role`, `profile_image`, `bio`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', NULL, NULL, NULL, 'active', '2026-01-31 12:16:05', '2026-01-31 12:16:05'),
(2, 'instructor1', 'instructor@ithub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Instructor', 'instructor', NULL, NULL, NULL, 'active', '2026-01-31 12:16:05', '2026-01-31 12:16:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories_new`
--
ALTER TABLE `categories_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses_new`
--
ALTER TABLE `courses_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_new`
--
ALTER TABLE `users_new`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories_new`
--
ALTER TABLE `categories_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses_new`
--
ALTER TABLE `courses_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users_new`
--
ALTER TABLE `users_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
