-- WhatsApp Integration Migration
-- This migration adds all necessary tables for Evolution API v2 integration

-- WhatsApp Instances Table
-- One instance per user for WhatsApp connection
CREATE TABLE `whatsapp_instances` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `instance_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `instance_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `status` enum('creating','connecting','connected','disconnected','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'creating',
    `qr_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `webhook_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `last_sync_at` timestamp NULL DEFAULT NULL,
    `settings` json DEFAULT NULL COMMENT 'Instance settings and configuration',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_instance` (`user_id`),
    UNIQUE KEY `idx_instance_name` (`instance_name`),
    KEY `idx_status` (`status`),
    KEY `idx_phone_number` (`phone_number`),
    CONSTRAINT `fk_whatsapp_instances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp Contacts Table
-- Only verified WhatsApp users to reduce storage
CREATE TABLE `whatsapp_contacts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_whatsapp_user` tinyint(1) DEFAULT '0' COMMENT 'Verified WhatsApp user',
    `is_business` tinyint(1) DEFAULT '0',
    `status` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `last_seen` timestamp NULL DEFAULT NULL,
    `is_blocked` tinyint(1) DEFAULT '0',
    `metadata` json DEFAULT NULL COMMENT 'Additional contact information',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_instance_phone` (`instance_id`, `phone_number`),
    KEY `idx_phone_number` (`phone_number`),
    KEY `idx_is_whatsapp_user` (`is_whatsapp_user`),
    KEY `idx_name` (`name`),
    CONSTRAINT `fk_whatsapp_contacts_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp Messages Table
-- Message content and metadata
CREATE TABLE `whatsapp_messages` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp message ID',
    `from_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `to_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `group_id` int DEFAULT NULL,
    `message_type` enum('text','image','audio','video','document','location','contact','sticker') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'text',
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `media_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_instance_msg_id` (`instance_id`, `message_id`),
    KEY `idx_from_phone` (`from_phone`),
    KEY `idx_to_phone` (`to_phone`),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_status` (`status`),
    KEY `idx_message_type` (`message_type`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_conversation` (`instance_id`, `from_phone`, `to_phone`, `timestamp`),
    CONSTRAINT `fk_whatsapp_messages_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message Queue Table
-- Redis backup for reliable message processing
CREATE TABLE `message_queue` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `queue_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `priority` enum('high','normal','low') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
    `payload` json NOT NULL COMMENT 'Message or task data',
    `status` enum('pending','processing','completed','failed','retry') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
    `attempts` int DEFAULT '0',
    `max_attempts` int DEFAULT '3',
    `scheduled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` timestamp NULL DEFAULT NULL,
    `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_instance_queue` (`instance_id`, `queue_name`),
    KEY `idx_status_priority` (`status`, `priority`, `scheduled_at`),
    KEY `idx_scheduled_at` (`scheduled_at`),
    CONSTRAINT `fk_message_queue_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp Groups Table
-- Group chat information
CREATE TABLE `whatsapp_groups` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `group_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp group ID',
    `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `profile_picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `owner_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `participants` json DEFAULT NULL COMMENT 'Array of participant phone numbers',
    `is_admin` tinyint(1) DEFAULT '0' COMMENT 'Is the instance admin of this group',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_instance_group` (`instance_id`, `group_id`),
    KEY `idx_name` (`name`),
    KEY `idx_owner_phone` (`owner_phone`),
    CONSTRAINT `fk_whatsapp_groups_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp Sync Log Table
-- Audit trail for data synchronization
CREATE TABLE `whatsapp_sync_log` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `sync_type` enum('contacts','messages','groups','full') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `status` enum('started','completed','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'started',
    `records_processed` int DEFAULT '0',
    `records_created` int DEFAULT '0',
    `records_updated` int DEFAULT '0',
    `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` timestamp NULL DEFAULT NULL,
    `metadata` json DEFAULT NULL COMMENT 'Additional sync information',
    PRIMARY KEY (`id`),
    KEY `idx_instance_type` (`instance_id`, `sync_type`),
    KEY `idx_status` (`status`),
    KEY `idx_started_at` (`started_at`),
    CONSTRAINT `fk_whatsapp_sync_log_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp AI Threads Table
-- Links WhatsApp conversations to AI agent threads
CREATE TABLE `whatsapp_threads` (
    `id` int NOT NULL AUTO_INCREMENT,
    `instance_id` int NOT NULL,
    `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `thread_id` int NOT NULL COMMENT 'Links to existing threads table',
    `agent_id` int DEFAULT NULL COMMENT 'AI agent handling this conversation',
    `is_active` tinyint(1) DEFAULT '1',
    `last_activity_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_instance_contact` (`instance_id`, `contact_phone`),
    KEY `idx_thread_id` (`thread_id`),
    KEY `idx_agent_id` (`agent_id`),
    KEY `idx_last_activity` (`last_activity_at`),
    CONSTRAINT `fk_whatsapp_threads_instance` FOREIGN KEY (`instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_whatsapp_threads_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_whatsapp_threads_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint for messages to groups after all tables are created
ALTER TABLE `whatsapp_messages`
ADD CONSTRAINT `fk_whatsapp_messages_group` FOREIGN KEY (`group_id`) REFERENCES `whatsapp_groups` (`id`) ON DELETE SET NULL;