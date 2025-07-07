<?php

require_once __DIR__ . '/load_env.php';

$envLoaded = loadEnv(__DIR__ . '/../.env');

if (!$envLoaded) {
    error_log("WARNING: .env file could not be loaded!");
}

return [
    'evolutionAPI' => [
        'enabled' => getenv('EVOLUTION_API_ENABLED'),  
        'api_url' => getenv('EVOLUTION_API_URL'),
        'api_key' => getenv('EVOLUTION_API_KEY'),
    ],

    'testing' => [
        'enabled' => getenv('TEST_EVOLUTION_API_ENABLED'),
        'instance' => getenv('TEST_EVOLUTION_API_INSTANCE'),
        'phone_number' => getenv('TEST_EVOLUTION_API_NUMBER'),
    ],
    
    'openai' => [
        'enabled' => getenv('OPENAI_API_ENABLED'),
        'api_url' => getenv('OPENAI_API_URL'),
        'api_key' => getenv('OPENAI_API_KEY'),
        'model' => getenv('OPENAI_MODEL'),
        'max_tokens' => (int)getenv('OPENAI_MAX_TOKENS'),
        'temperature' => (float)getenv('OPENAI_TEMPERATURE'),
    ],
    
    'database' => [
        'host' => getenv('DB_HOST'),
        'port' => (int)getenv('DB_PORT'),
        'database' => getenv('DB_DATABASE'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
    ],
    
    'app' => [
        'name' => getenv('SYSTEM_NAME'),
        'version' => getenv('SYSTEM_VERSION'),
        'debug' => getenv('SYSTEM_DEBUG') === 'true',
    ]
];