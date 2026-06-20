-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2026 at 12:16 AM
-- Server version: 10.6.25-MariaDB-cll-lve-log
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ardhanub_amlodashboard`
--
CREATE DATABASE IF NOT EXISTS `ardhanub_amlodashboard` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ardhanub_amlodashboard`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `detail` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `detail`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-06 15:10:04'),
(2, 1, 'task_create', 'Create progress task ID 1', '127.0.0.1', '2026-06-06 15:10:16'),
(3, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-06 15:10:37'),
(4, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-06 15:10:50'),
(5, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-06 15:11:13'),
(6, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-06 15:12:58'),
(7, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-06 18:15:09'),
(8, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-06 18:15:23'),
(9, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 00:45:29'),
(10, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 00:52:51'),
(11, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 00:52:57'),
(12, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:04:40'),
(13, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 01:04:45'),
(14, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:19:31'),
(15, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 01:19:36'),
(16, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:19:43'),
(17, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 01:19:50'),
(18, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:27:21'),
(19, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 01:27:25'),
(20, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:27:49'),
(21, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 01:27:54'),
(22, 20, 'assignment_create', 'Create assignment for user 1', '127.0.0.1', '2026-06-07 01:55:35'),
(23, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 01:57:10'),
(24, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 01:57:14'),
(25, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:03:55'),
(26, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 02:04:01'),
(27, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:04:45'),
(28, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 02:04:49'),
(29, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:05:00'),
(30, 30, 'login', 'Login sebagai ho', '127.0.0.1', '2026-06-07 02:05:07'),
(31, 30, 'ho_feedback', 'HO feedback for user 1', '127.0.0.1', '2026-06-07 02:26:29'),
(32, 30, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:27:13'),
(33, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 02:27:19'),
(34, 1, 'task_update', 'Update progress task ID 12 to 100% for user 1', '127.0.0.1', '2026-06-07 02:27:32'),
(35, 1, 'task_update', 'Update progress task ID 12 to 10% for user 1', '127.0.0.1', '2026-06-07 02:27:42'),
(36, 1, 'task_create', 'Create progress task ID 2 for bulan 6 tahun 2026 for user 1', '127.0.0.1', '2026-06-07 02:28:08'),
(37, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:33:20'),
(38, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 02:33:26'),
(39, 20, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:34:19'),
(40, 1, 'login', 'Login sebagai officer', '127.0.0.1', '2026-06-07 02:34:26'),
(41, 1, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:40:05'),
(42, 30, 'login', 'Login sebagai ho', '127.0.0.1', '2026-06-07 02:40:11'),
(43, 30, 'logout', 'User logout', '127.0.0.1', '2026-06-07 02:40:17'),
(44, 20, 'login', 'Login sebagai lead', '127.0.0.1', '2026-06-07 02:40:22'),
(45, 20, 'login', 'Login sebagai lead', '43.251.99.158', '2026-06-07 03:40:00'),
(46, 20, 'logout', 'User logout', '43.251.99.158', '2026-06-07 03:40:08'),
(47, 1, 'login', 'Login sebagai officer', '43.251.99.158', '2026-06-07 03:43:52'),
(48, 1, 'logout', 'User logout', '43.251.99.158', '2026-06-07 03:43:54'),
(49, 20, 'login', 'Login sebagai lead', '43.251.99.158', '2026-06-07 03:44:02'),
(50, 20, 'logout', 'User logout', '43.251.99.158', '2026-06-07 03:44:04'),
(51, 30, 'login', 'Login sebagai ho', '43.251.99.158', '2026-06-07 03:44:14'),
(52, 30, 'logout', 'User logout', '43.251.99.158', '2026-06-07 03:44:34'),
(53, 1, 'login', 'Login sebagai officer', '43.251.99.158', '2026-06-07 03:46:41'),
(54, 1, 'logout', 'User logout', '43.251.99.158', '2026-06-07 03:46:44'),
(55, 20, 'login', 'Login sebagai lead', '43.251.99.158', '2026-06-07 04:40:29'),
(56, 20, 'logout', 'User logout', '43.251.99.158', '2026-06-07 04:41:17'),
(57, 1, 'login', 'Login sebagai officer', '43.251.99.158', '2026-06-07 04:41:21'),
(58, 1, 'login', 'Login sebagai officer', '43.251.99.158', '2026-06-07 06:42:13'),
(59, 1, 'submit_approval', 'Submit task progress ID 242 for approval', '43.251.99.158', '2026-06-07 06:42:23'),
(60, 1, 'login', 'Login sebagai officer', '43.251.99.158', '2026-06-07 06:58:17'),
(61, 1, 'logout', 'User logout', '43.251.99.158', '2026-06-07 07:20:42'),
(62, 1, 'login', 'Login sebagai officer', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:45:18'),
(63, 1, 'task_update', 'Update progress task ID 1 to 20% for user 1', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:45:45'),
(64, 1, 'logout', 'User logout', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:46:10'),
(65, 20, 'login', 'Login sebagai lead', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:46:28'),
(66, 20, 'logout', 'User logout', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:47:00'),
(67, 30, 'login', 'Login sebagai ho', '2404:8000:1005:f868:d9a0:2ec2:b76d:225a', '2026-06-07 08:47:05'),
(68, 20, 'login', 'Login sebagai lead', '139.0.8.92', '2026-06-08 02:04:38'),
(69, 1, 'login', 'Login sebagai officer', '139.0.8.92', '2026-06-08 04:39:07'),
(70, 30, 'login', 'Login sebagai ho', '36.91.218.241', '2026-06-08 06:08:52'),
(71, 1, 'login', 'Login sebagai officer', '36.91.218.241', '2026-06-08 06:10:07'),
(72, 20, 'login', 'Login sebagai lead', '36.91.218.241', '2026-06-08 07:30:13');

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `role_approver` enum('lead','ho') NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL COMMENT 'Lead/HO who created',
  `to_user_id` int(11) NOT NULL COMMENT 'Officer assigned',
  `task_name` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `dokumen_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('belum_mulai','in_progress','selesai') DEFAULT 'belum_mulai',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `from_user_id`, `to_user_id`, `task_name`, `deskripsi`, `due_date`, `dokumen_pendukung`, `status`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 'Adhoc EDD', 'tolong lakukan edd pada nasabah Michael Ledger', '2026-06-12', '', 'belum_mulai', '2026-06-07 01:55:35', '2026-06-07 01:55:35');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `task_progress_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `from_role` enum('officer','lead','ho') NOT NULL,
  `isi` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `task_progress_id`, `from_user_id`, `from_role`, `isi`, `created_at`) VALUES
(1, 241, 30, 'ho', '✅ Lengkap — Sesuai standar: kerja bagus ahmad nugroho', '2026-06-07 02:26:29');

-- --------------------------------------------------------

--
-- Table structure for table `kantor_wilayah`
--

CREATE TABLE `kantor_wilayah` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL COMMENT 'KW01, KW02, etc',
  `nama` varchar(100) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kantor_wilayah`
--

INSERT INTO `kantor_wilayah` (`id`, `kode`, `nama`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'RO01', 'Regional Office 01 - Medan', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(2, 'RO02', 'Regional Office 02 - Pekanbaru', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(3, 'RO03', 'Regional Office 03 - Padang', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(4, 'RO04', 'Regional Office 04 - Palembang', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(5, 'RO05', 'Regional Office 05 - Bandar Lampung', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(6, 'RO06', 'Regional Office 06 - Jakarta 1', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(7, 'RO07', 'Regional Office 07 - Jakarta 2', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(8, 'RO08', 'Regional Office 08 - Jakarta 3', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(9, 'RO09', 'Regional Office 09 - Bandung', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(10, 'RO10', 'Regional Office 10 - Semarang', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(11, 'RO11', 'Regional Office 11 - Yogyakarta', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(12, 'RO12', 'Regional Office 12 - Surabaya', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(13, 'RO13', 'Regional Office 13 - Malang', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(14, 'RO14', 'Regional Office 14 - Banjarmasin', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(15, 'RO15', 'Regional Office 15 - Makassar', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(16, 'RO16', 'Regional Office 16 - Manado', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(17, 'RO17', 'Regional Office 17 - Denpasar', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54'),
(18, 'RO18', 'Regional Office 18 - Jayapura', 1, '2026-05-29 08:30:54', '2026-05-29 08:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `task_progress_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `task_progress_id`, `submitted_by`, `status`, `submitted_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 242, 1, 'pending', '2026-06-07 06:42:23', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_progress`
--

CREATE TABLE `task_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `tahun` int(11) NOT NULL,
  `bulan` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0 COMMENT '0-100',
  `status` enum('pending','active','done','approved') DEFAULT 'pending',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_progress`
--

INSERT INTO `task_progress` (`id`, `user_id`, `template_id`, `periode`, `tahun`, `bulan`, `progress`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'harian', 2026, 5, 75, 'active', 'Target 300 | Realisasi 225 | Sisa 75', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(2, 1, 2, 'bulanan', 2026, 5, 60, 'active', 'Target 50 | Realisasi 30 | Sisa 20', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(3, 1, 3, 'bulanan', 2026, 5, 40, 'active', 'Laporan + attachment bukti lapor', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(4, 1, 4, 'harian', 2026, 5, 90, 'active', 'Target Harian: 45 | Realisasi: 40 | Target Bulanan: 900', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(5, 1, 5, 'bulanan', 2026, 5, 55, 'active', 'Target Mei: 1000 | Realisasi: 550', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(6, 1, 6, 'harian', 2026, 5, 85, 'active', 'Target Harian | Berapa PEP pending/selesai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(7, 1, 7, 'semesteran', 2026, 5, 30, 'active', 'List PN | Nama E-Learning | UKO | Status | Nilai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(8, 1, 8, 'semesteran', 2026, 5, 65, 'active', 'Online atau offline per UKO bina', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(9, 1, 9, 'triwulan', 2026, 5, 50, 'active', 'Monitoring RBA medium/high risk UKO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(10, 1, 10, 'adhoc', 2026, 5, 100, 'done', 'Format: CIF, Norek, Nama, Concern, Source, Due Date, UKO, RO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(11, 1, 11, 'triwulan', 2026, 5, 0, 'pending', 'Reminder: Laporan belum disubmit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(12, 1, 12, 'adhoc', 2026, 5, 20, 'active', 'Format: CIF, Norek, Nama, Concern, Source, Due Date, UKO, RO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(13, 1, 13, 'adhoc', 2026, 5, 100, 'done', 'Kegiatan: Kunjungan UKO | Dokumen pendukung terlampir', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(14, 2, 1, 'harian', 2026, 5, 60, 'active', 'Target 300 | Realisasi 180 | Sisa 120', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(15, 2, 2, 'bulanan', 2026, 5, 45, 'active', 'Target 50 | Realisasi 22 | Sisa 28', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(16, 2, 3, 'bulanan', 2026, 5, 30, 'active', 'Laporan + attachment', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(17, 2, 4, 'harian', 2026, 5, 70, 'active', 'Target Harian: 45 | Realisasi: 32 | Target Bulanan: 900', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(18, 2, 5, 'bulanan', 2026, 5, 40, 'active', 'Target Mei: 1000 | Realisasi: 400', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(19, 2, 6, 'harian', 2026, 5, 45, 'active', 'Target Harian | Berapa PEP pending/selesai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(20, 2, 7, 'semesteran', 2026, 5, 20, 'active', 'List PN | Nama E-Learning | UKO | Status | Nilai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(21, 2, 8, 'semesteran', 2026, 5, 50, 'active', 'Online atau offline per UKO bina', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(22, 2, 9, 'triwulan', 2026, 5, 35, 'active', 'Monitoring RBA medium/high risk UKO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(23, 2, 10, 'adhoc', 2026, 5, 100, 'done', 'Format: CIF, Norek, Nama, Concern, Source, Due Date, UKO, RO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(24, 2, 11, 'triwulan', 2026, 5, 0, 'pending', 'Reminder: Laporan belum disubmit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(25, 2, 12, 'adhoc', 2026, 5, 15, 'active', 'Format: CIF, Norek, Nama', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(26, 2, 13, 'adhoc', 2026, 5, 100, 'done', 'Kegiatan: Kunjungan UKO', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(27, 3, 1, 'harian', 2026, 5, 95, 'active', 'Target 300 | Realisasi 285 | Sisa 15', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(28, 3, 2, 'bulanan', 2026, 5, 100, 'done', 'Target 50 | Realisasi 50 - COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(29, 3, 3, 'bulanan', 2026, 5, 80, 'active', 'Laporan + attachment bukti lapor', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(30, 3, 4, 'harian', 2026, 5, 88, 'active', 'Target Harian: 45 | Realisasi: 40', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(31, 3, 5, 'bulanan', 2026, 5, 70, 'active', 'Target Mei: 1000 | Realisasi: 700', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(32, 3, 6, 'harian', 2026, 5, 92, 'active', 'PEP Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(33, 3, 7, 'semesteran', 2026, 5, 60, 'active', 'List PN | E-Learning progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(34, 3, 8, 'semesteran', 2026, 5, 75, 'active', 'Online atau offline per UKO bina', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(35, 3, 9, 'triwulan', 2026, 5, 65, 'active', 'Monitoring RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(36, 3, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(37, 3, 11, 'triwulan', 2026, 5, 40, 'active', 'Report Progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(38, 3, 12, 'adhoc', 2026, 5, 80, 'active', 'Adhoc EDD', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(39, 3, 13, 'adhoc', 2026, 5, 100, 'done', 'Pendampingan AML COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(40, 4, 1, 'harian', 2026, 5, 40, 'active', 'Target 300 | Realisasi 120 | Sisa 180', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(41, 4, 2, 'bulanan', 2026, 5, 25, 'active', 'Target 50 | Realisasi 12', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(42, 4, 3, 'bulanan', 2026, 5, 15, 'active', 'Laporan belum lengkap', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(43, 4, 4, 'harian', 2026, 5, 50, 'active', 'Target Harian: 45 | Realisasi: 22', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(44, 4, 5, 'bulanan', 2026, 5, 20, 'active', 'Target Mei: 1000 | Realisasi: 200', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(45, 4, 6, 'harian', 2026, 5, 35, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(46, 4, 7, 'semesteran', 2026, 5, 10, 'active', 'E-Learning baru dimulai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(47, 4, 8, 'semesteran', 2026, 5, 30, 'active', 'Sosialisasi belum dilakukan', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(48, 4, 9, 'triwulan', 2026, 5, 20, 'active', 'RBA Monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(49, 4, 10, 'adhoc', 2026, 5, 50, 'active', 'RFI Remittance', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(50, 4, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum disubmit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(51, 4, 12, 'adhoc', 2026, 5, 0, 'pending', 'Adhoc EDD', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(52, 4, 13, 'adhoc', 2026, 5, 30, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(53, 5, 1, 'harian', 2026, 5, 80, 'active', 'Target 300 | Realisasi 240', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(54, 5, 2, 'bulanan', 2026, 5, 65, 'active', 'Target 50 | Realisasi 32', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(55, 5, 3, 'bulanan', 2026, 5, 55, 'active', 'Laporan + attachment', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(56, 5, 4, 'harian', 2026, 5, 75, 'active', 'Target Harian: 45 | Realisasi: 34', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(57, 5, 5, 'bulanan', 2026, 5, 60, 'active', 'Target Mei: 1000 | Realisasi: 600', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(58, 5, 6, 'harian', 2026, 5, 78, 'active', 'PEP Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(59, 5, 7, 'semesteran', 2026, 5, 40, 'active', 'E-Learning progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(60, 5, 8, 'semesteran', 2026, 5, 55, 'active', 'Sosialisasi', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(61, 5, 9, 'triwulan', 2026, 5, 45, 'active', 'RBA Monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(62, 5, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(63, 5, 11, 'triwulan', 2026, 5, 30, 'active', 'Report Progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(64, 5, 12, 'adhoc', 2026, 5, 60, 'active', 'Adhoc EDD', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(65, 5, 13, 'adhoc', 2026, 5, 90, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(66, 6, 1, 'harian', 2026, 5, 100, 'done', 'Target 300 | Realisasi 300 - EXCEED', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(67, 6, 2, 'bulanan', 2026, 5, 88, 'active', 'Target 50 | Realisasi 44', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(68, 6, 3, 'bulanan', 2026, 5, 90, 'active', 'Laporan lengkap', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(69, 6, 4, 'harian', 2026, 5, 95, 'active', 'Target Harian: 45 | Realisasi: 43', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(70, 6, 5, 'bulanan', 2026, 5, 85, 'active', 'Target Mei: 1000 | Realisasi: 850', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(71, 6, 6, 'harian', 2026, 5, 100, 'done', 'PEP Target - EXCEED', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(72, 6, 7, 'semesteran', 2026, 5, 70, 'active', 'E-Learning progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(73, 6, 8, 'semesteran', 2026, 5, 80, 'active', 'Sosialisasi', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(74, 6, 9, 'triwulan', 2026, 5, 75, 'active', 'RBA Monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(75, 6, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(76, 6, 11, 'triwulan', 2026, 5, 60, 'active', 'Report Progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(77, 6, 12, 'adhoc', 2026, 5, 100, 'done', 'Adhoc EDD - EXCEED', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(78, 6, 13, 'adhoc', 2026, 5, 100, 'done', 'Pendampingan AML - COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(79, 7, 1, 'harian', 2026, 5, 70, 'active', 'Target 300 | Realisasi 210', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(80, 7, 2, 'bulanan', 2026, 5, 50, 'active', 'Target 50 | Realisasi 25', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(81, 7, 3, 'bulanan', 2026, 5, 45, 'active', 'Laporan', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(82, 7, 4, 'harian', 2026, 5, 65, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(83, 7, 5, 'bulanan', 2026, 5, 50, 'active', 'Target Mei: 500 | Realisasi: 250', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(84, 7, 6, 'harian', 2026, 5, 68, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(85, 7, 7, 'semesteran', 2026, 5, 35, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(86, 7, 8, 'semesteran', 2026, 5, 50, 'active', 'Sosialisasi', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(87, 7, 9, 'triwulan', 2026, 5, 40, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(88, 7, 10, 'adhoc', 2026, 5, 80, 'active', 'RFI Remittance', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(89, 7, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(90, 7, 12, 'adhoc', 2026, 5, 40, 'active', 'Adhoc EDD', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(91, 7, 13, 'adhoc', 2026, 5, 70, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(92, 8, 1, 'harian', 2026, 5, 82, 'active', 'Target 300 | Realisasi 246', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(93, 8, 2, 'bulanan', 2026, 5, 72, 'active', 'Target 50 | Realisasi 36', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(94, 8, 4, 'harian', 2026, 5, 78, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(95, 8, 6, 'harian', 2026, 5, 80, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(96, 8, 7, 'semesteran', 2026, 5, 50, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(97, 8, 9, 'triwulan', 2026, 5, 55, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(98, 8, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(99, 8, 11, 'triwulan', 2026, 5, 40, 'active', 'Report Progress', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(100, 8, 13, 'adhoc', 2026, 5, 100, 'done', 'Pendampingan AML COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(101, 9, 1, 'harian', 2026, 5, 55, 'active', 'Target 300 | Realisasi 165', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(102, 9, 2, 'bulanan', 2026, 5, 40, 'active', 'Target 50 | Realisasi 20', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(103, 9, 4, 'harian', 2026, 5, 60, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(104, 9, 6, 'harian', 2026, 5, 50, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(105, 9, 7, 'semesteran', 2026, 5, 20, 'active', 'E-Learning baru dimulai', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(106, 9, 9, 'triwulan', 2026, 5, 30, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(107, 9, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(108, 9, 13, 'adhoc', 2026, 5, 50, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(109, 10, 1, 'harian', 2026, 5, 65, 'active', 'Target 300 | Realisasi 195', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(110, 10, 2, 'bulanan', 2026, 5, 55, 'active', 'Target 50 | Realisasi 27', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(111, 10, 4, 'harian', 2026, 5, 70, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(112, 10, 6, 'harian', 2026, 5, 65, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(113, 10, 7, 'semesteran', 2026, 5, 45, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(114, 10, 9, 'triwulan', 2026, 5, 50, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(115, 10, 10, 'adhoc', 2026, 5, 90, 'active', 'RFI Remittance', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(116, 10, 13, 'adhoc', 2026, 5, 80, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(117, 11, 1, 'harian', 2026, 5, 90, 'active', 'Target 300 | Realisasi 270', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(118, 11, 2, 'bulanan', 2026, 5, 80, 'active', 'Target 50 | Realisasi 40', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(119, 11, 4, 'harian', 2026, 5, 85, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(120, 11, 6, 'harian', 2026, 5, 88, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(121, 11, 7, 'semesteran', 2026, 5, 65, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(122, 11, 9, 'triwulan', 2026, 5, 70, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(123, 11, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(124, 11, 13, 'adhoc', 2026, 5, 100, 'done', 'Pendampingan AML COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(125, 12, 1, 'harian', 2026, 5, 45, 'active', 'Target 300 | Realisasi 135', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(126, 12, 2, 'bulanan', 2026, 5, 30, 'active', 'Target 50 | Realisasi 15', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(127, 12, 4, 'harian', 2026, 5, 40, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(128, 12, 6, 'harian', 2026, 5, 35, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(129, 12, 7, 'semesteran', 2026, 5, 15, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(130, 12, 9, 'triwulan', 2026, 5, 25, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(131, 12, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(132, 12, 13, 'adhoc', 2026, 5, 20, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(133, 13, 1, 'harian', 2026, 5, 78, 'active', 'Target 300 | Realisasi 234', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(134, 13, 2, 'bulanan', 2026, 5, 68, 'active', 'Target 50 | Realisasi 34', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(135, 13, 4, 'harian', 2026, 5, 75, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(136, 13, 6, 'harian', 2026, 5, 76, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(137, 13, 7, 'semesteran', 2026, 5, 55, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(138, 13, 9, 'triwulan', 2026, 5, 60, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(139, 13, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(140, 13, 13, 'adhoc', 2026, 5, 90, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(141, 14, 1, 'harian', 2026, 5, 62, 'active', 'Target 300 | Realisasi 186', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(142, 14, 2, 'bulanan', 2026, 5, 48, 'active', 'Target 50 | Realisasi 24', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(143, 14, 4, 'harian', 2026, 5, 58, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(144, 14, 6, 'harian', 2026, 5, 60, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(145, 14, 7, 'semesteran', 2026, 5, 30, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(146, 14, 9, 'triwulan', 2026, 5, 40, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(147, 14, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(148, 14, 13, 'adhoc', 2026, 5, 60, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(149, 15, 1, 'harian', 2026, 5, 88, 'active', 'Target 300 | Realisasi 264', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(150, 15, 2, 'bulanan', 2026, 5, 78, 'active', 'Target 50 | Realisasi 39', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(151, 15, 4, 'harian', 2026, 5, 85, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(152, 15, 6, 'harian', 2026, 5, 86, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(153, 15, 7, 'semesteran', 2026, 5, 60, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(154, 15, 9, 'triwulan', 2026, 5, 65, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(155, 15, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(156, 15, 13, 'adhoc', 2026, 5, 95, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(157, 16, 1, 'harian', 2026, 5, 52, 'active', 'Target 300 | Realisasi 156', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(158, 16, 2, 'bulanan', 2026, 5, 38, 'active', 'Target 50 | Realisasi 19', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(159, 16, 4, 'harian', 2026, 5, 50, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(160, 16, 6, 'harian', 2026, 5, 48, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(161, 16, 7, 'semesteran', 2026, 5, 25, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(162, 16, 9, 'triwulan', 2026, 5, 35, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(163, 16, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(164, 16, 13, 'adhoc', 2026, 5, 45, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(165, 17, 1, 'harian', 2026, 5, 72, 'active', 'Target 300 | Realisasi 216', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(166, 17, 2, 'bulanan', 2026, 5, 62, 'active', 'Target 50 | Realisasi 31', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(167, 17, 4, 'harian', 2026, 5, 70, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(168, 17, 6, 'harian', 2026, 5, 72, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(169, 17, 7, 'semesteran', 2026, 5, 48, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(170, 17, 9, 'triwulan', 2026, 5, 55, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(171, 17, 10, 'adhoc', 2026, 5, 85, 'active', 'RFI Remittance', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(172, 17, 13, 'adhoc', 2026, 5, 75, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(173, 18, 1, 'harian', 2026, 5, 80, 'active', 'Target 300 | Realisasi 240', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(174, 18, 2, 'bulanan', 2026, 5, 70, 'active', 'Target 50 | Realisasi 35', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(175, 18, 4, 'harian', 2026, 5, 78, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(176, 18, 6, 'harian', 2026, 5, 80, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(177, 18, 7, 'semesteran', 2026, 5, 55, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(178, 18, 9, 'triwulan', 2026, 5, 60, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(179, 18, 10, 'adhoc', 2026, 5, 100, 'done', 'RFI Remittance COMPLETE', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(180, 18, 13, 'adhoc', 2026, 5, 85, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(181, 19, 1, 'harian', 2026, 5, 58, 'active', 'Target 300 | Realisasi 174', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(182, 19, 2, 'bulanan', 2026, 5, 42, 'active', 'Target 50 | Realisasi 21', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(183, 19, 4, 'harian', 2026, 5, 55, 'active', 'Target Harian', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(184, 19, 6, 'harian', 2026, 5, 56, 'active', 'PEP Target', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(185, 19, 7, 'semesteran', 2026, 5, 28, 'active', 'E-Learning', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(186, 19, 9, 'triwulan', 2026, 5, 38, 'active', 'RBA', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(187, 19, 11, 'triwulan', 2026, 5, 0, 'pending', 'Report belum submit', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(188, 19, 13, 'adhoc', 2026, 5, 55, 'active', 'Pendampingan AML', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(189, 20, 1, 'harian', 2026, 5, 85, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(190, 20, 4, 'harian', 2026, 5, 80, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(191, 20, 9, 'triwulan', 2026, 5, 70, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(192, 20, 11, 'triwulan', 2026, 5, 50, 'active', 'Report review', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(193, 21, 1, 'harian', 2026, 5, 75, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(194, 21, 4, 'harian', 2026, 5, 70, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(195, 21, 9, 'triwulan', 2026, 5, 60, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(196, 22, 1, 'harian', 2026, 5, 65, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(197, 22, 4, 'harian', 2026, 5, 60, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(198, 22, 9, 'triwulan', 2026, 5, 50, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(199, 23, 1, 'harian', 2026, 5, 70, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(200, 23, 4, 'harian', 2026, 5, 65, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(201, 23, 9, 'triwulan', 2026, 5, 55, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(202, 24, 1, 'harian', 2026, 5, 80, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(203, 24, 4, 'harian', 2026, 5, 75, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(204, 24, 9, 'triwulan', 2026, 5, 65, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(205, 25, 1, 'harian', 2026, 5, 88, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(206, 25, 4, 'harian', 2026, 5, 85, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(207, 25, 9, 'triwulan', 2026, 5, 75, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(208, 26, 1, 'harian', 2026, 5, 50, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(209, 26, 4, 'harian', 2026, 5, 45, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(210, 26, 9, 'triwulan', 2026, 5, 35, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(211, 27, 1, 'harian', 2026, 5, 78, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(212, 27, 4, 'harian', 2026, 5, 72, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(213, 27, 9, 'triwulan', 2026, 5, 62, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(214, 28, 1, 'harian', 2026, 5, 90, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(215, 28, 4, 'harian', 2026, 5, 88, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(216, 28, 9, 'triwulan', 2026, 5, 78, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(217, 29, 1, 'harian', 2026, 5, 68, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(218, 29, 4, 'harian', 2026, 5, 62, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(219, 29, 9, 'triwulan', 2026, 5, 52, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(220, 30, 1, 'harian', 2026, 5, 85, 'active', 'Nasional STR overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(221, 30, 9, 'triwulan', 2026, 5, 75, 'active', 'Nasional RBA overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(222, 31, 1, 'harian', 2026, 5, 80, 'active', 'Nasional STR overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(223, 31, 9, 'triwulan', 2026, 5, 72, 'active', 'Nasional RBA overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(224, 32, 1, 'harian', 2026, 5, 78, 'active', 'Nasional STR overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(225, 32, 9, 'triwulan', 2026, 5, 70, 'active', 'Nasional RBA overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(226, 33, 1, 'harian', 2026, 5, 82, 'active', 'Nasional STR overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(227, 33, 9, 'triwulan', 2026, 5, 74, 'active', 'Nasional RBA overview', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(228, 34, 1, 'harian', 2026, 5, 65, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(229, 34, 4, 'harian', 2026, 5, 60, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(230, 34, 9, 'triwulan', 2026, 5, 50, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(231, 35, 1, 'harian', 2026, 5, 75, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(232, 35, 4, 'harian', 2026, 5, 70, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(233, 35, 9, 'triwulan', 2026, 5, 62, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(234, 36, 1, 'harian', 2026, 5, 80, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(235, 36, 4, 'harian', 2026, 5, 78, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(236, 36, 9, 'triwulan', 2026, 5, 68, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(237, 37, 1, 'harian', 2026, 5, 70, 'active', 'Monitoring daily STR alerts', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(238, 37, 4, 'harian', 2026, 5, 65, 'active', 'Bad Data monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(239, 37, 9, 'triwulan', 2026, 5, 55, 'active', 'RBA TL monitoring', '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(240, 1, 1, 'harian', 2026, 6, 20, 'active', '', '2026-06-06 15:10:16', '2026-06-07 08:45:45'),
(241, 1, 12, 'adhoc', 2026, 6, 10, 'active', 'Due Date: 12 Jun 2026\r\n\r\nInstruksi Lead:\r\ntolong lakukan edd pada nasabah Michael Ledger', '2026-06-07 01:55:35', '2026-06-07 02:27:42'),
(242, 1, 2, 'bulanan', 2026, 6, 100, 'done', '', '2026-06-07 02:28:08', '2026-06-07 02:28:08');

-- --------------------------------------------------------

--
-- Table structure for table `task_templates`
--

CREATE TABLE `task_templates` (
  `id` int(11) NOT NULL,
  `nama` varchar(200) NOT NULL,
  `kategori` varchar(5) NOT NULL COMMENT 'A, B, C, etc',
  `periode` enum('harian','bulanan','triwulan','semesteran','adhoc') NOT NULL,
  `tag` varchar(20) NOT NULL,
  `target` text DEFAULT NULL,
  `due_label` varchar(200) DEFAULT NULL,
  `source_link` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_templates`
--

INSERT INTO `task_templates` (`id`, `nama`, `kategori`, `periode`, `tag`, `target`, `due_label`, `source_link`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Alert STR Daily', 'A', 'harian', 'harian', 'Jumlah alert sesuai target UKO', 'H+3 sejak alert muncul', 'Link Alert STR', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(2, 'Alert STR Monthly', 'A', 'bulanan', 'bulanan', 'Target 50 alert bulanan', 'H+1 bulan sejak munculnya alert', 'Link Alert STR', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(3, 'STR Proaktif', 'A', 'bulanan', 'bulanan', 'Jumlah laporan STR proaktif', 'Cut off akhir bulan', 'Inputan AMLO', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(4, 'Bad Data BO (Harian & Bulanan)', 'J', 'harian', 'harian', 'Sesuai data report BO', 'Dibagi sistem (target harian)', 'Report Bad Data BO', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(5, 'Bad Data AML CTR IFTI', 'J', 'bulanan', 'bulanan', 'Sesuai data target + report', 'H+3 running ulang cek update', 'Upload/report bad data CTR IFTI', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(6, 'PEP Target', 'K', 'harian', 'harian', 'H+5 hari kerja sejak pemadanan PEP', 'H+5 hari kerja', 'Data PEP yang belum/sudah TL', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(7, 'E-Learning Target', 'H', 'semesteran', 'semesteran', 'Seluruh pekerja TL sesuai target waktu', 'Tgl 10 April & Oktober', 'Input PSA + Progress E-Learning', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(8, 'Sosialisasi AML CFT CPF', 'H', 'semesteran', 'semesteran', 'Min 1x sosialisasi per UKO bina', 'Tgl 10 April & Oktober', 'Inputan AMLO', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(9, 'Tindak Lanjut RBA Bankwide', 'C', 'triwulan', 'triwulan', 'Action plan sesuai ketentuan', 'Per tenggat aksi', 'Enterprise Risk', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(10, 'RFI Remittance', 'G', 'adhoc', 'adhoc', 'H+5 sejak tanggal input', 'H+5 sejak input', 'List request dari Kanpus', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(11, 'Report Progress AML CFT CPF', 'H', 'triwulan', 'triwulan', 'Laporan + attach', 'Tgl 10 Apr, Jul, Okt, Jan', 'Inputan', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(12, 'Adhoc EDD', 'G', 'adhoc', 'adhoc', 'H+5 sejak tanggal input', 'H+5 sejak input', 'List request dari Kanpus', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44'),
(13, 'Pendampingan AML', 'B', 'adhoc', 'adhoc', 'H-1 sebelum tanggal kunjungan', 'H-1 sebelum kunjungan', 'List request Kanpus', 1, '2026-06-06 15:06:44', '2026-06-06 15:06:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('officer','lead','ho') NOT NULL,
  `kanwil_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `nama`, `role`, `kanwil_id`, `email`, `aktif`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'a.nugroho', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Ahmad Nugroho', 'officer', 3, 'a.nugroho@amlodashboard.com', 1, '2026-06-08 13:10:07', '2026-06-06 15:06:44', '2026-06-08 06:10:07'),
(2, 'b.santoso', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Budi Santoso', 'officer', 1, 'b.santoso@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(3, 'c.dewi', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Citra Dewi', 'officer', 2, 'c.dewi@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(4, 'd.pratama', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Dian Pratama', 'officer', 4, 'd.pratama@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(5, 'e.fitriani', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Nur Fitriyani', 'officer', 5, 'e.fitriani@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(6, 'f.rahman', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Fajar Rahman', 'officer', 6, 'f.rahman@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(7, 'g.hidayat', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Gunawan Hidayat', 'officer', 9, 'g.hidayat@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(8, 'i.wulandari', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Indah Wulandari', 'officer', 9, 'i.wulandari@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(9, 'j.kurniawan', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Joko Kurniawan', 'officer', 10, 'j.kurniawan@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(10, 'k.rahayu', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Kartika Rahayu', 'officer', 11, 'k.rahayu@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(11, 'l.susilo', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Lina Susilowati', 'officer', 12, 'l.susilo@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(12, 'm.hermawan', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'M Adi Hermawan', 'officer', 13, 'm.hermawan@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(13, 'n.fauziah', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Nadya Fauziah', 'officer', 14, 'n.fauziah@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(14, 'o.aprianto', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Oki Aprinaldo', 'officer', 14, 'o.aprianto@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(15, 'p.setiawan', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Putu Setiawan', 'officer', 15, 'p.setiawan@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(16, 'q.iskandar', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Qori Iskandar', 'officer', 16, 'q.iskandar@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(17, 'r.novita', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Rika Novita Sari', 'officer', 17, 'r.novita@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(18, 's.habibi', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Sony Habibi', 'officer', 18, 's.habibi@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(19, 't.kuswandari', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Tia Kuswandari', 'officer', 9, 't.kuswandari@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(20, 'r.sari', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Ratna Sari, SE.', 'lead', 3, 'r.sari@amlodashboard.com', 1, '2026-06-08 14:30:13', '2026-06-06 15:06:44', '2026-06-08 07:30:13'),
(21, 'h.purnomo', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Hendra Purnomo', 'lead', 1, 'h.purnomo@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(22, 'i.wahyudi', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Ikin Wahyudi', 'lead', 9, 'i.wahyudi@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(23, 'j.hakim', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Jajang Hakim', 'lead', 10, 'j.hakim@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(24, 'k.wibowo', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Kukuh Wibowo', 'lead', 11, 'k.wibowo@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(25, 'l.ardiansyah', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Lutvi Ardiansyah', 'lead', 12, 'l.ardiansyah@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(26, 'm.zainuri', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Mirza Zainuri', 'lead', 13, 'm.zainuri@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(27, 'n.suryani', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Nesty Suryani', 'lead', 14, 'n.suryani@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(28, 'o.hidayatullah', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Omar Hidayatullah', 'lead', 15, 'o.hidayatullah@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(29, 'p.sutanto', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Panji Sutanto', 'lead', 16, 'p.sutanto@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(30, 'h.wijaya', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Dr. Hendra Wijaya', 'ho', 3, 'h.wijaya@amlodashboard.com', 1, '2026-06-08 13:08:52', '2026-06-06 15:06:44', '2026-06-08 06:08:52'),
(31, 'a.kusuma', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Ahmad Kusuma', 'ho', 1, 'a.kusuma@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(32, 'b.susanto', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Bobby Susanto', 'ho', 1, 'b.susanto@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(33, 'c.hartono', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Candra Hartono', 'ho', 1, 'c.hartono@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(34, 'f.hariyanto', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Farah Hariyanto', 'lead', 7, 'f.hariyanto@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(35, 's.wahyuni', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Sulistyowati', 'lead', 17, 's.wahyuni@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(36, 't.budianto', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Taufik Budianto', 'lead', 18, 't.budianto@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(37, 'u.arsyad', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Usman Arsyad', 'lead', 9, 'u.arsyad@amlodashboard.com', 1, NULL, '2026-06-06 15:06:44', '2026-06-07 07:19:21'),
(38, 'e.ferdiansyah', '$2y$12$O2Bp0IzZnEz.9.WELhRcNOcKxUbASj2wOjUnLVKFV0b3oP3CM7BmO', 'Eka Ferdiansyah', 'officer', 3, 'e.ferdiansyah@amlodashboard.com', 1, NULL, '2026-06-07 01:10:37', '2026-06-07 07:19:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_progress_id` (`task_progress_id`),
  ADD KEY `from_user_id` (`from_user_id`);

--
-- Indexes for table `kantor_wilayah`
--
ALTER TABLE `kantor_wilayah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_progress_id` (`task_progress_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `task_progress`
--
ALTER TABLE `task_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_task` (`user_id`,`template_id`,`tahun`,`bulan`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `task_templates`
--
ALTER TABLE `task_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `kanwil_id` (`kanwil_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kantor_wilayah`
--
ALTER TABLE `kantor_wilayah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_progress`
--
ALTER TABLE `task_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT for table `task_templates`
--
ALTER TABLE `task_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`),
  ADD CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`task_progress_id`) REFERENCES `task_progress` (`id`),
  ADD CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`task_progress_id`) REFERENCES `task_progress` (`id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_progress`
--
ALTER TABLE `task_progress`
  ADD CONSTRAINT `task_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_progress_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `task_templates` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`kanwil_id`) REFERENCES `kantor_wilayah` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
