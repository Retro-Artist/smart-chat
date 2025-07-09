<?php
// src/Web/Controllers/WhatsAppController.php - FINAL CLEAN VERSION

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/WebhookHandler.php';

class WhatsAppController
{
    private $whatsappInstance;
    private $db;

    public function __construct()
    {
        $this->whatsappInstance = new WhatsAppInstance();
        $this->db = Database::getInstance();
    }

    /**
     * Show QR code connection page
     */
    public function connect()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Use ONLY existing methods
        $existingInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        $allInstances = $this->whatsappInstance->getUserInstances($userId);
        
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
        
        // Use existing create method
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

        // Verify ownership using existing findByName method
        $userId = Helpers::getCurrentUserId();
        $instance = $this->whatsappInstance->findByName($instanceName);
        
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Use existing getQRCode method
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

        // Verify ownership using existing method
        $userId = Helpers::getCurrentUserId();
        $instance = $this->whatsappInstance->findByName($instanceName);
        
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Use existing getConnectionStatus method
        $result = $this->whatsappInstance->getConnectionStatus($instanceName);
        
        if ($result['success']) {
            // Add basic stats from the instance data itself
            $result['stats'] = [
                'instance_id' => $instance['id'],
                'created_at' => $instance['created_at'],
                'last_seen' => $instance['last_seen'],
                'phone_number' => $instance['phone_number'],
                'profile_name' => $instance['profile_name']
            ];
            
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

        // Use existing delete method
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

        // Verify ownership first
        $instance = $this->whatsappInstance->findByName($instanceName);
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Use existing restart method
        $result = $this->whatsappInstance->restart($instanceName);
        
        if ($result['success']) {
            Helpers::jsonResponse($result);
        } else {
            Helpers::jsonError($result['error'], 500);
        }
    }

    /**
     * Update instance settings - Using direct database update
     */
    public function updateSettings()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['instance_name', 'settings']);
        
        $userId = Helpers::getCurrentUserId();
        $instanceName = $input['instance_name'];
        $newSettings = $input['settings'];

        // Verify ownership
        $instance = $this->whatsappInstance->findByName($instanceName);
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Merge settings with existing ones
        $currentSettings = json_decode($instance['settings'] ?? '{}', true);
        $updatedSettings = array_merge($currentSettings, $newSettings);

        // Update using existing updateByName method
        $result = $this->whatsappInstance->updateByName($instanceName, [
            'settings' => json_encode($updatedSettings)
        ]);
        
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
     * Test Evolution API connection
     */
    public function testConnection()
    {
        try {
            $config = require __DIR__ . '/../../../config/config.php';
            
            require_once __DIR__ . '/../../Api/EvolutionAPI/EvolutionAPI.php';
            
            $api = new EvolutionAPI(
                $config['evolutionAPI']['api_url'],
                $config['evolutionAPI']['api_key'],
                'test-connection'
            );
            
            $result = $api->getInformation();
            
            if ($result['success']) {
                Helpers::jsonResponse([
                    'success' => true,
                    'api_status' => 'connected',
                    'api_info' => $result['data'] ?? null,
                    'message' => 'Evolution API connection successful'
                ]);
            } else {
                Helpers::jsonResponse([
                    'success' => false,
                    'api_status' => 'disconnected',
                    'error' => $result['error'] ?? 'Unknown error',
                    'message' => 'Evolution API connection failed'
                ]);
            }
            
        } catch (Exception $e) {
            Helpers::jsonResponse([
                'success' => false,
                'api_status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'Evolution API connection error'
            ]);
        }
    }

    /**
     * List user instances
     */
    public function listInstances()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Use existing method
        $instances = $this->whatsappInstance->getUserInstances($userId);
        
        Helpers::jsonResponse([
            'success' => true,
            'instances' => $instances
        ]);
    }

    /**
     * Get WhatsApp contacts
     */
    public function getContacts()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Direct database query since no specific method exists
        $contacts = $this->db->fetchAll("
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
     * Toggle auto-response
     */
    public function toggleAutoResponse()
    {
        Helpers::requireWebAuth();
        
        $input = Helpers::getJsonInput();
        Helpers::validateRequired($input, ['instance_name', 'auto_respond']);
        
        $userId = Helpers::getCurrentUserId();
        $instanceName = $input['instance_name'];
        $autoRespond = (bool)$input['auto_respond'];

        // Verify ownership
        $instance = $this->whatsappInstance->findByName($instanceName);
        if (!$instance || $instance['user_id'] != $userId) {
            Helpers::jsonError('Instance not found or access denied', 404);
        }

        // Update settings using existing method
        $currentSettings = json_decode($instance['settings'] ?? '{}', true);
        $currentSettings['auto_respond'] = $autoRespond;

        $result = $this->whatsappInstance->updateByName($instanceName, [
            'settings' => json_encode($currentSettings)
        ]);

        if ($result) {
            Helpers::jsonResponse([
                'success' => true,
                'message' => 'Auto-response setting updated'
            ]);
        } else {
            Helpers::jsonError('Failed to update setting', 500);
        }
    }

    /**
     * Dashboard widget data
     */
    public function dashboardWidget()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Use existing methods
        $activeInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        $allInstances = $this->whatsappInstance->getUserInstances($userId);
        
        // Count contacts directly from database
        $totalContacts = $this->db->fetch(
            "SELECT COUNT(*) as count FROM threads WHERE user_id = ? AND is_whatsapp_thread = 1",
            [$userId]
        )['count'] ?? 0;
        
        Helpers::jsonResponse([
            'success' => true,
            'data' => [
                'active_instances' => count(array_filter($allInstances, fn($i) => $i['status'] === 'connected')),
                'total_instances' => count($allInstances),
                'total_contacts' => $totalContacts,
                'active_instance' => $activeInstance
            ]
        ]);
    }

    /**
     * Webhook handler
     */
    public function webhook()
    {
        // Get webhook data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }
        
        try {
            $handler = new WebhookHandler();
            $result = $handler->handle($data);
            
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]);
        }
    }
}