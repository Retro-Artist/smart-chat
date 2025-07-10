<?php
// src/Api/OpenAI/ThreadsAPI.php - FIXED with better error handling

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../Models/Thread.php';
require_once __DIR__ . '/../Models/Agent.php';
require_once __DIR__ . '/SystemAPI.php';

class ThreadsAPI {
    
    public function getThreads() {
        Helpers::requireAuth();
        
        try {
            $userId = Helpers::getCurrentUserId();
            error_log("ThreadsAPI::getThreads - Getting threads for user: {$userId}");
            
            $threads = Thread::getUserThreads($userId);
            error_log("ThreadsAPI::getThreads - Found " . count($threads) . " threads");
            
            Helpers::jsonResponse($threads);
        } catch (Exception $e) {
            error_log("ThreadsAPI::getThreads - Error: " . $e->getMessage());
            error_log("ThreadsAPI::getThreads - Stack trace: " . $e->getTraceAsString());
            Helpers::jsonError('Failed to fetch threads: ' . $e->getMessage(), 500);
        }
    }
    
    public function createThread() {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        
        try {
            // Make title optional with default
            $title = $input['title'] ?? 'New Chat';
            $systemMessage = $input['system_message'] ?? null;
            
            error_log("ThreadsAPI::createThread - Creating thread with title: '{$title}'");
            
            $thread = Thread::create(
                Helpers::getCurrentUserId(), 
                $title,
                $systemMessage
            );
            
            error_log("ThreadsAPI::createThread - Thread created with ID: {$thread['id']}");
            
            Helpers::jsonResponse($thread, 201);
        } catch (Exception $e) {
            error_log("ThreadsAPI::createThread - Error: " . $e->getMessage());
            error_log("ThreadsAPI::createThread - Stack trace: " . $e->getTraceAsString());
            Helpers::jsonError('Failed to create thread: ' . $e->getMessage(), 500);
        }
    }
    
    public function getThread($threadId) {
        Helpers::requireAuth();
        
        try {
            // Validate thread ID
            if (!$threadId || !is_numeric($threadId)) {
                error_log("ThreadsAPI: Invalid thread ID received: " . var_export($threadId, true));
                Helpers::jsonError('Invalid thread ID', 400);
            }
            
            // Check ownership first
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                error_log("ThreadsAPI: Access denied for thread {$threadId} by user " . Helpers::getCurrentUserId());
                Helpers::jsonError('Access denied', 403);
            }
            
            $thread = Thread::findById($threadId);
            if (!$thread) {
                error_log("ThreadsAPI: Thread {$threadId} not found");
                Helpers::jsonError('Thread not found', 404);
            }
            
            // Include messages in response
            $thread['messages'] = Thread::getMessages($threadId);
            $thread['stats'] = Thread::getThreadStats($threadId);
            
            Helpers::jsonResponse($thread);
        } catch (Exception $e) {
            error_log("Error fetching thread: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch thread', 500);
        }
    }
    
    public function updateThread($threadId) {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        
        try {
            // Validate thread ID
            if (!$threadId || !is_numeric($threadId)) {
                error_log("ThreadsAPI: Invalid thread ID for update: " . var_export($threadId, true));
                Helpers::jsonError('Invalid thread ID', 400);
            }
            
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $updated = false;
            
            // Update title if provided
            if (isset($input['title'])) {
                Thread::updateTitle($threadId, $input['title']);
                $updated = true;
            }
            
            // Update status if provided (archive/activate)
            if (isset($input['status']) && in_array($input['status'], ['active', 'archived'])) {
                if ($input['status'] === 'archived') {
                    Thread::archive($threadId);
                } else {
                    // Reactivate archived thread
                    require_once __DIR__ . '/../../Core/Database.php';
                    $db = Database::getInstance();
                    $db->update('threads', ['status' => 'active'], 'id = ?', [$threadId]);
                }
                $updated = true;
            }
            
            if (!$updated) {
                Helpers::jsonError('No valid fields provided for update', 400);
            }
            
            $thread = Thread::findById($threadId);
            Helpers::jsonResponse($thread);
            
        } catch (Exception $e) {
            error_log("Error updating thread: " . $e->getMessage());
            Helpers::jsonError('Failed to update thread', 500);
        }
    }
    
    public function deleteThread($threadId) {
        Helpers::requireAuth();
        
        try {
            // Validate thread ID
            if (!$threadId || !is_numeric($threadId)) {
                error_log("ThreadsAPI: Invalid thread ID for delete: " . var_export($threadId, true));
                Helpers::jsonError('Invalid thread ID', 400);
            }
            
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            Thread::delete($threadId);
            Helpers::jsonResponse(['message' => 'Thread deleted successfully'], 200);
        } catch (Exception $e) {
            error_log("Error deleting thread: " . $e->getMessage());
            Helpers::jsonError('Failed to delete thread', 500);
        }
    }
    
    public function getMessages($threadId) {
        Helpers::requireAuth();
        
        try {
            // Validate thread ID
            if (!$threadId || !is_numeric($threadId)) {
                error_log("ThreadsAPI: Invalid thread ID for messages: " . var_export($threadId, true));
                Helpers::jsonError('Invalid thread ID', 400);
            }
            
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $messages = Thread::getMessages($threadId);
            Helpers::jsonResponse([
                'messages' => $messages,
                'count' => count($messages),
                'stats' => Thread::getThreadStats($threadId)
            ]);
        } catch (Exception $e) {
            error_log("Error fetching messages: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch messages', 500);
        }
    }
    
    public function sendMessage($threadId) {
        Helpers::requireAuth();
        
        // DEBUG: Log what we received
        error_log("ThreadsAPI::sendMessage called with threadId: " . var_export($threadId, true));
        
        $input = Helpers::getJsonInput();
        error_log("ThreadsAPI::sendMessage input: " . json_encode($input));
        
        try {
            // Validate thread ID first
            if (!$threadId || !is_numeric($threadId)) {
                error_log("ThreadsAPI: Invalid thread ID received in sendMessage: " . var_export($threadId, true));
                Helpers::jsonError('Invalid thread ID', 400);
                return;
            }
            
            // Validate input
            if (!isset($input['message'])) {
                error_log("ThreadsAPI: Missing message in input");
                Helpers::jsonError('Message is required', 400);
                return;
            }
            
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                error_log("ThreadsAPI: Access denied for thread {$threadId} by user " . Helpers::getCurrentUserId());
                Helpers::jsonError('Access denied', 403);
                return;
            }
            
            $userMessage = trim($input['message']);
            if (empty($userMessage)) {
                error_log("ThreadsAPI: Empty message after trim");
                Helpers::jsonError('Message cannot be empty', 400);
                return;
            }
            
            error_log("ThreadsAPI: Processing message: '{$userMessage}' for thread {$threadId}");
            
            // Add user message to thread
            $userMessageData = Thread::addMessage($threadId, 'user', $userMessage);
            error_log("ThreadsAPI: User message added successfully");
            
            // Get conversation history for OpenAI
            $conversationHistory = Thread::getOpenAIMessages($threadId);
            error_log("ThreadsAPI: Retrieved " . count($conversationHistory) . " messages for OpenAI");
            
            // Use SystemAPI for OpenAI communication
            try {
                $systemAPI = new SystemAPI();
                error_log("ThreadsAPI: SystemAPI instantiated successfully");
            } catch (Exception $e) {
                error_log("ThreadsAPI: Failed to instantiate SystemAPI: " . $e->getMessage());
                throw new Exception("OpenAI API configuration error: " . $e->getMessage());
            }
            
            // Check if a specific system message was provided
            $systemMessage = $input['system_message'] ?? null;
            
            // Make OpenAI API call
            try {
                $response = $systemAPI->callOpenAI([
                    'messages' => $conversationHistory,
                    'model' => $input['model'] ?? 'gpt-4o-mini',
                    'temperature' => $input['temperature'] ?? 0.7,
                    'max_tokens' => $input['max_tokens'] ?? 1024
                ]);
                
                error_log("ThreadsAPI: OpenAI API call successful");
            } catch (Exception $e) {
                error_log("ThreadsAPI: OpenAI API call failed: " . $e->getMessage());
                throw new Exception("OpenAI API error: " . $e->getMessage());
            }
            
            // Extract assistant response
            if (!isset($response['choices'][0]['message']['content'])) {
                error_log("ThreadsAPI: Invalid OpenAI response structure: " . json_encode($response));
                throw new Exception('Invalid response from OpenAI API');
            }
            
            $assistantContent = trim($response['choices'][0]['message']['content']);
            if (empty($assistantContent)) {
                $assistantContent = "I apologize, but I wasn't able to generate a proper response. Please try again.";
                error_log("ThreadsAPI: Empty response from OpenAI, using fallback message");
            }
            
            error_log("ThreadsAPI: Assistant response: '{$assistantContent}'");
            
            // Prepare assistant message metadata
            $assistantMetadata = [
                'model' => $response['model'] ?? 'gpt-4o-mini'
            ];
            
            // Add token usage if available
            if (isset($response['usage'])) {
                $assistantMetadata['token_usage'] = $response['usage'];
            }
            
            // Add assistant response to thread
            $assistantMessageData = Thread::addMessage($threadId, 'assistant', $assistantContent, $assistantMetadata);
            error_log("ThreadsAPI: Assistant message added successfully");
            
            // Return response with message details
            Helpers::jsonResponse([
                'success' => true,
                'response' => $assistantContent,
                'thread_id' => $threadId,
                'user_message' => $userMessageData,
                'assistant_message' => $assistantMessageData,
                'token_usage' => $response['usage'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("ThreadsAPI::sendMessage error: " . $e->getMessage());
            error_log("ThreadsAPI::sendMessage stack trace: " . $e->getTraceAsString());
            
            // Try to add an error message to the thread if we have a valid thread ID
            if ($threadId && is_numeric($threadId)) {
                try {
                    Thread::addMessage($threadId, 'assistant', 
                        "Sorry, I encountered an error processing your message. Please try again."
                    );
                    error_log("ThreadsAPI: Error message added to thread");
                } catch (Exception $saveError) {
                    error_log("ThreadsAPI: Failed to save error message: " . $saveError->getMessage());
                }
            }
            
            Helpers::jsonError('Failed to send message: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Import existing conversation from external source
     */
    public function importConversation($threadId) {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['messages']);
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $messages = $input['messages'];
            
            // Validate message format
            foreach ($messages as $message) {
                if (!isset($message['role']) || !isset($message['content'])) {
                    Helpers::jsonError('Invalid message format. Each message must have role and content.', 400);
                }
                
                if (!in_array($message['role'], ['user', 'assistant', 'system'])) {
                    Helpers::jsonError('Invalid message role. Must be user, assistant, or system.', 400);
                }
            }
            
            // Replace all messages in thread
            Thread::setMessages($threadId, $messages);
            
            $thread = Thread::findById($threadId);
            Helpers::jsonResponse([
                'success' => true,
                'thread' => $thread,
                'imported_count' => count($messages)
            ]);
            
        } catch (Exception $e) {
            error_log("Error importing conversation: " . $e->getMessage());
            Helpers::jsonError('Failed to import conversation', 500);
        }
    }
    
    /**
     * Export conversation in various formats
     */
    public function exportConversation($threadId) {
        Helpers::requireAuth();
        
        $format = $_GET['format'] ?? 'json';
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $thread = Thread::findById($threadId);
            $messages = Thread::getMessages($threadId);
            
            switch ($format) {
                case 'json':
                    Helpers::jsonResponse([
                        'thread' => $thread,
                        'messages' => $messages,
                        'exported_at' => date('c')
                    ]);
                    break;
                    
                case 'text':
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="conversation-' . $threadId . '.txt"');
                    
                    echo "Conversation: " . $thread['title'] . "\n";
                    echo "Created: " . $thread['created_at'] . "\n";
                    echo str_repeat("=", 50) . "\n\n";
                    
                    foreach ($messages as $message) {
                        $role = strtoupper($message['role']);
                        $timestamp = $message['timestamp'] ?? '';
                        echo "[$role] $timestamp\n";
                        echo $message['content'] . "\n\n";
                    }
                    exit;
                    
                default:
                    Helpers::jsonError('Unsupported export format. Use json or text.', 400);
            }
            
        } catch (Exception $e) {
            error_log("Error exporting conversation: " . $e->getMessage());
            Helpers::jsonError('Failed to export conversation', 500);
        }
    }
    
    /**
     * Search messages within a thread
     */
    public function searchMessages($threadId) {
        Helpers::requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            if (empty($query)) {
                Helpers::jsonError('Search query is required', 400);
            }
            
            $messages = Thread::getMessages($threadId);
            $results = [];
            
            foreach ($messages as $index => $message) {
                if (stripos($message['content'], $query) !== false) {
                    $results[] = array_merge($message, [
                        'message_index' => $index,
                        'preview' => $this->getSearchPreview($message['content'], $query)
                    ]);
                }
            }
            
            Helpers::jsonResponse([
                'query' => $query,
                'results' => $results,
                'count' => count($results)
            ]);
            
        } catch (Exception $e) {
            error_log("Error searching messages: " . $e->getMessage());
            Helpers::jsonError('Failed to search messages', 500);
        }
    }
    
    /**
     * Get conversation statistics
     */
    public function getThreadStats($threadId) {
        Helpers::requireAuth();
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $stats = Thread::getThreadStats($threadId);
            $thread = Thread::findById($threadId);
            
            Helpers::jsonResponse([
                'thread_id' => $threadId,
                'title' => $thread['title'],
                'stats' => $stats,
                'created_at' => $thread['created_at'],
                'updated_at' => $thread['updated_at']
            ]);
            
        } catch (Exception $e) {
            error_log("Error getting thread stats: " . $e->getMessage());
            Helpers::jsonError('Failed to get thread statistics', 500);
        }
    }
    
    /**
     * Trim old messages from thread (for performance)
     */
    public function trimMessages($threadId) {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        $keepLastN = $input['keep_last'] ?? 50;
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            if ($keepLastN < 10 || $keepLastN > 1000) {
                Helpers::jsonError('keep_last must be between 10 and 1000', 400);
            }
            
            $removedCount = Thread::trimMessages($threadId, $keepLastN);
            
            if ($removedCount === false) {
                Helpers::jsonResponse([
                    'message' => 'No trimming needed',
                    'removed_count' => 0
                ]);
            } else {
                Helpers::jsonResponse([
                    'message' => 'Messages trimmed successfully',
                    'removed_count' => $removedCount,
                    'kept_last' => $keepLastN
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error trimming messages: " . $e->getMessage());
            Helpers::jsonError('Failed to trim messages', 500);
        }
    }
    
    /**
     * Helper method to generate search preview with highlighted text
     */
    private function getSearchPreview($content, $query, $previewLength = 150) {
        $position = stripos($content, $query);
        
        if ($position === false) {
            return substr($content, 0, $previewLength) . '...';
        }
        
        // Get context around the match
        $start = max(0, $position - 50);
        $end = min(strlen($content), $position + strlen($query) + 50);
        
        $preview = substr($content, $start, $end - $start);
        
        // Add ellipsis if we cut the text
        if ($start > 0) {
            $preview = '...' . $preview;
        }
        if ($end < strlen($content)) {
            $preview = $preview . '...';
        }
        
        return $preview;
    }
}