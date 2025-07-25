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
     * @return array Status result with success/error information
     */
    public static function testOpenAIAPI() {
        require_once __DIR__ . '/../../config/config.php';
        
        if (!OPENAI_API_ENABLED || empty(OPENAI_API_KEY) || empty(OPENAI_API_URL)) {
            return ['status' => 'disabled', 'message' => 'API disabled or missing credentials'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => OPENAI_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . OPENAI_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => OPENAI_MODEL ?: 'gpt-3.5-turbo',
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
     * @return array Status result with success/error information
     */
    public static function testEvolutionAPI() {
        require_once __DIR__ . '/../../config/config.php';
        
        if (!EVOLUTION_API_ENABLED || empty(EVOLUTION_API_KEY) || empty(EVOLUTION_API_URL)) {
            return ['status' => 'disabled', 'message' => 'API disabled or missing credentials'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim(EVOLUTION_API_URL, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . EVOLUTION_API_KEY,
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
     * @return array Database connection result and information
     */
    public static function setupDatabaseConnection() {
        require_once __DIR__ . '/../../config/config.php';
        
        $result = [
            'connection' => null,
            'connected' => false,
            'error' => null,
            'notes' => [],
            'tableExists' => false,
            'mysqlVersion' => null,
            'dbname' => DB_DATABASE,
            'config_loaded' => true
        ];

        try {
            // Try to connect (max 3 attempts)
            for ($i = 0; $i < 3; $i++) {
                try {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                        DB_HOST,
                        DB_PORT,
                        DB_DATABASE
                    );
                    
                    $result['connection'] = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
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
                        $result['error'] = "Database \"" . DB_DATABASE . "\" does not exist yet!";
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
     * Check overall configuration status
     * 
     * @return array Configuration status information
     */
    public static function checkConfigurationStatus() {
        require_once __DIR__ . '/../../config/config.php';
        
        return [
            'env_loaded' => file_exists(__DIR__ . '/../../.env'),
            'config_accessible' => defined('SYSTEM_NAME'),
            'required_keys' => [
                'database' => defined('DB_HOST') && !empty(DB_HOST),
                'app' => defined('SYSTEM_NAME') && !empty(SYSTEM_NAME),
                'evolutionAPI' => defined('EVOLUTION_API_URL') && !empty(EVOLUTION_API_URL),
                'openai' => defined('OPENAI_API_URL') && !empty(OPENAI_API_URL)
            ]
        ];
    }
}