<?php
// src/Web/Controllers/AgentController.php  

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/Models/Agent.php';

class AgentController {
    
    public function index() {
        // Authentication is now handled by router-level authentication gate
        
        // Get user's agents
        $agents = Agent::getUserAgents(Helpers::getCurrentUserId());
        
        // Load agent management view
        Helpers::loadView('agents', [
            'pageTitle' => 'Agents - OpenAI Webchat',
            'agents' => $agents
        ]);
    }
}