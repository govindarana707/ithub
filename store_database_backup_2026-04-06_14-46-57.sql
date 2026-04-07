-- IT Hub Database Backup
-- Generated: 2026-04-06 14:46:57
-- Database: it_hub_new


-- Table structure for table `account_lockouts`
DROP TABLE IF EXISTS `account_lockouts`;
CREATE TABLE `account_lockouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL,
  `locked_until` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `admin_logs`
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `admin_logs`
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('1','2','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 18:17:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('2','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 18:20:25');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('3','1','login','User logged in from IP: 127.0.0.1',NULL,NULL,'2026-01-31 20:38:37');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('4','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 20:39:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('5','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 20:39:53');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('6','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 20:40:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('7','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 20:44:06');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('8','5','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:02:18');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('9','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:08:22');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('10','1','course_updated','Updated course ID: 7',NULL,NULL,'2026-01-31 21:09:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('11','1','user_deleted','Deleted user ID: 4',NULL,NULL,'2026-01-31 21:09:51');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('12','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:11:18');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('13','3','enroll_course','Enrolled in course: Web Development Bootcamp (ID: 7) via trial',NULL,NULL,'2026-01-31 21:13:26');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('14','3','enroll_course','Enrolled in course: Cybersecurity Essentials (ID: 10) via trial',NULL,NULL,'2026-01-31 21:13:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('15','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:14:43');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('16','3','enroll_course','Enrolled in course: Ethical Hacking & Penetration Testing (ID: 13) via trial',NULL,NULL,'2026-01-31 21:18:44');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('17','3','lesson_completed','Completed lesson: Introduction to Databases',NULL,NULL,'2026-01-31 21:19:31');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('18','3','lesson_completed','Completed lesson: Database Design Principles',NULL,NULL,'2026-01-31 21:21:07');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('19','3','lesson_completed','Completed lesson: SQL Basics - SELECT and Queries',NULL,NULL,'2026-01-31 21:21:13');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('20','3','lesson_completed','Completed lesson: SQL Data Manipulation',NULL,NULL,'2026-01-31 21:44:24');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('21','3','lesson_completed','Completed lesson: Advanced SQL and Joins',NULL,NULL,'2026-01-31 21:46:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('22','3','lesson_completed','Completed lesson: Advanced SQL Queries and Subqueries',NULL,NULL,'2026-01-31 21:46:09');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('23','3','lesson_completed','Completed lesson: Database Indexing and Performance',NULL,NULL,'2026-01-31 21:46:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('24','3','lesson_completed','Completed lesson: Database Security and Permissions',NULL,NULL,'2026-01-31 21:46:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('25','3','lesson_completed','Completed lesson: NoSQL Databases',NULL,NULL,'2026-01-31 21:46:17');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('26','3','lesson_completed','Completed lesson: Database Backup and Recovery',NULL,NULL,'2026-01-31 21:46:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('27','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:51:33');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('28','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 21:54:09');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('29','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 22:17:08');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('30','1','course_updated','Updated course ID: 13',NULL,NULL,'2026-01-31 22:42:36');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('31','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 23:10:15');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('32','1','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 23:49:41');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('33','3','login','User logged in from IP: ::1',NULL,NULL,'2026-01-31 23:52:02');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('34','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 16:14:10');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('35','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 18:37:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('36','3','profile_updated','Updated profile information',NULL,NULL,'2026-02-01 18:50:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('37','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 18:54:11');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('38','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 18:55:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('39','2','course_created','Created course: test for web',NULL,NULL,'2026-02-01 19:00:06');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('40','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 19:33:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('41','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 20:43:22');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('42','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:03:38');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('43','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:33:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('44','3','enroll_course','Enrolled in course: UI/UX Design Bootcamp (ID: 15) via trial',NULL,NULL,'2026-02-01 21:33:18');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('45','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:34:07');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('46','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:35:51');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('47','6','enroll_course','Enrolled in course: Ethical Hacking &amp; Penetration Testing (ID: 13) via trial',NULL,NULL,'2026-02-01 21:36:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('48','6','enroll_course','Enrolled in course: Cybersecurity Fundamentals (ID: 14) via trial',NULL,NULL,'2026-02-01 21:38:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('49','6','enroll_course','Enrolled in course: UI/UX Design Bootcamp (ID: 15) via trial',NULL,NULL,'2026-02-01 21:38:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('50','6','enroll_course','Enrolled in course: Advanced UX Research & Strategy (ID: 16) via trial',NULL,NULL,'2026-02-01 21:40:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('51','6','enroll_course','Enrolled in course: Unreal Engine 5 Game Development (ID: 18) via trial',NULL,NULL,'2026-02-01 21:46:33');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('52','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:52:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('53','6','enroll_course','Enrolled in course: Unity Game Development Complete Course (ID: 17) via trial',NULL,NULL,'2026-02-01 21:53:01');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('54','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:57:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('55','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:57:30');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('56','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 21:57:54');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('57','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-01 22:00:42');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('58','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-05 17:29:23');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('59','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 19:11:34');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('60','6','trial_activity','{\"activity\":\"reminders_scheduled\",\"course_id\":6,\"description\":\"Trial reminders scheduled\"}',NULL,NULL,'2026-02-12 19:32:24');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('61','6','trial_activity','{\"activity\":\"trial_started\",\"course_id\":6,\"description\":\"User enrolled in free trial\"}',NULL,NULL,'2026-02-12 19:32:24');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('62','6','free_enrollment','Free enrollment in course: Full-Stack JavaScript with MERN (ID: 6)',NULL,NULL,'2026-02-12 19:32:24');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('63','6','trial_activity','{\"activity\":\"reminders_scheduled\",\"course_id\":10,\"description\":\"Trial reminders scheduled\"}',NULL,NULL,'2026-02-12 19:34:41');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('64','6','trial_activity','{\"activity\":\"trial_started\",\"course_id\":10,\"description\":\"User enrolled in free trial\"}',NULL,NULL,'2026-02-12 19:34:41');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('65','6','free_enrollment','Free enrollment in course: Deep Learning with TensorFlow (ID: 10)',NULL,NULL,'2026-02-12 19:34:41');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('66','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 19:47:45');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('67','6','trial_activity','{\"activity\":\"reminders_scheduled\",\"course_id\":19,\"description\":\"Trial reminders scheduled\"}',NULL,NULL,'2026-02-12 19:50:29');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('68','6','trial_activity','{\"activity\":\"trial_started\",\"course_id\":19,\"description\":\"User enrolled in free trial\"}',NULL,NULL,'2026-02-12 19:50:29');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('69','6','free_enrollment','Free enrollment in course: Blockchain & Cryptocurrency Complete Course (ID: 19)',NULL,NULL,'2026-02-12 19:50:29');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('70','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 20:17:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('71','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 20:18:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('72','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 20:22:59');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('73','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-12 20:23:26');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('74','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 11:30:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('75','3','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 11:34:56');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('76','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 11:47:17');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('77','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 11:47:55');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('78','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 12:26:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('79','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 12:27:01');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('80','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 12:52:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('81','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 13:23:40');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('82','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 13:46:53');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('83','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 18:20:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('84','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 21:41:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('85','6','certificate_issued','Certificate issued for course: test for web',NULL,NULL,'2026-02-13 22:50:28');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('86','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 23:03:10');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('87','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 23:04:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('88','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 23:38:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('89','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-13 23:39:28');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('90','2','course_deleted','Deleted course ID: 8',NULL,NULL,'2026-02-13 23:40:04');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('91','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 08:43:23');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('92','6','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 08:44:04');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('93','2','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 08:45:22');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('94','7','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 08:59:23');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('95','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 09:00:46');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('96','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 09:04:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('97','1','login','User logged in from IP: ::1',NULL,NULL,'2026-02-14 09:05:09');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('98','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-19 23:04:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('99','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 16:13:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('100','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 16:14:09');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('101','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 16:26:03');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('102','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 16:28:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('103','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 16:36:33');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('104','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 17:30:10');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('105','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 17:34:36');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('106','5','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 17:36:18');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('107','5','profile_updated','Updated instructor profile',NULL,NULL,'2026-03-20 17:37:43');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('108','5','course_created','Created course: Example',NULL,NULL,'2026-03-20 17:41:25');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('109','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 17:45:48');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('110','5','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 17:59:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('111','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 18:04:09');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('112','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 18:05:27');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('113','2','course_updated','Updated course ID: 4',NULL,NULL,'2026-03-20 18:20:01');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('114','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:21:17');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('115','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:21:50');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('116','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:26:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('117','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:32:50');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('118','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:32:54');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('119','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:33:02');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('120','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:36:06');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('121','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:41:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('122','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:45:37');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('123','2','course_updated','Updated course ID: 1',NULL,NULL,'2026-03-20 18:46:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('124','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-20 19:25:38');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('125','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-22 19:18:41');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('126','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-22 19:19:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('127','2','course_deleted','Deleted course ID: 5',NULL,NULL,'2026-03-22 22:19:30');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('128','2','course_deleted','Deleted course ID: 3',NULL,NULL,'2026-03-22 22:19:58');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('129','2','course_deleted','Deleted course ID: 2',NULL,NULL,'2026-03-22 22:20:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('130','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 18:58:53');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('131','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 18:59:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('132','1','course_deleted','Deleted course ID: 14',NULL,NULL,'2026-03-23 18:59:38');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('133','1','course_deleted','Deleted course ID: 14',NULL,NULL,'2026-03-23 18:59:42');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('134','1','course_deleted','Deleted course ID: 14',NULL,NULL,'2026-03-23 18:59:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('135','1','course_deleted','Deleted course ID: 14',NULL,NULL,'2026-03-23 18:59:51');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('136','1','course_deleted','Deleted course ID: 14',NULL,NULL,'2026-03-23 18:59:56');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('137','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 19:03:49');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('138','2','course_created','Created course: Basic Website Development Tranning',NULL,NULL,'2026-03-23 19:07:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('139','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 19:11:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('140','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 19:12:59');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('141','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 20:36:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('142','2','course_deleted','Deleted course: Complete Web Development Bootcamp 2024',NULL,NULL,'2026-03-23 20:36:39');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('143','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 21:08:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('144','2','course_updated','Updated course ID: 7',NULL,NULL,'2026-03-23 21:40:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('145','2','course_updated','Updated course ID: 7',NULL,NULL,'2026-03-23 21:40:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('146','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 21:41:20');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('147','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 21:48:30');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('148','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 22:41:11');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('149','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 22:43:48');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('150','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 22:50:36');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('151','2','quiz_created','Created quiz: Hello Test',NULL,NULL,'2026-03-23 22:55:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('152','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:00:43');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('153','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:01:22');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('154','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:22:11');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('155','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:23:27');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('156','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:34:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('157','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:39:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('158','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-23 23:47:56');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('159','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-24 09:04:58');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('160','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-24 09:05:44');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('161','2','quiz_created','Created quiz: AAAAAAAAAAAAAAAAAAAXXXXXXXXXXXXXXXXXXXXAAAAAAAAAAAAAAAA (ID: 5)',NULL,NULL,'2026-03-24 09:34:29');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('162','2','quiz_question_created','Added question to quiz 8',NULL,NULL,'2026-03-24 09:48:50');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('163','2','quiz_deleted','Deleted quiz ID: 5',NULL,NULL,'2026-03-24 09:49:11');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('164','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-24 09:49:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('165','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-24 20:11:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('166','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-24 21:47:28');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('167','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 08:34:06');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('168','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 09:10:20');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('169','2','course_created','Created course: Data Analysis',NULL,NULL,'2026-03-25 09:12:22');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('170','2','course_updated','Updated course ID: 7',NULL,NULL,'2026-03-25 09:12:42');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('171','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 09:13:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('172','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 09:17:36');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('173','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 10:13:58');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('174','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 10:55:56');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('175','8','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 10:56:48');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('176','8','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 12:30:50');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('177','1','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 21:54:31');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('178','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 21:56:34');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('179','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 21:59:39');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('180','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-25 22:00:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('181','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 18:07:08');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('182','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 18:09:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('183','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 18:10:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('184','2','course_updated','Updated course ID: 8',NULL,NULL,'2026-03-28 18:28:12');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('185','2','course_updated','Updated course ID: 7',NULL,NULL,'2026-03-28 18:28:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('186','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 18:29:10');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('187','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 18:31:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('188','2','profile_updated','Updated instructor profile',NULL,NULL,'2026-03-28 18:31:31');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('189','2','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 19:35:34');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('190','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 19:48:04');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('191','9','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 20:41:26');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('192','9','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 22:00:58');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('193','9','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 22:01:19');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('194','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 22:05:04');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('195','7','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 22:41:07');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('196','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-28 22:41:30');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('197','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-29 07:37:20');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('198','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-29 13:03:05');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('199','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-29 13:27:21');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('200','6','login','User logged in from IP: ::1',NULL,NULL,'2026-03-29 13:35:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('201','9','login','User logged in from IP: ::1',NULL,NULL,'2026-03-29 14:03:43');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('202','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 20:47:51');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('203','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:24:48');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('204','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:30:57');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('205','1','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:32:20');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('206','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:33:23');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('207','13','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:34:27');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('208','2','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:46:00');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('209','1','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:51:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('210','2','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:57:06');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('211','1','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:57:51');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('212','2','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 21:59:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('213','14','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 22:07:16');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('214','1','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 22:12:53');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('215','13','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 22:31:24');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('216','2','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 22:32:07');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('217','14','login','User logged in from IP: ::1',NULL,NULL,'2026-04-01 22:47:32');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('218','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-06 12:07:38');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('219','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-06 12:07:46');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('220','9','login','User logged in from IP: ::1',NULL,NULL,'2026-04-06 12:41:14');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('221','1','login','User logged in from IP: ::1',NULL,NULL,'2026-04-06 12:47:45');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('222','1','category_updated','Updated category ID: 5',NULL,NULL,'2026-04-06 14:03:47');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('223','1','category_updated','Updated category ID: 5',NULL,NULL,'2026-04-06 14:03:52');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('224','1','category_deleted','Deleted category ID: 2',NULL,NULL,'2026-04-06 14:03:59');
INSERT INTO `admin_logs` (`id`,`user_id`,`action`,`details`,`ip_address`,`user_agent`,`created_at`) VALUES ('225','2','login','User logged in from IP: ::1',NULL,NULL,'2026-04-06 15:12:15');


-- Table structure for table `assignment_submissions`
DROP TABLE IF EXISTS `assignment_submissions`;
CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `status` enum('submitted','graded','returned','late') DEFAULT 'submitted',
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `graded_by` (`graded_by`),
  CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `lesson_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `categories_new`
DROP TABLE IF EXISTS `categories_new`;
CREATE TABLE `categories_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories_new`
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('1','Web Development','All about building websites and web apps.','2026-02-12 20:26:42','2026-02-12 20:26:42');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('3','Mobile App Dev','iOS and Android development.','2026-02-12 20:26:42','2026-02-12 20:26:42');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('4','Cyber Security','Network security, ethical hacking, and more.','2026-02-12 20:26:42','2026-02-12 20:26:42');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('5','Cloud Computing','AWS, Azure, and Google Cloud platform skills.','2026-02-12 20:26:42','2026-02-12 20:26:42');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('6','Mobile Development','iOS and Android app development','2026-01-31 18:12:08','2026-01-31 18:12:08');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('7','Cloud Computing','Cloud platforms and services','2026-01-31 18:12:08','2026-01-31 18:12:08');
INSERT INTO `categories_new` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES ('8','Data Science','Data analysis, machine learning, and AI','2026-01-31 18:12:08','2026-01-31 18:12:08');


-- Table structure for table `certificates`
DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(100) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `certificate_id` varchar(50) NOT NULL,
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('issued','revoked') DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_id` (`certificate_id`),
  UNIQUE KEY `certificate_code` (`certificate_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `certificates`
INSERT INTO `certificates` (`id`,`student_id`,`course_id`,`certificate_code`,`issued_at`,`certificate_id`,`issued_date`,`file_path`,`status`,`created_at`,`updated_at`) VALUES ('1','9','13','ITHUB-698F59D8320C0-2026','2026-02-13 22:50:28','','2026-02-13 22:50:28',NULL,'issued','2026-02-13 22:50:28','2026-03-29 13:14:36');
INSERT INTO `certificates` (`id`,`student_id`,`course_id`,`certificate_code`,`issued_at`,`certificate_id`,`issued_date`,`file_path`,`status`,`created_at`,`updated_at`) VALUES ('10','9','7',NULL,'2026-03-28 20:53:12','CERT_69C7EEDC8DC50_2026','2026-03-28 00:00:00','certificates/CERT_69C7EEDC8DC50_2026.html','issued','2026-03-28 20:53:12','2026-03-29 13:14:36');
INSERT INTO `certificates` (`id`,`student_id`,`course_id`,`certificate_code`,`issued_at`,`certificate_id`,`issued_date`,`file_path`,`status`,`created_at`,`updated_at`) VALUES ('11','6','7',NULL,'2026-03-29 13:36:44','CERT_69C8DA10C0B98_2026','2026-03-29 00:00:00','certificates/CERT_69C8DA10C0B98_2026.html','issued','2026-03-29 13:36:44','2026-03-29 13:36:44');


-- Table structure for table `completed_lessons`
DROP TABLE IF EXISTS `completed_lessons`;
CREATE TABLE `completed_lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_lesson` (`student_id`,`lesson_id`),
  KEY `lesson_id` (`lesson_id`),
  CONSTRAINT `completed_lessons_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `completed_lessons_ibfk_1_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `completed_lessons_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `completed_lessons`
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('13','6','26','2026-02-13 22:23:35');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('24','6','29','2026-03-23 23:38:36');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('25','6','30','2026-03-23 23:38:39');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('26','6','31','2026-03-23 23:52:38');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('28','6','33','2026-03-23 23:52:59');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('31','7','29','2026-03-25 22:01:19');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('32','7','30','2026-03-25 22:01:22');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('33','7','31','2026-03-25 22:01:24');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('35','7','33','2026-03-25 22:01:32');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('38','9','29','2026-03-28 20:44:25');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('39','9','30','2026-03-28 20:44:28');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('40','9','31','2026-03-28 20:44:31');
INSERT INTO `completed_lessons` (`id`,`student_id`,`lesson_id`,`completed_at`) VALUES ('41','9','33','2026-03-28 20:44:33');


-- Table structure for table `course_meta`
DROP TABLE IF EXISTS `course_meta`;
CREATE TABLE `course_meta` (
  `course_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`course_id`,`meta_key`),
  CONSTRAINT `fk_course_meta_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `course_meta`
INSERT INTO `course_meta` (`course_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('1','faqs','[]','2026-03-22 19:19:44');
INSERT INTO `course_meta` (`course_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('1','requirements','[]','2026-03-22 19:19:44');
INSERT INTO `course_meta` (`course_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('1','target_audience','[]','2026-03-22 19:19:44');
INSERT INTO `course_meta` (`course_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('1','what_you_learn','[\"hello\"]','2026-03-22 19:19:44');


-- Table structure for table `course_recommendations`
DROP TABLE IF EXISTS `course_recommendations`;
CREATE TABLE `course_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `recommendation_type` enum('knn','cold_start','popular','collaborative') NOT NULL DEFAULT 'knn',
  `recommendation_score` decimal(5,4) DEFAULT 0.0000,
  `recommendation_reason` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_course_type` (`user_id`,`course_id`,`recommendation_type`),
  KEY `idx_user_recommendations` (`user_id`,`recommendation_type`),
  KEY `idx_course_recommendations` (`course_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `course_reviews`
DROP TABLE IF EXISTS `course_reviews`;
CREATE TABLE `course_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`course_id`,`student_id`),
  KEY `fk_course_reviews_student` (`student_id`),
  CONSTRAINT `fk_course_reviews_student` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `course_reviews`
INSERT INTO `course_reviews` (`id`,`course_id`,`student_id`,`rating`,`review`,`is_public`,`created_at`,`updated_at`) VALUES ('1','1','3','5','Excellent course! Very well explained.','1','2026-01-31 18:18:25','2026-01-31 18:18:25');
INSERT INTO `course_reviews` (`id`,`course_id`,`student_id`,`rating`,`review`,`is_public`,`created_at`,`updated_at`) VALUES ('2','2','3','4','Good content but could use more examples.','1','2026-01-31 18:18:25','2026-01-31 18:18:25');


-- Table structure for table `courses_backup_20260329`
DROP TABLE IF EXISTS `courses_backup_20260329`;
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


-- Table structure for table `courses_new`
DROP TABLE IF EXISTS `courses_new`;
CREATE TABLE `courses_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `courses_new`
INSERT INTO `courses_new` (`id`,`title`,`description`,`category_id`,`instructor_id`,`thumbnail`,`price`,`duration_hours`,`difficulty_level`,`status`,`max_students`,`prerequisites`,`enrollment_count`,`created_at`,`updated_at`) VALUES ('7','Basic Website Development Tranning','This course is designed for beginners who want to start their journey in web development. \r\nYou will learn how to build modern, responsive, and user-friendly websites from scratch.\r\n\r\nThroughout this course, you will gain hands-on experience with:\r\n- HTML for structuring web pages\r\n- CSS for styling and responsive design\r\n- JavaScript for interactivity\r\n\r\nBy the end of this course, you will be able to create your own fully functional website and understand the core concepts of frontend development.\r\n\r\nPrerequisites:\r\n- Basic computer knowledge\r\n- No prior coding experience required','1','2','','2000.00','30','beginner','published','0',NULL,'0','2026-03-23 19:07:16','2026-04-01 21:46:27');
INSERT INTO `courses_new` (`id`,`title`,`description`,`category_id`,`instructor_id`,`thumbnail`,`price`,`duration_hours`,`difficulty_level`,`status`,`max_students`,`prerequisites`,`enrollment_count`,`created_at`,`updated_at`) VALUES ('8','Advanced Web Development','test a new aba the kajsjiajia  sjajsjakjsa  siajsisai wijsiajsiajsia s ais ai sai sia sia ssia si ais sai shai hsia hsia hsiahsi hai shaihsaisiasiasaisaisjaij s isaijja siajsia sjiajsia','1','2','','5000.00','30','beginner','published','0',NULL,'0','2026-03-25 09:12:22','2026-04-01 21:46:27');


-- Table structure for table `discussions`
DROP TABLE IF EXISTS `discussions`;
CREATE TABLE `discussions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_pinned` (`pinned`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `discussions_ibfk_1_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_discussion_parent` FOREIGN KEY (`parent_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `discussions`
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('1','7',NULL,NULL,'6','hii','hello','0','0','0','2','6','2026-04-01 22:13:55','2026-03-20 16:16:23','2026-04-01 22:13:55');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('2','7',NULL,NULL,'6','hii','hello','0','0','0','0','1','2026-04-01 22:17:40','2026-03-20 16:16:23','2026-04-01 22:17:40');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('3','7',NULL,'1','6','','hiie','0','1','0','0','0',NULL,'2026-03-20 16:26:20','2026-04-01 21:51:03');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('4','1',NULL,NULL,'1','Test Discussion','This is a test discussion created by the testing script.','0','0','0','1','1','2026-03-20 16:32:41','2026-03-20 16:32:41','2026-03-20 16:32:41');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('5','1',NULL,'4','1','','This is a test reply.','0','0','0','0','0',NULL,'2026-03-20 16:32:41','2026-03-20 16:32:41');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('8','7',NULL,'1','9','','hlo','0','0','0','0','0',NULL,'2026-04-01 22:08:41','2026-04-01 22:08:41');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('9','7',NULL,'1','9','','who are you','0','0','0','0','0',NULL,'2026-04-01 22:08:53','2026-04-01 22:08:53');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('10','7',NULL,'1','9','','who are you','0','0','0','0','0',NULL,'2026-04-01 22:11:41','2026-04-01 22:11:41');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('11','7',NULL,'1','9','','who are you','0','0','0','0','0',NULL,'2026-04-01 22:13:33','2026-04-01 22:13:33');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('12','7',NULL,'1','9','','hehe','0','0','0','0','0',NULL,'2026-04-01 22:13:55','2026-04-01 22:13:55');
INSERT INTO `discussions` (`id`,`course_id`,`lesson_id`,`parent_id`,`student_id`,`title`,`content`,`pinned`,`is_resolved`,`locked`,`views_count`,`replies_count`,`last_reply_at`,`created_at`,`updated_at`) VALUES ('13','7',NULL,'2','9','','haha','0','0','0','0','0',NULL,'2026-04-01 22:17:40','2026-04-01 22:17:40');


-- Table structure for table `email_verifications`
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`verification_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `enrollments_backup_20260329`
DROP TABLE IF EXISTS `enrollments_backup_20260329`;
CREATE TABLE `enrollments_backup_20260329` (
  `id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `enrollments_backup_20260329`
INSERT INTO `enrollments_backup_20260329` (`id`,`student_id`,`course_id`,`enrolled_at`,`completed_at`,`progress_percentage`,`status`) VALUES ('10','6','7','2026-03-23 19:11:33',NULL,'100.00','active');
INSERT INTO `enrollments_backup_20260329` (`id`,`student_id`,`course_id`,`enrolled_at`,`completed_at`,`progress_percentage`,`status`) VALUES ('11','6','8','2026-03-25 09:13:28',NULL,'0.00','active');


-- Table structure for table `enrollments_new`
DROP TABLE IF EXISTS `enrollments_new`;
CREATE TABLE `enrollments_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_enrolled_at` (`enrolled_at`),
  CONSTRAINT `enrollments_new_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_enrollments_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `enrollments_new`
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('15','6','10',NULL,'free_trial','active','0.00','2026-02-12 19:34:41',NULL,'2026-03-14 19:34:41','2026-02-12 19:34:41','2026-02-12 19:34:41');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('16','6','19',NULL,'free_trial','active','0.00','2026-02-12 19:50:29',NULL,'2026-03-14 19:50:29','2026-02-12 19:50:29','2026-02-12 19:50:29');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('17','8','7',NULL,'paid','active','0.00','2026-03-18 23:12:24',NULL,NULL,'2026-03-23 23:12:24','2026-03-28 20:00:00');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('18','9','7',NULL,'paid','active','100.00','2026-03-13 23:12:24',NULL,NULL,'2026-03-23 23:12:24','2026-04-01 21:14:48');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('19','10','7',NULL,'paid','active','90.00','2026-03-20 23:12:24',NULL,NULL,'2026-03-23 23:12:24','2026-03-23 23:12:24');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('20','11','7',NULL,'paid','active','30.00','2026-03-16 23:12:24',NULL,NULL,'2026-03-23 23:12:24','2026-03-23 23:12:24');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('21','12','7',NULL,'paid','active','60.00','2026-03-21 23:12:24',NULL,NULL,'2026-03-23 23:12:24','2026-03-23 23:12:24');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('22','7','7','34','paid','active','0.00','2026-03-25 10:20:44',NULL,NULL,'2026-03-25 10:20:44','2026-03-28 20:00:00');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('23','7','8','35','paid','active','0.00','2026-03-25 10:41:25',NULL,NULL,'2026-03-25 10:41:25','2026-03-25 10:41:25');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('24','6','7','37','paid','active','100.00','2026-03-28 19:48:46',NULL,NULL,'2026-03-28 19:48:46','2026-03-28 22:07:33');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('25','6','8','38','paid','active','0.00','2026-03-28 22:29:40',NULL,NULL,'2026-03-28 22:29:40','2026-03-28 22:29:40');
INSERT INTO `enrollments_new` (`id`,`user_id`,`course_id`,`payment_id`,`enrollment_type`,`status`,`progress_percentage`,`enrolled_at`,`completed_at`,`expires_at`,`created_at`,`updated_at`) VALUES ('27','9','8','39','paid','active','0.00','2026-04-01 21:07:48',NULL,NULL,'2026-04-01 21:07:48','2026-04-01 21:07:48');


-- Table structure for table `instructor_activity_log`
DROP TABLE IF EXISTS `instructor_activity_log`;
CREATE TABLE `instructor_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `instructor_activity_log_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `instructor_activity_log_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `instructor_activity_log`
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('1','2','course_created','Created course: test for web','13','2026-02-01 19:00:06');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('4','2','course_updated','Updated course ID: 4',NULL,'2026-03-20 18:20:01');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('5','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:21:17');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('6','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:21:50');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('7','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:26:12');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('8','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:32:50');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('9','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:32:54');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('10','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:33:02');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('11','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:36:06');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('12','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:41:32');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('13','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:45:37');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('14','2','course_updated','Updated course ID: 1',NULL,'2026-03-20 18:46:12');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('18','2','course_created','Created course: Test Course 1774198602',NULL,'2026-03-22 22:41:42');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('19','2','course_created','Created course: Basic Website Development Tranning','7','2026-03-23 19:07:16');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('21','2','course_updated','Updated course ID: 7','7','2026-03-23 21:40:21');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('22','2','course_updated','Updated course ID: 7','7','2026-03-23 21:40:32');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('23','2','course_created','Created course: Data Analysis','8','2026-03-25 09:12:22');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('24','2','course_updated','Updated course ID: 7','7','2026-03-25 09:12:42');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('25','2','course_updated','Updated course ID: 8','8','2026-03-28 18:28:12');
INSERT INTO `instructor_activity_log` (`id`,`instructor_id`,`action`,`details`,`course_id`,`created_at`) VALUES ('26','2','course_updated','Updated course ID: 7','7','2026-03-28 18:28:32');


-- Table structure for table `instructor_meta`
DROP TABLE IF EXISTS `instructor_meta`;
CREATE TABLE `instructor_meta` (
  `instructor_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`instructor_id`,`meta_key`),
  CONSTRAINT `fk_instructor_meta_user` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_instructor_meta_user_new` FOREIGN KEY (`instructor_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `instructor_meta`
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('2','qualifications','[\"[]\"]','2026-03-28 18:31:31');
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('2','social_links','{\"linkedin\":\"\",\"twitter\":\"\",\"github\":\"\",\"website\":\"\"}','2026-03-28 18:31:31');
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('2','specialties','[\"[]\"]','2026-03-28 18:31:31');
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('5','qualifications','[\"[]\"]','2026-03-20 17:37:43');
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('5','social_links','{\"linkedin\":\"\",\"twitter\":\"\",\"github\":\"\",\"website\":\"\"}','2026-03-20 17:37:43');
INSERT INTO `instructor_meta` (`instructor_id`,`meta_key`,`meta_value`,`updated_at`) VALUES ('5','specialties','[\"[]\"]','2026-03-20 17:37:43');


-- Table structure for table `learning_progress_dp`
DROP TABLE IF EXISTS `learning_progress_dp`;
CREATE TABLE `learning_progress_dp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `progress_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`progress_state`)),
  `optimal_path_score` decimal(5,2) DEFAULT 0.00,
  `completion_probability` decimal(5,4) DEFAULT 0.0000,
  `estimated_completion_time` int(11) DEFAULT 0,
  `last_calculated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  KEY `idx_user_progress` (`user_id`),
  KEY `idx_course_progress` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=773 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `learning_progress_dp`
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('1','3','4',NULL,'0','0.00','0.0000','-1','2026-02-01 21:33:37');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('3','3','13',NULL,'0','105.00','0.0000','-1','2026-02-01 21:33:37');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('5','3','5',NULL,'0','0.00','0.0000','-1','2026-02-01 21:33:00');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('133','1','1',NULL,'0','0.00','0.0000','-1','2026-01-31 21:53:23');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('587','3','15',NULL,'0','0.00','0.0000','-1','2026-02-01 21:33:37');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('593','6','18',NULL,'0','0.00','0.0000','-1','2026-02-12 19:57:35');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('595','6','16',NULL,'0','0.00','0.0000','-1','2026-02-12 19:57:35');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('597','6','15',NULL,'0','0.00','0.0000','-1','2026-02-01 21:52:47');
INSERT INTO `learning_progress_dp` (`id`,`user_id`,`course_id`,`lesson_id`,`progress_state`,`optimal_path_score`,`completion_probability`,`estimated_completion_time`,`last_calculated`) VALUES ('599','6','17',NULL,'0','0.00','0.0000','-1','2026-02-12 19:57:35');


-- Table structure for table `lesson_assignments`
DROP TABLE IF EXISTS `lesson_assignments`;
CREATE TABLE `lesson_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `lesson_assignments_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_assignments_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `lesson_assignments`
INSERT INTO `lesson_assignments` (`id`,`lesson_id`,`instructor_id`,`title`,`description`,`instructions`,`assignment_type`,`max_points`,`due_date`,`allow_late_submission`,`late_penalty_percent`,`max_attempts`,`time_limit_minutes`,`is_published`,`created_at`) VALUES ('4','31','1','Practice Exercise 1','Complete the first practice exercise','Follow the steps in the tutorial video','file_upload','100','2026-03-30 21:23:35','1','0','1',NULL,'1','2026-03-23 21:23:35');
INSERT INTO `lesson_assignments` (`id`,`lesson_id`,`instructor_id`,`title`,`description`,`instructions`,`assignment_type`,`max_points`,`due_date`,`allow_late_submission`,`late_penalty_percent`,`max_attempts`,`time_limit_minutes`,`is_published`,`created_at`) VALUES ('5','31','1','Practice Exercise 2','Complete the second practice exercise','Apply what you learned in the previous exercises','file_upload','100','2026-04-06 21:23:35','1','0','1',NULL,'1','2026-03-23 21:23:35');


-- Table structure for table `lesson_materials`
DROP TABLE IF EXISTS `lesson_materials`;
CREATE TABLE `lesson_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT 'file',
  `file_size` bigint(20) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  CONSTRAINT `lesson_materials_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `lesson_notes`
DROP TABLE IF EXISTS `lesson_notes`;
CREATE TABLE `lesson_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `note_type` enum('markdown','text','html') DEFAULT 'markdown',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `is_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `lesson_notes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_notes_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `lesson_progress`
DROP TABLE IF EXISTS `lesson_progress`;
CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lesson_progress` (`lesson_id`,`student_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_student_lesson_completion` (`student_id`,`completed`,`lesson_id`),
  CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_progress_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `lesson_progress`
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('5','29','6','1','0','0.00','0','0','0','0','0','0','2026-03-28 22:07:25',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('6','30','6','1','0','0.00','0','0','0','0','0','0','2026-03-28 22:07:27',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('7','31','6','1','0','0.00','0','0','0','0','0','0','2026-03-28 22:07:31',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('8','33','6','1','0','0.00','0','0','0','0','0','0','2026-03-28 22:07:33',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('9','29','7','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('10','30','7','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('11','31','7','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('12','33','7','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('13','29','8','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('14','30','8','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('15','31','8','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('16','33','8','0','0','0.00','0','0','0','0','0','0','2026-03-28 20:00:00',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('22','29','9','1','0','0.00','0','0','0','0','0','0','2026-03-28 21:02:24',NULL,'not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('23','30','9','1','0','0.00','0','0','0','0','0','0','2026-04-01 21:14:30','2026-04-01 21:14:30','not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('24','31','9','1','0','0.00','0','0','0','0','0','0','2026-04-01 21:14:45','2026-04-01 21:14:45','not_started');
INSERT INTO `lesson_progress` (`id`,`lesson_id`,`student_id`,`completed`,`video_watch_time_seconds`,`video_completion_percentage`,`notes_viewed`,`assignments_completed`,`assignments_total`,`resources_viewed`,`resources_total`,`time_spent_minutes`,`last_accessed_at`,`completed_at`,`status`) VALUES ('25','33','9','1','0','0.00','0','0','0','0','0','0','2026-04-01 21:14:48','2026-04-01 21:14:48','not_started');


-- Table structure for table `lesson_resources`
DROP TABLE IF EXISTS `lesson_resources`;
CREATE TABLE `lesson_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `lesson_resources_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_resources_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `lessons`
DROP TABLE IF EXISTS `lessons`;
CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `is_published` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lessons_video_source` (`video_source`),
  KEY `idx_lessons_video_processing` (`video_processing_status`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `lessons`
INSERT INTO `lessons` (`id`,`course_id`,`title`,`content`,`video_url`,`lesson_order`,`sort_order`,`lesson_type`,`duration_minutes`,`is_free`,`created_at`,`updated_at`,`video_file_path`,`google_drive_url`,`video_source`,`video_file_size`,`video_duration`,`video_thumbnail`,`video_processing_status`,`video_mime_type`,`video_quality`,`is_downloadable`,`auto_generate_thumbnail`,`content_type`,`video_path`,`description`,`duration`,`is_published`) VALUES ('26','13','Intro','inrodunctoiopn','','1','0','text','5','0','2026-02-13 12:16:22','2026-02-13 12:16:22',NULL,NULL,'none',NULL,NULL,NULL,'none',NULL,'720p','0','1','video',NULL,NULL,NULL,'0');
INSERT INTO `lessons` (`id`,`course_id`,`title`,`content`,`video_url`,`lesson_order`,`sort_order`,`lesson_type`,`duration_minutes`,`is_free`,`created_at`,`updated_at`,`video_file_path`,`google_drive_url`,`video_source`,`video_file_size`,`video_duration`,`video_thumbnail`,`video_processing_status`,`video_mime_type`,`video_quality`,`is_downloadable`,`auto_generate_thumbnail`,`content_type`,`video_path`,`description`,`duration`,`is_published`) VALUES ('29','7','Installing Code editor','Video lesson','uploads/videos/69c7c864e75d4.mp4','0','1','video','20','1','2026-03-23 21:23:35','2026-03-28 18:09:04',NULL,NULL,'none',NULL,NULL,NULL,'none',NULL,'720p','0','1','video',NULL,'Welcome to the course! In this lesson, we will cover the basics.','5:30','1');
INSERT INTO `lessons` (`id`,`course_id`,`title`,`content`,`video_url`,`lesson_order`,`sort_order`,`lesson_type`,`duration_minutes`,`is_free`,`created_at`,`updated_at`,`video_file_path`,`google_drive_url`,`video_source`,`video_file_size`,`video_duration`,`video_thumbnail`,`video_processing_status`,`video_mime_type`,`video_quality`,`is_downloadable`,`auto_generate_thumbnail`,`content_type`,`video_path`,`description`,`duration`,`is_published`) VALUES ('30','7','Basic Structure of an HTML Website _ Sigma Web Development Course','Video lesson. Thumbnail: uploads/thumbnails/thumbnails/69c7c8e515ce2.png','uploads/videos/69c7c8e51567c.mp4','0','2','video','12','1','2026-03-23 21:23:35','2026-03-28 18:11:13',NULL,NULL,'none',NULL,NULL,NULL,'none',NULL,'720p','0','1','video',NULL,'Learn how to set up your development environment.','15:00','1');
INSERT INTO `lessons` (`id`,`course_id`,`title`,`content`,`video_url`,`lesson_order`,`sort_order`,`lesson_type`,`duration_minutes`,`is_free`,`created_at`,`updated_at`,`video_file_path`,`google_drive_url`,`video_source`,`video_file_size`,`video_duration`,`video_thumbnail`,`video_processing_status`,`video_mime_type`,`video_quality`,`is_downloadable`,`auto_generate_thumbnail`,`content_type`,`video_path`,`description`,`duration`,`is_published`) VALUES ('31','7','Heading, Paragraphs and Links _ Sigma Web Development Course','Video lesson','uploads/videos/69c7c913f16a2.mp4','0','3','video','20','1','2026-03-23 21:23:35','2026-03-28 18:11:59',NULL,NULL,'none',NULL,NULL,NULL,'none',NULL,'720p','0','1','text',NULL,'Understanding the fundamental concepts.','10:00','1');
INSERT INTO `lessons` (`id`,`course_id`,`title`,`content`,`video_url`,`lesson_order`,`sort_order`,`lesson_type`,`duration_minutes`,`is_free`,`created_at`,`updated_at`,`video_file_path`,`google_drive_url`,`video_source`,`video_file_size`,`video_duration`,`video_thumbnail`,`video_processing_status`,`video_mime_type`,`video_quality`,`is_downloadable`,`auto_generate_thumbnail`,`content_type`,`video_path`,`description`,`duration`,`is_published`) VALUES ('33','7','Final Project',NULL,NULL,'0','5','text','0','1','2026-03-23 21:23:35','2026-03-23 21:23:35',NULL,NULL,'none',NULL,NULL,NULL,'none',NULL,'720p','0','1','project',NULL,'Complete your final project to earn the certificate.','60:00','1');


-- Table structure for table `login_attempts`
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `attempt_type` enum('login','register','reset_password') NOT NULL DEFAULT 'login',
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_email` (`email`),
  KEY `idx_attempt_type` (`attempt_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `login_attempts`
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('149','::1','govindarana@ithub.com','login','1','2026-04-01 21:30:57');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('150','::1','admin@ithub.com','login','0','2026-04-01 21:31:15');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('151','::1','admin@ithub.com','login','1','2026-04-01 21:32:20');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('152','::1','instructor@ithub.com','login','0','2026-04-01 21:32:54');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('153','::1','govindarana@ithub.com','login','1','2026-04-01 21:33:23');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('154','::1','instructor@ithub.com','login','1','2026-04-01 21:34:27');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('155','::1','instructor1@ithub.com','login','0','2026-04-01 21:45:53');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('156','::1','instructor1@ithub.com','login','1','2026-04-01 21:46:00');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('157','::1','admin@ithub.com','login','1','2026-04-01 21:51:47');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('158','::1','instructor1@ithub.com','login','1','2026-04-01 21:57:06');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('159','::1','admin@ithub.com','login','1','2026-04-01 21:57:51');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('160','::1','instructor1@ithub.com','login','1','2026-04-01 21:59:16');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('161','::1','sabina@ithub.com','login','1','2026-04-01 22:07:16');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('162','::1','admin@ithub.com','login','1','2026-04-01 22:12:53');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('163','::1','instructor@ithub.com','login','1','2026-04-01 22:31:24');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('164','::1','instructor1@ithub.com','login','0','2026-04-01 22:32:02');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('165','::1','instructor1@ithub.com','login','1','2026-04-01 22:32:07');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('166','::1','sabina@ithub.com','login','1','2026-04-01 22:47:32');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('167','::1','govindarana@ithub.com','login','1','2026-04-06 12:07:38');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('168','::1','govindarana@ithub.com','login','1','2026-04-06 12:07:46');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('169','::1','govindarana@ithub.com','login','1','2026-04-06 12:41:14');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('170','::1','admin@ithub.com','login','1','2026-04-06 12:47:45');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('171','::1','instructor1@ithub.com','login','0','2026-04-06 15:12:05');
INSERT INTO `login_attempts` (`id`,`ip_address`,`email`,`attempt_type`,`success`,`created_at`) VALUES ('172','::1','instructor1@ithub.com','login','1','2026-04-06 15:12:15');


-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `payment_analytics`
DROP TABLE IF EXISTS `payment_analytics`;
;

-- Dumping data for table `payment_analytics`
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-02-01','','pending','4','496.00','124.000000','0','0','0.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-02-01','esewa','pending','7','833.00','119.000000','0','0','0.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-03-25','esewa','pending','2','7000.00','3500.000000','0','0','0.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-03-25','esewa','completed','3','9000.00','3000.000000','3','0','100.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-03-25','khalti','pending','2','400000.00','200000.000000','0','0','0.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-03-28','esewa','completed','2','7000.00','3500.000000','2','0','100.00');
INSERT INTO `payment_analytics` (`payment_date`,`payment_method`,`status`,`transaction_count`,`total_amount`,`average_amount`,`successful_payments`,`failed_payments`,`success_rate`) VALUES ('2026-04-01','esewa','completed','1','5000.00','5000.000000','1','0','100.00');


-- Table structure for table `payment_settings`
DROP TABLE IF EXISTS `payment_settings`;
CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','boolean','integer','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payment_settings`
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('1','esewa_secret_key','8gBm/:&EnhH.1/q','string','eSewa secret key for HMAC signature','0','2026-02-01 22:17:31','2026-03-25 10:03:49');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('2','esewa_product_code','EPAYTEST','string','eSewa product code for testing','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('3','esewa_merchant_id','','string','eSewa merchant ID','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('4','esewa_test_mode','true','boolean','Enable eSewa test mode','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('5','esewa_success_url','payments/esewa_success.php','string','eSewa success callback URL','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('6','esewa_failure_url','payments/esewa_failure.php','string','eSewa failure callback URL','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('7','payment_timeout_minutes','30','integer','Payment session timeout in minutes','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('8','enable_payment_logging','true','boolean','Enable detailed payment logging','0','2026-02-01 22:17:31','2026-02-01 22:17:31');
INSERT INTO `payment_settings` (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_encrypted`,`created_at`,`updated_at`) VALUES ('9','max_payment_attempts','3','integer','Maximum payment attempts per transaction','0','2026-02-01 22:17:31','2026-02-01 22:17:31');


-- Table structure for table `payment_verification_logs`
DROP TABLE IF EXISTS `payment_verification_logs`;
CREATE TABLE `payment_verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `verification_type` enum('signature','status_check','amount_validation','product_code_validation') NOT NULL,
  `status` enum('success','failed','error') NOT NULL,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_verification_type` (`verification_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_payment_created` (`payment_id`,`created_at`),
  CONSTRAINT `payment_verification_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payment_verification_logs`
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('3','4','','success','{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}','{\"transaction_uuid\":\"697f84134fb57-1769964563-U3-C19\",\"amount\":\"169.00\",\"payment_method\":\"esewa\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 22:34:23');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('7','8','','success','{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}','{\"transaction_uuid\":\"697f85ccbc852-1769965004-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 22:41:44');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('11','11','','success','{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}','{\"transaction_uuid\":\"697f8b84ee5e9-1769966468-U3-C14\",\"amount\":\"89.00\",\"payment_method\":\"esewa\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 23:06:08');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('13','13','','success','{\"activity\":\"payment_created\",\"description\":\"Payment transaction created\"}','{\"transaction_uuid\":\"697f8d4ab4d2c-1769966922-U3-C16\",\"amount\":\"119.00\",\"payment_method\":\"esewa\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 23:13:42');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('15','15','','success','{\"activity\":\"free_enrollment_created\",\"description\":\"User enrolled for free\"}','{\"user_id\":6,\"course_id\":10,\"enrollment_type\":\"free_trial\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-12 19:34:41');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('17','33','','success','Status changed from pending to completed','Payment 33 status updated',NULL,NULL,NULL,'2026-03-25 10:13:27');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('18','34','','success','Status changed from pending to completed','Payment 34 status updated',NULL,NULL,NULL,'2026-03-25 10:14:57');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('20','35','','success','Status changed from pending to completed','Payment 35 status updated',NULL,NULL,NULL,'2026-03-25 10:41:24');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('21','23','','success','{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}','{\"user_id\":7,\"course_id\":8,\"payment_id\":35,\"enrollment_type\":\"paid\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 10:41:25');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('22','37','','success','Status changed from pending to completed','Payment 37 status updated',NULL,NULL,NULL,'2026-03-28 19:48:46');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('23','24','','success','{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}','{\"user_id\":6,\"course_id\":7,\"payment_id\":37,\"enrollment_type\":\"paid\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 19:48:46');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('24','38','','success','Status changed from pending to completed','Payment 38 status updated',NULL,NULL,NULL,'2026-03-28 22:29:40');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('25','25','','success','{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}','{\"user_id\":6,\"course_id\":8,\"payment_id\":38,\"enrollment_type\":\"paid\"}',NULL,'::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36','2026-03-28 22:29:40');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('26','39','','success','Status changed from pending to completed','Payment 39 status updated',NULL,NULL,NULL,'2026-04-01 21:06:43');
INSERT INTO `payment_verification_logs` (`id`,`payment_id`,`verification_type`,`status`,`request_data`,`response_data`,`error_message`,`ip_address`,`user_agent`,`created_at`) VALUES ('27','27','','success','{\"activity\":\"enrollment_created\",\"description\":\"User enrolled after payment verification\"}','{\"user_id\":9,\"course_id\":8,\"payment_id\":39,\"enrollment_type\":\"paid\"}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-01 21:07:48');


-- Table structure for table `payments`
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_uuid` (`transaction_uuid`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_transaction_uuid` (`transaction_uuid`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  KEY `idx_user_course_status` (`user_id`,`course_id`,`status`),
  KEY `idx_payment_method_status` (`payment_method`,`status`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('4','697f84134fb57-1769964563-U3-C19','3','19','','169.00','NPR','pending',NULL,NULL,NULL,'5Lu4LdQYUJUkOBfnscpECKYOYqnvqm1JxO7sIhg6c74=','total_amount,transaction_uuid,product_code','EPAYTEST',NULL,'2026-02-01 22:34:23','2026-02-01 22:34:23');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('8','697f85ccbc852-1769965004-U3-C16','3','16','','119.00','NPR','pending',NULL,NULL,NULL,'UKyVYEPr7u0tPJWxwNhuDP9ZTRL97xwq+KhqjKUR50E=','total_amount,transaction_uuid,product_code','EPAYTEST',NULL,'2026-02-01 22:41:44','2026-02-01 22:41:44');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('11','697f8b84ee5e9-1769966468-U3-C14','3','14','','89.00','NPR','pending',NULL,NULL,NULL,'rlboBS10CcG/wU2/JlKI9pExSIA2l1JAIiYhBAyZ4Lk=','total_amount,transaction_uuid,product_code','EPAYTEST',NULL,'2026-02-01 23:06:08','2026-02-01 23:06:08');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('13','697f8d4ab4d2c-1769966922-U3-C16','3','16','','119.00','NPR','pending',NULL,NULL,NULL,'JIUfLFY6TeIPL4r7bIdPvx/IzY+94QDZWiTHJ5xCex8=','total_amount,transaction_uuid,product_code','EPAYTEST',NULL,'2026-02-01 23:13:42','2026-02-01 23:13:42');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('15','TXN-697f9025c2214','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"83HRf6oL5YS5hzGXk7oFV+UYahq1nY\\/TR1oXRbFa5Hg=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:25:53','2026-02-01 23:25:53');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('20','TXN-697f910b2bc00','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"8c7KpjrhcdH8M9kYx\\/W7mkeX4F9KVD06oMzT99TQhp8=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:29:43','2026-02-01 23:29:43');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('21','TXN-697f91140debe','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"nrVaNNY1WS9etTfJnosuLuC3pUpQSXFKc7+A9ZqMxoU=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:29:52','2026-02-01 23:29:52');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('23','TXN-697f9385d14ea','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"l7+KJw72FGztGsfayKO3UjtZAmPJqpBkcrSCc5tgg8k=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:40:17','2026-02-01 23:40:17');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('24','TXN-697f942a6f184','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"XpbsQ7l+R5yDLvmKIXks6aSgINKxDnueIJgJCMq8MqA=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:43:02','2026-02-01 23:43:02');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('25','TXN-697f94b0b2116','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"9q\\/lXklWoqULDnK2pZ8eFNHqdq2Uex\\/Gyq+KOq8KVcA=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:45:16','2026-02-01 23:45:16');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('27','TXN-697f9618c1ec8','3','16','esewa','119.00','NPR','pending',NULL,NULL,'{\"signature\":\"UDIoekCTvBndHGnVV26DEvEwqKg51rxZGbR2Wgdofg0=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-02-01 23:51:16','2026-02-01 23:51:16');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('30','KHLTI-69c35e7a07c60-1774411386','7','7','khalti','200000.00','NPR','pending',NULL,NULL,'{\"integration\":\"khalti\",\"amount_paisa\":200000,\"product_identity\":\"course_7_1774411386\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 09:48:06','2026-03-25 09:48:06');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('31','KHLTI-69c35e883a904-1774411400','7','7','khalti','200000.00','NPR','pending',NULL,NULL,'{\"integration\":\"khalti\",\"amount_paisa\":200000,\"product_identity\":\"course_7_1774411400\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 09:48:20','2026-03-25 09:48:20');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('32','TXN-69c36158894ca','7','7','esewa','2000.00','NPR','pending',NULL,NULL,'{\"signature\":\"XIvWqw6BNqEvSi4DPjlGgXU0y\\/MyXXkgBHKmWgh4lyA=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 10:00:20','2026-03-25 10:00:20');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('33','TXN-69c362b248150','7','7','esewa','2000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69c362b248150\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"PdWga\\/QLM9UbgiJeZkRQynN8sYIwit3vh2UrGsZntO0=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKM3\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 10:06:06','2026-03-25 10:13:27');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('34','TXN-69c364a56e789','7','7','esewa','2000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69c364a56e789\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"fGGe1VR05qJB5k82c1Bjfnt1w6jhVYwcrUFXqTpDd+c=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKME\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 10:14:25','2026-03-25 10:14:57');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('35','TXN-69c36add970ee','7','8','esewa','5000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69c36add970ee\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"8DbX7VtzQZKZWkOgpg\\/rWUsII5D+oJWfzaOPf6fZF0k=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EKMV\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 10:40:57','2026-03-25 10:41:24');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('36','TXN-69c36ea4a474f','8','8','esewa','5000.00','NPR','pending',NULL,NULL,'{\"signature\":\"WD6eDM\\/gAz13m7GGkyk4v0LAKxLJgHIFGdYadO06C2c=\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-25 10:57:04','2026-03-25 10:57:04');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('37','TXN-69c7dfa6c6c2a','6','7','esewa','2000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69c7dfa6c6c2a\",\"total_amount\":\"2000.0\",\"status\":\"COMPLETE\",\"signature\":\"0YsPamRrD9lazRHPuGfz+9Lv0Ekge41ZfVC8zP6vFbI=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EM2A\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-28 19:48:18','2026-03-28 19:48:46');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('38','TXN-69c8055c66e16','6','8','esewa','5000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69c8055c66e16\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"pnw3\\/DlT4QJqMANHLV9wNLmDaafL7d2XshQDN2qRBdk=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EM5M\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-03-28 22:29:12','2026-03-28 22:29:40');
INSERT INTO `payments` (`id`,`transaction_uuid`,`user_id`,`course_id`,`payment_method`,`amount`,`currency`,`status`,`gateway_status`,`gateway_transaction_id`,`gateway_response`,`signature`,`signed_field_names`,`product_code`,`failure_reason`,`created_at`,`updated_at`) VALUES ('39','TXN-69cd37e3a68ea','9','8','esewa','5000.00','NPR','completed',NULL,NULL,'{\"transaction_uuid\":\"TXN-69cd37e3a68ea\",\"total_amount\":\"5000.0\",\"status\":\"COMPLETE\",\"signature\":\"6N0BxwI0eteFSUyL1RX3yFuDy8SYxJ2HLD1oB+Ijdzs=\",\"product_code\":\"EPAYTEST\",\"transaction_code\":\"000EPTO\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\"}',NULL,NULL,'EPAYTEST',NULL,'2026-04-01 21:06:07','2026-04-01 21:06:43');


-- Table structure for table `quiz_answers`
DROP TABLE IF EXISTS `quiz_answers`;
CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `fk_quiz_answers_option` (`selected_option_id`),
  KEY `idx_quiz_answers_attempt` (`attempt_id`),
  CONSTRAINT `fk_quiz_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `quiz_options` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_quiz_answers_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `quiz_answers`
INSERT INTO `quiz_answers` (`id`,`attempt_id`,`question_id`,`selected_option_id`,`answer_text`,`is_correct`,`points_earned`,`created_at`) VALUES ('6','6','17',NULL,'0','0','0.00','2026-03-24 09:50:16');
INSERT INTO `quiz_answers` (`id`,`attempt_id`,`question_id`,`selected_option_id`,`answer_text`,`is_correct`,`points_earned`,`created_at`) VALUES ('7','7','17',NULL,'0','0','0.00','2026-03-24 10:03:14');
INSERT INTO `quiz_answers` (`id`,`attempt_id`,`question_id`,`selected_option_id`,`answer_text`,`is_correct`,`points_earned`,`created_at`) VALUES ('8','8','17',NULL,'0','1','1.00','2026-03-24 10:12:00');
INSERT INTO `quiz_answers` (`id`,`attempt_id`,`question_id`,`selected_option_id`,`answer_text`,`is_correct`,`points_earned`,`created_at`) VALUES ('9','10','22',NULL,'0','1','1.00','2026-03-24 20:11:41');
INSERT INTO `quiz_answers` (`id`,`attempt_id`,`question_id`,`selected_option_id`,`answer_text`,`is_correct`,`points_earned`,`created_at`) VALUES ('10','12','17',NULL,'0','0','0.00','2026-04-01 21:52:52');


-- Table structure for table `quiz_attempts`
DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_points` decimal(5,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `passed` tinyint(1) DEFAULT 0,
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_quiz_attempts_quiz` (`quiz_id`),
  KEY `idx_quiz_attempts_user` (`student_id`),
  KEY `idx_student_quiz_status` (`student_id`,`quiz_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `quiz_attempts`
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('4','3','6','1','2026-03-24 09:40:50','2026-03-24 09:40:50','10.00','10.00','100.00','1','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('5','3','10','1','2026-03-24 09:48:01','2026-03-24 09:48:01','10.00','10.00','100.00','1','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('6','6','8','1','2026-03-24 09:49:39','2026-03-24 09:50:16','0.00','1.00','0.00','0','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('7','6','8','2','2026-03-24 10:02:52','2026-03-24 10:03:14','0.00','1.00','0.00','0','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('8','6','8','3','2026-03-24 10:12:00','2026-03-24 10:12:00','1.00','1.00','100.00','1','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('9','6','7','1','2026-03-24 10:13:08','2026-03-24 10:13:08','0.00','0.00','0.00','0','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('10','6','9','1','2026-03-24 20:11:28','2026-03-24 20:11:41','1.00','1.00','100.00','1','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('11','6','7','2','2026-03-25 08:34:48',NULL,'0.00','0.00','0.00','0','in_progress');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('12','9','8','1','2026-04-01 21:44:57','2026-04-01 21:52:52','0.00','1.00','0.00','0','completed');
INSERT INTO `quiz_attempts` (`id`,`student_id`,`quiz_id`,`attempt_number`,`started_at`,`completed_at`,`score`,`total_points`,`percentage`,`passed`,`status`) VALUES ('13','9','8','2','2026-04-01 22:04:21','2026-04-01 22:04:26','0.00','0.00','0.00','0','completed');


-- Table structure for table `quiz_options`
DROP TABLE IF EXISTS `quiz_options`;
CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_order` (`question_id`,`option_order`),
  KEY `idx_quiz_options_question` (`question_id`),
  CONSTRAINT `quiz_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `quiz_options`
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('99','10','Paris','1','0','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('100','10','London','0','1','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('101','10','Berlin','0','2','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('102','10','Madrid','0','3','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('103','11','True','0','1','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('104','11','False','1','2','2026-03-24 09:40:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('105','15','Hyper Text Markup Language','1','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('106','15','High Tech Modern Language','0','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('107','15','Home Tool Markup Language','0','3','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('108','15','Hyperlinks and Text Markup Language','0','4','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('109','16','<h1>','1','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('110','16','<h6>','0','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('111','16','<heading>','0','3','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('112','16','<head>','0','4','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('113','17','True','0','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('114','17','False','1','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('115','18','<br>','1','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('116','18','<break>','0','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('117','18','<lb>','0','3','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('118','18','<newline>','0','4','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('119','19','alt','1','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('120','19','src','0','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('121','19','title','0','3','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('122','19','longdesc','0','4','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('123','20','Cascading Style Sheets','1','1','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('124','20','Computer Style Sheets','0','2','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('125','20','Creative Style Sheets','0','3','2026-03-24 09:44:08');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('126','20','Colorful Style Sheets','0','4','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('127','21','background-color','1','1','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('128','21','color','0','2','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('129','21','bgcolor','0','3','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('130','21','background','0','4','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('131','22','True','1','1','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('132','22','False','0','2','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('133','23','body {color: black;}','1','1','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('134','23','body:color=black;','0','2','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('135','23','{body;color:black;}','0','3','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('136','23','{body:color=black}','0','4','2026-03-24 09:44:09');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('137','25','Paris','1','0','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('138','25','London','0','1','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('139','25','Berlin','0','2','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('140','25','Madrid','0','3','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('141','26','True','0','1','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('142','26','False','1','2','2026-03-24 09:48:01');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('143','28','me','1','0','2026-03-24 09:48:50');
INSERT INTO `quiz_options` (`id`,`question_id`,`option_text`,`is_correct`,`option_order`,`created_at`) VALUES ('144','28','you','0','1','2026-03-24 09:48:50');


-- Table structure for table `quiz_questions`
DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  `points` decimal(5,2) DEFAULT 1.00,
  `question_order` int(11) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_question_order` (`question_order`),
  KEY `idx_quiz_questions_quiz` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `quiz_questions`
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('10','6','What is the capital of France? (Updated)','multiple_choice','1.00','1',NULL,'0','2026-03-24 09:40:50');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('11','6','The Earth is flat.','true_false','1.00','2',NULL,'0','2026-03-24 09:40:50');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('15','8','What does HTML stand for?','multiple_choice','1.00','1',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('16','8','Which tag is used for the largest heading?','multiple_choice','1.00','2',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('17','8','HTML elements are case-sensitive.','true_false','1.00','3',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('18','8','What is the correct HTML element for inserting a line break?','multiple_choice','1.00','4',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('19','8','Which attribute specifies an alternate text for an image?','multiple_choice','2.00','5',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('20','9','What does CSS stand for?','multiple_choice','1.00','1',NULL,'0','2026-03-24 09:44:08');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('21','9','Which property is used to change the background color?','multiple_choice','1.00','2',NULL,'0','2026-03-24 09:44:09');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('22','9','The z-index property works on positioned elements.','true_false','1.00','3',NULL,'0','2026-03-24 09:44:09');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('23','9','Which is the correct CSS syntax?','multiple_choice','2.00','4',NULL,'0','2026-03-24 09:44:09');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('25','10','What is the capital of France? (Updated)','multiple_choice','1.00','1',NULL,'0','2026-03-24 09:48:01');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('26','10','The Earth is flat.','true_false','1.00','2',NULL,'0','2026-03-24 09:48:01');
INSERT INTO `quiz_questions` (`id`,`quiz_id`,`question_text`,`question_type`,`points`,`question_order`,`explanation`,`sort_order`,`created_at`) VALUES ('28','8','Who are you ??','multiple_choice','1.00','6',NULL,'0','2026-03-24 09:48:50');


-- Table structure for table `quizzes`
DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `time_limit_minutes` int(11) DEFAULT 60,
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_status` (`status`),
  KEY `idx_quizzes_course` (`course_id`),
  KEY `idx_quizzes_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `quizzes`
INSERT INTO `quizzes` (`id`,`course_id`,`lesson_id`,`title`,`description`,`time_limit`,`attempts_allowed`,`passing_score`,`randomize_questions`,`show_correct_answers`,`status`,`created_at`,`updated_at`,`max_attempts`,`time_limit_minutes`) VALUES ('7','7',NULL,'HTML Basics Quiz','Test your knowledge of HTML fundamentals',NULL,'3','70.00','0','1','published','2026-03-24 09:42:41','2026-03-24 09:42:41','3','15');
INSERT INTO `quizzes` (`id`,`course_id`,`lesson_id`,`title`,`description`,`time_limit`,`attempts_allowed`,`passing_score`,`randomize_questions`,`show_correct_answers`,`status`,`created_at`,`updated_at`,`max_attempts`,`time_limit_minutes`) VALUES ('8','7',NULL,'HTML Basics Quiz','Test your knowledge of HTML fundamentals',NULL,'3','70.00','0','1','published','2026-03-24 09:44:08','2026-03-24 09:44:08','3','15');
INSERT INTO `quizzes` (`id`,`course_id`,`lesson_id`,`title`,`description`,`time_limit`,`attempts_allowed`,`passing_score`,`randomize_questions`,`show_correct_answers`,`status`,`created_at`,`updated_at`,`max_attempts`,`time_limit_minutes`) VALUES ('9','7',NULL,'CSS Fundamentals Quiz','Test your CSS styling knowledge',NULL,'3','75.00','0','1','published','2026-03-24 09:44:08','2026-03-24 09:44:08','3','20');


-- Table structure for table `student_notes`
DROP TABLE IF EXISTS `student_notes`;
CREATE TABLE `student_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_note` (`lesson_id`,`student_id`),
  KEY `student_notes_ibfk_2_new` (`student_id`),
  CONSTRAINT `student_notes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notes_ibfk_1_new` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_notes_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `user_interactions`
DROP TABLE IF EXISTS `user_interactions`;
CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `interaction_type` enum('view','enroll','lesson_complete','quiz_attempt','discussion_post') NOT NULL,
  `interaction_value` decimal(5,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_course` (`user_id`,`course_id`),
  KEY `idx_interaction_type` (`interaction_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `users_new`
DROP TABLE IF EXISTS `users_new`;
CREATE TABLE `users_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users_new`
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('1','admin','admin@ithub.com','$2y$10$jViOhSTdsPM/cbpXk/w7V.WUKeAy3DJu8b5ywqrcWMt6FTAgiW/me','Admin User','admin',NULL,NULL,NULL,'active','2026-01-31 21:16:50','2026-04-01 21:31:59');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('2','instructor1','instructor1@ithub.com','$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK','John Instructor','instructor',NULL,NULL,NULL,'active','2026-01-31 21:16:50','2026-01-31 21:16:50');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('3','student1','student1@ithub.com','$2y$10$v8eVCgNotAIatcFSLA/0rObsKvb6WiPneKNDOvK8ykgfqtoxbWZtK','Test Student','student',NULL,NULL,NULL,'active','2026-01-31 21:16:50','2026-01-31 21:16:50');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('4','demo_student_1','demo1@example.com','$2y$10$XADdhdwb2e7kNYpTecuEte9aOS07V95wR2G/P4NUgiToSeuoHs1ri','Alice Johnson','student',NULL,NULL,NULL,'active','2026-03-23 23:09:22','2026-03-23 23:09:22');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('5','demo_student_2','demo2@example.com','$2y$10$btlUNqX/vbXIer3didANaO6xPMl0RqMqj2.JR6OaVa5r7yTA6PHqK','Bob Smith','student',NULL,NULL,NULL,'active','2026-03-23 23:09:22','2026-03-23 23:09:22');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('6','demo_student_3','demo3@example.com','$2y$10$a9EXW0lHmX39BEv7km5J2OBr3ROCJlRgYs0FFsSVyw/AvrjRUWaEe','Charlie Brown','student',NULL,NULL,NULL,'active','2026-03-23 23:09:22','2026-03-23 23:09:22');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('7','demo_student_4','demo4@example.com','$2y$10$LuLwNMmv7/hAgh0l6je4pu8N/0GSsbSuqwtixKKHxQdloNDsOck0.','Diana Prince','student',NULL,NULL,NULL,'active','2026-03-23 23:09:22','2026-03-23 23:09:22');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('8','demo_student_5','demo5@example.com','$2y$10$mAzrapP23JBpVUDQX1QTtOA8Yj3sA6k6XWVpjH.Hq3EAhJ6mPZxCy','Edward Norton','student',NULL,NULL,NULL,'active','2026-03-23 23:09:22','2026-03-23 23:09:22');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('9','govindarana','govindarana@ithub.com','$2y$10$RK4veyJAtRPmrDpCLiP/eepe6p5NHKDczo5vKS4tQGJhH7ARRsuri','Govinda Rana','student',NULL,NULL,NULL,'active','2026-03-28 20:40:19','2026-03-28 20:40:19');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('10','student_charlie','charlie@demo.com','$2y$10$RdNxHJRT1z.1QKlxOCRil.imr0ZNLcJIJA4qawrqr3IL5vZ47jo3m','Charlie Brown','student',NULL,NULL,NULL,'active','2026-03-23 23:11:35','2026-03-23 23:11:35');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('11','student_diana','diana@demo.com','$2y$10$vj26/vmXMafAoB6e2f6rKeeI88RVBXm7Xe.SCgrQwDJ8fzGvluHDu','Diana Prince','student',NULL,NULL,NULL,'active','2026-03-23 23:11:35','2026-03-23 23:11:35');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('12','student_edward','edward@demo.com','$2y$10$AHUJSpiszWWVjz.X9.WTvuKeo.eLjfT6c38u4u2OniSB7ka6Nn9G6','Edward Norton','student',NULL,NULL,NULL,'active','2026-03-23 23:11:35','2026-03-23 23:11:35');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('13','instructor','instructor@ithub.com','$2y$10$RNM4p6DOyy/gneVbLrMR.O3TtJtvJunxW.lo6RFeDdPL3jlzjrppe','Instructor User','instructor',NULL,NULL,'1234567890','active','2026-04-01 21:33:51','2026-04-01 21:33:51');
INSERT INTO `users_new` (`id`,`username`,`email`,`password`,`full_name`,`role`,`profile_image`,`bio`,`phone`,`status`,`created_at`,`updated_at`) VALUES ('14','sabina','sabina@ithub.com','$2y$10$al7JgkirUo6m2i1kc3D0D.fEKk4Pll6eZ9qpf9kV5eHh5YTVOULpO','Sabina Poudel','student',NULL,NULL,'0000000000','active','2026-04-01 22:07:02','2026-04-01 22:07:02');


-- Table structure for table `video_analytics`
DROP TABLE IF EXISTS `video_analytics`;
CREATE TABLE `video_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lesson_student` (`lesson_id`,`student_id`),
  KEY `idx_video_analytics_lesson` (`lesson_id`),
  KEY `idx_video_analytics_student` (`student_id`),
  KEY `idx_student_completion` (`student_id`,`completed_watching`,`lesson_id`),
  CONSTRAINT `video_analytics_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_analytics_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_analytics_ibfk_2_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `video_processing_queue`
DROP TABLE IF EXISTS `video_processing_queue`;
CREATE TABLE `video_processing_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `video_file_path` varchar(500) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `thumbnail_generated` tinyint(1) DEFAULT 0,
  `duration_extracted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `idx_video_queue_status` (`status`),
  CONSTRAINT `video_processing_queue_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `wishlists`
DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`student_id`,`course_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `fk_wishlist_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_course_new` FOREIGN KEY (`course_id`) REFERENCES `courses_new` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_student_new` FOREIGN KEY (`student_id`) REFERENCES `users_new` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

