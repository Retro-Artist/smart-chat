<?php
// src/Api/Models/Agent.php - Fixed agent editing issue

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../OpenAI/SystemAPI.php';

class Agent
{
    private $id;
    private $name;
    private $instructions;
    private $model;
    private $tools = [];
    private $userId;
    private $isActive;
    private $db;

    public function __construct($name, $instructions, $model = 'gpt-4o-mini')
    {
        $this->name = $name;
        $this->instructions = $instructions;
        $this->model = $model;
        $this->userId = Helpers::getCurrentUserId();
        $this->isActive = true;
        $this->db = Database::getInstance();
    }

    public function addTool($toolClassName)
    {
        $this->tools[] = $toolClassName;
        return $this; // For method chaining
    }

    public function clearTools()
    {
        $this->tools = [];
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function setActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function save()
    {
        if ($this->id) {
            // Update existing agent
            $result = $this->db->update('agents', [
                'name' => $this->name,
                'instructions' => $this->instructions,
                'model' => $this->model,
                'tools' => json_encode($this->tools),
                'is_active' => $this->isActive ? 1 : 0
            ], 'id = ?', [$this->id]);

            error_log("Updated agent {$this->id}: " . ($result ? 'success' : 'failed'));
        } else {
            // Create new agent
            $this->id = $this->db->insert('agents', [
                'name' => $this->name,
                'instructions' => $this->instructions,
                'model' => $this->model,
                'tools' => json_encode($this->tools),
                'user_id' => $this->userId,
                'is_active' => $this->isActive ? 1 : 0
            ]);

            error_log("Created new agent with ID: {$this->id}");
        }

        return $this;
    }

    // FIXED: Modified findById to not filter by is_active for editing purposes
    public static function findById($agentId, $activeOnly = false)
    {
        $db = Database::getInstance();
        
        if ($activeOnly) {
            // When we only want active agents (for execution, etc.)
            $data = $db->fetch("SELECT * FROM agents WHERE id = ? AND is_active = 1", [$agentId]);
        } else {
            // For editing and management purposes, we need to be able to load inactive agents too
            $data = $db->fetch("SELECT * FROM agents WHERE id = ?", [$agentId]);
        }

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    // NEW: Separate method for finding active agents only
    public static function findActiveById($agentId)
    {
        return self::findById($agentId, true);
    }

    public static function getUserAgents($userId)
    {
        $db = Database::getInstance();
        $results = $db->fetchAll("
            SELECT * FROM agents 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at DESC
        ", [$userId]);

        $agents = [];
        foreach ($results as $data) {
            $agents[] = self::fromArray($data);
        }

        return $agents;
    }

    private static function fromArray($data)
    {
        $agent = new self($data['name'], $data['instructions'], $data['model']);
        $agent->id = $data['id'];
        $agent->userId = $data['user_id'];
        $agent->isActive = (bool)$data['is_active'];
        $agent->tools = json_decode($data['tools'] ?? '[]', true);
        return $agent;
    }

    /**
     * EXECUTE AGENT WITH TOOLS - Updated for JSON message system
     * This is where agents differ from simple chat - they can use tools
     */
    public function execute($message, $threadId)
    {
        // Only allow execution of active agents
        if (!$this->isActive) {
            throw new Exception("Cannot execute inactive agent");
        }

        // Create a run for tracking with input message
        $run = $this->createRun($threadId, $message);

        try {
            // Add user message to thread first
            Thread::addMessage($threadId, 'user', $message);

            // Execute the agent with tools
            $executionResult = $this->executeWithTools($message, $threadId);

            // Add agent response to thread with metadata
            $agentMetadata = [
                'agent_id' => $this->id,
                'agent_name' => $this->name,
                'model' => $this->model,
                'tools_available' => $this->tools,
                'run_id' => $run['id']
            ];

            // Add token usage if available
            if (isset($executionResult['token_usage'])) {
                $agentMetadata['token_usage'] = $executionResult['token_usage'];
            }

            Thread::addMessage($threadId, 'assistant', $executionResult['response'], $agentMetadata);

            // Complete the run with full details
            $this->completeRun($run['id'], 'completed', [
                'input_message' => $message,
                'output_message' => $executionResult['response'],
                'token_usage' => $executionResult['token_usage'] ?? null,
                'tools_used' => $executionResult['tools_used'] ?? []
            ]);

            return $executionResult['response'];
        } catch (Exception $e) {
            error_log("Agent execution failed: " . $e->getMessage());

            // Complete the run with error
            $this->completeRun($run['id'], 'failed', [
                'input_message' => $message,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function executeWithTools($message, $threadId)
    {
        // Get OpenAI API key
        $openaiKey = $_ENV['OPENAI_API_KEY'] ?? null;
        if (!$openaiKey) {
            throw new Exception("OpenAI API key not configured");
        }

        // Build messages for context
        $messages = [
            [
                'role' => 'system',
                'content' => $this->instructions
            ],
            [
                'role' => 'user',
                'content' => $message
            ]
        ];

        // Build tools for OpenAI
        $tools = [];
        foreach ($this->tools as $toolClass) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => strtolower($toolClass),
                    'description' => "Execute {$toolClass} tool",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'The query or input for the tool'
                            ]
                        ],
                        'required' => ['query']
                    ]
                ]
            ];
        }

        // Prepare OpenAI request
        $requestData = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];

        if (!empty($tools)) {
            $requestData['tools'] = $tools;
            $requestData['tool_choice'] = 'auto';
        }

        // Make OpenAI API call
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $openaiKey,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("OpenAI API error: HTTP {$httpCode}, Response: {$response}");
            throw new Exception("OpenAI API error: HTTP {$httpCode}");
        }

        $responseData = json_decode($response, true);

        if (!$responseData || !isset($responseData['choices'][0]['message'])) {
            error_log("Invalid OpenAI response: " . $response);
            throw new Exception("Invalid response from OpenAI API");
        }

        $message = $responseData['choices'][0]['message'];
        $toolsUsed = [];

        // Handle tool calls if present
        if (isset($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $toolCall) {
                $toolName = $toolCall['function']['name'];
                $parameters = json_decode($toolCall['function']['arguments'], true);

                try {
                    $toolResult = $this->executeTool($toolName, $parameters);
                    $toolsUsed[] = [
                        'name' => $toolName,
                        'parameters' => $parameters,
                        'result' => $toolResult
                    ];
                } catch (Exception $e) {
                    error_log("Tool execution failed: " . $e->getMessage());
                    $toolsUsed[] = [
                        'name' => $toolName,
                        'parameters' => $parameters,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'response' => $message['content'] ?? 'I apologize, but I encountered an issue processing your request.',
            'token_usage' => $responseData['usage'] ?? null,
            'tools_used' => $toolsUsed
        ];
    }

    private function executeTool($toolName, $parameters)
    {
        $toolClassName = ucfirst($toolName);
        $toolFile = __DIR__ . "/../Tools/{$toolClassName}.php";

        if (!file_exists($toolFile)) {
            throw new Exception("Tool file not found: {$toolFile}");
        }

        require_once $toolFile;

        if (!class_exists($toolClassName)) {
            throw new Exception("Tool class not found: {$toolClassName}");
        }

        $tool = new $toolClassName();
        return $tool->safeExecute($parameters);
    }

    private function createRun($threadId, $message)
    {
        return [
            'id' => uniqid(),
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress',
            'input_message' => $message,
            'started_at' => date('Y-m-d H:i:s')
        ];
    }

    private function completeRun($runId, $status, $metadata = [])
    {
        // Log run completion (you can implement database storage here)
        error_log("Run {$runId} completed with status: {$status}");
    }

    /**
     * FIXED DELETE METHOD - Actually deletes from database
     * This method now permanently removes the agent and related data
     */
    public function delete()
    {
        if (!$this->id) {
            error_log("Cannot delete agent: no ID set");
            throw new Exception("Cannot delete agent: no ID set");
        }

        error_log("Attempting to permanently delete agent {$this->id}");

        try {
            // Begin transaction to ensure data consistency
            $this->db->getConnection()->beginTransaction();

            // 1. First, delete related runs (foreign key constraint handling)
            $deleteRunsResult = $this->db->delete('runs', 'agent_id = ?', [$this->id]);
            error_log("Deleted runs for agent {$this->id}: affected rows - " . ($deleteRunsResult ? 'success' : 'none'));

            // 2. Delete the agent itself
            $deleteAgentResult = $this->db->delete('agents', 'id = ?', [$this->id]);

            if ($deleteAgentResult) {
                // Commit the transaction
                $this->db->getConnection()->commit();
                error_log("Successfully deleted agent {$this->id} and all related data");

                // Clear the object state since it's been deleted
                $this->id = null;
                $this->isActive = false;

                return true;
            } else {
                // Rollback if agent deletion failed
                $this->db->getConnection()->rollback();
                error_log("Failed to delete agent {$this->id} - rolling back transaction");
                throw new Exception("Failed to delete agent from database");
            }
        } catch (Exception $e) {
            // Rollback transaction on any error
            $this->db->getConnection()->rollback();
            error_log("Error deleting agent {$this->id}: " . $e->getMessage());
            throw new Exception("Database error during agent deletion: " . $e->getMessage());
        }
    }

    /**
     * Soft delete method (for cases where you want to keep the data but mark as inactive)
     * This is separate from the main delete() method
     */
    public function softDelete()
    {
        if ($this->id) {
            error_log("Soft deleting agent {$this->id} (marking as inactive)");

            $result = $this->db->update(
                'agents',
                ['is_active' => 0],
                'id = ?',
                [$this->id]
            );

            if ($result) {
                error_log("Successfully soft deleted agent {$this->id}");
                $this->isActive = false;
                return true;
            } else {
                error_log("Failed to soft delete agent {$this->id}");
                throw new Exception("Failed to deactivate agent");
            }
        } else {
            error_log("Cannot soft delete agent: no ID set");
            throw new Exception("Cannot deactivate agent: no ID set");
        }
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getInstructions()
    {
        return $this->instructions;
    }
    public function getModel()
    {
        return $this->model;
    }
    public function getTools()
    {
        return $this->tools;
    }
    public function getUserId()
    {
        return $this->userId;
    }
    public function isActive()
    {
        return $this->isActive;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'model' => $this->model,
            'tools' => $this->tools,
            'user_id' => $this->userId,
            'is_active' => $this->isActive
        ];
    }
}