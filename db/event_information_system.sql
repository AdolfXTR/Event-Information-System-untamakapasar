-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 10:34 AM
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
-- Database: `event_information_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `activity_description` text NOT NULL,
  `activity_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `activity_type`, `activity_description`, `activity_date`) VALUES
(8, 1, 'User Logout', 'User logged out', '2025-10-13 03:39:42'),
(9, 1, 'User Login', 'User logged in successfully', '2025-10-13 03:39:54'),
(10, 1, 'User Logout', 'User logged out', '2025-10-13 03:40:15'),
(15, 1, 'User Login', 'User logged in successfully', '2025-10-13 03:40:31'),
(16, 1, 'User Logout', 'User logged out', '2025-10-13 03:40:36'),
(17, 2, 'User Login', 'User logged in successfully', '2025-10-13 03:40:40'),
(18, 2, 'User Logout', 'User logged out', '2025-10-13 03:40:41'),
(19, 1, 'User Login', 'User logged in successfully', '2025-10-13 03:40:47'),
(20, 1, 'System Maintenance', 'Cleared 0 old activity logs', '2025-10-13 04:13:07'),
(21, 2, 'User Login', 'User logged in successfully', '2025-10-14 01:02:11'),
(22, 2, 'User Logout', 'User logged out', '2025-10-14 01:02:15'),
(23, 2, 'User Login', 'User logged in successfully', '2025-10-14 01:03:15'),
(24, 2, 'User Logout', 'User logged out', '2025-10-14 01:03:19'),
(25, 1, 'User Login', 'User logged in successfully', '2025-10-14 01:03:24'),
(26, 1, 'User Logout', 'User logged out', '2025-10-14 01:11:11'),
(27, 6, 'User Registration', 'New student account created', '2025-10-14 01:11:37'),
(28, 6, 'User Login', 'User logged in successfully', '2025-10-14 01:11:43'),
(29, 6, 'Event Registration', 'Registered for event ID: 1', '2025-10-14 01:11:47'),
(30, 6, 'Event Registration', 'Registered for event ID: 2', '2025-10-14 01:11:56'),
(31, 6, 'Profile Picture Updated', 'User updated profile picture', '2025-10-14 01:12:11'),
(32, 6, 'User Logout', 'User logged out', '2025-10-14 01:12:30'),
(33, 2, 'User Login', 'User logged in successfully', '2025-10-14 01:12:34'),
(34, 2, 'User Login', 'User logged in successfully', '2025-10-19 14:59:10'),
(35, 2, 'User Logout', 'User logged out', '2025-10-19 14:59:17'),
(36, 1, 'User Login', 'User logged in successfully', '2025-10-19 14:59:26'),
(37, 1, 'User Logout', 'User logged out', '2025-10-19 15:09:30'),
(38, 2, 'User Login', 'User logged in successfully', '2025-10-19 15:11:12'),
(39, 2, 'Attendance Updated', 'Updated attendance for registration ID: 10 to absent', '2025-10-19 15:20:09'),
(40, 2, 'Attendance Updated', 'Updated attendance for registration ID: 10 to registered', '2025-10-19 15:20:12'),
(41, 2, 'Attendance Updated', 'Updated attendance for registration ID: 10 to registered', '2025-10-19 15:20:14'),
(42, 2, 'Attendance Updated', 'Updated attendance for registration ID: 10 to attended', '2025-10-19 15:20:17'),
(43, 2, 'Attendance Updated', 'Updated attendance for registration ID: 9 to attended', '2025-10-19 15:20:28'),
(44, 2, 'User Logout', 'User logged out', '2025-10-19 15:25:48'),
(45, 6, 'User Login', 'User logged in successfully', '2025-10-19 15:25:54'),
(46, 6, 'Password Changed', 'User changed password', '2025-10-19 15:38:08'),
(47, 6, 'User Logout', 'User logged out', '2025-10-19 15:38:12'),
(48, 1, 'User Login', 'User logged in successfully', '2025-10-19 15:38:17'),
(49, 1, 'User Logout', 'User logged out', '2025-10-19 15:39:32'),
(50, 2, 'User Login', 'User logged in successfully', '2025-10-19 15:39:41'),
(51, 6, 'User Login', 'User logged in successfully', '2025-10-20 01:24:26'),
(52, 6, 'User Logout', 'User logged out', '2025-10-20 01:37:20'),
(53, 2, 'User Login', 'User logged in successfully', '2025-10-20 01:37:24'),
(54, 2, 'Announcement Created', 'Created announcement: “𝙉𝙖𝙜𝙞𝙣𝙜 𝙢𝙖𝙜𝙠𝙖-𝙞𝙗𝙞𝙜𝙖𝙣, 𝙝𝙖𝙣𝙜𝙜𝙖𝙣𝙜 𝙢𝙖𝙜 𝙠𝙖𝙞𝙗𝙞𝙜𝙖𝙣&quot;', '2025-10-20 01:38:52'),
(55, 2, 'User Logout', 'User logged out', '2025-10-20 01:39:02'),
(56, 6, 'User Login', 'User logged in successfully', '2025-10-20 01:39:08'),
(57, 6, 'User Logout', 'User logged out', '2025-10-20 01:42:16'),
(58, 1, 'User Login', 'User logged in successfully', '2025-10-20 01:42:21'),
(59, 1, 'User Logout', 'User logged out', '2025-10-20 01:45:20'),
(60, 6, 'User Login', 'User logged in successfully', '2025-10-20 01:46:04'),
(61, 6, 'User Logout', 'User logged out', '2025-10-20 01:50:10'),
(62, 2, 'User Login', 'User logged in successfully', '2025-10-20 01:50:15'),
(63, 2, 'Announcement Updated', 'Updated announcement: “𝙉𝙖𝙜𝙞𝙣𝙜 𝙢𝙖𝙜𝙠𝙖-𝙞𝙗𝙞𝙜𝙖𝙣, 𝙝𝙖𝙣𝙜𝙜𝙖𝙣𝙜 𝙢𝙖𝙜 𝙠𝙖𝙞𝙗𝙞𝙜𝙖𝙣&quot;', '2025-10-20 01:50:38'),
(64, 2, 'Event Status Changed', 'Event ID 2 unpublished', '2025-10-20 01:51:24'),
(65, 2, 'Event Status Changed', 'Event ID 2 published', '2025-10-20 01:51:26'),
(66, 2, 'User Logout', 'User logged out', '2025-10-20 01:51:31'),
(67, 6, 'User Login', 'User logged in successfully', '2025-10-20 01:51:35'),
(68, 6, 'User Logout', 'User logged out', '2025-10-20 01:52:28'),
(69, 6, 'User Login', 'User logged in successfully', '2025-10-20 01:59:59'),
(70, 6, 'User Login', 'User logged in successfully', '2025-10-20 08:52:17'),
(71, 6, 'User Logout', 'User logged out', '2025-10-20 08:57:10'),
(72, 2, 'User Login', 'User logged in successfully', '2025-10-20 08:57:17'),
(73, 2, 'Announcement Updated', 'Updated announcement: “𝙉𝙖𝙜𝙞𝙣𝙜 𝙢𝙖𝙜𝙠𝙖-𝙞𝙗𝙞𝙜𝙖𝙣, 𝙝𝙖𝙣𝙜𝙜𝙖𝙣𝙜 𝙢𝙖𝙜 𝙠𝙖𝙞𝙗𝙞𝙜𝙖𝙣&quot;', '2025-10-20 08:57:57'),
(74, 2, 'User Logout', 'User logged out', '2025-10-20 08:58:11'),
(75, 6, 'User Login', 'User logged in successfully', '2025-10-20 08:58:21'),
(76, 6, 'User Logout', 'User logged out', '2025-10-20 08:58:54'),
(77, 6, 'User Login', 'User logged in successfully', '2025-10-20 17:28:43'),
(78, 6, 'User Login', 'User logged in successfully', '2025-10-20 17:28:43'),
(79, 6, 'User Logout', 'User logged out', '2025-10-20 17:44:57'),
(80, 2, 'User Login', 'User logged in successfully', '2025-10-20 17:45:04'),
(81, 6, 'User Login', 'User logged in successfully', '2025-10-21 14:27:30'),
(82, 6, 'User Logout', 'User logged out', '2025-10-21 14:28:59'),
(83, 2, 'User Login', 'User logged in successfully', '2025-10-21 14:29:03'),
(84, 2, 'User Logout', 'User logged out', '2025-10-21 14:31:16'),
(85, 1, 'User Login', 'User logged in successfully', '2025-10-21 14:31:20'),
(86, 1, 'User Status Changed', 'Changed user ID 6 status to suspended', '2025-10-21 14:32:22'),
(87, 1, 'User Status Changed', 'Changed user ID 6 status to active', '2025-10-21 14:32:30'),
(88, 1, 'User Status Changed', 'Changed user ID 6 status to suspended', '2025-10-21 14:32:32'),
(89, 1, 'User Logout', 'User logged out', '2025-10-21 14:32:51'),
(90, 1, 'User Login', 'User logged in successfully', '2025-10-21 14:33:06'),
(91, 1, 'User Logout', 'User logged out', '2025-10-21 14:33:37'),
(92, 7, 'User Registration', 'New student account created', '2025-10-21 14:35:39'),
(93, 7, 'User Login', 'User logged in successfully', '2025-10-21 14:35:45'),
(94, 7, 'Profile Picture Updated', 'User updated profile picture', '2025-10-21 14:35:52'),
(95, 7, 'Profile Picture Updated', 'User updated profile picture', '2025-10-21 14:36:01'),
(96, 7, 'Profile Picture Updated', 'User updated profile picture', '2025-10-21 14:36:12'),
(97, 7, 'User Logout', 'User logged out', '2025-10-21 14:38:07'),
(98, 7, 'User Login', 'User logged in successfully', '2025-10-21 14:38:12'),
(99, 7, 'Password Changed', 'User changed password', '2025-10-21 14:38:20'),
(100, 7, 'User Logout', 'User logged out', '2025-10-21 14:38:22'),
(101, 7, 'User Login', 'User logged in successfully', '2025-10-21 14:38:29'),
(102, 7, 'User Logout', 'User logged out', '2025-10-21 14:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `announcement_image` varchar(255) DEFAULT NULL,
  `announcement_type` enum('general','urgent','reminder') DEFAULT 'general',
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `image_path`, `announcement_image`, `announcement_type`, `is_published`, `created_by`, `created_at`, `updated_at`, `category`) VALUES
(1, 'Tryouts Delayed – Safety First After Mindanao Earthquake', 'Good afternoon, ITECHS! The tryouts have been postponed due to the recent earthquake in Mindanao. We will announce further updates regarding the new schedule soon. Stay safe, everyone! 💙', NULL, '', 'general', 1, 2, '2025-10-11 14:02:02', '2025-10-12 08:50:58', 'general'),
(2, '𝑬𝑴𝑬𝑹𝑮𝑬𝑵𝑪𝒀 𝑯𝑶𝑻𝑳𝑰𝑵𝑬𝑺:', 'MINGLANILLA\r\nMAYOR&amp;#039;S OFFICE : 232-3780 \r\nMSWD : 0936-886-2970 \r\nRHU : 1 239-4431 \r\nRHU : 2 238-5170 \r\nBFP : 0931-054-1094 \r\nPNP : 0998-598-6337 \r\nMITCOM : 0908-662-2877 \r\nMDRRMO : 0943-850-9665\r\nKEEP SAFE EVERYONE!', NULL, 'announcement_1760193077_8406.jpg', 'general', 1, 2, '2025-10-11 14:31:17', '2025-10-11 14:45:04', 'general'),
(3, '“𝙉𝙖𝙜𝙞𝙣𝙜 𝙢𝙖𝙜𝙠𝙖-𝙞𝙗𝙞𝙜𝙖𝙣, 𝙝𝙖𝙣𝙜𝙜𝙖𝙣𝙜 𝙢𝙖𝙜 𝙠𝙖𝙞𝙗𝙞𝙜𝙖𝙣&quot;', '𝗜𝗠𝗣𝗢𝗥𝗧𝗔𝗡𝗧 𝗔𝗡𝗡𝗢𝗨𝗡𝗖𝗘𝗠𝗘𝗡𝗧\r\n\r\n𝗛𝗲𝗮𝗱𝘀 𝘂𝗽, 𝗜𝗧𝗘𝗖𝗛𝗦!\r\n𝙁𝙖𝙘𝙚-𝙩𝙤-𝙛𝙖𝙘𝙚 𝙘𝙡𝙖𝙨𝙨𝙚𝙨 𝙬𝙞𝙡𝙡 𝙤𝙛𝙛𝙞𝙘𝙞𝙖𝙡𝙡𝙮 𝙧𝙚𝙨𝙪𝙢𝙚 𝙩𝙤𝙢𝙤𝙧𝙧𝙤𝙬, 𝙊𝙘𝙩𝙤𝙗𝙚𝙧 20, 2025, 𝙛𝙤𝙧 𝙩𝙝𝙚 𝘾𝙤𝙡𝙡𝙚𝙜𝙚 𝘿𝙚𝙥𝙖𝙧𝙩𝙢𝙚𝙣𝙩.\r\n\r\nAll college instructors and students are expected to report onsite for their regular classes. Let’s get back to learning and reconnect in person once again.\r\n\r\n#iTechBackToSchool #ITechSociety #SCCUpdates', NULL, 'announcement_1760924332_4331.jpg', 'urgent', 1, 2, '2025-10-20 01:38:52', '2025-10-20 08:57:57', 'general');

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications`
--

CREATE TABLE `email_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `notification_type` enum('event_reminder','new_event','event_update','password_reset','registration_confirmation') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text NOT NULL,
  `event_category` varchar(100) DEFAULT NULL,
  `event_venue` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `event_end_time` time DEFAULT NULL,
  `event_image` varchar(255) DEFAULT 'default-event.jpg',
  `max_participants` int(11) DEFAULT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_title`, `event_description`, `event_category`, `event_venue`, `event_date`, `event_time`, `event_end_time`, `event_image`, `max_participants`, `registration_deadline`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', 'Step into a night filled with stars, laughter, and unforgettable memories! ✨ This is your chance to connect, celebrate, and shine bright with your fellow iTechs. Let the music, fun, and magic of the evening bring everyone closer together as we kick off another exciting year! 🌙💫\r\n📅 𝙎𝙚𝙥𝙩𝙚𝙢𝙗𝙚𝙧 20, 2025 | ⏰ 3:00 𝙋𝙈 \r\n📍 𝙈𝙞𝙣𝙜𝙡𝙖𝙣𝙞𝙡𝙡𝙖 𝙎𝙥𝙤𝙧𝙩𝙨 𝘾𝙤𝙢𝙥𝙡𝙚𝙭\r\nMark your calendars and be part of this stellar celebration you’ll never forget!\r\n📌 NOTE:\r\n𝘛𝘩𝘦 𝘢𝘵𝘵𝘪𝘳𝘦 𝘴𝘩𝘰𝘸𝘯 𝘪𝘯 𝘵𝘩𝘦 𝘳𝘦𝘧𝘦𝘳𝘦𝘯𝘤𝘦/𝘱𝘶𝘣𝘮𝘢𝘵 𝘴𝘦𝘳𝘷𝘦𝘴 𝘢𝘴 𝘪𝘯𝘴𝘱𝘪𝘳𝘢𝘵𝘪𝘰𝘯 𝘰𝘯𝘭𝘺. 𝘠𝘰𝘶 𝘢𝘳𝘦 𝘯𝘰𝘵 𝘳𝘦𝘲𝘶𝘪𝘳𝘦𝘥 𝘵𝘰 𝘸𝘦𝘢𝘳 𝘵𝘩𝘦 𝘦𝘹𝘢𝘤𝘵 𝘴𝘢𝘮𝘦 𝘥𝘦𝘴𝘪𝘨𝘯—𝘧𝘦𝘦𝘭 𝘧𝘳𝘦𝘦 𝘵𝘰 𝘴𝘩𝘰𝘸𝘤𝘢𝘴𝘦 𝘺𝘰𝘶𝘳 𝘰𝘸𝘯 𝘴𝘵𝘺𝘭𝘦 𝘵𝘩𝘢𝘵 𝘮𝘢𝘵𝘤𝘩𝘦𝘴 𝘵𝘩𝘦 𝘵𝘩𝘦𝘮𝘦.\r\n#ITAcquaintanceParty2025 #StCeciliasCollege', '', '𝙈𝙞𝙣𝙜𝙡𝙖𝙣𝙞𝙡𝙡𝙖 𝙎𝙥𝙤𝙧𝙩𝙨 𝘾𝙤𝙢𝙥𝙡𝙚𝙭', '2025-10-15', '17:30:00', '20:50:00', '68ebdbde20f05.jpg', 500, '2025-10-14 17:00:00', 1, 2, '2025-10-11 08:22:09', '2025-10-12 16:48:30'),
(2, '🌟 STARS of ITech Got Talent Revealed! 🌟', 'As part of our Acquaintance Party: Starry Night this Saturday, Sept 20, meet the talented contestants competing in 𝙄𝙏𝙚𝙘𝙝 𝙂𝙤𝙩 𝙏𝙖𝙡𝙚𝙣𝙩! 🎶💃🎭🎸\r\n🔍 Check out:\r\n- 🧑‍🎤 Contestants Lineup: See who’s shining bright!\r\n- 📚 Mechanics &amp; Criteria for Judging: Know how the talents will be showcased &amp; evaluated!\r\n- 🌠 Starry Night Vibes: Get ready for a night under the twinkling skies at our Acquaintance Party!\r\n👏 Let’s cheer for our IT stars! Who’s your favorite? 💬 Share with us!\r\n#itechgottalent #starrynight #acquaintanceparty #itechsociety&quot;', '', '𝙈𝙞𝙣𝙜𝙡𝙖𝙣𝙞𝙡𝙡𝙖 𝙎𝙥𝙤𝙧𝙩𝙨 𝘾𝙤𝙢𝙥𝙡𝙚𝙭', '2025-10-15', '17:00:00', '22:00:00', '68ec67613dedf.jpg', 500, '2025-10-14 23:59:00', 1, 2, '2025-10-13 02:43:45', '2025-10-20 01:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `event_comments`
--

CREATE TABLE `event_comments` (
  `comment_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_reactions`
--

CREATE TABLE `event_reactions` (
  `reaction_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','haha','wow','sad','angry') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('registered','attended','absent','cancelled') DEFAULT 'registered',
  `registered_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `user_id`, `registration_date`, `attendance_status`, `registered_at`, `status`) VALUES
(9, 1, 6, '2025-10-14 01:11:47', 'attended', '2025-10-14 09:11:47', 'pending'),
(10, 2, 6, '2025-10-14 01:11:56', 'attended', '2025-10-14 09:11:56', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `notification_title` varchar(255) NOT NULL,
  `notification_message` text NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `notification_type`, `notification_title`, `notification_message`, `event_id`, `is_read`, `created_at`) VALUES
(4, 6, 'attendance', 'Attendance Confirmed! ✅', 'Your attendance for \'🌟 STARS of ITech Got Talent Revealed! 🌟\' has been marked as ATTENDED. Thank you for participating!', 2, 1, '2025-10-19 15:20:17'),
(5, 6, 'attendance', 'Attendance Confirmed! ✅', 'Your attendance for \'𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!\' has been marked as ATTENDED. Thank you for participating!', 1, 1, '2025-10-19 15:20:28'),
(6, 6, 'general', 'New Announcement: “𝙉𝙖𝙜𝙞𝙣𝙜 𝙢𝙖𝙜𝙠𝙖-𝙞𝙗𝙞𝙜𝙖𝙣, 𝙝𝙖𝙣𝙜𝙜𝙖𝙣𝙜 𝙢𝙖𝙜 𝙠𝙖𝙞𝙗𝙞𝙜𝙖𝙣&quot;', 'A new announcement has been posted. Check it out!', NULL, 1, '2025-10-20 01:38:52');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, NULL, 'User Registration', 'New student account created', '::1', '2025-10-08 02:53:06'),
(2, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 02:53:19'),
(3, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:33:12'),
(4, 1, 'User Logout', 'User logged out', '::1', '2025-10-08 07:36:07'),
(5, NULL, 'User Registration', 'New student account created', '::1', '2025-10-08 07:36:32'),
(6, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:36:44'),
(7, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 07:36:53'),
(8, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:37:07'),
(9, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 07:39:06'),
(10, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:40:03'),
(11, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 07:48:49'),
(12, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:49:01'),
(13, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 07:50:11'),
(14, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:50:20'),
(15, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 07:51:42'),
(16, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 07:52:05'),
(17, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 08:14:54'),
(18, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 12:25:44'),
(19, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 12:59:41'),
(20, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 13:04:57'),
(21, NULL, 'User Registration', 'New student account created', '::1', '2025-10-08 13:12:11'),
(22, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 13:12:18'),
(23, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 13:12:49'),
(24, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 13:12:56'),
(25, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:05:46'),
(26, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:22:50'),
(27, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 17:27:59'),
(28, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:29:01'),
(29, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 17:34:06'),
(30, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:36:27'),
(31, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 17:36:40'),
(32, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:36:44'),
(33, NULL, 'User Logout', 'User logged out', '::1', '2025-10-08 17:36:48'),
(34, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-08 17:55:39'),
(35, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 04:02:38'),
(36, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 04:03:11'),
(37, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 04:05:51'),
(38, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 04:11:34'),
(39, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-09 04:11:40'),
(40, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 04:16:00'),
(41, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 04:18:16'),
(42, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 04:23:07'),
(43, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 04:28:10'),
(44, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 04:28:14'),
(45, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 09:05:19'),
(46, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 09:06:13'),
(47, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 09:10:59'),
(48, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 09:11:35'),
(49, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 12:32:13'),
(50, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 12:45:59'),
(51, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 12:46:05'),
(52, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 12:48:16'),
(53, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 12:49:14'),
(54, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 12:56:26'),
(55, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 12:57:07'),
(56, NULL, 'User Logout', 'User logged out', '::1', '2025-10-09 13:00:19'),
(57, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 13:00:45'),
(58, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-09 13:00:53'),
(59, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 14:05:47'),
(60, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 23:25:58'),
(61, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-09 23:34:09'),
(62, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:02:31'),
(63, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 08:06:40'),
(64, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:09:16'),
(65, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 08:09:34'),
(66, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:09:38'),
(67, 2, 'Event Created', 'Created event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-11 08:22:09'),
(68, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 08:22:15'),
(69, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:22:19'),
(70, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 08:25:01'),
(71, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:25:06'),
(72, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 08:25:07'),
(73, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 08:25:23'),
(74, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 08:25:57'),
(75, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:40:09'),
(76, 2, 'Event Status Changed', 'Event ID: 1 - unpublished', '::1', '2025-10-11 13:41:46'),
(77, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 13:41:52'),
(78, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:42:05'),
(79, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 13:42:13'),
(80, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:42:19'),
(81, 2, 'Event Status Changed', 'Event ID: 1 - published', '::1', '2025-10-11 13:42:33'),
(82, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 13:42:44'),
(83, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:42:49'),
(84, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-11 13:45:41'),
(85, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 13:45:45'),
(86, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:45:49'),
(87, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 13:46:17'),
(88, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:46:27'),
(89, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-11 13:46:54'),
(90, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 13:46:56'),
(91, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:47:03'),
(92, NULL, 'Event Registration', 'Registered for event ID: 1', '::1', '2025-10-11 13:47:11'),
(93, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-11 13:49:08'),
(94, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-11 13:49:20'),
(95, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 13:49:59'),
(96, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:50:23'),
(97, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-11 13:50:31'),
(98, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-11 13:50:44'),
(99, NULL, 'Event Registration', 'Registered for event ID: 1', '::1', '2025-10-11 13:50:50'),
(100, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 13:52:29'),
(101, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 13:52:33'),
(102, 2, 'Announcement Created', 'Created announcement: &quot;Tryouts Delayed – Safety First After Mindanao Earthquake&quot;', '::1', '2025-10-11 14:02:02'),
(103, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:02:08'),
(104, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:02:12'),
(105, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:04:35'),
(106, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:04:42'),
(107, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:12:33'),
(108, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:12:39'),
(109, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:13:32'),
(110, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:13:36'),
(111, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:21:22'),
(112, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:21:28'),
(113, 2, 'Announcement Updated', 'Updated announcement: Tryouts Delayed – Safety First After Mindanao Earthquake&amp;quot;', '::1', '2025-10-11 14:27:28'),
(114, 2, 'Announcement Updated', 'Updated announcement: Tryouts Delayed – Safety First After Mindanao Earthquake&amp;amp;quot;', '::1', '2025-10-11 14:27:34'),
(115, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:27:39'),
(116, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:27:43'),
(117, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:27:49'),
(118, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:27:56'),
(119, 2, 'Announcement Created', 'Created announcement: 𝑬𝑴𝑬𝑹𝑮𝑬𝑵𝑪𝒀 𝑯𝑶𝑻𝑳𝑰𝑵𝑬𝑺:', '::1', '2025-10-11 14:31:17'),
(120, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:31:27'),
(121, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:31:31'),
(122, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-11 14:35:13'),
(123, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-11 14:35:18'),
(124, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-11 14:38:50'),
(125, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:38:55'),
(126, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:38:59'),
(127, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-11 14:39:05'),
(128, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:39:10'),
(129, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:39:14'),
(130, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:39:36'),
(131, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:39:47'),
(132, NULL, 'User Logout', 'User logged out', '::1', '2025-10-11 14:44:08'),
(133, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:44:15'),
(134, 2, 'Announcement Updated', 'Updated announcement: 𝑬𝑴𝑬𝑹𝑮𝑬𝑵𝑪𝒀 𝑯𝑶𝑻𝑳𝑰𝑵𝑬𝑺:', '::1', '2025-10-11 14:45:04'),
(135, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:45:28'),
(136, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:46:11'),
(137, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:46:21'),
(138, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:47:58'),
(139, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:48:01'),
(140, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:48:09'),
(141, 2, 'User Logout', 'User logged out', '::1', '2025-10-11 14:49:53'),
(142, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-11 14:49:57'),
(143, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 08:22:00'),
(144, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 08:22:18'),
(145, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 08:22:27'),
(146, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 08:23:28'),
(147, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 08:31:22'),
(148, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:02'),
(149, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:02'),
(150, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:02'),
(151, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:03'),
(152, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:03'),
(153, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:03'),
(154, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:44:03'),
(155, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:45:31'),
(156, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:47:04'),
(157, 2, 'Announcement Updated', 'Updated announcement: Tryouts Delayed – Safety First After Mindanao Earthquake', '::1', '2025-10-12 08:50:58'),
(158, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 08:56:49'),
(159, 2, 'Export Registrations', 'Exported registrations for event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 09:02:21'),
(160, 2, 'Event Status Changed', 'Event ID 1 unpublished', '::1', '2025-10-12 09:02:29'),
(161, 2, 'Event Status Changed', 'Event ID 1 published', '::1', '2025-10-12 09:02:31'),
(162, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 09:06:28'),
(163, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:21:54'),
(164, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 09:23:03'),
(165, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:23:10'),
(166, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 09:24:16'),
(167, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:24:21'),
(168, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 09:26:16'),
(169, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 09:26:26'),
(170, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:26:32'),
(171, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 09:26:44'),
(172, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:26:50'),
(173, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 09:27:05'),
(174, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 09:27:27'),
(175, NULL, 'User Registration', 'New student account created', '::1', '2025-10-12 09:27:54'),
(176, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:28:00'),
(177, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-12 09:28:11'),
(178, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 09:33:32'),
(179, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:33:38'),
(180, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 09:34:10'),
(181, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 09:34:15'),
(182, 2, 'Announcement Updated', 'Updated announcement: Tryouts Delayed – Safety First After Mindanao Earthquake', '::1', '2025-10-12 09:35:24'),
(183, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 15:58:19'),
(184, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 15:59:13'),
(185, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 15:59:18'),
(186, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 16:24:59'),
(187, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:25:54'),
(188, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 16:28:44'),
(189, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:29:13'),
(190, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 16:29:21'),
(191, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 16:40:54'),
(192, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:41:02'),
(193, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 16:41:37'),
(194, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 16:41:45'),
(195, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:41:49'),
(196, NULL, 'Event Registration', 'Registered for event ID: 1', '::1', '2025-10-12 16:41:52'),
(197, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 16:42:26'),
(198, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:47:34'),
(199, 2, 'Event Updated', 'Updated event: 𝘼𝙘𝙦𝙪𝙖𝙞𝙣𝙩𝙖𝙣𝙘𝙚 𝙋𝙖𝙧𝙩𝙮 2025!', '::1', '2025-10-12 16:48:30'),
(200, 2, 'User Logout', 'User logged out', '::1', '2025-10-12 16:49:53'),
(201, NULL, 'User Registration', 'New student account created', '::1', '2025-10-12 16:50:18'),
(202, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:50:28'),
(203, NULL, 'User Logout', 'User logged out', '::1', '2025-10-12 16:50:43'),
(204, NULL, 'User Registration', 'New student account created', '::1', '2025-10-12 16:51:03'),
(205, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-12 16:51:22'),
(206, NULL, 'Event Registration', 'Registered for event ID: 1', '::1', '2025-10-12 16:51:34'),
(207, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:38:48'),
(208, 2, 'Event Created', 'Created event: 🌟 STARS of ITech Got Talent Revealed! 🌟', '::1', '2025-10-13 02:43:45'),
(209, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 02:43:55'),
(210, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:44:02'),
(211, NULL, 'Event Registration', 'Registered for event ID: 2', '::1', '2025-10-13 02:44:15'),
(212, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-13 02:44:53'),
(213, NULL, 'Profile Updated', 'User updated profile information', '::1', '2025-10-13 02:45:05'),
(214, NULL, 'Password Changed', 'User changed password', '::1', '2025-10-13 02:45:13'),
(215, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 02:45:15'),
(216, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:45:22'),
(217, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 02:45:49'),
(218, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:45:55'),
(219, NULL, 'Event Unregistration', 'Unregistered from event ID: 2', '::1', '2025-10-13 02:46:23'),
(220, NULL, 'Event Registration', 'Registered for event ID: 2', '::1', '2025-10-13 02:46:25'),
(221, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 02:46:39'),
(222, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:46:43'),
(223, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 02:46:57'),
(224, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:47:02'),
(225, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 02:47:36'),
(226, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:47:40'),
(227, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 02:47:49'),
(228, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:47:55'),
(229, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 02:55:07'),
(230, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 02:55:18'),
(231, 1, 'User Deleted', 'Deleted user ID 5', '::1', '2025-10-13 02:58:49'),
(232, 1, 'User Status Changed', 'Changed user ID 3 status to suspended', '::1', '2025-10-13 02:59:14'),
(233, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 02:59:23'),
(234, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:00:22'),
(235, 1, 'User Status Changed', 'Changed user ID 3 status to active', '::1', '2025-10-13 03:00:29'),
(236, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:06:57'),
(237, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:07:01'),
(238, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 03:07:31'),
(239, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:07:57'),
(240, 1, 'System Maintenance', 'Database backup initiated', '::1', '2025-10-13 03:10:22'),
(241, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:12:26'),
(242, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:12:31'),
(243, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 03:12:32'),
(244, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:12:37'),
(245, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:14:12'),
(246, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:14:21'),
(247, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-13 03:14:27'),
(248, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-13 03:14:30'),
(249, NULL, 'Profile Picture Updated', 'User updated profile picture', '::1', '2025-10-13 03:14:36'),
(250, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 03:14:39'),
(251, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:14:43'),
(252, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:17:06'),
(253, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:17:14'),
(254, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 03:17:17'),
(255, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:17:24'),
(256, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:30:26'),
(257, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:30:59'),
(258, 2, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:31:29'),
(259, 2, 'User Logout', 'User logged out', '::1', '2025-10-13 03:31:40'),
(260, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:31:46'),
(261, 1, 'User Logout', 'User logged out', '::1', '2025-10-13 03:32:30'),
(262, NULL, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:32:43'),
(263, NULL, 'Event Registration', 'Registered for event ID: 1', '::1', '2025-10-13 03:32:49'),
(264, NULL, 'Event Registration', 'Registered for event ID: 2', '::1', '2025-10-13 03:32:53'),
(265, NULL, 'User Logout', 'User logged out', '::1', '2025-10-13 03:32:57'),
(266, 1, 'User Login', 'User logged in successfully', '::1', '2025-10-13 03:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','sao_staff','admin') NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `user_type`, `student_id`, `profile_picture`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `status`, `created_at`, `last_activity`, `updated_at`) VALUES
(1, 'System', 'Administrator', 'admin@sao.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'default.jpg', 1, NULL, NULL, NULL, 'active', '2025-10-08 01:50:22', NULL, '2025-10-08 01:50:22'),
(2, 'SAO', 'Staff', 'sao@sao.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sao_staff', NULL, 'default.jpg', 1, NULL, NULL, NULL, 'active', '2025-10-08 01:50:33', NULL, '2025-10-08 01:50:33'),
(6, 'France', 'Adolf', 'Adolfborja132@gmail.com', '$2y$10$LTAdrM2U2qqH7CA3dxtsF.DW5mdGzYIg/PPj09vJHGIuSLsA4rQ2O', 'student', '555555', 'profile_6_1760404331.jpg', 0, '846f358d6b70fe5efcdc24eb5271c22e879f8b3dd6351f44949f13674e21615f', NULL, NULL, 'suspended', '2025-10-14 01:11:37', NULL, '2025-10-21 14:32:32'),
(7, 'Cedric', 'Sanchez', 'CedricSanchez@gmail.com', '$2y$10$wiZnp91jxAwEzRRBIrXco.uCuao.DXRXZU5Vpvrmyb8MHHzo1.jAi', 'student', '4124124', 'profile_7_1761057372.jpg', 0, 'ca00b360eb1b3b568ec9bedfadc136518aa15614f989d9ff49527bf348190f81', NULL, NULL, 'active', '2025-10-21 14:35:39', NULL, '2025-10-21 14:38:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_reactions`
--
ALTER TABLE `event_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `unique_reaction` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_notifications`
--
ALTER TABLE `email_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_comments`
--
ALTER TABLE `event_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_reactions`
--
ALTER TABLE `event_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD CONSTRAINT `email_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD CONSTRAINT `event_comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_reactions`
--
ALTER TABLE `event_reactions`
  ADD CONSTRAINT `event_reactions_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
