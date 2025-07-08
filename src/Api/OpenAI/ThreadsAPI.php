<?php
// src/Api/OpenAI/ThreadsAPI.php - UPDATED for JSON message storage

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../Models/Thread.php';
require_once __DIR__ . '/../Models/Agent.php';
require_once __DIR__ . '/SystemAPI.php';

class ThreadsAPI {
    
    public function getThreads() {
        Helpers::requireAuth();
        
        try {
            $threads = Thread::getUserThreads(Helpers::getCurrentUserId());
            Helpers::jsonResponse($threads);
        } catch (Exception $e) {
            error_log("Error fetching threads: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch threads', 500);
        }
    }
    
    public function createThread() {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['title']);
        
        try {
            $systemMessage = $input['system_message'] ?? null;
            $thread = Thread::create(
                Helpers::getCurrentUserId(), 
                $input['title'],
                $systemMessage
            );
            
            Helpers::jsonResponse($thread, 201);
        } catch (Exception $e) {
            error_log("Error creating thread: " . $e->getMessage());
            Helpers::jsonError('Failed to create thread', 500);
        }
    }
    
    public function getThread($threadId) {
        Helpers::requireAuth();
        
        try {
            // Check ownership first
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $thread = Thread::findById($threadId);
            if (!$thread) {
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
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['message']);
        
        try {
            // Check ownership
            if (!Thread::belongsToUser($threadId, Helpers::getCurrentUserId())) {
                Helpers::jsonError('Access denied', 403);
            }
            
            $userMessage = trim($input['message']);
            if (empty($userMessage)) {
                Helpers::jsonError('Message cannot be empty', 400);
            }
            
            // Add user message to thread
            $userMessageData = Thread::addMessage($threadId, 'user', $userMessage);
            
            // Get conversation history for OpenAI
            $conversationHistory = Thread::getOpenAIMessages($threadId);
            
            // Use SystemAPI for OpenAI communication
            $systemAPI = new SystemAPI();
            
            // Check if a specific system message was provided
            $systemMessage = $input['system_message'] ?? null;
            
            // Make OpenAI API call
            $response = $systemAPI->callOpenAI([
                'messages' => $conversationHistory,
                'model' => $input['model'] ?? 'gpt-4o-mini',
                'temperature' => $input['temperature'] ?? 0.7,
                'max_tokens' => $input['max_tokens'] ?? 1024
            ]);
            
            // Extract assistant response
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response from OpenAI API');
            }
            
            $assistantContent = trim($response['choices'][0]['message']['content']);
            if (empty($assistantContent)) {
                $assistantContent = "I apologize, but I wasn't able to generate a proper response. Please try again.";
            }
            
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
            error_log("Error sending message: " . $e->getMessage());
            
            // Try to add an error message to the thread
            try {
                Thread::addMessage($threadId, 'assistant', 
                    "I encountered an error processing your message. Please try again."
                );
            } catch (Exception $saveError) {
                error_log("Failed to save error message: " . $saveError->getMessage());
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