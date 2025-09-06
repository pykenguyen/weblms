-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th9 06, 2025 lúc 07:50 AM
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
-- Cơ sở dữ liệu: `lcms_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignments`
--

CREATE TABLE `assignments` (
  `id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `description`, `due_date`, `file_path`, `created_at`) VALUES
(1, 1, 'Bài tập Tiếng Anh số 1', 'Viết một đoạn văn 150 từ về chủ đề \"My Favorite Hobby\".', '2025-08-20 23:59:59', NULL, '2025-08-10 09:00:00'),
(2, 1, 'Bài tập Tiếng Anh số 2', 'Nghe đoạn hội thoại và trả lời 10 câu hỏi.', '2025-08-25 23:59:59', NULL, '2025-08-10 09:10:00'),
(3, 2, 'Bài tập Toán số 1', 'Giải 10 bài toán về phương trình bậc hai.', '2025-08-22 23:59:59', NULL, '2025-08-10 09:20:00'),
(4, 2, 'Bài tập Toán số 2', 'Chứng minh bất đẳng thức Cauchy–Schwarz.', '2025-08-28 23:59:59', NULL, '2025-08-10 09:30:00'),
(5, 3, 'Bài tập Lập trình số 1', 'Viết chương trình C++ quản lý sinh viên với các chức năng thêm, sửa, xóa.', '2025-08-21 23:59:59', NULL, '2025-08-10 09:40:00'),
(6, 3, 'Bài tập Lập trình số 2', 'Tạo trang web HTML hiển thị thông tin cá nhân kèm CSS.', '2025-08-27 23:59:59', NULL, '2025-08-10 09:50:00'),
(50, 1, 'TA 12 thì', 'lẹ mấy em ơi', '2025-09-17 12:20:00', 'uploads/assignments/1757136061_Bài Tập Tổng Hợp 12 Thì.pdf', '2025-09-06 12:21:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `teacher_id` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher_id`, `created_at`) VALUES
(1, '22BITV05', 2, '2025-09-02 22:02:04'),
(2, '22BLGV01', 3, '2025-09-02 22:02:04'),
(3, '23BLGV02', 2, '2025-09-02 22:02:04'),
(4, '22BITV04', 19, '2025-09-02 22:02:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `lesson_id` bigint(20) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) NOT NULL,
  `teacher_id` bigint(20) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `courses`
--

INSERT INTO `courses` (`id`, `teacher_id`, `title`, `description`, `thumbnail`, `created_at`, `updated_at`, `price`) VALUES
(1, 2, 'Khóa học Tiếng Anh cơ bản', 'Học tiếng Anh giao tiếp cho người mới bắt đầu', 'image/ta.jpg', '2025-08-12 03:22:08', '2025-09-05 16:43:27', 4000000.00),
(2, 3, 'Khóa học Toán nâng cao', 'Chuyên đề phương trình và bất đẳng thức', 'image/toan.png', '2025-08-12 03:22:08', '2025-09-05 16:44:43', 3000000.00),
(3, 19, 'Khóa học Lập trình C++', 'Lập trình hướng đối tượng với C++', 'image/c++.png', '2025-08-12 03:22:08', '2025-09-05 16:44:14', 2500000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `course_reviews`
--

CREATE TABLE `course_reviews` (
  `id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `student_id` bigint(20) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `course_user`
--

CREATE TABLE `course_user` (
  `id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `course_user`
--

INSERT INTO `course_user` (`id`, `course_id`, `user_id`, `created_at`) VALUES
(10, 1, 4, '2025-09-06 11:07:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lessons`
--

CREATE TABLE `lessons` (
  `id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `content_url` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `assignment_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content_type`, `content_url`, `file_path`, `created_at`, `assignment_file`) VALUES
(1, 1, 'Các thì trong Tiếng Anh (Tenses)', 'pdf', NULL, 'uploads/lessons/placeholder.pdf', '2025-08-24 00:13:53', NULL),
(2, 1, 'Từ vựng về Sở thích (Hobbies Vocabulary)', 'youtube', 'https://www.youtube.com/watch?v=zdlLu_w_pcw', NULL, '2025-08-24 00:13:53', NULL),
(3, 2, 'Lý thuyết và Công thức nghiệm Phương trình bậc hai', 'pdf', NULL, 'uploads/lessons/placeholder.pdf', '2025-08-24 00:13:53', NULL),
(4, 2, 'Video Hướng dẫn chứng minh Bất đẳng thức', 'youtube', 'https://www.youtube.com/watch?v=example_math', NULL, '2025-08-24 00:13:53', NULL),
(5, 3, 'Khái niệm về Lập trình Hướng đối tượng (OOP)', 'youtube', 'https://www.youtube.com/watch?v=example_oop', NULL, '2025-08-24 00:13:53', NULL),
(6, 3, 'Cú pháp cơ bản và cách tạo Class trong C++', 'pdf', NULL, 'uploads/lessons/placeholder.pdf', '2025-08-24 00:13:53', NULL),
(7, 1, 'Bài 1: Bảng chữ cái và phát âm', 'youtube', NULL, NULL, '2025-08-23 22:29:00', NULL),
(8, 1, 'Bài 2: Giới thiệu bản thân và chào hỏi', 'pdf', NULL, NULL, '2025-08-23 22:29:00', NULL),
(9, 1, 'Bài 3: Các thì cơ bản trong tiếng Anh', 'text', NULL, NULL, '2025-08-23 22:29:00', NULL),
(10, 2, 'Chuyên đề 1: Phương trình và bất phương trình bậc hai', 'pdf', NULL, NULL, '2025-08-23 22:29:00', NULL),
(11, 2, 'Chuyên đề 2: Hệ phương trình tuyến tính', 'docx', NULL, NULL, '2025-08-23 22:29:00', NULL),
(12, 2, 'Chuyên đề 3: Giới hạn và continous của hàm số', 'youtube', NULL, NULL, '2025-08-23 22:29:00', NULL),
(13, 3, 'Bài 1: Cài đặt môi trường và chương trình \"Hello World\"', 'text', NULL, NULL, '2025-08-23 22:29:00', NULL),
(14, 3, 'Bài 2: Biến, kiểu dữ liệu và các toán tử cơ bản', 'pdf', NULL, NULL, '2025-08-23 22:29:00', NULL),
(15, 3, 'Bài 3: Cấu trúc điều khiển If-Else và Switch-Case', 'youtube', NULL, NULL, '2025-08-23 22:29:00', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `link` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'general',
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `link`, `type`, `expires_at`) VALUES
(1, 2, 'Bài tập đã được nộp', 'Học sinh Nguyễn Văn An đã nộp Bài tập Tiếng Anh số 1.', 0, '2025-09-02 23:28:59', NULL, 'general', NULL),
(2, 3, 'Có một câu hỏi mới', 'Một học sinh đã hỏi về Bài tập Toán số 2.', 0, '2025-09-02 23:29:05', NULL, 'general', NULL),
(3, 3, 'Đã hoàn thành khóa học', 'Khóa học Toán nâng cao của bạn đã có một học sinh hoàn thành.', 1, '2025-09-02 23:29:05', NULL, 'general', NULL),
(4, 4, 'Bài tập mới: ta', 'Khóa học: Khóa học Tiếng Anh cơ bản. Hạn nộp: 2025-09-17 12:19:00', 0, '2025-09-06 12:20:17', 'assignment-detail.php?id=48', 'course', NULL),
(5, 4, 'Bài tập mới: TA 12 thì', 'Khóa học: Khóa học Tiếng Anh cơ bản. Hạn nộp: 2025-09-16 12:20:00', 0, '2025-09-06 12:20:44', 'assignment-detail.php?id=49', 'course', NULL),
(6, 4, 'Bài tập mới: TA 12 thì', 'Khóa học: Khóa học Tiếng Anh cơ bản. Hạn nộp: 2025-09-17 12:20:00', 0, '2025-09-06 12:21:01', 'assignment-detail.php?id=50', 'course', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `course_id`, `amount`, `payment_date`, `created_at`) VALUES
(1, 2, 1, 500000.00, '2025-08-20 10:00:00', '2025-08-31 16:36:50'),
(2, 3, 2, 750000.00, '2025-08-22 14:30:00', '2025-08-31 16:36:50'),
(3, 2, 3, 250000.00, '2025-08-25 09:15:00', '2025-08-31 16:36:50'),
(4, 4, 1, 500000.00, '2025-08-28 11:00:00', '2025-08-31 16:36:50'),
(5, 3, 3, 250000.00, '2025-08-30 16:45:00', '2025-08-31 16:36:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `progress_tracking`
--

CREATE TABLE `progress_tracking` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `lesson_id` bigint(20) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `last_viewed` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `progress_tracking`
--

INSERT INTO `progress_tracking` (`id`, `user_id`, `lesson_id`, `is_completed`, `last_viewed`) VALUES
(1, 4, 1, 1, '2025-08-10 09:15:00'),
(2, 4, 2, 0, '2025-08-10 09:30:00'),
(3, 5, 1, 1, '2025-08-10 10:05:00'),
(4, 5, 3, 0, '2025-08-10 10:25:00'),
(5, 6, 2, 1, '2025-08-10 11:00:00'),
(6, 6, 4, 0, '2025-08-10 11:15:00'),
(7, 7, 3, 1, '2025-08-10 13:20:00'),
(8, 7, 4, 1, '2025-08-10 13:45:00'),
(9, 8, 5, 0, '2025-08-10 14:10:00'),
(10, 8, 6, 1, '2025-08-10 14:40:00'),
(11, 9, 5, 1, '2025-08-10 15:05:00'),
(12, 9, 6, 0, '2025-08-10 15:20:00'),
(13, 10, 1, 1, '2025-08-10 16:00:00'),
(14, 10, 2, 1, '2025-08-10 16:25:00'),
(20, 5, 13, 1, '2025-09-02 01:15:44'),
(21, 5, 14, 1, '2025-09-02 01:15:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quizzes`
--

CREATE TABLE `quizzes` (
  `id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` bigint(20) NOT NULL,
  `attempt_id` bigint(20) NOT NULL,
  `question_id` bigint(20) NOT NULL,
  `option_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` bigint(20) NOT NULL,
  `quiz_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `started_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT current_timestamp(),
  `score` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` bigint(20) NOT NULL,
  `question_id` bigint(20) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` bigint(20) NOT NULL,
  `quiz_id` bigint(20) NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 1,
  `type` enum('single','multiple') NOT NULL DEFAULT 'single'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) NOT NULL,
  `teacher_id` bigint(20) DEFAULT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `schedules`
--

INSERT INTO `schedules` (`id`, `teacher_id`, `course_id`, `day_of_week`, `start_time`, `end_time`, `location`, `created_at`) VALUES
(1, 2, 1, 2, '08:00:00', '10:00:00', 'Phòng A101', '2025-09-02 23:42:24'),
(2, 3, 2, 4, '14:00:00', '16:00:00', 'Phòng B202', '2025-09-02 23:42:24'),
(3, 19, 3, 5, '09:30:00', '11:30:00', 'Online', '2025-09-02 23:42:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `submissions`
--

CREATE TABLE `submissions` (
  `id` bigint(20) NOT NULL,
  `assignment_id` bigint(20) DEFAULT NULL,
  `student_id` bigint(20) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `grade` float DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `submissions`
--

INSERT INTO `submissions` (`id`, `assignment_id`, `student_id`, `file_path`, `submitted_at`, `grade`, `feedback`) VALUES
(1, 1, 4, 'uploads/assignments/1_student4.docx', '2025-08-15 10:30:00', 8.5, 'Bài viết khá tốt, cần chú ý ngữ pháp.'),
(2, 1, 5, 'uploads/assignments/1_student5.docx', '2025-08-15 11:15:00', 9, 'Bài viết rõ ràng và mạch lạc.'),
(3, 2, 6, 'uploads/assignments/2_student6.docx', '2025-08-18 09:45:00', NULL, NULL),
(5, 3, 7, 'uploads/assignments/3_student7.pdf', '2025-08-16 15:10:00', 9.5, 'Bài giải chính xác và trình bày tốt.'),
(6, 4, 8, 'uploads/assignments/4_student8.pdf', '2025-08-19 08:55:00', NULL, NULL),
(7, 5, 9, 'uploads/assignments/5_student9.zip', '2025-08-17 20:00:00', 10, 'Hoàn thành xuất sắc.'),
(8, 6, 10, 'uploads/assignments/6_student10.zip', '2025-08-18 19:30:00', 8, 'Code chạy tốt, nhưng cần tối ưu hơn.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `avatar_url`, `date_of_birth`, `reset_token`, `reset_token_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Admin Quản Trị', 'admin@lms.com', '$2y$10$4L/NwJ6sbtH7CveTsEj9F..XVwiTbiFcMpao/0mJXu1mo75KhQpHC', 'admin', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(2, 'Giáo viên Anh Văn', 'teacher.anhvan@lms.com', '$2y$10$NDaJwpBi3RpADflO1XE/IOfXqWh9cdQbo4LWX3Lk46O3YMxNo81bi', 'teacher', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(3, 'Giáo viên Toán', 'teacher.toan@lms.com', '$2y$10$rC/GEsECpFryFw1/qR1GpeReZzPCd9Gpwg.3OTj6ror6UZe8aFjbW', 'teacher', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(4, 'Nguyễn Văn An', 'student.an@lms.com', '$2y$10$M7Vmey4tOW9hCKwAfGKhUO/EfIJ0Mj7fX1B.DMJMRlqX6hYvLzUJa', 'student', '0989127583', NULL, NULL, '294c32272aa34c5c4a40c6f6e60d6e4ab1864e4ba8829e0c5da9835ef47360b0', '2025-09-03 21:08:40', '2025-08-05 11:16:35', '2025-09-05 14:57:38'),
(5, 'Trần Thị Bình', 'student.binh@lms.com', '$2y$10$x6ikEmYAIcmOnzQ14U1YSOxIiCHQd/YNnb8lPygE5x.3/4ntnqz8O', 'student', '0947391284', NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-31 17:42:35'),
(6, 'Lê Văn Cường', 'student.cuong@lms.com', '$2y$10$LbKjv0H3HgQH1/4C50KgMOD3CRwYZpp.ZbrnitBBLmi4.Fx4fZt.m', 'student', '0957483164', NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-31 17:42:44'),
(7, 'Phạm Thị Dung', 'student.dung@lms.com', '$2y$10$SF9bQdDd3swfrnzB.pIBfekfY9Akr9vYdEkDO3.QU1hj2V3InibiK', 'student', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(8, 'Võ Văn Giang', 'student.giang@lms.com', '$2y$10$/4FZW3.vNWk6T6nDf/mhI.Kbdb7Hu16rs/xq2GqhW041gRwAftAGm', 'student', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(9, 'Đỗ Thị Hương', 'student.huong@lms.com', '$2y$10$WSBl3PATabuUWbBO3IgSiesEgHevE9mv7eMTHc/JZZmfU69zHFd1C', 'student', NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:16:35', '2025-08-23 14:04:10'),
(10, 'Hoàng Văn Khánh', 'student.khanh@lms.com', '$2y$10$.NDuKA1NqalWIhli9sYk.O1zO0NWoH2oHfq3QQGmY69zYEcMratfC', 'student', '', NULL, '2002-01-04', NULL, NULL, '2025-08-05 11:16:35', '2025-09-05 14:30:20'),
(12, 'Nguyễn Đức Bảo Long', 'baolong260704@gmail.com', '$2y$10$mek40Tkm8wDnKYbQqSWBlO9/uohTl8vtj6PfOHKILZ6ehymLo53f6', 'student', NULL, NULL, NULL, '3e80f732393a47d8dde3bc5eb40666fa6ddb1ad2e1faa79ece88450aaa1b2664', '2025-09-03 21:09:35', '2025-08-23 13:09:41', '2025-09-04 01:39:35'),
(19, 'Giáo Viên Lập Trình', 'teacher.laptrinh@lms.com', '$2y$10$AsKSye//jJLuxWg2gjJ6meifw.FmGsga.hBzh5hFAW1.YbyrLqywO', 'teacher', NULL, NULL, NULL, NULL, NULL, '2025-08-23 22:22:34', '2025-08-23 22:22:34'),
(20, 'Long', 'majinbulong@gmail.com', '$2y$10$PEtR6U46iPZyCzn6MU0iTOZMsWkgJOWd4U5BtsgO1r1GhJLmMvepS', 'student', NULL, NULL, NULL, NULL, NULL, '2025-09-02 23:38:18', '2025-09-02 23:38:18'),
(21, 'Giáo Viên Ngữ Văn', 'teacher.nguvan@lms.com', '$2y$10$8eNg3cmtiaSdpb6HTD77KerQ6ZlvP2ZLTOUezPZUnH7ILp47XPuY2', 'teacher', '', NULL, NULL, NULL, NULL, '2025-09-05 16:17:02', '2025-09-05 16:17:02');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignment_course` (`course_id`);

--
-- Chỉ mục cho bảng `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Chỉ mục cho bảng `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Chỉ mục cho bảng `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_teacher` (`teacher_id`);

--
-- Chỉ mục cho bảng `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Chỉ mục cho bảng `course_user`
--
ALTER TABLE `course_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lesson_course` (`course_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `progress_tracking`
--
ALTER TABLE `progress_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_progress_user_lesson` (`user_id`,`lesson_id`);

--
-- Chỉ mục cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_course` (`course_id`);

--
-- Chỉ mục cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_answer_attempt` (`attempt_id`),
  ADD KEY `idx_answer_question` (`question_id`),
  ADD KEY `idx_answer_option` (`option_id`);

--
-- Chỉ mục cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt_quiz` (`quiz_id`),
  ADD KEY `idx_attempt_student` (`student_id`);

--
-- Chỉ mục cho bảng `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_option_question` (`question_id`);

--
-- Chỉ mục cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_quiz` (`quiz_id`);

--
-- Chỉ mục cho bảng `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_teacher` (`teacher_id`),
  ADD KEY `fk_schedule_course` (`course_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Chỉ mục cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submission_assignment` (`assignment_id`),
  ADD KEY `idx_submission_student` (`student_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT cho bảng `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `course_reviews`
--
ALTER TABLE `course_reviews`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `course_user`
--
ALTER TABLE `course_user`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `progress_tracking`
--
ALTER TABLE `progress_tracking`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD CONSTRAINT `course_reviews_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_reviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `course_user`
--
ALTER TABLE `course_user`
  ADD CONSTRAINT `course_user_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `progress_tracking`
--
ALTER TABLE `progress_tracking`
  ADD CONSTRAINT `progress_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_tracking_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_option` FOREIGN KEY (`option_id`) REFERENCES `quiz_options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `fk_option_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedule_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_schedule_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
