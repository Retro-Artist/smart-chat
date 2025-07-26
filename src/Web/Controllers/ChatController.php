<?php
// src/Web/Controllers/ChatController.php - FIXED for thread switching

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/Models/Thread.php';
require_once __DIR__ . '/../../Api/Models/Agent.php';

class ChatController {
    
    public function index() {
        // Authentication is now handled by router-level authentication gate
        $userId = Helpers::getCurrentUserId();
        
        // Get user's threads
        $threads = Thread::getUserThreads($userId);
        
        // Determine current thread
        $currentThread = null;
        $requestedThreadId = filter_var($_GET['thread'] ?? null, FILTER_VALIDATE_INT);
        
        if ($requestedThreadId !== false && $requestedThreadId > 0) {
            // User clicked on a specific thread
            $requestedThread = Thread::findById($requestedThreadId);
            
            // Verify ownership and thread exists
            if ($requestedThread && Thread::belongsToUser($requestedThreadId, $userId)) {
                $currentThread = $requestedThread;
            }
        }
        
        // If no valid thread selected, use the most recent one or create new
        if (!$currentThread) {
            if (!empty($threads)) {
                $currentThread = $threads[0]; // Most recent thread
            } else {
                // Create first thread for new user
                $currentThread = Thread::create($userId, 'Welcome Chat');
                // Refresh threads list to include the new one
                $threads = Thread::getUserThreads($userId);
            }
        }
        
        // Get messages for current thread
        $messages = Thread::getMessages($currentThread['id']);
        
        // Get available agents for user
        $availableAgents = Agent::getUserAgents($userId);
        
        // Check if a specific agent was requested via URL parameter
        $selectedAgentId = null;
        $agentId = filter_var($_GET['agent'] ?? null, FILTER_VALIDATE_INT);
        if ($agentId !== false && $agentId > 0) {
            $requestedAgent = Agent::findById($agentId);
            if ($requestedAgent && $requestedAgent->getUserId() == $userId) {
                $selectedAgentId = $agentId;
            }
        }
        
        // Load chat view
        Helpers::loadView('chat', [
            'pageTitle' => 'Chat - OpenAI Webchat',
            'threads' => $threads,
            'currentThread' => $currentThread,
            'messages' => $messages,
            'availableAgents' => $availableAgents,
            'selectedAgentId' => $selectedAgentId
        ]);
    }
}