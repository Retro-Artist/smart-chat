<?php
// src/Api/OpenAI/ToolsAPI.php  

require_once __DIR__ . '/../../Core/Helpers.php';

class ToolsAPI {
    
    public function getTools() {
        Helpers::requireAuth();
        
        try {
            $availableTools = [
                [
                    'name' => 'Math',
                    'class' => 'Math',
                    'description' => 'Perform mathematical calculations safely'
                ],
                [
                    'name' => 'Search',
                    'class' => 'Search',
                    'description' => 'Search the web for current information'
                ],
                [
                    'name' => 'Weather',
                    'class' => 'Weather',
                    'description' => 'Get current weather information for any location'
                ],
                [
                    'name' => 'ReadPDF',
                    'class' => 'ReadPDF',
                    'description' => 'Extract text and information from PDF files'
                ]
            ];
            
            Helpers::jsonResponse($availableTools);
        } catch (Exception $e) {
            error_log("Error fetching tools: " . $e->getMessage());
            Helpers::jsonError('Failed to fetch available tools', 500);
        }
    }
    
    public function executeTool($toolName) {
        Helpers::requireAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['parameters']);
        
        try {
            // Load the tool - UPDATED PATH
            $toolFile = __DIR__ . "/../Tools/{$toolName}.php";
            
            if (!file_exists($toolFile)) {
                Helpers::jsonError("Tool not found: {$toolName}", 404);
            }
            
            require_once $toolFile;
            
            if (!class_exists($toolName)) {
                Helpers::jsonError("Tool class not found: {$toolName}", 404);
            }
            
            $tool = new $toolName();
            $result = $tool->safeExecute($input['parameters']);
            
            Helpers::jsonResponse($result);
        } catch (Exception $e) {
            error_log("Error executing tool {$toolName}: " . $e->getMessage());
            Helpers::jsonError('Failed to execute tool: ' . $e->getMessage(), 500);
        }
    }
}