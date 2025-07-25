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