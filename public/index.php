<?php
// public/index.php

// Start output buffering to catch any accidental output
ob_start();

// Start session
session_start();

// For API requests, set JSON header early and disable error display
$isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
if ($isApiRequest) {
    header('Content-Type: application/json');
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Load configuration
require_once '../config/config.php';
require_once '../src/Core/Router.php';

// Clean any output buffer before proceeding
if ($isApiRequest) {
    ob_clean();
}

// Initialize router
$router = new Router();

// ===================================
// WEB ROUTES (HTML Pages)
// ===================================

// Existing routes
$router->addWebRoute('GET', '/', 'HomeController@index');
$router->addWebRoute('GET', '/chat', 'ChatController@index');
$router->addWebRoute('GET', '/dashboard', 'DashboardController@index');
$router->addWebRoute('GET', '/agents', 'AgentController@index');
$router->addWebRoute('GET', '/login', 'AuthController@showLogin');
$router->addWebRoute('POST', '/login', 'AuthController@processLogin');
$router->addWebRoute('GET', '/register', 'AuthController@showRegister');
$router->addWebRoute('POST', '/register', 'AuthController@processRegister');
$router->addWebRoute('GET', '/logout', 'AuthController@logout');

// WhatsApp Web Routes (only if WhatsApp is enabled)
if (defined('WHATSAPP_ENABLED') && WHATSAPP_ENABLED) {
    $router->addWebRoute('GET', '/whatsapp', 'WhatsAppController@index');
    $router->addWebRoute('GET', '/whatsapp/connect', 'WhatsAppController@connect');
    $router->addWebRoute('POST', '/whatsapp/connect', 'WhatsAppController@connect');
    $router->addWebRoute('GET', '/whatsapp/chat', 'WhatsAppController@chat');
}

// ===================================
// API ROUTES (JSON Responses)
// ===================================

// Existing OpenAI API routes
$router->addApiRoute('GET', '/api/threads', 'ThreadsAPI@getThreads');
$router->addApiRoute('POST', '/api/threads', 'ThreadsAPI@createThread');
$router->addApiRoute('GET', '/api/threads/{id}', 'ThreadsAPI@getThread');
$router->addApiRoute('PUT', '/api/threads/{id}', 'ThreadsAPI@updateThread');
$router->addApiRoute('DELETE', '/api/threads/{id}', 'ThreadsAPI@deleteThread');
$router->addApiRoute('GET', '/api/threads/{id}/messages', 'ThreadsAPI@getMessages');
$router->addApiRoute('POST', '/api/threads/{id}/messages', 'ThreadsAPI@sendMessage');

$router->addApiRoute('GET', '/api/agents', 'AgentsAPI@getAgents');
$router->addApiRoute('POST', '/api/agents', 'AgentsAPI@createAgent');
$router->addApiRoute('GET', '/api/agents/{id}', 'AgentsAPI@getAgent');
$router->addApiRoute('PUT', '/api/agents/{id}', 'AgentsAPI@updateAgent');
$router->addApiRoute('DELETE', '/api/agents/{id}', 'AgentsAPI@deleteAgent');
$router->addApiRoute('POST', '/api/agents/{id}/run', 'AgentsAPI@runAgent');

$router->addApiRoute('GET', '/api/tools', 'ToolsAPI@getTools');
$router->addApiRoute('POST', '/api/tools/{name}/execute', 'ToolsAPI@executeTool');

$router->addApiRoute('GET', '/api/system/status', 'SystemAPI@getStatus');
$router->addApiRoute('GET', '/api/system/config', 'SystemAPI@getConfig');

// WhatsApp API Routes (only if WhatsApp is enabled)
if (defined('WHATSAPP_ENABLED') && WHATSAPP_ENABLED) {
    // CONSOLIDATED ENDPOINTS - Updated to use new unified functions
    $router->addApiRoute('POST', '/whatsapp/generateQR', 'WhatsAppController@generateQR');              // Unified QR generation
    $router->addApiRoute('GET', '/whatsapp/getConnectionStatus', 'WhatsAppController@getConnectionStatus'); // Unified status check
    $router->addApiRoute('GET', '/whatsapp/connectionStatusStream', 'WhatsAppController@connectionStatusStream'); // Real-time SSE
    
    // Instance management
    $router->addApiRoute('POST', '/whatsapp/createInstance', 'WhatsAppController@createInstance');
    $router->addApiRoute('POST', '/whatsapp/restartInstance', 'WhatsAppController@restartInstance');
    $router->addApiRoute('POST', '/whatsapp/disconnect', 'WhatsAppController@disconnect');
    
    // Alternative API paths for backwards compatibility
    $router->addApiRoute('POST', '/api/whatsapp/qr', 'WhatsAppController@generateQR');
    $router->addApiRoute('GET', '/api/whatsapp/status', 'WhatsAppController@getConnectionStatus');
    $router->addApiRoute('POST', '/api/whatsapp/restart', 'WhatsAppController@restartInstance');
    $router->addApiRoute('POST', '/api/whatsapp/disconnect', 'WhatsAppController@disconnect');
    
    // Messaging
    $router->addApiRoute('POST', '/api/whatsapp/send', 'WhatsAppController@sendMessage');
    $router->addApiRoute('GET', '/api/whatsapp/messages', 'WhatsAppController@getMessages');
    $router->addApiRoute('GET', '/api/whatsapp/conversations', 'WhatsAppController@getConversations');
    $router->addApiRoute('GET', '/api/whatsapp/contacts', 'WhatsAppController@getContacts');
    
    // Sync operations
    $router->addApiRoute('POST', '/api/whatsapp/sync', 'WhatsAppController@syncData');
    $router->addApiRoute('POST', '/api/whatsapp/refreshContacts', 'WhatsAppController@refreshContacts');
    $router->addApiRoute('GET', '/api/whatsapp/syncStatus', 'WhatsAppController@getSyncStatus');
}

// ===================================
// HANDLE REQUEST
// ===================================

try {
    $router->handleRequest();
} catch (Exception $e) {
    if ($isApiRequest) {
        // Clean any output and return clean JSON error
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error'
        ]);
    } else {
        // Load error view for web requests
        http_response_code(500);
        include '../src/Web/Views/error.php';
    }
    
    // Log the error
    error_log("Router error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}