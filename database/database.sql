-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jan 25, 2025 at 08:00 AM
-- Server version: 8.0.42
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `simple_php`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `created_at`, `updated_at`) VALUES
(1, 'demo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', '2025-01-25 08:00:00', '2025-01-25 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'New Conversation',
  `agent_id` int DEFAULT NULL COMMENT 'Optional agent assignment',
  `messages` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of messages',
  `message_count` int DEFAULT '0' COMMENT 'Cached message count for performance',
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of last message',
  `status` enum('active','archived','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`id`, `user_id`, `title`, `agent_id`, `messages`, `message_count`, `last_message_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Welcome to OpenAI Webchat', NULL, '[\n  {\n    \"role\": \"system\",\n    \"content\": \"You are a helpful AI assistant.\",\n    \"timestamp\": \"2025-01-25T08:00:00Z\"\n  },\n  {\n    \"role\": \"assistant\", \n    \"content\": \"Hello! Welcome to OpenAI Webchat. I am your AI assistant. How can I help you today?\",\n    \"timestamp\": \"2025-01-25T08:00:01Z\"\n  }\n]', 2, '2025-01-25 08:00:01', 'active', '2025-01-25 08:00:00', '2025-01-25 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'System prompt/personality',
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'gpt-4o-mini' COMMENT 'OpenAI model to use',
  `tools` json DEFAULT NULL COMMENT 'Array of available tool names',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Whether agent is active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `user_id`, `name`, `instructions`, `model`, `tools`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Code Assistant', 'You are a helpful programming assistant. You specialize in PHP, JavaScript, and web development. Provide clear, well-commented code examples and explain programming concepts thoroughly.', 'gpt-4o-mini', '[\"Math\"]', 1, '2025-01-25 08:00:00', '2025-01-25 08:00:00'),
(2, 1, 'Research Helper', 'You are a research assistant who helps gather information and analyze data. You can search the web and perform calculations to provide comprehensive answers.', 'gpt-4o-mini', '[\"Search\", \"Math\"]', 1, '2025-01-25 08:00:00', '2025-01-25 08:00:00'),
(3, 1, 'Weather Assistant', 'You are a helpful weather assistant. You can provide current weather information for any location and help with weather-related planning.', 'gpt-4o-mini', '[\"Weather\"]', 1, '2025-01-25 08:00:00', '2025-01-25 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `runs`
--

CREATE TABLE `runs` (
  `id` int NOT NULL,
  `thread_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `status` enum('queued','in_progress','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'queued',
  `input_message` text COLLATE utf8mb4_unicode_ci COMMENT 'User input message',
  `output_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Agent response',
  `token_usage` json DEFAULT NULL COMMENT 'OpenAI token usage statistics',
  `tools_used` json DEFAULT NULL COMMENT 'Array of tools that were executed',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional execution details',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_user_timestamp` (`user_id`,`last_message_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_threads_user_status` (`user_id`,`status`),
  ADD KEY `idx_threads_message_count` (`message_count`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_agents_model` (`model`);

--
-- Indexes for table `runs`
--
ALTER TABLE `runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_thread_id` (`thread_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_thread_status` (`thread_id`,`status`),
  ADD KEY `idx_agent_status` (`agent_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_runs_status_created` (`status`,`created_at`),
  ADD KEY `idx_runs_completed_at` (`completed_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_threads_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_threads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `fk_agents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `runs`
--
ALTER TABLE `runs`
  ADD CONSTRAINT `fk_runs_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_runs_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

COMMIT;