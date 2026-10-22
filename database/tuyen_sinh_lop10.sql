-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 22, 2026 lúc 04:30 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `tuyen_sinh_lop10`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `adjustment_requests`
--

CREATE TABLE `adjustment_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `submitted_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`new_data`)),
  `reason` text NOT NULL,
  `evidence_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `review_reason` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_bonus_reviews`
--

CREATE TABLE `admission_bonus_reviews` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `priority_group` varchar(30) NOT NULL,
  `award_group` varchar(30) NOT NULL,
  `evidence` text NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED NOT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_periods`
--

CREATE TABLE `admission_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `admission_method` varchar(100) DEFAULT NULL,
  `plan_content` text DEFAULT NULL,
  `registration_start` datetime DEFAULT NULL,
  `registration_end` datetime DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `enrollment_start` datetime DEFAULT NULL,
  `enrollment_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admission_periods`
--

INSERT INTO `admission_periods` (`id`, `name`, `academic_year`, `admission_method`, `plan_content`, `registration_start`, `registration_end`, `status`, `created_at`, `updated_at`, `enrollment_start`, `enrollment_end`) VALUES
(375, '[MÔ PHỎNG] Tuyển sinh lớp 10 thường TP.HCM 2026-2027', '2026-2027', 'Thi tuyển lớp 10 thường', 'Bản nháp phục vụ dự án với 720 học sinh giả lập. Thời hạn và chỉ tiêu chưa thiết lập; không phải kỳ tuyển sinh chính thức.', NULL, NULL, 'draft', '2026-10-20 23:54:10', '2026-10-20 23:54:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_period_configs`
--

CREATE TABLE `admission_period_configs` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `settings` longtext NOT NULL,
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admission_period_configs`
--

INSERT INTO `admission_period_configs` (`admission_period_id`, `settings`, `revision`, `updated_by`, `updated_at`) VALUES
(375, '{\"name\":\"[MÔ PHỎNG] Tuyển sinh lớp 10 thường TP.HCM 2026-2027\",\"academic_year\":\"2026-2027\",\"plan_content\":\"Bản nháp phục vụ dự án với 720 học sinh giả lập. Thời hạn và chỉ tiêu chưa thiết lập; không phải kỳ tuyển sinh chính thức.\",\"mode\":\"simulation\",\"source_url\":\"https:\\/\\/storage-edu.vnpt.vn\\/edu-vtu\\/503\\/2025\\/2026\\/TUYENSINH2026\\/2027\\/1700-Ke_hoach_tuyen_sinh_ban_hanh.pdf\",\"schedule_source_url\":\"\",\"quota_source_url\":\"\",\"appeal_source_url\":\"\",\"enrollment_source_url\":\"\",\"registration_start\":null,\"registration_end\":null,\"adjustment_start\":null,\"adjustment_end\":null,\"appeal_start\":null,\"appeal_end\":null,\"enrollment_start\":null,\"enrollment_end\":null,\"policy_id\":\"hcm_regular_2026_v1\",\"reviewed\":false}', 1, 2, '2026-10-20 23:54:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_period_events`
--

CREATE TABLE `admission_period_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `detail` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admission_period_events`
--

INSERT INTO `admission_period_events` (`id`, `admission_period_id`, `actor_user_id`, `action`, `detail`, `created_at`) VALUES
(1, 375, 2, 'save_draft', '{\"settings\":{\"name\":\"[MÔ PHỎNG] Tuyển sinh lớp 10 thường TP.HCM 2026-2027\",\"academic_year\":\"2026-2027\",\"plan_content\":\"Bản nháp phục vụ dự án với 720 học sinh giả lập. Thời hạn và chỉ tiêu chưa thiết lập; không phải kỳ tuyển sinh chính thức.\",\"mode\":\"simulation\",\"source_url\":\"https:\\/\\/storage-edu.vnpt.vn\\/edu-vtu\\/503\\/2025\\/2026\\/TUYENSINH2026\\/2027\\/1700-Ke_hoach_tuyen_sinh_ban_hanh.pdf\",\"schedule_source_url\":\"\",\"quota_source_url\":\"\",\"appeal_source_url\":\"\",\"enrollment_source_url\":\"\",\"registration_start\":null,\"registration_end\":null,\"adjustment_start\":null,\"adjustment_end\":null,\"appeal_start\":null,\"appeal_end\":null,\"enrollment_start\":null,\"enrollment_end\":null,\"policy_id\":\"hcm_regular_2026_v1\",\"reviewed\":false}}', '2026-10-20 23:54:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_quotas`
--

CREATE TABLE `admission_quotas` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `quota` int(10) UNSIGNED NOT NULL,
  `status` enum('not_set','set') NOT NULL DEFAULT 'not_set',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_results`
--

CREATE TABLE `admission_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_run_id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `aspiration_id` bigint(20) UNSIGNED DEFAULT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `priority_order` int(10) UNSIGNED DEFAULT NULL,
  `score_total` decimal(8,2) NOT NULL,
  `priority_points` decimal(6,2) NOT NULL DEFAULT 0.00,
  `admission_score` decimal(8,2) NOT NULL,
  `rank_no` int(10) UNSIGNED DEFAULT NULL,
  `result_status` enum('admitted','not_admitted','unprocessed') NOT NULL DEFAULT 'unprocessed',
  `enrollment_status` enum('pending','confirmed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_runs`
--

CREATE TABLE `admission_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','calculated','blocked','finalized') NOT NULL DEFAULT 'draft',
  `started_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `calculated_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `blocked_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_run_snapshots`
--

CREATE TABLE `admission_run_snapshots` (
  `admission_run_id` bigint(20) UNSIGNED NOT NULL,
  `input_hash` char(64) NOT NULL,
  `inputs` longtext NOT NULL,
  `report` longtext NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admission_tie_resolutions`
--

CREATE TABLE `admission_tie_resolutions` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `tie_key` char(64) NOT NULL,
  `chosen_candidates` longtext NOT NULL,
  `evidence` text NOT NULL,
  `resolved_by` bigint(20) UNSIGNED NOT NULL,
  `resolved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeal_grader_assignments`
--

CREATE TABLE `appeal_grader_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appeal_request_id` bigint(20) UNSIGNED NOT NULL,
  `council_member_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeal_requests`
--

CREATE TABLE `appeal_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `exam_paper_id` bigint(20) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `original_score` decimal(5,2) NOT NULL,
  `reason` text NOT NULL,
  `fee_amount` decimal(12,2) DEFAULT NULL,
  `payment_status` enum('not_required','pending','paid','failed') NOT NULL DEFAULT 'not_required',
  `status` enum('pending','grading','completed','cancelled') NOT NULL DEFAULT 'pending',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeal_results`
--

CREATE TABLE `appeal_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appeal_request_id` bigint(20) UNSIGNED NOT NULL,
  `old_score` decimal(5,2) NOT NULL,
  `new_score` decimal(5,2) NOT NULL,
  `grader_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `change_reason` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeal_windows`
--

CREATE TABLE `appeal_windows` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `fee_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('draft','open','closed') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `aspirations`
--

CREATE TABLE `aspirations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `priority_order` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `candidates`
--

CREATE TABLE `candidates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `candidate_code` varchar(30) DEFAULT NULL,
  `national_id` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `middle_school_id` int(10) UNSIGNED DEFAULT NULL,
  `eligible` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `exam_number` varchar(30) DEFAULT NULL,
  `exam_site_name` varchar(150) DEFAULT NULL,
  `exam_room_name` varchar(100) DEFAULT NULL,
  `exam_schedule` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admission_status` enum('not_processed','admitted','enrolled') NOT NULL DEFAULT 'not_processed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `candidates`
--

INSERT INTO `candidates` (`id`, `user_id`, `candidate_code`, `national_id`, `full_name`, `date_of_birth`, `gender`, `address`, `phone`, `email`, `middle_school_id`, `eligible`, `status`, `exam_number`, `exam_site_name`, `exam_room_name`, `exam_schedule`, `created_at`, `updated_at`, `admission_status`) VALUES
(1235, 1311, NULL, '079211700001', 'Trần Văn An', '2011-09-13', 'Nam', 'Lớp 9A1, số 11 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000001', 'thcs_nguyen_du.9a1.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1236, 1312, NULL, '079311700002', 'Lê Thị Ánh', '2011-04-24', 'Nữ', 'Lớp 9A1, số 12 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000002', 'thcs_nguyen_du.9a1.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1237, 1313, NULL, '079211700003', 'Phạm Văn Bình', '2011-11-07', 'Nam', 'Lớp 9A1, số 13 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000003', 'thcs_nguyen_du.9a1.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1238, 1314, NULL, '079311700004', 'Hoàng Thị Bích', '2011-06-18', 'Nữ', 'Lớp 9A1, số 14 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000004', 'thcs_nguyen_du.9a1.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1239, 1315, NULL, '079211700005', 'Huỳnh Văn Cường', '2011-01-01', 'Nam', 'Lớp 9A1, số 15 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000005', 'thcs_nguyen_du.9a1.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1240, 1316, NULL, '079311700006', 'Phan Thị Chi', '2011-08-12', 'Nữ', 'Lớp 9A1, số 16 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000006', 'thcs_nguyen_du.9a1.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1241, 1317, NULL, '079211700007', 'Vũ Văn Dũng', '2011-03-23', 'Nam', 'Lớp 9A1, số 17 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000007', 'thcs_nguyen_du.9a1.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1242, 1318, NULL, '079311700008', 'Võ Thị Dung', '2011-10-06', 'Nữ', 'Lớp 9A1, số 18 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000008', 'thcs_nguyen_du.9a1.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1243, 1319, NULL, '079211700009', 'Đặng Văn Đạt', '2011-05-17', 'Nam', 'Lớp 9A1, số 19 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000009', 'thcs_nguyen_du.9a1.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1244, 1320, NULL, '079311700010', 'Bùi Thị Duyên', '2011-12-28', 'Nữ', 'Lớp 9A1, số 20 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000010', 'thcs_nguyen_du.9a1.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1245, 1321, NULL, '079211700011', 'Đỗ Văn Duy', '2011-07-11', 'Nam', 'Lớp 9A1, số 21 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000011', 'thcs_nguyen_du.9a1.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1246, 1322, NULL, '079311700012', 'Hồ Thị Giang', '2011-02-22', 'Nữ', 'Lớp 9A1, số 22 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000012', 'thcs_nguyen_du.9a1.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1247, 1323, NULL, '079211700013', 'Ngô Văn Giang', '2011-09-05', 'Nam', 'Lớp 9A1, số 23 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000013', 'thcs_nguyen_du.9a1.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1248, 1324, NULL, '079311700014', 'Dương Thị Hà', '2011-04-16', 'Nữ', 'Lớp 9A1, số 24 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000014', 'thcs_nguyen_du.9a1.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1249, 1325, NULL, '079211700015', 'Lý Văn Hải', '2011-11-27', 'Nam', 'Lớp 9A1, số 25 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000015', 'thcs_nguyen_du.9a1.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1250, 1326, NULL, '079311700016', 'Đinh Thị Hân', '2011-06-10', 'Nữ', 'Lớp 9A1, số 26 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000016', 'thcs_nguyen_du.9a1.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1251, 1327, NULL, '079211700017', 'Đoàn Văn Hiếu', '2011-01-21', 'Nam', 'Lớp 9A1, số 27 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000017', 'thcs_nguyen_du.9a1.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1252, 1328, NULL, '079311700018', 'Lâm Thị Hằng', '2011-08-04', 'Nữ', 'Lớp 9A1, số 28 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000018', 'thcs_nguyen_du.9a1.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1253, 1329, NULL, '079211700019', 'Trịnh Văn Hoàng', '2011-03-15', 'Nam', 'Lớp 9A1, số 29 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000019', 'thcs_nguyen_du.9a1.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1254, 1330, NULL, '079311700020', 'Nguyễn Thị Hạnh', '2011-10-26', 'Nữ', 'Lớp 9A1, số 30 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000020', 'thcs_nguyen_du.9a1.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1255, 1331, NULL, '079211700021', 'Trần Văn Huy', '2011-05-09', 'Nam', 'Lớp 9A1, số 31 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000021', 'thcs_nguyen_du.9a1.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1256, 1332, NULL, '079311700022', 'Lê Thị Hoa', '2011-12-20', 'Nữ', 'Lớp 9A1, số 32 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000022', 'thcs_nguyen_du.9a1.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1257, 1333, NULL, '079211700023', 'Phạm Văn Hùng', '2011-07-03', 'Nam', 'Lớp 9A1, số 33 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000023', 'thcs_nguyen_du.9a1.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1258, 1334, NULL, '079311700024', 'Hoàng Thị Hương', '2011-02-14', 'Nữ', 'Lớp 9A1, số 34 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000024', 'thcs_nguyen_du.9a1.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1259, 1335, NULL, '079211700025', 'Huỳnh Văn Hưng', '2011-09-25', 'Nam', 'Lớp 9A1, số 35 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000025', 'thcs_nguyen_du.9a1.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1260, 1336, NULL, '079311700026', 'Phan Thị Huyền', '2011-04-08', 'Nữ', 'Lớp 9A1, số 36 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000026', 'thcs_nguyen_du.9a1.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1261, 1337, NULL, '079211700027', 'Vũ Văn Khoa', '2011-11-19', 'Nam', 'Lớp 9A1, số 37 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000027', 'thcs_nguyen_du.9a1.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1262, 1338, NULL, '079311700028', 'Võ Thị Lan', '2011-06-02', 'Nữ', 'Lớp 9A1, số 38 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000028', 'thcs_nguyen_du.9a1.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1263, 1339, NULL, '079211700029', 'Đặng Văn Khang', '2011-01-13', 'Nam', 'Lớp 9A1, số 39 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000029', 'thcs_nguyen_du.9a1.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1264, 1340, NULL, '079311700030', 'Bùi Thị Linh', '2011-08-24', 'Nữ', 'Lớp 9A1, số 40 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000030', 'thcs_nguyen_du.9a1.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1265, 1341, NULL, '079211700031', 'Đỗ Văn Khôi', '2011-03-07', 'Nam', 'Lớp 9A1, số 41 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000031', 'thcs_nguyen_du.9a1.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1266, 1342, NULL, '079311700032', 'Hồ Thị Mai', '2011-10-18', 'Nữ', 'Lớp 9A1, số 42 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000032', 'thcs_nguyen_du.9a1.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1267, 1343, NULL, '079211700033', 'Ngô Văn Lâm', '2011-05-01', 'Nam', 'Lớp 9A1, số 43 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000033', 'thcs_nguyen_du.9a1.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1268, 1344, NULL, '079311700034', 'Dương Thị My', '2011-12-12', 'Nữ', 'Lớp 9A1, số 44 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000034', 'thcs_nguyen_du.9a1.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1269, 1345, NULL, '079211700035', 'Lý Văn Long', '2011-07-23', 'Nam', 'Lớp 9A1, số 45 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000035', 'thcs_nguyen_du.9a1.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1270, 1346, NULL, '079311700036', 'Đinh Thị Ngân', '2011-02-06', 'Nữ', 'Lớp 9A1, số 46 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000036', 'thcs_nguyen_du.9a1.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1271, 1347, NULL, '079211700037', 'Đoàn Văn Minh', '2011-09-17', 'Nam', 'Lớp 9A1, số 47 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000037', 'thcs_nguyen_du.9a1.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1272, 1348, NULL, '079311700038', 'Lâm Thị Nhung', '2011-04-28', 'Nữ', 'Lớp 9A1, số 48 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000038', 'thcs_nguyen_du.9a1.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1273, 1349, NULL, '079211700039', 'Trịnh Văn Nam', '2011-11-11', 'Nam', 'Lớp 9A1, số 49 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000039', 'thcs_nguyen_du.9a1.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1274, 1350, NULL, '079311700040', 'Nguyễn Thị Trang', '2011-06-22', 'Nữ', 'Lớp 9A1, số 50 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000040', 'thcs_nguyen_du.9a1.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1275, 1351, NULL, '079211700041', 'Trần Hữu An', '2011-02-06', 'Nam', 'Lớp 9A2, số 51 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000041', 'thcs_nguyen_du.9a2.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1276, 1352, NULL, '079311700042', 'Lê Ngọc Ánh', '2011-09-17', 'Nữ', 'Lớp 9A2, số 52 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000042', 'thcs_nguyen_du.9a2.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1277, 1353, NULL, '079211700043', 'Phạm Hữu Bình', '2011-04-28', 'Nam', 'Lớp 9A2, số 53 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000043', 'thcs_nguyen_du.9a2.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1278, 1354, NULL, '079311700044', 'Hoàng Ngọc Bích', '2011-11-11', 'Nữ', 'Lớp 9A2, số 54 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000044', 'thcs_nguyen_du.9a2.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1279, 1355, NULL, '079211700045', 'Huỳnh Hữu Cường', '2011-06-22', 'Nam', 'Lớp 9A2, số 55 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000045', 'thcs_nguyen_du.9a2.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1280, 1356, NULL, '079311700046', 'Phan Ngọc Chi', '2011-01-05', 'Nữ', 'Lớp 9A2, số 56 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000046', 'thcs_nguyen_du.9a2.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1281, 1357, NULL, '079211700047', 'Vũ Hữu Dũng', '2011-08-16', 'Nam', 'Lớp 9A2, số 57 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000047', 'thcs_nguyen_du.9a2.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1282, 1358, NULL, '079311700048', 'Võ Ngọc Dung', '2011-03-27', 'Nữ', 'Lớp 9A2, số 58 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000048', 'thcs_nguyen_du.9a2.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1283, 1359, NULL, '079211700049', 'Đặng Hữu Đạt', '2011-10-10', 'Nam', 'Lớp 9A2, số 59 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000049', 'thcs_nguyen_du.9a2.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1284, 1360, NULL, '079311700050', 'Bùi Ngọc Duyên', '2011-05-21', 'Nữ', 'Lớp 9A2, số 60 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000050', 'thcs_nguyen_du.9a2.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1285, 1361, NULL, '079211700051', 'Đỗ Hữu Duy', '2011-12-04', 'Nam', 'Lớp 9A2, số 61 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000051', 'thcs_nguyen_du.9a2.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1286, 1362, NULL, '079311700052', 'Hồ Ngọc Giang', '2011-07-15', 'Nữ', 'Lớp 9A2, số 62 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000052', 'thcs_nguyen_du.9a2.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1287, 1363, NULL, '079211700053', 'Ngô Hữu Giang', '2011-02-26', 'Nam', 'Lớp 9A2, số 63 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000053', 'thcs_nguyen_du.9a2.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1288, 1364, NULL, '079311700054', 'Dương Ngọc Hà', '2011-09-09', 'Nữ', 'Lớp 9A2, số 64 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000054', 'thcs_nguyen_du.9a2.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1289, 1365, NULL, '079211700055', 'Lý Hữu Hải', '2011-04-20', 'Nam', 'Lớp 9A2, số 65 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000055', 'thcs_nguyen_du.9a2.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1290, 1366, NULL, '079311700056', 'Đinh Ngọc Hân', '2011-11-03', 'Nữ', 'Lớp 9A2, số 66 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000056', 'thcs_nguyen_du.9a2.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1291, 1367, NULL, '079211700057', 'Đoàn Hữu Hiếu', '2011-06-14', 'Nam', 'Lớp 9A2, số 67 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000057', 'thcs_nguyen_du.9a2.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1292, 1368, NULL, '079311700058', 'Lâm Ngọc Hằng', '2011-01-25', 'Nữ', 'Lớp 9A2, số 68 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000058', 'thcs_nguyen_du.9a2.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1293, 1369, NULL, '079211700059', 'Trịnh Hữu Hoàng', '2011-08-08', 'Nam', 'Lớp 9A2, số 69 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000059', 'thcs_nguyen_du.9a2.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1294, 1370, NULL, '079311700060', 'Nguyễn Ngọc Hạnh', '2011-03-19', 'Nữ', 'Lớp 9A2, số 70 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000060', 'thcs_nguyen_du.9a2.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1295, 1371, NULL, '079211700061', 'Trần Hữu Huy', '2011-10-02', 'Nam', 'Lớp 9A2, số 71 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000061', 'thcs_nguyen_du.9a2.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1296, 1372, NULL, '079311700062', 'Lê Ngọc Hoa', '2011-05-13', 'Nữ', 'Lớp 9A2, số 72 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000062', 'thcs_nguyen_du.9a2.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1297, 1373, NULL, '079211700063', 'Phạm Hữu Hùng', '2011-12-24', 'Nam', 'Lớp 9A2, số 73 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000063', 'thcs_nguyen_du.9a2.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1298, 1374, NULL, '079311700064', 'Hoàng Ngọc Hương', '2011-07-07', 'Nữ', 'Lớp 9A2, số 74 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000064', 'thcs_nguyen_du.9a2.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1299, 1375, NULL, '079211700065', 'Huỳnh Hữu Hưng', '2011-02-18', 'Nam', 'Lớp 9A2, số 75 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000065', 'thcs_nguyen_du.9a2.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1300, 1376, NULL, '079311700066', 'Phan Ngọc Huyền', '2011-09-01', 'Nữ', 'Lớp 9A2, số 76 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000066', 'thcs_nguyen_du.9a2.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1301, 1377, NULL, '079211700067', 'Vũ Hữu Khoa', '2011-04-12', 'Nam', 'Lớp 9A2, số 77 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000067', 'thcs_nguyen_du.9a2.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1302, 1378, NULL, '079311700068', 'Võ Ngọc Lan', '2011-11-23', 'Nữ', 'Lớp 9A2, số 78 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000068', 'thcs_nguyen_du.9a2.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1303, 1379, NULL, '079211700069', 'Đặng Hữu Khang', '2011-06-06', 'Nam', 'Lớp 9A2, số 79 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000069', 'thcs_nguyen_du.9a2.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1304, 1380, NULL, '079311700070', 'Bùi Ngọc Linh', '2011-01-17', 'Nữ', 'Lớp 9A2, số 80 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000070', 'thcs_nguyen_du.9a2.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1305, 1381, NULL, '079211700071', 'Đỗ Hữu Khôi', '2011-08-28', 'Nam', 'Lớp 9A2, số 81 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000071', 'thcs_nguyen_du.9a2.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1306, 1382, NULL, '079311700072', 'Hồ Ngọc Mai', '2011-03-11', 'Nữ', 'Lớp 9A2, số 82 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000072', 'thcs_nguyen_du.9a2.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1307, 1383, NULL, '079211700073', 'Ngô Hữu Lâm', '2011-10-22', 'Nam', 'Lớp 9A2, số 83 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000073', 'thcs_nguyen_du.9a2.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1308, 1384, NULL, '079311700074', 'Dương Ngọc My', '2011-05-05', 'Nữ', 'Lớp 9A2, số 84 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000074', 'thcs_nguyen_du.9a2.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1309, 1385, NULL, '079211700075', 'Lý Hữu Long', '2011-12-16', 'Nam', 'Lớp 9A2, số 85 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000075', 'thcs_nguyen_du.9a2.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1310, 1386, NULL, '079311700076', 'Đinh Ngọc Ngân', '2011-07-27', 'Nữ', 'Lớp 9A2, số 86 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000076', 'thcs_nguyen_du.9a2.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1311, 1387, NULL, '079211700077', 'Đoàn Hữu Minh', '2011-02-10', 'Nam', 'Lớp 9A2, số 87 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000077', 'thcs_nguyen_du.9a2.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1312, 1388, NULL, '079311700078', 'Lâm Ngọc Nhung', '2011-09-21', 'Nữ', 'Lớp 9A2, số 88 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000078', 'thcs_nguyen_du.9a2.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1313, 1389, NULL, '079211700079', 'Trịnh Hữu Nam', '2011-04-04', 'Nam', 'Lớp 9A2, số 89 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000079', 'thcs_nguyen_du.9a2.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1314, 1390, NULL, '079311700080', 'Nguyễn Ngọc Trang', '2011-11-15', 'Nữ', 'Lớp 9A2, số 90 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000080', 'thcs_nguyen_du.9a2.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1315, 1391, NULL, '079211700081', 'Trần Đức An', '2011-07-27', 'Nam', 'Lớp 9A3, số 91 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000081', 'thcs_nguyen_du.9a3.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1316, 1392, NULL, '079311700082', 'Lê Kim Ánh', '2011-02-10', 'Nữ', 'Lớp 9A3, số 92 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000082', 'thcs_nguyen_du.9a3.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1317, 1393, NULL, '079211700083', 'Phạm Đức Bình', '2011-09-21', 'Nam', 'Lớp 9A3, số 93 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000083', 'thcs_nguyen_du.9a3.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1318, 1394, NULL, '079311700084', 'Hoàng Kim Bích', '2011-04-04', 'Nữ', 'Lớp 9A3, số 94 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000084', 'thcs_nguyen_du.9a3.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1319, 1395, NULL, '079211700085', 'Huỳnh Đức Cường', '2011-11-15', 'Nam', 'Lớp 9A3, số 95 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000085', 'thcs_nguyen_du.9a3.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1320, 1396, NULL, '079311700086', 'Phan Kim Chi', '2011-06-26', 'Nữ', 'Lớp 9A3, số 96 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000086', 'thcs_nguyen_du.9a3.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1321, 1397, NULL, '079211700087', 'Vũ Đức Dũng', '2011-01-09', 'Nam', 'Lớp 9A3, số 97 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000087', 'thcs_nguyen_du.9a3.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1322, 1398, NULL, '079311700088', 'Võ Kim Dung', '2011-08-20', 'Nữ', 'Lớp 9A3, số 98 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000088', 'thcs_nguyen_du.9a3.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1323, 1399, NULL, '079211700089', 'Đặng Đức Đạt', '2011-03-03', 'Nam', 'Lớp 9A3, số 99 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000089', 'thcs_nguyen_du.9a3.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1324, 1400, NULL, '079311700090', 'Bùi Kim Duyên', '2011-10-14', 'Nữ', 'Lớp 9A3, số 100 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000090', 'thcs_nguyen_du.9a3.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1325, 1401, NULL, '079211700091', 'Đỗ Đức Duy', '2011-05-25', 'Nam', 'Lớp 9A3, số 101 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000091', 'thcs_nguyen_du.9a3.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1326, 1402, NULL, '079311700092', 'Hồ Kim Giang', '2011-12-08', 'Nữ', 'Lớp 9A3, số 102 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000092', 'thcs_nguyen_du.9a3.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1327, 1403, NULL, '079211700093', 'Ngô Đức Giang', '2011-07-19', 'Nam', 'Lớp 9A3, số 103 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000093', 'thcs_nguyen_du.9a3.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1328, 1404, NULL, '079311700094', 'Dương Kim Hà', '2011-02-02', 'Nữ', 'Lớp 9A3, số 104 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000094', 'thcs_nguyen_du.9a3.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1329, 1405, NULL, '079211700095', 'Lý Đức Hải', '2011-09-13', 'Nam', 'Lớp 9A3, số 105 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000095', 'thcs_nguyen_du.9a3.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1330, 1406, NULL, '079311700096', 'Đinh Kim Hân', '2011-04-24', 'Nữ', 'Lớp 9A3, số 106 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000096', 'thcs_nguyen_du.9a3.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1331, 1407, NULL, '079211700097', 'Đoàn Đức Hiếu', '2011-11-07', 'Nam', 'Lớp 9A3, số 107 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000097', 'thcs_nguyen_du.9a3.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1332, 1408, NULL, '079311700098', 'Lâm Kim Hằng', '2011-06-18', 'Nữ', 'Lớp 9A3, số 108 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000098', 'thcs_nguyen_du.9a3.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1333, 1409, NULL, '079211700099', 'Trịnh Đức Hoàng', '2011-01-01', 'Nam', 'Lớp 9A3, số 109 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000099', 'thcs_nguyen_du.9a3.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1334, 1410, NULL, '079311700100', 'Nguyễn Kim Hạnh', '2011-08-12', 'Nữ', 'Lớp 9A3, số 110 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000100', 'thcs_nguyen_du.9a3.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1335, 1411, NULL, '079211700101', 'Trần Đức Huy', '2011-03-23', 'Nam', 'Lớp 9A3, số 111 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000101', 'thcs_nguyen_du.9a3.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1336, 1412, NULL, '079311700102', 'Lê Kim Hoa', '2011-10-06', 'Nữ', 'Lớp 9A3, số 112 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000102', 'thcs_nguyen_du.9a3.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1337, 1413, NULL, '079211700103', 'Phạm Đức Hùng', '2011-05-17', 'Nam', 'Lớp 9A3, số 113 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000103', 'thcs_nguyen_du.9a3.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1338, 1414, NULL, '079311700104', 'Hoàng Kim Hương', '2011-12-28', 'Nữ', 'Lớp 9A3, số 114 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000104', 'thcs_nguyen_du.9a3.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1339, 1415, NULL, '079211700105', 'Huỳnh Đức Hưng', '2011-07-11', 'Nam', 'Lớp 9A3, số 115 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000105', 'thcs_nguyen_du.9a3.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1340, 1416, NULL, '079311700106', 'Phan Kim Huyền', '2011-02-22', 'Nữ', 'Lớp 9A3, số 116 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000106', 'thcs_nguyen_du.9a3.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1341, 1417, NULL, '079211700107', 'Vũ Đức Khoa', '2011-09-05', 'Nam', 'Lớp 9A3, số 117 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000107', 'thcs_nguyen_du.9a3.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1342, 1418, NULL, '079311700108', 'Võ Kim Lan', '2011-04-16', 'Nữ', 'Lớp 9A3, số 118 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000108', 'thcs_nguyen_du.9a3.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1343, 1419, NULL, '079211700109', 'Đặng Đức Khang', '2011-11-27', 'Nam', 'Lớp 9A3, số 119 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000109', 'thcs_nguyen_du.9a3.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1344, 1420, NULL, '079311700110', 'Bùi Kim Linh', '2011-06-10', 'Nữ', 'Lớp 9A3, số 120 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000110', 'thcs_nguyen_du.9a3.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1345, 1421, NULL, '079211700111', 'Đỗ Đức Khôi', '2011-01-21', 'Nam', 'Lớp 9A3, số 121 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000111', 'thcs_nguyen_du.9a3.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1346, 1422, NULL, '079311700112', 'Hồ Kim Mai', '2011-08-04', 'Nữ', 'Lớp 9A3, số 122 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000112', 'thcs_nguyen_du.9a3.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1347, 1423, NULL, '079211700113', 'Ngô Đức Lâm', '2011-03-15', 'Nam', 'Lớp 9A3, số 123 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000113', 'thcs_nguyen_du.9a3.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1348, 1424, NULL, '079311700114', 'Dương Kim My', '2011-10-26', 'Nữ', 'Lớp 9A3, số 124 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000114', 'thcs_nguyen_du.9a3.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1349, 1425, NULL, '079211700115', 'Lý Đức Long', '2011-05-09', 'Nam', 'Lớp 9A3, số 125 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000115', 'thcs_nguyen_du.9a3.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1350, 1426, NULL, '079311700116', 'Đinh Kim Ngân', '2011-12-20', 'Nữ', 'Lớp 9A3, số 126 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000116', 'thcs_nguyen_du.9a3.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1351, 1427, NULL, '079211700117', 'Đoàn Đức Minh', '2011-07-03', 'Nam', 'Lớp 9A3, số 127 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000117', 'thcs_nguyen_du.9a3.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1352, 1428, NULL, '079311700118', 'Lâm Kim Nhung', '2011-02-14', 'Nữ', 'Lớp 9A3, số 128 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000118', 'thcs_nguyen_du.9a3.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1353, 1429, NULL, '079211700119', 'Trịnh Đức Nam', '2011-09-25', 'Nam', 'Lớp 9A3, số 129 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000119', 'thcs_nguyen_du.9a3.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1354, 1430, NULL, '079311700120', 'Nguyễn Kim Trang', '2011-04-08', 'Nữ', 'Lớp 9A3, số 130 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000120', 'thcs_nguyen_du.9a3.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1355, 1431, NULL, '079211700121', 'Trần Đình An', '2011-12-20', 'Nam', 'Lớp 9A4, số 131 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000121', 'thcs_nguyen_du.9a4.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1356, 1432, NULL, '079311700122', 'Lê Mai Ánh', '2011-07-03', 'Nữ', 'Lớp 9A4, số 132 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000122', 'thcs_nguyen_du.9a4.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1357, 1433, NULL, '079211700123', 'Phạm Đình Bình', '2011-02-14', 'Nam', 'Lớp 9A4, số 133 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000123', 'thcs_nguyen_du.9a4.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1358, 1434, NULL, '079311700124', 'Hoàng Mai Bích', '2011-09-25', 'Nữ', 'Lớp 9A4, số 134 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000124', 'thcs_nguyen_du.9a4.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1359, 1435, NULL, '079211700125', 'Huỳnh Đình Cường', '2011-04-08', 'Nam', 'Lớp 9A4, số 135 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000125', 'thcs_nguyen_du.9a4.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1360, 1436, NULL, '079311700126', 'Phan Mai Chi', '2011-11-19', 'Nữ', 'Lớp 9A4, số 136 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000126', 'thcs_nguyen_du.9a4.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1361, 1437, NULL, '079211700127', 'Vũ Đình Dũng', '2011-06-02', 'Nam', 'Lớp 9A4, số 137 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000127', 'thcs_nguyen_du.9a4.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1362, 1438, NULL, '079311700128', 'Võ Mai Dung', '2011-01-13', 'Nữ', 'Lớp 9A4, số 138 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000128', 'thcs_nguyen_du.9a4.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1363, 1439, NULL, '079211700129', 'Đặng Đình Đạt', '2011-08-24', 'Nam', 'Lớp 9A4, số 139 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000129', 'thcs_nguyen_du.9a4.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1364, 1440, NULL, '079311700130', 'Bùi Mai Duyên', '2011-03-07', 'Nữ', 'Lớp 9A4, số 140 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000130', 'thcs_nguyen_du.9a4.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1365, 1441, NULL, '079211700131', 'Đỗ Đình Duy', '2011-10-18', 'Nam', 'Lớp 9A4, số 141 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000131', 'thcs_nguyen_du.9a4.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1366, 1442, NULL, '079311700132', 'Hồ Mai Giang', '2011-05-01', 'Nữ', 'Lớp 9A4, số 142 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000132', 'thcs_nguyen_du.9a4.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1367, 1443, NULL, '079211700133', 'Ngô Đình Giang', '2011-12-12', 'Nam', 'Lớp 9A4, số 143 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000133', 'thcs_nguyen_du.9a4.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1368, 1444, NULL, '079311700134', 'Dương Mai Hà', '2011-07-23', 'Nữ', 'Lớp 9A4, số 144 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000134', 'thcs_nguyen_du.9a4.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1369, 1445, NULL, '079211700135', 'Lý Đình Hải', '2011-02-06', 'Nam', 'Lớp 9A4, số 145 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000135', 'thcs_nguyen_du.9a4.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1370, 1446, NULL, '079311700136', 'Đinh Mai Hân', '2011-09-17', 'Nữ', 'Lớp 9A4, số 146 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000136', 'thcs_nguyen_du.9a4.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1371, 1447, NULL, '079211700137', 'Đoàn Đình Hiếu', '2011-04-28', 'Nam', 'Lớp 9A4, số 147 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000137', 'thcs_nguyen_du.9a4.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1372, 1448, NULL, '079311700138', 'Lâm Mai Hằng', '2011-11-11', 'Nữ', 'Lớp 9A4, số 148 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000138', 'thcs_nguyen_du.9a4.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1373, 1449, NULL, '079211700139', 'Trịnh Đình Hoàng', '2011-06-22', 'Nam', 'Lớp 9A4, số 149 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000139', 'thcs_nguyen_du.9a4.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1374, 1450, NULL, '079311700140', 'Nguyễn Mai Hạnh', '2011-01-05', 'Nữ', 'Lớp 9A4, số 150 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000140', 'thcs_nguyen_du.9a4.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1375, 1451, NULL, '079211700141', 'Trần Đình Huy', '2011-08-16', 'Nam', 'Lớp 9A4, số 151 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000141', 'thcs_nguyen_du.9a4.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1376, 1452, NULL, '079311700142', 'Lê Mai Hoa', '2011-03-27', 'Nữ', 'Lớp 9A4, số 152 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000142', 'thcs_nguyen_du.9a4.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1377, 1453, NULL, '079211700143', 'Phạm Đình Hùng', '2011-10-10', 'Nam', 'Lớp 9A4, số 153 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000143', 'thcs_nguyen_du.9a4.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1378, 1454, NULL, '079311700144', 'Hoàng Mai Hương', '2011-05-21', 'Nữ', 'Lớp 9A4, số 154 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000144', 'thcs_nguyen_du.9a4.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1379, 1455, NULL, '079211700145', 'Huỳnh Đình Hưng', '2011-12-04', 'Nam', 'Lớp 9A4, số 155 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000145', 'thcs_nguyen_du.9a4.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1380, 1456, NULL, '079311700146', 'Phan Mai Huyền', '2011-07-15', 'Nữ', 'Lớp 9A4, số 156 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000146', 'thcs_nguyen_du.9a4.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1381, 1457, NULL, '079211700147', 'Vũ Đình Khoa', '2011-02-26', 'Nam', 'Lớp 9A4, số 157 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000147', 'thcs_nguyen_du.9a4.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1382, 1458, NULL, '079311700148', 'Võ Mai Lan', '2011-09-09', 'Nữ', 'Lớp 9A4, số 158 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000148', 'thcs_nguyen_du.9a4.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1383, 1459, NULL, '079211700149', 'Đặng Đình Khang', '2011-04-20', 'Nam', 'Lớp 9A4, số 159 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000149', 'thcs_nguyen_du.9a4.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1384, 1460, NULL, '079311700150', 'Bùi Mai Linh', '2011-11-03', 'Nữ', 'Lớp 9A4, số 160 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000150', 'thcs_nguyen_du.9a4.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1385, 1461, NULL, '079211700151', 'Đỗ Đình Khôi', '2011-06-14', 'Nam', 'Lớp 9A4, số 161 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000151', 'thcs_nguyen_du.9a4.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1386, 1462, NULL, '079311700152', 'Hồ Mai Mai', '2011-01-25', 'Nữ', 'Lớp 9A4, số 162 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000152', 'thcs_nguyen_du.9a4.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1387, 1463, NULL, '079211700153', 'Ngô Đình Lâm', '2011-08-08', 'Nam', 'Lớp 9A4, số 163 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000153', 'thcs_nguyen_du.9a4.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1388, 1464, NULL, '079311700154', 'Dương Mai My', '2011-03-19', 'Nữ', 'Lớp 9A4, số 164 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000154', 'thcs_nguyen_du.9a4.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1389, 1465, NULL, '079211700155', 'Lý Đình Long', '2011-10-02', 'Nam', 'Lớp 9A4, số 165 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000155', 'thcs_nguyen_du.9a4.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1390, 1466, NULL, '079311700156', 'Đinh Mai Ngân', '2011-05-13', 'Nữ', 'Lớp 9A4, số 166 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000156', 'thcs_nguyen_du.9a4.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1391, 1467, NULL, '079211700157', 'Đoàn Đình Minh', '2011-12-24', 'Nam', 'Lớp 9A4, số 167 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000157', 'thcs_nguyen_du.9a4.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1392, 1468, NULL, '079311700158', 'Lâm Mai Nhung', '2011-07-07', 'Nữ', 'Lớp 9A4, số 168 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000158', 'thcs_nguyen_du.9a4.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1393, 1469, NULL, '079211700159', 'Trịnh Đình Nam', '2011-02-18', 'Nam', 'Lớp 9A4, số 169 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000159', 'thcs_nguyen_du.9a4.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1394, 1470, NULL, '079311700160', 'Nguyễn Mai Trang', '2011-09-01', 'Nữ', 'Lớp 9A4, số 170 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000160', 'thcs_nguyen_du.9a4.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1395, 1471, NULL, '079211700161', 'Trần Quang An', '2011-05-13', 'Nam', 'Lớp 9A5, số 171 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000161', 'thcs_nguyen_du.9a5.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1396, 1472, NULL, '079311700162', 'Lê Phương Ánh', '2011-12-24', 'Nữ', 'Lớp 9A5, số 172 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000162', 'thcs_nguyen_du.9a5.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1397, 1473, NULL, '079211700163', 'Phạm Quang Bình', '2011-07-07', 'Nam', 'Lớp 9A5, số 173 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000163', 'thcs_nguyen_du.9a5.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1398, 1474, NULL, '079311700164', 'Hoàng Phương Bích', '2011-02-18', 'Nữ', 'Lớp 9A5, số 174 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000164', 'thcs_nguyen_du.9a5.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1399, 1475, NULL, '079211700165', 'Huỳnh Quang Cường', '2011-09-01', 'Nam', 'Lớp 9A5, số 175 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000165', 'thcs_nguyen_du.9a5.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1400, 1476, NULL, '079311700166', 'Phan Phương Chi', '2011-04-12', 'Nữ', 'Lớp 9A5, số 176 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000166', 'thcs_nguyen_du.9a5.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1401, 1477, NULL, '079211700167', 'Vũ Quang Dũng', '2011-11-23', 'Nam', 'Lớp 9A5, số 177 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000167', 'thcs_nguyen_du.9a5.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1402, 1478, NULL, '079311700168', 'Võ Phương Dung', '2011-06-06', 'Nữ', 'Lớp 9A5, số 178 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000168', 'thcs_nguyen_du.9a5.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1403, 1479, NULL, '079211700169', 'Đặng Quang Đạt', '2011-01-17', 'Nam', 'Lớp 9A5, số 179 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000169', 'thcs_nguyen_du.9a5.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1404, 1480, NULL, '079311700170', 'Bùi Phương Duyên', '2011-08-28', 'Nữ', 'Lớp 9A5, số 180 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000170', 'thcs_nguyen_du.9a5.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1405, 1481, NULL, '079211700171', 'Đỗ Quang Duy', '2011-03-11', 'Nam', 'Lớp 9A5, số 181 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000171', 'thcs_nguyen_du.9a5.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1406, 1482, NULL, '079311700172', 'Hồ Phương Giang', '2011-10-22', 'Nữ', 'Lớp 9A5, số 182 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000172', 'thcs_nguyen_du.9a5.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed');
INSERT INTO `candidates` (`id`, `user_id`, `candidate_code`, `national_id`, `full_name`, `date_of_birth`, `gender`, `address`, `phone`, `email`, `middle_school_id`, `eligible`, `status`, `exam_number`, `exam_site_name`, `exam_room_name`, `exam_schedule`, `created_at`, `updated_at`, `admission_status`) VALUES
(1407, 1483, NULL, '079211700173', 'Ngô Quang Giang', '2011-05-05', 'Nam', 'Lớp 9A5, số 183 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000173', 'thcs_nguyen_du.9a5.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1408, 1484, NULL, '079311700174', 'Dương Phương Hà', '2011-12-16', 'Nữ', 'Lớp 9A5, số 184 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000174', 'thcs_nguyen_du.9a5.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1409, 1485, NULL, '079211700175', 'Lý Quang Hải', '2011-07-27', 'Nam', 'Lớp 9A5, số 185 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000175', 'thcs_nguyen_du.9a5.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1410, 1486, NULL, '079311700176', 'Đinh Phương Hân', '2011-02-10', 'Nữ', 'Lớp 9A5, số 186 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000176', 'thcs_nguyen_du.9a5.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1411, 1487, NULL, '079211700177', 'Đoàn Quang Hiếu', '2011-09-21', 'Nam', 'Lớp 9A5, số 187 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000177', 'thcs_nguyen_du.9a5.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1412, 1488, NULL, '079311700178', 'Lâm Phương Hằng', '2011-04-04', 'Nữ', 'Lớp 9A5, số 188 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000178', 'thcs_nguyen_du.9a5.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1413, 1489, NULL, '079211700179', 'Trịnh Quang Hoàng', '2011-11-15', 'Nam', 'Lớp 9A5, số 189 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000179', 'thcs_nguyen_du.9a5.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1414, 1490, NULL, '079311700180', 'Nguyễn Phương Hạnh', '2011-06-26', 'Nữ', 'Lớp 9A5, số 190 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000180', 'thcs_nguyen_du.9a5.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1415, 1491, NULL, '079211700181', 'Trần Quang Huy', '2011-01-09', 'Nam', 'Lớp 9A5, số 191 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000181', 'thcs_nguyen_du.9a5.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1416, 1492, NULL, '079311700182', 'Lê Phương Hoa', '2011-08-20', 'Nữ', 'Lớp 9A5, số 192 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000182', 'thcs_nguyen_du.9a5.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1417, 1493, NULL, '079211700183', 'Phạm Quang Hùng', '2011-03-03', 'Nam', 'Lớp 9A5, số 193 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000183', 'thcs_nguyen_du.9a5.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1418, 1494, NULL, '079311700184', 'Hoàng Phương Hương', '2011-10-14', 'Nữ', 'Lớp 9A5, số 194 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000184', 'thcs_nguyen_du.9a5.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1419, 1495, NULL, '079211700185', 'Huỳnh Quang Hưng', '2011-05-25', 'Nam', 'Lớp 9A5, số 195 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000185', 'thcs_nguyen_du.9a5.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1420, 1496, NULL, '079311700186', 'Phan Phương Huyền', '2011-12-08', 'Nữ', 'Lớp 9A5, số 196 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000186', 'thcs_nguyen_du.9a5.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1421, 1497, NULL, '079211700187', 'Vũ Quang Khoa', '2011-07-19', 'Nam', 'Lớp 9A5, số 197 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000187', 'thcs_nguyen_du.9a5.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1422, 1498, NULL, '079311700188', 'Võ Phương Lan', '2011-02-02', 'Nữ', 'Lớp 9A5, số 198 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000188', 'thcs_nguyen_du.9a5.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1423, 1499, NULL, '079211700189', 'Đặng Quang Khang', '2011-09-13', 'Nam', 'Lớp 9A5, số 199 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000189', 'thcs_nguyen_du.9a5.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1424, 1500, NULL, '079311700190', 'Bùi Phương Linh', '2011-04-24', 'Nữ', 'Lớp 9A5, số 200 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000190', 'thcs_nguyen_du.9a5.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1425, 1501, NULL, '079211700191', 'Đỗ Quang Khôi', '2011-11-07', 'Nam', 'Lớp 9A5, số 201 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000191', 'thcs_nguyen_du.9a5.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1426, 1502, NULL, '079311700192', 'Hồ Phương Mai', '2011-06-18', 'Nữ', 'Lớp 9A5, số 202 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000192', 'thcs_nguyen_du.9a5.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1427, 1503, NULL, '079211700193', 'Ngô Quang Lâm', '2011-01-01', 'Nam', 'Lớp 9A5, số 203 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000193', 'thcs_nguyen_du.9a5.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1428, 1504, NULL, '079311700194', 'Dương Phương My', '2011-08-12', 'Nữ', 'Lớp 9A5, số 204 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000194', 'thcs_nguyen_du.9a5.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1429, 1505, NULL, '079211700195', 'Lý Quang Long', '2011-03-23', 'Nam', 'Lớp 9A5, số 205 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000195', 'thcs_nguyen_du.9a5.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1430, 1506, NULL, '079311700196', 'Đinh Phương Ngân', '2011-10-06', 'Nữ', 'Lớp 9A5, số 206 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000196', 'thcs_nguyen_du.9a5.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1431, 1507, NULL, '079211700197', 'Đoàn Quang Minh', '2011-05-17', 'Nam', 'Lớp 9A5, số 207 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000197', 'thcs_nguyen_du.9a5.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1432, 1508, NULL, '079311700198', 'Lâm Phương Nhung', '2011-12-28', 'Nữ', 'Lớp 9A5, số 208 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000198', 'thcs_nguyen_du.9a5.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1433, 1509, NULL, '079211700199', 'Trịnh Quang Nam', '2011-07-11', 'Nam', 'Lớp 9A5, số 209 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000199', 'thcs_nguyen_du.9a5.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1434, 1510, NULL, '079311700200', 'Nguyễn Phương Trang', '2011-02-22', 'Nữ', 'Lớp 9A5, số 210 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000200', 'thcs_nguyen_du.9a5.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1435, 1511, NULL, '079211700201', 'Trần Minh An', '2011-10-06', 'Nam', 'Lớp 9A6, số 211 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000201', 'thcs_nguyen_du.9a6.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1436, 1512, NULL, '079311700202', 'Lê Thảo Ánh', '2011-05-17', 'Nữ', 'Lớp 9A6, số 212 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000202', 'thcs_nguyen_du.9a6.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1437, 1513, NULL, '079211700203', 'Phạm Minh Bình', '2011-12-28', 'Nam', 'Lớp 9A6, số 213 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000203', 'thcs_nguyen_du.9a6.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1438, 1514, NULL, '079311700204', 'Hoàng Thảo Bích', '2011-07-11', 'Nữ', 'Lớp 9A6, số 214 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000204', 'thcs_nguyen_du.9a6.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1439, 1515, NULL, '079211700205', 'Huỳnh Minh Cường', '2011-02-22', 'Nam', 'Lớp 9A6, số 215 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000205', 'thcs_nguyen_du.9a6.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1440, 1516, NULL, '079311700206', 'Phan Thảo Chi', '2011-09-05', 'Nữ', 'Lớp 9A6, số 216 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000206', 'thcs_nguyen_du.9a6.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1441, 1517, NULL, '079211700207', 'Vũ Minh Dũng', '2011-04-16', 'Nam', 'Lớp 9A6, số 217 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000207', 'thcs_nguyen_du.9a6.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1442, 1518, NULL, '079311700208', 'Võ Thảo Dung', '2011-11-27', 'Nữ', 'Lớp 9A6, số 218 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000208', 'thcs_nguyen_du.9a6.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1443, 1519, NULL, '079211700209', 'Đặng Minh Đạt', '2011-06-10', 'Nam', 'Lớp 9A6, số 219 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000209', 'thcs_nguyen_du.9a6.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1444, 1520, NULL, '079311700210', 'Bùi Thảo Duyên', '2011-01-21', 'Nữ', 'Lớp 9A6, số 220 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000210', 'thcs_nguyen_du.9a6.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1445, 1521, NULL, '079211700211', 'Đỗ Minh Duy', '2011-08-04', 'Nam', 'Lớp 9A6, số 221 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000211', 'thcs_nguyen_du.9a6.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1446, 1522, NULL, '079311700212', 'Hồ Thảo Giang', '2011-03-15', 'Nữ', 'Lớp 9A6, số 222 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000212', 'thcs_nguyen_du.9a6.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1447, 1523, NULL, '079211700213', 'Ngô Minh Giang', '2011-10-26', 'Nam', 'Lớp 9A6, số 223 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000213', 'thcs_nguyen_du.9a6.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1448, 1524, NULL, '079311700214', 'Dương Thảo Hà', '2011-05-09', 'Nữ', 'Lớp 9A6, số 224 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000214', 'thcs_nguyen_du.9a6.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1449, 1525, NULL, '079211700215', 'Lý Minh Hải', '2011-12-20', 'Nam', 'Lớp 9A6, số 225 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000215', 'thcs_nguyen_du.9a6.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1450, 1526, NULL, '079311700216', 'Đinh Thảo Hân', '2011-07-03', 'Nữ', 'Lớp 9A6, số 226 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000216', 'thcs_nguyen_du.9a6.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1451, 1527, NULL, '079211700217', 'Đoàn Minh Hiếu', '2011-02-14', 'Nam', 'Lớp 9A6, số 227 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000217', 'thcs_nguyen_du.9a6.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1452, 1528, NULL, '079311700218', 'Lâm Thảo Hằng', '2011-09-25', 'Nữ', 'Lớp 9A6, số 228 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000218', 'thcs_nguyen_du.9a6.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1453, 1529, NULL, '079211700219', 'Trịnh Minh Hoàng', '2011-04-08', 'Nam', 'Lớp 9A6, số 229 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000219', 'thcs_nguyen_du.9a6.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1454, 1530, NULL, '079311700220', 'Nguyễn Thảo Hạnh', '2011-11-19', 'Nữ', 'Lớp 9A6, số 230 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000220', 'thcs_nguyen_du.9a6.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1455, 1531, NULL, '079211700221', 'Trần Minh Huy', '2011-06-02', 'Nam', 'Lớp 9A6, số 231 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000221', 'thcs_nguyen_du.9a6.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1456, 1532, NULL, '079311700222', 'Lê Thảo Hoa', '2011-01-13', 'Nữ', 'Lớp 9A6, số 232 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000222', 'thcs_nguyen_du.9a6.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1457, 1533, NULL, '079211700223', 'Phạm Minh Hùng', '2011-08-24', 'Nam', 'Lớp 9A6, số 233 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000223', 'thcs_nguyen_du.9a6.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1458, 1534, NULL, '079311700224', 'Hoàng Thảo Hương', '2011-03-07', 'Nữ', 'Lớp 9A6, số 234 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000224', 'thcs_nguyen_du.9a6.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1459, 1535, NULL, '079211700225', 'Huỳnh Minh Hưng', '2011-10-18', 'Nam', 'Lớp 9A6, số 235 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000225', 'thcs_nguyen_du.9a6.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1460, 1536, NULL, '079311700226', 'Phan Thảo Huyền', '2011-05-01', 'Nữ', 'Lớp 9A6, số 236 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000226', 'thcs_nguyen_du.9a6.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1461, 1537, NULL, '079211700227', 'Vũ Minh Khoa', '2011-12-12', 'Nam', 'Lớp 9A6, số 237 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000227', 'thcs_nguyen_du.9a6.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1462, 1538, NULL, '079311700228', 'Võ Thảo Lan', '2011-07-23', 'Nữ', 'Lớp 9A6, số 238 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000228', 'thcs_nguyen_du.9a6.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1463, 1539, NULL, '079211700229', 'Đặng Minh Khang', '2011-02-06', 'Nam', 'Lớp 9A6, số 239 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000229', 'thcs_nguyen_du.9a6.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1464, 1540, NULL, '079311700230', 'Bùi Thảo Linh', '2011-09-17', 'Nữ', 'Lớp 9A6, số 240 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000230', 'thcs_nguyen_du.9a6.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1465, 1541, NULL, '079211700231', 'Đỗ Minh Khôi', '2011-04-28', 'Nam', 'Lớp 9A6, số 241 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000231', 'thcs_nguyen_du.9a6.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1466, 1542, NULL, '079311700232', 'Hồ Thảo Mai', '2011-11-11', 'Nữ', 'Lớp 9A6, số 242 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000232', 'thcs_nguyen_du.9a6.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1467, 1543, NULL, '079211700233', 'Ngô Minh Lâm', '2011-06-22', 'Nam', 'Lớp 9A6, số 243 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000233', 'thcs_nguyen_du.9a6.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1468, 1544, NULL, '079311700234', 'Dương Thảo My', '2011-01-05', 'Nữ', 'Lớp 9A6, số 244 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000234', 'thcs_nguyen_du.9a6.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1469, 1545, NULL, '079211700235', 'Lý Minh Long', '2011-08-16', 'Nam', 'Lớp 9A6, số 245 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000235', 'thcs_nguyen_du.9a6.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1470, 1546, NULL, '079311700236', 'Đinh Thảo Ngân', '2011-03-27', 'Nữ', 'Lớp 9A6, số 246 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000236', 'thcs_nguyen_du.9a6.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1471, 1547, NULL, '079211700237', 'Đoàn Minh Minh', '2011-10-10', 'Nam', 'Lớp 9A6, số 247 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000237', 'thcs_nguyen_du.9a6.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1472, 1548, NULL, '079311700238', 'Lâm Thảo Nhung', '2011-05-21', 'Nữ', 'Lớp 9A6, số 248 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000238', 'thcs_nguyen_du.9a6.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1473, 1549, NULL, '079211700239', 'Trịnh Minh Nam', '2011-12-04', 'Nam', 'Lớp 9A6, số 249 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000239', 'thcs_nguyen_du.9a6.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1474, 1550, NULL, '079311700240', 'Nguyễn Thảo Trang', '2011-07-15', 'Nữ', 'Lớp 9A6, số 250 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000240', 'thcs_nguyen_du.9a6.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1475, 1551, NULL, '079211700241', 'Trần Tuấn An', '2011-03-27', 'Nam', 'Lớp 9A7, số 251 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000241', 'thcs_nguyen_du.9a7.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1476, 1552, NULL, '079311700242', 'Lê Khánh Ánh', '2011-10-10', 'Nữ', 'Lớp 9A7, số 252 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000242', 'thcs_nguyen_du.9a7.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1477, 1553, NULL, '079211700243', 'Phạm Tuấn Bình', '2011-05-21', 'Nam', 'Lớp 9A7, số 253 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000243', 'thcs_nguyen_du.9a7.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1478, 1554, NULL, '079311700244', 'Hoàng Khánh Bích', '2011-12-04', 'Nữ', 'Lớp 9A7, số 254 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000244', 'thcs_nguyen_du.9a7.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1479, 1555, NULL, '079211700245', 'Huỳnh Tuấn Cường', '2011-07-15', 'Nam', 'Lớp 9A7, số 255 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000245', 'thcs_nguyen_du.9a7.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1480, 1556, NULL, '079311700246', 'Phan Khánh Chi', '2011-02-26', 'Nữ', 'Lớp 9A7, số 256 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000246', 'thcs_nguyen_du.9a7.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1481, 1557, NULL, '079211700247', 'Vũ Tuấn Dũng', '2011-09-09', 'Nam', 'Lớp 9A7, số 257 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000247', 'thcs_nguyen_du.9a7.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1482, 1558, NULL, '079311700248', 'Võ Khánh Dung', '2011-04-20', 'Nữ', 'Lớp 9A7, số 258 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000248', 'thcs_nguyen_du.9a7.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1483, 1559, NULL, '079211700249', 'Đặng Tuấn Đạt', '2011-11-03', 'Nam', 'Lớp 9A7, số 259 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000249', 'thcs_nguyen_du.9a7.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1484, 1560, NULL, '079311700250', 'Bùi Khánh Duyên', '2011-06-14', 'Nữ', 'Lớp 9A7, số 260 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000250', 'thcs_nguyen_du.9a7.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1485, 1561, NULL, '079211700251', 'Đỗ Tuấn Duy', '2011-01-25', 'Nam', 'Lớp 9A7, số 261 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000251', 'thcs_nguyen_du.9a7.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1486, 1562, NULL, '079311700252', 'Hồ Khánh Giang', '2011-08-08', 'Nữ', 'Lớp 9A7, số 262 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000252', 'thcs_nguyen_du.9a7.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1487, 1563, NULL, '079211700253', 'Ngô Tuấn Giang', '2011-03-19', 'Nam', 'Lớp 9A7, số 263 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000253', 'thcs_nguyen_du.9a7.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1488, 1564, NULL, '079311700254', 'Dương Khánh Hà', '2011-10-02', 'Nữ', 'Lớp 9A7, số 264 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000254', 'thcs_nguyen_du.9a7.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1489, 1565, NULL, '079211700255', 'Lý Tuấn Hải', '2011-05-13', 'Nam', 'Lớp 9A7, số 265 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000255', 'thcs_nguyen_du.9a7.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1490, 1566, NULL, '079311700256', 'Đinh Khánh Hân', '2011-12-24', 'Nữ', 'Lớp 9A7, số 266 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000256', 'thcs_nguyen_du.9a7.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1491, 1567, NULL, '079211700257', 'Đoàn Tuấn Hiếu', '2011-07-07', 'Nam', 'Lớp 9A7, số 267 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000257', 'thcs_nguyen_du.9a7.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1492, 1568, NULL, '079311700258', 'Lâm Khánh Hằng', '2011-02-18', 'Nữ', 'Lớp 9A7, số 268 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000258', 'thcs_nguyen_du.9a7.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1493, 1569, NULL, '079211700259', 'Trịnh Tuấn Hoàng', '2011-09-01', 'Nam', 'Lớp 9A7, số 269 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000259', 'thcs_nguyen_du.9a7.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1494, 1570, NULL, '079311700260', 'Nguyễn Khánh Hạnh', '2011-04-12', 'Nữ', 'Lớp 9A7, số 270 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000260', 'thcs_nguyen_du.9a7.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1495, 1571, NULL, '079211700261', 'Trần Tuấn Huy', '2011-11-23', 'Nam', 'Lớp 9A7, số 271 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000261', 'thcs_nguyen_du.9a7.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1496, 1572, NULL, '079311700262', 'Lê Khánh Hoa', '2011-06-06', 'Nữ', 'Lớp 9A7, số 272 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000262', 'thcs_nguyen_du.9a7.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1497, 1573, NULL, '079211700263', 'Phạm Tuấn Hùng', '2011-01-17', 'Nam', 'Lớp 9A7, số 273 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000263', 'thcs_nguyen_du.9a7.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1498, 1574, NULL, '079311700264', 'Hoàng Khánh Hương', '2011-08-28', 'Nữ', 'Lớp 9A7, số 274 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000264', 'thcs_nguyen_du.9a7.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1499, 1575, NULL, '079211700265', 'Huỳnh Tuấn Hưng', '2011-03-11', 'Nam', 'Lớp 9A7, số 275 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000265', 'thcs_nguyen_du.9a7.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1500, 1576, NULL, '079311700266', 'Phan Khánh Huyền', '2011-10-22', 'Nữ', 'Lớp 9A7, số 276 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000266', 'thcs_nguyen_du.9a7.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1501, 1577, NULL, '079211700267', 'Vũ Tuấn Khoa', '2011-05-05', 'Nam', 'Lớp 9A7, số 277 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000267', 'thcs_nguyen_du.9a7.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1502, 1578, NULL, '079311700268', 'Võ Khánh Lan', '2011-12-16', 'Nữ', 'Lớp 9A7, số 278 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000268', 'thcs_nguyen_du.9a7.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1503, 1579, NULL, '079211700269', 'Đặng Tuấn Khang', '2011-07-27', 'Nam', 'Lớp 9A7, số 279 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000269', 'thcs_nguyen_du.9a7.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1504, 1580, NULL, '079311700270', 'Bùi Khánh Linh', '2011-02-10', 'Nữ', 'Lớp 9A7, số 280 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000270', 'thcs_nguyen_du.9a7.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1505, 1581, NULL, '079211700271', 'Đỗ Tuấn Khôi', '2011-09-21', 'Nam', 'Lớp 9A7, số 281 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000271', 'thcs_nguyen_du.9a7.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1506, 1582, NULL, '079311700272', 'Hồ Khánh Mai', '2011-04-04', 'Nữ', 'Lớp 9A7, số 282 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000272', 'thcs_nguyen_du.9a7.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1507, 1583, NULL, '079211700273', 'Ngô Tuấn Lâm', '2011-11-15', 'Nam', 'Lớp 9A7, số 283 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000273', 'thcs_nguyen_du.9a7.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1508, 1584, NULL, '079311700274', 'Dương Khánh My', '2011-06-26', 'Nữ', 'Lớp 9A7, số 284 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000274', 'thcs_nguyen_du.9a7.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1509, 1585, NULL, '079211700275', 'Lý Tuấn Long', '2011-01-09', 'Nam', 'Lớp 9A7, số 285 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000275', 'thcs_nguyen_du.9a7.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1510, 1586, NULL, '079311700276', 'Đinh Khánh Ngân', '2011-08-20', 'Nữ', 'Lớp 9A7, số 286 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000276', 'thcs_nguyen_du.9a7.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1511, 1587, NULL, '079211700277', 'Đoàn Tuấn Minh', '2011-03-03', 'Nam', 'Lớp 9A7, số 287 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000277', 'thcs_nguyen_du.9a7.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1512, 1588, NULL, '079311700278', 'Lâm Khánh Nhung', '2011-10-14', 'Nữ', 'Lớp 9A7, số 288 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000278', 'thcs_nguyen_du.9a7.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1513, 1589, NULL, '079211700279', 'Trịnh Tuấn Nam', '2011-05-25', 'Nam', 'Lớp 9A7, số 289 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000279', 'thcs_nguyen_du.9a7.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1514, 1590, NULL, '079311700280', 'Nguyễn Khánh Trang', '2011-12-08', 'Nữ', 'Lớp 9A7, số 290 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000280', 'thcs_nguyen_du.9a7.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1515, 1591, NULL, '079211700281', 'Trần Gia An', '2011-08-20', 'Nam', 'Lớp 9A8, số 291 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000281', 'thcs_nguyen_du.9a8.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1516, 1592, NULL, '079311700282', 'Lê Quỳnh Ánh', '2011-03-03', 'Nữ', 'Lớp 9A8, số 292 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000282', 'thcs_nguyen_du.9a8.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1517, 1593, NULL, '079211700283', 'Phạm Gia Bình', '2011-10-14', 'Nam', 'Lớp 9A8, số 293 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000283', 'thcs_nguyen_du.9a8.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1518, 1594, NULL, '079311700284', 'Hoàng Quỳnh Bích', '2011-05-25', 'Nữ', 'Lớp 9A8, số 294 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000284', 'thcs_nguyen_du.9a8.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1519, 1595, NULL, '079211700285', 'Huỳnh Gia Cường', '2011-12-08', 'Nam', 'Lớp 9A8, số 295 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000285', 'thcs_nguyen_du.9a8.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1520, 1596, NULL, '079311700286', 'Phan Quỳnh Chi', '2011-07-19', 'Nữ', 'Lớp 9A8, số 296 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000286', 'thcs_nguyen_du.9a8.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1521, 1597, NULL, '079211700287', 'Vũ Gia Dũng', '2011-02-02', 'Nam', 'Lớp 9A8, số 297 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000287', 'thcs_nguyen_du.9a8.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1522, 1598, NULL, '079311700288', 'Võ Quỳnh Dung', '2011-09-13', 'Nữ', 'Lớp 9A8, số 298 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000288', 'thcs_nguyen_du.9a8.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1523, 1599, NULL, '079211700289', 'Đặng Gia Đạt', '2011-04-24', 'Nam', 'Lớp 9A8, số 299 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000289', 'thcs_nguyen_du.9a8.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1524, 1600, NULL, '079311700290', 'Bùi Quỳnh Duyên', '2011-11-07', 'Nữ', 'Lớp 9A8, số 300 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000290', 'thcs_nguyen_du.9a8.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1525, 1601, NULL, '079211700291', 'Đỗ Gia Duy', '2011-06-18', 'Nam', 'Lớp 9A8, số 301 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000291', 'thcs_nguyen_du.9a8.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1526, 1602, NULL, '079311700292', 'Hồ Quỳnh Giang', '2011-01-01', 'Nữ', 'Lớp 9A8, số 302 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000292', 'thcs_nguyen_du.9a8.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1527, 1603, NULL, '079211700293', 'Ngô Gia Giang', '2011-08-12', 'Nam', 'Lớp 9A8, số 303 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000293', 'thcs_nguyen_du.9a8.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1528, 1604, NULL, '079311700294', 'Dương Quỳnh Hà', '2011-03-23', 'Nữ', 'Lớp 9A8, số 304 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000294', 'thcs_nguyen_du.9a8.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1529, 1605, NULL, '079211700295', 'Lý Gia Hải', '2011-10-06', 'Nam', 'Lớp 9A8, số 305 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000295', 'thcs_nguyen_du.9a8.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1530, 1606, NULL, '079311700296', 'Đinh Quỳnh Hân', '2011-05-17', 'Nữ', 'Lớp 9A8, số 306 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000296', 'thcs_nguyen_du.9a8.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1531, 1607, NULL, '079211700297', 'Đoàn Gia Hiếu', '2011-12-28', 'Nam', 'Lớp 9A8, số 307 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000297', 'thcs_nguyen_du.9a8.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1532, 1608, NULL, '079311700298', 'Lâm Quỳnh Hằng', '2011-07-11', 'Nữ', 'Lớp 9A8, số 308 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000298', 'thcs_nguyen_du.9a8.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1533, 1609, NULL, '079211700299', 'Trịnh Gia Hoàng', '2011-02-22', 'Nam', 'Lớp 9A8, số 309 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000299', 'thcs_nguyen_du.9a8.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1534, 1610, NULL, '079311700300', 'Nguyễn Quỳnh Hạnh', '2011-09-05', 'Nữ', 'Lớp 9A8, số 310 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000300', 'thcs_nguyen_du.9a8.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1535, 1611, NULL, '079211700301', 'Trần Gia Huy', '2011-04-16', 'Nam', 'Lớp 9A8, số 311 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000301', 'thcs_nguyen_du.9a8.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1536, 1612, NULL, '079311700302', 'Lê Quỳnh Hoa', '2011-11-27', 'Nữ', 'Lớp 9A8, số 312 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000302', 'thcs_nguyen_du.9a8.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1537, 1613, NULL, '079211700303', 'Phạm Gia Hùng', '2011-06-10', 'Nam', 'Lớp 9A8, số 313 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000303', 'thcs_nguyen_du.9a8.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1538, 1614, NULL, '079311700304', 'Hoàng Quỳnh Hương', '2011-01-21', 'Nữ', 'Lớp 9A8, số 314 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000304', 'thcs_nguyen_du.9a8.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1539, 1615, NULL, '079211700305', 'Huỳnh Gia Hưng', '2011-08-04', 'Nam', 'Lớp 9A8, số 315 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000305', 'thcs_nguyen_du.9a8.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1540, 1616, NULL, '079311700306', 'Phan Quỳnh Huyền', '2011-03-15', 'Nữ', 'Lớp 9A8, số 316 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000306', 'thcs_nguyen_du.9a8.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1541, 1617, NULL, '079211700307', 'Vũ Gia Khoa', '2011-10-26', 'Nam', 'Lớp 9A8, số 317 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000307', 'thcs_nguyen_du.9a8.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1542, 1618, NULL, '079311700308', 'Võ Quỳnh Lan', '2011-05-09', 'Nữ', 'Lớp 9A8, số 318 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000308', 'thcs_nguyen_du.9a8.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1543, 1619, NULL, '079211700309', 'Đặng Gia Khang', '2011-12-20', 'Nam', 'Lớp 9A8, số 319 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000309', 'thcs_nguyen_du.9a8.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1544, 1620, NULL, '079311700310', 'Bùi Quỳnh Linh', '2011-07-03', 'Nữ', 'Lớp 9A8, số 320 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000310', 'thcs_nguyen_du.9a8.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1545, 1621, NULL, '079211700311', 'Đỗ Gia Khôi', '2011-02-14', 'Nam', 'Lớp 9A8, số 321 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000311', 'thcs_nguyen_du.9a8.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1546, 1622, NULL, '079311700312', 'Hồ Quỳnh Mai', '2011-09-25', 'Nữ', 'Lớp 9A8, số 322 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000312', 'thcs_nguyen_du.9a8.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1547, 1623, NULL, '079211700313', 'Ngô Gia Lâm', '2011-04-08', 'Nam', 'Lớp 9A8, số 323 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000313', 'thcs_nguyen_du.9a8.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1548, 1624, NULL, '079311700314', 'Dương Quỳnh My', '2011-11-19', 'Nữ', 'Lớp 9A8, số 324 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000314', 'thcs_nguyen_du.9a8.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1549, 1625, NULL, '079211700315', 'Lý Gia Long', '2011-06-02', 'Nam', 'Lớp 9A8, số 325 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000315', 'thcs_nguyen_du.9a8.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1550, 1626, NULL, '079311700316', 'Đinh Quỳnh Ngân', '2011-01-13', 'Nữ', 'Lớp 9A8, số 326 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000316', 'thcs_nguyen_du.9a8.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1551, 1627, NULL, '079211700317', 'Đoàn Gia Minh', '2011-08-24', 'Nam', 'Lớp 9A8, số 327 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000317', 'thcs_nguyen_du.9a8.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1552, 1628, NULL, '079311700318', 'Lâm Quỳnh Nhung', '2011-03-07', 'Nữ', 'Lớp 9A8, số 328 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000318', 'thcs_nguyen_du.9a8.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1553, 1629, NULL, '079211700319', 'Trịnh Gia Nam', '2011-10-18', 'Nam', 'Lớp 9A8, số 329 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000319', 'thcs_nguyen_du.9a8.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1554, 1630, NULL, '079311700320', 'Nguyễn Quỳnh Trang', '2011-05-01', 'Nữ', 'Lớp 9A8, số 330 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000320', 'thcs_nguyen_du.9a8.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1555, 1631, NULL, '079211700321', 'Trần Bảo An', '2011-01-13', 'Nam', 'Lớp 9A9, số 331 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000321', 'thcs_nguyen_du.9a9.01@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1556, 1632, NULL, '079311700322', 'Lê Thanh Ánh', '2011-08-24', 'Nữ', 'Lớp 9A9, số 332 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000322', 'thcs_nguyen_du.9a9.02@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1557, 1633, NULL, '079211700323', 'Phạm Bảo Bình', '2011-03-07', 'Nam', 'Lớp 9A9, số 333 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000323', 'thcs_nguyen_du.9a9.03@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1558, 1634, NULL, '079311700324', 'Hoàng Thanh Bích', '2011-10-18', 'Nữ', 'Lớp 9A9, số 334 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000324', 'thcs_nguyen_du.9a9.04@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1559, 1635, NULL, '079211700325', 'Huỳnh Bảo Cường', '2011-05-01', 'Nam', 'Lớp 9A9, số 335 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000325', 'thcs_nguyen_du.9a9.05@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1560, 1636, NULL, '079311700326', 'Phan Thanh Chi', '2011-12-12', 'Nữ', 'Lớp 9A9, số 336 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000326', 'thcs_nguyen_du.9a9.06@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1561, 1637, NULL, '079211700327', 'Vũ Bảo Dũng', '2011-07-23', 'Nam', 'Lớp 9A9, số 337 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000327', 'thcs_nguyen_du.9a9.07@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1562, 1638, NULL, '079311700328', 'Võ Thanh Dung', '2011-02-06', 'Nữ', 'Lớp 9A9, số 338 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000328', 'thcs_nguyen_du.9a9.08@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1563, 1639, NULL, '079211700329', 'Đặng Bảo Đạt', '2011-09-17', 'Nam', 'Lớp 9A9, số 339 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000329', 'thcs_nguyen_du.9a9.09@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1564, 1640, NULL, '079311700330', 'Bùi Thanh Duyên', '2011-04-28', 'Nữ', 'Lớp 9A9, số 340 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000330', 'thcs_nguyen_du.9a9.10@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1565, 1641, NULL, '079211700331', 'Đỗ Bảo Duy', '2011-11-11', 'Nam', 'Lớp 9A9, số 341 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000331', 'thcs_nguyen_du.9a9.11@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1566, 1642, NULL, '079311700332', 'Hồ Thanh Giang', '2011-06-22', 'Nữ', 'Lớp 9A9, số 342 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000332', 'thcs_nguyen_du.9a9.12@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1567, 1643, NULL, '079211700333', 'Ngô Bảo Giang', '2011-01-05', 'Nam', 'Lớp 9A9, số 343 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000333', 'thcs_nguyen_du.9a9.13@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1568, 1644, NULL, '079311700334', 'Dương Thanh Hà', '2011-08-16', 'Nữ', 'Lớp 9A9, số 344 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000334', 'thcs_nguyen_du.9a9.14@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1569, 1645, NULL, '079211700335', 'Lý Bảo Hải', '2011-03-27', 'Nam', 'Lớp 9A9, số 345 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000335', 'thcs_nguyen_du.9a9.15@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1570, 1646, NULL, '079311700336', 'Đinh Thanh Hân', '2011-10-10', 'Nữ', 'Lớp 9A9, số 346 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000336', 'thcs_nguyen_du.9a9.16@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1571, 1647, NULL, '079211700337', 'Đoàn Bảo Hiếu', '2011-05-21', 'Nam', 'Lớp 9A9, số 347 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000337', 'thcs_nguyen_du.9a9.17@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1572, 1648, NULL, '079311700338', 'Lâm Thanh Hằng', '2011-12-04', 'Nữ', 'Lớp 9A9, số 348 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000338', 'thcs_nguyen_du.9a9.18@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1573, 1649, NULL, '079211700339', 'Trịnh Bảo Hoàng', '2011-07-15', 'Nam', 'Lớp 9A9, số 349 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000339', 'thcs_nguyen_du.9a9.19@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1574, 1650, NULL, '079311700340', 'Nguyễn Thanh Hạnh', '2011-02-26', 'Nữ', 'Lớp 9A9, số 350 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000340', 'thcs_nguyen_du.9a9.20@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1575, 1651, NULL, '079211700341', 'Trần Bảo Huy', '2011-09-09', 'Nam', 'Lớp 9A9, số 351 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000341', 'thcs_nguyen_du.9a9.21@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1576, 1652, NULL, '079311700342', 'Lê Thanh Hoa', '2011-04-20', 'Nữ', 'Lớp 9A9, số 352 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000342', 'thcs_nguyen_du.9a9.22@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1577, 1653, NULL, '079211700343', 'Phạm Bảo Hùng', '2011-11-03', 'Nam', 'Lớp 9A9, số 353 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000343', 'thcs_nguyen_du.9a9.23@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed');
INSERT INTO `candidates` (`id`, `user_id`, `candidate_code`, `national_id`, `full_name`, `date_of_birth`, `gender`, `address`, `phone`, `email`, `middle_school_id`, `eligible`, `status`, `exam_number`, `exam_site_name`, `exam_room_name`, `exam_schedule`, `created_at`, `updated_at`, `admission_status`) VALUES
(1578, 1654, NULL, '079311700344', 'Hoàng Thanh Hương', '2011-06-14', 'Nữ', 'Lớp 9A9, số 354 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000344', 'thcs_nguyen_du.9a9.24@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1579, 1655, NULL, '079211700345', 'Huỳnh Bảo Hưng', '2011-01-25', 'Nam', 'Lớp 9A9, số 355 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000345', 'thcs_nguyen_du.9a9.25@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1580, 1656, NULL, '079311700346', 'Phan Thanh Huyền', '2011-08-08', 'Nữ', 'Lớp 9A9, số 356 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000346', 'thcs_nguyen_du.9a9.26@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1581, 1657, NULL, '079211700347', 'Vũ Bảo Khoa', '2011-03-19', 'Nam', 'Lớp 9A9, số 357 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000347', 'thcs_nguyen_du.9a9.27@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1582, 1658, NULL, '079311700348', 'Võ Thanh Lan', '2011-10-02', 'Nữ', 'Lớp 9A9, số 358 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000348', 'thcs_nguyen_du.9a9.28@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1583, 1659, NULL, '079211700349', 'Đặng Bảo Khang', '2011-05-13', 'Nam', 'Lớp 9A9, số 359 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000349', 'thcs_nguyen_du.9a9.29@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1584, 1660, NULL, '079311700350', 'Bùi Thanh Linh', '2011-12-24', 'Nữ', 'Lớp 9A9, số 360 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000350', 'thcs_nguyen_du.9a9.30@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1585, 1661, NULL, '079211700351', 'Đỗ Bảo Khôi', '2011-07-07', 'Nam', 'Lớp 9A9, số 361 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000351', 'thcs_nguyen_du.9a9.31@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1586, 1662, NULL, '079311700352', 'Hồ Thanh Mai', '2011-02-18', 'Nữ', 'Lớp 9A9, số 362 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000352', 'thcs_nguyen_du.9a9.32@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1587, 1663, NULL, '079211700353', 'Ngô Bảo Lâm', '2011-09-01', 'Nam', 'Lớp 9A9, số 363 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000353', 'thcs_nguyen_du.9a9.33@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1588, 1664, NULL, '079311700354', 'Dương Thanh My', '2011-04-12', 'Nữ', 'Lớp 9A9, số 364 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000354', 'thcs_nguyen_du.9a9.34@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1589, 1665, NULL, '079211700355', 'Lý Bảo Long', '2011-11-23', 'Nam', 'Lớp 9A9, số 365 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000355', 'thcs_nguyen_du.9a9.35@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1590, 1666, NULL, '079311700356', 'Đinh Thanh Ngân', '2011-06-06', 'Nữ', 'Lớp 9A9, số 366 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000356', 'thcs_nguyen_du.9a9.36@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1591, 1667, NULL, '079211700357', 'Đoàn Bảo Minh', '2011-01-17', 'Nam', 'Lớp 9A9, số 367 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000357', 'thcs_nguyen_du.9a9.37@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1592, 1668, NULL, '079311700358', 'Lâm Thanh Nhung', '2011-08-28', 'Nữ', 'Lớp 9A9, số 368 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000358', 'thcs_nguyen_du.9a9.38@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1593, 1669, NULL, '079211700359', 'Trịnh Bảo Nam', '2011-03-11', 'Nam', 'Lớp 9A9, số 369 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000359', 'thcs_nguyen_du.9a9.39@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1594, 1670, NULL, '079311700360', 'Nguyễn Thanh Trang', '2011-10-22', 'Nữ', 'Lớp 9A9, số 370 đường Nguyễn Du, phường Bến Thành, TP.HCM', '0960000360', 'thcs_nguyen_du.9a9.40@example.com', 290, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1595, 1671, NULL, '079211700361', 'Võ Hữu An', '2011-09-13', 'Nam', 'Lớp 9A1, số 11 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000361', 'thcs_le_quy_don.9a1.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1596, 1672, NULL, '079311700362', 'Đặng Ngọc Ánh', '2011-04-24', 'Nữ', 'Lớp 9A1, số 12 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000362', 'thcs_le_quy_don.9a1.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1597, 1673, NULL, '079211700363', 'Bùi Hữu Bình', '2011-11-07', 'Nam', 'Lớp 9A1, số 13 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000363', 'thcs_le_quy_don.9a1.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1598, 1674, NULL, '079311700364', 'Đỗ Ngọc Bích', '2011-06-18', 'Nữ', 'Lớp 9A1, số 14 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000364', 'thcs_le_quy_don.9a1.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1599, 1675, NULL, '079211700365', 'Hồ Hữu Cường', '2011-01-01', 'Nam', 'Lớp 9A1, số 15 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000365', 'thcs_le_quy_don.9a1.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1600, 1676, NULL, '079311700366', 'Ngô Ngọc Chi', '2011-08-12', 'Nữ', 'Lớp 9A1, số 16 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000366', 'thcs_le_quy_don.9a1.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1601, 1677, NULL, '079211700367', 'Dương Hữu Dũng', '2011-03-23', 'Nam', 'Lớp 9A1, số 17 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000367', 'thcs_le_quy_don.9a1.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1602, 1678, NULL, '079311700368', 'Lý Ngọc Dung', '2011-10-06', 'Nữ', 'Lớp 9A1, số 18 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000368', 'thcs_le_quy_don.9a1.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1603, 1679, NULL, '079211700369', 'Đinh Hữu Đạt', '2011-05-17', 'Nam', 'Lớp 9A1, số 19 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000369', 'thcs_le_quy_don.9a1.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1604, 1680, NULL, '079311700370', 'Đoàn Ngọc Duyên', '2011-12-28', 'Nữ', 'Lớp 9A1, số 20 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000370', 'thcs_le_quy_don.9a1.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1605, 1681, NULL, '079211700371', 'Lâm Hữu Duy', '2011-07-11', 'Nam', 'Lớp 9A1, số 21 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000371', 'thcs_le_quy_don.9a1.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1606, 1682, NULL, '079311700372', 'Trịnh Ngọc Giang', '2011-02-22', 'Nữ', 'Lớp 9A1, số 22 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000372', 'thcs_le_quy_don.9a1.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1607, 1683, NULL, '079211700373', 'Nguyễn Hữu Giang', '2011-09-05', 'Nam', 'Lớp 9A1, số 23 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000373', 'thcs_le_quy_don.9a1.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1608, 1684, NULL, '079311700374', 'Trần Ngọc Hà', '2011-04-16', 'Nữ', 'Lớp 9A1, số 24 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000374', 'thcs_le_quy_don.9a1.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1609, 1685, NULL, '079211700375', 'Lê Hữu Hải', '2011-11-27', 'Nam', 'Lớp 9A1, số 25 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000375', 'thcs_le_quy_don.9a1.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1610, 1686, NULL, '079311700376', 'Phạm Ngọc Hân', '2011-06-10', 'Nữ', 'Lớp 9A1, số 26 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000376', 'thcs_le_quy_don.9a1.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1611, 1687, NULL, '079211700377', 'Hoàng Hữu Hiếu', '2011-01-21', 'Nam', 'Lớp 9A1, số 27 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000377', 'thcs_le_quy_don.9a1.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1612, 1688, NULL, '079311700378', 'Huỳnh Ngọc Hằng', '2011-08-04', 'Nữ', 'Lớp 9A1, số 28 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000378', 'thcs_le_quy_don.9a1.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1613, 1689, NULL, '079211700379', 'Phan Hữu Hoàng', '2011-03-15', 'Nam', 'Lớp 9A1, số 29 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000379', 'thcs_le_quy_don.9a1.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1614, 1690, NULL, '079311700380', 'Vũ Ngọc Hạnh', '2011-10-26', 'Nữ', 'Lớp 9A1, số 30 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000380', 'thcs_le_quy_don.9a1.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1615, 1691, NULL, '079211700381', 'Võ Hữu Huy', '2011-05-09', 'Nam', 'Lớp 9A1, số 31 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000381', 'thcs_le_quy_don.9a1.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1616, 1692, NULL, '079311700382', 'Đặng Ngọc Hoa', '2011-12-20', 'Nữ', 'Lớp 9A1, số 32 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000382', 'thcs_le_quy_don.9a1.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1617, 1693, NULL, '079211700383', 'Bùi Hữu Hùng', '2011-07-03', 'Nam', 'Lớp 9A1, số 33 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000383', 'thcs_le_quy_don.9a1.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1618, 1694, NULL, '079311700384', 'Đỗ Ngọc Hương', '2011-02-14', 'Nữ', 'Lớp 9A1, số 34 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000384', 'thcs_le_quy_don.9a1.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1619, 1695, NULL, '079211700385', 'Hồ Hữu Hưng', '2011-09-25', 'Nam', 'Lớp 9A1, số 35 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000385', 'thcs_le_quy_don.9a1.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1620, 1696, NULL, '079311700386', 'Ngô Ngọc Huyền', '2011-04-08', 'Nữ', 'Lớp 9A1, số 36 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000386', 'thcs_le_quy_don.9a1.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1621, 1697, NULL, '079211700387', 'Dương Hữu Khoa', '2011-11-19', 'Nam', 'Lớp 9A1, số 37 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000387', 'thcs_le_quy_don.9a1.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1622, 1698, NULL, '079311700388', 'Lý Ngọc Lan', '2011-06-02', 'Nữ', 'Lớp 9A1, số 38 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000388', 'thcs_le_quy_don.9a1.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1623, 1699, NULL, '079211700389', 'Đinh Hữu Khang', '2011-01-13', 'Nam', 'Lớp 9A1, số 39 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000389', 'thcs_le_quy_don.9a1.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1624, 1700, NULL, '079311700390', 'Đoàn Ngọc Linh', '2011-08-24', 'Nữ', 'Lớp 9A1, số 40 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000390', 'thcs_le_quy_don.9a1.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1625, 1701, NULL, '079211700391', 'Lâm Hữu Khôi', '2011-03-07', 'Nam', 'Lớp 9A1, số 41 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000391', 'thcs_le_quy_don.9a1.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1626, 1702, NULL, '079311700392', 'Trịnh Ngọc Mai', '2011-10-18', 'Nữ', 'Lớp 9A1, số 42 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000392', 'thcs_le_quy_don.9a1.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1627, 1703, NULL, '079211700393', 'Nguyễn Hữu Lâm', '2011-05-01', 'Nam', 'Lớp 9A1, số 43 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000393', 'thcs_le_quy_don.9a1.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1628, 1704, NULL, '079311700394', 'Trần Ngọc My', '2011-12-12', 'Nữ', 'Lớp 9A1, số 44 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000394', 'thcs_le_quy_don.9a1.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1629, 1705, NULL, '079211700395', 'Lê Hữu Long', '2011-07-23', 'Nam', 'Lớp 9A1, số 45 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000395', 'thcs_le_quy_don.9a1.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1630, 1706, NULL, '079311700396', 'Phạm Ngọc Ngân', '2011-02-06', 'Nữ', 'Lớp 9A1, số 46 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000396', 'thcs_le_quy_don.9a1.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1631, 1707, NULL, '079211700397', 'Hoàng Hữu Minh', '2011-09-17', 'Nam', 'Lớp 9A1, số 47 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000397', 'thcs_le_quy_don.9a1.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1632, 1708, NULL, '079311700398', 'Huỳnh Ngọc Nhung', '2011-04-28', 'Nữ', 'Lớp 9A1, số 48 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000398', 'thcs_le_quy_don.9a1.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1633, 1709, NULL, '079211700399', 'Phan Hữu Nam', '2011-11-11', 'Nam', 'Lớp 9A1, số 49 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000399', 'thcs_le_quy_don.9a1.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1634, 1710, NULL, '079311700400', 'Vũ Ngọc Trang', '2011-06-22', 'Nữ', 'Lớp 9A1, số 50 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000400', 'thcs_le_quy_don.9a1.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1635, 1711, NULL, '079211700401', 'Võ Đức An', '2011-02-06', 'Nam', 'Lớp 9A2, số 51 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000401', 'thcs_le_quy_don.9a2.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1636, 1712, NULL, '079311700402', 'Đặng Kim Ánh', '2011-09-17', 'Nữ', 'Lớp 9A2, số 52 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000402', 'thcs_le_quy_don.9a2.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1637, 1713, NULL, '079211700403', 'Bùi Đức Bình', '2011-04-28', 'Nam', 'Lớp 9A2, số 53 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000403', 'thcs_le_quy_don.9a2.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1638, 1714, NULL, '079311700404', 'Đỗ Kim Bích', '2011-11-11', 'Nữ', 'Lớp 9A2, số 54 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000404', 'thcs_le_quy_don.9a2.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1639, 1715, NULL, '079211700405', 'Hồ Đức Cường', '2011-06-22', 'Nam', 'Lớp 9A2, số 55 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000405', 'thcs_le_quy_don.9a2.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1640, 1716, NULL, '079311700406', 'Ngô Kim Chi', '2011-01-05', 'Nữ', 'Lớp 9A2, số 56 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000406', 'thcs_le_quy_don.9a2.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1641, 1717, NULL, '079211700407', 'Dương Đức Dũng', '2011-08-16', 'Nam', 'Lớp 9A2, số 57 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000407', 'thcs_le_quy_don.9a2.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1642, 1718, NULL, '079311700408', 'Lý Kim Dung', '2011-03-27', 'Nữ', 'Lớp 9A2, số 58 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000408', 'thcs_le_quy_don.9a2.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1643, 1719, NULL, '079211700409', 'Đinh Đức Đạt', '2011-10-10', 'Nam', 'Lớp 9A2, số 59 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000409', 'thcs_le_quy_don.9a2.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1644, 1720, NULL, '079311700410', 'Đoàn Kim Duyên', '2011-05-21', 'Nữ', 'Lớp 9A2, số 60 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000410', 'thcs_le_quy_don.9a2.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1645, 1721, NULL, '079211700411', 'Lâm Đức Duy', '2011-12-04', 'Nam', 'Lớp 9A2, số 61 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000411', 'thcs_le_quy_don.9a2.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1646, 1722, NULL, '079311700412', 'Trịnh Kim Giang', '2011-07-15', 'Nữ', 'Lớp 9A2, số 62 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000412', 'thcs_le_quy_don.9a2.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1647, 1723, NULL, '079211700413', 'Nguyễn Đức Giang', '2011-02-26', 'Nam', 'Lớp 9A2, số 63 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000413', 'thcs_le_quy_don.9a2.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1648, 1724, NULL, '079311700414', 'Trần Kim Hà', '2011-09-09', 'Nữ', 'Lớp 9A2, số 64 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000414', 'thcs_le_quy_don.9a2.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1649, 1725, NULL, '079211700415', 'Lê Đức Hải', '2011-04-20', 'Nam', 'Lớp 9A2, số 65 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000415', 'thcs_le_quy_don.9a2.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1650, 1726, NULL, '079311700416', 'Phạm Kim Hân', '2011-11-03', 'Nữ', 'Lớp 9A2, số 66 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000416', 'thcs_le_quy_don.9a2.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1651, 1727, NULL, '079211700417', 'Hoàng Đức Hiếu', '2011-06-14', 'Nam', 'Lớp 9A2, số 67 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000417', 'thcs_le_quy_don.9a2.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1652, 1728, NULL, '079311700418', 'Huỳnh Kim Hằng', '2011-01-25', 'Nữ', 'Lớp 9A2, số 68 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000418', 'thcs_le_quy_don.9a2.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1653, 1729, NULL, '079211700419', 'Phan Đức Hoàng', '2011-08-08', 'Nam', 'Lớp 9A2, số 69 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000419', 'thcs_le_quy_don.9a2.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1654, 1730, NULL, '079311700420', 'Vũ Kim Hạnh', '2011-03-19', 'Nữ', 'Lớp 9A2, số 70 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000420', 'thcs_le_quy_don.9a2.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1655, 1731, NULL, '079211700421', 'Võ Đức Huy', '2011-10-02', 'Nam', 'Lớp 9A2, số 71 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000421', 'thcs_le_quy_don.9a2.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1656, 1732, NULL, '079311700422', 'Đặng Kim Hoa', '2011-05-13', 'Nữ', 'Lớp 9A2, số 72 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000422', 'thcs_le_quy_don.9a2.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1657, 1733, NULL, '079211700423', 'Bùi Đức Hùng', '2011-12-24', 'Nam', 'Lớp 9A2, số 73 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000423', 'thcs_le_quy_don.9a2.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1658, 1734, NULL, '079311700424', 'Đỗ Kim Hương', '2011-07-07', 'Nữ', 'Lớp 9A2, số 74 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000424', 'thcs_le_quy_don.9a2.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1659, 1735, NULL, '079211700425', 'Hồ Đức Hưng', '2011-02-18', 'Nam', 'Lớp 9A2, số 75 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000425', 'thcs_le_quy_don.9a2.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1660, 1736, NULL, '079311700426', 'Ngô Kim Huyền', '2011-09-01', 'Nữ', 'Lớp 9A2, số 76 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000426', 'thcs_le_quy_don.9a2.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1661, 1737, NULL, '079211700427', 'Dương Đức Khoa', '2011-04-12', 'Nam', 'Lớp 9A2, số 77 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000427', 'thcs_le_quy_don.9a2.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12', 'not_processed'),
(1662, 1738, NULL, '079311700428', 'Lý Kim Lan', '2011-11-23', 'Nữ', 'Lớp 9A2, số 78 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000428', 'thcs_le_quy_don.9a2.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:13', 'not_processed'),
(1663, 1739, NULL, '079211700429', 'Đinh Đức Khang', '2011-06-06', 'Nam', 'Lớp 9A2, số 79 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000429', 'thcs_le_quy_don.9a2.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1664, 1740, NULL, '079311700430', 'Đoàn Kim Linh', '2011-01-17', 'Nữ', 'Lớp 9A2, số 80 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000430', 'thcs_le_quy_don.9a2.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1665, 1741, NULL, '079211700431', 'Lâm Đức Khôi', '2011-08-28', 'Nam', 'Lớp 9A2, số 81 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000431', 'thcs_le_quy_don.9a2.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1666, 1742, NULL, '079311700432', 'Trịnh Kim Mai', '2011-03-11', 'Nữ', 'Lớp 9A2, số 82 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000432', 'thcs_le_quy_don.9a2.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1667, 1743, NULL, '079211700433', 'Nguyễn Đức Lâm', '2011-10-22', 'Nam', 'Lớp 9A2, số 83 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000433', 'thcs_le_quy_don.9a2.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1668, 1744, NULL, '079311700434', 'Trần Kim My', '2011-05-05', 'Nữ', 'Lớp 9A2, số 84 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000434', 'thcs_le_quy_don.9a2.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1669, 1745, NULL, '079211700435', 'Lê Đức Long', '2011-12-16', 'Nam', 'Lớp 9A2, số 85 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000435', 'thcs_le_quy_don.9a2.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1670, 1746, NULL, '079311700436', 'Phạm Kim Ngân', '2011-07-27', 'Nữ', 'Lớp 9A2, số 86 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000436', 'thcs_le_quy_don.9a2.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1671, 1747, NULL, '079211700437', 'Hoàng Đức Minh', '2011-02-10', 'Nam', 'Lớp 9A2, số 87 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000437', 'thcs_le_quy_don.9a2.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1672, 1748, NULL, '079311700438', 'Huỳnh Kim Nhung', '2011-09-21', 'Nữ', 'Lớp 9A2, số 88 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000438', 'thcs_le_quy_don.9a2.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1673, 1749, NULL, '079211700439', 'Phan Đức Nam', '2011-04-04', 'Nam', 'Lớp 9A2, số 89 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000439', 'thcs_le_quy_don.9a2.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1674, 1750, NULL, '079311700440', 'Vũ Kim Trang', '2011-11-15', 'Nữ', 'Lớp 9A2, số 90 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000440', 'thcs_le_quy_don.9a2.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1675, 1751, NULL, '079211700441', 'Võ Đình An', '2011-07-27', 'Nam', 'Lớp 9A3, số 91 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000441', 'thcs_le_quy_don.9a3.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1676, 1752, NULL, '079311700442', 'Đặng Mai Ánh', '2011-02-10', 'Nữ', 'Lớp 9A3, số 92 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000442', 'thcs_le_quy_don.9a3.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1677, 1753, NULL, '079211700443', 'Bùi Đình Bình', '2011-09-21', 'Nam', 'Lớp 9A3, số 93 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000443', 'thcs_le_quy_don.9a3.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1678, 1754, NULL, '079311700444', 'Đỗ Mai Bích', '2011-04-04', 'Nữ', 'Lớp 9A3, số 94 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000444', 'thcs_le_quy_don.9a3.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1679, 1755, NULL, '079211700445', 'Hồ Đình Cường', '2011-11-15', 'Nam', 'Lớp 9A3, số 95 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000445', 'thcs_le_quy_don.9a3.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1680, 1756, NULL, '079311700446', 'Ngô Mai Chi', '2011-06-26', 'Nữ', 'Lớp 9A3, số 96 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000446', 'thcs_le_quy_don.9a3.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1681, 1757, NULL, '079211700447', 'Dương Đình Dũng', '2011-01-09', 'Nam', 'Lớp 9A3, số 97 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000447', 'thcs_le_quy_don.9a3.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1682, 1758, NULL, '079311700448', 'Lý Mai Dung', '2011-08-20', 'Nữ', 'Lớp 9A3, số 98 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000448', 'thcs_le_quy_don.9a3.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1683, 1759, NULL, '079211700449', 'Đinh Đình Đạt', '2011-03-03', 'Nam', 'Lớp 9A3, số 99 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000449', 'thcs_le_quy_don.9a3.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1684, 1760, NULL, '079311700450', 'Đoàn Mai Duyên', '2011-10-14', 'Nữ', 'Lớp 9A3, số 100 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000450', 'thcs_le_quy_don.9a3.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1685, 1761, NULL, '079211700451', 'Lâm Đình Duy', '2011-05-25', 'Nam', 'Lớp 9A3, số 101 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000451', 'thcs_le_quy_don.9a3.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1686, 1762, NULL, '079311700452', 'Trịnh Mai Giang', '2011-12-08', 'Nữ', 'Lớp 9A3, số 102 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000452', 'thcs_le_quy_don.9a3.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1687, 1763, NULL, '079211700453', 'Nguyễn Đình Giang', '2011-07-19', 'Nam', 'Lớp 9A3, số 103 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000453', 'thcs_le_quy_don.9a3.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1688, 1764, NULL, '079311700454', 'Trần Mai Hà', '2011-02-02', 'Nữ', 'Lớp 9A3, số 104 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000454', 'thcs_le_quy_don.9a3.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1689, 1765, NULL, '079211700455', 'Lê Đình Hải', '2011-09-13', 'Nam', 'Lớp 9A3, số 105 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000455', 'thcs_le_quy_don.9a3.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1690, 1766, NULL, '079311700456', 'Phạm Mai Hân', '2011-04-24', 'Nữ', 'Lớp 9A3, số 106 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000456', 'thcs_le_quy_don.9a3.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1691, 1767, NULL, '079211700457', 'Hoàng Đình Hiếu', '2011-11-07', 'Nam', 'Lớp 9A3, số 107 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000457', 'thcs_le_quy_don.9a3.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1692, 1768, NULL, '079311700458', 'Huỳnh Mai Hằng', '2011-06-18', 'Nữ', 'Lớp 9A3, số 108 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000458', 'thcs_le_quy_don.9a3.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1693, 1769, NULL, '079211700459', 'Phan Đình Hoàng', '2011-01-01', 'Nam', 'Lớp 9A3, số 109 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000459', 'thcs_le_quy_don.9a3.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1694, 1770, NULL, '079311700460', 'Vũ Mai Hạnh', '2011-08-12', 'Nữ', 'Lớp 9A3, số 110 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000460', 'thcs_le_quy_don.9a3.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1695, 1771, NULL, '079211700461', 'Võ Đình Huy', '2011-03-23', 'Nam', 'Lớp 9A3, số 111 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000461', 'thcs_le_quy_don.9a3.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1696, 1772, NULL, '079311700462', 'Đặng Mai Hoa', '2011-10-06', 'Nữ', 'Lớp 9A3, số 112 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000462', 'thcs_le_quy_don.9a3.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1697, 1773, NULL, '079211700463', 'Bùi Đình Hùng', '2011-05-17', 'Nam', 'Lớp 9A3, số 113 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000463', 'thcs_le_quy_don.9a3.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1698, 1774, NULL, '079311700464', 'Đỗ Mai Hương', '2011-12-28', 'Nữ', 'Lớp 9A3, số 114 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000464', 'thcs_le_quy_don.9a3.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1699, 1775, NULL, '079211700465', 'Hồ Đình Hưng', '2011-07-11', 'Nam', 'Lớp 9A3, số 115 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000465', 'thcs_le_quy_don.9a3.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1700, 1776, NULL, '079311700466', 'Ngô Mai Huyền', '2011-02-22', 'Nữ', 'Lớp 9A3, số 116 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000466', 'thcs_le_quy_don.9a3.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1701, 1777, NULL, '079211700467', 'Dương Đình Khoa', '2011-09-05', 'Nam', 'Lớp 9A3, số 117 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000467', 'thcs_le_quy_don.9a3.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1702, 1778, NULL, '079311700468', 'Lý Mai Lan', '2011-04-16', 'Nữ', 'Lớp 9A3, số 118 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000468', 'thcs_le_quy_don.9a3.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1703, 1779, NULL, '079211700469', 'Đinh Đình Khang', '2011-11-27', 'Nam', 'Lớp 9A3, số 119 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000469', 'thcs_le_quy_don.9a3.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1704, 1780, NULL, '079311700470', 'Đoàn Mai Linh', '2011-06-10', 'Nữ', 'Lớp 9A3, số 120 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000470', 'thcs_le_quy_don.9a3.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1705, 1781, NULL, '079211700471', 'Lâm Đình Khôi', '2011-01-21', 'Nam', 'Lớp 9A3, số 121 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000471', 'thcs_le_quy_don.9a3.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1706, 1782, NULL, '079311700472', 'Trịnh Mai Mai', '2011-08-04', 'Nữ', 'Lớp 9A3, số 122 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000472', 'thcs_le_quy_don.9a3.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1707, 1783, NULL, '079211700473', 'Nguyễn Đình Lâm', '2011-03-15', 'Nam', 'Lớp 9A3, số 123 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000473', 'thcs_le_quy_don.9a3.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1708, 1784, NULL, '079311700474', 'Trần Mai My', '2011-10-26', 'Nữ', 'Lớp 9A3, số 124 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000474', 'thcs_le_quy_don.9a3.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1709, 1785, NULL, '079211700475', 'Lê Đình Long', '2011-05-09', 'Nam', 'Lớp 9A3, số 125 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000475', 'thcs_le_quy_don.9a3.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1710, 1786, NULL, '079311700476', 'Phạm Mai Ngân', '2011-12-20', 'Nữ', 'Lớp 9A3, số 126 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000476', 'thcs_le_quy_don.9a3.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1711, 1787, NULL, '079211700477', 'Hoàng Đình Minh', '2011-07-03', 'Nam', 'Lớp 9A3, số 127 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000477', 'thcs_le_quy_don.9a3.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1712, 1788, NULL, '079311700478', 'Huỳnh Mai Nhung', '2011-02-14', 'Nữ', 'Lớp 9A3, số 128 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000478', 'thcs_le_quy_don.9a3.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1713, 1789, NULL, '079211700479', 'Phan Đình Nam', '2011-09-25', 'Nam', 'Lớp 9A3, số 129 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000479', 'thcs_le_quy_don.9a3.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1714, 1790, NULL, '079311700480', 'Vũ Mai Trang', '2011-04-08', 'Nữ', 'Lớp 9A3, số 130 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000480', 'thcs_le_quy_don.9a3.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1715, 1791, NULL, '079211700481', 'Võ Quang An', '2011-12-20', 'Nam', 'Lớp 9A4, số 131 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000481', 'thcs_le_quy_don.9a4.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1716, 1792, NULL, '079311700482', 'Đặng Phương Ánh', '2011-07-03', 'Nữ', 'Lớp 9A4, số 132 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000482', 'thcs_le_quy_don.9a4.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1717, 1793, NULL, '079211700483', 'Bùi Quang Bình', '2011-02-14', 'Nam', 'Lớp 9A4, số 133 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000483', 'thcs_le_quy_don.9a4.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1718, 1794, NULL, '079311700484', 'Đỗ Phương Bích', '2011-09-25', 'Nữ', 'Lớp 9A4, số 134 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000484', 'thcs_le_quy_don.9a4.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1719, 1795, NULL, '079211700485', 'Hồ Quang Cường', '2011-04-08', 'Nam', 'Lớp 9A4, số 135 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000485', 'thcs_le_quy_don.9a4.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1720, 1796, NULL, '079311700486', 'Ngô Phương Chi', '2011-11-19', 'Nữ', 'Lớp 9A4, số 136 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000486', 'thcs_le_quy_don.9a4.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1721, 1797, NULL, '079211700487', 'Dương Quang Dũng', '2011-06-02', 'Nam', 'Lớp 9A4, số 137 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000487', 'thcs_le_quy_don.9a4.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1722, 1798, NULL, '079311700488', 'Lý Phương Dung', '2011-01-13', 'Nữ', 'Lớp 9A4, số 138 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000488', 'thcs_le_quy_don.9a4.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1723, 1799, NULL, '079211700489', 'Đinh Quang Đạt', '2011-08-24', 'Nam', 'Lớp 9A4, số 139 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000489', 'thcs_le_quy_don.9a4.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1724, 1800, NULL, '079311700490', 'Đoàn Phương Duyên', '2011-03-07', 'Nữ', 'Lớp 9A4, số 140 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000490', 'thcs_le_quy_don.9a4.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1725, 1801, NULL, '079211700491', 'Lâm Quang Duy', '2011-10-18', 'Nam', 'Lớp 9A4, số 141 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000491', 'thcs_le_quy_don.9a4.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1726, 1802, NULL, '079311700492', 'Trịnh Phương Giang', '2011-05-01', 'Nữ', 'Lớp 9A4, số 142 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000492', 'thcs_le_quy_don.9a4.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1727, 1803, NULL, '079211700493', 'Nguyễn Quang Giang', '2011-12-12', 'Nam', 'Lớp 9A4, số 143 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000493', 'thcs_le_quy_don.9a4.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1728, 1804, NULL, '079311700494', 'Trần Phương Hà', '2011-07-23', 'Nữ', 'Lớp 9A4, số 144 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000494', 'thcs_le_quy_don.9a4.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1729, 1805, NULL, '079211700495', 'Lê Quang Hải', '2011-02-06', 'Nam', 'Lớp 9A4, số 145 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000495', 'thcs_le_quy_don.9a4.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1730, 1806, NULL, '079311700496', 'Phạm Phương Hân', '2011-09-17', 'Nữ', 'Lớp 9A4, số 146 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000496', 'thcs_le_quy_don.9a4.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1731, 1807, NULL, '079211700497', 'Hoàng Quang Hiếu', '2011-04-28', 'Nam', 'Lớp 9A4, số 147 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000497', 'thcs_le_quy_don.9a4.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1732, 1808, NULL, '079311700498', 'Huỳnh Phương Hằng', '2011-11-11', 'Nữ', 'Lớp 9A4, số 148 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000498', 'thcs_le_quy_don.9a4.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1733, 1809, NULL, '079211700499', 'Phan Quang Hoàng', '2011-06-22', 'Nam', 'Lớp 9A4, số 149 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000499', 'thcs_le_quy_don.9a4.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1734, 1810, NULL, '079311700500', 'Vũ Phương Hạnh', '2011-01-05', 'Nữ', 'Lớp 9A4, số 150 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000500', 'thcs_le_quy_don.9a4.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1735, 1811, NULL, '079211700501', 'Võ Quang Huy', '2011-08-16', 'Nam', 'Lớp 9A4, số 151 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000501', 'thcs_le_quy_don.9a4.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1736, 1812, NULL, '079311700502', 'Đặng Phương Hoa', '2011-03-27', 'Nữ', 'Lớp 9A4, số 152 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000502', 'thcs_le_quy_don.9a4.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1737, 1813, NULL, '079211700503', 'Bùi Quang Hùng', '2011-10-10', 'Nam', 'Lớp 9A4, số 153 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000503', 'thcs_le_quy_don.9a4.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1738, 1814, NULL, '079311700504', 'Đỗ Phương Hương', '2011-05-21', 'Nữ', 'Lớp 9A4, số 154 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000504', 'thcs_le_quy_don.9a4.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1739, 1815, NULL, '079211700505', 'Hồ Quang Hưng', '2011-12-04', 'Nam', 'Lớp 9A4, số 155 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000505', 'thcs_le_quy_don.9a4.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1740, 1816, NULL, '079311700506', 'Ngô Phương Huyền', '2011-07-15', 'Nữ', 'Lớp 9A4, số 156 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000506', 'thcs_le_quy_don.9a4.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1741, 1817, NULL, '079211700507', 'Dương Quang Khoa', '2011-02-26', 'Nam', 'Lớp 9A4, số 157 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000507', 'thcs_le_quy_don.9a4.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1742, 1818, NULL, '079311700508', 'Lý Phương Lan', '2011-09-09', 'Nữ', 'Lớp 9A4, số 158 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000508', 'thcs_le_quy_don.9a4.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1743, 1819, NULL, '079211700509', 'Đinh Quang Khang', '2011-04-20', 'Nam', 'Lớp 9A4, số 159 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000509', 'thcs_le_quy_don.9a4.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1744, 1820, NULL, '079311700510', 'Đoàn Phương Linh', '2011-11-03', 'Nữ', 'Lớp 9A4, số 160 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000510', 'thcs_le_quy_don.9a4.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1745, 1821, NULL, '079211700511', 'Lâm Quang Khôi', '2011-06-14', 'Nam', 'Lớp 9A4, số 161 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000511', 'thcs_le_quy_don.9a4.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1746, 1822, NULL, '079311700512', 'Trịnh Phương Mai', '2011-01-25', 'Nữ', 'Lớp 9A4, số 162 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000512', 'thcs_le_quy_don.9a4.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1747, 1823, NULL, '079211700513', 'Nguyễn Quang Lâm', '2011-08-08', 'Nam', 'Lớp 9A4, số 163 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000513', 'thcs_le_quy_don.9a4.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1748, 1824, NULL, '079311700514', 'Trần Phương My', '2011-03-19', 'Nữ', 'Lớp 9A4, số 164 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000514', 'thcs_le_quy_don.9a4.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed');
INSERT INTO `candidates` (`id`, `user_id`, `candidate_code`, `national_id`, `full_name`, `date_of_birth`, `gender`, `address`, `phone`, `email`, `middle_school_id`, `eligible`, `status`, `exam_number`, `exam_site_name`, `exam_room_name`, `exam_schedule`, `created_at`, `updated_at`, `admission_status`) VALUES
(1749, 1825, NULL, '079211700515', 'Lê Quang Long', '2011-10-02', 'Nam', 'Lớp 9A4, số 165 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000515', 'thcs_le_quy_don.9a4.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1750, 1826, NULL, '079311700516', 'Phạm Phương Ngân', '2011-05-13', 'Nữ', 'Lớp 9A4, số 166 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000516', 'thcs_le_quy_don.9a4.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1751, 1827, NULL, '079211700517', 'Hoàng Quang Minh', '2011-12-24', 'Nam', 'Lớp 9A4, số 167 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000517', 'thcs_le_quy_don.9a4.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1752, 1828, NULL, '079311700518', 'Huỳnh Phương Nhung', '2011-07-07', 'Nữ', 'Lớp 9A4, số 168 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000518', 'thcs_le_quy_don.9a4.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1753, 1829, NULL, '079211700519', 'Phan Quang Nam', '2011-02-18', 'Nam', 'Lớp 9A4, số 169 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000519', 'thcs_le_quy_don.9a4.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1754, 1830, NULL, '079311700520', 'Vũ Phương Trang', '2011-09-01', 'Nữ', 'Lớp 9A4, số 170 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000520', 'thcs_le_quy_don.9a4.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1755, 1831, NULL, '079211700521', 'Võ Minh An', '2011-05-13', 'Nam', 'Lớp 9A5, số 171 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000521', 'thcs_le_quy_don.9a5.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1756, 1832, NULL, '079311700522', 'Đặng Thảo Ánh', '2011-12-24', 'Nữ', 'Lớp 9A5, số 172 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000522', 'thcs_le_quy_don.9a5.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1757, 1833, NULL, '079211700523', 'Bùi Minh Bình', '2011-07-07', 'Nam', 'Lớp 9A5, số 173 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000523', 'thcs_le_quy_don.9a5.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1758, 1834, NULL, '079311700524', 'Đỗ Thảo Bích', '2011-02-18', 'Nữ', 'Lớp 9A5, số 174 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000524', 'thcs_le_quy_don.9a5.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1759, 1835, NULL, '079211700525', 'Hồ Minh Cường', '2011-09-01', 'Nam', 'Lớp 9A5, số 175 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000525', 'thcs_le_quy_don.9a5.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1760, 1836, NULL, '079311700526', 'Ngô Thảo Chi', '2011-04-12', 'Nữ', 'Lớp 9A5, số 176 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000526', 'thcs_le_quy_don.9a5.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1761, 1837, NULL, '079211700527', 'Dương Minh Dũng', '2011-11-23', 'Nam', 'Lớp 9A5, số 177 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000527', 'thcs_le_quy_don.9a5.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1762, 1838, NULL, '079311700528', 'Lý Thảo Dung', '2011-06-06', 'Nữ', 'Lớp 9A5, số 178 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000528', 'thcs_le_quy_don.9a5.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1763, 1839, NULL, '079211700529', 'Đinh Minh Đạt', '2011-01-17', 'Nam', 'Lớp 9A5, số 179 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000529', 'thcs_le_quy_don.9a5.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1764, 1840, NULL, '079311700530', 'Đoàn Thảo Duyên', '2011-08-28', 'Nữ', 'Lớp 9A5, số 180 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000530', 'thcs_le_quy_don.9a5.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1765, 1841, NULL, '079211700531', 'Lâm Minh Duy', '2011-03-11', 'Nam', 'Lớp 9A5, số 181 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000531', 'thcs_le_quy_don.9a5.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1766, 1842, NULL, '079311700532', 'Trịnh Thảo Giang', '2011-10-22', 'Nữ', 'Lớp 9A5, số 182 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000532', 'thcs_le_quy_don.9a5.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1767, 1843, NULL, '079211700533', 'Nguyễn Minh Giang', '2011-05-05', 'Nam', 'Lớp 9A5, số 183 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000533', 'thcs_le_quy_don.9a5.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1768, 1844, NULL, '079311700534', 'Trần Thảo Hà', '2011-12-16', 'Nữ', 'Lớp 9A5, số 184 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000534', 'thcs_le_quy_don.9a5.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1769, 1845, NULL, '079211700535', 'Lê Minh Hải', '2011-07-27', 'Nam', 'Lớp 9A5, số 185 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000535', 'thcs_le_quy_don.9a5.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1770, 1846, NULL, '079311700536', 'Phạm Thảo Hân', '2011-02-10', 'Nữ', 'Lớp 9A5, số 186 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000536', 'thcs_le_quy_don.9a5.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1771, 1847, NULL, '079211700537', 'Hoàng Minh Hiếu', '2011-09-21', 'Nam', 'Lớp 9A5, số 187 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000537', 'thcs_le_quy_don.9a5.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1772, 1848, NULL, '079311700538', 'Huỳnh Thảo Hằng', '2011-04-04', 'Nữ', 'Lớp 9A5, số 188 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000538', 'thcs_le_quy_don.9a5.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1773, 1849, NULL, '079211700539', 'Phan Minh Hoàng', '2011-11-15', 'Nam', 'Lớp 9A5, số 189 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000539', 'thcs_le_quy_don.9a5.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1774, 1850, NULL, '079311700540', 'Vũ Thảo Hạnh', '2011-06-26', 'Nữ', 'Lớp 9A5, số 190 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000540', 'thcs_le_quy_don.9a5.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1775, 1851, NULL, '079211700541', 'Võ Minh Huy', '2011-01-09', 'Nam', 'Lớp 9A5, số 191 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000541', 'thcs_le_quy_don.9a5.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1776, 1852, NULL, '079311700542', 'Đặng Thảo Hoa', '2011-08-20', 'Nữ', 'Lớp 9A5, số 192 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000542', 'thcs_le_quy_don.9a5.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1777, 1853, NULL, '079211700543', 'Bùi Minh Hùng', '2011-03-03', 'Nam', 'Lớp 9A5, số 193 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000543', 'thcs_le_quy_don.9a5.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1778, 1854, NULL, '079311700544', 'Đỗ Thảo Hương', '2011-10-14', 'Nữ', 'Lớp 9A5, số 194 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000544', 'thcs_le_quy_don.9a5.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1779, 1855, NULL, '079211700545', 'Hồ Minh Hưng', '2011-05-25', 'Nam', 'Lớp 9A5, số 195 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000545', 'thcs_le_quy_don.9a5.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1780, 1856, NULL, '079311700546', 'Ngô Thảo Huyền', '2011-12-08', 'Nữ', 'Lớp 9A5, số 196 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000546', 'thcs_le_quy_don.9a5.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1781, 1857, NULL, '079211700547', 'Dương Minh Khoa', '2011-07-19', 'Nam', 'Lớp 9A5, số 197 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000547', 'thcs_le_quy_don.9a5.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1782, 1858, NULL, '079311700548', 'Lý Thảo Lan', '2011-02-02', 'Nữ', 'Lớp 9A5, số 198 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000548', 'thcs_le_quy_don.9a5.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1783, 1859, NULL, '079211700549', 'Đinh Minh Khang', '2011-09-13', 'Nam', 'Lớp 9A5, số 199 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000549', 'thcs_le_quy_don.9a5.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1784, 1860, NULL, '079311700550', 'Đoàn Thảo Linh', '2011-04-24', 'Nữ', 'Lớp 9A5, số 200 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000550', 'thcs_le_quy_don.9a5.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1785, 1861, NULL, '079211700551', 'Lâm Minh Khôi', '2011-11-07', 'Nam', 'Lớp 9A5, số 201 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000551', 'thcs_le_quy_don.9a5.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1786, 1862, NULL, '079311700552', 'Trịnh Thảo Mai', '2011-06-18', 'Nữ', 'Lớp 9A5, số 202 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000552', 'thcs_le_quy_don.9a5.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1787, 1863, NULL, '079211700553', 'Nguyễn Minh Lâm', '2011-01-01', 'Nam', 'Lớp 9A5, số 203 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000553', 'thcs_le_quy_don.9a5.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1788, 1864, NULL, '079311700554', 'Trần Thảo My', '2011-08-12', 'Nữ', 'Lớp 9A5, số 204 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000554', 'thcs_le_quy_don.9a5.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1789, 1865, NULL, '079211700555', 'Lê Minh Long', '2011-03-23', 'Nam', 'Lớp 9A5, số 205 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000555', 'thcs_le_quy_don.9a5.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1790, 1866, NULL, '079311700556', 'Phạm Thảo Ngân', '2011-10-06', 'Nữ', 'Lớp 9A5, số 206 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000556', 'thcs_le_quy_don.9a5.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1791, 1867, NULL, '079211700557', 'Hoàng Minh Minh', '2011-05-17', 'Nam', 'Lớp 9A5, số 207 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000557', 'thcs_le_quy_don.9a5.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1792, 1868, NULL, '079311700558', 'Huỳnh Thảo Nhung', '2011-12-28', 'Nữ', 'Lớp 9A5, số 208 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000558', 'thcs_le_quy_don.9a5.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1793, 1869, NULL, '079211700559', 'Phan Minh Nam', '2011-07-11', 'Nam', 'Lớp 9A5, số 209 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000559', 'thcs_le_quy_don.9a5.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1794, 1870, NULL, '079311700560', 'Vũ Thảo Trang', '2011-02-22', 'Nữ', 'Lớp 9A5, số 210 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000560', 'thcs_le_quy_don.9a5.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1795, 1871, NULL, '079211700561', 'Võ Tuấn An', '2011-10-06', 'Nam', 'Lớp 9A6, số 211 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000561', 'thcs_le_quy_don.9a6.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1796, 1872, NULL, '079311700562', 'Đặng Khánh Ánh', '2011-05-17', 'Nữ', 'Lớp 9A6, số 212 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000562', 'thcs_le_quy_don.9a6.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1797, 1873, NULL, '079211700563', 'Bùi Tuấn Bình', '2011-12-28', 'Nam', 'Lớp 9A6, số 213 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000563', 'thcs_le_quy_don.9a6.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1798, 1874, NULL, '079311700564', 'Đỗ Khánh Bích', '2011-07-11', 'Nữ', 'Lớp 9A6, số 214 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000564', 'thcs_le_quy_don.9a6.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1799, 1875, NULL, '079211700565', 'Hồ Tuấn Cường', '2011-02-22', 'Nam', 'Lớp 9A6, số 215 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000565', 'thcs_le_quy_don.9a6.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1800, 1876, NULL, '079311700566', 'Ngô Khánh Chi', '2011-09-05', 'Nữ', 'Lớp 9A6, số 216 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000566', 'thcs_le_quy_don.9a6.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1801, 1877, NULL, '079211700567', 'Dương Tuấn Dũng', '2011-04-16', 'Nam', 'Lớp 9A6, số 217 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000567', 'thcs_le_quy_don.9a6.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1802, 1878, NULL, '079311700568', 'Lý Khánh Dung', '2011-11-27', 'Nữ', 'Lớp 9A6, số 218 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000568', 'thcs_le_quy_don.9a6.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1803, 1879, NULL, '079211700569', 'Đinh Tuấn Đạt', '2011-06-10', 'Nam', 'Lớp 9A6, số 219 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000569', 'thcs_le_quy_don.9a6.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1804, 1880, NULL, '079311700570', 'Đoàn Khánh Duyên', '2011-01-21', 'Nữ', 'Lớp 9A6, số 220 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000570', 'thcs_le_quy_don.9a6.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1805, 1881, NULL, '079211700571', 'Lâm Tuấn Duy', '2011-08-04', 'Nam', 'Lớp 9A6, số 221 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000571', 'thcs_le_quy_don.9a6.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1806, 1882, NULL, '079311700572', 'Trịnh Khánh Giang', '2011-03-15', 'Nữ', 'Lớp 9A6, số 222 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000572', 'thcs_le_quy_don.9a6.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1807, 1883, NULL, '079211700573', 'Nguyễn Tuấn Giang', '2011-10-26', 'Nam', 'Lớp 9A6, số 223 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000573', 'thcs_le_quy_don.9a6.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1808, 1884, NULL, '079311700574', 'Trần Khánh Hà', '2011-05-09', 'Nữ', 'Lớp 9A6, số 224 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000574', 'thcs_le_quy_don.9a6.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1809, 1885, NULL, '079211700575', 'Lê Tuấn Hải', '2011-12-20', 'Nam', 'Lớp 9A6, số 225 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000575', 'thcs_le_quy_don.9a6.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1810, 1886, NULL, '079311700576', 'Phạm Khánh Hân', '2011-07-03', 'Nữ', 'Lớp 9A6, số 226 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000576', 'thcs_le_quy_don.9a6.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1811, 1887, NULL, '079211700577', 'Hoàng Tuấn Hiếu', '2011-02-14', 'Nam', 'Lớp 9A6, số 227 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000577', 'thcs_le_quy_don.9a6.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1812, 1888, NULL, '079311700578', 'Huỳnh Khánh Hằng', '2011-09-25', 'Nữ', 'Lớp 9A6, số 228 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000578', 'thcs_le_quy_don.9a6.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1813, 1889, NULL, '079211700579', 'Phan Tuấn Hoàng', '2011-04-08', 'Nam', 'Lớp 9A6, số 229 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000579', 'thcs_le_quy_don.9a6.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1814, 1890, NULL, '079311700580', 'Vũ Khánh Hạnh', '2011-11-19', 'Nữ', 'Lớp 9A6, số 230 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000580', 'thcs_le_quy_don.9a6.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1815, 1891, NULL, '079211700581', 'Võ Tuấn Huy', '2011-06-02', 'Nam', 'Lớp 9A6, số 231 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000581', 'thcs_le_quy_don.9a6.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1816, 1892, NULL, '079311700582', 'Đặng Khánh Hoa', '2011-01-13', 'Nữ', 'Lớp 9A6, số 232 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000582', 'thcs_le_quy_don.9a6.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1817, 1893, NULL, '079211700583', 'Bùi Tuấn Hùng', '2011-08-24', 'Nam', 'Lớp 9A6, số 233 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000583', 'thcs_le_quy_don.9a6.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1818, 1894, NULL, '079311700584', 'Đỗ Khánh Hương', '2011-03-07', 'Nữ', 'Lớp 9A6, số 234 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000584', 'thcs_le_quy_don.9a6.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1819, 1895, NULL, '079211700585', 'Hồ Tuấn Hưng', '2011-10-18', 'Nam', 'Lớp 9A6, số 235 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000585', 'thcs_le_quy_don.9a6.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1820, 1896, NULL, '079311700586', 'Ngô Khánh Huyền', '2011-05-01', 'Nữ', 'Lớp 9A6, số 236 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000586', 'thcs_le_quy_don.9a6.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1821, 1897, NULL, '079211700587', 'Dương Tuấn Khoa', '2011-12-12', 'Nam', 'Lớp 9A6, số 237 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000587', 'thcs_le_quy_don.9a6.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1822, 1898, NULL, '079311700588', 'Lý Khánh Lan', '2011-07-23', 'Nữ', 'Lớp 9A6, số 238 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000588', 'thcs_le_quy_don.9a6.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1823, 1899, NULL, '079211700589', 'Đinh Tuấn Khang', '2011-02-06', 'Nam', 'Lớp 9A6, số 239 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000589', 'thcs_le_quy_don.9a6.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1824, 1900, NULL, '079311700590', 'Đoàn Khánh Linh', '2011-09-17', 'Nữ', 'Lớp 9A6, số 240 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000590', 'thcs_le_quy_don.9a6.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1825, 1901, NULL, '079211700591', 'Lâm Tuấn Khôi', '2011-04-28', 'Nam', 'Lớp 9A6, số 241 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000591', 'thcs_le_quy_don.9a6.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1826, 1902, NULL, '079311700592', 'Trịnh Khánh Mai', '2011-11-11', 'Nữ', 'Lớp 9A6, số 242 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000592', 'thcs_le_quy_don.9a6.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1827, 1903, NULL, '079211700593', 'Nguyễn Tuấn Lâm', '2011-06-22', 'Nam', 'Lớp 9A6, số 243 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000593', 'thcs_le_quy_don.9a6.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1828, 1904, NULL, '079311700594', 'Trần Khánh My', '2011-01-05', 'Nữ', 'Lớp 9A6, số 244 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000594', 'thcs_le_quy_don.9a6.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1829, 1905, NULL, '079211700595', 'Lê Tuấn Long', '2011-08-16', 'Nam', 'Lớp 9A6, số 245 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000595', 'thcs_le_quy_don.9a6.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1830, 1906, NULL, '079311700596', 'Phạm Khánh Ngân', '2011-03-27', 'Nữ', 'Lớp 9A6, số 246 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000596', 'thcs_le_quy_don.9a6.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1831, 1907, NULL, '079211700597', 'Hoàng Tuấn Minh', '2011-10-10', 'Nam', 'Lớp 9A6, số 247 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000597', 'thcs_le_quy_don.9a6.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1832, 1908, NULL, '079311700598', 'Huỳnh Khánh Nhung', '2011-05-21', 'Nữ', 'Lớp 9A6, số 248 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000598', 'thcs_le_quy_don.9a6.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1833, 1909, NULL, '079211700599', 'Phan Tuấn Nam', '2011-12-04', 'Nam', 'Lớp 9A6, số 249 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000599', 'thcs_le_quy_don.9a6.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1834, 1910, NULL, '079311700600', 'Vũ Khánh Trang', '2011-07-15', 'Nữ', 'Lớp 9A6, số 250 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000600', 'thcs_le_quy_don.9a6.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1835, 1911, NULL, '079211700601', 'Võ Gia An', '2011-03-27', 'Nam', 'Lớp 9A7, số 251 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000601', 'thcs_le_quy_don.9a7.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1836, 1912, NULL, '079311700602', 'Đặng Quỳnh Ánh', '2011-10-10', 'Nữ', 'Lớp 9A7, số 252 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000602', 'thcs_le_quy_don.9a7.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1837, 1913, NULL, '079211700603', 'Bùi Gia Bình', '2011-05-21', 'Nam', 'Lớp 9A7, số 253 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000603', 'thcs_le_quy_don.9a7.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1838, 1914, NULL, '079311700604', 'Đỗ Quỳnh Bích', '2011-12-04', 'Nữ', 'Lớp 9A7, số 254 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000604', 'thcs_le_quy_don.9a7.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1839, 1915, NULL, '079211700605', 'Hồ Gia Cường', '2011-07-15', 'Nam', 'Lớp 9A7, số 255 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000605', 'thcs_le_quy_don.9a7.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1840, 1916, NULL, '079311700606', 'Ngô Quỳnh Chi', '2011-02-26', 'Nữ', 'Lớp 9A7, số 256 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000606', 'thcs_le_quy_don.9a7.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1841, 1917, NULL, '079211700607', 'Dương Gia Dũng', '2011-09-09', 'Nam', 'Lớp 9A7, số 257 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000607', 'thcs_le_quy_don.9a7.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1842, 1918, NULL, '079311700608', 'Lý Quỳnh Dung', '2011-04-20', 'Nữ', 'Lớp 9A7, số 258 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000608', 'thcs_le_quy_don.9a7.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1843, 1919, NULL, '079211700609', 'Đinh Gia Đạt', '2011-11-03', 'Nam', 'Lớp 9A7, số 259 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000609', 'thcs_le_quy_don.9a7.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1844, 1920, NULL, '079311700610', 'Đoàn Quỳnh Duyên', '2011-06-14', 'Nữ', 'Lớp 9A7, số 260 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000610', 'thcs_le_quy_don.9a7.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1845, 1921, NULL, '079211700611', 'Lâm Gia Duy', '2011-01-25', 'Nam', 'Lớp 9A7, số 261 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000611', 'thcs_le_quy_don.9a7.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1846, 1922, NULL, '079311700612', 'Trịnh Quỳnh Giang', '2011-08-08', 'Nữ', 'Lớp 9A7, số 262 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000612', 'thcs_le_quy_don.9a7.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1847, 1923, NULL, '079211700613', 'Nguyễn Gia Giang', '2011-03-19', 'Nam', 'Lớp 9A7, số 263 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000613', 'thcs_le_quy_don.9a7.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1848, 1924, NULL, '079311700614', 'Trần Quỳnh Hà', '2011-10-02', 'Nữ', 'Lớp 9A7, số 264 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000614', 'thcs_le_quy_don.9a7.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1849, 1925, NULL, '079211700615', 'Lê Gia Hải', '2011-05-13', 'Nam', 'Lớp 9A7, số 265 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000615', 'thcs_le_quy_don.9a7.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1850, 1926, NULL, '079311700616', 'Phạm Quỳnh Hân', '2011-12-24', 'Nữ', 'Lớp 9A7, số 266 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000616', 'thcs_le_quy_don.9a7.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1851, 1927, NULL, '079211700617', 'Hoàng Gia Hiếu', '2011-07-07', 'Nam', 'Lớp 9A7, số 267 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000617', 'thcs_le_quy_don.9a7.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1852, 1928, NULL, '079311700618', 'Huỳnh Quỳnh Hằng', '2011-02-18', 'Nữ', 'Lớp 9A7, số 268 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000618', 'thcs_le_quy_don.9a7.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1853, 1929, NULL, '079211700619', 'Phan Gia Hoàng', '2011-09-01', 'Nam', 'Lớp 9A7, số 269 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000619', 'thcs_le_quy_don.9a7.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1854, 1930, NULL, '079311700620', 'Vũ Quỳnh Hạnh', '2011-04-12', 'Nữ', 'Lớp 9A7, số 270 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000620', 'thcs_le_quy_don.9a7.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1855, 1931, NULL, '079211700621', 'Võ Gia Huy', '2011-11-23', 'Nam', 'Lớp 9A7, số 271 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000621', 'thcs_le_quy_don.9a7.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1856, 1932, NULL, '079311700622', 'Đặng Quỳnh Hoa', '2011-06-06', 'Nữ', 'Lớp 9A7, số 272 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000622', 'thcs_le_quy_don.9a7.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1857, 1933, NULL, '079211700623', 'Bùi Gia Hùng', '2011-01-17', 'Nam', 'Lớp 9A7, số 273 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000623', 'thcs_le_quy_don.9a7.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1858, 1934, NULL, '079311700624', 'Đỗ Quỳnh Hương', '2011-08-28', 'Nữ', 'Lớp 9A7, số 274 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000624', 'thcs_le_quy_don.9a7.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1859, 1935, NULL, '079211700625', 'Hồ Gia Hưng', '2011-03-11', 'Nam', 'Lớp 9A7, số 275 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000625', 'thcs_le_quy_don.9a7.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1860, 1936, NULL, '079311700626', 'Ngô Quỳnh Huyền', '2011-10-22', 'Nữ', 'Lớp 9A7, số 276 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000626', 'thcs_le_quy_don.9a7.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1861, 1937, NULL, '079211700627', 'Dương Gia Khoa', '2011-05-05', 'Nam', 'Lớp 9A7, số 277 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000627', 'thcs_le_quy_don.9a7.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1862, 1938, NULL, '079311700628', 'Lý Quỳnh Lan', '2011-12-16', 'Nữ', 'Lớp 9A7, số 278 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000628', 'thcs_le_quy_don.9a7.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1863, 1939, NULL, '079211700629', 'Đinh Gia Khang', '2011-07-27', 'Nam', 'Lớp 9A7, số 279 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000629', 'thcs_le_quy_don.9a7.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1864, 1940, NULL, '079311700630', 'Đoàn Quỳnh Linh', '2011-02-10', 'Nữ', 'Lớp 9A7, số 280 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000630', 'thcs_le_quy_don.9a7.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1865, 1941, NULL, '079211700631', 'Lâm Gia Khôi', '2011-09-21', 'Nam', 'Lớp 9A7, số 281 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000631', 'thcs_le_quy_don.9a7.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1866, 1942, NULL, '079311700632', 'Trịnh Quỳnh Mai', '2011-04-04', 'Nữ', 'Lớp 9A7, số 282 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000632', 'thcs_le_quy_don.9a7.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1867, 1943, NULL, '079211700633', 'Nguyễn Gia Lâm', '2011-11-15', 'Nam', 'Lớp 9A7, số 283 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000633', 'thcs_le_quy_don.9a7.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1868, 1944, NULL, '079311700634', 'Trần Quỳnh My', '2011-06-26', 'Nữ', 'Lớp 9A7, số 284 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000634', 'thcs_le_quy_don.9a7.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1869, 1945, NULL, '079211700635', 'Lê Gia Long', '2011-01-09', 'Nam', 'Lớp 9A7, số 285 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000635', 'thcs_le_quy_don.9a7.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1870, 1946, NULL, '079311700636', 'Phạm Quỳnh Ngân', '2011-08-20', 'Nữ', 'Lớp 9A7, số 286 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000636', 'thcs_le_quy_don.9a7.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1871, 1947, NULL, '079211700637', 'Hoàng Gia Minh', '2011-03-03', 'Nam', 'Lớp 9A7, số 287 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000637', 'thcs_le_quy_don.9a7.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1872, 1948, NULL, '079311700638', 'Huỳnh Quỳnh Nhung', '2011-10-14', 'Nữ', 'Lớp 9A7, số 288 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000638', 'thcs_le_quy_don.9a7.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1873, 1949, NULL, '079211700639', 'Phan Gia Nam', '2011-05-25', 'Nam', 'Lớp 9A7, số 289 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000639', 'thcs_le_quy_don.9a7.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1874, 1950, NULL, '079311700640', 'Vũ Quỳnh Trang', '2011-12-08', 'Nữ', 'Lớp 9A7, số 290 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000640', 'thcs_le_quy_don.9a7.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1875, 1951, NULL, '079211700641', 'Võ Bảo An', '2011-08-20', 'Nam', 'Lớp 9A8, số 291 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000641', 'thcs_le_quy_don.9a8.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1876, 1952, NULL, '079311700642', 'Đặng Thanh Ánh', '2011-03-03', 'Nữ', 'Lớp 9A8, số 292 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000642', 'thcs_le_quy_don.9a8.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1877, 1953, NULL, '079211700643', 'Bùi Bảo Bình', '2011-10-14', 'Nam', 'Lớp 9A8, số 293 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000643', 'thcs_le_quy_don.9a8.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1878, 1954, NULL, '079311700644', 'Đỗ Thanh Bích', '2011-05-25', 'Nữ', 'Lớp 9A8, số 294 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000644', 'thcs_le_quy_don.9a8.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1879, 1955, NULL, '079211700645', 'Hồ Bảo Cường', '2011-12-08', 'Nam', 'Lớp 9A8, số 295 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000645', 'thcs_le_quy_don.9a8.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1880, 1956, NULL, '079311700646', 'Ngô Thanh Chi', '2011-07-19', 'Nữ', 'Lớp 9A8, số 296 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000646', 'thcs_le_quy_don.9a8.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1881, 1957, NULL, '079211700647', 'Dương Bảo Dũng', '2011-02-02', 'Nam', 'Lớp 9A8, số 297 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000647', 'thcs_le_quy_don.9a8.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1882, 1958, NULL, '079311700648', 'Lý Thanh Dung', '2011-09-13', 'Nữ', 'Lớp 9A8, số 298 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000648', 'thcs_le_quy_don.9a8.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1883, 1959, NULL, '079211700649', 'Đinh Bảo Đạt', '2011-04-24', 'Nam', 'Lớp 9A8, số 299 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000649', 'thcs_le_quy_don.9a8.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1884, 1960, NULL, '079311700650', 'Đoàn Thanh Duyên', '2011-11-07', 'Nữ', 'Lớp 9A8, số 300 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000650', 'thcs_le_quy_don.9a8.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1885, 1961, NULL, '079211700651', 'Lâm Bảo Duy', '2011-06-18', 'Nam', 'Lớp 9A8, số 301 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000651', 'thcs_le_quy_don.9a8.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1886, 1962, NULL, '079311700652', 'Trịnh Thanh Giang', '2011-01-01', 'Nữ', 'Lớp 9A8, số 302 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000652', 'thcs_le_quy_don.9a8.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1887, 1963, NULL, '079211700653', 'Nguyễn Bảo Giang', '2011-08-12', 'Nam', 'Lớp 9A8, số 303 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000653', 'thcs_le_quy_don.9a8.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1888, 1964, NULL, '079311700654', 'Trần Thanh Hà', '2011-03-23', 'Nữ', 'Lớp 9A8, số 304 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000654', 'thcs_le_quy_don.9a8.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1889, 1965, NULL, '079211700655', 'Lê Bảo Hải', '2011-10-06', 'Nam', 'Lớp 9A8, số 305 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000655', 'thcs_le_quy_don.9a8.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1890, 1966, NULL, '079311700656', 'Phạm Thanh Hân', '2011-05-17', 'Nữ', 'Lớp 9A8, số 306 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000656', 'thcs_le_quy_don.9a8.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1891, 1967, NULL, '079211700657', 'Hoàng Bảo Hiếu', '2011-12-28', 'Nam', 'Lớp 9A8, số 307 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000657', 'thcs_le_quy_don.9a8.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1892, 1968, NULL, '079311700658', 'Huỳnh Thanh Hằng', '2011-07-11', 'Nữ', 'Lớp 9A8, số 308 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000658', 'thcs_le_quy_don.9a8.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1893, 1969, NULL, '079211700659', 'Phan Bảo Hoàng', '2011-02-22', 'Nam', 'Lớp 9A8, số 309 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000659', 'thcs_le_quy_don.9a8.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1894, 1970, NULL, '079311700660', 'Vũ Thanh Hạnh', '2011-09-05', 'Nữ', 'Lớp 9A8, số 310 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000660', 'thcs_le_quy_don.9a8.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1895, 1971, NULL, '079211700661', 'Võ Bảo Huy', '2011-04-16', 'Nam', 'Lớp 9A8, số 311 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000661', 'thcs_le_quy_don.9a8.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1896, 1972, NULL, '079311700662', 'Đặng Thanh Hoa', '2011-11-27', 'Nữ', 'Lớp 9A8, số 312 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000662', 'thcs_le_quy_don.9a8.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1897, 1973, NULL, '079211700663', 'Bùi Bảo Hùng', '2011-06-10', 'Nam', 'Lớp 9A8, số 313 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000663', 'thcs_le_quy_don.9a8.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1898, 1974, NULL, '079311700664', 'Đỗ Thanh Hương', '2011-01-21', 'Nữ', 'Lớp 9A8, số 314 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000664', 'thcs_le_quy_don.9a8.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1899, 1975, NULL, '079211700665', 'Hồ Bảo Hưng', '2011-08-04', 'Nam', 'Lớp 9A8, số 315 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000665', 'thcs_le_quy_don.9a8.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1900, 1976, NULL, '079311700666', 'Ngô Thanh Huyền', '2011-03-15', 'Nữ', 'Lớp 9A8, số 316 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000666', 'thcs_le_quy_don.9a8.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1901, 1977, NULL, '079211700667', 'Dương Bảo Khoa', '2011-10-26', 'Nam', 'Lớp 9A8, số 317 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000667', 'thcs_le_quy_don.9a8.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1902, 1978, NULL, '079311700668', 'Lý Thanh Lan', '2011-05-09', 'Nữ', 'Lớp 9A8, số 318 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000668', 'thcs_le_quy_don.9a8.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1903, 1979, NULL, '079211700669', 'Đinh Bảo Khang', '2011-12-20', 'Nam', 'Lớp 9A8, số 319 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000669', 'thcs_le_quy_don.9a8.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1904, 1980, NULL, '079311700670', 'Đoàn Thanh Linh', '2011-07-03', 'Nữ', 'Lớp 9A8, số 320 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000670', 'thcs_le_quy_don.9a8.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1905, 1981, NULL, '079211700671', 'Lâm Bảo Khôi', '2011-02-14', 'Nam', 'Lớp 9A8, số 321 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000671', 'thcs_le_quy_don.9a8.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1906, 1982, NULL, '079311700672', 'Trịnh Thanh Mai', '2011-09-25', 'Nữ', 'Lớp 9A8, số 322 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000672', 'thcs_le_quy_don.9a8.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1907, 1983, NULL, '079211700673', 'Nguyễn Bảo Lâm', '2011-04-08', 'Nam', 'Lớp 9A8, số 323 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000673', 'thcs_le_quy_don.9a8.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1908, 1984, NULL, '079311700674', 'Trần Thanh My', '2011-11-19', 'Nữ', 'Lớp 9A8, số 324 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000674', 'thcs_le_quy_don.9a8.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1909, 1985, NULL, '079211700675', 'Lê Bảo Long', '2011-06-02', 'Nam', 'Lớp 9A8, số 325 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000675', 'thcs_le_quy_don.9a8.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1910, 1986, NULL, '079311700676', 'Phạm Thanh Ngân', '2011-01-13', 'Nữ', 'Lớp 9A8, số 326 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000676', 'thcs_le_quy_don.9a8.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1911, 1987, NULL, '079211700677', 'Hoàng Bảo Minh', '2011-08-24', 'Nam', 'Lớp 9A8, số 327 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000677', 'thcs_le_quy_don.9a8.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1912, 1988, NULL, '079311700678', 'Huỳnh Thanh Nhung', '2011-03-07', 'Nữ', 'Lớp 9A8, số 328 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000678', 'thcs_le_quy_don.9a8.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1913, 1989, NULL, '079211700679', 'Phan Bảo Nam', '2011-10-18', 'Nam', 'Lớp 9A8, số 329 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000679', 'thcs_le_quy_don.9a8.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1914, 1990, NULL, '079311700680', 'Vũ Thanh Trang', '2011-05-01', 'Nữ', 'Lớp 9A8, số 330 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000680', 'thcs_le_quy_don.9a8.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1915, 1991, NULL, '079211700681', 'Võ Văn An', '2011-01-13', 'Nam', 'Lớp 9A9, số 331 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000681', 'thcs_le_quy_don.9a9.01@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1916, 1992, NULL, '079311700682', 'Đặng Thị Ánh', '2011-08-24', 'Nữ', 'Lớp 9A9, số 332 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000682', 'thcs_le_quy_don.9a9.02@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1917, 1993, NULL, '079211700683', 'Bùi Văn Bình', '2011-03-07', 'Nam', 'Lớp 9A9, số 333 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000683', 'thcs_le_quy_don.9a9.03@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1918, 1994, NULL, '079311700684', 'Đỗ Thị Bích', '2011-10-18', 'Nữ', 'Lớp 9A9, số 334 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000684', 'thcs_le_quy_don.9a9.04@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1919, 1995, NULL, '079211700685', 'Hồ Văn Cường', '2011-05-01', 'Nam', 'Lớp 9A9, số 335 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000685', 'thcs_le_quy_don.9a9.05@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed');
INSERT INTO `candidates` (`id`, `user_id`, `candidate_code`, `national_id`, `full_name`, `date_of_birth`, `gender`, `address`, `phone`, `email`, `middle_school_id`, `eligible`, `status`, `exam_number`, `exam_site_name`, `exam_room_name`, `exam_schedule`, `created_at`, `updated_at`, `admission_status`) VALUES
(1920, 1996, NULL, '079311700686', 'Ngô Thị Chi', '2011-12-12', 'Nữ', 'Lớp 9A9, số 336 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000686', 'thcs_le_quy_don.9a9.06@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1921, 1997, NULL, '079211700687', 'Dương Văn Dũng', '2011-07-23', 'Nam', 'Lớp 9A9, số 337 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000687', 'thcs_le_quy_don.9a9.07@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1922, 1998, NULL, '079311700688', 'Lý Thị Dung', '2011-02-06', 'Nữ', 'Lớp 9A9, số 338 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000688', 'thcs_le_quy_don.9a9.08@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1923, 1999, NULL, '079211700689', 'Đinh Văn Đạt', '2011-09-17', 'Nam', 'Lớp 9A9, số 339 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000689', 'thcs_le_quy_don.9a9.09@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1924, 2000, NULL, '079311700690', 'Đoàn Thị Duyên', '2011-04-28', 'Nữ', 'Lớp 9A9, số 340 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000690', 'thcs_le_quy_don.9a9.10@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1925, 2001, NULL, '079211700691', 'Lâm Văn Duy', '2011-11-11', 'Nam', 'Lớp 9A9, số 341 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000691', 'thcs_le_quy_don.9a9.11@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1926, 2002, NULL, '079311700692', 'Trịnh Thị Giang', '2011-06-22', 'Nữ', 'Lớp 9A9, số 342 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000692', 'thcs_le_quy_don.9a9.12@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1927, 2003, NULL, '079211700693', 'Nguyễn Văn Giang', '2011-01-05', 'Nam', 'Lớp 9A9, số 343 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000693', 'thcs_le_quy_don.9a9.13@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1928, 2004, NULL, '079311700694', 'Trần Thị Hà', '2011-08-16', 'Nữ', 'Lớp 9A9, số 344 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000694', 'thcs_le_quy_don.9a9.14@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1929, 2005, NULL, '079211700695', 'Lê Văn Hải', '2011-03-27', 'Nam', 'Lớp 9A9, số 345 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000695', 'thcs_le_quy_don.9a9.15@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1930, 2006, NULL, '079311700696', 'Phạm Thị Hân', '2011-10-10', 'Nữ', 'Lớp 9A9, số 346 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000696', 'thcs_le_quy_don.9a9.16@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1931, 2007, NULL, '079211700697', 'Hoàng Văn Hiếu', '2011-05-21', 'Nam', 'Lớp 9A9, số 347 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000697', 'thcs_le_quy_don.9a9.17@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1932, 2008, NULL, '079311700698', 'Huỳnh Thị Hằng', '2011-12-04', 'Nữ', 'Lớp 9A9, số 348 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000698', 'thcs_le_quy_don.9a9.18@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1933, 2009, NULL, '079211700699', 'Phan Văn Hoàng', '2011-07-15', 'Nam', 'Lớp 9A9, số 349 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000699', 'thcs_le_quy_don.9a9.19@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1934, 2010, NULL, '079311700700', 'Vũ Thị Hạnh', '2011-02-26', 'Nữ', 'Lớp 9A9, số 350 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000700', 'thcs_le_quy_don.9a9.20@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1935, 2011, NULL, '079211700701', 'Võ Văn Huy', '2011-09-09', 'Nam', 'Lớp 9A9, số 351 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000701', 'thcs_le_quy_don.9a9.21@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1936, 2012, NULL, '079311700702', 'Đặng Thị Hoa', '2011-04-20', 'Nữ', 'Lớp 9A9, số 352 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000702', 'thcs_le_quy_don.9a9.22@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1937, 2013, NULL, '079211700703', 'Bùi Văn Hùng', '2011-11-03', 'Nam', 'Lớp 9A9, số 353 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000703', 'thcs_le_quy_don.9a9.23@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1938, 2014, NULL, '079311700704', 'Đỗ Thị Hương', '2011-06-14', 'Nữ', 'Lớp 9A9, số 354 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000704', 'thcs_le_quy_don.9a9.24@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1939, 2015, NULL, '079211700705', 'Hồ Văn Hưng', '2011-01-25', 'Nam', 'Lớp 9A9, số 355 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000705', 'thcs_le_quy_don.9a9.25@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1940, 2016, NULL, '079311700706', 'Ngô Thị Huyền', '2011-08-08', 'Nữ', 'Lớp 9A9, số 356 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000706', 'thcs_le_quy_don.9a9.26@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1941, 2017, NULL, '079211700707', 'Dương Văn Khoa', '2011-03-19', 'Nam', 'Lớp 9A9, số 357 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000707', 'thcs_le_quy_don.9a9.27@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1942, 2018, NULL, '079311700708', 'Lý Thị Lan', '2011-10-02', 'Nữ', 'Lớp 9A9, số 358 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000708', 'thcs_le_quy_don.9a9.28@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1943, 2019, NULL, '079211700709', 'Đinh Văn Khang', '2011-05-13', 'Nam', 'Lớp 9A9, số 359 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000709', 'thcs_le_quy_don.9a9.29@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1944, 2020, NULL, '079311700710', 'Đoàn Thị Linh', '2011-12-24', 'Nữ', 'Lớp 9A9, số 360 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000710', 'thcs_le_quy_don.9a9.30@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1945, 2021, NULL, '079211700711', 'Lâm Văn Khôi', '2011-07-07', 'Nam', 'Lớp 9A9, số 361 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000711', 'thcs_le_quy_don.9a9.31@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1946, 2022, NULL, '079311700712', 'Trịnh Thị Mai', '2011-02-18', 'Nữ', 'Lớp 9A9, số 362 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000712', 'thcs_le_quy_don.9a9.32@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1947, 2023, NULL, '079211700713', 'Nguyễn Văn Lâm', '2011-09-01', 'Nam', 'Lớp 9A9, số 363 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000713', 'thcs_le_quy_don.9a9.33@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1948, 2024, NULL, '079311700714', 'Trần Thị My', '2011-04-12', 'Nữ', 'Lớp 9A9, số 364 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000714', 'thcs_le_quy_don.9a9.34@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1949, 2025, NULL, '079211700715', 'Lê Văn Long', '2011-11-23', 'Nam', 'Lớp 9A9, số 365 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000715', 'thcs_le_quy_don.9a9.35@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1950, 2026, NULL, '079311700716', 'Phạm Thị Ngân', '2011-06-06', 'Nữ', 'Lớp 9A9, số 366 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000716', 'thcs_le_quy_don.9a9.36@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1951, 2027, NULL, '079211700717', 'Hoàng Văn Minh', '2011-01-17', 'Nam', 'Lớp 9A9, số 367 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000717', 'thcs_le_quy_don.9a9.37@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1952, 2028, NULL, '079311700718', 'Huỳnh Thị Nhung', '2011-08-28', 'Nữ', 'Lớp 9A9, số 368 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000718', 'thcs_le_quy_don.9a9.38@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1953, 2029, NULL, '079211700719', 'Phan Văn Nam', '2011-03-11', 'Nam', 'Lớp 9A9, số 369 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000719', 'thcs_le_quy_don.9a9.39@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed'),
(1954, 2030, NULL, '079311700720', 'Vũ Thị Trang', '2011-10-22', 'Nữ', 'Lớp 9A9, số 370 đường Lê Quý Đôn, phường Xuân Hòa, TP.HCM', '0960000720', 'thcs_le_quy_don.9a9.40@example.com', 291, 1, 'active', NULL, NULL, NULL, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13', 'not_processed');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `candidate_priority_points`
--

CREATE TABLE `candidate_priority_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `priority_group` varchar(100) DEFAULT NULL,
  `priority_points` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `councils`
--

CREATE TABLE `councils` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `council_type` enum('invigilation','grading','appeal','question') NOT NULL,
  `name` varchar(200) NOT NULL,
  `exam_site_id` int(10) UNSIGNED DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('draft','established','cancelled') NOT NULL DEFAULT 'established',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `council_members`
--

CREATE TABLE `council_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `council_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `task_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `council_subjects`
--

CREATE TABLE `council_subjects` (
  `council_id` bigint(20) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `enrollment_confirmations`
--

CREATE TABLE `enrollment_confirmations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_result_id` bigint(20) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `confirmed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admission_period_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `receipt_code` char(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_allocations`
--

CREATE TABLE `exam_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `exam_site_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_candidate_numbers`
--

CREATE TABLE `exam_candidate_numbers` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `sequence_no` int(10) UNSIGNED NOT NULL,
  `exam_number` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_grading_states`
--

CREATE TABLE `exam_grading_states` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `reviewed_at` datetime NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_import_logs`
--

CREATE TABLE `exam_import_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `valid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('previewed','saved','failed') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_invigilator_assignments`
--

CREATE TABLE `exam_invigilator_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_room_id` int(10) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `position` enum('invigilator_1','invigilator_2') NOT NULL,
  `status` enum('assigned','replaced','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_invigilator_assignment_history`
--

CREATE TABLE `exam_invigilator_assignment_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `exam_room_id` int(10) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `position` enum('invigilator_1','invigilator_2') NOT NULL,
  `event_type` enum('assigned','replaced') NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_organization_states`
--

CREATE TABLE `exam_organization_states` (
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `number_prefix` varchar(10) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `released_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_papers`
--

CREATE TABLE `exam_papers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `exam_room_id` int(10) UNSIGNED NOT NULL,
  `attendance_status` enum('present','present_penalty','absent') DEFAULT NULL,
  `penalty_percent` tinyint(3) UNSIGNED DEFAULT NULL,
  `anonymous_code` varchar(50) DEFAULT NULL,
  `paper_status` enum('waiting','coded','scored','matched') NOT NULL DEFAULT 'waiting',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_paper_grader_assignments`
--

CREATE TABLE `exam_paper_grader_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_paper_id` bigint(20) UNSIGNED NOT NULL,
  `council_member_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_rooms`
--

CREATE TABLE `exam_rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `exam_site_id` int(10) UNSIGNED NOT NULL,
  `room_code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','ready','assigned','completed') NOT NULL DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_score_import_batches`
--

CREATE TABLE `exam_score_import_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `exam_subject_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `valid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('previewed','saved','failed') NOT NULL DEFAULT 'previewed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_score_import_rows`
--

CREATE TABLE `exam_score_import_rows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `row_number` int(10) UNSIGNED NOT NULL,
  `anonymous_code` varchar(50) DEFAULT NULL,
  `score_1` decimal(5,2) DEFAULT NULL,
  `score_2` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_sites`
--

CREATE TABLE `exam_sites` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `site_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','ready','assigned','completed') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `subject_code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `exam_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `component_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('draft','scheduled','completed') NOT NULL DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(150) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'staff.manage', 'Quản lý danh sách cán bộ - giáo viên', '2026-09-18 23:45:51'),
(2, 'staff.create', 'Thêm thông tin cán bộ - giáo viên', '2026-09-18 23:45:51'),
(3, 'staff.update', 'Sửa thông tin cán bộ - giáo viên', '2026-09-18 23:45:51'),
(4, 'staff.delete', 'Xóa thông tin cán bộ - giáo viên', '2026-09-18 23:45:51'),
(5, 'candidate.manage', 'Quản lý danh sách thí sinh/học sinh', '2026-09-18 23:56:34'),
(6, 'candidate.create', 'Thêm thí sinh/học sinh', '2026-09-18 23:56:34'),
(7, 'candidate.update', 'Sửa thí sinh/học sinh', '2026-09-18 23:56:34'),
(8, 'candidate.delete', 'Xóa thí sinh/học sinh', '2026-09-18 23:56:34'),
(9, 'adjustment.submit', 'Gửi yêu cầu điều chỉnh hồ sơ', '2026-09-18 23:56:34'),
(10, 'adjustment.review', 'Duyệt yêu cầu điều chỉnh hồ sơ', '2026-09-18 23:56:34'),
(11, 'aspiration.manage', 'Đăng ký nguyện vọng', '2026-09-18 23:56:34'),
(12, 'exam_card.view', 'Xem thẻ dự thi', '2026-09-18 23:56:34'),
(13, 'exam.manage', 'Quản lý điểm thi/phòng thi/môn thi', '2026-09-19 21:12:52'),
(14, 'exam.allocate', 'Phân bố và sắp xếp thí sinh', '2026-09-19 21:12:52'),
(15, 'invigilation.assign', 'Phân công cán bộ coi thi', '2026-09-19 21:12:52'),
(16, 'paper.manage', 'Quản lý phách', '2026-09-19 21:12:52'),
(17, 'score.enter', 'Nhập điểm', '2026-09-19 21:12:52'),
(18, 'appeal.submit', 'Đăng ký phúc khảo', '2026-09-19 21:41:57'),
(19, 'appeal.view', 'Theo dõi trạng thái phúc khảo', '2026-09-19 21:41:57'),
(20, 'appeal.manage', 'Cập nhật kết quả phúc khảo', '2026-09-19 21:41:57'),
(21, 'admission.manage', 'Xét tuyển thí sinh', '2026-09-19 22:07:40'),
(22, 'admission.view', 'Xem kết quả xét tuyển', '2026-09-19 22:07:40'),
(23, 'enrollment.confirm', 'Xác nhận nhập học', '2026-09-19 22:07:40'),
(24, 'publication.manage', 'Công bố điểm thi và kết quả tuyển sinh', '2026-09-19 22:17:34'),
(25, 'results.view', 'Xem điểm thi và kết quả xét tuyển', '2026-09-19 22:17:34'),
(26, 'quota.view', 'Xem chỉ tiêu tuyển sinh', '2026-09-19 22:17:34'),
(27, 'statistics.view', 'Xem thống kê tuyển sinh', '2026-09-19 22:17:34'),
(28, 'admitted.view', 'Xem/Tải danh sách học sinh trúng tuyển', '2026-09-19 22:17:34'),
(29, 'school.manage', 'Quản lý trường THCS, THPT', '2026-09-19 23:56:04'),
(30, 'council.manage', 'Thành lập và quản lý các hội đồng kỳ thi', '2026-09-20 02:00:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `publication_events`
--

CREATE TABLE `publication_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `publication_type` enum('exam_results','admission_results') NOT NULL,
  `status` enum('published','cancelled') NOT NULL DEFAULT 'published',
  `published_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `published_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `active`, `created_at`) VALUES
(1, 'so_gdtd', 'Sở Giáo dục & Đào tạo', 1, '2026-09-18 23:45:51'),
(2, 'admin', 'Quản trị viên', 1, '2026-09-18 23:45:51'),
(3, 'candidate', 'Thí sinh/Học sinh', 1, '2026-09-18 23:56:34'),
(4, 'teacher', 'Giáo viên', 1, '2026-10-12 23:38:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 10),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 20),
(1, 21),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 10),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 20),
(2, 21),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 30),
(3, 9),
(3, 11),
(3, 12),
(3, 18),
(3, 19),
(3, 22),
(3, 23),
(3, 25),
(4, 9),
(4, 19),
(4, 25),
(4, 26),
(4, 27),
(4, 28);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_assignments`
--

CREATE TABLE `room_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_period_id` int(10) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `exam_site_id` int(10) UNSIGNED NOT NULL,
  `exam_room_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `schools`
--

CREATE TABLE `schools` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `level` enum('THCS','THPT') NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `admission_area` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `schools`
--

INSERT INTO `schools` (`id`, `school_code`, `name`, `level`, `address`, `district`, `admission_area`, `phone`, `email`, `active`, `created_at`, `updated_at`) VALUES
(288, 'THCS_AN_HOA_1', 'Trường THCS An Hòa 1', 'THCS', NULL, NULL, NULL, NULL, NULL, 1, '2026-10-14 21:49:39', '2026-10-14 21:49:39'),
(289, 'THCS_DOAN_THI_DIEM', 'Trường THCS Đoàn Thị Điểm', 'THCS', NULL, NULL, NULL, NULL, NULL, 1, '2026-10-14 21:49:39', '2026-10-14 21:49:39'),
(290, 'THCS_NGUYEN_DU', 'Trường THCS Nguyễn Du', 'THCS', '139 Nguyễn Du, phường Bến Thành, TP.HCM', 'Phường Bến Thành', NULL, '02838298731', 'lienhe@thcsnguyenduq1.edu.vn', 1, '2026-10-18 00:00:11', '2026-10-18 00:00:11'),
(291, 'THCS_LE_QUY_DON', 'Trường THCS Lê Quý Đôn', 'THCS', 'Số 2 Lê Quý Đôn, phường Xuân Hòa, TP.HCM', 'Phường Xuân Hòa', NULL, NULL, 'thcslequydonq3@gmail.com', 1, '2026-10-18 00:00:11', '2026-10-18 00:00:11'),
(292, 'THPT_NGUYEN_THI_MINH_KHAI', 'Trường THPT Nguyễn Thị Minh Khai', 'THPT', '275 Điện Biên Phủ, Phường Xuân Hòa, TP.HCM', 'Phường Xuân Hòa', NULL, '02839330801', 'thpt.hcm@minhkhai.edu.vn', 1, '2026-10-18 00:40:30', '2026-10-18 00:40:30'),
(293, 'THPT_TRAN_PHU', 'Trường THPT Trần Phú', 'THPT', '18 Lê Thúc Hoạch, Phường Phú Thọ Hòa, TP.HCM', 'Phường Phú Thọ Hòa', NULL, '02838658727', 'c3tranphu.tphcm@moet.edu.vn', 1, '2026-10-18 00:40:30', '2026-10-18 00:40:30'),
(294, 'THPT_MARIE_CURIE', 'Trường THPT Marie Curie', 'THPT', '159 Nam Kỳ Khởi Nghĩa, Phường Xuân Hòa, TP.HCM', 'Phường Xuân Hòa', NULL, '02839306989', 'c3mariecurie.tphcm@moet.edu.vn', 1, '2026-10-18 00:40:30', '2026-10-18 00:40:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `scores`
--

CREATE TABLE `scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_paper_id` bigint(20) UNSIGNED NOT NULL,
  `anonymous_code` varchar(50) NOT NULL,
  `score_1` decimal(5,2) DEFAULT NULL,
  `score_2` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) NOT NULL,
  `entered_by` bigint(20) UNSIGNED NOT NULL,
  `entered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `scores`
--
DELIMITER $$
CREATE TRIGGER `trg_scores_delete_lock_after_exam_publication` BEFORE DELETE ON `scores` FOR EACH ROW BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=OLD.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_scores_lock_after_exam_publication` BEFORE INSERT ON `scores` FOR EACH ROW BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=NEW.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_scores_update_lock_after_exam_publication` BEFORE UPDATE ON `scores` FOR EACH ROW BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=OLD.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `score_change_logs`
--

CREATE TABLE `score_change_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_paper_id` bigint(20) UNSIGNED NOT NULL,
  `appeal_request_id` bigint(20) UNSIGNED NOT NULL,
  `old_score` decimal(5,2) NOT NULL,
  `new_score` decimal(5,2) NOT NULL,
  `changed_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `staff`
--

CREATE TABLE `staff` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `workplace` varchar(200) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `staff_role` varchar(150) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `school_id`, `full_name`, `workplace`, `phone`, `email`, `staff_role`, `active`, `created_at`, `updated_at`) VALUES
(436, 2032, 290, 'Nguyễn Minh Tuấn', 'Trường THCS Nguyễn Du', NULL, NULL, 'Giáo viên phụ trách lớp 9 (mô phỏng)', 1, '2026-10-18 00:40:30', '2026-10-18 00:40:30'),
(437, 2033, 291, 'Trần Thị Thanh Mai', 'Trường THCS Lê Quý Đôn', NULL, NULL, 'Giáo viên phụ trách lớp 9 (mô phỏng)', 1, '2026-10-18 00:40:30', '2026-10-18 00:40:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` varchar(100) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `phone`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(2, 'test_so_gdtd', '$2y$10$Z3oV6jUjDJJAIe/Z9/fw0u8FPjoNra80qVrVZkKNotV3IRfFccCJS', NULL, 'so_gdtd', NULL, NULL, 1, '2026-10-22 21:13:14', '2026-09-20 00:13:48', '2026-10-22 21:13:14'),
(1311, '079211700001', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Văn An', 'candidate', 'thcs_nguyen_du.9a1.01@example.com', '0960000001', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1312, '079311700002', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thị Ánh', 'candidate', 'thcs_nguyen_du.9a1.02@example.com', '0960000002', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1313, '079211700003', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Văn Bình', 'candidate', 'thcs_nguyen_du.9a1.03@example.com', '0960000003', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1314, '079311700004', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thị Bích', 'candidate', 'thcs_nguyen_du.9a1.04@example.com', '0960000004', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1315, '079211700005', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Văn Cường', 'candidate', 'thcs_nguyen_du.9a1.05@example.com', '0960000005', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1316, '079311700006', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thị Chi', 'candidate', 'thcs_nguyen_du.9a1.06@example.com', '0960000006', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1317, '079211700007', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Văn Dũng', 'candidate', 'thcs_nguyen_du.9a1.07@example.com', '0960000007', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1318, '079311700008', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thị Dung', 'candidate', 'thcs_nguyen_du.9a1.08@example.com', '0960000008', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1319, '079211700009', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Văn Đạt', 'candidate', 'thcs_nguyen_du.9a1.09@example.com', '0960000009', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1320, '079311700010', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thị Duyên', 'candidate', 'thcs_nguyen_du.9a1.10@example.com', '0960000010', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1321, '079211700011', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Văn Duy', 'candidate', 'thcs_nguyen_du.9a1.11@example.com', '0960000011', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1322, '079311700012', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thị Giang', 'candidate', 'thcs_nguyen_du.9a1.12@example.com', '0960000012', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1323, '079211700013', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Văn Giang', 'candidate', 'thcs_nguyen_du.9a1.13@example.com', '0960000013', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1324, '079311700014', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thị Hà', 'candidate', 'thcs_nguyen_du.9a1.14@example.com', '0960000014', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1325, '079211700015', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Văn Hải', 'candidate', 'thcs_nguyen_du.9a1.15@example.com', '0960000015', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1326, '079311700016', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thị Hân', 'candidate', 'thcs_nguyen_du.9a1.16@example.com', '0960000016', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1327, '079211700017', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Văn Hiếu', 'candidate', 'thcs_nguyen_du.9a1.17@example.com', '0960000017', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1328, '079311700018', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thị Hằng', 'candidate', 'thcs_nguyen_du.9a1.18@example.com', '0960000018', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1329, '079211700019', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Văn Hoàng', 'candidate', 'thcs_nguyen_du.9a1.19@example.com', '0960000019', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1330, '079311700020', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thị Hạnh', 'candidate', 'thcs_nguyen_du.9a1.20@example.com', '0960000020', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1331, '079211700021', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Văn Huy', 'candidate', 'thcs_nguyen_du.9a1.21@example.com', '0960000021', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1332, '079311700022', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thị Hoa', 'candidate', 'thcs_nguyen_du.9a1.22@example.com', '0960000022', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1333, '079211700023', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Văn Hùng', 'candidate', 'thcs_nguyen_du.9a1.23@example.com', '0960000023', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1334, '079311700024', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thị Hương', 'candidate', 'thcs_nguyen_du.9a1.24@example.com', '0960000024', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1335, '079211700025', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Văn Hưng', 'candidate', 'thcs_nguyen_du.9a1.25@example.com', '0960000025', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1336, '079311700026', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thị Huyền', 'candidate', 'thcs_nguyen_du.9a1.26@example.com', '0960000026', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1337, '079211700027', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Văn Khoa', 'candidate', 'thcs_nguyen_du.9a1.27@example.com', '0960000027', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1338, '079311700028', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thị Lan', 'candidate', 'thcs_nguyen_du.9a1.28@example.com', '0960000028', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1339, '079211700029', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Văn Khang', 'candidate', 'thcs_nguyen_du.9a1.29@example.com', '0960000029', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1340, '079311700030', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thị Linh', 'candidate', 'thcs_nguyen_du.9a1.30@example.com', '0960000030', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1341, '079211700031', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Văn Khôi', 'candidate', 'thcs_nguyen_du.9a1.31@example.com', '0960000031', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1342, '079311700032', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thị Mai', 'candidate', 'thcs_nguyen_du.9a1.32@example.com', '0960000032', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1343, '079211700033', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Văn Lâm', 'candidate', 'thcs_nguyen_du.9a1.33@example.com', '0960000033', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1344, '079311700034', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thị My', 'candidate', 'thcs_nguyen_du.9a1.34@example.com', '0960000034', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1345, '079211700035', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Văn Long', 'candidate', 'thcs_nguyen_du.9a1.35@example.com', '0960000035', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1346, '079311700036', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thị Ngân', 'candidate', 'thcs_nguyen_du.9a1.36@example.com', '0960000036', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1347, '079211700037', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Văn Minh', 'candidate', 'thcs_nguyen_du.9a1.37@example.com', '0960000037', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1348, '079311700038', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thị Nhung', 'candidate', 'thcs_nguyen_du.9a1.38@example.com', '0960000038', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1349, '079211700039', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Văn Nam', 'candidate', 'thcs_nguyen_du.9a1.39@example.com', '0960000039', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1350, '079311700040', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thị Trang', 'candidate', 'thcs_nguyen_du.9a1.40@example.com', '0960000040', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1351, '079211700041', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Hữu An', 'candidate', 'thcs_nguyen_du.9a2.01@example.com', '0960000041', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1352, '079311700042', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Ngọc Ánh', 'candidate', 'thcs_nguyen_du.9a2.02@example.com', '0960000042', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1353, '079211700043', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Hữu Bình', 'candidate', 'thcs_nguyen_du.9a2.03@example.com', '0960000043', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1354, '079311700044', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Ngọc Bích', 'candidate', 'thcs_nguyen_du.9a2.04@example.com', '0960000044', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1355, '079211700045', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Hữu Cường', 'candidate', 'thcs_nguyen_du.9a2.05@example.com', '0960000045', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1356, '079311700046', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Ngọc Chi', 'candidate', 'thcs_nguyen_du.9a2.06@example.com', '0960000046', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1357, '079211700047', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Hữu Dũng', 'candidate', 'thcs_nguyen_du.9a2.07@example.com', '0960000047', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1358, '079311700048', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Ngọc Dung', 'candidate', 'thcs_nguyen_du.9a2.08@example.com', '0960000048', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1359, '079211700049', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Hữu Đạt', 'candidate', 'thcs_nguyen_du.9a2.09@example.com', '0960000049', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1360, '079311700050', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Ngọc Duyên', 'candidate', 'thcs_nguyen_du.9a2.10@example.com', '0960000050', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1361, '079211700051', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Hữu Duy', 'candidate', 'thcs_nguyen_du.9a2.11@example.com', '0960000051', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1362, '079311700052', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Ngọc Giang', 'candidate', 'thcs_nguyen_du.9a2.12@example.com', '0960000052', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1363, '079211700053', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Hữu Giang', 'candidate', 'thcs_nguyen_du.9a2.13@example.com', '0960000053', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1364, '079311700054', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Ngọc Hà', 'candidate', 'thcs_nguyen_du.9a2.14@example.com', '0960000054', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1365, '079211700055', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Hữu Hải', 'candidate', 'thcs_nguyen_du.9a2.15@example.com', '0960000055', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1366, '079311700056', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Ngọc Hân', 'candidate', 'thcs_nguyen_du.9a2.16@example.com', '0960000056', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1367, '079211700057', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Hữu Hiếu', 'candidate', 'thcs_nguyen_du.9a2.17@example.com', '0960000057', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1368, '079311700058', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Ngọc Hằng', 'candidate', 'thcs_nguyen_du.9a2.18@example.com', '0960000058', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1369, '079211700059', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Hữu Hoàng', 'candidate', 'thcs_nguyen_du.9a2.19@example.com', '0960000059', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1370, '079311700060', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Ngọc Hạnh', 'candidate', 'thcs_nguyen_du.9a2.20@example.com', '0960000060', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1371, '079211700061', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Hữu Huy', 'candidate', 'thcs_nguyen_du.9a2.21@example.com', '0960000061', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1372, '079311700062', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Ngọc Hoa', 'candidate', 'thcs_nguyen_du.9a2.22@example.com', '0960000062', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1373, '079211700063', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Hữu Hùng', 'candidate', 'thcs_nguyen_du.9a2.23@example.com', '0960000063', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1374, '079311700064', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Ngọc Hương', 'candidate', 'thcs_nguyen_du.9a2.24@example.com', '0960000064', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1375, '079211700065', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Hữu Hưng', 'candidate', 'thcs_nguyen_du.9a2.25@example.com', '0960000065', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1376, '079311700066', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Ngọc Huyền', 'candidate', 'thcs_nguyen_du.9a2.26@example.com', '0960000066', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1377, '079211700067', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Hữu Khoa', 'candidate', 'thcs_nguyen_du.9a2.27@example.com', '0960000067', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1378, '079311700068', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Ngọc Lan', 'candidate', 'thcs_nguyen_du.9a2.28@example.com', '0960000068', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1379, '079211700069', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Hữu Khang', 'candidate', 'thcs_nguyen_du.9a2.29@example.com', '0960000069', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1380, '079311700070', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Ngọc Linh', 'candidate', 'thcs_nguyen_du.9a2.30@example.com', '0960000070', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1381, '079211700071', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Hữu Khôi', 'candidate', 'thcs_nguyen_du.9a2.31@example.com', '0960000071', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1382, '079311700072', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Ngọc Mai', 'candidate', 'thcs_nguyen_du.9a2.32@example.com', '0960000072', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1383, '079211700073', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Hữu Lâm', 'candidate', 'thcs_nguyen_du.9a2.33@example.com', '0960000073', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1384, '079311700074', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Ngọc My', 'candidate', 'thcs_nguyen_du.9a2.34@example.com', '0960000074', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1385, '079211700075', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Hữu Long', 'candidate', 'thcs_nguyen_du.9a2.35@example.com', '0960000075', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1386, '079311700076', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Ngọc Ngân', 'candidate', 'thcs_nguyen_du.9a2.36@example.com', '0960000076', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1387, '079211700077', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Hữu Minh', 'candidate', 'thcs_nguyen_du.9a2.37@example.com', '0960000077', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1388, '079311700078', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Ngọc Nhung', 'candidate', 'thcs_nguyen_du.9a2.38@example.com', '0960000078', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1389, '079211700079', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Hữu Nam', 'candidate', 'thcs_nguyen_du.9a2.39@example.com', '0960000079', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1390, '079311700080', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Ngọc Trang', 'candidate', 'thcs_nguyen_du.9a2.40@example.com', '0960000080', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1391, '079211700081', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Đức An', 'candidate', 'thcs_nguyen_du.9a3.01@example.com', '0960000081', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1392, '079311700082', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Kim Ánh', 'candidate', 'thcs_nguyen_du.9a3.02@example.com', '0960000082', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1393, '079211700083', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Đức Bình', 'candidate', 'thcs_nguyen_du.9a3.03@example.com', '0960000083', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1394, '079311700084', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Kim Bích', 'candidate', 'thcs_nguyen_du.9a3.04@example.com', '0960000084', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1395, '079211700085', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Đức Cường', 'candidate', 'thcs_nguyen_du.9a3.05@example.com', '0960000085', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1396, '079311700086', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Kim Chi', 'candidate', 'thcs_nguyen_du.9a3.06@example.com', '0960000086', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1397, '079211700087', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Đức Dũng', 'candidate', 'thcs_nguyen_du.9a3.07@example.com', '0960000087', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1398, '079311700088', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Kim Dung', 'candidate', 'thcs_nguyen_du.9a3.08@example.com', '0960000088', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1399, '079211700089', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Đức Đạt', 'candidate', 'thcs_nguyen_du.9a3.09@example.com', '0960000089', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1400, '079311700090', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Kim Duyên', 'candidate', 'thcs_nguyen_du.9a3.10@example.com', '0960000090', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1401, '079211700091', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Đức Duy', 'candidate', 'thcs_nguyen_du.9a3.11@example.com', '0960000091', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1402, '079311700092', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Kim Giang', 'candidate', 'thcs_nguyen_du.9a3.12@example.com', '0960000092', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1403, '079211700093', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Đức Giang', 'candidate', 'thcs_nguyen_du.9a3.13@example.com', '0960000093', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1404, '079311700094', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Kim Hà', 'candidate', 'thcs_nguyen_du.9a3.14@example.com', '0960000094', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1405, '079211700095', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Đức Hải', 'candidate', 'thcs_nguyen_du.9a3.15@example.com', '0960000095', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1406, '079311700096', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Kim Hân', 'candidate', 'thcs_nguyen_du.9a3.16@example.com', '0960000096', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1407, '079211700097', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Đức Hiếu', 'candidate', 'thcs_nguyen_du.9a3.17@example.com', '0960000097', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1408, '079311700098', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Kim Hằng', 'candidate', 'thcs_nguyen_du.9a3.18@example.com', '0960000098', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1409, '079211700099', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Đức Hoàng', 'candidate', 'thcs_nguyen_du.9a3.19@example.com', '0960000099', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1410, '079311700100', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Kim Hạnh', 'candidate', 'thcs_nguyen_du.9a3.20@example.com', '0960000100', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1411, '079211700101', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Đức Huy', 'candidate', 'thcs_nguyen_du.9a3.21@example.com', '0960000101', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1412, '079311700102', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Kim Hoa', 'candidate', 'thcs_nguyen_du.9a3.22@example.com', '0960000102', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1413, '079211700103', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Đức Hùng', 'candidate', 'thcs_nguyen_du.9a3.23@example.com', '0960000103', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1414, '079311700104', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Kim Hương', 'candidate', 'thcs_nguyen_du.9a3.24@example.com', '0960000104', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1415, '079211700105', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Đức Hưng', 'candidate', 'thcs_nguyen_du.9a3.25@example.com', '0960000105', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1416, '079311700106', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Kim Huyền', 'candidate', 'thcs_nguyen_du.9a3.26@example.com', '0960000106', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1417, '079211700107', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Đức Khoa', 'candidate', 'thcs_nguyen_du.9a3.27@example.com', '0960000107', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1418, '079311700108', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Kim Lan', 'candidate', 'thcs_nguyen_du.9a3.28@example.com', '0960000108', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1419, '079211700109', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Đức Khang', 'candidate', 'thcs_nguyen_du.9a3.29@example.com', '0960000109', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1420, '079311700110', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Kim Linh', 'candidate', 'thcs_nguyen_du.9a3.30@example.com', '0960000110', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1421, '079211700111', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Đức Khôi', 'candidate', 'thcs_nguyen_du.9a3.31@example.com', '0960000111', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1422, '079311700112', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Kim Mai', 'candidate', 'thcs_nguyen_du.9a3.32@example.com', '0960000112', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1423, '079211700113', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Đức Lâm', 'candidate', 'thcs_nguyen_du.9a3.33@example.com', '0960000113', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1424, '079311700114', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Kim My', 'candidate', 'thcs_nguyen_du.9a3.34@example.com', '0960000114', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1425, '079211700115', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Đức Long', 'candidate', 'thcs_nguyen_du.9a3.35@example.com', '0960000115', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1426, '079311700116', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Kim Ngân', 'candidate', 'thcs_nguyen_du.9a3.36@example.com', '0960000116', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1427, '079211700117', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Đức Minh', 'candidate', 'thcs_nguyen_du.9a3.37@example.com', '0960000117', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1428, '079311700118', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Kim Nhung', 'candidate', 'thcs_nguyen_du.9a3.38@example.com', '0960000118', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1429, '079211700119', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Đức Nam', 'candidate', 'thcs_nguyen_du.9a3.39@example.com', '0960000119', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1430, '079311700120', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Kim Trang', 'candidate', 'thcs_nguyen_du.9a3.40@example.com', '0960000120', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1431, '079211700121', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Đình An', 'candidate', 'thcs_nguyen_du.9a4.01@example.com', '0960000121', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1432, '079311700122', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Mai Ánh', 'candidate', 'thcs_nguyen_du.9a4.02@example.com', '0960000122', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1433, '079211700123', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Đình Bình', 'candidate', 'thcs_nguyen_du.9a4.03@example.com', '0960000123', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1434, '079311700124', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Mai Bích', 'candidate', 'thcs_nguyen_du.9a4.04@example.com', '0960000124', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1435, '079211700125', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Đình Cường', 'candidate', 'thcs_nguyen_du.9a4.05@example.com', '0960000125', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1436, '079311700126', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Mai Chi', 'candidate', 'thcs_nguyen_du.9a4.06@example.com', '0960000126', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1437, '079211700127', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Đình Dũng', 'candidate', 'thcs_nguyen_du.9a4.07@example.com', '0960000127', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1438, '079311700128', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Mai Dung', 'candidate', 'thcs_nguyen_du.9a4.08@example.com', '0960000128', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1439, '079211700129', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Đình Đạt', 'candidate', 'thcs_nguyen_du.9a4.09@example.com', '0960000129', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1440, '079311700130', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Mai Duyên', 'candidate', 'thcs_nguyen_du.9a4.10@example.com', '0960000130', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1441, '079211700131', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Đình Duy', 'candidate', 'thcs_nguyen_du.9a4.11@example.com', '0960000131', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1442, '079311700132', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Mai Giang', 'candidate', 'thcs_nguyen_du.9a4.12@example.com', '0960000132', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1443, '079211700133', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Đình Giang', 'candidate', 'thcs_nguyen_du.9a4.13@example.com', '0960000133', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1444, '079311700134', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Mai Hà', 'candidate', 'thcs_nguyen_du.9a4.14@example.com', '0960000134', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1445, '079211700135', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Đình Hải', 'candidate', 'thcs_nguyen_du.9a4.15@example.com', '0960000135', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1446, '079311700136', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Mai Hân', 'candidate', 'thcs_nguyen_du.9a4.16@example.com', '0960000136', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1447, '079211700137', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Đình Hiếu', 'candidate', 'thcs_nguyen_du.9a4.17@example.com', '0960000137', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1448, '079311700138', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Mai Hằng', 'candidate', 'thcs_nguyen_du.9a4.18@example.com', '0960000138', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1449, '079211700139', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Đình Hoàng', 'candidate', 'thcs_nguyen_du.9a4.19@example.com', '0960000139', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1450, '079311700140', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Mai Hạnh', 'candidate', 'thcs_nguyen_du.9a4.20@example.com', '0960000140', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1451, '079211700141', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Đình Huy', 'candidate', 'thcs_nguyen_du.9a4.21@example.com', '0960000141', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1452, '079311700142', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Mai Hoa', 'candidate', 'thcs_nguyen_du.9a4.22@example.com', '0960000142', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1453, '079211700143', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Đình Hùng', 'candidate', 'thcs_nguyen_du.9a4.23@example.com', '0960000143', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1454, '079311700144', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Mai Hương', 'candidate', 'thcs_nguyen_du.9a4.24@example.com', '0960000144', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1455, '079211700145', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Đình Hưng', 'candidate', 'thcs_nguyen_du.9a4.25@example.com', '0960000145', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1456, '079311700146', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Mai Huyền', 'candidate', 'thcs_nguyen_du.9a4.26@example.com', '0960000146', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1457, '079211700147', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Đình Khoa', 'candidate', 'thcs_nguyen_du.9a4.27@example.com', '0960000147', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1458, '079311700148', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Mai Lan', 'candidate', 'thcs_nguyen_du.9a4.28@example.com', '0960000148', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1459, '079211700149', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Đình Khang', 'candidate', 'thcs_nguyen_du.9a4.29@example.com', '0960000149', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1460, '079311700150', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Mai Linh', 'candidate', 'thcs_nguyen_du.9a4.30@example.com', '0960000150', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1461, '079211700151', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Đình Khôi', 'candidate', 'thcs_nguyen_du.9a4.31@example.com', '0960000151', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1462, '079311700152', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Mai Mai', 'candidate', 'thcs_nguyen_du.9a4.32@example.com', '0960000152', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1463, '079211700153', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Đình Lâm', 'candidate', 'thcs_nguyen_du.9a4.33@example.com', '0960000153', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1464, '079311700154', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Mai My', 'candidate', 'thcs_nguyen_du.9a4.34@example.com', '0960000154', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1465, '079211700155', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Đình Long', 'candidate', 'thcs_nguyen_du.9a4.35@example.com', '0960000155', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1466, '079311700156', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Mai Ngân', 'candidate', 'thcs_nguyen_du.9a4.36@example.com', '0960000156', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1467, '079211700157', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Đình Minh', 'candidate', 'thcs_nguyen_du.9a4.37@example.com', '0960000157', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1468, '079311700158', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Mai Nhung', 'candidate', 'thcs_nguyen_du.9a4.38@example.com', '0960000158', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1469, '079211700159', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Đình Nam', 'candidate', 'thcs_nguyen_du.9a4.39@example.com', '0960000159', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1470, '079311700160', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Mai Trang', 'candidate', 'thcs_nguyen_du.9a4.40@example.com', '0960000160', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1471, '079211700161', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Quang An', 'candidate', 'thcs_nguyen_du.9a5.01@example.com', '0960000161', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1472, '079311700162', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Phương Ánh', 'candidate', 'thcs_nguyen_du.9a5.02@example.com', '0960000162', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1473, '079211700163', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Quang Bình', 'candidate', 'thcs_nguyen_du.9a5.03@example.com', '0960000163', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1474, '079311700164', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Phương Bích', 'candidate', 'thcs_nguyen_du.9a5.04@example.com', '0960000164', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1475, '079211700165', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Quang Cường', 'candidate', 'thcs_nguyen_du.9a5.05@example.com', '0960000165', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1476, '079311700166', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Phương Chi', 'candidate', 'thcs_nguyen_du.9a5.06@example.com', '0960000166', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1477, '079211700167', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Quang Dũng', 'candidate', 'thcs_nguyen_du.9a5.07@example.com', '0960000167', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1478, '079311700168', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Phương Dung', 'candidate', 'thcs_nguyen_du.9a5.08@example.com', '0960000168', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1479, '079211700169', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Quang Đạt', 'candidate', 'thcs_nguyen_du.9a5.09@example.com', '0960000169', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1480, '079311700170', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Phương Duyên', 'candidate', 'thcs_nguyen_du.9a5.10@example.com', '0960000170', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1481, '079211700171', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Quang Duy', 'candidate', 'thcs_nguyen_du.9a5.11@example.com', '0960000171', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1482, '079311700172', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Phương Giang', 'candidate', 'thcs_nguyen_du.9a5.12@example.com', '0960000172', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1483, '079211700173', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Quang Giang', 'candidate', 'thcs_nguyen_du.9a5.13@example.com', '0960000173', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1484, '079311700174', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Phương Hà', 'candidate', 'thcs_nguyen_du.9a5.14@example.com', '0960000174', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1485, '079211700175', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Quang Hải', 'candidate', 'thcs_nguyen_du.9a5.15@example.com', '0960000175', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1486, '079311700176', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Phương Hân', 'candidate', 'thcs_nguyen_du.9a5.16@example.com', '0960000176', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1487, '079211700177', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Quang Hiếu', 'candidate', 'thcs_nguyen_du.9a5.17@example.com', '0960000177', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1488, '079311700178', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Phương Hằng', 'candidate', 'thcs_nguyen_du.9a5.18@example.com', '0960000178', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1489, '079211700179', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Quang Hoàng', 'candidate', 'thcs_nguyen_du.9a5.19@example.com', '0960000179', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1490, '079311700180', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Phương Hạnh', 'candidate', 'thcs_nguyen_du.9a5.20@example.com', '0960000180', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1491, '079211700181', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Quang Huy', 'candidate', 'thcs_nguyen_du.9a5.21@example.com', '0960000181', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1492, '079311700182', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Phương Hoa', 'candidate', 'thcs_nguyen_du.9a5.22@example.com', '0960000182', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1493, '079211700183', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Quang Hùng', 'candidate', 'thcs_nguyen_du.9a5.23@example.com', '0960000183', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1494, '079311700184', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Phương Hương', 'candidate', 'thcs_nguyen_du.9a5.24@example.com', '0960000184', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1495, '079211700185', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Quang Hưng', 'candidate', 'thcs_nguyen_du.9a5.25@example.com', '0960000185', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1496, '079311700186', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Phương Huyền', 'candidate', 'thcs_nguyen_du.9a5.26@example.com', '0960000186', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1497, '079211700187', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Quang Khoa', 'candidate', 'thcs_nguyen_du.9a5.27@example.com', '0960000187', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1498, '079311700188', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Phương Lan', 'candidate', 'thcs_nguyen_du.9a5.28@example.com', '0960000188', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1499, '079211700189', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Quang Khang', 'candidate', 'thcs_nguyen_du.9a5.29@example.com', '0960000189', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1500, '079311700190', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Phương Linh', 'candidate', 'thcs_nguyen_du.9a5.30@example.com', '0960000190', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1501, '079211700191', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Quang Khôi', 'candidate', 'thcs_nguyen_du.9a5.31@example.com', '0960000191', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1502, '079311700192', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Phương Mai', 'candidate', 'thcs_nguyen_du.9a5.32@example.com', '0960000192', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1503, '079211700193', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Quang Lâm', 'candidate', 'thcs_nguyen_du.9a5.33@example.com', '0960000193', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1504, '079311700194', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Phương My', 'candidate', 'thcs_nguyen_du.9a5.34@example.com', '0960000194', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1505, '079211700195', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Quang Long', 'candidate', 'thcs_nguyen_du.9a5.35@example.com', '0960000195', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1506, '079311700196', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Phương Ngân', 'candidate', 'thcs_nguyen_du.9a5.36@example.com', '0960000196', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1507, '079211700197', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Quang Minh', 'candidate', 'thcs_nguyen_du.9a5.37@example.com', '0960000197', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1508, '079311700198', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Phương Nhung', 'candidate', 'thcs_nguyen_du.9a5.38@example.com', '0960000198', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1509, '079211700199', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Quang Nam', 'candidate', 'thcs_nguyen_du.9a5.39@example.com', '0960000199', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1510, '079311700200', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Phương Trang', 'candidate', 'thcs_nguyen_du.9a5.40@example.com', '0960000200', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1511, '079211700201', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Minh An', 'candidate', 'thcs_nguyen_du.9a6.01@example.com', '0960000201', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1512, '079311700202', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thảo Ánh', 'candidate', 'thcs_nguyen_du.9a6.02@example.com', '0960000202', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1513, '079211700203', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Minh Bình', 'candidate', 'thcs_nguyen_du.9a6.03@example.com', '0960000203', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1514, '079311700204', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thảo Bích', 'candidate', 'thcs_nguyen_du.9a6.04@example.com', '0960000204', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1515, '079211700205', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Minh Cường', 'candidate', 'thcs_nguyen_du.9a6.05@example.com', '0960000205', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1516, '079311700206', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thảo Chi', 'candidate', 'thcs_nguyen_du.9a6.06@example.com', '0960000206', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1517, '079211700207', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Minh Dũng', 'candidate', 'thcs_nguyen_du.9a6.07@example.com', '0960000207', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1518, '079311700208', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thảo Dung', 'candidate', 'thcs_nguyen_du.9a6.08@example.com', '0960000208', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1519, '079211700209', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Minh Đạt', 'candidate', 'thcs_nguyen_du.9a6.09@example.com', '0960000209', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1520, '079311700210', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thảo Duyên', 'candidate', 'thcs_nguyen_du.9a6.10@example.com', '0960000210', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1521, '079211700211', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Minh Duy', 'candidate', 'thcs_nguyen_du.9a6.11@example.com', '0960000211', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1522, '079311700212', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thảo Giang', 'candidate', 'thcs_nguyen_du.9a6.12@example.com', '0960000212', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1523, '079211700213', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Minh Giang', 'candidate', 'thcs_nguyen_du.9a6.13@example.com', '0960000213', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1524, '079311700214', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thảo Hà', 'candidate', 'thcs_nguyen_du.9a6.14@example.com', '0960000214', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1525, '079211700215', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Minh Hải', 'candidate', 'thcs_nguyen_du.9a6.15@example.com', '0960000215', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1526, '079311700216', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thảo Hân', 'candidate', 'thcs_nguyen_du.9a6.16@example.com', '0960000216', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1527, '079211700217', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Minh Hiếu', 'candidate', 'thcs_nguyen_du.9a6.17@example.com', '0960000217', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1528, '079311700218', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thảo Hằng', 'candidate', 'thcs_nguyen_du.9a6.18@example.com', '0960000218', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1529, '079211700219', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Minh Hoàng', 'candidate', 'thcs_nguyen_du.9a6.19@example.com', '0960000219', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1530, '079311700220', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thảo Hạnh', 'candidate', 'thcs_nguyen_du.9a6.20@example.com', '0960000220', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1531, '079211700221', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Minh Huy', 'candidate', 'thcs_nguyen_du.9a6.21@example.com', '0960000221', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1532, '079311700222', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thảo Hoa', 'candidate', 'thcs_nguyen_du.9a6.22@example.com', '0960000222', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1533, '079211700223', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Minh Hùng', 'candidate', 'thcs_nguyen_du.9a6.23@example.com', '0960000223', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12');
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `phone`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1534, '079311700224', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thảo Hương', 'candidate', 'thcs_nguyen_du.9a6.24@example.com', '0960000224', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1535, '079211700225', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Minh Hưng', 'candidate', 'thcs_nguyen_du.9a6.25@example.com', '0960000225', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1536, '079311700226', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thảo Huyền', 'candidate', 'thcs_nguyen_du.9a6.26@example.com', '0960000226', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1537, '079211700227', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Minh Khoa', 'candidate', 'thcs_nguyen_du.9a6.27@example.com', '0960000227', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1538, '079311700228', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thảo Lan', 'candidate', 'thcs_nguyen_du.9a6.28@example.com', '0960000228', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1539, '079211700229', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Minh Khang', 'candidate', 'thcs_nguyen_du.9a6.29@example.com', '0960000229', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1540, '079311700230', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thảo Linh', 'candidate', 'thcs_nguyen_du.9a6.30@example.com', '0960000230', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1541, '079211700231', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Minh Khôi', 'candidate', 'thcs_nguyen_du.9a6.31@example.com', '0960000231', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1542, '079311700232', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thảo Mai', 'candidate', 'thcs_nguyen_du.9a6.32@example.com', '0960000232', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1543, '079211700233', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Minh Lâm', 'candidate', 'thcs_nguyen_du.9a6.33@example.com', '0960000233', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1544, '079311700234', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thảo My', 'candidate', 'thcs_nguyen_du.9a6.34@example.com', '0960000234', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1545, '079211700235', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Minh Long', 'candidate', 'thcs_nguyen_du.9a6.35@example.com', '0960000235', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1546, '079311700236', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thảo Ngân', 'candidate', 'thcs_nguyen_du.9a6.36@example.com', '0960000236', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1547, '079211700237', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Minh Minh', 'candidate', 'thcs_nguyen_du.9a6.37@example.com', '0960000237', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1548, '079311700238', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thảo Nhung', 'candidate', 'thcs_nguyen_du.9a6.38@example.com', '0960000238', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1549, '079211700239', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Minh Nam', 'candidate', 'thcs_nguyen_du.9a6.39@example.com', '0960000239', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1550, '079311700240', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thảo Trang', 'candidate', 'thcs_nguyen_du.9a6.40@example.com', '0960000240', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1551, '079211700241', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Tuấn An', 'candidate', 'thcs_nguyen_du.9a7.01@example.com', '0960000241', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1552, '079311700242', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Khánh Ánh', 'candidate', 'thcs_nguyen_du.9a7.02@example.com', '0960000242', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1553, '079211700243', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Tuấn Bình', 'candidate', 'thcs_nguyen_du.9a7.03@example.com', '0960000243', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1554, '079311700244', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Khánh Bích', 'candidate', 'thcs_nguyen_du.9a7.04@example.com', '0960000244', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1555, '079211700245', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Tuấn Cường', 'candidate', 'thcs_nguyen_du.9a7.05@example.com', '0960000245', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1556, '079311700246', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Khánh Chi', 'candidate', 'thcs_nguyen_du.9a7.06@example.com', '0960000246', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1557, '079211700247', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Tuấn Dũng', 'candidate', 'thcs_nguyen_du.9a7.07@example.com', '0960000247', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1558, '079311700248', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Khánh Dung', 'candidate', 'thcs_nguyen_du.9a7.08@example.com', '0960000248', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1559, '079211700249', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Tuấn Đạt', 'candidate', 'thcs_nguyen_du.9a7.09@example.com', '0960000249', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1560, '079311700250', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Khánh Duyên', 'candidate', 'thcs_nguyen_du.9a7.10@example.com', '0960000250', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1561, '079211700251', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Tuấn Duy', 'candidate', 'thcs_nguyen_du.9a7.11@example.com', '0960000251', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1562, '079311700252', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Khánh Giang', 'candidate', 'thcs_nguyen_du.9a7.12@example.com', '0960000252', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1563, '079211700253', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Tuấn Giang', 'candidate', 'thcs_nguyen_du.9a7.13@example.com', '0960000253', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1564, '079311700254', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Khánh Hà', 'candidate', 'thcs_nguyen_du.9a7.14@example.com', '0960000254', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1565, '079211700255', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Tuấn Hải', 'candidate', 'thcs_nguyen_du.9a7.15@example.com', '0960000255', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1566, '079311700256', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Khánh Hân', 'candidate', 'thcs_nguyen_du.9a7.16@example.com', '0960000256', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1567, '079211700257', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Tuấn Hiếu', 'candidate', 'thcs_nguyen_du.9a7.17@example.com', '0960000257', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1568, '079311700258', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Khánh Hằng', 'candidate', 'thcs_nguyen_du.9a7.18@example.com', '0960000258', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1569, '079211700259', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Tuấn Hoàng', 'candidate', 'thcs_nguyen_du.9a7.19@example.com', '0960000259', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1570, '079311700260', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Khánh Hạnh', 'candidate', 'thcs_nguyen_du.9a7.20@example.com', '0960000260', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1571, '079211700261', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Tuấn Huy', 'candidate', 'thcs_nguyen_du.9a7.21@example.com', '0960000261', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1572, '079311700262', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Khánh Hoa', 'candidate', 'thcs_nguyen_du.9a7.22@example.com', '0960000262', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1573, '079211700263', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Tuấn Hùng', 'candidate', 'thcs_nguyen_du.9a7.23@example.com', '0960000263', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1574, '079311700264', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Khánh Hương', 'candidate', 'thcs_nguyen_du.9a7.24@example.com', '0960000264', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1575, '079211700265', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Tuấn Hưng', 'candidate', 'thcs_nguyen_du.9a7.25@example.com', '0960000265', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1576, '079311700266', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Khánh Huyền', 'candidate', 'thcs_nguyen_du.9a7.26@example.com', '0960000266', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1577, '079211700267', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Tuấn Khoa', 'candidate', 'thcs_nguyen_du.9a7.27@example.com', '0960000267', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1578, '079311700268', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Khánh Lan', 'candidate', 'thcs_nguyen_du.9a7.28@example.com', '0960000268', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1579, '079211700269', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Tuấn Khang', 'candidate', 'thcs_nguyen_du.9a7.29@example.com', '0960000269', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1580, '079311700270', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Khánh Linh', 'candidate', 'thcs_nguyen_du.9a7.30@example.com', '0960000270', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1581, '079211700271', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Tuấn Khôi', 'candidate', 'thcs_nguyen_du.9a7.31@example.com', '0960000271', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1582, '079311700272', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Khánh Mai', 'candidate', 'thcs_nguyen_du.9a7.32@example.com', '0960000272', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1583, '079211700273', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Tuấn Lâm', 'candidate', 'thcs_nguyen_du.9a7.33@example.com', '0960000273', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1584, '079311700274', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Khánh My', 'candidate', 'thcs_nguyen_du.9a7.34@example.com', '0960000274', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1585, '079211700275', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Tuấn Long', 'candidate', 'thcs_nguyen_du.9a7.35@example.com', '0960000275', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1586, '079311700276', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Khánh Ngân', 'candidate', 'thcs_nguyen_du.9a7.36@example.com', '0960000276', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1587, '079211700277', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Tuấn Minh', 'candidate', 'thcs_nguyen_du.9a7.37@example.com', '0960000277', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1588, '079311700278', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Khánh Nhung', 'candidate', 'thcs_nguyen_du.9a7.38@example.com', '0960000278', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1589, '079211700279', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Tuấn Nam', 'candidate', 'thcs_nguyen_du.9a7.39@example.com', '0960000279', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1590, '079311700280', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Khánh Trang', 'candidate', 'thcs_nguyen_du.9a7.40@example.com', '0960000280', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1591, '079211700281', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Gia An', 'candidate', 'thcs_nguyen_du.9a8.01@example.com', '0960000281', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1592, '079311700282', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Quỳnh Ánh', 'candidate', 'thcs_nguyen_du.9a8.02@example.com', '0960000282', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1593, '079211700283', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Gia Bình', 'candidate', 'thcs_nguyen_du.9a8.03@example.com', '0960000283', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1594, '079311700284', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Quỳnh Bích', 'candidate', 'thcs_nguyen_du.9a8.04@example.com', '0960000284', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1595, '079211700285', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Gia Cường', 'candidate', 'thcs_nguyen_du.9a8.05@example.com', '0960000285', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1596, '079311700286', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Quỳnh Chi', 'candidate', 'thcs_nguyen_du.9a8.06@example.com', '0960000286', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1597, '079211700287', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Gia Dũng', 'candidate', 'thcs_nguyen_du.9a8.07@example.com', '0960000287', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1598, '079311700288', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Quỳnh Dung', 'candidate', 'thcs_nguyen_du.9a8.08@example.com', '0960000288', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1599, '079211700289', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Gia Đạt', 'candidate', 'thcs_nguyen_du.9a8.09@example.com', '0960000289', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1600, '079311700290', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Quỳnh Duyên', 'candidate', 'thcs_nguyen_du.9a8.10@example.com', '0960000290', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1601, '079211700291', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Gia Duy', 'candidate', 'thcs_nguyen_du.9a8.11@example.com', '0960000291', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1602, '079311700292', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Quỳnh Giang', 'candidate', 'thcs_nguyen_du.9a8.12@example.com', '0960000292', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1603, '079211700293', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Gia Giang', 'candidate', 'thcs_nguyen_du.9a8.13@example.com', '0960000293', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1604, '079311700294', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Quỳnh Hà', 'candidate', 'thcs_nguyen_du.9a8.14@example.com', '0960000294', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1605, '079211700295', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Gia Hải', 'candidate', 'thcs_nguyen_du.9a8.15@example.com', '0960000295', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1606, '079311700296', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Quỳnh Hân', 'candidate', 'thcs_nguyen_du.9a8.16@example.com', '0960000296', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1607, '079211700297', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Gia Hiếu', 'candidate', 'thcs_nguyen_du.9a8.17@example.com', '0960000297', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1608, '079311700298', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Quỳnh Hằng', 'candidate', 'thcs_nguyen_du.9a8.18@example.com', '0960000298', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1609, '079211700299', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Gia Hoàng', 'candidate', 'thcs_nguyen_du.9a8.19@example.com', '0960000299', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1610, '079311700300', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Quỳnh Hạnh', 'candidate', 'thcs_nguyen_du.9a8.20@example.com', '0960000300', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1611, '079211700301', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Gia Huy', 'candidate', 'thcs_nguyen_du.9a8.21@example.com', '0960000301', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1612, '079311700302', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Quỳnh Hoa', 'candidate', 'thcs_nguyen_du.9a8.22@example.com', '0960000302', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1613, '079211700303', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Gia Hùng', 'candidate', 'thcs_nguyen_du.9a8.23@example.com', '0960000303', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1614, '079311700304', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Quỳnh Hương', 'candidate', 'thcs_nguyen_du.9a8.24@example.com', '0960000304', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1615, '079211700305', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Gia Hưng', 'candidate', 'thcs_nguyen_du.9a8.25@example.com', '0960000305', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1616, '079311700306', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Quỳnh Huyền', 'candidate', 'thcs_nguyen_du.9a8.26@example.com', '0960000306', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1617, '079211700307', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Gia Khoa', 'candidate', 'thcs_nguyen_du.9a8.27@example.com', '0960000307', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1618, '079311700308', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Quỳnh Lan', 'candidate', 'thcs_nguyen_du.9a8.28@example.com', '0960000308', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1619, '079211700309', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Gia Khang', 'candidate', 'thcs_nguyen_du.9a8.29@example.com', '0960000309', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1620, '079311700310', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Quỳnh Linh', 'candidate', 'thcs_nguyen_du.9a8.30@example.com', '0960000310', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1621, '079211700311', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Gia Khôi', 'candidate', 'thcs_nguyen_du.9a8.31@example.com', '0960000311', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1622, '079311700312', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Quỳnh Mai', 'candidate', 'thcs_nguyen_du.9a8.32@example.com', '0960000312', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1623, '079211700313', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Gia Lâm', 'candidate', 'thcs_nguyen_du.9a8.33@example.com', '0960000313', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1624, '079311700314', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Quỳnh My', 'candidate', 'thcs_nguyen_du.9a8.34@example.com', '0960000314', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1625, '079211700315', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Gia Long', 'candidate', 'thcs_nguyen_du.9a8.35@example.com', '0960000315', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1626, '079311700316', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Quỳnh Ngân', 'candidate', 'thcs_nguyen_du.9a8.36@example.com', '0960000316', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1627, '079211700317', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Gia Minh', 'candidate', 'thcs_nguyen_du.9a8.37@example.com', '0960000317', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1628, '079311700318', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Quỳnh Nhung', 'candidate', 'thcs_nguyen_du.9a8.38@example.com', '0960000318', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1629, '079211700319', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Gia Nam', 'candidate', 'thcs_nguyen_du.9a8.39@example.com', '0960000319', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1630, '079311700320', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Quỳnh Trang', 'candidate', 'thcs_nguyen_du.9a8.40@example.com', '0960000320', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1631, '079211700321', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Bảo An', 'candidate', 'thcs_nguyen_du.9a9.01@example.com', '0960000321', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1632, '079311700322', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thanh Ánh', 'candidate', 'thcs_nguyen_du.9a9.02@example.com', '0960000322', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1633, '079211700323', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Bảo Bình', 'candidate', 'thcs_nguyen_du.9a9.03@example.com', '0960000323', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1634, '079311700324', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thanh Bích', 'candidate', 'thcs_nguyen_du.9a9.04@example.com', '0960000324', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1635, '079211700325', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Bảo Cường', 'candidate', 'thcs_nguyen_du.9a9.05@example.com', '0960000325', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1636, '079311700326', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thanh Chi', 'candidate', 'thcs_nguyen_du.9a9.06@example.com', '0960000326', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1637, '079211700327', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Bảo Dũng', 'candidate', 'thcs_nguyen_du.9a9.07@example.com', '0960000327', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1638, '079311700328', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thanh Dung', 'candidate', 'thcs_nguyen_du.9a9.08@example.com', '0960000328', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1639, '079211700329', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Bảo Đạt', 'candidate', 'thcs_nguyen_du.9a9.09@example.com', '0960000329', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1640, '079311700330', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thanh Duyên', 'candidate', 'thcs_nguyen_du.9a9.10@example.com', '0960000330', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1641, '079211700331', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Bảo Duy', 'candidate', 'thcs_nguyen_du.9a9.11@example.com', '0960000331', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1642, '079311700332', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thanh Giang', 'candidate', 'thcs_nguyen_du.9a9.12@example.com', '0960000332', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1643, '079211700333', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Bảo Giang', 'candidate', 'thcs_nguyen_du.9a9.13@example.com', '0960000333', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1644, '079311700334', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thanh Hà', 'candidate', 'thcs_nguyen_du.9a9.14@example.com', '0960000334', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1645, '079211700335', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Bảo Hải', 'candidate', 'thcs_nguyen_du.9a9.15@example.com', '0960000335', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1646, '079311700336', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thanh Hân', 'candidate', 'thcs_nguyen_du.9a9.16@example.com', '0960000336', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1647, '079211700337', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Bảo Hiếu', 'candidate', 'thcs_nguyen_du.9a9.17@example.com', '0960000337', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1648, '079311700338', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thanh Hằng', 'candidate', 'thcs_nguyen_du.9a9.18@example.com', '0960000338', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1649, '079211700339', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Bảo Hoàng', 'candidate', 'thcs_nguyen_du.9a9.19@example.com', '0960000339', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1650, '079311700340', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thanh Hạnh', 'candidate', 'thcs_nguyen_du.9a9.20@example.com', '0960000340', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1651, '079211700341', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trần Bảo Huy', 'candidate', 'thcs_nguyen_du.9a9.21@example.com', '0960000341', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1652, '079311700342', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lê Thanh Hoa', 'candidate', 'thcs_nguyen_du.9a9.22@example.com', '0960000342', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1653, '079211700343', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phạm Bảo Hùng', 'candidate', 'thcs_nguyen_du.9a9.23@example.com', '0960000343', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1654, '079311700344', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hoàng Thanh Hương', 'candidate', 'thcs_nguyen_du.9a9.24@example.com', '0960000344', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1655, '079211700345', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Huỳnh Bảo Hưng', 'candidate', 'thcs_nguyen_du.9a9.25@example.com', '0960000345', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1656, '079311700346', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Phan Thanh Huyền', 'candidate', 'thcs_nguyen_du.9a9.26@example.com', '0960000346', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1657, '079211700347', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Vũ Bảo Khoa', 'candidate', 'thcs_nguyen_du.9a9.27@example.com', '0960000347', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1658, '079311700348', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Võ Thanh Lan', 'candidate', 'thcs_nguyen_du.9a9.28@example.com', '0960000348', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1659, '079211700349', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đặng Bảo Khang', 'candidate', 'thcs_nguyen_du.9a9.29@example.com', '0960000349', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1660, '079311700350', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Bùi Thanh Linh', 'candidate', 'thcs_nguyen_du.9a9.30@example.com', '0960000350', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1661, '079211700351', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đỗ Bảo Khôi', 'candidate', 'thcs_nguyen_du.9a9.31@example.com', '0960000351', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1662, '079311700352', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Hồ Thanh Mai', 'candidate', 'thcs_nguyen_du.9a9.32@example.com', '0960000352', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1663, '079211700353', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Ngô Bảo Lâm', 'candidate', 'thcs_nguyen_du.9a9.33@example.com', '0960000353', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1664, '079311700354', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Dương Thanh My', 'candidate', 'thcs_nguyen_du.9a9.34@example.com', '0960000354', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1665, '079211700355', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lý Bảo Long', 'candidate', 'thcs_nguyen_du.9a9.35@example.com', '0960000355', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1666, '079311700356', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đinh Thanh Ngân', 'candidate', 'thcs_nguyen_du.9a9.36@example.com', '0960000356', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1667, '079211700357', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Đoàn Bảo Minh', 'candidate', 'thcs_nguyen_du.9a9.37@example.com', '0960000357', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1668, '079311700358', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Lâm Thanh Nhung', 'candidate', 'thcs_nguyen_du.9a9.38@example.com', '0960000358', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1669, '079211700359', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Trịnh Bảo Nam', 'candidate', 'thcs_nguyen_du.9a9.39@example.com', '0960000359', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1670, '079311700360', '$2y$10$OBppds3AegA.N5D/i6ovs.ZAX/584WH6U4Mh.UoOAS6xYhmt8COAe', 'Nguyễn Thanh Trang', 'candidate', 'thcs_nguyen_du.9a9.40@example.com', '0960000360', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1671, '079211700361', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Hữu An', 'candidate', 'thcs_le_quy_don.9a1.01@example.com', '0960000361', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1672, '079311700362', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Ngọc Ánh', 'candidate', 'thcs_le_quy_don.9a1.02@example.com', '0960000362', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1673, '079211700363', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Hữu Bình', 'candidate', 'thcs_le_quy_don.9a1.03@example.com', '0960000363', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1674, '079311700364', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Ngọc Bích', 'candidate', 'thcs_le_quy_don.9a1.04@example.com', '0960000364', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1675, '079211700365', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Hữu Cường', 'candidate', 'thcs_le_quy_don.9a1.05@example.com', '0960000365', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1676, '079311700366', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Ngọc Chi', 'candidate', 'thcs_le_quy_don.9a1.06@example.com', '0960000366', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1677, '079211700367', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Hữu Dũng', 'candidate', 'thcs_le_quy_don.9a1.07@example.com', '0960000367', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1678, '079311700368', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Ngọc Dung', 'candidate', 'thcs_le_quy_don.9a1.08@example.com', '0960000368', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1679, '079211700369', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Hữu Đạt', 'candidate', 'thcs_le_quy_don.9a1.09@example.com', '0960000369', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1680, '079311700370', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Ngọc Duyên', 'candidate', 'thcs_le_quy_don.9a1.10@example.com', '0960000370', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1681, '079211700371', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Hữu Duy', 'candidate', 'thcs_le_quy_don.9a1.11@example.com', '0960000371', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1682, '079311700372', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Ngọc Giang', 'candidate', 'thcs_le_quy_don.9a1.12@example.com', '0960000372', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1683, '079211700373', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Hữu Giang', 'candidate', 'thcs_le_quy_don.9a1.13@example.com', '0960000373', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1684, '079311700374', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Ngọc Hà', 'candidate', 'thcs_le_quy_don.9a1.14@example.com', '0960000374', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1685, '079211700375', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Hữu Hải', 'candidate', 'thcs_le_quy_don.9a1.15@example.com', '0960000375', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1686, '079311700376', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Ngọc Hân', 'candidate', 'thcs_le_quy_don.9a1.16@example.com', '0960000376', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1687, '079211700377', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Hữu Hiếu', 'candidate', 'thcs_le_quy_don.9a1.17@example.com', '0960000377', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1688, '079311700378', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Ngọc Hằng', 'candidate', 'thcs_le_quy_don.9a1.18@example.com', '0960000378', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1689, '079211700379', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Hữu Hoàng', 'candidate', 'thcs_le_quy_don.9a1.19@example.com', '0960000379', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1690, '079311700380', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Ngọc Hạnh', 'candidate', 'thcs_le_quy_don.9a1.20@example.com', '0960000380', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1691, '079211700381', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Hữu Huy', 'candidate', 'thcs_le_quy_don.9a1.21@example.com', '0960000381', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1692, '079311700382', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Ngọc Hoa', 'candidate', 'thcs_le_quy_don.9a1.22@example.com', '0960000382', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1693, '079211700383', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Hữu Hùng', 'candidate', 'thcs_le_quy_don.9a1.23@example.com', '0960000383', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1694, '079311700384', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Ngọc Hương', 'candidate', 'thcs_le_quy_don.9a1.24@example.com', '0960000384', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1695, '079211700385', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Hữu Hưng', 'candidate', 'thcs_le_quy_don.9a1.25@example.com', '0960000385', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1696, '079311700386', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Ngọc Huyền', 'candidate', 'thcs_le_quy_don.9a1.26@example.com', '0960000386', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1697, '079211700387', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Hữu Khoa', 'candidate', 'thcs_le_quy_don.9a1.27@example.com', '0960000387', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1698, '079311700388', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Ngọc Lan', 'candidate', 'thcs_le_quy_don.9a1.28@example.com', '0960000388', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1699, '079211700389', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Hữu Khang', 'candidate', 'thcs_le_quy_don.9a1.29@example.com', '0960000389', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1700, '079311700390', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Ngọc Linh', 'candidate', 'thcs_le_quy_don.9a1.30@example.com', '0960000390', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1701, '079211700391', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Hữu Khôi', 'candidate', 'thcs_le_quy_don.9a1.31@example.com', '0960000391', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1702, '079311700392', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Ngọc Mai', 'candidate', 'thcs_le_quy_don.9a1.32@example.com', '0960000392', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1703, '079211700393', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Hữu Lâm', 'candidate', 'thcs_le_quy_don.9a1.33@example.com', '0960000393', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1704, '079311700394', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Ngọc My', 'candidate', 'thcs_le_quy_don.9a1.34@example.com', '0960000394', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1705, '079211700395', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Hữu Long', 'candidate', 'thcs_le_quy_don.9a1.35@example.com', '0960000395', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1706, '079311700396', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Ngọc Ngân', 'candidate', 'thcs_le_quy_don.9a1.36@example.com', '0960000396', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1707, '079211700397', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Hữu Minh', 'candidate', 'thcs_le_quy_don.9a1.37@example.com', '0960000397', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1708, '079311700398', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Ngọc Nhung', 'candidate', 'thcs_le_quy_don.9a1.38@example.com', '0960000398', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1709, '079211700399', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Hữu Nam', 'candidate', 'thcs_le_quy_don.9a1.39@example.com', '0960000399', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1710, '079311700400', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Ngọc Trang', 'candidate', 'thcs_le_quy_don.9a1.40@example.com', '0960000400', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1711, '079211700401', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Đức An', 'candidate', 'thcs_le_quy_don.9a2.01@example.com', '0960000401', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1712, '079311700402', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Kim Ánh', 'candidate', 'thcs_le_quy_don.9a2.02@example.com', '0960000402', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1713, '079211700403', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Đức Bình', 'candidate', 'thcs_le_quy_don.9a2.03@example.com', '0960000403', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1714, '079311700404', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Kim Bích', 'candidate', 'thcs_le_quy_don.9a2.04@example.com', '0960000404', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1715, '079211700405', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Đức Cường', 'candidate', 'thcs_le_quy_don.9a2.05@example.com', '0960000405', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1716, '079311700406', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Kim Chi', 'candidate', 'thcs_le_quy_don.9a2.06@example.com', '0960000406', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1717, '079211700407', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Đức Dũng', 'candidate', 'thcs_le_quy_don.9a2.07@example.com', '0960000407', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1718, '079311700408', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Kim Dung', 'candidate', 'thcs_le_quy_don.9a2.08@example.com', '0960000408', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1719, '079211700409', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Đức Đạt', 'candidate', 'thcs_le_quy_don.9a2.09@example.com', '0960000409', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1720, '079311700410', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Kim Duyên', 'candidate', 'thcs_le_quy_don.9a2.10@example.com', '0960000410', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1721, '079211700411', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Đức Duy', 'candidate', 'thcs_le_quy_don.9a2.11@example.com', '0960000411', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1722, '079311700412', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Kim Giang', 'candidate', 'thcs_le_quy_don.9a2.12@example.com', '0960000412', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1723, '079211700413', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Đức Giang', 'candidate', 'thcs_le_quy_don.9a2.13@example.com', '0960000413', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1724, '079311700414', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Kim Hà', 'candidate', 'thcs_le_quy_don.9a2.14@example.com', '0960000414', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1725, '079211700415', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Đức Hải', 'candidate', 'thcs_le_quy_don.9a2.15@example.com', '0960000415', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1726, '079311700416', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Kim Hân', 'candidate', 'thcs_le_quy_don.9a2.16@example.com', '0960000416', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1727, '079211700417', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Đức Hiếu', 'candidate', 'thcs_le_quy_don.9a2.17@example.com', '0960000417', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1728, '079311700418', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Kim Hằng', 'candidate', 'thcs_le_quy_don.9a2.18@example.com', '0960000418', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1729, '079211700419', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Đức Hoàng', 'candidate', 'thcs_le_quy_don.9a2.19@example.com', '0960000419', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1730, '079311700420', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Kim Hạnh', 'candidate', 'thcs_le_quy_don.9a2.20@example.com', '0960000420', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1731, '079211700421', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Đức Huy', 'candidate', 'thcs_le_quy_don.9a2.21@example.com', '0960000421', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1732, '079311700422', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Kim Hoa', 'candidate', 'thcs_le_quy_don.9a2.22@example.com', '0960000422', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1733, '079211700423', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Đức Hùng', 'candidate', 'thcs_le_quy_don.9a2.23@example.com', '0960000423', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1734, '079311700424', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Kim Hương', 'candidate', 'thcs_le_quy_don.9a2.24@example.com', '0960000424', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1735, '079211700425', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Đức Hưng', 'candidate', 'thcs_le_quy_don.9a2.25@example.com', '0960000425', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1736, '079311700426', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Kim Huyền', 'candidate', 'thcs_le_quy_don.9a2.26@example.com', '0960000426', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1737, '079211700427', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Đức Khoa', 'candidate', 'thcs_le_quy_don.9a2.27@example.com', '0960000427', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1738, '079311700428', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Kim Lan', 'candidate', 'thcs_le_quy_don.9a2.28@example.com', '0960000428', 1, NULL, '2026-10-18 00:00:12', '2026-10-18 00:00:12'),
(1739, '079211700429', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Đức Khang', 'candidate', 'thcs_le_quy_don.9a2.29@example.com', '0960000429', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1740, '079311700430', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Kim Linh', 'candidate', 'thcs_le_quy_don.9a2.30@example.com', '0960000430', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1741, '079211700431', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Đức Khôi', 'candidate', 'thcs_le_quy_don.9a2.31@example.com', '0960000431', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1742, '079311700432', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Kim Mai', 'candidate', 'thcs_le_quy_don.9a2.32@example.com', '0960000432', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1743, '079211700433', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Đức Lâm', 'candidate', 'thcs_le_quy_don.9a2.33@example.com', '0960000433', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1744, '079311700434', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Kim My', 'candidate', 'thcs_le_quy_don.9a2.34@example.com', '0960000434', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1745, '079211700435', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Đức Long', 'candidate', 'thcs_le_quy_don.9a2.35@example.com', '0960000435', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1746, '079311700436', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Kim Ngân', 'candidate', 'thcs_le_quy_don.9a2.36@example.com', '0960000436', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1747, '079211700437', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Đức Minh', 'candidate', 'thcs_le_quy_don.9a2.37@example.com', '0960000437', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1748, '079311700438', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Kim Nhung', 'candidate', 'thcs_le_quy_don.9a2.38@example.com', '0960000438', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1749, '079211700439', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Đức Nam', 'candidate', 'thcs_le_quy_don.9a2.39@example.com', '0960000439', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1750, '079311700440', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Kim Trang', 'candidate', 'thcs_le_quy_don.9a2.40@example.com', '0960000440', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1751, '079211700441', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Đình An', 'candidate', 'thcs_le_quy_don.9a3.01@example.com', '0960000441', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1752, '079311700442', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Mai Ánh', 'candidate', 'thcs_le_quy_don.9a3.02@example.com', '0960000442', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1753, '079211700443', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Đình Bình', 'candidate', 'thcs_le_quy_don.9a3.03@example.com', '0960000443', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1754, '079311700444', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Mai Bích', 'candidate', 'thcs_le_quy_don.9a3.04@example.com', '0960000444', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1755, '079211700445', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Đình Cường', 'candidate', 'thcs_le_quy_don.9a3.05@example.com', '0960000445', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1756, '079311700446', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Mai Chi', 'candidate', 'thcs_le_quy_don.9a3.06@example.com', '0960000446', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13');
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `phone`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1757, '079211700447', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Đình Dũng', 'candidate', 'thcs_le_quy_don.9a3.07@example.com', '0960000447', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1758, '079311700448', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Mai Dung', 'candidate', 'thcs_le_quy_don.9a3.08@example.com', '0960000448', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1759, '079211700449', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Đình Đạt', 'candidate', 'thcs_le_quy_don.9a3.09@example.com', '0960000449', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1760, '079311700450', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Mai Duyên', 'candidate', 'thcs_le_quy_don.9a3.10@example.com', '0960000450', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1761, '079211700451', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Đình Duy', 'candidate', 'thcs_le_quy_don.9a3.11@example.com', '0960000451', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1762, '079311700452', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Mai Giang', 'candidate', 'thcs_le_quy_don.9a3.12@example.com', '0960000452', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1763, '079211700453', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Đình Giang', 'candidate', 'thcs_le_quy_don.9a3.13@example.com', '0960000453', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1764, '079311700454', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Mai Hà', 'candidate', 'thcs_le_quy_don.9a3.14@example.com', '0960000454', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1765, '079211700455', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Đình Hải', 'candidate', 'thcs_le_quy_don.9a3.15@example.com', '0960000455', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1766, '079311700456', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Mai Hân', 'candidate', 'thcs_le_quy_don.9a3.16@example.com', '0960000456', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1767, '079211700457', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Đình Hiếu', 'candidate', 'thcs_le_quy_don.9a3.17@example.com', '0960000457', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1768, '079311700458', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Mai Hằng', 'candidate', 'thcs_le_quy_don.9a3.18@example.com', '0960000458', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1769, '079211700459', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Đình Hoàng', 'candidate', 'thcs_le_quy_don.9a3.19@example.com', '0960000459', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1770, '079311700460', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Mai Hạnh', 'candidate', 'thcs_le_quy_don.9a3.20@example.com', '0960000460', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1771, '079211700461', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Đình Huy', 'candidate', 'thcs_le_quy_don.9a3.21@example.com', '0960000461', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1772, '079311700462', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Mai Hoa', 'candidate', 'thcs_le_quy_don.9a3.22@example.com', '0960000462', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1773, '079211700463', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Đình Hùng', 'candidate', 'thcs_le_quy_don.9a3.23@example.com', '0960000463', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1774, '079311700464', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Mai Hương', 'candidate', 'thcs_le_quy_don.9a3.24@example.com', '0960000464', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1775, '079211700465', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Đình Hưng', 'candidate', 'thcs_le_quy_don.9a3.25@example.com', '0960000465', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1776, '079311700466', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Mai Huyền', 'candidate', 'thcs_le_quy_don.9a3.26@example.com', '0960000466', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1777, '079211700467', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Đình Khoa', 'candidate', 'thcs_le_quy_don.9a3.27@example.com', '0960000467', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1778, '079311700468', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Mai Lan', 'candidate', 'thcs_le_quy_don.9a3.28@example.com', '0960000468', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1779, '079211700469', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Đình Khang', 'candidate', 'thcs_le_quy_don.9a3.29@example.com', '0960000469', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1780, '079311700470', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Mai Linh', 'candidate', 'thcs_le_quy_don.9a3.30@example.com', '0960000470', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1781, '079211700471', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Đình Khôi', 'candidate', 'thcs_le_quy_don.9a3.31@example.com', '0960000471', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1782, '079311700472', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Mai Mai', 'candidate', 'thcs_le_quy_don.9a3.32@example.com', '0960000472', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1783, '079211700473', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Đình Lâm', 'candidate', 'thcs_le_quy_don.9a3.33@example.com', '0960000473', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1784, '079311700474', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Mai My', 'candidate', 'thcs_le_quy_don.9a3.34@example.com', '0960000474', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1785, '079211700475', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Đình Long', 'candidate', 'thcs_le_quy_don.9a3.35@example.com', '0960000475', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1786, '079311700476', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Mai Ngân', 'candidate', 'thcs_le_quy_don.9a3.36@example.com', '0960000476', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1787, '079211700477', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Đình Minh', 'candidate', 'thcs_le_quy_don.9a3.37@example.com', '0960000477', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1788, '079311700478', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Mai Nhung', 'candidate', 'thcs_le_quy_don.9a3.38@example.com', '0960000478', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1789, '079211700479', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Đình Nam', 'candidate', 'thcs_le_quy_don.9a3.39@example.com', '0960000479', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1790, '079311700480', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Mai Trang', 'candidate', 'thcs_le_quy_don.9a3.40@example.com', '0960000480', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1791, '079211700481', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Quang An', 'candidate', 'thcs_le_quy_don.9a4.01@example.com', '0960000481', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1792, '079311700482', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Phương Ánh', 'candidate', 'thcs_le_quy_don.9a4.02@example.com', '0960000482', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1793, '079211700483', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Quang Bình', 'candidate', 'thcs_le_quy_don.9a4.03@example.com', '0960000483', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1794, '079311700484', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Phương Bích', 'candidate', 'thcs_le_quy_don.9a4.04@example.com', '0960000484', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1795, '079211700485', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Quang Cường', 'candidate', 'thcs_le_quy_don.9a4.05@example.com', '0960000485', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1796, '079311700486', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Phương Chi', 'candidate', 'thcs_le_quy_don.9a4.06@example.com', '0960000486', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1797, '079211700487', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Quang Dũng', 'candidate', 'thcs_le_quy_don.9a4.07@example.com', '0960000487', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1798, '079311700488', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Phương Dung', 'candidate', 'thcs_le_quy_don.9a4.08@example.com', '0960000488', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1799, '079211700489', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Quang Đạt', 'candidate', 'thcs_le_quy_don.9a4.09@example.com', '0960000489', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1800, '079311700490', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Phương Duyên', 'candidate', 'thcs_le_quy_don.9a4.10@example.com', '0960000490', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1801, '079211700491', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Quang Duy', 'candidate', 'thcs_le_quy_don.9a4.11@example.com', '0960000491', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1802, '079311700492', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Phương Giang', 'candidate', 'thcs_le_quy_don.9a4.12@example.com', '0960000492', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1803, '079211700493', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Quang Giang', 'candidate', 'thcs_le_quy_don.9a4.13@example.com', '0960000493', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1804, '079311700494', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Phương Hà', 'candidate', 'thcs_le_quy_don.9a4.14@example.com', '0960000494', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1805, '079211700495', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Quang Hải', 'candidate', 'thcs_le_quy_don.9a4.15@example.com', '0960000495', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1806, '079311700496', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Phương Hân', 'candidate', 'thcs_le_quy_don.9a4.16@example.com', '0960000496', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1807, '079211700497', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Quang Hiếu', 'candidate', 'thcs_le_quy_don.9a4.17@example.com', '0960000497', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1808, '079311700498', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Phương Hằng', 'candidate', 'thcs_le_quy_don.9a4.18@example.com', '0960000498', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1809, '079211700499', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Quang Hoàng', 'candidate', 'thcs_le_quy_don.9a4.19@example.com', '0960000499', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1810, '079311700500', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Phương Hạnh', 'candidate', 'thcs_le_quy_don.9a4.20@example.com', '0960000500', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1811, '079211700501', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Quang Huy', 'candidate', 'thcs_le_quy_don.9a4.21@example.com', '0960000501', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1812, '079311700502', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Phương Hoa', 'candidate', 'thcs_le_quy_don.9a4.22@example.com', '0960000502', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1813, '079211700503', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Quang Hùng', 'candidate', 'thcs_le_quy_don.9a4.23@example.com', '0960000503', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1814, '079311700504', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Phương Hương', 'candidate', 'thcs_le_quy_don.9a4.24@example.com', '0960000504', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1815, '079211700505', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Quang Hưng', 'candidate', 'thcs_le_quy_don.9a4.25@example.com', '0960000505', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1816, '079311700506', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Phương Huyền', 'candidate', 'thcs_le_quy_don.9a4.26@example.com', '0960000506', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1817, '079211700507', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Quang Khoa', 'candidate', 'thcs_le_quy_don.9a4.27@example.com', '0960000507', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1818, '079311700508', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Phương Lan', 'candidate', 'thcs_le_quy_don.9a4.28@example.com', '0960000508', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1819, '079211700509', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Quang Khang', 'candidate', 'thcs_le_quy_don.9a4.29@example.com', '0960000509', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1820, '079311700510', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Phương Linh', 'candidate', 'thcs_le_quy_don.9a4.30@example.com', '0960000510', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1821, '079211700511', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Quang Khôi', 'candidate', 'thcs_le_quy_don.9a4.31@example.com', '0960000511', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1822, '079311700512', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Phương Mai', 'candidate', 'thcs_le_quy_don.9a4.32@example.com', '0960000512', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1823, '079211700513', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Quang Lâm', 'candidate', 'thcs_le_quy_don.9a4.33@example.com', '0960000513', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1824, '079311700514', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Phương My', 'candidate', 'thcs_le_quy_don.9a4.34@example.com', '0960000514', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1825, '079211700515', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Quang Long', 'candidate', 'thcs_le_quy_don.9a4.35@example.com', '0960000515', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1826, '079311700516', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Phương Ngân', 'candidate', 'thcs_le_quy_don.9a4.36@example.com', '0960000516', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1827, '079211700517', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Quang Minh', 'candidate', 'thcs_le_quy_don.9a4.37@example.com', '0960000517', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1828, '079311700518', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Phương Nhung', 'candidate', 'thcs_le_quy_don.9a4.38@example.com', '0960000518', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1829, '079211700519', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Quang Nam', 'candidate', 'thcs_le_quy_don.9a4.39@example.com', '0960000519', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1830, '079311700520', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Phương Trang', 'candidate', 'thcs_le_quy_don.9a4.40@example.com', '0960000520', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1831, '079211700521', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Minh An', 'candidate', 'thcs_le_quy_don.9a5.01@example.com', '0960000521', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1832, '079311700522', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thảo Ánh', 'candidate', 'thcs_le_quy_don.9a5.02@example.com', '0960000522', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1833, '079211700523', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Minh Bình', 'candidate', 'thcs_le_quy_don.9a5.03@example.com', '0960000523', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1834, '079311700524', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thảo Bích', 'candidate', 'thcs_le_quy_don.9a5.04@example.com', '0960000524', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1835, '079211700525', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Minh Cường', 'candidate', 'thcs_le_quy_don.9a5.05@example.com', '0960000525', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1836, '079311700526', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thảo Chi', 'candidate', 'thcs_le_quy_don.9a5.06@example.com', '0960000526', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1837, '079211700527', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Minh Dũng', 'candidate', 'thcs_le_quy_don.9a5.07@example.com', '0960000527', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1838, '079311700528', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thảo Dung', 'candidate', 'thcs_le_quy_don.9a5.08@example.com', '0960000528', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1839, '079211700529', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Minh Đạt', 'candidate', 'thcs_le_quy_don.9a5.09@example.com', '0960000529', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1840, '079311700530', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thảo Duyên', 'candidate', 'thcs_le_quy_don.9a5.10@example.com', '0960000530', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1841, '079211700531', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Minh Duy', 'candidate', 'thcs_le_quy_don.9a5.11@example.com', '0960000531', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1842, '079311700532', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thảo Giang', 'candidate', 'thcs_le_quy_don.9a5.12@example.com', '0960000532', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1843, '079211700533', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Minh Giang', 'candidate', 'thcs_le_quy_don.9a5.13@example.com', '0960000533', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1844, '079311700534', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thảo Hà', 'candidate', 'thcs_le_quy_don.9a5.14@example.com', '0960000534', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1845, '079211700535', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Minh Hải', 'candidate', 'thcs_le_quy_don.9a5.15@example.com', '0960000535', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1846, '079311700536', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thảo Hân', 'candidate', 'thcs_le_quy_don.9a5.16@example.com', '0960000536', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1847, '079211700537', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Minh Hiếu', 'candidate', 'thcs_le_quy_don.9a5.17@example.com', '0960000537', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1848, '079311700538', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thảo Hằng', 'candidate', 'thcs_le_quy_don.9a5.18@example.com', '0960000538', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1849, '079211700539', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Minh Hoàng', 'candidate', 'thcs_le_quy_don.9a5.19@example.com', '0960000539', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1850, '079311700540', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thảo Hạnh', 'candidate', 'thcs_le_quy_don.9a5.20@example.com', '0960000540', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1851, '079211700541', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Minh Huy', 'candidate', 'thcs_le_quy_don.9a5.21@example.com', '0960000541', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1852, '079311700542', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thảo Hoa', 'candidate', 'thcs_le_quy_don.9a5.22@example.com', '0960000542', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1853, '079211700543', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Minh Hùng', 'candidate', 'thcs_le_quy_don.9a5.23@example.com', '0960000543', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1854, '079311700544', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thảo Hương', 'candidate', 'thcs_le_quy_don.9a5.24@example.com', '0960000544', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1855, '079211700545', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Minh Hưng', 'candidate', 'thcs_le_quy_don.9a5.25@example.com', '0960000545', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1856, '079311700546', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thảo Huyền', 'candidate', 'thcs_le_quy_don.9a5.26@example.com', '0960000546', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1857, '079211700547', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Minh Khoa', 'candidate', 'thcs_le_quy_don.9a5.27@example.com', '0960000547', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1858, '079311700548', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thảo Lan', 'candidate', 'thcs_le_quy_don.9a5.28@example.com', '0960000548', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1859, '079211700549', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Minh Khang', 'candidate', 'thcs_le_quy_don.9a5.29@example.com', '0960000549', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1860, '079311700550', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thảo Linh', 'candidate', 'thcs_le_quy_don.9a5.30@example.com', '0960000550', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1861, '079211700551', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Minh Khôi', 'candidate', 'thcs_le_quy_don.9a5.31@example.com', '0960000551', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1862, '079311700552', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thảo Mai', 'candidate', 'thcs_le_quy_don.9a5.32@example.com', '0960000552', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1863, '079211700553', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Minh Lâm', 'candidate', 'thcs_le_quy_don.9a5.33@example.com', '0960000553', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1864, '079311700554', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thảo My', 'candidate', 'thcs_le_quy_don.9a5.34@example.com', '0960000554', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1865, '079211700555', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Minh Long', 'candidate', 'thcs_le_quy_don.9a5.35@example.com', '0960000555', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1866, '079311700556', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thảo Ngân', 'candidate', 'thcs_le_quy_don.9a5.36@example.com', '0960000556', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1867, '079211700557', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Minh Minh', 'candidate', 'thcs_le_quy_don.9a5.37@example.com', '0960000557', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1868, '079311700558', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thảo Nhung', 'candidate', 'thcs_le_quy_don.9a5.38@example.com', '0960000558', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1869, '079211700559', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Minh Nam', 'candidate', 'thcs_le_quy_don.9a5.39@example.com', '0960000559', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1870, '079311700560', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thảo Trang', 'candidate', 'thcs_le_quy_don.9a5.40@example.com', '0960000560', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1871, '079211700561', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Tuấn An', 'candidate', 'thcs_le_quy_don.9a6.01@example.com', '0960000561', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1872, '079311700562', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Khánh Ánh', 'candidate', 'thcs_le_quy_don.9a6.02@example.com', '0960000562', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1873, '079211700563', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Tuấn Bình', 'candidate', 'thcs_le_quy_don.9a6.03@example.com', '0960000563', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1874, '079311700564', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Khánh Bích', 'candidate', 'thcs_le_quy_don.9a6.04@example.com', '0960000564', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1875, '079211700565', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Tuấn Cường', 'candidate', 'thcs_le_quy_don.9a6.05@example.com', '0960000565', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1876, '079311700566', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Khánh Chi', 'candidate', 'thcs_le_quy_don.9a6.06@example.com', '0960000566', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1877, '079211700567', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Tuấn Dũng', 'candidate', 'thcs_le_quy_don.9a6.07@example.com', '0960000567', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1878, '079311700568', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Khánh Dung', 'candidate', 'thcs_le_quy_don.9a6.08@example.com', '0960000568', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1879, '079211700569', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Tuấn Đạt', 'candidate', 'thcs_le_quy_don.9a6.09@example.com', '0960000569', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1880, '079311700570', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Khánh Duyên', 'candidate', 'thcs_le_quy_don.9a6.10@example.com', '0960000570', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1881, '079211700571', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Tuấn Duy', 'candidate', 'thcs_le_quy_don.9a6.11@example.com', '0960000571', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1882, '079311700572', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Khánh Giang', 'candidate', 'thcs_le_quy_don.9a6.12@example.com', '0960000572', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1883, '079211700573', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Tuấn Giang', 'candidate', 'thcs_le_quy_don.9a6.13@example.com', '0960000573', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1884, '079311700574', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Khánh Hà', 'candidate', 'thcs_le_quy_don.9a6.14@example.com', '0960000574', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1885, '079211700575', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Tuấn Hải', 'candidate', 'thcs_le_quy_don.9a6.15@example.com', '0960000575', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1886, '079311700576', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Khánh Hân', 'candidate', 'thcs_le_quy_don.9a6.16@example.com', '0960000576', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1887, '079211700577', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Tuấn Hiếu', 'candidate', 'thcs_le_quy_don.9a6.17@example.com', '0960000577', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1888, '079311700578', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Khánh Hằng', 'candidate', 'thcs_le_quy_don.9a6.18@example.com', '0960000578', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1889, '079211700579', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Tuấn Hoàng', 'candidate', 'thcs_le_quy_don.9a6.19@example.com', '0960000579', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1890, '079311700580', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Khánh Hạnh', 'candidate', 'thcs_le_quy_don.9a6.20@example.com', '0960000580', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1891, '079211700581', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Tuấn Huy', 'candidate', 'thcs_le_quy_don.9a6.21@example.com', '0960000581', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1892, '079311700582', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Khánh Hoa', 'candidate', 'thcs_le_quy_don.9a6.22@example.com', '0960000582', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1893, '079211700583', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Tuấn Hùng', 'candidate', 'thcs_le_quy_don.9a6.23@example.com', '0960000583', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1894, '079311700584', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Khánh Hương', 'candidate', 'thcs_le_quy_don.9a6.24@example.com', '0960000584', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1895, '079211700585', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Tuấn Hưng', 'candidate', 'thcs_le_quy_don.9a6.25@example.com', '0960000585', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1896, '079311700586', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Khánh Huyền', 'candidate', 'thcs_le_quy_don.9a6.26@example.com', '0960000586', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1897, '079211700587', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Tuấn Khoa', 'candidate', 'thcs_le_quy_don.9a6.27@example.com', '0960000587', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1898, '079311700588', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Khánh Lan', 'candidate', 'thcs_le_quy_don.9a6.28@example.com', '0960000588', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1899, '079211700589', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Tuấn Khang', 'candidate', 'thcs_le_quy_don.9a6.29@example.com', '0960000589', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1900, '079311700590', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Khánh Linh', 'candidate', 'thcs_le_quy_don.9a6.30@example.com', '0960000590', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1901, '079211700591', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Tuấn Khôi', 'candidate', 'thcs_le_quy_don.9a6.31@example.com', '0960000591', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1902, '079311700592', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Khánh Mai', 'candidate', 'thcs_le_quy_don.9a6.32@example.com', '0960000592', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1903, '079211700593', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Tuấn Lâm', 'candidate', 'thcs_le_quy_don.9a6.33@example.com', '0960000593', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1904, '079311700594', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Khánh My', 'candidate', 'thcs_le_quy_don.9a6.34@example.com', '0960000594', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1905, '079211700595', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Tuấn Long', 'candidate', 'thcs_le_quy_don.9a6.35@example.com', '0960000595', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1906, '079311700596', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Khánh Ngân', 'candidate', 'thcs_le_quy_don.9a6.36@example.com', '0960000596', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1907, '079211700597', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Tuấn Minh', 'candidate', 'thcs_le_quy_don.9a6.37@example.com', '0960000597', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1908, '079311700598', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Khánh Nhung', 'candidate', 'thcs_le_quy_don.9a6.38@example.com', '0960000598', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1909, '079211700599', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Tuấn Nam', 'candidate', 'thcs_le_quy_don.9a6.39@example.com', '0960000599', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1910, '079311700600', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Khánh Trang', 'candidate', 'thcs_le_quy_don.9a6.40@example.com', '0960000600', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1911, '079211700601', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Gia An', 'candidate', 'thcs_le_quy_don.9a7.01@example.com', '0960000601', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1912, '079311700602', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Quỳnh Ánh', 'candidate', 'thcs_le_quy_don.9a7.02@example.com', '0960000602', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1913, '079211700603', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Gia Bình', 'candidate', 'thcs_le_quy_don.9a7.03@example.com', '0960000603', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1914, '079311700604', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Quỳnh Bích', 'candidate', 'thcs_le_quy_don.9a7.04@example.com', '0960000604', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1915, '079211700605', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Gia Cường', 'candidate', 'thcs_le_quy_don.9a7.05@example.com', '0960000605', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1916, '079311700606', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Quỳnh Chi', 'candidate', 'thcs_le_quy_don.9a7.06@example.com', '0960000606', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1917, '079211700607', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Gia Dũng', 'candidate', 'thcs_le_quy_don.9a7.07@example.com', '0960000607', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1918, '079311700608', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Quỳnh Dung', 'candidate', 'thcs_le_quy_don.9a7.08@example.com', '0960000608', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1919, '079211700609', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Gia Đạt', 'candidate', 'thcs_le_quy_don.9a7.09@example.com', '0960000609', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1920, '079311700610', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Quỳnh Duyên', 'candidate', 'thcs_le_quy_don.9a7.10@example.com', '0960000610', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1921, '079211700611', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Gia Duy', 'candidate', 'thcs_le_quy_don.9a7.11@example.com', '0960000611', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1922, '079311700612', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Quỳnh Giang', 'candidate', 'thcs_le_quy_don.9a7.12@example.com', '0960000612', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1923, '079211700613', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Gia Giang', 'candidate', 'thcs_le_quy_don.9a7.13@example.com', '0960000613', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1924, '079311700614', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Quỳnh Hà', 'candidate', 'thcs_le_quy_don.9a7.14@example.com', '0960000614', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1925, '079211700615', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Gia Hải', 'candidate', 'thcs_le_quy_don.9a7.15@example.com', '0960000615', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1926, '079311700616', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Quỳnh Hân', 'candidate', 'thcs_le_quy_don.9a7.16@example.com', '0960000616', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1927, '079211700617', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Gia Hiếu', 'candidate', 'thcs_le_quy_don.9a7.17@example.com', '0960000617', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1928, '079311700618', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Quỳnh Hằng', 'candidate', 'thcs_le_quy_don.9a7.18@example.com', '0960000618', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1929, '079211700619', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Gia Hoàng', 'candidate', 'thcs_le_quy_don.9a7.19@example.com', '0960000619', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1930, '079311700620', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Quỳnh Hạnh', 'candidate', 'thcs_le_quy_don.9a7.20@example.com', '0960000620', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1931, '079211700621', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Gia Huy', 'candidate', 'thcs_le_quy_don.9a7.21@example.com', '0960000621', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1932, '079311700622', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Quỳnh Hoa', 'candidate', 'thcs_le_quy_don.9a7.22@example.com', '0960000622', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1933, '079211700623', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Gia Hùng', 'candidate', 'thcs_le_quy_don.9a7.23@example.com', '0960000623', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1934, '079311700624', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Quỳnh Hương', 'candidate', 'thcs_le_quy_don.9a7.24@example.com', '0960000624', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1935, '079211700625', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Gia Hưng', 'candidate', 'thcs_le_quy_don.9a7.25@example.com', '0960000625', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1936, '079311700626', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Quỳnh Huyền', 'candidate', 'thcs_le_quy_don.9a7.26@example.com', '0960000626', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1937, '079211700627', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Gia Khoa', 'candidate', 'thcs_le_quy_don.9a7.27@example.com', '0960000627', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1938, '079311700628', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Quỳnh Lan', 'candidate', 'thcs_le_quy_don.9a7.28@example.com', '0960000628', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1939, '079211700629', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Gia Khang', 'candidate', 'thcs_le_quy_don.9a7.29@example.com', '0960000629', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1940, '079311700630', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Quỳnh Linh', 'candidate', 'thcs_le_quy_don.9a7.30@example.com', '0960000630', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1941, '079211700631', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Gia Khôi', 'candidate', 'thcs_le_quy_don.9a7.31@example.com', '0960000631', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1942, '079311700632', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Quỳnh Mai', 'candidate', 'thcs_le_quy_don.9a7.32@example.com', '0960000632', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1943, '079211700633', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Gia Lâm', 'candidate', 'thcs_le_quy_don.9a7.33@example.com', '0960000633', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1944, '079311700634', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Quỳnh My', 'candidate', 'thcs_le_quy_don.9a7.34@example.com', '0960000634', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1945, '079211700635', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Gia Long', 'candidate', 'thcs_le_quy_don.9a7.35@example.com', '0960000635', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1946, '079311700636', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Quỳnh Ngân', 'candidate', 'thcs_le_quy_don.9a7.36@example.com', '0960000636', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1947, '079211700637', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Gia Minh', 'candidate', 'thcs_le_quy_don.9a7.37@example.com', '0960000637', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1948, '079311700638', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Quỳnh Nhung', 'candidate', 'thcs_le_quy_don.9a7.38@example.com', '0960000638', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1949, '079211700639', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Gia Nam', 'candidate', 'thcs_le_quy_don.9a7.39@example.com', '0960000639', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1950, '079311700640', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Quỳnh Trang', 'candidate', 'thcs_le_quy_don.9a7.40@example.com', '0960000640', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1951, '079211700641', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Bảo An', 'candidate', 'thcs_le_quy_don.9a8.01@example.com', '0960000641', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1952, '079311700642', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thanh Ánh', 'candidate', 'thcs_le_quy_don.9a8.02@example.com', '0960000642', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1953, '079211700643', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Bảo Bình', 'candidate', 'thcs_le_quy_don.9a8.03@example.com', '0960000643', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1954, '079311700644', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thanh Bích', 'candidate', 'thcs_le_quy_don.9a8.04@example.com', '0960000644', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1955, '079211700645', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Bảo Cường', 'candidate', 'thcs_le_quy_don.9a8.05@example.com', '0960000645', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1956, '079311700646', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thanh Chi', 'candidate', 'thcs_le_quy_don.9a8.06@example.com', '0960000646', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1957, '079211700647', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Bảo Dũng', 'candidate', 'thcs_le_quy_don.9a8.07@example.com', '0960000647', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1958, '079311700648', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thanh Dung', 'candidate', 'thcs_le_quy_don.9a8.08@example.com', '0960000648', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1959, '079211700649', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Bảo Đạt', 'candidate', 'thcs_le_quy_don.9a8.09@example.com', '0960000649', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1960, '079311700650', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thanh Duyên', 'candidate', 'thcs_le_quy_don.9a8.10@example.com', '0960000650', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1961, '079211700651', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Bảo Duy', 'candidate', 'thcs_le_quy_don.9a8.11@example.com', '0960000651', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1962, '079311700652', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thanh Giang', 'candidate', 'thcs_le_quy_don.9a8.12@example.com', '0960000652', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1963, '079211700653', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Bảo Giang', 'candidate', 'thcs_le_quy_don.9a8.13@example.com', '0960000653', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1964, '079311700654', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thanh Hà', 'candidate', 'thcs_le_quy_don.9a8.14@example.com', '0960000654', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1965, '079211700655', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Bảo Hải', 'candidate', 'thcs_le_quy_don.9a8.15@example.com', '0960000655', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1966, '079311700656', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thanh Hân', 'candidate', 'thcs_le_quy_don.9a8.16@example.com', '0960000656', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1967, '079211700657', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Bảo Hiếu', 'candidate', 'thcs_le_quy_don.9a8.17@example.com', '0960000657', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1968, '079311700658', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thanh Hằng', 'candidate', 'thcs_le_quy_don.9a8.18@example.com', '0960000658', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1969, '079211700659', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Bảo Hoàng', 'candidate', 'thcs_le_quy_don.9a8.19@example.com', '0960000659', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1970, '079311700660', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thanh Hạnh', 'candidate', 'thcs_le_quy_don.9a8.20@example.com', '0960000660', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1971, '079211700661', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Bảo Huy', 'candidate', 'thcs_le_quy_don.9a8.21@example.com', '0960000661', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1972, '079311700662', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thanh Hoa', 'candidate', 'thcs_le_quy_don.9a8.22@example.com', '0960000662', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1973, '079211700663', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Bảo Hùng', 'candidate', 'thcs_le_quy_don.9a8.23@example.com', '0960000663', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1974, '079311700664', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thanh Hương', 'candidate', 'thcs_le_quy_don.9a8.24@example.com', '0960000664', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1975, '079211700665', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Bảo Hưng', 'candidate', 'thcs_le_quy_don.9a8.25@example.com', '0960000665', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1976, '079311700666', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thanh Huyền', 'candidate', 'thcs_le_quy_don.9a8.26@example.com', '0960000666', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1977, '079211700667', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Bảo Khoa', 'candidate', 'thcs_le_quy_don.9a8.27@example.com', '0960000667', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1978, '079311700668', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thanh Lan', 'candidate', 'thcs_le_quy_don.9a8.28@example.com', '0960000668', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13');
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `phone`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1979, '079211700669', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Bảo Khang', 'candidate', 'thcs_le_quy_don.9a8.29@example.com', '0960000669', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1980, '079311700670', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thanh Linh', 'candidate', 'thcs_le_quy_don.9a8.30@example.com', '0960000670', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1981, '079211700671', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Bảo Khôi', 'candidate', 'thcs_le_quy_don.9a8.31@example.com', '0960000671', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1982, '079311700672', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thanh Mai', 'candidate', 'thcs_le_quy_don.9a8.32@example.com', '0960000672', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1983, '079211700673', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Bảo Lâm', 'candidate', 'thcs_le_quy_don.9a8.33@example.com', '0960000673', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1984, '079311700674', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thanh My', 'candidate', 'thcs_le_quy_don.9a8.34@example.com', '0960000674', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1985, '079211700675', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Bảo Long', 'candidate', 'thcs_le_quy_don.9a8.35@example.com', '0960000675', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1986, '079311700676', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thanh Ngân', 'candidate', 'thcs_le_quy_don.9a8.36@example.com', '0960000676', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1987, '079211700677', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Bảo Minh', 'candidate', 'thcs_le_quy_don.9a8.37@example.com', '0960000677', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1988, '079311700678', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thanh Nhung', 'candidate', 'thcs_le_quy_don.9a8.38@example.com', '0960000678', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1989, '079211700679', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Bảo Nam', 'candidate', 'thcs_le_quy_don.9a8.39@example.com', '0960000679', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1990, '079311700680', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thanh Trang', 'candidate', 'thcs_le_quy_don.9a8.40@example.com', '0960000680', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1991, '079211700681', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Văn An', 'candidate', 'thcs_le_quy_don.9a9.01@example.com', '0960000681', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1992, '079311700682', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thị Ánh', 'candidate', 'thcs_le_quy_don.9a9.02@example.com', '0960000682', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1993, '079211700683', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Văn Bình', 'candidate', 'thcs_le_quy_don.9a9.03@example.com', '0960000683', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1994, '079311700684', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thị Bích', 'candidate', 'thcs_le_quy_don.9a9.04@example.com', '0960000684', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1995, '079211700685', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Văn Cường', 'candidate', 'thcs_le_quy_don.9a9.05@example.com', '0960000685', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1996, '079311700686', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thị Chi', 'candidate', 'thcs_le_quy_don.9a9.06@example.com', '0960000686', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1997, '079211700687', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Văn Dũng', 'candidate', 'thcs_le_quy_don.9a9.07@example.com', '0960000687', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1998, '079311700688', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thị Dung', 'candidate', 'thcs_le_quy_don.9a9.08@example.com', '0960000688', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(1999, '079211700689', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Văn Đạt', 'candidate', 'thcs_le_quy_don.9a9.09@example.com', '0960000689', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2000, '079311700690', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thị Duyên', 'candidate', 'thcs_le_quy_don.9a9.10@example.com', '0960000690', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2001, '079211700691', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Văn Duy', 'candidate', 'thcs_le_quy_don.9a9.11@example.com', '0960000691', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2002, '079311700692', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thị Giang', 'candidate', 'thcs_le_quy_don.9a9.12@example.com', '0960000692', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2003, '079211700693', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Văn Giang', 'candidate', 'thcs_le_quy_don.9a9.13@example.com', '0960000693', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2004, '079311700694', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thị Hà', 'candidate', 'thcs_le_quy_don.9a9.14@example.com', '0960000694', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2005, '079211700695', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Văn Hải', 'candidate', 'thcs_le_quy_don.9a9.15@example.com', '0960000695', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2006, '079311700696', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thị Hân', 'candidate', 'thcs_le_quy_don.9a9.16@example.com', '0960000696', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2007, '079211700697', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Văn Hiếu', 'candidate', 'thcs_le_quy_don.9a9.17@example.com', '0960000697', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2008, '079311700698', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thị Hằng', 'candidate', 'thcs_le_quy_don.9a9.18@example.com', '0960000698', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2009, '079211700699', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Văn Hoàng', 'candidate', 'thcs_le_quy_don.9a9.19@example.com', '0960000699', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2010, '079311700700', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thị Hạnh', 'candidate', 'thcs_le_quy_don.9a9.20@example.com', '0960000700', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2011, '079211700701', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Võ Văn Huy', 'candidate', 'thcs_le_quy_don.9a9.21@example.com', '0960000701', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2012, '079311700702', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đặng Thị Hoa', 'candidate', 'thcs_le_quy_don.9a9.22@example.com', '0960000702', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2013, '079211700703', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Bùi Văn Hùng', 'candidate', 'thcs_le_quy_don.9a9.23@example.com', '0960000703', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2014, '079311700704', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đỗ Thị Hương', 'candidate', 'thcs_le_quy_don.9a9.24@example.com', '0960000704', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2015, '079211700705', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hồ Văn Hưng', 'candidate', 'thcs_le_quy_don.9a9.25@example.com', '0960000705', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2016, '079311700706', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Ngô Thị Huyền', 'candidate', 'thcs_le_quy_don.9a9.26@example.com', '0960000706', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2017, '079211700707', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Dương Văn Khoa', 'candidate', 'thcs_le_quy_don.9a9.27@example.com', '0960000707', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2018, '079311700708', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lý Thị Lan', 'candidate', 'thcs_le_quy_don.9a9.28@example.com', '0960000708', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2019, '079211700709', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đinh Văn Khang', 'candidate', 'thcs_le_quy_don.9a9.29@example.com', '0960000709', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2020, '079311700710', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Đoàn Thị Linh', 'candidate', 'thcs_le_quy_don.9a9.30@example.com', '0960000710', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2021, '079211700711', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lâm Văn Khôi', 'candidate', 'thcs_le_quy_don.9a9.31@example.com', '0960000711', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2022, '079311700712', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trịnh Thị Mai', 'candidate', 'thcs_le_quy_don.9a9.32@example.com', '0960000712', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2023, '079211700713', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Nguyễn Văn Lâm', 'candidate', 'thcs_le_quy_don.9a9.33@example.com', '0960000713', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2024, '079311700714', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Trần Thị My', 'candidate', 'thcs_le_quy_don.9a9.34@example.com', '0960000714', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2025, '079211700715', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Lê Văn Long', 'candidate', 'thcs_le_quy_don.9a9.35@example.com', '0960000715', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2026, '079311700716', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phạm Thị Ngân', 'candidate', 'thcs_le_quy_don.9a9.36@example.com', '0960000716', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2027, '079211700717', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Hoàng Văn Minh', 'candidate', 'thcs_le_quy_don.9a9.37@example.com', '0960000717', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2028, '079311700718', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Huỳnh Thị Nhung', 'candidate', 'thcs_le_quy_don.9a9.38@example.com', '0960000718', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2029, '079211700719', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Phan Văn Nam', 'candidate', 'thcs_le_quy_don.9a9.39@example.com', '0960000719', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2030, '079311700720', '$2y$10$K4xH4PtUEm1n502obRKg8u1yC3Tud9DxCIwl8/Mc1hyW6OMs9UycK', 'Vũ Thị Trang', 'candidate', 'thcs_le_quy_don.9a9.40@example.com', '0960000720', 1, NULL, '2026-10-18 00:00:13', '2026-10-18 00:00:13'),
(2032, 'gv.thcs.nguyendu', '$2y$10$oWk2js21T1GtWnsO9Ruac.mPK4SCLMDP1HWR714ZGGGilSeCb7Bym', 'Nguyễn Minh Tuấn', 'teacher', NULL, NULL, 1, '2026-10-22 21:19:55', '2026-10-18 00:40:30', '2026-10-22 21:19:55'),
(2033, 'gv.thcs.lequydon', '$2y$10$8tvuJ5RbLAxy3eu8wXHB9eiKKw8Xo9U32vvI2/QtwyCKaGgbcXwEG', 'Trần Thị Thanh Mai', 'teacher', NULL, NULL, 1, NULL, '2026-10-18 00:40:30', '2026-10-22 21:05:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(2, 1),
(1311, 3),
(1312, 3),
(1313, 3),
(1314, 3),
(1315, 3),
(1316, 3),
(1317, 3),
(1318, 3),
(1319, 3),
(1320, 3),
(1321, 3),
(1322, 3),
(1323, 3),
(1324, 3),
(1325, 3),
(1326, 3),
(1327, 3),
(1328, 3),
(1329, 3),
(1330, 3),
(1331, 3),
(1332, 3),
(1333, 3),
(1334, 3),
(1335, 3),
(1336, 3),
(1337, 3),
(1338, 3),
(1339, 3),
(1340, 3),
(1341, 3),
(1342, 3),
(1343, 3),
(1344, 3),
(1345, 3),
(1346, 3),
(1347, 3),
(1348, 3),
(1349, 3),
(1350, 3),
(1351, 3),
(1352, 3),
(1353, 3),
(1354, 3),
(1355, 3),
(1356, 3),
(1357, 3),
(1358, 3),
(1359, 3),
(1360, 3),
(1361, 3),
(1362, 3),
(1363, 3),
(1364, 3),
(1365, 3),
(1366, 3),
(1367, 3),
(1368, 3),
(1369, 3),
(1370, 3),
(1371, 3),
(1372, 3),
(1373, 3),
(1374, 3),
(1375, 3),
(1376, 3),
(1377, 3),
(1378, 3),
(1379, 3),
(1380, 3),
(1381, 3),
(1382, 3),
(1383, 3),
(1384, 3),
(1385, 3),
(1386, 3),
(1387, 3),
(1388, 3),
(1389, 3),
(1390, 3),
(1391, 3),
(1392, 3),
(1393, 3),
(1394, 3),
(1395, 3),
(1396, 3),
(1397, 3),
(1398, 3),
(1399, 3),
(1400, 3),
(1401, 3),
(1402, 3),
(1403, 3),
(1404, 3),
(1405, 3),
(1406, 3),
(1407, 3),
(1408, 3),
(1409, 3),
(1410, 3),
(1411, 3),
(1412, 3),
(1413, 3),
(1414, 3),
(1415, 3),
(1416, 3),
(1417, 3),
(1418, 3),
(1419, 3),
(1420, 3),
(1421, 3),
(1422, 3),
(1423, 3),
(1424, 3),
(1425, 3),
(1426, 3),
(1427, 3),
(1428, 3),
(1429, 3),
(1430, 3),
(1431, 3),
(1432, 3),
(1433, 3),
(1434, 3),
(1435, 3),
(1436, 3),
(1437, 3),
(1438, 3),
(1439, 3),
(1440, 3),
(1441, 3),
(1442, 3),
(1443, 3),
(1444, 3),
(1445, 3),
(1446, 3),
(1447, 3),
(1448, 3),
(1449, 3),
(1450, 3),
(1451, 3),
(1452, 3),
(1453, 3),
(1454, 3),
(1455, 3),
(1456, 3),
(1457, 3),
(1458, 3),
(1459, 3),
(1460, 3),
(1461, 3),
(1462, 3),
(1463, 3),
(1464, 3),
(1465, 3),
(1466, 3),
(1467, 3),
(1468, 3),
(1469, 3),
(1470, 3),
(1471, 3),
(1472, 3),
(1473, 3),
(1474, 3),
(1475, 3),
(1476, 3),
(1477, 3),
(1478, 3),
(1479, 3),
(1480, 3),
(1481, 3),
(1482, 3),
(1483, 3),
(1484, 3),
(1485, 3),
(1486, 3),
(1487, 3),
(1488, 3),
(1489, 3),
(1490, 3),
(1491, 3),
(1492, 3),
(1493, 3),
(1494, 3),
(1495, 3),
(1496, 3),
(1497, 3),
(1498, 3),
(1499, 3),
(1500, 3),
(1501, 3),
(1502, 3),
(1503, 3),
(1504, 3),
(1505, 3),
(1506, 3),
(1507, 3),
(1508, 3),
(1509, 3),
(1510, 3),
(1511, 3),
(1512, 3),
(1513, 3),
(1514, 3),
(1515, 3),
(1516, 3),
(1517, 3),
(1518, 3),
(1519, 3),
(1520, 3),
(1521, 3),
(1522, 3),
(1523, 3),
(1524, 3),
(1525, 3),
(1526, 3),
(1527, 3),
(1528, 3),
(1529, 3),
(1530, 3),
(1531, 3),
(1532, 3),
(1533, 3),
(1534, 3),
(1535, 3),
(1536, 3),
(1537, 3),
(1538, 3),
(1539, 3),
(1540, 3),
(1541, 3),
(1542, 3),
(1543, 3),
(1544, 3),
(1545, 3),
(1546, 3),
(1547, 3),
(1548, 3),
(1549, 3),
(1550, 3),
(1551, 3),
(1552, 3),
(1553, 3),
(1554, 3),
(1555, 3),
(1556, 3),
(1557, 3),
(1558, 3),
(1559, 3),
(1560, 3),
(1561, 3),
(1562, 3),
(1563, 3),
(1564, 3),
(1565, 3),
(1566, 3),
(1567, 3),
(1568, 3),
(1569, 3),
(1570, 3),
(1571, 3),
(1572, 3),
(1573, 3),
(1574, 3),
(1575, 3),
(1576, 3),
(1577, 3),
(1578, 3),
(1579, 3),
(1580, 3),
(1581, 3),
(1582, 3),
(1583, 3),
(1584, 3),
(1585, 3),
(1586, 3),
(1587, 3),
(1588, 3),
(1589, 3),
(1590, 3),
(1591, 3),
(1592, 3),
(1593, 3),
(1594, 3),
(1595, 3),
(1596, 3),
(1597, 3),
(1598, 3),
(1599, 3),
(1600, 3),
(1601, 3),
(1602, 3),
(1603, 3),
(1604, 3),
(1605, 3),
(1606, 3),
(1607, 3),
(1608, 3),
(1609, 3),
(1610, 3),
(1611, 3),
(1612, 3),
(1613, 3),
(1614, 3),
(1615, 3),
(1616, 3),
(1617, 3),
(1618, 3),
(1619, 3),
(1620, 3),
(1621, 3),
(1622, 3),
(1623, 3),
(1624, 3),
(1625, 3),
(1626, 3),
(1627, 3),
(1628, 3),
(1629, 3),
(1630, 3),
(1631, 3),
(1632, 3),
(1633, 3),
(1634, 3),
(1635, 3),
(1636, 3),
(1637, 3),
(1638, 3),
(1639, 3),
(1640, 3),
(1641, 3),
(1642, 3),
(1643, 3),
(1644, 3),
(1645, 3),
(1646, 3),
(1647, 3),
(1648, 3),
(1649, 3),
(1650, 3),
(1651, 3),
(1652, 3),
(1653, 3),
(1654, 3),
(1655, 3),
(1656, 3),
(1657, 3),
(1658, 3),
(1659, 3),
(1660, 3),
(1661, 3),
(1662, 3),
(1663, 3),
(1664, 3),
(1665, 3),
(1666, 3),
(1667, 3),
(1668, 3),
(1669, 3),
(1670, 3),
(1671, 3),
(1672, 3),
(1673, 3),
(1674, 3),
(1675, 3),
(1676, 3),
(1677, 3),
(1678, 3),
(1679, 3),
(1680, 3),
(1681, 3),
(1682, 3),
(1683, 3),
(1684, 3),
(1685, 3),
(1686, 3),
(1687, 3),
(1688, 3),
(1689, 3),
(1690, 3),
(1691, 3),
(1692, 3),
(1693, 3),
(1694, 3),
(1695, 3),
(1696, 3),
(1697, 3),
(1698, 3),
(1699, 3),
(1700, 3),
(1701, 3),
(1702, 3),
(1703, 3),
(1704, 3),
(1705, 3),
(1706, 3),
(1707, 3),
(1708, 3),
(1709, 3),
(1710, 3),
(1711, 3),
(1712, 3),
(1713, 3),
(1714, 3),
(1715, 3),
(1716, 3),
(1717, 3),
(1718, 3),
(1719, 3),
(1720, 3),
(1721, 3),
(1722, 3),
(1723, 3),
(1724, 3),
(1725, 3),
(1726, 3),
(1727, 3),
(1728, 3),
(1729, 3),
(1730, 3),
(1731, 3),
(1732, 3),
(1733, 3),
(1734, 3),
(1735, 3),
(1736, 3),
(1737, 3),
(1738, 3),
(1739, 3),
(1740, 3),
(1741, 3),
(1742, 3),
(1743, 3),
(1744, 3),
(1745, 3),
(1746, 3),
(1747, 3),
(1748, 3),
(1749, 3),
(1750, 3),
(1751, 3),
(1752, 3),
(1753, 3),
(1754, 3),
(1755, 3),
(1756, 3),
(1757, 3),
(1758, 3),
(1759, 3),
(1760, 3),
(1761, 3),
(1762, 3),
(1763, 3),
(1764, 3),
(1765, 3),
(1766, 3),
(1767, 3),
(1768, 3),
(1769, 3),
(1770, 3),
(1771, 3),
(1772, 3),
(1773, 3),
(1774, 3),
(1775, 3),
(1776, 3),
(1777, 3),
(1778, 3),
(1779, 3),
(1780, 3),
(1781, 3),
(1782, 3),
(1783, 3),
(1784, 3),
(1785, 3),
(1786, 3),
(1787, 3),
(1788, 3),
(1789, 3),
(1790, 3),
(1791, 3),
(1792, 3),
(1793, 3),
(1794, 3),
(1795, 3),
(1796, 3),
(1797, 3),
(1798, 3),
(1799, 3),
(1800, 3),
(1801, 3),
(1802, 3),
(1803, 3),
(1804, 3),
(1805, 3),
(1806, 3),
(1807, 3),
(1808, 3),
(1809, 3),
(1810, 3),
(1811, 3),
(1812, 3),
(1813, 3),
(1814, 3),
(1815, 3),
(1816, 3),
(1817, 3),
(1818, 3),
(1819, 3),
(1820, 3),
(1821, 3),
(1822, 3),
(1823, 3),
(1824, 3),
(1825, 3),
(1826, 3),
(1827, 3),
(1828, 3),
(1829, 3),
(1830, 3),
(1831, 3),
(1832, 3),
(1833, 3),
(1834, 3),
(1835, 3),
(1836, 3),
(1837, 3),
(1838, 3),
(1839, 3),
(1840, 3),
(1841, 3),
(1842, 3),
(1843, 3),
(1844, 3),
(1845, 3),
(1846, 3),
(1847, 3),
(1848, 3),
(1849, 3),
(1850, 3),
(1851, 3),
(1852, 3),
(1853, 3),
(1854, 3),
(1855, 3),
(1856, 3),
(1857, 3),
(1858, 3),
(1859, 3),
(1860, 3),
(1861, 3),
(1862, 3),
(1863, 3),
(1864, 3),
(1865, 3),
(1866, 3),
(1867, 3),
(1868, 3),
(1869, 3),
(1870, 3),
(1871, 3),
(1872, 3),
(1873, 3),
(1874, 3),
(1875, 3),
(1876, 3),
(1877, 3),
(1878, 3),
(1879, 3),
(1880, 3),
(1881, 3),
(1882, 3),
(1883, 3),
(1884, 3),
(1885, 3),
(1886, 3),
(1887, 3),
(1888, 3),
(1889, 3),
(1890, 3),
(1891, 3),
(1892, 3),
(1893, 3),
(1894, 3),
(1895, 3),
(1896, 3),
(1897, 3),
(1898, 3),
(1899, 3),
(1900, 3),
(1901, 3),
(1902, 3),
(1903, 3),
(1904, 3),
(1905, 3),
(1906, 3),
(1907, 3),
(1908, 3),
(1909, 3),
(1910, 3),
(1911, 3),
(1912, 3),
(1913, 3),
(1914, 3),
(1915, 3),
(1916, 3),
(1917, 3),
(1918, 3),
(1919, 3),
(1920, 3),
(1921, 3),
(1922, 3),
(1923, 3),
(1924, 3),
(1925, 3),
(1926, 3),
(1927, 3),
(1928, 3),
(1929, 3),
(1930, 3),
(1931, 3),
(1932, 3),
(1933, 3),
(1934, 3),
(1935, 3),
(1936, 3),
(1937, 3),
(1938, 3),
(1939, 3),
(1940, 3),
(1941, 3),
(1942, 3),
(1943, 3),
(1944, 3),
(1945, 3),
(1946, 3),
(1947, 3),
(1948, 3),
(1949, 3),
(1950, 3),
(1951, 3),
(1952, 3),
(1953, 3),
(1954, 3),
(1955, 3),
(1956, 3),
(1957, 3),
(1958, 3),
(1959, 3),
(1960, 3),
(1961, 3),
(1962, 3),
(1963, 3),
(1964, 3),
(1965, 3),
(1966, 3),
(1967, 3),
(1968, 3),
(1969, 3),
(1970, 3),
(1971, 3),
(1972, 3),
(1973, 3),
(1974, 3),
(1975, 3),
(1976, 3),
(1977, 3),
(1978, 3),
(1979, 3),
(1980, 3),
(1981, 3),
(1982, 3),
(1983, 3),
(1984, 3),
(1985, 3),
(1986, 3),
(1987, 3),
(1988, 3),
(1989, 3),
(1990, 3),
(1991, 3),
(1992, 3),
(1993, 3),
(1994, 3),
(1995, 3),
(1996, 3),
(1997, 3),
(1998, 3),
(1999, 3),
(2000, 3),
(2001, 3),
(2002, 3),
(2003, 3),
(2004, 3),
(2005, 3),
(2006, 3),
(2007, 3),
(2008, 3),
(2009, 3),
(2010, 3),
(2011, 3),
(2012, 3),
(2013, 3),
(2014, 3),
(2015, 3),
(2016, 3),
(2017, 3),
(2018, 3),
(2019, 3),
(2020, 3),
(2021, 3),
(2022, 3),
(2023, 3),
(2024, 3),
(2025, 3),
(2026, 3),
(2027, 3),
(2028, 3),
(2029, 3),
(2030, 3),
(2032, 4),
(2033, 4);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `adjustment_requests`
--
ALTER TABLE `adjustment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adjustment_candidate_status` (`candidate_id`,`status`),
  ADD KEY `fk_adjustment_submitter` (`submitted_by_user_id`),
  ADD KEY `fk_adjustment_reviewer` (`reviewed_by_user_id`);

--
-- Chỉ mục cho bảng `admission_bonus_reviews`
--
ALTER TABLE `admission_bonus_reviews`
  ADD PRIMARY KEY (`admission_period_id`,`candidate_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Chỉ mục cho bảng `admission_periods`
--
ALTER TABLE `admission_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admission_periods_year` (`academic_year`),
  ADD KEY `idx_admission_periods_status` (`status`);

--
-- Chỉ mục cho bảng `admission_period_configs`
--
ALTER TABLE `admission_period_configs`
  ADD PRIMARY KEY (`admission_period_id`),
  ADD KEY `fk_period_config_actor` (`updated_by`);

--
-- Chỉ mục cho bảng `admission_period_events`
--
ALTER TABLE `admission_period_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_period_event_period` (`admission_period_id`),
  ADD KEY `fk_period_event_actor` (`actor_user_id`);

--
-- Chỉ mục cho bảng `admission_quotas`
--
ALTER TABLE `admission_quotas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quota_period_school` (`admission_period_id`,`school_id`),
  ADD KEY `idx_quota_school` (`school_id`);

--
-- Chỉ mục cho bảng `admission_results`
--
ALTER TABLE `admission_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admission_result_run_candidate_aspiration` (`admission_run_id`,`candidate_id`,`aspiration_id`),
  ADD KEY `idx_admission_result_school` (`admission_run_id`,`school_id`,`priority_order`,`admission_score`),
  ADD KEY `idx_admission_result_candidate_period` (`admission_period_id`,`candidate_id`),
  ADD KEY `fk_admission_result_candidate` (`candidate_id`),
  ADD KEY `fk_admission_result_aspiration` (`aspiration_id`),
  ADD KEY `fk_admission_result_school` (`school_id`);

--
-- Chỉ mục cho bảng `admission_runs`
--
ALTER TABLE `admission_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admission_run_period` (`admission_period_id`,`status`),
  ADD KEY `fk_admission_run_user` (`started_by_user_id`);

--
-- Chỉ mục cho bảng `admission_run_snapshots`
--
ALTER TABLE `admission_run_snapshots`
  ADD PRIMARY KEY (`admission_run_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Chỉ mục cho bảng `admission_tie_resolutions`
--
ALTER TABLE `admission_tie_resolutions`
  ADD PRIMARY KEY (`admission_period_id`,`tie_key`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Chỉ mục cho bảng `appeal_grader_assignments`
--
ALTER TABLE `appeal_grader_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appeal_grader_assignment_request` (`appeal_request_id`),
  ADD KEY `idx_appeal_grader_assignment_member` (`council_member_id`),
  ADD KEY `fk_appeal_grader_assignment_user` (`assigned_by_user_id`);

--
-- Chỉ mục cho bảng `appeal_requests`
--
ALTER TABLE `appeal_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appeal_candidate_subject_period` (`admission_period_id`,`candidate_id`,`exam_subject_id`),
  ADD UNIQUE KEY `uq_appeal_exam_paper` (`exam_paper_id`),
  ADD KEY `idx_appeal_subject_status` (`exam_subject_id`,`status`),
  ADD KEY `fk_appeal_candidate` (`candidate_id`);

--
-- Chỉ mục cho bảng `appeal_results`
--
ALTER TABLE `appeal_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appeal_result_request` (`appeal_request_id`),
  ADD KEY `fk_appeal_result_grader` (`grader_user_id`);

--
-- Chỉ mục cho bảng `appeal_windows`
--
ALTER TABLE `appeal_windows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appeal_window_period` (`admission_period_id`);

--
-- Chỉ mục cho bảng `aspirations`
--
ALTER TABLE `aspirations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_aspiration_candidate_period_priority` (`candidate_id`,`admission_period_id`,`priority_order`),
  ADD UNIQUE KEY `uq_aspiration_candidate_period_school` (`candidate_id`,`admission_period_id`,`school_id`),
  ADD KEY `idx_aspiration_school` (`school_id`),
  ADD KEY `fk_aspiration_period` (`admission_period_id`);

--
-- Chỉ mục cho bảng `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_candidates_national_id` (`national_id`),
  ADD UNIQUE KEY `uq_candidates_candidate_code` (`candidate_code`),
  ADD UNIQUE KEY `uq_candidates_user_id` (`user_id`),
  ADD KEY `idx_candidates_school` (`middle_school_id`),
  ADD KEY `idx_candidates_status` (`status`);

--
-- Chỉ mục cho bảng `candidate_priority_points`
--
ALTER TABLE `candidate_priority_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_priority_candidate_period` (`admission_period_id`,`candidate_id`),
  ADD KEY `fk_priority_candidate` (`candidate_id`);

--
-- Chỉ mục cho bảng `councils`
--
ALTER TABLE `councils`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_council_period_type_name` (`admission_period_id`,`council_type`,`name`),
  ADD KEY `idx_council_period_type` (`admission_period_id`,`council_type`),
  ADD KEY `idx_council_site` (`exam_site_id`);

--
-- Chỉ mục cho bảng `council_members`
--
ALTER TABLE `council_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_council_member` (`council_id`,`staff_id`),
  ADD KEY `idx_council_member_staff` (`staff_id`);

--
-- Chỉ mục cho bảng `council_subjects`
--
ALTER TABLE `council_subjects`
  ADD PRIMARY KEY (`council_id`,`exam_subject_id`),
  ADD KEY `idx_council_subject_subject` (`exam_subject_id`);

--
-- Chỉ mục cho bảng `enrollment_confirmations`
--
ALTER TABLE `enrollment_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment_result` (`admission_result_id`),
  ADD UNIQUE KEY `uq_enrollment_period_candidate` (`admission_period_id`,`candidate_id`),
  ADD UNIQUE KEY `uq_enrollment_receipt` (`receipt_code`),
  ADD KEY `fk_enrollment_school` (`school_id`),
  ADD KEY `idx_enrollment_candidate` (`candidate_id`),
  ADD KEY `fk_enrollment_actor` (`actor_user_id`);

--
-- Chỉ mục cho bảng `exam_allocations`
--
ALTER TABLE `exam_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_allocation_candidate_period` (`admission_period_id`,`candidate_id`),
  ADD UNIQUE KEY `uq_exam_allocation_period_candidate_site` (`admission_period_id`,`candidate_id`,`exam_site_id`),
  ADD KEY `idx_exam_allocation_site` (`exam_site_id`),
  ADD KEY `fk_exam_allocation_candidate` (`candidate_id`),
  ADD KEY `fk_exam_allocation_site_period` (`exam_site_id`,`admission_period_id`);

--
-- Chỉ mục cho bảng `exam_candidate_numbers`
--
ALTER TABLE `exam_candidate_numbers`
  ADD PRIMARY KEY (`admission_period_id`,`candidate_id`),
  ADD UNIQUE KEY `uq_exam_number_period` (`admission_period_id`,`exam_number`),
  ADD UNIQUE KEY `uq_exam_sequence_period` (`admission_period_id`,`sequence_no`),
  ADD KEY `fk_exam_number_candidate` (`candidate_id`);

--
-- Chỉ mục cho bảng `exam_grading_states`
--
ALTER TABLE `exam_grading_states`
  ADD PRIMARY KEY (`admission_period_id`),
  ADD KEY `fk_grading_actor` (`reviewed_by`);

--
-- Chỉ mục cho bảng `exam_import_logs`
--
ALTER TABLE `exam_import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_import_user` (`user_id`),
  ADD KEY `fk_exam_import_subject` (`exam_subject_id`);

--
-- Chỉ mục cho bảng `exam_invigilator_assignments`
--
ALTER TABLE `exam_invigilator_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invigilator_room_subject_position` (`exam_room_id`,`exam_subject_id`,`position`),
  ADD KEY `idx_invigilator_staff` (`staff_id`),
  ADD KEY `fk_invigilator_subject` (`exam_subject_id`);

--
-- Chỉ mục cho bảng `exam_invigilator_assignment_history`
--
ALTER TABLE `exam_invigilator_assignment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invigilator_history_assignment` (`assignment_id`),
  ADD KEY `idx_invigilator_history_staff` (`staff_id`),
  ADD KEY `fk_invigilator_history_room` (`exam_room_id`),
  ADD KEY `fk_invigilator_history_subject` (`exam_subject_id`);

--
-- Chỉ mục cho bảng `exam_organization_states`
--
ALTER TABLE `exam_organization_states`
  ADD PRIMARY KEY (`admission_period_id`),
  ADD KEY `fk_exam_org_actor` (`released_by`);

--
-- Chỉ mục cho bảng `exam_papers`
--
ALTER TABLE `exam_papers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_paper_candidate_subject` (`admission_period_id`,`candidate_id`,`exam_subject_id`),
  ADD UNIQUE KEY `uq_exam_paper_period_code` (`admission_period_id`,`anonymous_code`),
  ADD KEY `idx_exam_paper_subject_room` (`exam_subject_id`,`exam_room_id`),
  ADD KEY `fk_exam_paper_candidate` (`candidate_id`),
  ADD KEY `fk_exam_paper_room` (`exam_room_id`);

--
-- Chỉ mục cho bảng `exam_paper_grader_assignments`
--
ALTER TABLE `exam_paper_grader_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_paper_grader_assignment_paper` (`exam_paper_id`),
  ADD KEY `idx_exam_paper_grader_assignment_member` (`council_member_id`),
  ADD KEY `fk_exam_paper_grader_assignment_user` (`assigned_by_user_id`);

--
-- Chỉ mục cho bảng `exam_rooms`
--
ALTER TABLE `exam_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_room_site_code` (`exam_site_id`,`room_code`),
  ADD UNIQUE KEY `uq_exam_room_id_site` (`id`,`exam_site_id`);

--
-- Chỉ mục cho bảng `exam_score_import_batches`
--
ALTER TABLE `exam_score_import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_score_import_batch_scope` (`admission_period_id`,`exam_subject_id`,`user_id`),
  ADD KEY `fk_score_import_batch_user` (`user_id`),
  ADD KEY `fk_score_import_batch_subject` (`exam_subject_id`);

--
-- Chỉ mục cho bảng `exam_score_import_rows`
--
ALTER TABLE `exam_score_import_rows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_score_import_batch_row` (`batch_id`,`row_number`),
  ADD KEY `idx_score_import_row_valid` (`batch_id`,`is_valid`);

--
-- Chỉ mục cho bảng `exam_sites`
--
ALTER TABLE `exam_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_site_period_code` (`admission_period_id`,`site_code`),
  ADD UNIQUE KEY `uq_exam_site_id_period` (`id`,`admission_period_id`),
  ADD KEY `idx_exam_site_period` (`admission_period_id`),
  ADD KEY `fk_exam_site_school` (`school_id`);

--
-- Chỉ mục cho bảng `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_subject_period_code` (`admission_period_id`,`subject_code`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`created_at`);

--
-- Chỉ mục cho bảng `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_code` (`code`);

--
-- Chỉ mục cho bảng `publication_events`
--
ALTER TABLE `publication_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_publication_period_type` (`admission_period_id`,`publication_type`),
  ADD KEY `fk_publication_user` (`published_by_user_id`),
  ADD KEY `idx_publication_period_status` (`admission_period_id`,`status`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_code` (`code`);

--
-- Chỉ mục cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Chỉ mục cho bảng `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_assignment_candidate_period` (`admission_period_id`,`candidate_id`),
  ADD KEY `idx_room_assignment_room` (`exam_room_id`),
  ADD KEY `fk_room_assignment_candidate` (`candidate_id`),
  ADD KEY `fk_room_assignment_site_period` (`exam_site_id`,`admission_period_id`),
  ADD KEY `fk_room_assignment_room_site` (`exam_room_id`,`exam_site_id`),
  ADD KEY `fk_room_assignment_allocation` (`admission_period_id`,`candidate_id`,`exam_site_id`);

--
-- Chỉ mục cho bảng `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schools_code` (`school_code`),
  ADD KEY `idx_schools_level` (`level`),
  ADD KEY `idx_schools_area` (`admission_area`),
  ADD KEY `idx_schools_active` (`active`);

--
-- Chỉ mục cho bảng `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_score_paper` (`exam_paper_id`),
  ADD UNIQUE KEY `uq_score_code` (`anonymous_code`),
  ADD KEY `fk_score_user` (`entered_by`);

--
-- Chỉ mục cho bảng `score_change_logs`
--
ALTER TABLE `score_change_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_score_log_paper` (`exam_paper_id`),
  ADD KEY `idx_score_log_appeal` (`appeal_request_id`),
  ADD KEY `fk_score_log_user` (`changed_by_user_id`);

--
-- Chỉ mục cho bảng `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_user_id` (`user_id`),
  ADD KEY `idx_staff_active` (`active`),
  ADD KEY `idx_staff_workplace` (`workplace`),
  ADD KEY `idx_staff_email` (`email`),
  ADD KEY `idx_staff_phone` (`phone`),
  ADD KEY `idx_staff_school_id` (`school_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role_active` (`role`,`active`);

--
-- Chỉ mục cho bảng `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `adjustment_requests`
--
ALTER TABLE `adjustment_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT cho bảng `admission_periods`
--
ALTER TABLE `admission_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- AUTO_INCREMENT cho bảng `admission_period_events`
--
ALTER TABLE `admission_period_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `admission_quotas`
--
ALTER TABLE `admission_quotas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT cho bảng `admission_results`
--
ALTER TABLE `admission_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT cho bảng `admission_runs`
--
ALTER TABLE `admission_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT cho bảng `appeal_grader_assignments`
--
ALTER TABLE `appeal_grader_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT cho bảng `appeal_requests`
--
ALTER TABLE `appeal_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT cho bảng `appeal_results`
--
ALTER TABLE `appeal_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT cho bảng `appeal_windows`
--
ALTER TABLE `appeal_windows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT cho bảng `aspirations`
--
ALTER TABLE `aspirations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=455;

--
-- AUTO_INCREMENT cho bảng `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1957;

--
-- AUTO_INCREMENT cho bảng `candidate_priority_points`
--
ALTER TABLE `candidate_priority_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT cho bảng `councils`
--
ALTER TABLE `councils`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=352;

--
-- AUTO_INCREMENT cho bảng `council_members`
--
ALTER TABLE `council_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT cho bảng `enrollment_confirmations`
--
ALTER TABLE `enrollment_confirmations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `exam_allocations`
--
ALTER TABLE `exam_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `exam_import_logs`
--
ALTER TABLE `exam_import_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `exam_invigilator_assignments`
--
ALTER TABLE `exam_invigilator_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT cho bảng `exam_invigilator_assignment_history`
--
ALTER TABLE `exam_invigilator_assignment_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `exam_papers`
--
ALTER TABLE `exam_papers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT cho bảng `exam_paper_grader_assignments`
--
ALTER TABLE `exam_paper_grader_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT cho bảng `exam_rooms`
--
ALTER TABLE `exam_rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=319;

--
-- AUTO_INCREMENT cho bảng `exam_score_import_batches`
--
ALTER TABLE `exam_score_import_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `exam_score_import_rows`
--
ALTER TABLE `exam_score_import_rows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT cho bảng `exam_sites`
--
ALTER TABLE `exam_sites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT cho bảng `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=395;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT cho bảng `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `publication_events`
--
ALTER TABLE `publication_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `room_assignments`
--
ALTER TABLE `room_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT cho bảng `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT cho bảng `scores`
--
ALTER TABLE `scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=232;

--
-- AUTO_INCREMENT cho bảng `score_change_logs`
--
ALTER TABLE `score_change_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `staff`
--
ALTER TABLE `staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=438;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2034;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `adjustment_requests`
--
ALTER TABLE `adjustment_requests`
  ADD CONSTRAINT `fk_adjustment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adjustment_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adjustment_submitter` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `admission_bonus_reviews`
--
ALTER TABLE `admission_bonus_reviews`
  ADD CONSTRAINT `admission_bonus_reviews_ibfk_1` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`),
  ADD CONSTRAINT `admission_bonus_reviews_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`),
  ADD CONSTRAINT `admission_bonus_reviews_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `admission_period_configs`
--
ALTER TABLE `admission_period_configs`
  ADD CONSTRAINT `fk_period_config_actor` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_period_config_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `admission_period_events`
--
ALTER TABLE `admission_period_events`
  ADD CONSTRAINT `fk_period_event_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_period_event_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `admission_quotas`
--
ALTER TABLE `admission_quotas`
  ADD CONSTRAINT `fk_quota_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_quota_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `admission_results`
--
ALTER TABLE `admission_results`
  ADD CONSTRAINT `fk_admission_result_aspiration` FOREIGN KEY (`aspiration_id`) REFERENCES `aspirations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_result_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_result_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_result_run` FOREIGN KEY (`admission_run_id`) REFERENCES `admission_runs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_result_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `admission_runs`
--
ALTER TABLE `admission_runs`
  ADD CONSTRAINT `fk_admission_run_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admission_run_user` FOREIGN KEY (`started_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `admission_run_snapshots`
--
ALTER TABLE `admission_run_snapshots`
  ADD CONSTRAINT `admission_run_snapshots_ibfk_1` FOREIGN KEY (`admission_run_id`) REFERENCES `admission_runs` (`id`),
  ADD CONSTRAINT `admission_run_snapshots_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `admission_tie_resolutions`
--
ALTER TABLE `admission_tie_resolutions`
  ADD CONSTRAINT `admission_tie_resolutions_ibfk_1` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`),
  ADD CONSTRAINT `admission_tie_resolutions_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `appeal_grader_assignments`
--
ALTER TABLE `appeal_grader_assignments`
  ADD CONSTRAINT `fk_appeal_grader_assignment_member` FOREIGN KEY (`council_member_id`) REFERENCES `council_members` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_grader_assignment_request` FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_grader_assignment_user` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `appeal_requests`
--
ALTER TABLE `appeal_requests`
  ADD CONSTRAINT `fk_appeal_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `appeal_results`
--
ALTER TABLE `appeal_results`
  ADD CONSTRAINT `fk_appeal_result_grader` FOREIGN KEY (`grader_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appeal_result_request` FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `appeal_windows`
--
ALTER TABLE `appeal_windows`
  ADD CONSTRAINT `fk_appeal_window_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `aspirations`
--
ALTER TABLE `aspirations`
  ADD CONSTRAINT `fk_aspiration_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aspiration_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aspiration_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `fk_candidates_school` FOREIGN KEY (`middle_school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_candidates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `candidate_priority_points`
--
ALTER TABLE `candidate_priority_points`
  ADD CONSTRAINT `fk_priority_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_priority_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `councils`
--
ALTER TABLE `councils`
  ADD CONSTRAINT `fk_council_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_council_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `council_members`
--
ALTER TABLE `council_members`
  ADD CONSTRAINT `fk_council_member_council` FOREIGN KEY (`council_id`) REFERENCES `councils` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_council_member_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `council_subjects`
--
ALTER TABLE `council_subjects`
  ADD CONSTRAINT `fk_council_subject_council` FOREIGN KEY (`council_id`) REFERENCES `councils` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_council_subject_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `enrollment_confirmations`
--
ALTER TABLE `enrollment_confirmations`
  ADD CONSTRAINT `fk_enrollment_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_enrollment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`),
  ADD CONSTRAINT `fk_enrollment_result` FOREIGN KEY (`admission_result_id`) REFERENCES `admission_results` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_allocations`
--
ALTER TABLE `exam_allocations`
  ADD CONSTRAINT `fk_exam_allocation_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_allocation_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_allocation_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_allocation_site_period` FOREIGN KEY (`exam_site_id`,`admission_period_id`) REFERENCES `exam_sites` (`id`, `admission_period_id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_candidate_numbers`
--
ALTER TABLE `exam_candidate_numbers`
  ADD CONSTRAINT `fk_exam_number_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`),
  ADD CONSTRAINT `fk_exam_number_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `exam_grading_states`
--
ALTER TABLE `exam_grading_states`
  ADD CONSTRAINT `fk_grading_actor` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_grading_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`);

--
-- Các ràng buộc cho bảng `exam_import_logs`
--
ALTER TABLE `exam_import_logs`
  ADD CONSTRAINT `fk_exam_import_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_import_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_invigilator_assignments`
--
ALTER TABLE `exam_invigilator_assignments`
  ADD CONSTRAINT `fk_invigilator_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invigilator_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invigilator_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_invigilator_assignment_history`
--
ALTER TABLE `exam_invigilator_assignment_history`
  ADD CONSTRAINT `fk_invigilator_history_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `exam_invigilator_assignments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invigilator_history_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invigilator_history_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invigilator_history_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_organization_states`
--
ALTER TABLE `exam_organization_states`
  ADD CONSTRAINT `fk_exam_org_actor` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_exam_org_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `exam_papers`
--
ALTER TABLE `exam_papers`
  ADD CONSTRAINT `fk_exam_paper_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_paper_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_paper_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_paper_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_paper_grader_assignments`
--
ALTER TABLE `exam_paper_grader_assignments`
  ADD CONSTRAINT `fk_exam_paper_grader_assignment_member` FOREIGN KEY (`council_member_id`) REFERENCES `council_members` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_paper_grader_assignment_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_paper_grader_assignment_user` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_rooms`
--
ALTER TABLE `exam_rooms`
  ADD CONSTRAINT `fk_exam_room_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_score_import_batches`
--
ALTER TABLE `exam_score_import_batches`
  ADD CONSTRAINT `fk_score_import_batch_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_import_batch_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_import_batch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_score_import_rows`
--
ALTER TABLE `exam_score_import_rows`
  ADD CONSTRAINT `fk_score_import_row_batch` FOREIGN KEY (`batch_id`) REFERENCES `exam_score_import_batches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_sites`
--
ALTER TABLE `exam_sites`
  ADD CONSTRAINT `fk_exam_site_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_site_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD CONSTRAINT `fk_exam_subject_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `publication_events`
--
ALTER TABLE `publication_events`
  ADD CONSTRAINT `fk_publication_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_publication_user` FOREIGN KEY (`published_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD CONSTRAINT `fk_room_assignment_allocation` FOREIGN KEY (`admission_period_id`,`candidate_id`,`exam_site_id`) REFERENCES `exam_allocations` (`admission_period_id`, `candidate_id`, `exam_site_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_room_site` FOREIGN KEY (`exam_room_id`,`exam_site_id`) REFERENCES `exam_rooms` (`id`, `exam_site_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_assignment_site_period` FOREIGN KEY (`exam_site_id`,`admission_period_id`) REFERENCES `exam_sites` (`id`, `admission_period_id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `fk_score_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_user` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `score_change_logs`
--
ALTER TABLE `score_change_logs`
  ADD CONSTRAINT `fk_score_log_appeal` FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_log_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_log_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
