<?php
// src/Api/WhatsApp/WebhookController.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/WebhookHandler.php';

class WebhookController {
    private $handler;
    
    public function __construct() {
        $this->handler = new WebhookHandler();
    }
    
    public function handle() {
        // Set response headers for quick response
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            return;
        }
        
        // Get raw input
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Empty request body'
            ]);
            return;
        }
        
        // Parse JSON
        $payload = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg()
            ]);
            return;
        }
        
        // Handle the webhook using WebhookHandler
        $this->handler->handleWebhook($payload);
    }
    
    public static function handleRequest() {
        $controller = new self();
        $controller->handle();
    }
}