<?php
// src/Api/Models/Agent.php - Updated with enhanced edit methods

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

    public static function findById($agentId)
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM agents WHERE id = ? AND is_active = 1", [$agentId]);

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
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
                'tools_used' => $executionResult['tools_used'] ?? [],
                'model_used' => $this->model,
                'agent_name' => $this->name
            ]);

            return $executionResult['response'];
        } catch (Exception $e) {
            // Mark run as failed with error details
            $this->completeRun($run['id'], 'failed', [
                'input_message' => $message,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    // Enhanced createRun method
    private function createRun($threadId, $inputMessage = null)
    {
        $runId = $this->db->insert('runs', [
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress',
            'input_message' => $inputMessage, // Store input message immediately
            'started_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'id' => $runId,
            'thread_id' => $threadId,
            'agent_id' => $this->id,
            'status' => 'in_progress'
        ];
    }

    // Enhanced completeRun method
    private function completeRun($runId, $status, $runData = [])
    {
        $updateData = [
            'status' => $status,
            'completed_at' => date('Y-m-d H:i:s')
        ];

        // Add specific fields if provided
        if (isset($runData['input_message'])) {
            $updateData['input_message'] = $runData['input_message'];
        }

        if (isset($runData['output_message'])) {
            $updateData['output_message'] = $runData['output_message'];
        }

        if (isset($runData['token_usage'])) {
            $updateData['token_usage'] = json_encode($runData['token_usage']);
        }

        if (isset($runData['tools_used'])) {
            $updateData['tools_used'] = json_encode($runData['tools_used']);
        }

        // Store additional metadata
        $metadata = array_diff_key($runData, array_flip([
            'input_message',
            'output_message',
            'token_usage',
            'tools_used'
        ]));

        if (!empty($metadata)) {
            $updateData['metadata'] = json_encode($metadata);
        }

        $this->db->update('runs', $updateData, 'id = ?', [$runId]);
    }

    // Enhanced executeWithTools to track tool usage and token consumption
    private function executeWithTools($message, $threadId)
    {
        // Get conversation history as OpenAI-compatible format
        require_once __DIR__ . '/Thread.php';
        $conversationHistory = Thread::getOpenAIMessages($threadId);

        // Prepare tools for OpenAI (if agent has tools)
        $tools = $this->prepareTools();
        $toolsUsed = [];
        $totalTokenUsage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0
        ];

        // Build messages array for OpenAI
        $conversationMessages = [
            [
                'role' => 'system',
                'content' => $this->instructions
            ]
        ];

        // Add recent conversation history (limit to last 20 for performance)
        $recentHistory = array_slice($conversationHistory, 0, -1);
        $recentHistory = array_slice($recentHistory, -20);

        foreach ($recentHistory as $msg) {
            if ($msg['role'] !== 'system') {
                $conversationMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }

        // Add current user message
        $conversationMessages[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Use SystemAPI for all OpenAI communication
        $systemAPI = new SystemAPI();

        try {
            // Make initial API call
            $response = $systemAPI->agentChat(
                $conversationMessages,
                $tools,
                $this->model,
                0.7
            );

            // Track token usage from initial call
            if (isset($response['usage'])) {
                $totalTokenUsage['prompt_tokens'] += $response['usage']['prompt_tokens'] ?? 0;
                $totalTokenUsage['completion_tokens'] += $response['usage']['completion_tokens'] ?? 0;
                $totalTokenUsage['total_tokens'] += $response['usage']['total_tokens'] ?? 0;
            }

            // Validate response structure
            if (!isset($response['choices'][0]['message'])) {
                throw new Exception('Invalid OpenAI response structure');
            }

            $assistantMessage = $response['choices'][0]['message'];

            // Check if tools were called
            if (isset($assistantMessage['tool_calls']) && !empty($assistantMessage['tool_calls'])) {
                // Execute tool calls and track usage
                $toolResults = $this->executeToolCalls($assistantMessage['tool_calls']);

                // Track which tools were used
                foreach ($assistantMessage['tool_calls'] as $toolCall) {
                    $toolsUsed[] = [
                        'tool_name' => $toolCall['function']['name'],
                        'tool_call_id' => $toolCall['id'],
                        'parameters' => json_decode($toolCall['function']['arguments'], true),
                        'executed_at' => date('c')
                    ];
                }

                // Add assistant message with tool calls to conversation
                $conversationMessages[] = $assistantMessage;

                // Add tool results to conversation
                foreach ($toolResults as $toolResult) {
                    $conversationMessages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolResult['tool_call_id'],
                        'content' => json_encode($toolResult['result'])
                    ];
                }

                // Make another API call with tool results
                $finalResponse = $systemAPI->agentChat($conversationMessages, $tools, $this->model);

                // Track token usage from final call
                if (isset($finalResponse['usage'])) {
                    $totalTokenUsage['prompt_tokens'] += $finalResponse['usage']['prompt_tokens'] ?? 0;
                    $totalTokenUsage['completion_tokens'] += $finalResponse['usage']['completion_tokens'] ?? 0;
                    $totalTokenUsage['total_tokens'] += $finalResponse['usage']['total_tokens'] ?? 0;
                }

                $finalContent = $finalResponse['choices'][0]['message']['content'] ?? '';

                if (empty(trim($finalContent))) {
                    $toolSummary = $this->summarizeToolResults($toolResults);
                    $finalContent = "I've completed your request using the available tools. " . $toolSummary;
                }

                return [
                    'response' => $finalContent,
                    'token_usage' => $totalTokenUsage,
                    'tools_used' => $toolsUsed,
                    'tool_results' => $toolResults
                ];
            } else {
                // No tools needed, return response directly
                $content = $assistantMessage['content'] ?? '';

                if (empty(trim($content))) {
                    $content = "I understand your request, but I wasn't able to generate a proper response.";
                }

                return [
                    'response' => $content,
                    'token_usage' => $totalTokenUsage,
                    'tools_used' => [],
                    'tool_results' => []
                ];
            }
        } catch (Exception $e) {
            error_log("Error in agent execution: " . $e->getMessage());
            throw $e;
        }
    }

    private function summarizeToolResults($toolResults)
    {
        $summaries = [];

        foreach ($toolResults as $result) {
            $toolName = $result['tool_name'] ?? 'Unknown';
            $toolResult = $result['result'] ?? [];

            if (isset($toolResult['success']) && $toolResult['success']) {
                switch ($toolName) {
                    case 'weather':
                        if (isset($toolResult['weather']['description'])) {
                            $summaries[] = $toolResult['weather']['description'];
                        }
                        break;
                    case 'math':
                        if (isset($toolResult['result'])) {
                            $summaries[] = "Calculation result: " . $toolResult['result'];
                        }
                        break;
                    case 'search':
                        if (isset($toolResult['results'])) {
                            $summaries[] = "Found " . count($toolResult['results']) . " search results";
                        }
                        break;
                    case 'read_pdf':
                        if (isset($toolResult['word_count'])) {
                            $summaries[] = "Processed PDF with " . $toolResult['word_count'] . " words";
                        }
                        break;
                    default:
                        $summaries[] = "Executed " . $toolName . " successfully";
                }
            } else {
                $summaries[] = "Tool " . $toolName . " encountered an issue";
            }
        }

        return implode('. ', $summaries);
    }

    private function prepareTools()
    {
        $tools = [];

        foreach ($this->tools as $toolClassName) {
            try {
                $toolFile = __DIR__ . "/../Tools/{$toolClassName}.php";

                if (file_exists($toolFile)) {
                    require_once $toolFile;

                    if (class_exists($toolClassName)) {
                        $tool = new $toolClassName();
                        $tools[] = $tool->getOpenAIDefinition();
                    }
                }
            } catch (Exception $e) {
                error_log("Error loading tool {$toolClassName}: " . $e->getMessage());
            }
        }

        return $tools;
    }

    private function executeToolCalls($toolCalls)
    {
        $results = [];

        foreach ($toolCalls as $toolCall) {
            try {
                $toolName = $toolCall['function']['name'];
                $parameters = json_decode($toolCall['function']['arguments'], true);

                $result = $this->executeTool($toolName, $parameters);

                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolName,
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'tool_call_id' => $toolCall['id'],
                    'tool_name' => $toolCall['function']['name'] ?? 'unknown',
                    'result' => [
                        'success' => false,
                        'error' => $e->getMessage()
                    ]
                ];
            }
        }

        return $results;
    }

    private function executeTool($toolName, $parameters)
    {
        // Map tool names to class names
        $toolMap = [
            'math' => 'Math',
            'search' => 'Search',
            'weather' => 'Weather',
            'read_pdf' => 'ReadPDF'
        ];

        if (!isset($toolMap[$toolName])) {
            throw new Exception("Unknown tool: {$toolName}");
        }

        $toolClassName = $toolMap[$toolName];
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
