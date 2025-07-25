<?php
// src/Api/SystemAPI.php - REFACTORED for single responsibility

require_once __DIR__ . '/../../Core/Helpers.php';

class SystemAPI {
    private $apiKey;
    private $model;
    private $maxTokens;
    private $temperature;
    
    public function __construct() {
        $this->loadConfig();
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
    }
    
    private function loadConfig() {
        // Environment variables first
        $this->apiKey = getenv('OPENAI_API_KEY') ?: '';
        $this->model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
        $this->maxTokens = (int)(getenv('OPENAI_MAX_TOKENS') ?: 1024);
        $this->temperature = (float)(getenv('OPENAI_TEMPERATURE') ?: 0.7);
        
        // Fallback to config file if needed
        if (empty($this->apiKey)) {
            try {
                if (file_exists(__DIR__ . '/../../config/config.php')) {
                    require_once __DIR__ . '/../../config/config.php';
                    $this->apiKey = OPENAI_API_KEY ?? '';
                    $this->model = OPENAI_MODEL ?? $this->model;
                    $this->maxTokens = OPENAI_MAX_TOKENS ?? $this->maxTokens;
                    $this->temperature = OPENAI_TEMPERATURE ?? $this->temperature;
                }
            } catch (Exception $e) {
                error_log("Config file loading error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * SINGLE METHOD FOR ALL OPENAI CALLS
     * This is the only method that should talk to OpenAI
     */
    public function callOpenAI($payload) {
        // Validate payload has required fields
        if (!isset($payload['messages']) || !is_array($payload['messages'])) {
            throw new Exception('Invalid payload: messages array required');
        }
        
        // Set defaults while preserving custom values
        $payload = array_merge([
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream' => false
        ], $payload);
        
        return $this->makeOpenAICall($payload);
    }
    
    /**
     * CONVENIENCE METHOD FOR SIMPLE CHAT
     * Used by ThreadsAPI for basic conversations
     */
    public function simpleChat($userMessage, $conversationHistory = [], $systemMessage = null) {
        // Build messages array
        $messages = [];
        
        // Add system message if provided
        if ($systemMessage) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        } else {
            $messages[] = [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant. Provide clear, helpful, and concise responses.'
            ];
        }
        
        // Add conversation history (last 10 messages to keep it reasonable)
        $recentHistory = array_slice($conversationHistory, -10);
        foreach ($recentHistory as $msg) {
            if ($msg['role'] !== 'system') {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        
        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];
        
        // Call OpenAI using the single method
        $response = $this->callOpenAI(['messages' => $messages]);
        
        // Extract and return response content
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        } else {
            throw new Exception('Invalid response from OpenAI API');
        }
    }
    
    /**
     * CONVENIENCE METHOD FOR AGENT EXECUTION
     * Used by Agent Model for tool-enabled conversations
     */
    public function agentChat($messages, $tools = null, $customModel = null, $customTemperature = null) {
        $payload = [
            'messages' => $messages,
            'model' => $customModel ?: $this->model,
            'temperature' => $customTemperature ?: $this->temperature,
            'max_tokens' => $this->maxTokens
        ];
        
        // Add tools if provided
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }
        
        return $this->callOpenAI($payload);
    }
    
    private function makeOpenAICall($payload) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Handle curl errors
        if ($response === false) {
            throw new Exception('cURL error: ' . $curlError);
        }
        
        // Parse JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }
        
        // Handle HTTP errors
        if ($httpCode !== 200) {
            $errorMessage = isset($decoded['error']['message']) 
                ? $decoded['error']['message'] 
                : "HTTP error: $httpCode";
            throw new Exception("OpenAI API error: " . $errorMessage);
        }
        
        return $decoded;
    }
    
    // System status methods
    public function getStatus() {
        Helpers::requireAuth();
        
        try {
            $status = [
                'system' => 'OpenAI Webchat',
                'version' => '1.0.0',
                'status' => 'operational',
                'timestamp' => date('c'),
                'database' => $this->checkDatabaseStatus(),
                'openai' => $this->checkOpenAIStatus()
            ];
            
            Helpers::jsonResponse($status);
        } catch (Exception $e) {
            error_log("Error getting system status: " . $e->getMessage());
            Helpers::jsonError('Failed to get system status', 500);
        }
    }
    
    public function getConfig() {
        Helpers::requireAuth();
        
        try {
            $config = [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'features' => [
                    'agents' => true,
                    'tools' => true,
                    'threads' => true
                ]
            ];
            
            Helpers::jsonResponse($config);
        } catch (Exception $e) {
            error_log("Error getting config: " . $e->getMessage());
            Helpers::jsonError('Failed to get configuration', 500);
        }
    }
    
    private function checkDatabaseStatus() {
        try {
            require_once __DIR__ . '/../Core/Database.php';
            $db = Database::getInstance();
            $result = $db->fetch("SELECT 1 as test");
            return $result ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            error_log('Database status check failed: ' . $e->getMessage());
            return 'error';
        }
    }
    
    private function checkOpenAIStatus() {
        try {
            $payload = [
                'messages' => [['role' => 'user', 'content' => 'Test']],
                'max_tokens' => 5
            ];
            
            $response = $this->callOpenAI($payload);
            return isset($response['choices']) ? 'connected' : 'error';
        } catch (Exception $e) {
            error_log('OpenAI status check failed: ' . $e->getMessage());
            return 'error';
        }
    }
}