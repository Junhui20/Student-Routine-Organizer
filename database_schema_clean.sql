-- Clean Database Schema for Diary Journal (No Attachments)
-- This is the updated schema after removing all upload/attachment functionality

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_routine_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
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

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
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

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `exercise_name` varchar(100) DEFAULT NULL,
  `met_value` decimal(4,1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exercises`
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
-- Table structure for table `exercise_tracker`
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

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_remember_tokens`
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
-- Indexes for dumped tables
--

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_error_type` (`error_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_errors` (`user_id`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  ADD PRIMARY KEY (`exercise_id`),
  ADD KEY `idx_user_date` (`user_id`,`exercise_date`),
  ADD KEY `idx_exercise_type` (`exercise_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_exercise_ref` (`exercise_ref_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_user_reset` (`user_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_user_tokens` (`user_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  MODIFY `exercise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD CONSTRAINT `diary_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `exercise_tracker`
--
ALTER TABLE `exercise_tracker`
  ADD CONSTRAINT `exercise_tracker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exercise_ref` FOREIGN KEY (`exercise_ref_id`) REFERENCES `exercises` (`id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD CONSTRAINT `user_remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;