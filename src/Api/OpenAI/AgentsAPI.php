<?php
// src/Api/OpenAI/AgentsAPI.php - Updated with enhanced updateAgent method

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../Models/Agent.php';
require_once __DIR__ . '/../Models/Thread.php';

class AgentsAPI
{

    public function getAgents()
    {
        Helpers::requireAuth();

        try {
            $agents = Agent::getUserAgents(Helpers::getCurrentUserId());

            // Convert to array format
            $agentData = [];
            foreach ($agents as $agent) {
                $agentData[] = $agent->toArray();
            }

            Helpers::jsonResponse($agentData);
        } catch (Exception $e) {
            error_log("Error fetching agents: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch agents', 500);
        }
    }

    public function createAgent()
    {
        Helpers::requireAuth();

        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['name', 'instructions']);

        try {
            // Create new agent
            $agent = new Agent(
                $input['name'],
                $input['instructions'],
                $input['model'] ?? 'gpt-4o-mini'
            );

            // Add tools if provided
            if (isset($input['tools']) && is_array($input['tools'])) {
                foreach ($input['tools'] as $tool) {
                    $agent->addTool($tool);
                }
            }

            // Save agent
            $agent->save();

            Helpers::jsonResponse($agent->toArray(), 201);
        } catch (Exception $e) {
            error_log("Error creating agent: " . $e->getMessage());
            Helpers::jsonError('Failed to create agent: ' . $e->getMessage(), 500);
        }
    }

    public function getAgent($agentId)
    {
        Helpers::requireAuth();

        try {
            $agent = Agent::findById($agentId);

            if (!$agent) {
                Helpers::jsonError('Agent not found', 404);
            }

            // Check ownership
            if ($agent->getUserId() != Helpers::getCurrentUserId()) {
                Helpers::jsonError('Access denied', 403);
            }

            Helpers::jsonResponse($agent->toArray());
        } catch (Exception $e) {
            error_log("Error fetching agent: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch agent', 500);
        }
    }

    public function updateAgent($agentId)
    {
        Helpers::requireAuth();

        $input = Helpers::getJsonInput();

        try {
            $agent = Agent::findById($agentId);

            if (!$agent) {
                Helpers::jsonError('Agent not found', 404);
            }

            // Check ownership
            if ($agent->getUserId() != Helpers::getCurrentUserId()) {
                Helpers::jsonError('Access denied', 403);
            }

            // Update agent properties
            if (isset($input['name'])) {
                $agent->setName($input['name']);
            }

            if (isset($input['instructions'])) {
                $agent->setInstructions($input['instructions']);
            }

            if (isset($input['model'])) {
                $agent->setModel($input['model']);
            }

            if (isset($input['is_active'])) {
                $agent->setActive($input['is_active']);
            }

            // Update tools - clear existing and add new ones
            if (isset($input['tools']) && is_array($input['tools'])) {
                $agent->clearTools();
                foreach ($input['tools'] as $tool) {
                    $agent->addTool($tool);
                }
            }

            // Save the updated agent
            $agent->save();

            Helpers::jsonResponse($agent->toArray());
        } catch (Exception $e) {
            error_log("Error updating agent: " . $e->getMessage());
            Helpers::jsonError('Failed to update agent: ' . $e->getMessage(), 500);
        }
    }

    public function deleteAgent($agentId)
    {
        Helpers::requireAuth();

        try {
            $agent = Agent::findById($agentId);

            if (!$agent) {
                Helpers::jsonError('Agent not found', 404);
            }

            // Check ownership
            if ($agent->getUserId() != Helpers::getCurrentUserId()) {
                Helpers::jsonError('Access denied', 403);
            }

            $agent->delete();

            Helpers::jsonResponse(['message' => 'Agent deleted successfully'], 200);
        } catch (Exception $e) {
            error_log("Error deleting agent: " . $e->getMessage());
            Helpers::jsonError('Failed to delete agent', 500);
        }
    }

    public function runAgent($agentId)
    {
        Helpers::requireAuth();

        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['message', 'threadId']);

        try {
            $agent = Agent::findById($agentId);

            if (!$agent) {
                Helpers::jsonError('Agent not found', 404);
            }

            // Check agent ownership
            if ($agent->getUserId() != Helpers::getCurrentUserId()) {
                Helpers::jsonError('Access denied', 403);
            }

            // Verify thread ownership
            if (!Thread::belongsToUser($input['threadId'], Helpers::getCurrentUserId())) {
                Helpers::jsonError('Thread access denied', 403);
            }

            // Execute agent (this now handles adding both user and assistant messages)
            $response = $agent->execute($input['message'], $input['threadId']);

            // Validate response
            if ($response === null || trim($response) === '') {
                error_log("Agent execution returned null/empty response for agent {$agentId}");
                $response = "I apologize, but I encountered an issue processing your request. Please try again.";

                // Add fallback response to thread
                Thread::addMessage($input['threadId'], 'assistant', $response, [
                    'agent_id' => $agentId,
                    'error' => 'Empty response fallback'
                ]);
            }

            // Get the most recent assistant message to extract metadata
            $messages = Thread::getMessages($input['threadId']);
            $lastMessage = end($messages);

            // Build response with metadata for immediate UI update
            $responseData = [
                'success' => true,
                'response' => $response,
                'agentId' => $agentId,
                'threadId' => $input['threadId']
            ];

            // Include metadata if available from the last message
            if ($lastMessage && $lastMessage['role'] === 'assistant') {
                // Extract metadata fields that the frontend expects
                if (isset($lastMessage['agent_name'])) {
                    $responseData['agent_name'] = $lastMessage['agent_name'];
                }

                if (isset($lastMessage['model'])) {
                    $responseData['model'] = $lastMessage['model'];
                }

                if (isset($lastMessage['token_usage'])) {
                    $responseData['token_usage'] = $lastMessage['token_usage'];
                }

                if (isset($lastMessage['tools_used'])) {
                    $responseData['tools_used'] = $lastMessage['tools_used'];
                }

                // Also include any additional metadata
                $metadataFields = ['agent_id', 'tools_available', 'run_id', 'execution_duration_ms'];
                foreach ($metadataFields as $field) {
                    if (isset($lastMessage[$field])) {
                        $responseData[$field] = $lastMessage[$field];
                    }
                }
            }

            // If for some reason we don't have metadata from the message, 
            // fall back to agent properties
            if (!isset($responseData['agent_name'])) {
                $responseData['agent_name'] = $agent->getName();
            }

            if (!isset($responseData['model'])) {
                $responseData['model'] = $agent->getModel();
            }

            Helpers::jsonResponse($responseData);
        } catch (Exception $e) {
            error_log("Error executing agent: " . $e->getMessage());

            // Try to add an error message to the thread
            try {
                if (isset($input['threadId'])) {
                    Thread::addMessage(
                        $input['threadId'],
                        'assistant',
                        "I encountered an error while processing your request. Please try again.",
                        [
                            'agent_id' => $agentId ?? null,
                            'error' => $e->getMessage()
                        ]
                    );
                }
            } catch (Exception $saveError) {
                error_log("Failed to save error message: " . $saveError->getMessage());
            }

            Helpers::jsonError('Failed to execute agent: ' . $e->getMessage(), 500);
        }
    }
}
