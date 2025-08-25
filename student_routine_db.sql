-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-08-25 19:31:28
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `student_routine_db`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `session_id` varchar(128) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `email`, `password`, `role`, `is_active`, `created_at`, `last_login`, `login_attempts`, `locked_until`) VALUES
(1, 'admin', 'admin@studentorganizer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, '2025-08-24 04:45:55', NULL, 2, NULL),
(3, 'CheeseStar', 'wilsontan0427@1utar.my', '$2y$10$BDKq0xWsisxe87Eym/CJTu7pqA51wRzC7tJD7yZtxAuXnWOBWNhAi', 'super_admin', 1, '2025-08-24 21:30:03', '2025-08-25 00:41:06', 0, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `category_type` enum('income','expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_type`, `created_at`) VALUES
(1, 'Allowance', 'income', '2025-07-20 08:27:09'),
(2, 'Part-time Job', 'income', '2025-07-20 08:27:09'),
(3, 'Scholarship', 'income', '2025-07-20 08:27:09'),
(4, 'Gift Money', 'income', '2025-07-20 08:27:09'),
(5, 'Other Income', 'income', '2025-07-20 08:27:09'),
(6, 'Food', 'expense', '2025-07-20 08:27:09'),
(7, 'Transportation', 'expense', '2025-07-20 08:27:09'),
(8, 'Books & Supplies', 'expense', '2025-07-20 08:27:09'),
(9, 'Entertainment', 'expense', '2025-07-20 08:27:09'),
(10, 'Bills', 'expense', '2025-07-20 08:27:09'),
(11, 'Clothing', 'expense', '2025-07-20 08:27:09'),
(12, 'Health', 'expense', '2025-07-20 08:27:09'),
(13, 'Other Expenses', 'expense', '2025-07-20 08:27:09');

-- --------------------------------------------------------

--
-- 表的结构 `diary_entries`
--

CREATE TABLE `diary_entries` (
  `entry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `mood` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `diary_entries`
--

INSERT INTO `diary_entries` (`entry_id`, `user_id`, `title`, `content`, `mood`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Day 2', 'Bad day, <b>raining</b>', 'Angry', '2025-08-04', '2025-08-25 17:00:16', '2025-08-25 17:00:16'),
(2, 1, 'Day4', 'Noting bad thing happen', 'Happy', '2025-08-06', '2025-08-25 17:00:43', '2025-08-25 17:00:43'),
(3, 1, 'Haizzz', 'Assignment due date<div><br></div>', 'Tired', '2025-08-09', '2025-08-25 17:01:08', '2025-08-25 17:01:08');

-- --------------------------------------------------------

--
-- 表的结构 `error_logs`
--

CREATE TABLE `error_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `error_type` varchar(50) NOT NULL,
  `error_message` text NOT NULL,
  `error_file` varchar(500) DEFAULT NULL,
  `error_line` int(11) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `error_logs`
--

INSERT INTO `error_logs` (`log_id`, `user_id`, `error_type`, `error_message`, `error_file`, `error_line`, `stack_trace`, `request_uri`, `user_agent`, `ip_address`, `created_at`) VALUES
(1, 1, 'DATABASE:ERROR', 'Database error in add_habit.php', '', 0, '[{\"file\":\"C:\\\\xampp\\\\htdocs\\\\Student-Routine-Organizer\\\\includes\\\\ErrorHandler.php\",\"line\":193,\"function\":\"logApplicationError\",\"class\":\"ErrorHandler\",\"type\":\"::\"},{\"file\":\"C:\\\\xampp\\\\htdocs\\\\Student-Routine-Organizer\\\\diary\\\\add_entry.php\",\"line\":62,\"function\":\"logDatabaseError\",\"class\":\"ErrorHandler\",\"type\":\"::\"}]', '/student-routine-organizer/diary/add_entry.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '::1', '2025-08-25 16:23:52'),
(2, 1, 'DATABASE:ERROR', 'Database error in add_habit.php', '', 0, '[{\"file\":\"C:\\\\xampp\\\\htdocs\\\\Student-Routine-Organizer\\\\includes\\\\ErrorHandler.php\",\"line\":193,\"function\":\"logApplicationError\",\"class\":\"ErrorHandler\",\"type\":\"::\"},{\"file\":\"C:\\\\xampp\\\\htdocs\\\\Student-Routine-Organizer\\\\diary\\\\add_entry.php\",\"line\":62,\"function\":\"logDatabaseError\",\"class\":\"ErrorHandler\",\"type\":\"::\"}]', '/student-routine-organizer/diary/add_entry.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '::1', '2025-08-25 16:23:58');

-- --------------------------------------------------------

--
-- 表的结构 `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `exercise_name` varchar(100) DEFAULT NULL,
  `met_value` decimal(4,1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `exercises`
--

INSERT INTO `exercises` (`id`, `category`, `exercise_name`, `met_value`) VALUES
(1, 'Running & Jogging', 'Jogging', 7.5),
(2, 'Running & Jogging', 'Running, 5 mph (12 min/mile)', 8.5),
(3, 'Running & Jogging', 'Running, 6 mph (10 min/mile)', 9.3),
(4, 'Running & Jogging', 'Running, 7 mph (8.5 min/mile)', 11.0),
(5, 'Bicycling', 'Bicycling', 7.0),
(6, 'Bicycling', 'Bicycling, mountain', 8.5),
(7, 'Yoga & Pilates', 'Yoga', 2.3),
(8, 'Yoga & Pilates', 'Yoga, Surya Namaskar', 3.5),
(9, 'Yoga & Pilates', 'Pilates', 2.8),
(10, 'Gym & Calisthenics', 'Pushups, sit ups, pull-ups, vigorous effort', 7.5),
(11, 'Gym & Calisthenics', 'Pushups, sit ups, moderate effort', 3.8),
(12, 'Gym & Calisthenics', 'Plank, crunches, light effort', 2.8),
(13, 'Gym & Calisthenics', 'Stationary bike', 6.8),
(14, 'Gym & Calisthenics', 'Rowing machine (moderate effort)', 7.5),
(15, 'Gym & Calisthenics', 'Rowing machine (very vigorous)', 14.0),
(16, 'Gym & Calisthenics', 'Rope skipping (jump rope)', 11.0),
(17, 'Dance', 'Zumba', 6.5),
(18, 'Dance', 'Jazz dancing', 4.5),
(19, 'Dance', 'Ballet', 5.0),
(20, 'Sports', 'Basketball', 7.5),
(21, 'Sports', 'Badminton', 5.5),
(22, 'Sports', 'Volleyball', 4.0),
(23, 'Sports', 'Beach Volleyball', 8.0),
(24, 'Sports', 'Football', 8.0),
(25, 'Sports', 'Hockey', 8.0),
(26, 'Water Activities', 'Swimming', 6.0),
(27, 'Water Activities', 'Water aerobics', 5.5),
(28, 'Water Activities', 'Water polo', 10.0),
(29, 'Martial Arts', 'Muay Thai boxing', 10.3),
(30, 'Martial Arts', 'Judo', 11.3),
(31, 'Martial Arts', 'Taekwondo', 14.3);

-- --------------------------------------------------------

--
-- 表的结构 `exercise_tracker`
--

CREATE TABLE `exercise_tracker` (
  `exercise_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exercise_ref_id` int(11) DEFAULT NULL,
  `exercise_type` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `exercise_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exercise_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `exercise_tracker`
--

INSERT INTO `exercise_tracker` (`exercise_id`, `user_id`, `exercise_ref_id`, `exercise_type`, `duration_minutes`, `calories_burned`, `exercise_date`, `created_at`, `updated_at`, `exercise_time`, `notes`) VALUES
(2, 1, 5, 'Bicycling', 60, 515, '2025-08-05', '2025-08-25 16:40:37', '2025-08-25 16:40:37', '17:20:00', ''),
(3, 1, 10, 'Pushups, sit ups, pull-ups, vigorous effort', 60, 551, '2025-08-08', '2025-08-25 16:41:23', '2025-08-25 16:41:23', '09:01:00', ''),
(4, 1, 2, 'Running, 5 mph (12 min/mile)', 24, 250, '2025-08-10', '2025-08-25 16:42:23', '2025-08-25 16:42:23', '18:10:00', ''),
(5, 1, 5, 'Bicycling', 50, 429, '2025-08-13', '2025-08-25 16:43:01', '2025-08-25 16:43:01', '17:14:00', ''),
(6, 1, 16, 'Rope skipping (jump rope)', 40, 539, '2025-08-16', '2025-08-25 16:43:54', '2025-08-25 16:43:54', '09:05:00', ''),
(7, 1, 2, 'Running, 5 mph (12 min/mile)', 36, 375, '2025-08-22', '2025-08-25 16:44:48', '2025-08-25 16:44:48', '08:38:00', '');

-- --------------------------------------------------------

--
-- 表的结构 `money_categories`
--

CREATE TABLE `money_categories` (
  `category_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT 'fas fa-circle',
  `color` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `money_categories`
--

INSERT INTO `money_categories` (`category_id`, `type`, `category_name`, `icon`, `color`, `is_active`) VALUES
(1, 'income', 'Salary', 'fas fa-briefcase', '#28a745', 1),
(2, 'income', 'Freelance', 'fas fa-laptop', '#17a2b8', 1),
(3, 'income', 'Part-time Job', 'fas fa-clock', '#20c997', 1),
(4, 'income', 'Scholarship', 'fas fa-graduation-cap', '#0d6efd', 1),
(5, 'income', 'Gift Money', 'fas fa-gift', '#e83e8c', 1),
(6, 'income', 'Investment', 'fas fa-chart-line', '#fd7e14', 1),
(7, 'income', 'Other Income', 'fas fa-plus-circle', '#6f42c1', 1),
(8, 'expense', 'Food & Dining', 'fas fa-utensils', '#dc3545', 1),
(9, 'expense', 'Transportation', 'fas fa-car', '#fd7e14', 1),
(10, 'expense', 'Shopping', 'fas fa-shopping-bag', '#e83e8c', 1),
(11, 'expense', 'Entertainment', 'fas fa-gamepad', '#6f42c1', 1),
(12, 'expense', 'Bills & Utilities', 'fas fa-file-invoice', '#6c757d', 1),
(13, 'expense', 'Healthcare', 'fas fa-heartbeat', '#20c997', 1),
(14, 'expense', 'Education', 'fas fa-book', '#0d6efd', 1),
(15, 'expense', 'Rent', 'fas fa-home', '#795548', 1),
(16, 'expense', 'Groceries', 'fas fa-shopping-cart', '#ff9800', 1),
(17, 'expense', 'Other Expense', 'fas fa-minus-circle', '#adb5bd', 1);

-- --------------------------------------------------------

--
-- 表的结构 `money_transactions`
--

CREATE TABLE `money_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `money_transactions`
--

INSERT INTO `money_transactions` (`transaction_id`, `user_id`, `type`, `amount`, `category`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'income', 500.00, 'Scholarship', 'From family', '2025-08-02', '2025-08-25 16:29:51', '2025-08-25 16:29:51'),
(2, 1, 'expense', 10.00, 'Food & Dining', 'Lunch', '2025-08-04', '2025-08-25 16:30:08', '2025-08-25 16:30:08'),
(3, 1, 'expense', 10.00, 'Food & Dining', 'Lunch', '2025-08-04', '2025-08-25 16:31:19', '2025-08-25 16:31:19'),
(4, 1, 'expense', 100.00, 'Education', 'Fee', '2025-08-05', '2025-08-25 16:31:41', '2025-08-25 16:31:41'),
(5, 1, 'income', 120.00, 'Part-time Job', 'Promoter', '2025-08-08', '2025-08-25 16:32:32', '2025-08-25 16:32:32'),
(6, 1, 'expense', 30.00, 'Bills & Utilities', 'Mobile Fee', '2025-08-05', '2025-08-25 16:34:09', '2025-08-25 16:34:09'),
(7, 1, 'expense', 300.00, 'Rent', 'Unit', '2025-08-04', '2025-08-25 16:35:10', '2025-08-25 16:35:10');

-- --------------------------------------------------------

--
-- 表的结构 `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category_id` int(11) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'jhteoh20', 'jhteoh20@1utar.my', '$2y$10$7ugJCsVKS2Tf6YAqKzzTxOzsLhnzFpsr2bVzA9PmWVFnu6OtiBDRu', '2025-08-25 16:18:31');

-- --------------------------------------------------------

--
-- 表的结构 `user_remember_tokens`
--

CREATE TABLE `user_remember_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used` timestamp NULL DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- 表的索引 `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- 表的索引 `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_error_type` (`error_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_errors` (`user_id`);

--
-- 表的索引 `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  ADD PRIMARY KEY (`exercise_id`),
  ADD KEY `idx_user_date` (`user_id`,`exercise_date`),
  ADD KEY `idx_exercise_type` (`exercise_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_exercise_ref` (`exercise_ref_id`);

--
-- 表的索引 `money_categories`
--
ALTER TABLE `money_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `idx_type_category` (`type`);

--
-- 表的索引 `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_user_transactions` (`user_id`,`transaction_date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category`);

--
-- 表的索引 `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_user_reset` (`user_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_email` (`email`);

--
-- 表的索引 `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_public` (`is_public`),
  ADD KEY `system_settings_ibfk_1` (`updated_by`);

--
-- 表的索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_user_tokens` (`user_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- 使用表AUTO_INCREMENT `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  MODIFY `exercise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `money_categories`
--
ALTER TABLE `money_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `money_transactions`
--
ALTER TABLE `money_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE CASCADE;

--
-- 限制表 `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE CASCADE;

--
-- 限制表 `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD CONSTRAINT `diary_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- 限制表 `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  ADD CONSTRAINT `exercise_tracker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exercise_ref` FOREIGN KEY (`exercise_ref_id`) REFERENCES `exercises` (`id`);

--
-- 限制表 `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD CONSTRAINT `money_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- 限制表 `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- 限制表 `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD CONSTRAINT `user_remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
