<?php
// Simple webhook test to isolate the issue

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo json_encode(['status' => 'test started']);

try {
    require_once '../config/config.php';
    echo json_encode(['status' => 'config loaded']);
    
    require_once '../src/Core/Logger.php';
    echo json_encode(['status' => 'logger loaded']);
    
    Logger::getInstance()->info("Test webhook started");
    echo json_encode(['status' => 'logger working']);
    
    require_once '../src/Api/WhatsApp/WebhookHandler.php';
    echo json_encode(['status' => 'handler loaded']);
    
    $handler = new WebhookHandler();
    echo json_encode(['status' => 'handler created']);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>