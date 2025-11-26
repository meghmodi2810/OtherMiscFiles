-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 25, 2025 at 09:09 PM
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
-- Database: `bmiit_pms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `temp_passkey` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `user_id`, `name`, `email`, `phone`, `temp_passkey`) VALUES
(1, 1, 'System Administrator', 'admin@gmail.com', '0000000000', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `name` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `semester_id`, `name`) VALUES
(1, 1, 'A'),
(2, 1, 'B'),
(3, 1, 'C');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`) VALUES
(1, 'B.Sc(IT)'),
(2, 'M.Sc(IT)'),
(3, 'Integrated M.Sc(IT)');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body_html` text NOT NULL,
  `body_plain` text DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `specialization` enum('Data Science','Web Development','Mobile Development','Machine Learning','Artificial Intelligence','Cybersecurity','Cloud Computing','DevOps','Database Management','Software Engineering','Network Administration','IoT','Blockchain','UI/UX Design','Other') NOT NULL DEFAULT 'Other',
  `experience` tinyint(2) UNSIGNED NOT NULL,
  `temp_passkey` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `faculty`
--
DELIMITER $$
CREATE TRIGGER `before_faculty_insert` BEFORE INSERT ON `faculty` FOR EACH ROW BEGIN
    DECLARE name_length INT;
    DECLARE counter INT;
    DECLARE current_char VARCHAR(1);
    DECLARE prev_char VARCHAR(1);
    DECLARE new_name VARCHAR(255);
    
    IF NEW.name IS NOT NULL THEN
        SET new_name = LOWER(TRIM(NEW.name));
        SET name_length = CHAR_LENGTH(new_name);
        SET counter = 1;
        SET prev_char = ' ';
        
        -- Capitalize first letter of each word
        WHILE counter <= name_length DO
            SET current_char = SUBSTRING(new_name, counter, 1);
            
            -- If previous character was a space, capitalize current character
            IF prev_char = ' ' THEN
                SET new_name = CONCAT(
                    SUBSTRING(new_name, 1, counter - 1),
                    UPPER(current_char),
                    SUBSTRING(new_name, counter + 1)
                );
            END IF;
            
            SET prev_char = current_char;
            SET counter = counter + 1;
        END WHILE;
        
        SET NEW.name = new_name;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_faculty_update` BEFORE UPDATE ON `faculty` FOR EACH ROW BEGIN
    DECLARE name_length INT;
    DECLARE counter INT;
    DECLARE current_char VARCHAR(1);
    DECLARE prev_char VARCHAR(1);
    DECLARE new_name VARCHAR(255);
    
    IF NEW.name IS NOT NULL THEN
        SET new_name = LOWER(TRIM(NEW.name));
        SET name_length = CHAR_LENGTH(new_name);
        SET counter = 1;
        SET prev_char = ' ';
        
        -- Capitalize first letter of each word
        WHILE counter <= name_length DO
            SET current_char = SUBSTRING(new_name, counter, 1);
            
            -- If previous character was a space, capitalize current character
            IF prev_char = ' ' THEN
                SET new_name = CONCAT(
                    SUBSTRING(new_name, 1, counter - 1),
                    UPPER(current_char),
                    SUBSTRING(new_name, counter + 1)
                );
            END IF;
            
            SET prev_char = current_char;
            SET counter = counter + 1;
        END WHILE;
        
        SET NEW.name = new_name;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `finalized` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `leader_id`, `semester_id`, `class_id`, `created_at`, `finalized`) VALUES
(1, 48, 1, 1, '2025-10-25 10:29:08', 0);

-- --------------------------------------------------------

--
-- Table structure for table `group_invites`
--

CREATE TABLE `group_invites` (
  `invite_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_invites`
--

INSERT INTO `group_invites` (`invite_id`, `group_id`, `sender_id`, `receiver_id`, `status`, `sent_at`) VALUES
(1, 1, 48, 46, 'accepted', '2025-10-25 11:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `member_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `role` enum('leader','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`member_id`, `group_id`, `student_id`, `role`, `joined_at`) VALUES
(1, 1, 48, 'leader', '2025-10-25 10:29:08'),
(2, 1, 46, 'member', '2025-10-25 11:50:25');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `year` varchar(20) NOT NULL,
  `semester_no` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `project_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `year`, `semester_no`, `course_id`, `project_active`) VALUES
(1, '2025-26', 5, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `semester_config`
--

CREATE TABLE `semester_config` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `interclass_allowed` tinyint(1) NOT NULL,
  `team_size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semester_config`
--

INSERT INTO `semester_config` (`id`, `semester_id`, `interclass_allowed`, `team_size`) VALUES
(1, 1, 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `temp_passkey` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `name`, `email`, `class_id`, `phone`, `temp_passkey`) VALUES
(1, 2, 'Hetvi Sonawala', 'hetvi.sonawala@bmiit.edu.in', 1, '9825167301', ''),
(2, 3, 'Mistry Vinit Yogeshkumar', 'vinit.mistry@bmiit.edu.in', 1, '9825167302', ''),
(3, 4, 'Misha Vimalbhai Patel', 'misha.patel@bmiit.edu.in', 1, '9825167303', ''),
(4, 5, 'Koladiya Monish Dipak', 'monish.koladiya@bmiit.edu.in', 1, '9825167305', ''),
(5, 6, 'Katariya Prince Dhirubhai', 'prince.katariya@bmiit.edu.in', 1, '9825167306', ''),
(6, 7, 'Hemilkumar Kamleshbhai Prajapati', 'hemilkumar.prajapati@bmiit.edu.in', 1, '9825167308', ''),
(7, 8, 'Jariwala Mrudvi Bhaveshkumar', 'mrudvi.jariwala@bmiit.edu.in', 1, '9825167309', ''),
(8, 9, 'Ansari Faizalam Mahmoodbhai', 'faizalam.ansari@bmiit.edu.in', 1, '9825167310', ''),
(9, 10, 'Agrawal Khushi Anil', 'khushi.agrawal@bmiit.edu.in', 1, '9825167311', ''),
(10, 11, 'Ahir Kripa Jaysukhbhai', 'kripa.ahir@bmiit.edu.in', 1, '9825167312', ''),
(11, 12, 'Aniket Nileshbhai Patel', 'aniket.patel@bmiit.edu.in', 1, '9825167316', ''),
(12, 13, 'Chavada Vaibhav Maheshbhai', 'vaibhav.chavada@bmiit.edu.in', 1, '9825167317', ''),
(13, 14, 'Dabhi Harsh Maheshbhai', 'harsh.dabhi@bmiit.edu.in', 1, '9825167318', ''),
(14, 15, 'Daksh Sandipkumar Patel', 'daksh.patel@bmiit.edu.in', 1, '9825167319', ''),
(15, 16, 'Desai Parth Bankim', 'parth.desai@bmiit.edu.in', 1, '9825167320', ''),
(16, 17, 'Devmurari Manthan Ketanbhai', 'manthan.devmurari@bmiit.edu.in', 1, '9825167322', ''),
(17, 18, 'Dhruv Dhavalkumar Patel', 'dhruv.patel@bmiit.edu.in', 1, '9825167323', ''),
(18, 19, 'Gosvami Rudradatt Shivdattpuri', 'rudradatt.gosvami@bmiit.edu.in', 1, '9825167325', ''),
(19, 20, 'Goyani Jemskumar Vipulbhai', 'jemskumar.goyani@bmiit.edu.in', 1, '9825167326', ''),
(20, 21, 'Kashish Birju Patel', 'kashish.patel@bmiit.edu.in', 1, '9825167332', ''),
(21, 22, 'Kathiriya Dhruvil Jitendrakumar', 'dhruvil.kathiriya@bmiit.edu.in', 1, '9825167334', ''),
(22, 23, 'Kher Ayushikumari Kishorsinh', 'ayushikumari.kher@bmiit.edu.in', 1, '9825167335', ''),
(23, 24, 'Solanki Dakshkumar Dipakbhai', 'dakshkumar.solanki@bmiit.edu.in', 1, '9825167336', ''),
(24, 25, 'Lad Tweeshaben Bhupendrabhai', 'tweeshaben.lad@bmiit.edu.in', 1, '9825167337', ''),
(25, 26, 'Maisuria Rudra Dharmeshkumar', 'rudra.maisuria@bmiit.edu.in', 1, '9825167338', ''),
(26, 27, 'Modi Vraj Jitendrakumar', 'vraj.modi@bmiit.edu.in', 1, '9825167340', ''),
(27, 28, 'Mohammed Ali Mo Sajid Safeda', 'mohammed.safeda@bmiit.edu.in', 1, '9825167341', ''),
(28, 29, 'Jadav Jenishkumar Sanjaybhai', 'jenishkumar.jadav@bmiit.edu.in', 1, '9825167344', ''),
(29, 30, 'Gandhi Viraj Prashantkumar', 'viraj.gandhi@bmiit.edu.in', 1, '9825167345', ''),
(30, 31, 'Gondaliya Jaimeenkumar Bhupatbhai', 'jaimeenkumar.gondaliya@bmiit.edu.in', 1, '9825167346', ''),
(31, 32, 'Desai Swayam Nirajkumar', 'swayam.desai@bmiit.edu.in', 1, '9825167347', ''),
(32, 33, 'Parmar Unnati Shaileshkumar', 'unnati.parmar@bmiit.edu.in', 1, '9825167354', ''),
(33, 34, 'Patadiya Tirth Chetankumar', 'tirth.patadiya@bmiit.edu.in', 1, '9825167355', ''),
(34, 35, 'Patel Chaitanya Jigneshbhai', 'chaitanya.patel@bmiit.edu.in', 1, '9825167356', ''),
(35, 36, 'Patel Deep Piyushkumar', 'deep.patel@bmiit.edu.in', 1, '9825167357', ''),
(36, 37, 'Patel Hetvi Dipakbhai', 'hetvi.patel@bmiit.edu.in', 1, '9825167360', ''),
(37, 38, 'Patel Jainish Jayeshbhai', 'jainish.patel@bmiit.edu.in', 1, '9825167361', ''),
(38, 39, 'Patel Kavya Dharmendrakumar', 'kavya.patel@bmiit.edu.in', 1, '9825167362', ''),
(39, 40, 'Patel Krish Dipakkumar', 'krish.patel@bmiit.edu.in', 1, '9825167363', ''),
(40, 41, 'Patel Raj Bharatbhai', 'raj.patel@bmiit.edu.in', 1, '9825167368', ''),
(41, 42, 'Patel Zeel Jitendrabhai', 'zeel.patel@bmiit.edu.in', 1, '9825167372', ''),
(42, 43, 'Patil Mahek Rajendrabhai', 'mahek.patil@bmiit.edu.in', 1, '9825167375', ''),
(43, 44, 'Pipaliya Hitax Arvindbhai', 'hitax.pipaliya@bmiit.edu.in', 1, '9825167377', ''),
(44, 45, 'Prajapati Bhumi Mukeshbhai', 'bhumi.prajapati@bmiit.edu.in', 1, '9825167379', ''),
(45, 46, 'Rana Chirag Ashokkumar', 'chirag.rana@bmiit.edu.in', 1, '9825167382', ''),
(46, 47, 'Ratnani Heer Prakashbhai', 'neelsalot.work@gmail.com', 1, '9825167384', ''),
(47, 48, 'Sakhiya Heer Ajaybhai', 'heer.sakhiya@bmiit.edu.in', 1, '9825167386', ''),
(48, 49, 'Salot Neel Chetanbhai', 'neelsalot0@gmail.com', 1, '9825167387', NULL),
(49, 50, 'Sarkhedi Smity Sunilbhai', 'smity.sarkhedi@bmiit.edu.in', 1, '9825167388', '12345678'),
(50, 51, 'Savaliya Khushal Jitendrabhai', 'khushal.savaliya@bmiit.edu.in', 2, '9825167389', ''),
(51, 52, 'Shaikh Mohammad Iqbal', 'mohammad.shaikh@bmiit.edu.in', 2, '9825167392', ''),
(52, 53, 'Solanki Jash Yogeshkumar', 'jash.solanki@bmiit.edu.in', 2, '9825167395', ''),
(53, 54, 'Tailor Krinaben Mehulbhai', 'krinaben.tailor@bmiit.edu.in', 2, '9825167396', ''),
(54, 55, 'Vasaniya Krishna Girishbhai', 'krishna.vasaniya@bmiit.edu.in', 2, '9825167397', ''),
(55, 56, 'Vasaniya Mahekben Sanjaybhai', 'mahekben.vasaniya@bmiit.edu.in', 2, '9825167398', ''),
(56, 57, 'Paghdal Kartik Nileshbhai', 'kartik.paghdal@bmiit.edu.in', 2, '9825167401', ''),
(57, 58, 'Sutariya Dhruvi Vijaybhai', 'dhruvi.sutariya@bmiit.edu.in', 2, '9825167403', ''),
(58, 59, 'Gohil Neel Dipakbhai', 'neel.gohil@bmiit.edu.in', 2, '9825167404', ''),
(59, 60, 'Sumra Hanifkhan Ramdinkhan', 'hanifkhan.sumra@bmiit.edu.in', 2, '9825167405', ''),
(60, 61, 'Patel Palak Dilipbhai', 'palak.patel@bmiit.edu.in', 2, '9825167408', ''),
(61, 62, 'Dankhara Vanshil Laxmanbhai', 'vanshil.dankhara@bmiit.edu.in', 2, '9825167412', ''),
(62, 63, 'Patel Dhruvkumar', 'dhruvkumar.patel@bmiit.edu.in', 2, '9825167414', ''),
(63, 64, 'Kothari Yugam Mayurkumar', 'yugam.kothari@bmiit.edu.in', 2, '9825167415', ''),
(64, 65, 'Umariya Harshil Ashokbhai', 'harshil.umariya@bmiit.edu.in', 2, '9825167416', ''),
(65, 66, 'Kachhadiya Harmish Rajeshbhai', 'harmish.kachhadiya@bmiit.edu.in', 2, '9825167420', ''),
(66, 67, 'Pavasiya Khushiben Pankajbhai', 'khushiben.pavasiya@bmiit.edu.in', 2, '9825167423', ''),
(67, 68, 'Lakhani Rushi Ashokbhai', 'rushi.lakhani@bmiit.edu.in', 2, '9825167424', ''),
(68, 69, 'Parekh Veer Mehulkumar', 'veer.parekh@bmiit.edu.in', 2, '9825167427', ''),
(69, 70, 'Patoliya Vrajesh Vallabhbhai', 'vrajesh.patoliya@bmiit.edu.in', 2, '9825167428', ''),
(70, 71, 'Parkhiya Meet Maheshbhai', 'meet.parkhiya@bmiit.edu.in', 2, '9825167429', ''),
(71, 72, 'Sheliya Vaibhav Ramnikbhai', 'vaibhav.sheliya@bmiit.edu.in', 2, '9825167434', ''),
(72, 73, 'Nakrani Meet Jagdishbhai', 'meet.nakrani@bmiit.edu.in', 2, '9825167439', ''),
(73, 74, 'Gadhiya Yashkumar Pravinbhai', 'yashkumar.gadhiya@bmiit.edu.in', 2, '9825167441', ''),
(74, 75, 'Lokesh Patil', 'lokesh.patil@bmiit.edu.in', 2, '9825167442', ''),
(75, 76, 'Patel Riyaben Kanubhai', 'riyaben.patel@bmiit.edu.in', 2, '9825167445', ''),
(76, 77, 'Modi Megh Darshan', 'megh.modi@bmiit.edu.in', 2, '9825167446', ''),
(77, 78, 'Jain Ayushkumar Mukeshbhai', 'ayushkumar.jain@bmiit.edu.in', 2, '9825167447', ''),
(78, 79, 'Ved Bankimbhai Paghadiwala', 'ved.paghadiwala@bmiit.edu.in', 2, '9825167449', ''),
(79, 80, 'Sabhadiya Anuj Vinaybhai', 'anuj.sabhadiya@bmiit.edu.in', 2, '9825167451', ''),
(80, 81, 'Dholakiya Khushaliben Kishorbhai', 'khushaliben.dholakiya@bmiit.edu.in', 2, '9825167452', ''),
(81, 82, 'Patel Jay Dharmeshkumar', 'jay.patel@bmiit.edu.in', 2, '9825167453', ''),
(82, 83, 'Kumbhani Anshkumar Prakashbhai', 'anshkumar.kumbhani@bmiit.edu.in', 2, '9825167455', ''),
(83, 84, 'Vaghasiya Jiya Dipakbhai', 'jiya.vaghasiya@bmiit.edu.in', 2, '9825167457', ''),
(84, 85, 'Narola Harsh Ketankumar', 'harsh.narola@bmiit.edu.in', 2, '9825167460', ''),
(85, 86, 'Sheliya Kevin Sanjaybhai', 'kevin.sheliya@bmiit.edu.in', 2, '9825167461', ''),
(86, 87, 'Desai Yasvi Nileshbhai', 'yasvi.desai@bmiit.edu.in', 2, '9825167464', ''),
(87, 88, 'Vadsak Yash Vipulbhai', 'yash.vadsak@bmiit.edu.in', 2, '9825167465', ''),
(88, 89, 'Panchal Jiya Rakeshkumar', 'neelsalot3@gmail.com', 2, '9825167466', ''),
(89, 90, 'Jisaheb Lisa Vipulkumar', '23bmiit087@gmail.com', 2, '9825167467', ''),
(90, 91, 'Lankapati Helly Birju', 'helly.lankapati@bmiit.edu.in', 2, '9825167471', ''),
(91, 92, 'Vaghamshi Darshakbhai Rameshbhai', 'darshakbhai.vaghamshi@bmiit.edu.in', 2, '9825167472', ''),
(92, 93, 'Patel Het Jitendrakumar', 'het.patel@bmiit.edu.in', 2, '9825167473', ''),
(93, 94, 'Kothiya Nirj Janakbhai', 'nirj.kothiya@bmiit.edu.in', 2, '9825167474', ''),
(94, 95, 'Paghadiwala Zeel Dipeshbhai', 'zeel.paghadiwala@bmiit.edu.in', 2, '9825167477', ''),
(95, 96, 'Savaliya Trishaben Harshadbhai', 'trishaben.savaliya@bmiit.edu.in', 2, '9825167479', ''),
(96, 97, 'Bhuva Harshil Rameshbhai', 'harshil.bhuva@bmiit.edu.in', 2, '9825167480', ''),
(97, 98, 'Beniwal Vivekkumar Satvirsingh', 'vivekkumar.beniwal@bmiit.edu.in', 2, '9825167485', ''),
(98, 99, 'Deshmukh Jaimil Umeshbhai', 'jaimil.deshmukh@bmiit.edu.in', 2, '9825167489', ''),
(99, 100, 'Kathrotiya Dhruviben Hareshbhai', 'dhruviben.kathrotiya@bmiit.edu.in', 2, '9825167491', ''),
(100, 101, 'Desai Dhruvilkumar Milansinh', 'dhruvilkumar.desai@bmiit.edu.in', 3, '9825167493', ''),
(101, 102, 'Desai Prachi Laljibhai', 'prachi.desai@bmiit.edu.in', 3, '9825167494', ''),
(102, 103, 'Dobariya Dhruvkumar Vipulbhai', 'dhruvkumar.dobariya@bmiit.edu.in', 3, '9825167495', ''),
(103, 104, 'Joshi Bhavin Dharmeshbhai', 'bhavin.joshi@bmiit.edu.in', 3, '9825167496', ''),
(104, 105, 'Kalkani Nirbhay Jaysukhbhai', 'nirbhay.kalkani@bmiit.edu.in', 3, '9825167497', ''),
(105, 106, 'Kalkani Yash Sureshbhai', 'yash.kalkani@bmiit.edu.in', 3, '9825167498', ''),
(106, 107, 'Mangukiya Hardiben Sanjaybhai', 'hardiben.mangukiya@bmiit.edu.in', 3, '9825167499', ''),
(107, 108, 'Devani Vins Pravinbhai', 'vins.devani@bmiit.edu.in', 3, '9825167500', ''),
(108, 109, 'Mori Henilkumar Jayendrasinh', 'henilkumar.mori@bmiit.edu.in', 3, '9825167501', ''),
(109, 110, 'Padsala Nutan Pravinbhai', 'nutan.padsala@bmiit.edu.in', 3, '9825167502', ''),
(110, 111, 'Paladiya Harsh Mukeshbhai', 'harsh.paladiya@bmiit.edu.in', 3, '9825167503', ''),
(111, 112, 'Panchal Smitkumar Ashokbhai', 'smitkumar.panchal@bmiit.edu.in', 3, '9825167504', ''),
(112, 113, 'Patel Harshkumar Anilkumar', 'harshkumar.patel@bmiit.edu.in', 3, '9825167505', ''),
(113, 114, 'Patel Krisha Maheshbhai', 'krisha.patel@bmiit.edu.in', 3, '9825167506', ''),
(114, 115, 'Patel Manan Manishkumar', 'manan.patel@bmiit.edu.in', 3, '9825167507', ''),
(115, 116, 'Patel Mannkumar Sunilbhai', 'mannkumar.patel@bmiit.edu.in', 3, '9825167508', ''),
(116, 117, 'Patel Prem Pareshbhai', 'prem.patel@bmiit.edu.in', 3, '9825167509', ''),
(117, 118, 'Patel Rajal Pralhad', 'rajal.patel@bmiit.edu.in', 3, '9825167510', ''),
(118, 119, 'Patel Shikha Dhansukhbhai', 'shikha.patel@bmiit.edu.in', 3, '9825167511', ''),
(119, 120, 'Patel Shrami Paragbhai', 'shrami.patel@bmiit.edu.in', 3, '9825167512', ''),
(120, 121, 'Patel Shreedhara Ganpatbhai', 'shreedhara.patel@bmiit.edu.in', 3, '9825167513', ''),
(121, 122, 'Patil Ashmita Bhatu', 'ashmita.patil@bmiit.edu.in', 3, '9825167514', ''),
(122, 123, 'Patolia Datt Pareshbhai', 'datt.patolia@bmiit.edu.in', 3, '9825167515', ''),
(123, 124, 'Purav Mehulbhai Patel', 'purav.patel@bmiit.edu.in', 3, '9825167516', ''),
(124, 125, 'Rathod Megh Pradipsinh', 'megh.rathod@bmiit.edu.in', 3, '9825167517', ''),
(125, 126, 'Shah Nevil Miteshbhai', 'nevil.shah@bmiit.edu.in', 3, '9825167518', ''),
(126, 127, 'Vekariya Jay Hareshbhai', 'jay.vekariya@bmiit.edu.in', 3, '9825167519', ''),
(127, 128, 'Sitapara Manthan Ashokbhai', 'manthan.sitapara@bmiit.edu.in', 3, '9825167520', ''),
(128, 129, 'Siddhi Pareshbhai Modi', 'siddhi.modi@bmiit.edu.in', 3, '9825167521', ''),
(129, 130, 'Mohali Rakeshbhai Radadiya', 'rakeshbhai.radadiya@bmiit.edu.in', 3, '9825167522', ''),
(130, 131, 'Gondaliya Vishal Rameshbhai', 'vishal.gondaliya@bmiit.edu.in', 3, '9825167523', ''),
(131, 132, 'Khadela Dharmil Rasikbhai', 'dharmil.khadela@bmiit.edu.in', 3, '9825167524', ''),
(132, 133, 'Basopiya Drashtikumari Jigneshbhai', 'drashtikumari.basopiya@bmiit.edu.in', 3, '9825167525', ''),
(133, 134, 'Manpara Bhavykumar Jayantibhai', 'bhavykumar.manpara@bmiit.edu.in', 3, '9825167528', ''),
(134, 135, 'Dhameliya Zarna Vijaykumar', 'zarna.dhameliya@bmiit.edu.in', 3, '9825167529', ''),
(135, 136, 'Aryan Animeshsinh Parmar', 'aryan.parmar@bmiit.edu.in', 3, '9825167530', ''),
(136, 137, 'Mistry Dhairya Shaileshbhai', 'dhairya.mistry@bmiit.edu.in', 3, '9825167531', ''),
(137, 138, 'Bavani Swyam Hasmukhbhai', 'swyam.bavani@bmiit.edu.in', 3, '9825167532', ''),
(138, 139, 'Sabhaya Trishaben Amulbhai', 'trishaben.sabhaya@bmiit.edu.in', 3, '9825167533', ''),
(139, 140, 'Khunt Mitul Lashkarbhai', 'mitul.khunt@bmiit.edu.in', 3, '9825167534', ''),
(140, 141, 'Jariwala Preet Vickykumar', 'preet.jariwala@bmiit.edu.in', 3, '9825167535', ''),
(141, 142, 'Dobariya Drashtiben Sanjaybhai', 'drashtiben.dobariya@bmiit.edu.in', 3, '9825167536', ''),
(142, 143, 'Patel Zalak Nareshbhai', 'zalak.patel@bmiit.edu.in', 3, '9825167537', ''),
(143, 144, 'Goyani Aastha Anilbhai', 'aastha.goyani@bmiit.edu.in', 3, '9825167538', ''),
(144, 145, 'Sarkhedi Het Piyushbhai', 'het.sarkhedi@bmiit.edu.in', 3, '9825167539', ''),
(145, 146, 'Lalwala Tanish Chetankumar', 'tanish.lalwala@bmiit.edu.in', 3, '9825167540', ''),
(146, 147, 'Ahir Rajkumar Yogeshbhai', 'rajkumar.ahir@bmiit.edu.in', 3, '9825167541', ''),
(147, 148, 'Poshiya Harshilkumar Jagdishbhai', 'harshilkumar.poshiya@bmiit.edu.in', 3, '9825167542', ''),
(148, 149, 'Harsora Krish Upendrabhai', 'krish.harsora@bmiit.edu.in', 3, '9825167543', ''),
(149, 150, 'Vaghani Vishv Kiritbhai', 'vishv.vaghani@bmiit.edu.in', 3, '9825167544', ''),
(150, 151, 'Sorathiya Feni Nareshbhai', 'feni.sorathiya@bmiit.edu.in', 3, '9825167545', ''),
(151, 152, 'Katudiya Mohit Bhikhabhai', 'mohit.katudiya@bmiit.edu.in', 3, '9825167546', ''),
(152, 153, 'Rakholiya Sneha Ghanshyambhai', 'sneha.rakholiya@bmiit.edu.in', 3, '9825167547', '');

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `before_student_insert` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
    DECLARE name_length INT;
    DECLARE counter INT;
    DECLARE current_char VARCHAR(1);
    DECLARE prev_char VARCHAR(1);
    DECLARE new_name VARCHAR(255);
    
    IF NEW.name IS NOT NULL THEN
        SET new_name = LOWER(TRIM(NEW.name));
        SET name_length = CHAR_LENGTH(new_name);
        SET counter = 1;
        SET prev_char = ' ';
        
        -- Capitalize first letter of each word
        WHILE counter <= name_length DO
            SET current_char = SUBSTRING(new_name, counter, 1);
            
            -- If previous character was a space, capitalize current character
            IF prev_char = ' ' THEN
                SET new_name = CONCAT(
                    SUBSTRING(new_name, 1, counter - 1),
                    UPPER(current_char),
                    SUBSTRING(new_name, counter + 1)
                );
            END IF;
            
            SET prev_char = current_char;
            SET counter = counter + 1;
        END WHILE;
        
        SET NEW.name = new_name;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_student_update` BEFORE UPDATE ON `students` FOR EACH ROW BEGIN
    DECLARE name_length INT;
    DECLARE counter INT;
    DECLARE current_char VARCHAR(1);
    DECLARE prev_char VARCHAR(1);
    DECLARE new_name VARCHAR(255);
    
    IF NEW.name IS NOT NULL THEN
        SET new_name = LOWER(TRIM(NEW.name));
        SET name_length = CHAR_LENGTH(new_name);
        SET counter = 1;
        SET prev_char = ' ';
        
        -- Capitalize first letter of each word
        WHILE counter <= name_length DO
            SET current_char = SUBSTRING(new_name, counter, 1);
            
            -- If previous character was a space, capitalize current character
            IF prev_char = ' ' THEN
                SET new_name = CONCAT(
                    SUBSTRING(new_name, 1, counter - 1),
                    UPPER(current_char),
                    SUBSTRING(new_name, counter + 1)
                );
            END IF;
            
            SET prev_char = current_char;
            SET counter = counter + 1;
        END WHILE;
        
        SET NEW.name = new_name;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(60) NOT NULL,
  `role` enum('admin','faculty','student') NOT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `first_login`, `is_active`) VALUES
(1, 'admin@gmail.com', '$2y$10$HTkzgpXQQlud1vnmxqpNgeGw3MKUm8obrz9eQHxlnSKlHWvMzPLbu', 'admin', 0, 1),
(2, '202307100110001', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(3, '202307100110002', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(4, '202307100110003', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(5, '202307100110005', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(6, '202307100110006', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(7, '202307100110008', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(8, '202307100110009', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(9, '202307100110010', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(10, '202307100110011', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(11, '202307100110012', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(12, '202307100110016', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(13, '202307100110017', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(14, '202307100110018', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(15, '202307100110019', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(16, '202307100110020', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(17, '202307100110022', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(18, '202307100110023', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(19, '202307100110025', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(20, '202307100110026', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(21, '202307100110032', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(22, '202307100110034', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(23, '202307100110035', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(24, '202307100110036', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(25, '202307100110037', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(26, '202307100110038', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(27, '202307100110040', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(28, '202307100110041', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(29, '202307100110044', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(30, '202307100110045', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(31, '202307100110046', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(32, '202307100110047', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(33, '202307100110054', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(34, '202307100110055', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(35, '202307100110056', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(36, '202307100110057', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(37, '202307100110060', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(38, '202307100110061', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(39, '202307100110062', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(40, '202307100110063', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(41, '202307100110068', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(42, '202307100110072', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(43, '202307100110075', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(44, '202307100110077', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(45, '202307100110079', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(46, '202307100110082', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(47, '202307100110084', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(48, '202307100110086', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(49, '202307100110087', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(50, '202307100110088', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(51, '202307100110089', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(52, '202307100110092', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(53, '202307100110095', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(54, '202307100110096', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(55, '202307100110097', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(56, '202307100110098', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(57, '202307100110101', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(58, '202307100110103', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(59, '202307100110104', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(60, '202307100110105', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(61, '202307100110108', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(62, '202307100110112', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(63, '202307100110114', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(64, '202307100110115', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(65, '202307100110116', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(66, '202307100110120', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(67, '202307100110123', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(68, '202307100110124', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(69, '202307100110127', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(70, '202307100110128', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(71, '202307100110129', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(72, '202307100110134', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(73, '202307100110139', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(74, '202307100110141', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(75, '202307100110142', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(76, '202307100110145', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(77, '202307100110146', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(78, '202307100110147', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(79, '202307100110149', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(80, '202307100110151', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(81, '202307100110152', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(82, '202307100110153', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(83, '202307100110155', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(84, '202307100110157', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(85, '202307100110160', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(86, '202307100110161', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(87, '202307100110164', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(88, '202307100110165', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(89, '202307100110166', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(90, '202307100110167', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(91, '202307100110171', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(92, '202307100110172', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(93, '202307100110173', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(94, '202307100110174', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(95, '202307100110177', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(96, '202307100110179', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(97, '202307100110180', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(98, '202307100110185', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(99, '202407100120001', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(100, '202407100120003', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(101, '202307100110014', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(102, '202307100110021', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(103, '202307100110024', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(104, '202307100110028', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(105, '202307100110030', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(106, '202307100110031', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(107, '202307100110039', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(108, '202307100110048', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(109, '202307100110049', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(110, '202307100110050', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(111, '202307100110051', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(112, '202307100110052', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(113, '202307100110059', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(114, '202307100110064', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(115, '202307100110065', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(116, '202307100110066', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(117, '202307100110067', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(118, '202307100110069', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(119, '202307100110070', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(120, '202307100110071', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(121, '202307100110073', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(122, '202307100110074', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(123, '202307100110076', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(124, '202307100110080', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(125, '202307100110083', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(126, '202307100110091', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(127, '202307100110099', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(128, '202307100110102', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(129, '202307100110106', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(130, '202307100110107', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(131, '202307100110111', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(132, '202307100110117', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(133, '202307100110118', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(134, '202307100110130', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(135, '202307100110131', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(136, '202307100110132', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(137, '202307100110133', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(138, '202307100110135', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(139, '202307100110136', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(140, '202307100110140', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(141, '202307100110143', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(142, '202307100110144', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(143, '202307100110148', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(144, '202307100110150', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(145, '202307100110156', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(146, '202307100110159', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(147, '202307100110162', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(148, '202307100110168', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(149, '202307100110169', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(150, '202307100110175', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(151, '202307100110176', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(152, '202307100110178', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(153, '202307100110187', '$2y$10$c7gwDqpmegR.Beek5BURj.SYNX86NoW5N6Ouh.LZRLlqB2wrh4ULm', 'student', 0, 1),
(154, 'rakesh.savant@gmail.com', '$2y$10$zWv.OSr8v.6EoQ27HM.hvOvrEHWHTblXddzvzYMCOVItP0i8eoU92', 'faculty', 1, 0),
(155, 'neel@gmail.com', '$2y$10$ojmyRUlryPdas4Oxj3/0H.3kZ52tyy4Jox32W4Udv7IIk2n7bkgM6', 'faculty', 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `leader_id` (`leader_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `group_invites`
--
ALTER TABLE `group_invites`
  ADD PRIMARY KEY (`invite_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semesters_ibfk_1` (`course_id`);

--
-- Indexes for table `semester_config`
--
ALTER TABLE `semester_config`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_config_ibfk_1` (`semester_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `students_ibfk_2` (`class_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_invites`
--
ALTER TABLE `group_invites`
  ADD CONSTRAINT `group_invites_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invites_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invites_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `semester_config`
--
ALTER TABLE `semester_config`
  ADD CONSTRAINT `semester_config_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
