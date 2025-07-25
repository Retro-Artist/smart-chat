<?php

require_once __DIR__ . '/load_env.php';

$envLoaded = loadEnv(__DIR__ . '/../.env');

if (!$envLoaded) {
    error_log("WARNING: .env file could not be loaded!");
}

// Evolution API Configuration
define('EVOLUTION_API_ENABLED', getenv('EVOLUTION_API_ENABLED'));
define('EVOLUTION_API_URL', getenv('EVOLUTION_API_URL'));
define('EVOLUTION_API_KEY', getenv('EVOLUTION_API_KEY'));

// Testing Configuration
define('TEST_EVOLUTION_API_ENABLED', getenv('TEST_EVOLUTION_API_ENABLED'));
define('TEST_EVOLUTION_API_INSTANCE', getenv('TEST_EVOLUTION_API_INSTANCE'));
define('TEST_EVOLUTION_API_NUMBER', getenv('TEST_EVOLUTION_API_NUMBER'));

// OpenAI Configuration
define('OPENAI_API_ENABLED', getenv('OPENAI_API_ENABLED'));
define('OPENAI_API_URL', getenv('OPENAI_API_URL'));
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
define('OPENAI_MODEL', getenv('OPENAI_MODEL'));
define('OPENAI_MAX_TOKENS', (int)getenv('OPENAI_MAX_TOKENS'));
define('OPENAI_TEMPERATURE', (float)getenv('OPENAI_TEMPERATURE'));

// Database Configuration
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', (int)getenv('DB_PORT'));
define('DB_DATABASE', getenv('DB_DATABASE'));
define('DB_USERNAME', getenv('DB_USERNAME'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));

// Application Configuration
define('SYSTEM_NAME', getenv('SYSTEM_NAME'));
define('SYSTEM_VERSION', getenv('SYSTEM_VERSION'));
define('SYSTEM_DEBUG', getenv('SYSTEM_DEBUG') === 'true');

// WhatsApp Configuration
define('WHATSAPP_ENABLED', getenv('WHATSAPP_ENABLED') === 'true');
define('WHATSAPP_WEBHOOK_URL', getenv('WHATSAPP_WEBHOOK_URL'));
define('WHATSAPP_DEFAULT_AGENT_ID', (int)getenv('WHATSAPP_DEFAULT_AGENT_ID'));
define('WHATSAPP_AUTO_CREATE_INSTANCE', getenv('WHATSAPP_AUTO_CREATE_INSTANCE') === 'true');
define('WHATSAPP_SYNC_HISTORY_DAYS', (int)getenv('WHATSAPP_SYNC_HISTORY_DAYS'));
define('WHATSAPP_MAX_MESSAGE_LENGTH', (int)getenv('WHATSAPP_MAX_MESSAGE_LENGTH'));
define('WHATSAPP_MEDIA_UPLOAD_PATH', getenv('WHATSAPP_MEDIA_UPLOAD_PATH'));

// Redis Configuration
define('REDIS_HOST', getenv('REDIS_HOST'));
define('REDIS_PORT', (int)getenv('REDIS_PORT'));
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD'));
define('REDIS_DATABASE', (int)getenv('REDIS_DATABASE'));
define('REDIS_PREFIX', getenv('REDIS_PREFIX'));

// Queue Configuration
define('QUEUE_DEFAULT_CONNECTION', getenv('QUEUE_DEFAULT_CONNECTION'));
define('QUEUE_HIGH_PRIORITY', getenv('QUEUE_HIGH_PRIORITY'));
define('QUEUE_NORMAL_PRIORITY', getenv('QUEUE_NORMAL_PRIORITY'));
define('QUEUE_LOW_PRIORITY', getenv('QUEUE_LOW_PRIORITY'));
define('QUEUE_RETRY_ATTEMPTS', (int)getenv('QUEUE_RETRY_ATTEMPTS'));
define('QUEUE_RETRY_DELAY', (int)getenv('QUEUE_RETRY_DELAY'));

// Cache Configuration
define('CACHE_TTL_CONTACTS', (int)getenv('CACHE_TTL_CONTACTS'));
define('CACHE_TTL_MESSAGES', (int)getenv('CACHE_TTL_MESSAGES'));
define('CACHE_TTL_INSTANCES', (int)getenv('CACHE_TTL_INSTANCES'));
define('CACHE_TTL_QR_CODE', (int)getenv('CACHE_TTL_QR_CODE'));

// Webhook Configuration
define('WEBHOOK_SIGNATURE_SECRET', getenv('WEBHOOK_SIGNATURE_SECRET'));
define('WEBHOOK_TIMEOUT', (int)getenv('WEBHOOK_TIMEOUT'));
define('WEBHOOK_ENABLED_EVENTS', getenv('WEBHOOK_ENABLED_EVENTS'));