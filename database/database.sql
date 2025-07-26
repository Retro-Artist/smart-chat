-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Tempo de geração: 26/07/2025 às 07:37
-- Versão do servidor: 8.0.43
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `message_queue`
--

CREATE TABLE `message_queue` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `queue_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('high','normal','low') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `payload` json NOT NULL COMMENT 'Message or task data',
  `status` enum('pending','processing','completed','failed','retry') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `scheduled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `redis_fallback`
--

CREATE TABLE `redis_fallback` (
  `id` int NOT NULL,
  `cache_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `messages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of messages',
  `message_count` int DEFAULT '0' COMMENT 'Cached message count for performance',
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of last message',
  `status` enum('active','archived','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_jid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_contacts`
--

CREATE TABLE `whatsapp_contacts` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_whatsapp_user` tinyint(1) DEFAULT '0' COMMENT 'Verified WhatsApp user',
  `is_business` tinyint(1) DEFAULT '0',
  `status` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT '0',
  `metadata` json DEFAULT NULL COMMENT 'Additional contact information',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_groups`
--

CREATE TABLE `whatsapp_groups` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `group_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp group ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `owner_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants` json DEFAULT NULL COMMENT 'Array of participant phone numbers',
  `is_admin` tinyint(1) DEFAULT '0' COMMENT 'Is the instance admin of this group',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_instances`
--

CREATE TABLE `whatsapp_instances` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `instance_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instance_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Evolution API instance token for webhook authentication',
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('creating','connecting','connected','disconnected','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'creating',
  `qr_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `webhook_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `settings` json DEFAULT NULL COMMENT 'Instance settings and configuration',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_messages`
--

CREATE TABLE `whatsapp_messages` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp message ID',
  `from_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` int DEFAULT NULL,
  `message_type` enum('text','image','audio','video','document','location','contact','sticker') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `media_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `media_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_mimetype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_size` int DEFAULT NULL,
  `is_from_me` tinyint(1) DEFAULT '0',
  `status` enum('pending','sent','delivered','read','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `timestamp` timestamp NOT NULL,
  `reply_to_message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forwarded` tinyint(1) DEFAULT '0',
  `quoted_message` json DEFAULT NULL COMMENT 'Quoted message data if reply',
  `metadata` json DEFAULT NULL COMMENT 'Additional message metadata',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_sync_log`
--

CREATE TABLE `whatsapp_sync_log` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `sync_type` enum('contacts','messages','groups','full') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('started','completed','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'started',
  `records_processed` int DEFAULT '0',
  `records_created` int DEFAULT '0',
  `records_updated` int DEFAULT '0',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional sync information'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_threads`
--

CREATE TABLE `whatsapp_threads` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thread_id` int NOT NULL COMMENT 'Links to existing threads table',
  `agent_id` int DEFAULT NULL COMMENT 'AI agent handling this conversation',
  `is_active` tinyint(1) DEFAULT '1',
  `last_activity_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Índices de tabela `message_queue`
--
ALTER TABLE `message_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instance_queue` (`instance_id`,`queue_name`),
  ADD KEY `idx_status_priority` (`status`,`priority`,`scheduled_at`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`);

--
-- Índices de tabela `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `redis_fallback`
--
ALTER TABLE `redis_fallback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_cache_key_expires` (`cache_key`,`expires_at`);

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
  ADD KEY `idx_threads_message_count` (`message_count`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_users_owner_jid` (`owner_jid`);

--
-- Índices de tabela `whatsapp_contacts`
--
ALTER TABLE `whatsapp_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_instance_phone` (`instance_id`,`phone_number`),
  ADD KEY `idx_phone_number` (`phone_number`),
  ADD KEY `idx_is_whatsapp_user` (`is_whatsapp_user`),
  ADD KEY `idx_name` (`name`);

--
-- Índices de tabela `whatsapp_groups`
--
ALTER TABLE `whatsapp_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_instance_group` (`instance_id`,`group_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_owner_phone` (`owner_phone`);

--
-- Índices de tabela `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_instance` (`user_id`),
  ADD UNIQUE KEY `idx_instance_name` (`instance_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_phone_number` (`phone_number`),
  ADD KEY `idx_whatsapp_instances_token` (`token`),
  ADD KEY `idx_whatsapp_instances_name` (`instance_name`);

--
-- Índices de tabela `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_instance_msg_id` (`instance_id`,`message_id`),
  ADD KEY `idx_from_phone` (`from_phone`),
  ADD KEY `idx_to_phone` (`to_phone`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_conversation` (`instance_id`,`from_phone`,`to_phone`,`timestamp`);

--
-- Índices de tabela `whatsapp_sync_log`
--
ALTER TABLE `whatsapp_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instance_type` (`instance_id`,`sync_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_started_at` (`started_at`);

--
-- Índices de tabela `whatsapp_threads`
--
ALTER TABLE `whatsapp_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_instance_contact` (`instance_id`,`contact_phone`),
  ADD KEY `idx_thread_id` (`thread_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_last_activity` (`last_activity_at`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `message_queue`
--
ALTER TABLE `message_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `redis_fallback`
--
ALTER TABLE `redis_fallback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_contacts`
--
ALTER TABLE `whatsapp_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_groups`
--
ALTER TABLE `whatsapp_groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_sync_log`
--
ALTER TABLE `whatsapp_sync_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_threads`
--
ALTER TABLE `whatsapp_threads`
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
-- Restrições para tabelas `message_queue`
--
ALTER TABLE `message_queue`
  ADD CONSTRAINT `fk_message_queue_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_threads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_contacts`
--
ALTER TABLE `whatsapp_contacts`
  ADD CONSTRAINT `fk_whatsapp_contacts_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_groups`
--
ALTER TABLE `whatsapp_groups`
  ADD CONSTRAINT `fk_whatsapp_groups_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_instances`
--
ALTER TABLE `whatsapp_instances`
  ADD CONSTRAINT `fk_whatsapp_instances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD CONSTRAINT `fk_whatsapp_messages_group` FOREIGN KEY (`group_id`) REFERENCES `whatsapp_groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_whatsapp_messages_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_sync_log`
--
ALTER TABLE `whatsapp_sync_log`
  ADD CONSTRAINT `fk_whatsapp_sync_log_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_threads`
--
ALTER TABLE `whatsapp_threads`
  ADD CONSTRAINT `fk_whatsapp_threads_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_whatsapp_threads_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_whatsapp_threads_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
