-- Migration: Add redis_fallback table for Redis cache fallback storage
-- This table replaces the file-based fallback system in temp/redis_fallback/

CREATE TABLE IF NOT EXISTS `redis_fallback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'NULL means no expiry',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_cache_key_expires` (`cache_key`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Redis cache fallback storage when Redis is unavailable';