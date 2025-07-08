<?php
// src/Web/Controllers/WhatsAppController.php

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/WebhookHandler.php';

class WhatsAppController
{
    private $whatsappInstance;

    public function __construct()
    {
        $this->whatsappInstance = new WhatsAppInstance();
    }

    /**
     * Show QR code connection page
     */
    public function connect()
    {
        // Check if user is logged in
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Check if user already has an active instance
        $existingInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        
        // Get all user instances
        $allInstances = $this->whatsappInstance->getUserInstances($userId);
        
        // Load QR connection view
        Helpers::loadView('whatsapp-connect', [
            'pageTitle' => 'Connect WhatsApp - Smart Chat',
            'existingInstance' => $existingInstance,
            'allInstances' => $allInstances
        ]);
    }

    /**
     * Create new WhatsApp instance
     */
    public function createInstance()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Create instance
        $result = $this->whatsappInstance->create($userId);
        
        if ($result['success']) {
            Helpers::jsonResponse([
                'success' => true,
                'instance' => $result,
                'message' => 'WhatsApp instance created successfully'
            ]);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Get QR code for instance
     */
    public function getQRCode()
    {
        Helpers::requireWebAuth();
        
        $instanceName = $_GET['instance'] ?? '';
        
        if (!$instanceName) {
            Helpers::jsonError('Instance name required', 400);
        }

        // Verify ownership
        $userId = Helpers::getCurrentUserId();
        $instance = $this->whatsappInstance->findByName($instanceName);
        
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Get QR code
        $result = $this->whatsappInstance->getQRCode($instanceName);
        
        if ($result['success']) {
            Helpers::jsonResponse($result);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Get instance connection status
     */
    public function getStatus()
    {
        Helpers::requireWebAuth();
        
        $instanceName = $_GET['instance'] ?? '';
        
        if (!$instanceName) {
            Helpers::jsonError('Instance name required', 400);
        }

        // Verify ownership
        $userId = Helpers::getCurrentUserId();
        $instance = $this->whatsappInstance->findByName($instanceName);
        
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Get status
        $result = $this->whatsappInstance->getConnectionStatus($instanceName);
        
        if ($result['success']) {
            // Include instance stats
            $stats = $this->whatsappInstance->getStats($instance['id']);
            $result['stats'] = $stats;
            
            Helpers::jsonResponse($result);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Delete instance
     */
    public function deleteInstance()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['instance_name']);
        
        $userId = Helpers::getCurrentUserId();
        $instanceName = $input['instance_name'];

        // Delete instance
        $result = $this->whatsappInstance->delete($instanceName, $userId);
        
        if ($result['success']) {
            Helpers::jsonResponse($result);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Restart instance
     */
    public function restartInstance()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['instance_name']);
        
        $userId = Helpers::getCurrentUserId();
        $instanceName = $input['instance_name'];

        // Verify ownership
        $instance = $this->whatsappInstance->findByName($instanceName);
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Restart instance
        $result = $this->whatsappInstance->restart($instanceName);
        
        if ($result['success']) {
            Helpers::jsonResponse($result);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Update instance settings
     */
    public function updateSettings()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['instance_id', 'settings']);
        
        $userId = Helpers::getCurrentUserId();
        $instanceId = $input['instance_id'];
        $settings = $input['settings'];

        // Verify ownership
        $instance = $this->whatsappInstance->findById($instanceId);
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Update settings
        $result = $this->whatsappInstance->updateSettings($instanceId, $settings);
        
        if ($result) {
            Helpers::jsonResponse([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } else {
            Helpers::jsonError('Failed to update settings', 500);
        }
    }

    /**
     * Get instance list for user
     */
    public function listInstances()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        $instances = $this->whatsappInstance->getUserInstances($userId);
        
        // Add stats to each instance
        foreach ($instances as &$instance) {
            $instance['stats'] = $this->whatsappInstance->getStats($instance['id']);
        }
        
        Helpers::jsonResponse([
            'success' => true,
            'instances' => $instances
        ]);
    }

    /**
     * Handle Evolution API webhooks
     */
    public function webhook()
    {
        try {
            // Get webhook data
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }

            // Handle webhook
            $webhookHandler = new WebhookHandler();
            $result = $webhookHandler->handle($data);
            
            // Return response
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]);
        }
    }

    /**
     * Dashboard widget for WhatsApp status
     */
    public function dashboardWidget()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Get active instance
        $activeInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        
        $widgetData = [
            'connected' => false,
            'instance' => null,
            'stats' => null
        ];
        
        if ($activeInstance) {
            $widgetData['connected'] = $activeInstance['status'] === 'connected';
            $widgetData['instance'] = $activeInstance;
            $widgetData['stats'] = $this->whatsappInstance->getStats($activeInstance['id']);
        }
        
        Helpers::jsonResponse([
            'success' => true,
            'widget' => $widgetData
        ]);
    }

    /**
     * Test connection to Evolution API
     */
    public function testConnection()
    {
        Helpers::requireWebAuth();
        
        try {
            $config = require __DIR__ . '/../../../config/config.php';
            
            require_once __DIR__ . '/../../Api/EvolutionAPI/EvolutionAPI.php';
            
            $api = new EvolutionAPI(
                $config['evolutionAPI']['api_url'],
                $config['evolutionAPI']['api_key'],
                'test-connection'
            );
            
            $result = $api->getInformation();
            
            Helpers::jsonResponse([
                'success' => $result['success'],
                'api_status' => $result['success'] ? 'connected' : 'disconnected',
                'api_info' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ]);
            
        } catch (Exception $e) {
            Helpers::jsonResponse([
                'success' => false,
                'api_status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get WhatsApp contacts for user
     */
    public function getContacts()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Get threads that are WhatsApp contacts
        $db = Database::getInstance();
        $contacts = $db->fetchAll("
            SELECT 
                t.id as thread_id,
                t.whatsapp_contact_jid,
                t.contact_name,
                t.contact_phone,
                t.contact_avatar,
                t.message_count,
                t.last_message_at,
                wi.instance_name,
                wi.status as instance_status
            FROM threads t
            JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.user_id = ? AND t.is_whatsapp_thread = 1
            ORDER BY t.last_message_at DESC
        ", [$userId]);
        
        Helpers::jsonResponse([
            'success' => true,
            'contacts' => $contacts
        ]);
    }

    /**
     * Toggle AI auto-response for specific contact
     */
    public function toggleAutoResponse()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['thread_id', 'auto_respond']);
        
        $userId = Helpers::getCurrentUserId();
        $threadId = $input['thread_id'];
        $autoRespond = (bool)$input['auto_respond'];

        // Verify thread ownership
        if (!Thread::belongsToUser($threadId, $userId)) {
            Helpers::jsonError('Thread not found or access denied', 404);
        }

        // Get thread info
        $thread = Thread::findById($threadId);
        if (!$thread || !$thread['is_whatsapp_thread']) {
            Helpers::jsonError('Not a WhatsApp thread', 400);
        }

        // Update or create routing rule
        $db = Database::getInstance();
        $existing = $db->fetch(
            "SELECT id FROM conversation_routing WHERE instance_id = ? AND contact_jid = ?",
            [$thread['whatsapp_instance_id'], $thread['whatsapp_contact_jid']]
        );

        if ($existing) {
            $db->update('conversation_routing', [
                'auto_respond' => $autoRespond,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('conversation_routing', [
                'instance_id' => $thread['whatsapp_instance_id'],
                'contact_jid' => $thread['whatsapp_contact_jid'],
                'auto_respond' => $autoRespond,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        Helpers::jsonResponse([
            'success' => true,
            'message' => 'Auto-response setting updated'
        ]);
    }
}