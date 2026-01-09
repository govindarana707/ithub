-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Jan 08, 2026 at 05:16 PM
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
(1, 1, 'login', 'User logged in from IP: ::1', NULL, NULL, '2026-01-08 15:55:24'),
(2, 1, 'course_updated', 'Updated course ID: 9', NULL, NULL, '2026-01-08 16:01:38');

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
(1, 'IT Fundamentals', 'Basic computer and IT concepts for beginners', '2026-01-08 12:07:56'),
(2, 'Programming', 'Learn various programming languages and concepts', '2026-01-08 12:07:56'),
(3, 'Web Development', 'Frontend and backend web development technologies', '2026-01-08 12:07:56'),
(4, 'Database', 'Database design and management systems', '2026-01-08 12:07:56'),
(5, 'Cybersecurity', 'Information security and ethical hacking', '2026-01-08 12:07:56'),
(6, 'Mobile Development', 'iOS and Android app development', '2026-01-08 12:07:56'),
(7, 'Cloud Computing', 'Cloud platforms and services', '2026-01-08 12:07:56'),
(8, 'Data Science', 'Data analysis, machine learning, and AI', '2026-01-08 12:07:56'),
(9, 'IT Fundamentals', 'Basic computer and IT concepts for beginners', '2026-01-08 13:10:14'),
(10, 'Programming', 'Learn various programming languages and concepts', '2026-01-08 13:10:14'),
(11, 'Web Development', 'Frontend and backend web development technologies', '2026-01-08 13:10:14'),
(12, 'Database', 'Database design and management systems', '2026-01-08 13:10:14'),
(13, 'Cybersecurity', 'Information security and ethical hacking', '2026-01-08 13:10:14'),
(14, 'Mobile Development', 'iOS and Android app development', '2026-01-08 13:10:14'),
(15, 'Cloud Computing', 'Cloud platforms and services', '2026-01-08 13:10:14'),
(16, 'Data Science', 'Data analysis, machine learning, and AI', '2026-01-08 13:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(50) NOT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','file') DEFAULT 'text',
  `file_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `course_id`, `message`, `message_type`, `file_path`, `is_read`, `created_at`) VALUES
(1, 2, 3, 4, 'Welcome to the course! Feel free to ask any questions.', 'text', NULL, 0, '2026-01-08 12:07:56'),
(2, 3, 2, 4, 'Thank you! I\'m excited to learn web development.', 'text', NULL, 0, '2026-01-08 12:07:56'),
(3, 2, 4, 4, 'Great progress on your assignments! Keep up the good work.', 'text', NULL, 0, '2026-01-08 12:07:56'),
(4, 4, 2, 4, 'Thanks! The lessons are very helpful.', 'text', NULL, 0, '2026-01-08 12:07:56');

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
(1, 'Web Development Fundamentals', 'Learn HTML, CSS, and JavaScript from scratch. Build responsive websites and understand modern web development practices.', 3, 2, NULL, 49.99, 40, 'beginner', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(2, 'Advanced PHP Programming', 'Master PHP with advanced concepts including OOP, MVC patterns, and framework development.', 2, 2, NULL, 79.99, 60, 'advanced', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(3, 'Database Design and SQL', 'Learn database design principles and SQL programming. Work with MySQL and understand relational databases.', 4, 1, NULL, 59.99, 45, 'intermediate', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(4, 'Cybersecurity Essentials', 'Introduction to cybersecurity concepts, ethical hacking, and security best practices for IT professionals.', 5, 1, NULL, 89.99, 50, 'intermediate', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(5, 'Mobile App Development with React Native', 'Build cross-platform mobile applications using React Native framework.', 6, 2, NULL, 99.99, 70, 'advanced', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(6, 'Cloud Computing with AWS', 'Master Amazon Web Services and cloud computing concepts for modern IT infrastructure.', 7, 1, NULL, 119.99, 80, 'advanced', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(7, 'Data Science with Python', 'Learn data analysis, visualization, and machine learning using Python and popular libraries.', 8, 2, NULL, 109.99, 90, 'advanced', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(8, 'Network Administration', 'Comprehensive guide to network administration, protocols, and troubleshooting.', 1, 1, NULL, 69.99, 55, 'intermediate', 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(9, 'Test-web', 'Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstoremodelsInstructor.php:308 Stack trace: #0 C:xampphtdocsstoreinstructordashboard.php(32): Instructor-&amp;gt;getInstructorAnalytics(9, \'30days\') #1 {main} thrown in C:xampphtdocsstoremodelsInstructor.php on line 308Fatal error: Uncaught Error: Call to a member function bind_param() on bool in C:xampphtdocsstoremodelsInstructor.php:308 Stack trace: #0 C:xampphtdocsstoreinstructordashboard.php(32): Instructor-&amp;gt;getInstructorAnalytics(9, \'30days\') #1 {main} thrown in C:xampphtdocsstoremodelsInstructor.php on line 308', 3, 9, 'course_thumbnails/695fd4e2102ab.png', 1200.00, 30, 'beginner', 'draft', '2026-01-08 12:42:13', '2026-01-08 16:01:38');

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
-- Table structure for table `course_progress`
--

CREATE TABLE `course_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completion_time` timestamp NULL DEFAULT NULL,
  `time_spent_minutes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_progress`
--

INSERT INTO `course_progress` (`id`, `student_id`, `course_id`, `lesson_id`, `is_completed`, `completion_time`, `time_spent_minutes`, `created_at`, `updated_at`) VALUES
(1, 3, 4, 1, 1, NULL, 30, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(2, 3, 4, 2, 1, NULL, 45, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(3, 3, 4, 3, 1, NULL, 40, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(4, 3, 4, 4, 1, NULL, 50, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(5, 3, 4, 5, 0, NULL, 20, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(6, 4, 4, 1, 1, NULL, 25, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(7, 4, 4, 2, 1, NULL, 40, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(8, 4, 4, 3, 1, NULL, 35, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(9, 4, 4, 4, 0, NULL, 30, '2026-01-08 12:07:56', '2026-01-08 12:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `course_reviews`
--

CREATE TABLE `course_reviews` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
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
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `course_id`, `student_id`, `title`, `content`, `parent_id`, `is_pinned`, `is_resolved`, `created_at`, `updated_at`) VALUES
(1, 4, 3, 'Best practices for responsive design?', 'I\'m working on making my website responsive. What are the best practices for breakpoints and mobile-first design?', NULL, 0, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(2, 4, 4, 'JavaScript frameworks vs vanilla JS', 'Should I learn a framework like React or focus on vanilla JavaScript first as a beginner?', NULL, 0, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(3, 5, 3, 'When to use static vs instance methods?', 'I\'m confused about when to use static methods versus instance methods in PHP OOP. Can someone explain?', NULL, 0, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(4, 5, 4, 'Best MVC framework for PHP?', 'Which MVC framework would you recommend for a beginner: Laravel, Symfony, or CodeIgniter?', NULL, 0, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56');

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
(1, 3, 4, '2026-01-08 12:07:56', NULL, 75.00, 'active'),
(2, 3, 5, '2026-01-08 12:07:56', NULL, 40.00, 'active'),
(3, 4, 4, '2026-01-08 12:07:56', NULL, 60.00, 'active'),
(4, 4, 5, '2026-01-08 12:07:56', NULL, 85.00, 'active'),
(5, 5, 4, '2026-01-08 12:07:56', NULL, 30.00, 'active'),
(6, 5, 6, '2026-01-08 12:07:56', NULL, 20.00, 'active'),
(7, 4, 1, '2026-01-08 12:10:01', NULL, 0.00, 'active'),
(10, 16, 9, '2026-01-08 14:24:10', NULL, 0.00, 'active'),
(11, 16, 1, '2026-01-08 14:26:16', NULL, 0.00, 'active'),
(12, 16, 2, '2026-01-08 14:44:32', NULL, 0.00, 'active'),
(13, 16, 4, '2026-01-08 14:54:00', NULL, 0.00, 'active'),
(14, 16, 3, '2026-01-08 15:06:26', NULL, 0.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `course_id`, `student_id`, `instructor_id`, `rating`, `review`, `is_public`, `created_at`) VALUES
(1, 4, 3, 2, 5, 'Excellent course! The instructor explains concepts clearly and the projects are very practical.', 1, '2026-01-08 12:07:56'),
(2, 4, 4, 2, 4, 'Great content and well-structured. Would love to see more advanced topics covered.', 1, '2026-01-08 12:07:56'),
(3, 5, 3, 2, 5, 'Perfect for understanding PHP OOP. The examples are real-world and very helpful.', 1, '2026-01-08 12:07:56'),
(4, 5, 4, 2, 4, 'Comprehensive coverage of MVC. Could use more hands-on exercises though.', 1, '2026-01-08 12:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_activity_log`
--

CREATE TABLE `instructor_activity_log` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_activity_log`
--

INSERT INTO `instructor_activity_log` (`id`, `instructor_id`, `action`, `details`, `course_id`, `created_at`) VALUES
(1, 9, 'course_created', 'Created course: Test-web', 9, '2026-01-08 12:42:13'),
(2, 9, 'course_created', 'Created course: Integration Test Course 18:32:19', NULL, '2026-01-08 12:47:19'),
(3, 9, 'course_updated', 'Updated course ID: 10', NULL, '2026-01-08 12:47:19');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `video_url`, `lesson_order`, `lesson_type`, `duration_minutes`, `is_free`, `created_at`, `updated_at`) VALUES
(1, 4, 'Introduction to HTML', 'Learn the basics of HTML5, semantic tags, and document structure.', NULL, 1, 'text', 30, 1, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(2, 4, 'HTML Forms and Input', 'Master HTML forms, input types, validation, and user interaction.', NULL, 2, 'text', 45, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(3, 4, 'CSS Fundamentals', 'Introduction to CSS, selectors, properties, and basic styling.', NULL, 3, 'text', 40, 1, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(4, 4, 'Responsive Design with Flexbox', 'Learn modern CSS layout techniques with Flexbox for responsive designs.', NULL, 4, 'text', 50, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(5, 4, 'JavaScript Basics', 'Introduction to JavaScript programming, variables, functions, and DOM manipulation.', NULL, 5, 'text', 60, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(6, 4, 'Building Your First Website', 'Combine HTML, CSS, and JavaScript to build a complete website project.', NULL, 6, 'text', 90, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(7, 5, 'Object-Oriented PHP', 'Master OOP concepts in PHP including classes, inheritance, and polymorphism.', NULL, 1, 'text', 60, 1, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(8, 5, 'MVC Architecture', 'Understanding and implementing Model-View-Controller pattern in PHP.', NULL, 2, 'text', 75, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(9, 5, 'Database Integration', 'Advanced database operations with PDO and prepared statements.', NULL, 3, 'text', 65, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(10, 5, 'REST API Development', 'Building RESTful APIs with PHP for modern web applications.', NULL, 4, 'text', 80, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(11, 5, 'Security Best Practices', 'Implementing security measures including authentication, authorization, and data protection.', NULL, 5, 'text', 70, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(12, 5, 'Performance Optimization', 'Techniques for optimizing PHP applications for better performance.', NULL, 6, 'text', 55, 0, '2026-01-08 12:07:56', '2026-01-08 12:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_materials`
--

CREATE TABLE `lesson_materials` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `notification_type`, `is_read`, `created_at`) VALUES
(1, 3, 'New Course Available', 'Check out our new course on Advanced PHP Programming!', 'info', 0, '2026-01-08 12:07:56'),
(2, 3, 'Quiz Reminder', 'Don\'t forget to complete your JavaScript Fundamentals Quiz', 'warning', 0, '2026-01-08 12:07:56'),
(3, 4, 'Course Updated', 'Web Development Fundamentals has new content available', 'success', 0, '2026-01-08 12:07:56'),
(4, 5, 'Welcome to IT HUB', 'Get started with your learning journey today!', 'info', 0, '2026-01-08 12:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT 30,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `max_attempts` int(11) DEFAULT 3,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `lesson_id`, `title`, `description`, `time_limit_minutes`, `passing_score`, `max_attempts`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 'HTML & CSS Basics Quiz', 'Test your knowledge of HTML and CSS fundamentals covered in the first few lessons.', 30, 70.00, 3, 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(2, 4, NULL, 'JavaScript Fundamentals Quiz', 'Evaluate your understanding of JavaScript basics and DOM manipulation.', 45, 75.00, 3, 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(3, 5, NULL, 'PHP OOP Quiz', 'Test your knowledge of object-oriented programming concepts in PHP.', 40, 80.00, 3, 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(4, 5, NULL, 'MVC Architecture Quiz', 'Evaluate your understanding of MVC pattern and its implementation.', 35, 75.00, 3, 'published', '2026-01-08 12:07:56', '2026-01-08 12:07:56'),
(5, 9, NULL, 'sqasqwseqw', 'wqwqwsq', 30, 70.00, 3, 'published', '2026-01-08 15:14:32', '2026-01-08 15:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `answer_text`, `is_correct`, `points_earned`, `created_at`) VALUES
(1, 1, 1, 1, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(2, 1, 2, 2, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(3, 1, 3, 1, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(4, 1, 4, 2, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(5, 1, 5, 2, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(6, 2, 1, 1, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(7, 2, 2, 1, NULL, 0, 0.00, '2026-01-08 12:07:56'),
(8, 2, 3, 1, NULL, 1, 1.00, '2026-01-08 12:07:56'),
(9, 2, 4, 1, NULL, 0, 0.00, '2026-01-08 12:07:56'),
(10, 2, 5, 1, NULL, 0, 0.00, '2026-01-08 12:07:56'),
(11, 4, 1, NULL, '1', 0, 0.00, '2026-01-08 15:32:44'),
(12, 4, 2, NULL, '6', 0, 0.00, '2026-01-08 15:32:44'),
(13, 4, 3, NULL, '9', 0, 0.00, '2026-01-08 15:32:44'),
(14, 4, 4, NULL, '16', 0, 0.00, '2026-01-08 15:32:44'),
(15, 4, 5, NULL, '18', 0, 0.00, '2026-01-08 15:32:44');

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
(1, 3, 1, 1, '2026-01-08 12:07:56', NULL, 4.00, 5.00, 80.00, 1, 'completed'),
(2, 4, 1, 1, '2026-01-08 12:07:56', NULL, 3.00, 5.00, 60.00, 0, 'completed'),
(3, 3, 2, 1, '2026-01-08 12:07:56', NULL, 0.00, 0.00, 0.00, 0, 'in_progress'),
(4, 16, 1, 1, '2026-01-08 15:32:10', '2026-01-08 15:32:44', 0.00, 5.00, 0.00, 0, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_options`
--

INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `is_correct`, `option_order`) VALUES
(1, 1, 'Hyper Text Markup Language', 1, 1),
(2, 1, 'High Tech Modern Language', 0, 2),
(3, 1, 'Home Tool Markup Language', 0, 3),
(4, 1, 'Hyperlinks and Text Markup Language', 0, 4),
(5, 2, 'text-color', 0, 1),
(6, 2, 'color', 1, 2),
(7, 2, 'font-color', 0, 3),
(8, 2, 'text-style', 0, 4),
(9, 3, 'To specify the HTML version', 1, 1),
(10, 3, 'To create comments', 0, 2),
(11, 3, 'To link CSS files', 0, 3),
(12, 3, 'To define metadata', 0, 4),
(13, 4, '<navigation>', 0, 1),
(14, 4, '<nav>', 1, 2),
(15, 4, '<menu>', 0, 3),
(16, 4, '<navbar>', 0, 4),
(17, 5, 'Creating animations', 0, 1),
(18, 5, 'Creating flexible layouts', 1, 2),
(19, 5, 'Adding colors', 0, 3),
(20, 5, 'Creating shadows', 0, 4);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `question_order`, `created_at`) VALUES
(1, 1, 'What does HTML stand for?', 'multiple_choice', 1.00, 1, '2026-01-08 12:07:56'),
(2, 1, 'Which CSS property is used to change the text color of an element?', 'multiple_choice', 1.00, 2, '2026-01-08 12:07:56'),
(3, 1, 'What is the purpose of the DOCTYPE declaration in HTML?', 'multiple_choice', 1.00, 3, '2026-01-08 12:07:56'),
(4, 1, 'Which HTML5 element is used for navigation links?', 'multiple_choice', 1.00, 4, '2026-01-08 12:07:56'),
(5, 1, 'CSS Flexbox is used for:', 'multiple_choice', 1.00, 5, '2026-01-08 12:07:56');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `profile_image`, `bio`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@ithub.com', '$2y$10$bAWW.DNeiicnSZbB/epKBO2sda.cJ8JhEFoU4PHqIydtItKFo2A0G', 'System Administrator', 'admin', NULL, NULL, NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:27:27'),
(2, 'john_instructor', 'john@ithub.com', '$2y$10$V1w/ZShasnOezFYGovYOHOCey87hB0rOFsIGWsB385vEbqf.kJ7Ii', 'John Smith', 'instructor', NULL, 'Experienced web developer with 10+ years in industry', NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:11:59'),
(3, 'sarah_instructor', 'sarah@ithub.com', '$2y$10$V1w/ZShasnOezFYGovYOHOCey87hB0rOFsIGWsB385vEbqf.kJ7Ii', 'Sarah Johnson', 'instructor', NULL, 'Data science expert and machine learning specialist', NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:11:59'),
(4, 'alice_student', 'alice@ithub.com', '$2y$10$UOu/dvWQCOIGsrS/WbBty.TthmZcKxzhpweUWqHaFXXJmaBIloi5u', 'Alice Wilson', 'student', NULL, NULL, NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:11:59'),
(5, 'bob_student', 'bob@ithub.com', '$2y$10$UOu/dvWQCOIGsrS/WbBty.TthmZcKxzhpweUWqHaFXXJmaBIloi5u', 'Bob Brown', 'student', NULL, NULL, NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:11:59'),
(6, 'charlie_student', 'charlie@ithub.com', '$2y$10$UOu/dvWQCOIGsrS/WbBty.TthmZcKxzhpweUWqHaFXXJmaBIloi5u', 'Charlie Davis', 'student', NULL, NULL, NULL, 'active', '2026-01-08 12:07:56', '2026-01-08 12:11:59'),
(7, 'ball', 'ball@ball.com', '$2y$10$pj7A0dkwXgXgexwxrW7up.Rm1mrwfRCIKcn2WZORyJo4I3zeNESBy', 'Ball', 'instructor', NULL, NULL, '9766655262', 'active', '2026-01-08 12:22:18', '2026-01-08 12:22:18'),
(8, 'amir', 'bcarjun2075@gmail.com', '$2y$10$8ASGhgyVtfkV91RX6ZjoPOTcOslymvuv3Gnv5HTC.aFknStCutLmO', 'Amir B.C', 'student', NULL, NULL, '9766655262', 'active', '2026-01-08 12:24:20', '2026-01-08 12:24:20'),
(9, 'teach', 'teach@test.com', '$2y$10$CuQMNsLwlrCQ/dunARsoy.zFDVCvW/P0Yb4TiEVhqdfvKzjP4oVyC', 'Teacher', 'instructor', NULL, NULL, 'teach@test.com', 'active', '2026-01-08 12:35:07', '2026-01-08 12:35:07'),
(16, 'student', 'student@test.com', '$2y$10$dUX6fo0b7noewPT5FUeNx.WXU5VcooNFq2B79Tp4v7ZYlOEK/Tpli', 'Student', 'student', NULL, NULL, '9766655262', 'active', '2026-01-08 13:16:37', '2026-01-08 13:16:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_chat_messages_sender` (`sender_id`),
  ADD KEY `idx_chat_messages_receiver` (`receiver_id`);

--
-- Indexes for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_lesson` (`student_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `course_meta`
--
ALTER TABLE `course_meta`
  ADD PRIMARY KEY (`course_id`,`meta_key`);

--
-- Indexes for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`student_id`,`course_id`,`lesson_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_course_progress_student` (`student_id`);

--
-- Indexes for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`course_id`,`student_id`),
  ADD KEY `fk_course_review_student` (`student_id`),
  ADD KEY `idx_course_reviews_course` (`course_id`),
  ADD KEY `idx_course_reviews_rating` (`rating`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_discussions_course` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `idx_enrollments_student` (`student_id`),
  ADD KEY `idx_enrollments_course` (`course_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `idx_feedback_course` (`course_id`);

--
-- Indexes for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_instructor_activity_course` (`course_id`),
  ADD KEY `idx_instructor_activity_instructor` (`instructor_id`),
  ADD KEY `idx_instructor_activity_created` (`created_at`);

--
-- Indexes for table `instructor_meta`
--
ALTER TABLE `instructor_meta`
  ADD PRIMARY KEY (`instructor_id`,`meta_key`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_course` (`course_id`);

--
-- Indexes for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_quizzes_course` (`course_id`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `selected_option_id` (`selected_option_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_attempts_student` (`student_id`),
  ADD KEY `idx_quiz_attempts_quiz` (`quiz_id`);

--
-- Indexes for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `course_progress`
--
ALTER TABLE `course_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `course_reviews`
--
ALTER TABLE `course_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_messages_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `completed_lessons`
--
ALTER TABLE `completed_lessons`
  ADD CONSTRAINT `completed_lessons_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `completed_lessons_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_meta`
--
ALTER TABLE `course_meta`
  ADD CONSTRAINT `fk_course_meta_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD CONSTRAINT `course_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_progress_ibfk_3` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD CONSTRAINT `fk_course_review_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_course_review_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discussions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discussions_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_activity_log`
--
ALTER TABLE `instructor_activity_log`
  ADD CONSTRAINT `fk_instructor_activity_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_instructor_activity_user` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_meta`
--
ALTER TABLE `instructor_meta`
  ADD CONSTRAINT `fk_instructor_meta_user` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD CONSTRAINT `lesson_materials_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `quiz_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_answers_ibfk_3` FOREIGN KEY (`selected_option_id`) REFERENCES `quiz_options` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `quiz_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
