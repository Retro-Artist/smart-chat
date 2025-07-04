<?php

/**
 * Status Checker Class
 * 
 * Handles all status validation for APIs, database connections,
 * and configuration checking for the application.
 */

class Status {

    /**
     * Test OpenAI API connectivity and authentication
     * 
     * @param array $config Configuration array
     * @return array Status result with success/error information
     */
    public static function testOpenAIAPI($config) {
        if (!$config['openai']['enabled'] || empty($config['openai']['api_key']) || empty($config['openai']['api_url'])) {
            return ['status' => 'disabled', 'message' => 'API disabled or missing credentials'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $config['openai']['api_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $config['openai']['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $config['openai']['model'] ?: 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'test']],
                'max_tokens' => 5
            ]),
            CURLOPT_POST => true,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['status' => 'error', 'message' => 'Connection failed: ' . $error];
        }

        if ($httpCode === 200) {
            return ['status' => 'success', 'message' => 'API working correctly'];
        } elseif ($httpCode === 401) {
            return ['status' => 'error', 'message' => 'Invalid API key'];
        } elseif ($httpCode === 429) {
            return ['status' => 'warning', 'message' => 'Rate limited (API key valid)'];
        } else {
            return ['status' => 'error', 'message' => "HTTP $httpCode: " . substr($response, 0, 100)];
        }
    }

    /**
     * Test Evolution API connectivity and authentication
     * 
     * @param array $config Configuration array
     * @return array Status result with success/error information
     */
    public static function testEvolutionAPI($config) {
        if (!$config['evolutionAPI']['enabled'] || empty($config['evolutionAPI']['api_key']) || empty($config['evolutionAPI']['api_url'])) {
            return ['status' => 'disabled', 'message' => 'API disabled or missing credentials'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($config['evolutionAPI']['api_url'], '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $config['evolutionAPI']['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['status' => 'error', 'message' => 'Connection failed: ' . $error];
        }

        if ($httpCode === 200) {
            return ['status' => 'success', 'message' => 'API working correctly'];
        } elseif ($httpCode === 401 || $httpCode === 403) {
            return ['status' => 'error', 'message' => 'Invalid API key'];
        } else {
            return ['status' => 'error', 'message' => "HTTP $httpCode: " . substr($response, 0, 100)];
        }
    }

    /**
     * Setup and test database connection
     * 
     * @param array $config Configuration array
     * @return array Database connection result and information
     */
    public static function setupDatabaseConnection($config) {
        $result = [
            'connection' => null,
            'connected' => false,
            'error' => null,
            'notes' => [],
            'tableExists' => false,
            'mysqlVersion' => null,
            'dbname' => $config['database']['database'],
            'config_loaded' => true
        ];

        try {
            // Try to connect (max 3 attempts)
            for ($i = 0; $i < 3; $i++) {
                try {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                        $config['database']['host'],
                        $config['database']['port'],
                        $config['database']['database']
                    );
                    
                    $result['connection'] = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    $result['connected'] = true;

                    // Check for notes table and fetch data
                    if ($result['connection']->query("SHOW TABLES LIKE 'notes'")->rowCount() > 0) {
                        $result['tableExists'] = true;
                        $result['notes'] = $result['connection']->query("SELECT * FROM notes ORDER BY created_at DESC LIMIT 5")
                            ->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Get MySQL version
                    $result['mysqlVersion'] = $result['connection']->query('SELECT version()')->fetchColumn();
                    break;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Unknown database') !== false) {
                        $result['error'] = "Database \"{$config['database']['database']}\" does not exist yet!";
                        break;
                    }

                    if ($i === 2) throw $e; // Last attempt failed
                    sleep(1); // Wait before retry
                }
            }
        } catch (PDOException $e) {
            $result['error'] = 'Database connection failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if configuration section has meaningful values
     * 
     * @param array $section Configuration section to validate
     * @return bool True if section has valid values
     */
    public static function hasValidConfig($section) {
        if (!is_array($section)) return false;
        
        foreach ($section as $key => $value) {
            // Skip boolean values and check for meaningful string/numeric values
            if (is_bool($value)) continue;
            if (is_string($value) && !empty(trim($value))) return true;
            if (is_numeric($value) && $value > 0) return true;
        }
        return false;
    }

    /**
     * Check overall configuration status
     * 
     * @param array $config Full configuration array
     * @return array Configuration status information
     */
    public static function checkConfigurationStatus($config) {
        return [
            'env_loaded' => file_exists(__DIR__ . '/../../.env'),
            'config_accessible' => is_array($config),
            'required_keys' => [
                'database' => isset($config['database']) && self::hasValidConfig($config['database']),
                'app' => isset($config['app']) && self::hasValidConfig($config['app']),
                'evolutionAPI' => isset($config['evolutionAPI']) && self::hasValidConfig($config['evolutionAPI']),
                'openai' => isset($config['openai']) && self::hasValidConfig($config['openai'])
            ]
        ];
    }
}