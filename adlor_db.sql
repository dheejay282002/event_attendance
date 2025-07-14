-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 05:00 AM
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
-- Database: `adlor_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `year_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT INTO `academic_calendar` (`id`, `academic_year`, `year_name`, `start_date`, `end_date`, `is_current`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2025-2026', 'Academic Year 2025-2026', '2025-08-01', '2026-07-31', 1, 1, '2025-07-11 08:46:47', '2025-07-11 08:46:47'),
(2, '2026-2027', 'Academic Year 2026-2027', '2026-08-01', '2027-07-31', 0, 1, '2025-07-11 08:46:47', '2025-07-11 08:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `event_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `event_id`, `time_in`, `time_out`, `created_at`, `updated_at`) VALUES
(2, '23-11797', 21, '2025-07-11 16:13:08', NULL, '2025-07-11 16:13:08', '2025-07-11 16:13:08');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'Information Technology program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'Computer Science program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(3, 'BSIS', 'Bachelor of Science in Information Systems', 'Information Systems program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(4, 'BSBA', 'Bachelor of Science in Business Administration', 'Business Administration program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(5, 'BSED', 'Bachelor of Science in Education', 'Education program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(6, 'BSEE', 'Bachelor of Science in Electrical Engineering', 'Electrical Engineering program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(7, 'BSME', 'Bachelor of Science in Mechanical Engineering', 'Mechanical Engineering program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(8, 'BSCE', 'Bachelor of Science in Civil Engineering', 'Civil Engineering program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(9, 'MAGICAL', 'Magical Studies', 'Hogwarts magical education program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(10, 'GEN', 'General Studies', 'General education program', 1, '2025-07-11 10:15:10', '2025-07-11 10:15:10'),
(11, 'Magical Studies', 'Magical Studies Program', 'Auto-generated course from student import', 1, '2025-07-11 10:20:14', '2025-07-11 10:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `assigned_sections` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allow_qr_scanner` tinyint(1) DEFAULT 1 COMMENT 'Allow QR scanner attendance',
  `allow_manual_entry` tinyint(1) DEFAULT 1 COMMENT 'Allow manual student ID entry',
  `attendance_method_note` text DEFAULT NULL COMMENT 'Optional note about attendance method restrictions',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID of the user who created this event',
  `creator_type` enum('admin','sbo') DEFAULT NULL COMMENT 'Type of user who created this event'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `start_datetime`, `end_datetime`, `assigned_sections`, `created_at`, `updated_at`, `allow_qr_scanner`, `allow_manual_entry`, `attendance_method_note`, `created_by`, `creator_type`) VALUES
(7, 'Sorting Ceremony', 'Annual sorting of new students into their respective houses', '2025-09-01 19:00:00', '2025-09-01 21:00:00', 'Gryffindor-1,Hufflepuff-1,Slytherin-1', '2025-07-11 09:12:06', '2025-07-11 10:24:48', 1, 1, NULL, NULL, NULL),
(8, 'Defense Against the Dark Arts Practical Exam', 'Practical examination for all 5th year students in DADA', '2025-06-15 09:00:00', '2025-06-15 12:00:00', 'Gryffindor-5,Hufflepuff-5,Ravenclaw-5,Slytherin-5', '2025-07-11 09:12:06', '2025-07-11 09:12:06', 1, 1, NULL, NULL, NULL),
(9, 'Quidditch World Cup Viewing', 'Watch the Quidditch World Cup finals in the Great Hall', '2025-07-20 14:00:00', '2025-07-20 18:00:00', 'Gryffindor-4,Gryffindor-5,Gryffindor-6,Gryffindor-7,Hufflepuff-5,Hufflepuff-6,Ravenclaw-4,Ravenclaw-5,Ravenclaw-6,Ravenclaw-7,Slytherin-4,Slytherin-5,Slytherin-6,Slytherin-7', '2025-07-11 09:12:06', '2025-07-11 10:24:48', 1, 1, NULL, NULL, NULL),
(10, 'Potions Master Class', 'Advanced potions brewing session with Professor Snape', '2025-03-10 10:00:00', '2025-03-10 13:00:00', 'Slytherin-6,Slytherin-7,Ravenclaw-6,Ravenclaw-7', '2025-07-11 09:12:06', '2025-07-11 09:12:06', 1, 1, NULL, NULL, NULL),
(11, 'Herbology Field Trip', 'Visit to the Forbidden Forest for rare plant collection', '2025-04-22 08:00:00', '2025-04-22 16:00:00', 'Hufflepuff-3,Hufflepuff-5,Gryffindor-3,Gryffindor-4', '2025-07-11 09:12:06', '2025-07-11 10:24:48', 1, 1, NULL, NULL, NULL),
(12, 'Transfiguration Tournament', 'Inter-house transfiguration competition', '2025-05-05 13:00:00', '2025-05-05 17:00:00', 'Gryffindor-5,Hufflepuff-5,Ravenclaw-5,Slytherin-5,Gryffindor-6,Hufflepuff-6,Ravenclaw-6,Slytherin-6', '2025-07-11 09:12:06', '2025-07-11 09:12:06', 1, 1, NULL, NULL, NULL),
(13, 'Care of Magical Creatures Demonstration', 'Hippogriff handling and care demonstration', '2025-02-14 11:00:00', '2025-02-14 15:00:00', 'Gryffindor-3,Slytherin-3,Hufflepuff-3,Ravenclaw-2', '2025-07-11 09:12:06', '2025-07-11 09:12:06', 1, 1, NULL, NULL, NULL),
(14, 'Astronomy Tower Observation Night', 'Planetary alignment observation and star charting', '2025-01-30 20:00:00', '2025-01-31 02:00:00', 'Ravenclaw-4,Ravenclaw-5,Ravenclaw-6,Ravenclaw-7', '2025-07-11 09:12:06', '2025-07-11 09:12:06', 1, 1, NULL, NULL, NULL),
(15, 'Dumbledore\'s Army Meeting', 'Secret defense training session', '2025-12-15 19:30:00', '2025-12-15 22:00:00', 'Gryffindor-4,Gryffindor-5,Hufflepuff-5,Ravenclaw-4,Ravenclaw-5', '2025-07-11 09:12:06', '2025-07-11 15:22:56', 1, 0, '', NULL, NULL),
(21, 'Test', '', '2025-07-12 00:07:00', '2025-07-12 01:07:00', 'NS-3A', '2025-07-11 16:10:35', '2025-07-11 16:10:35', 1, 1, '', 6, 'sbo'),
(23, 'UAPSA NIGHTTTT', '', '2025-07-12 00:10:00', '2025-07-12 00:20:00', 'NS-3A', '2025-07-11 16:15:18', '2025-07-11 16:15:18', 0, 1, '', 2, 'sbo');

-- --------------------------------------------------------

--
-- Table structure for table `official_students`
--

CREATE TABLE `official_students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `course` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `official_students`
--

INSERT INTO `official_students` (`id`, `student_id`, `full_name`, `course`, `section`, `created_at`, `updated_at`) VALUES
(13, '23-11797', 'Cristobal, Dee Jay B.', 'BSIT', 'NS-3A', '2025-07-11 08:47:21', '2025-07-11 10:25:41'),
(14, 'HP-0000001', 'McLaggen, Cormac', 'Magical Studies', 'Gryffindor-6', '2025-07-11 09:03:52', '2025-07-11 10:24:47'),
(15, 'HP-0000002', 'Vane, Romilda', 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(16, 'HP-0000003', 'Midgen, Eloise', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(17, 'HP-0000004', 'Bell, Katie', 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(18, 'HP-0000005', 'Robins, Demelza', 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(19, 'HP-0000006', 'Bobbin, Melinda', 'Magical Studies', 'Hufflepuff-6', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(20, 'HP-0000007', 'Jones, Megan', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(21, 'HP-0000008', 'Madley, Laura', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(22, 'HP-0000009', 'Perks, Sally-Anne', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(23, 'HP-0000010', 'Quirke, Orla', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(24, 'HP-0000011', 'MacDougal, Morag', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(25, 'HP-0000012', 'Entwhistle, Kevin', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(26, 'HP-0000013', 'Li, Su', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(27, 'HP-0000014', 'Cornfoot, Stephen', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(28, 'HP-0000015', 'Smith, Sally', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(29, 'HP-0000016', 'Carmichael, Eddie', 'Magical Studies', 'Ravenclaw-7', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(30, 'HP-0000017', 'Pritchard, Graham', 'Magical Studies', 'Slytherin-4', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(31, 'HP-0000018', 'Baddock, Malcolm', 'Magical Studies', 'Slytherin-4', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(32, 'HP-0000019', 'Harper', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(33, 'HP-0000020', 'Dingle, Harold', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(34, 'HP-0000021', 'Montague', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(35, 'HP-0000022', 'Warrington', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(36, 'HP-0000023', 'Warrington, Cassius', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(37, 'HP-0000024', 'Bletchley, Miles', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(38, 'HP-0000025', 'Urquhart', 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(39, 'HP-0000026', 'Vaisey', 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(40, 'HP-0000027', 'Derek', 'Magical Studies', 'Unknown-4', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(41, 'HP-0000028', 'Creevey, Dennis', 'Magical Studies', 'Gryffindor-3', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(42, 'HP-0000029', 'Creevey, Colin', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(43, 'HP-0000030', 'Potter, Albus Severus', 'Magical Studies', 'Slytherin-1', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(44, 'HP-0000031', 'Malfoy, Scorpius', 'Magical Studies', 'Slytherin-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(45, 'HP-0000032', 'Granger-Weasley, Rose', 'Magical Studies', 'Gryffindor-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(46, 'HP-0000033', 'Fredericks, Yann', 'Magical Studies', 'Hufflepuff-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(47, 'HP-0000034', 'Chapman, Polly', 'Magical Studies', 'Slytherin-2', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(48, 'HP-0000035', 'Bowker Jr., Craig', 'Magical Studies', 'Hufflepuff-3', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(49, 'HP-0000036', 'Jenkins, Karl', 'Magical Studies', 'Gryffindor-2', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(50, 'HP-0000037', 'Lexie', 'Magical Studies', 'Ravenclaw-2', '2025-07-11 09:03:54', '2025-07-11 10:24:52'),
(51, 'HP-0000038', 'Balthazar', 'Magical Studies', 'Slytherin-3', '2025-07-11 09:03:54', '2025-07-11 10:24:52'),
(52, 'HP-0000039', 'Potter, Harry', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:52'),
(53, 'HP-0000040', 'Granger, Hermione', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:53'),
(54, 'HP-0000041', 'Weasley, Ron', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:55'),
(55, 'HP-0000042', 'Longbottom, Neville', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:57'),
(56, 'HP-0000043', 'Thomas, Dean', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(57, 'HP-0000044', 'Finnigan, Seamus', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(58, 'HP-0000045', 'Brown, Lavender', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(59, 'HP-0000046', 'Patil, Parvati', 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:59'),
(60, 'HP-0000047', 'Weasley, Ginny', 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:54', '2025-07-11 10:24:59'),
(61, 'HP-0000048', 'Johnson, Angelina', 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:54', '2025-07-11 10:25:00'),
(62, 'HP-0000049', 'Spinnet, Alicia', 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(63, 'HP-0000050', 'Jordan, Lee', 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(64, 'HP-0000051', 'Wood, Oliver', 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(65, 'HP-0000052', 'Diggory, Cedric', 'Magical Studies', 'Hufflepuff-6', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(66, 'HP-0000053', 'Abbott, Hannah', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(67, 'HP-0000054', 'Macmillan, Ernie', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(68, 'HP-0000055', 'Bones, Susan', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(69, 'HP-0000056', 'Finch-Fletchley, Justin', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(70, 'HP-0000057', 'Smith, Zacharias', 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(71, 'HP-0000058', 'Chang, Cho', 'Magical Studies', 'Ravenclaw-6', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(72, 'HP-0000059', 'Lovegood, Luna', 'Magical Studies', 'Ravenclaw-4', '2025-07-11 09:03:55', '2025-07-11 10:25:04'),
(73, 'HP-0000060', 'Patil, Padma', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(74, 'HP-0000061', 'Corner, Michael', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(75, 'HP-0000062', 'Boot, Terry', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(76, 'HP-0000063', 'Goldstein, Anthony', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(77, 'HP-0000064', 'Turpin, Lisa', 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(78, 'HP-0000065', 'Edgecombe, Marietta', 'Magical Studies', 'Ravenclaw-6', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(79, 'HP-0000066', 'Malfoy, Draco', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:06'),
(80, 'HP-0000067', 'Parkinson, Pansy', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(81, 'HP-0000068', 'Crabbe, Vincent', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(82, 'HP-0000069', 'Goyle, Gregory', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(83, 'HP-0000070', 'Nott, Theodore', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(84, 'HP-0000071', 'Zabini, Blaise', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(85, 'HP-0000072', 'Bulstrode, Millicent', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(86, 'HP-0000073', 'Greengrass, Daphne', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(87, 'HP-0000074', 'Davis, Tracey', 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(88, 'HP-0000075', 'Greengrass, Astoria', 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(89, 'HP-0000076', 'Flint, Marcus', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(90, 'HP-0000077', 'Pucey, Adrian', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(91, 'HP-0000078', 'Higgs, Terence', 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:09'),
(92, 'HP-0000079', 'Davies, Roger', 'Magical Studies', 'Ravenclaw-7', '2025-07-11 09:03:56', '2025-07-11 10:25:09'),
(93, '23-10413', 'Agustin, Vrenelli M.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(94, '22-11588', 'Albano, Mikko L.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(95, '23-10424', 'Alber, Aaron John L.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(96, '23-10540', 'Andajer, Richard C.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(97, '23-10655', 'Aquino, Cris Adrian C.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(98, '23-10769', 'Austria, Christian James E.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(99, '23-10954', 'Baltazar, Rudy A.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(100, '23-11057', 'Battung, Joshua M.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(101, '23-20064', 'Bautista, Jane Anelin M.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(102, '22-10859', 'Bojangin, Trixie Nicole B.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(103, '23-11237', 'Bueno, Hains Cristine J.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(104, '23-11338', 'Cabanos, Rodel E.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(105, '23-11536', 'Carag, Angelo Miguel N.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(106, '23-11791', 'Cortez, Roland Jade D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(107, '23-15841', 'Curampez, Christian Johann D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(108, '23-11887', 'Dalog, Jazer Aaron D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(109, '23-11933', 'De Guzman, Marie Joy W.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(110, '23-12146', 'Domingo, John Willard D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(111, '23-12186', 'Donato, Charles Bobby Q.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(112, '23-12187', 'Donato, Judell L.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(113, '23-12216', 'Dulay, Chaspher Owen P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(114, '22-11305', 'Eder, Precious Karina Marie D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(115, '23-12352', 'Estrada, Jahmyl O.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(116, '24-17151', 'Gabina, Letlet R.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(117, '23-12528', 'Gabotero, John Loyd G.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(118, '23-12619', 'Gallo, Fredzamay C.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(119, '23-12631', 'Galut, John Elrond C.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(120, '23-12710', 'Garcia, Carlo V.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(121, '23-12992', 'Ilarde, Tristhan Reve P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(122, '23-15706', 'Javier, Joshua G.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(123, '21-11240', 'Juan, Ricci Leigh T.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(124, '23-13121', 'Labog, Ken Mark S.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(125, '23-13227', 'Larioza, Mary Grace P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:49'),
(126, '23-13273', 'Leongson, Carl Justine P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(127, '23-13335', 'Lopez, John Wincel M.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(128, '23-13439', 'Madayag, Frederick S.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(129, '23-15681', 'Agub, John Mark A.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(130, '23-13543', 'Manarang, Rhomar R.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(131, '23-13818', 'Mendoza, Fernando A.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(132, '23-13928', 'Morta, Melson S.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(133, '23-13979', 'Nicolas, Earl Ivan P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(134, '23-14030', 'Olipas, Norman Jay V.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(135, '23-14037', 'Olivete, Gian Carlo T.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(136, '23-14113', 'Pagatpatan, Jasmine Precious A.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(137, '23-20011', 'Puruganan, Maricar A.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(138, '23-14474', 'Ramones, John Joshua G.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(139, '23-14514', 'Ramos, Marites R.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(140, '23-14566', 'Respicio, Kenneth C.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(141, '23-14636', 'Robles, Richard H.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(142, '23-14647', 'Rodriguez, John Carlo P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(143, '23-14691', 'Rotugal, Jasmine L.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(144, '23-14708', 'Ruiz, Apolonio T.', 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(145, '23-14721', 'Saad, Gian Carlo O.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(146, '21-11232', 'Sotto, Eddieson F.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(147, '23-15020', 'Sumido, Jovan D.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(148, '23-15190', 'Tejada, Kristel Joy V.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(149, '23-15393', 'Valencia, Mariel P.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51'),
(150, '23-15472', 'Vergara, Justin Von T.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51'),
(151, '23-15474', 'Versoza, John Paul N.', 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51');

-- --------------------------------------------------------

--
-- Table structure for table `qr_generation_log`
--

CREATE TABLE `qr_generation_log` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sbo_users`
--

CREATE TABLE `sbo_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT 'SBO Member',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sbo_users`
--

INSERT INTO `sbo_users` (`id`, `email`, `password`, `profile_picture`, `full_name`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@sbo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'SBO Administrator', 'Administrator', 1, '2025-07-11 08:29:11', '2025-07-11 08:29:11'),
(2, 'sbo.president@school.edu', '$2y$10$DQ63ySVJOrJlTQNfZ9jhQuUZfJc1bE53JOvvhzmH9qL9bionxgnwS', NULL, 'SBO President', 'President', 1, '2025-07-11 08:38:43', '2025-07-11 08:38:43'),
(3, 'sbo.secretary@school.edu', '$2y$10$nOFeLUCPw2eGZ8ZuW9YFE.T1zSD5sekDXaZB2l27oOFsT8VyhABu.', NULL, 'SBO Secretary', 'Secretary', 1, '2025-07-11 08:38:43', '2025-07-11 08:38:43'),
(4, 'sbo.events@school.edu', '$2y$10$SD4Fcp.MQTRtEa.Rsdte9ew4pT2Xfp6B1cKyokJNhJSzqwM7pyXvG', NULL, 'Events Coordinator', 'Events Coordinator', 1, '2025-07-11 08:38:43', '2025-07-11 08:38:43'),
(5, 'dheejaycristobal28@gmail.com', '$2y$10$/MPz2T5qfSs9PlAnLfXCcueslq98Q3hbEmoOr1NyT6pKg0X7atJtC', NULL, 'Harry Potter', 'Secretary', 0, '2025-07-11 15:51:06', '2025-07-11 15:52:11'),
(6, 'officer@edu.ph', '$2y$10$OJ.fr2s8nmpzbi9i0tU42OvurjLb7sPkSS5YY8IZfHuMNOLejx262', NULL, 'Harry Potter', 'Secretary', 1, '2025-07-11 15:52:42', '2025-07-11 15:52:42'),
(7, 'test.sbo@adlor.com', '$2y$10$V86R8FSPmju1csTxRuiNAeoN6NTEm7bu2xSaJ/8HTjMcy0HoJnnTm', NULL, 'Test SBO User', 'Test Officer', 0, '2025-07-11 15:58:56', '2025-07-11 16:18:12');

-- --------------------------------------------------------

--
-- Table structure for table `scanner_settings`
--

CREATE TABLE `scanner_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `days_of_week` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scanner_settings`
--

INSERT INTO `scanner_settings` (`id`, `setting_name`, `setting_value`, `is_enabled`, `start_time`, `end_time`, `start_date`, `end_date`, `days_of_week`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'qr_scanner_enabled', 'enabled', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-11 08:39:14', '2025-07-11 15:25:27'),
(2, 'manual_id_entry_enabled', 'enabled', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-11 08:39:14', '2025-07-11 08:39:14'),
(3, 'scanner_schedule_enabled', 'disabled', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-11 08:39:14', '2025-07-11 08:39:14'),
(4, 'scanner_time_restriction', 'none', 0, '08:00:00', '17:00:00', NULL, NULL, NULL, NULL, '2025-07-11 08:39:14', '2025-07-11 08:39:14'),
(5, 'scanner_date_restriction', 'none', 0, NULL, NULL, '2025-07-11', '2025-08-10', NULL, NULL, '2025-07-11 08:39:14', '2025-07-11 08:39:14'),
(6, 'scanner_days_restriction', 'none', 0, NULL, NULL, NULL, NULL, '1,2,3,4,5', NULL, '2025-07-11 08:39:14', '2025-07-11 08:39:14');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_code` varchar(50) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `year_id` int(11) DEFAULT NULL,
  `max_students` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_code`, `section_name`, `course_id`, `year_level`, `is_active`, `created_at`, `updated_at`, `year_id`, `max_students`) VALUES
(1, 'BSIT-1A', 'BSIT 1A', 1, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(2, 'BSIT-1B', 'BSIT 1B', 1, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(3, 'BSIT-2A', 'BSIT 2A', 1, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(4, 'BSIT-2B', 'BSIT 2B', 1, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(5, 'BSIT-3A', 'BSIT 3A', 1, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(6, 'BSIT-3B', 'BSIT 3B', 1, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(7, 'BSIT-4A', 'BSIT 4A', 1, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(8, 'BSIT-4B', 'BSIT 4B', 1, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(9, 'Gryffindor-1', 'Gryffindor 1st Year', 9, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(10, 'Gryffindor-2', 'Gryffindor 2nd Year', 9, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(11, 'Gryffindor-3', 'Gryffindor 3rd Year', 9, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(12, 'Gryffindor-4', 'Gryffindor 4th Year', 9, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(13, 'Gryffindor-5', 'Gryffindor 5th Year', 9, 5, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 9, 50),
(14, 'Gryffindor-6', 'Gryffindor 6th Year', 9, 6, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 10, 50),
(15, 'Gryffindor-7', 'Gryffindor 7th Year', 9, 7, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 11, 50),
(16, 'Hufflepuff-1', 'Hufflepuff 1st Year', 9, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(17, 'Hufflepuff-2', 'Hufflepuff 2nd Year', 9, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(18, 'Hufflepuff-3', 'Hufflepuff 3rd Year', 9, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(19, 'Hufflepuff-4', 'Hufflepuff 4th Year', 9, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(20, 'Hufflepuff-5', 'Hufflepuff 5th Year', 9, 5, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 9, 50),
(21, 'Hufflepuff-6', 'Hufflepuff 6th Year', 9, 6, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 10, 50),
(22, 'Hufflepuff-7', 'Hufflepuff 7th Year', 9, 7, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 11, 50),
(23, 'Ravenclaw-1', 'Ravenclaw 1st Year', 9, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(24, 'Ravenclaw-2', 'Ravenclaw 2nd Year', 9, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(25, 'Ravenclaw-3', 'Ravenclaw 3rd Year', 9, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(26, 'Ravenclaw-4', 'Ravenclaw 4th Year', 9, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(27, 'Ravenclaw-5', 'Ravenclaw 5th Year', 9, 5, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 9, 50),
(28, 'Ravenclaw-6', 'Ravenclaw 6th Year', 9, 6, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 10, 50),
(29, 'Ravenclaw-7', 'Ravenclaw 7th Year', 9, 7, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 11, 50),
(30, 'Slytherin-1', 'Slytherin 1st Year', 9, 1, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 1, 50),
(31, 'Slytherin-2', 'Slytherin 2nd Year', 9, 2, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 2, 50),
(32, 'Slytherin-3', 'Slytherin 3rd Year', 9, 3, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 3, 50),
(33, 'Slytherin-4', 'Slytherin 4th Year', 9, 4, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 4, 50),
(34, 'Slytherin-5', 'Slytherin 5th Year', 9, 5, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 9, 50),
(35, 'Slytherin-6', 'Slytherin 6th Year', 9, 6, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 10, 50),
(36, 'Slytherin-7', 'Slytherin 7th Year', 9, 7, 1, '2025-07-11 10:15:10', '2025-07-11 10:23:32', 11, 50),
(63, 'Unknown-4', 'Section Unknown-4', 11, NULL, 1, '2025-07-11 10:24:49', '2025-07-11 10:24:49', 1, 50),
(116, 'NS-3A', 'Section NS-3A', 1, NULL, 1, '2025-07-11 10:25:40', '2025-07-11 10:25:40', 1, 50);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `course` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `full_name`, `course`, `section`, `password`, `profile_picture`, `photo`, `created_at`, `updated_at`) VALUES
(1, '23-11797', 'Cristobal, Dee Jay B.', 'BSIT', 'NS-3A', '$2y$10$emmDTRQ/8R.RM/Y/S0gj9ejAdGNZ4Z5mFcqxoWqow9T7rzpszSMzu', 'uploads/profile_pictures/students/student_23-11797_1752226242.png', 'uploads/23-11797_1748333384_1000027700.png', '2025-07-11 09:08:49', '2025-07-11 10:25:41'),
(3, 'HP-0000039', 'Potter, Harry', 'Magical Studies', 'Gryffindor-5', '$2y$10$QCGcGIR56VKe6gJ/bpAjxOf.akmiPbjk4ldK8Sw1c.DyiNI3AGzgu', NULL, NULL, '2025-07-11 09:14:06', '2025-07-11 10:24:52'),
(4, 'HP-0000040', 'Granger, Hermione', 'Magical Studies', 'Gryffindor-5', '$2y$10$0OPPXG9IysVspjR.s9BnFebfOCZBnuRXy69.aCLOfqvCF6LOctcgO', NULL, NULL, '2025-07-11 09:14:06', '2025-07-11 10:24:53'),
(5, 'HP-0000041', 'Weasley, Ron', 'Magical Studies', 'Gryffindor-5', '$2y$10$3h/W5xDFd8b0jj78sBbw3uRsPwBmhonnMx6McY6fcnJQ74PagDhau', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:24:55'),
(6, 'HP-0000066', 'Malfoy, Draco', 'Magical Studies', 'Slytherin-5', '$2y$10$dvgWjXlCMpWEOG7DB9ObSuitntSoa.pL.NbQnQiKwzeHCu..jSWCO', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:25:06'),
(7, 'HP-0000059', 'Lovegood, Luna', 'Magical Studies', 'Ravenclaw-4', '$2y$10$5G0umyFvWdbuxCzIMQ.Ot.OrljsH.9yfP9dRGYzVYdk9b9tv.1t76', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:25:04'),
(8, 'HP-0000052', 'Diggory, Cedric', 'Magical Studies', 'Hufflepuff-6', '$2y$10$uYQhf8F3RM8xm7cYav.IJOcLK1csEsEwlLV0lerL7025LIyCRi6mi', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:25:00'),
(9, 'HP-0000047', 'Weasley, Ginny', 'Magical Studies', 'Gryffindor-4', '$2y$10$Y4msuUGEyL1f.kM/Ax4WtOBj4p02lkSJAPLe1hohjgZiG6VncpGVe', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:24:59'),
(10, 'HP-0000042', 'Longbottom, Neville', 'Magical Studies', 'Gryffindor-5', '$2y$10$5TXgMqDd4nzJ0T0fEET9wOoyOel3ibbglM6ivNwCWfAqI7rf99XNi', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:24:57'),
(11, 'HP-0000058', 'Chang, Cho', 'Magical Studies', 'Ravenclaw-6', '$2y$10$k7jFutoLAddznYkrNZuWAOST9Kl7.a9wqBukHjCUTwlng.Y7i3cEe', NULL, NULL, '2025-07-11 09:14:07', '2025-07-11 10:25:02'),
(12, 'HP-0000030', 'Potter, Albus Severus', 'Magical Studies', 'Slytherin-1', '$2y$10$2Vyug2lyZje67qmTKd5FEOowSVT6AMcbqxelp9TLBa9Sj16VFvTVa', NULL, NULL, '2025-07-11 09:14:08', '2025-07-11 10:24:49');

-- --------------------------------------------------------

--
-- Table structure for table `student_sync_log`
--

CREATE TABLE `student_sync_log` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `operations` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_sync_log`
--

INSERT INTO `student_sync_log` (`id`, `student_id`, `action`, `operations`, `timestamp`) VALUES
(1, '23-11797', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: 3A-NS)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 08:47:21'),
(2, 'HP-0000001', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(3, 'HP-0000002', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(4, 'HP-0000003', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(5, 'HP-0000004', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(6, 'HP-0000005', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(7, 'HP-0000006', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(8, 'HP-0000007', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(9, 'HP-0000008', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(10, 'HP-0000009', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(11, 'HP-0000010', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(12, 'HP-0000011', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(13, 'HP-0000012', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:52'),
(14, 'HP-0000013', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(15, 'HP-0000014', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(16, 'HP-0000015', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(17, 'HP-0000016', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(18, 'HP-0000017', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(19, 'HP-0000018', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(20, 'HP-0000019', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(21, 'HP-0000020', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(22, 'HP-0000021', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(23, 'HP-0000022', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(24, 'HP-0000023', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(25, 'HP-0000024', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(26, 'HP-0000025', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(27, 'HP-0000026', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(28, 'HP-0000027', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Unknown-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(29, 'HP-0000028', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(30, 'HP-0000029', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(31, 'HP-0000030', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(32, 'HP-0000031', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(33, 'HP-0000032', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:53'),
(34, 'HP-0000033', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(35, 'HP-0000034', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(36, 'HP-0000035', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(37, 'HP-0000036', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(38, 'HP-0000037', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(39, 'HP-0000038', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(40, 'HP-0000039', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(41, 'HP-0000040', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(42, 'HP-0000041', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(43, 'HP-0000042', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(44, 'HP-0000043', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(45, 'HP-0000044', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(46, 'HP-0000045', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(47, 'HP-0000046', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(48, 'HP-0000047', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(49, 'HP-0000048', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:54'),
(50, 'HP-0000049', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(51, 'HP-0000050', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(52, 'HP-0000051', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(53, 'HP-0000052', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(54, 'HP-0000053', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(55, 'HP-0000054', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(56, 'HP-0000055', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(57, 'HP-0000056', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(58, 'HP-0000057', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(59, 'HP-0000058', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(60, 'HP-0000059', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(61, 'HP-0000060', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(62, 'HP-0000061', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(63, 'HP-0000062', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(64, 'HP-0000063', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(65, 'HP-0000064', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:55'),
(66, 'HP-0000065', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(67, 'HP-0000066', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(68, 'HP-0000067', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(69, 'HP-0000068', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(70, 'HP-0000069', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(71, 'HP-0000070', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(72, 'HP-0000071', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(73, 'HP-0000072', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(74, 'HP-0000073', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(75, 'HP-0000074', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(76, 'HP-0000075', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(77, 'HP-0000076', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(78, 'HP-0000077', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(79, 'HP-0000078', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(80, 'HP-0000079', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:03:56'),
(81, '23-10413', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(82, '22-11588', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(83, '23-10424', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(84, '23-10540', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(85, '23-10655', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(86, '23-10769', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(87, '23-10954', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(88, '23-11057', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(89, '23-20064', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(90, '22-10859', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(91, '23-11237', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(92, '23-11338', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(93, '23-11536', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:35'),
(94, '23-11791', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(95, '23-11797', 'update', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\",\"Cleaned up 1 unused sections\"]', '2025-07-11 09:04:36'),
(96, '23-15841', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(97, '23-11887', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(98, '23-11933', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(99, '23-12146', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(100, '23-12186', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(101, '23-12187', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(102, '23-12216', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(103, '22-11305', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(104, '23-12352', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(105, '24-17151', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(106, '23-12528', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(107, '23-12619', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(108, '23-12631', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(109, '23-12710', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(110, '23-12992', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(111, '23-15706', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(112, '21-11240', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(113, '23-13121', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(114, '23-13227', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:36'),
(115, '23-13273', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(116, '23-13335', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(117, '23-13439', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(118, '23-15681', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(119, '23-13543', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(120, '23-13818', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(121, '23-13928', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(122, '23-13979', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(123, '23-14030', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(124, '23-14037', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(125, '23-14113', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(126, '23-20011', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(127, '23-14474', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(128, '23-14514', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(129, '23-14566', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(130, '23-14636', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(131, '23-14647', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(132, '23-14691', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(133, '23-14708', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:37'),
(134, '23-14721', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(135, '21-11232', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(136, '23-15020', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(137, '23-15190', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(138, '23-15393', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(139, '23-15472', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(140, '23-15474', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 09:04:38'),
(141, 'HP-0000001', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\",\"Cleaned up 2 unused sections\"]', '2025-07-11 10:24:48'),
(142, 'HP-0000002', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(143, 'HP-0000003', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(144, 'HP-0000004', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(145, 'HP-0000005', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(146, 'HP-0000006', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(147, 'HP-0000007', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(148, 'HP-0000008', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(149, 'HP-0000009', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(150, 'HP-0000010', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(151, 'HP-0000011', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(152, 'HP-0000012', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(153, 'HP-0000013', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(154, 'HP-0000014', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(155, 'HP-0000015', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(156, 'HP-0000016', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:48'),
(157, 'HP-0000017', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(158, 'HP-0000018', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(159, 'HP-0000019', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(160, 'HP-0000020', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(161, 'HP-0000021', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(162, 'HP-0000022', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(163, 'HP-0000023', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(164, 'HP-0000024', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(165, 'HP-0000025', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(166, 'HP-0000026', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(167, 'HP-0000027', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Unknown-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(168, 'HP-0000028', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(169, 'HP-0000029', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:49'),
(170, 'HP-0000030', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Slytherin-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(171, 'HP-0000031', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(172, 'HP-0000032', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(173, 'HP-0000033', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-1)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(174, 'HP-0000034', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(175, 'HP-0000035', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(176, 'HP-0000036', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:51'),
(177, 'HP-0000037', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-2)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:52'),
(178, 'HP-0000038', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-3)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:52'),
(179, 'HP-0000039', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:53'),
(180, 'HP-0000040', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:55'),
(181, 'HP-0000041', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:57'),
(182, 'HP-0000042', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:58'),
(183, 'HP-0000043', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:58'),
(184, 'HP-0000044', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:58'),
(185, 'HP-0000045', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:59'),
(186, 'HP-0000046', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:24:59');
INSERT INTO `student_sync_log` (`id`, `student_id`, `action`, `operations`, `timestamp`) VALUES
(187, 'HP-0000047', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Gryffindor-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:00'),
(188, 'HP-0000048', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:00'),
(189, 'HP-0000049', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:00'),
(190, 'HP-0000050', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:00'),
(191, 'HP-0000051', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Gryffindor-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:00'),
(192, 'HP-0000052', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Hufflepuff-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(193, 'HP-0000053', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(194, 'HP-0000054', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(195, 'HP-0000055', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(196, 'HP-0000056', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(197, 'HP-0000057', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Hufflepuff-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:02'),
(198, 'HP-0000058', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Ravenclaw-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:04'),
(199, 'HP-0000059', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Ravenclaw-4)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(200, 'HP-0000060', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(201, 'HP-0000061', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(202, 'HP-0000062', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(203, 'HP-0000063', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(204, 'HP-0000064', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(205, 'HP-0000065', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:06'),
(206, 'HP-0000066', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(207, 'HP-0000067', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(208, 'HP-0000068', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(209, 'HP-0000069', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(210, 'HP-0000070', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(211, 'HP-0000071', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(212, 'HP-0000072', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(213, 'HP-0000073', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(214, 'HP-0000074', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-5)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(215, 'HP-0000075', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-6)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(216, 'HP-0000076', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(217, 'HP-0000077', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:08'),
(218, 'HP-0000078', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Slytherin-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:09'),
(219, 'HP-0000079', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: Ravenclaw-7)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:09'),
(220, '23-10413', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(221, '22-11588', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(222, '23-10424', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(223, '23-10540', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(224, '23-10655', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(225, '23-10769', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(226, '23-10954', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(227, '23-11057', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(228, '23-20064', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:40'),
(229, '22-10859', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:41'),
(230, '23-11237', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:41'),
(231, '23-11338', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:41'),
(232, '23-11536', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:41'),
(233, '23-11791', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:41'),
(234, '23-11797', 'add', '[\"Updated official_students table\",\"Updated students login table\",\"Regenerated student QR code\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(235, '23-15841', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(236, '23-11887', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(237, '23-11933', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(238, '23-12146', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(239, '23-12186', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(240, '23-12187', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:47'),
(241, '23-12216', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(242, '22-11305', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(243, '23-12352', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(244, '24-17151', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(245, '23-12528', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(246, '23-12619', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(247, '23-12631', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(248, '23-12710', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(249, '23-12992', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(250, '23-15706', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(251, '21-11240', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:48'),
(252, '23-13121', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(253, '23-13227', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(254, '23-13273', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(255, '23-13335', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(256, '23-13439', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(257, '23-15681', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(258, '23-13543', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(259, '23-13818', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(260, '23-13928', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(261, '23-13979', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(262, '23-14030', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(263, '23-14037', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:49'),
(264, '23-14113', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(265, '23-20011', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(266, '23-14474', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(267, '23-14514', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(268, '23-14566', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(269, '23-14636', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(270, '23-14647', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(271, '23-14691', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(272, '23-14708', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(273, '23-14721', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(274, '21-11232', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(275, '23-15020', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:50'),
(276, '23-15190', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:51'),
(277, '23-15393', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:51'),
(278, '23-15472', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:51'),
(279, '23-15474', 'add', '[\"Updated official_students table\",\"Updated year level information (extracted from section: NS-3A)\",\"Attendance records remain linked via student_id\",\"Event participation managed via assigned_sections\"]', '2025-07-11 10:25:51');

-- --------------------------------------------------------

--
-- Table structure for table `student_year_levels`
--

CREATE TABLE `student_year_levels` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `year_level` int(11) NOT NULL DEFAULT 1,
  `course` varchar(100) NOT NULL DEFAULT '',
  `section` varchar(100) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_year_levels`
--

INSERT INTO `student_year_levels` (`id`, `student_id`, `year_level`, `course`, `section`, `created_at`, `updated_at`) VALUES
(1, '202300001', 3, 'BSIT', 'IT-3A', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(2, '202300002', 2, 'BSCS', 'CS-2B', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(3, '202300003', 3, 'BSIT', 'IT-3A', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(4, '202300004', 2, 'BSCS', 'CS-2B', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(5, '202300005', 3, 'BSIT', 'IT-3B', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(6, '202300006', 3, 'BSCS', 'CS-3A', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(7, '202300007', 2, 'BSIT', 'IT-2A', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(8, '202300008', 2, 'BSCS', 'CS-2A', '2025-07-11 08:41:21', '2025-07-11 08:41:21'),
(16, '23-11797', 3, 'BSIT', 'NS-3A', '2025-07-11 08:47:21', '2025-07-11 10:25:47'),
(17, 'HP-0000001', 1, 'Magical Studies', 'Gryffindor-6', '2025-07-11 09:03:52', '2025-07-11 10:24:47'),
(18, 'HP-0000002', 4, 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(19, 'HP-0000003', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(20, 'HP-0000004', 1, 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(21, 'HP-0000005', 4, 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(22, 'HP-0000006', 1, 'Magical Studies', 'Hufflepuff-6', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(23, 'HP-0000007', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(24, 'HP-0000008', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(25, 'HP-0000009', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(26, 'HP-0000010', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(27, 'HP-0000011', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(28, 'HP-0000012', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(29, 'HP-0000013', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:52', '2025-07-11 10:24:48'),
(30, 'HP-0000014', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(31, 'HP-0000015', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(32, 'HP-0000016', 1, 'Magical Studies', 'Ravenclaw-7', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(33, 'HP-0000017', 4, 'Magical Studies', 'Slytherin-4', '2025-07-11 09:03:53', '2025-07-11 10:24:48'),
(34, 'HP-0000018', 4, 'Magical Studies', 'Slytherin-4', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(35, 'HP-0000019', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(36, 'HP-0000020', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(37, 'HP-0000021', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(38, 'HP-0000022', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(39, 'HP-0000023', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(40, 'HP-0000024', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(41, 'HP-0000025', 1, 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(42, 'HP-0000026', 1, 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(43, 'HP-0000027', 4, 'Magical Studies', 'Unknown-4', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(44, 'HP-0000028', 3, 'Magical Studies', 'Gryffindor-3', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(45, 'HP-0000029', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:53', '2025-07-11 10:24:49'),
(46, 'HP-0000030', 1, 'Magical Studies', 'Slytherin-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(47, 'HP-0000031', 1, 'Magical Studies', 'Slytherin-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(48, 'HP-0000032', 1, 'Magical Studies', 'Gryffindor-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(49, 'HP-0000033', 1, 'Magical Studies', 'Hufflepuff-1', '2025-07-11 09:03:53', '2025-07-11 10:24:51'),
(50, 'HP-0000034', 2, 'Magical Studies', 'Slytherin-2', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(51, 'HP-0000035', 3, 'Magical Studies', 'Hufflepuff-3', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(52, 'HP-0000036', 2, 'Magical Studies', 'Gryffindor-2', '2025-07-11 09:03:54', '2025-07-11 10:24:51'),
(53, 'HP-0000037', 2, 'Magical Studies', 'Ravenclaw-2', '2025-07-11 09:03:54', '2025-07-11 10:24:52'),
(54, 'HP-0000038', 3, 'Magical Studies', 'Slytherin-3', '2025-07-11 09:03:54', '2025-07-11 10:24:52'),
(55, 'HP-0000039', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:53'),
(56, 'HP-0000040', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:55'),
(57, 'HP-0000041', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:56'),
(58, 'HP-0000042', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(59, 'HP-0000043', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(60, 'HP-0000044', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(61, 'HP-0000045', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:58'),
(62, 'HP-0000046', 1, 'Magical Studies', 'Gryffindor-5', '2025-07-11 09:03:54', '2025-07-11 10:24:59'),
(63, 'HP-0000047', 4, 'Magical Studies', 'Gryffindor-4', '2025-07-11 09:03:54', '2025-07-11 10:25:00'),
(64, 'HP-0000048', 1, 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:54', '2025-07-11 10:25:00'),
(65, 'HP-0000049', 1, 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(66, 'HP-0000050', 1, 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(67, 'HP-0000051', 1, 'Magical Studies', 'Gryffindor-7', '2025-07-11 09:03:55', '2025-07-11 10:25:00'),
(68, 'HP-0000052', 1, 'Magical Studies', 'Hufflepuff-6', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(69, 'HP-0000053', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(70, 'HP-0000054', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(71, 'HP-0000055', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(72, 'HP-0000056', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(73, 'HP-0000057', 1, 'Magical Studies', 'Hufflepuff-5', '2025-07-11 09:03:55', '2025-07-11 10:25:02'),
(74, 'HP-0000058', 1, 'Magical Studies', 'Ravenclaw-6', '2025-07-11 09:03:55', '2025-07-11 10:25:04'),
(75, 'HP-0000059', 4, 'Magical Studies', 'Ravenclaw-4', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(76, 'HP-0000060', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(77, 'HP-0000061', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(78, 'HP-0000062', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(79, 'HP-0000063', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(80, 'HP-0000064', 1, 'Magical Studies', 'Ravenclaw-5', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(81, 'HP-0000065', 1, 'Magical Studies', 'Ravenclaw-6', '2025-07-11 09:03:55', '2025-07-11 10:25:06'),
(82, 'HP-0000066', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(83, 'HP-0000067', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(84, 'HP-0000068', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(85, 'HP-0000069', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(86, 'HP-0000070', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(87, 'HP-0000071', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(88, 'HP-0000072', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(89, 'HP-0000073', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(90, 'HP-0000074', 1, 'Magical Studies', 'Slytherin-5', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(91, 'HP-0000075', 1, 'Magical Studies', 'Slytherin-6', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(92, 'HP-0000076', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(93, 'HP-0000077', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:08'),
(94, 'HP-0000078', 1, 'Magical Studies', 'Slytherin-7', '2025-07-11 09:03:56', '2025-07-11 10:25:09'),
(95, 'HP-0000079', 1, 'Magical Studies', 'Ravenclaw-7', '2025-07-11 09:03:56', '2025-07-11 10:25:09'),
(96, '23-10413', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(97, '22-11588', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(98, '23-10424', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(99, '23-10540', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(100, '23-10655', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(101, '23-10769', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(102, '23-10954', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(103, '23-11057', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(104, '23-20064', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:40'),
(105, '22-10859', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(106, '23-11237', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(107, '23-11338', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(108, '23-11536', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(109, '23-11791', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:35', '2025-07-11 10:25:41'),
(111, '23-15841', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(112, '23-11887', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(113, '23-11933', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(114, '23-12146', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(115, '23-12186', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(116, '23-12187', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(117, '23-12216', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:47'),
(118, '22-11305', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(119, '23-12352', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(120, '24-17151', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(121, '23-12528', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(122, '23-12619', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(123, '23-12631', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(124, '23-12710', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(125, '23-12992', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(126, '23-15706', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(127, '21-11240', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(128, '23-13121', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:48'),
(129, '23-13227', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:36', '2025-07-11 10:25:49'),
(130, '23-13273', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(131, '23-13335', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(132, '23-13439', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(133, '23-15681', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(134, '23-13543', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(135, '23-13818', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(136, '23-13928', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(137, '23-13979', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(138, '23-14030', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(139, '23-14037', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(140, '23-14113', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:49'),
(141, '23-20011', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(142, '23-14474', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(143, '23-14514', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(144, '23-14566', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(145, '23-14636', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(146, '23-14647', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(147, '23-14691', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(148, '23-14708', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:37', '2025-07-11 10:25:50'),
(149, '23-14721', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(150, '21-11232', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(151, '23-15020', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(152, '23-15190', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:50'),
(153, '23-15393', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51'),
(154, '23-15472', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51'),
(155, '23-15474', 3, 'BSIT', 'NS-3A', '2025-07-11 09:04:38', '2025-07-11 10:25:51');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'system_name', 'HOGWARTS', '2025-07-11 08:38:32', '2025-07-11 09:13:26'),
(2, 'system_logo', 'assets/images/system_logo.png', '2025-07-11 08:38:32', '2025-07-11 08:46:18'),
(3, 'system_description', 'Event Attendance System', '2025-07-11 08:38:32', '2025-07-11 08:38:32'),
(4, 'system_version', '1.0.0', '2025-07-11 08:38:32', '2025-07-11 08:38:32');

-- --------------------------------------------------------

--
-- Table structure for table `year_levels`
--

CREATE TABLE `year_levels` (
  `id` int(11) NOT NULL,
  `year_code` varchar(10) NOT NULL,
  `year_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `year_levels`
--

INSERT INTO `year_levels` (`id`, `year_code`, `year_name`, `is_active`, `created_at`) VALUES
(1, '1', 'First Year', 1, '2025-07-11 08:52:06'),
(2, '2', 'Second Year', 1, '2025-07-11 08:52:06'),
(3, '3', 'Third Year', 1, '2025-07-11 08:52:06'),
(4, '4', 'Fourth Year', 1, '2025-07-11 08:52:06'),
(9, '5', '5th Year', 1, '2025-07-11 10:23:32'),
(10, '6', '6th Year', 1, '2025-07-11 10:23:32'),
(11, '7', '7th Year', 1, '2025-07-11 10:23:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_year` (`academic_year`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_event` (`student_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_attendance_student_event` (`student_id`,`event_id`),
  ADD KEY `idx_attendance_event_id` (`event_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_datetime` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_events_creator` (`creator_type`,`created_by`);

--
-- Indexes for table `official_students`
--
ALTER TABLE `official_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_official_students_student_id` (`student_id`);

--
-- Indexes for table `qr_generation_log`
--
ALTER TABLE `qr_generation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `sbo_users`
--
ALTER TABLE `sbo_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `scanner_settings`
--
ALTER TABLE `scanner_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_code` (`section_code`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_students_student_id` (`student_id`);

--
-- Indexes for table `student_sync_log`
--
ALTER TABLE `student_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `student_year_levels`
--
ALTER TABLE `student_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student` (`student_id`),
  ADD KEY `idx_year_level` (`year_level`),
  ADD KEY `idx_course` (`course`),
  ADD KEY `idx_section` (`section`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `year_levels`
--
ALTER TABLE `year_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_code` (`year_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `official_students`
--
ALTER TABLE `official_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=370;

--
-- AUTO_INCREMENT for table `qr_generation_log`
--
ALTER TABLE `qr_generation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbo_users`
--
ALTER TABLE `sbo_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `scanner_settings`
--
ALTER TABLE `scanner_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_sync_log`
--
ALTER TABLE `student_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- AUTO_INCREMENT for table `student_year_levels`
--
ALTER TABLE `student_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `year_levels`
--
ALTER TABLE `year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
