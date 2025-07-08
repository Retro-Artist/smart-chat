<?php
// src/Web/Controllers/ChatController.php - FIXED for thread switching

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/Models/Thread.php';
require_once __DIR__ . '/../../Api/Models/Agent.php';

class ChatController {
    
    public function index() {
        // Check if user is logged in
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Get user's threads
        $threads = Thread::getUserThreads($userId);
        
        // Determine current thread
        $currentThread = null;
        $requestedThreadId = $_GET['thread'] ?? null;
        
        if ($requestedThreadId && is_numeric($requestedThreadId)) {
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
        if (isset($_GET['agent']) && is_numeric($_GET['agent'])) {
            $requestedAgent = Agent::findById($_GET['agent']);
            if ($requestedAgent && $requestedAgent->getUserId() == $userId) {
                $selectedAgentId = $_GET['agent'];
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