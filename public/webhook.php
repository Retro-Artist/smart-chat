<?php
// public/webhook.php - Evolution API Webhook Endpoint

// Disable output buffering and error display for webhook performance
ini_set('output_buffering', 'off');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Load configuration
    require_once '../config/config.php';
    require_once '../src/Core/Logger.php';
    require_once '../src/Api/WhatsApp/WebhookHandler.php';
    
    // Validate webhook security (optional authentication)
    $headers = getallheaders();
    $apikey = $headers['apikey'] ?? $headers['Authorization'] ?? $_GET['apikey'] ?? null;
    
    if (defined('EVOLUTION_API_KEY') && EVOLUTION_API_KEY && $apikey !== EVOLUTION_API_KEY) {
        Logger::getInstance()->warning('Webhook unauthorized access attempt', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'provided_key' => substr($apikey, 0, 10) . '...' // Only log first 10 chars for security
        ]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get raw POST data
    $rawPayload = file_get_contents('php://input');
    
    if (empty($rawPayload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Decode JSON payload
    $payload = json_decode($rawPayload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        Logger::getInstance()->error('Webhook invalid JSON payload', [
            'json_error' => json_last_error_msg(),
            'raw_payload' => substr($rawPayload, 0, 500) // Only log first 500 chars
        ]);
        
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Extract instance name from payload or URL
    $instanceName = $payload['instance'] ?? $_GET['instance'] ?? null;
    
    if (!$instanceName) {
        http_response_code(400);
        echo json_encode(['error' => 'Instance name required']);
        exit;
    }
    
    // Log webhook reception (for debugging)
    Logger::getInstance()->info('Webhook received', [
        'instance' => $instanceName,
        'event' => $payload['event'] ?? 'unknown',
        'payload_size' => strlen($rawPayload)
    ]);
    
    // Process webhook with handler
    $handler = new WebhookHandler();
    $result = $handler->handleWebhook($instanceName, $payload);
    
    // Return success response immediately (webhook processing is async)
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed',
        'processed' => $result
    ]);
    
} catch (Exception $e) {
    // Log error but don't expose details to webhook sender
    Logger::getInstance()->error('Webhook processing error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'payload' => substr($rawPayload ?? '', 0, 500)
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

// Ensure we exit immediately for webhook performance
exit;