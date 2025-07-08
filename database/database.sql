-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Tempo de geração: 08/07/2025 às 17:31
-- Versão do servidor: 8.0.42
-- Versão do PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `smartchat`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agents`
--

CREATE TABLE `agents` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'System prompt/personality',
  `model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'gpt-4o-mini' COMMENT 'OpenAI model to use',
  `tools` json DEFAULT NULL COMMENT 'Array of available tool names',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Whether agent is active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `agents`
--

INSERT INTO `agents` (`id`, `user_id`, `name`, `instructions`, `model`, `tools`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Code Assistant', 'You are a helpful programming assistant. You specialize in PHP, JavaScript, and web development. Provide clear, well-commented code examples and explain programming concepts thoroughly.', 'gpt-4o-mini', '[\"Math\"]', 0, '2025-01-25 08:00:00', '2025-07-08 17:28:43'),
(2, 1, 'Research Helper', 'You are a research assistant who helps gather information and analyze data. You can search the web and perform calculations to provide comprehensive answers.', 'gpt-4o-mini', '[\"Search\", \"Math\"]', 1, '2025-01-25 08:00:00', '2025-01-25 08:00:00'),
(3, 1, 'Weather Assistant', 'You are a helpful weather assistant. You can provide current weather information for any location and help with weather-related planning.', 'gpt-4o-mini', '[\"Weather\"]', 1, '2025-01-25 08:00:00', '2025-01-25 08:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notes`
--

CREATE TABLE `notes` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notes`
--

INSERT INTO `notes` (`id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to Simple PHP Initialization', 'This is a sample note created by the database migration script.', '2025-05-21 13:40:56', '2025-05-21 13:40:56'),
(2, 'Getting Started', 'Edit the public/index.php file to begin building your PHP application.', '2025-05-21 13:40:56', '2025-05-21 13:40:56'),
(3, 'Database Connections', 'Use PDO to connect to MySQL from your PHP scripts.', '2025-05-21 13:40:56', '2025-05-21 13:40:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `runs`
--

CREATE TABLE `runs` (
  `id` int NOT NULL,
  `thread_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `status` enum('queued','in_progress','completed','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'queued',
  `input_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'User input message',
  `output_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Agent response',
  `token_usage` json DEFAULT NULL COMMENT 'OpenAI token usage statistics',
  `tools_used` json DEFAULT NULL COMMENT 'Array of tools that were executed',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional execution details',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `threads`
--

CREATE TABLE `threads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'New Conversation',
  `agent_id` int DEFAULT NULL COMMENT 'Optional agent assignment',
  `whatsapp_contact_jid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_instance_id` int DEFAULT NULL,
  `is_whatsapp_thread` tinyint(1) DEFAULT '0',
  `contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_avatar` text COLLATE utf8mb4_unicode_ci,
  `messages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of messages',
  `message_count` int DEFAULT '0' COMMENT 'Cached message count for performance',
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of last message',
  `status` enum('active','archived','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `threads`
--

INSERT INTO `threads` (`id`, `user_id`, `title`, `agent_id`, `whatsapp_contact_jid`, `whatsapp_instance_id`, `is_whatsapp_thread`, `contact_name`, `contact_phone`, `contact_avatar`, `messages`, `message_count`, `last_message_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Welcome to OpenAI Webchat', NULL, '5511999999999@s.whatsapp.net', 1, 1, 'Demo WhatsApp Contact', '+55 11 99999-9999', NULL, '[\n  {\n    \"role\": \"system\",\n    \"content\": \"You are a helpful AI assistant.\",\n    \"timestamp\": \"2025-01-25T08:00:00Z\"\n  },\n  {\n    \"role\": \"assistant\", \n    \"content\": \"Hello! Welcome to OpenAI Webchat. I am your AI assistant. How can I help you today?\",\n    \"timestamp\": \"2025-01-25T08:00:01Z\"\n  }\n]', 2, '2025-01-25 08:00:01', 'active', '2025-01-25 08:00:00', '2025-07-08 14:12:55'),
(2, 1, 'New Chat', NULL, NULL, NULL, 0, NULL, NULL, NULL, '[{\"role\":\"user\",\"content\":\"hi\",\"timestamp\":\"2025-07-08T13:03:41+00:00\"},{\"role\":\"assistant\",\"content\":\"Hello! How can I assist you today?\",\"timestamp\":\"2025-07-08T13:03:42+00:00\",\"model\":\"gpt-4o-mini-2024-07-18\",\"token_usage\":{\"prompt_tokens\":8,\"completion_tokens\":9,\"total_tokens\":17,\"prompt_tokens_details\":{\"cached_tokens\":0,\"audio_tokens\":0},\"completion_tokens_details\":{\"reasoning_tokens\":0,\"audio_tokens\":0,\"accepted_prediction_tokens\":0,\"rejected_prediction_tokens\":0}}}]', 2, '2025-07-08 13:03:42', 'active', '2025-07-08 13:03:37', '2025-07-08 13:03:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `created_at`, `updated_at`) VALUES
(1, 'demo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', '2025-01-25 08:00:00', '2025-07-08 12:52:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_instances`
--

CREATE TABLE `whatsapp_instances` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `instance_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('disconnected','connecting','connected','error') COLLATE utf8mb4_unicode_ci DEFAULT 'disconnected',
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  `profile_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` text COLLATE utf8mb4_unicode_ci,
  `last_seen` timestamp NULL DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_instances`
--

INSERT INTO `whatsapp_instances` (`id`, `user_id`, `instance_name`, `phone_number`, `status`, `qr_code`, `profile_name`, `profile_picture`, `last_seen`, `settings`, `created_at`, `updated_at`) VALUES
(1, 1, 'demo_instance', NULL, 'disconnected', NULL, NULL, NULL, NULL, '{\"auto_respond\": true, \"greeting_message\": \"Hello! I am your AI assistant.\"}', '2025-07-08 14:12:55', '2025-07-08 14:12:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_message_metadata`
--

CREATE TABLE `whatsapp_message_metadata` (
  `id` int NOT NULL,
  `thread_id` int NOT NULL,
  `whatsapp_message_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jid_from` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jid_to` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','image','video','audio','document','sticker','location') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `media_url` text COLLATE utf8mb4_unicode_ci,
  `media_caption` text COLLATE utf8mb4_unicode_ci,
  `quoted_message_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `status` enum('sent','delivered','read','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'sent',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_agents_model` (`model`);

--
-- Índices de tabela `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `runs`
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
-- Índices de tabela `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_user_timestamp` (`user_id`,`last_message_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_threads_user_status` (`user_id`,`status`),
  ADD KEY `idx_threads_message_count` (`message_count`),
  ADD KEY `idx_whatsapp_contact` (`whatsapp_contact_jid`),
  ADD KEY `idx_whatsapp_instance` (`whatsapp_instance_id`),
  ADD KEY `idx_is_whatsapp` (`is_whatsapp_thread`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `instance_name` (`instance_name`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_instance_name` (`instance_name`);

--
-- Índices de tabela `whatsapp_message_metadata`
--
ALTER TABLE `whatsapp_message_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wa_message` (`whatsapp_message_id`),
  ADD KEY `idx_thread_id` (`thread_id`),
  ADD KEY `idx_whatsapp_message_id` (`whatsapp_message_id`),
  ADD KEY `idx_jid_from` (`jid_from`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `whatsapp_message_metadata`
--
ALTER TABLE `whatsapp_message_metadata`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `fk_agents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `runs`
--
ALTER TABLE `runs`
  ADD CONSTRAINT `fk_runs_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_runs_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_threads_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_threads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_threads_whatsapp_instance` FOREIGN KEY (`whatsapp_instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  ADD CONSTRAINT `whatsapp_instances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_message_metadata`
--
ALTER TABLE `whatsapp_message_metadata`
  ADD CONSTRAINT `whatsapp_message_metadata_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
