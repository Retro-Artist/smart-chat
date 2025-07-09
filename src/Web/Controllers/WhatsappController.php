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
     * Show QR code connection page (GET) and handle form submissions (POST)
     */
    public function connect()
    {
        Helpers::requireWebAuth();
        
        $userId = Helpers::getCurrentUserId();
        
        // Handle POST form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleFormSubmission($userId);
            return;
        }
        
        // Handle GET request - show the page
        $existingInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        $allInstances = $this->whatsappInstance->getUserInstances($userId);
        
        // Update connection status for all instances before displaying
        foreach ($allInstances as &$instance) {
            if ($instance['status'] === 'connecting' || $instance['status'] === 'connected') {
                // Check current connection status with Evolution API
                $statusResult = $this->whatsappInstance->getConnectionStatus($instance['instance_name']);
                if ($statusResult['success']) {
                    // Status has been updated in database, refresh the instance data
                    $instance['status'] = $statusResult['status'];
                    if (isset($statusResult['stats']['phone_number'])) {
                        $instance['phone_number'] = $statusResult['stats']['phone_number'];
                    }
                    if (isset($statusResult['stats']['profile_name'])) {
                        $instance['profile_name'] = $statusResult['stats']['profile_name'];
                    }
                }
            }
        }
        
        // Refresh active instance data if it was updated
        if ($existingInstance) {
            $existingInstance = $this->whatsappInstance->getUserActiveInstance($userId);
        }
        
        Helpers::loadView('whatsapp-connect', [
            'pageTitle' => 'Connect WhatsApp - Smart Chat',
            'existingInstance' => $existingInstance,
            'allInstances' => $allInstances
        ]);
    }
    
    /**
     * Handle form submissions from the connect page
     */
    private function handleFormSubmission($userId)
    {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $this->handleCreateInstance($userId);
                break;
                
            case 'get_qr':
                $this->handleGetQRCode($userId);
                break;
                
            case 'delete':
                $this->handleDeleteInstance($userId);
                break;
                
            default:
                $this->redirectWithError('Invalid action');
        }
    }
    
    /**
     * Handle create instance form submission
     */
    private function handleCreateInstance($userId)
    {
        $result = $this->whatsappInstance->create($userId);
        
        if ($result['success']) {
            $instanceName = $result['instance_name'];
            
            // Wait a moment for instance to be ready
            sleep(3);
            
            // Try to get QR code immediately after creation
            $qrResult = $this->whatsappInstance->getQRCode($instanceName);
            
            if ($qrResult['success']) {
                $_SESSION['whatsapp_qr_code'] = $qrResult['qr_code'];
                $_SESSION['whatsapp_current_instance'] = $instanceName;
                $this->redirectWithSuccess("Instance created and QR code generated: " . $instanceName);
            } else {
                // Instance created but QR code not ready yet
                $this->redirectWithSuccess("Instance created: " . $instanceName . ". Click 'Get QR Code' to connect.");
            }
        } else {
            $this->redirectWithError("Failed to create instance: " . $result['error']);
        }
    }
    
    /**
     * Handle get QR code form submission
     */
    private function handleGetQRCode($userId)
    {
        $instanceName = $_POST['instance_name'] ?? '';
        
        if (!$instanceName) {
            $this->redirectWithError('Instance name required');
            return;
        }
        
        // Verify ownership
        $instance = $this->whatsappInstance->findByName($instanceName);
        if (!$instance || $instance['user_id'] != $userId) {
            $this->redirectWithError('Instance not found or access denied');
            return;
        }
        
        $qrResult = $this->whatsappInstance->getQRCode($instanceName);
        
        if ($qrResult['success']) {
            $_SESSION['whatsapp_qr_code'] = $qrResult['qr_code'];
            $_SESSION['whatsapp_current_instance'] = $instanceName;
            $this->redirectWithSuccess("QR code generated for: " . $instanceName);
        } else {
            $this->redirectWithError("Failed to get QR code: " . $qrResult['error']);
        }
    }
    
    /**
     * Handle delete instance form submission
     */
    private function handleDeleteInstance($userId)
    {
        $instanceName = $_POST['instance_name'] ?? '';
        
        if (!$instanceName) {
            $this->redirectWithError('Instance name required');
            return;
        }
        
        $deleteResult = $this->whatsappInstance->delete($instanceName, $userId);
        
        if ($deleteResult['success']) {
            $this->redirectWithSuccess("Instance deleted: " . $instanceName);
        } else {
            $this->redirectWithError("Failed to delete instance: " . $deleteResult['error']);
        }
    }
    
    /**
     * Redirect with success message
     */
    private function redirectWithSuccess($message)
    {
        $_SESSION['whatsapp_success'] = $message;
        header('Location: /whatsapp/connect');
        exit;
    }
    
    /**
     * Redirect with error message
     */
    private function redirectWithError($message)
    {
        $_SESSION['whatsapp_error'] = $message;
        header('Location: /whatsapp/connect');
        exit;
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
