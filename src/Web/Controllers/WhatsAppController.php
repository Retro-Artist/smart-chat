<?php
// src/Web/Controllers/WhatsAppController.php

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/../../Api/Models/WhatsAppInstance.php';
require_once __DIR__ . '/../../Api/Models/WhatsAppContact.php';
require_once __DIR__ . '/../../Api/Models/WhatsAppMessage.php';
require_once __DIR__ . '/../../Api/WhatsApp/InstanceManager.php';
require_once __DIR__ . '/../../Api/WhatsApp/EvolutionAPI.php';

class WhatsAppController {
    private $instanceManager;
    private $instanceModel;
    private $contactModel;
    private $messageModel;
    
    public function __construct() {
        if (!Helpers::isAuthenticated()) {
            Helpers::redirect('/login');
        }
        
        if (!WHATSAPP_ENABLED) {
            Helpers::redirect('/dashboard');
        }
        
        $this->instanceManager = new InstanceManager();
        $this->instanceModel = new WhatsAppInstance();
        $this->contactModel = new WhatsAppContact();
        $this->messageModel = new WhatsAppMessage();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $instance = $this->instanceModel->findByUserId($userId);
        
        if (!$instance) {
            // No instance exists - redirect to connect
            Helpers::redirect('/whatsapp/connect');
        }
        
        switch ($instance['status']) {
            case WhatsAppInstance::STATUS_CONNECTED:
                Helpers::redirect('/whatsapp/chat');
                break;
                
            case WhatsAppInstance::STATUS_CONNECTING:
            case WhatsAppInstance::STATUS_CREATING:
                Helpers::redirect('/whatsapp/connect');
                break;
                
            default:
                Helpers::redirect('/whatsapp/connect');
                break;
        }
    }
    
    public function connect() {
        $userId = $_SESSION['user_id'];
        $instance = $this->instanceModel->findByUserId($userId);
        $error = $_GET['error'] ?? null;
        $currentState = $_GET['state'] ?? 'unknown';
        
        $data = [
            'pageTitle' => 'Connect WhatsApp - Smart Chat',
            'instance' => $instance,
            'error' => $error,
            'is_first_login' => isset($_SESSION['whatsapp_first_login']),
            'connection_state' => $currentState
        ];
        
        
        // If instance exists, get real-time connection state
        if ($instance) {
            try {
                $evolutionAPI = new EvolutionAPI();
                $realTimeState = $evolutionAPI->getConnectionState($instance['instance_name']);
                $currentState = $realTimeState;
                $data['connection_state'] = $currentState;
                
                // If already connected, auto-redirect to dashboard after brief display
                if ($currentState === 'open') {
                    $data['auto_redirect'] = true;
                    $data['redirect_url'] = '/dashboard';
                    $data['redirect_delay'] = 2000; // 2 seconds
                }
            } catch (Exception $e) {
                Logger::getInstance()->error("Failed to get real-time connection state: " . $e->getMessage());
                $currentState = 'unknown';
            }
        }
        
        // Smart QR code management based on connection state
        $qrData = null;
        if ($instance && $currentState !== 'open') {
            $qrData = $this->getSmartQRCode($instance, $currentState);
            $data['qr_code'] = $qrData;
        }
        
        Helpers::loadView('wa_connect', $data);
    }
    
    /**
     * Smart QR code generation based on connection state
     */
    private function getSmartQRCode($instance, $connectionState) {
        require_once __DIR__ . '/../../Core/Redis.php';
        $redis = Redis::getInstance();
        $cacheKey = "qr:{$instance['instance_name']}";
        
        try {
            $evolutionAPI = new EvolutionAPI();
            $needsRestart = $this->shouldRestartInstance($connectionState);
            
            // Handle different connection states intelligently
            switch ($connectionState) {
                case 'connecting':
                    // Use existing QR code if available and not expired
                    $qrData = $redis->get($cacheKey);
                    if ($qrData) {
                        Logger::getInstance()->info("Using existing QR code for connecting instance", [
                            'instance' => $instance['instance_name']
                        ]);
                        return $qrData;
                    }
                    // If no cached QR, generate fresh one
                    break;
                    
                case 'disconnected':
                case 'failed':
                    // Restart instance first, then generate new QR
                    Logger::getInstance()->info("Restarting instance due to state: {$connectionState}", [
                        'instance' => $instance['instance_name']
                    ]);
                    $evolutionAPI->restartInstance($instance['instance_name']);
                    // Wait for restart to complete
                    sleep(2);
                    // Clear old QR cache
                    $redis->delete($cacheKey);
                    break;
                    
                case 'close':
                case 'unknown':
                default:
                    // Generate fresh QR code
                    $redis->delete($cacheKey);
                    break;
            }
            
            // Generate new QR code
            $response = $evolutionAPI->connectInstance($instance['instance_name']);
            
            if (isset($response['base64'])) {
                $qrData = $response['base64'];
                // Cache QR code for 2 minutes
                $redis->set($cacheKey, $qrData, 120);
                
                Logger::getInstance()->info("Generated new QR code", [
                    'instance' => $instance['instance_name'],
                    'state' => $connectionState,
                    'restarted' => $needsRestart
                ]);
                
                return $qrData;
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error('Smart QR generation failed: ' . $e->getMessage(), [
                'instance' => $instance['instance_name'],
                'state' => $connectionState
            ]);
        }
        
        return null;
    }
    
    /**
     * Determine if instance needs restart based on connection state
     */
    private function shouldRestartInstance($connectionState) {
        $restartStates = ['disconnected', 'failed', 'close'];
        return in_array($connectionState, $restartStates);
    }
    
    public function chat() {
        // Check if user is logged in and WhatsApp is connected
        require_once __DIR__ . '/../../Core/Security.php';
        Security::requireWhatsAppConnection();
        
        $userId = $_SESSION['user_id'];
        $instance = $this->instanceModel->findByUserId($userId);
        
        // Queue contact sync if not done recently (check last sync time)
        if ($this->shouldSyncContacts($instance['id'])) {
            require_once __DIR__ . '/../../Core/MessageQueue.php';
            $queue = new MessageQueue();
            $queue->push('sync_contacts', [
                'instance_id' => $instance['id'],
                'full_sync' => false
            ], MessageQueue::PRIORITY_NORMAL, $instance['id']);
        }
        
        $selectedContact = $_GET['contact'] ?? null;
        $contacts = $this->contactModel->findByInstance($instance['id'], true);
        $recentContacts = $this->contactModel->getRecentContacts($instance['id'], 20);
        
        $messages = [];
        $contactInfo = null;
        
        if ($selectedContact) {
            $contactInfo = $this->contactModel->findByInstanceAndPhone($instance['id'], $selectedContact);
            $messages = $this->messageModel->findConversation($instance['id'], $selectedContact, 50);
            $messages = array_reverse($messages); // Show oldest first
            
            // Mark messages as read
            $this->messageModel->markAsRead($instance['id'], $selectedContact);
        }
        
        $data = [
            'pageTitle' => 'WhatsApp Chat - Smart Chat',
            'instance' => $instance,
            'contacts' => $contacts,
            'recent_contacts' => $recentContacts,
            'selected_contact' => $selectedContact,
            'contact_info' => $contactInfo,
            'messages' => $messages,
            'user_phone' => $instance['phone_number']
        ];
        
        Helpers::loadView('wa_chat', $data);
    }
    
    public function checkStatus() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $status = $this->instanceManager->getInstanceStatus($instance['id']);
            
            echo json_encode([
                'success' => true,
                'status' => $status['status'],
                'phone_number' => $instance['phone_number'],
                'last_check' => $status['last_check'] ?? date('Y-m-d H:i:s'),
                'qr_valid' => $this->instanceModel->hasValidQRCode($instance['id'])
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function generateQRCode() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $qrData = $this->instanceManager->generateQRCode($instance['id']);
            
            echo json_encode([
                'success' => true,
                'qr_code' => $qrData['qr_code'],
                'generated_at' => $qrData['generated_at'],
                'expires_at' => $qrData['expires_at']
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function createInstance() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $phoneNumber = $_POST['phone_number'] ?? null;
            
            $existingInstance = $this->instanceModel->findByUserId($userId);
            if ($existingInstance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'WhatsApp instance already exists'
                ]);
                return;
            }
            
            $instance = $this->instanceManager->createInstance($userId, $phoneNumber);
            
            echo json_encode([
                'success' => true,
                'instance_id' => $instance['id'],
                'instance_name' => $instance['instance_name'],
                'status' => $instance['status']
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function restartInstance() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $this->instanceManager->restartInstance($instance['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Instance restarted successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function disconnect() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $this->instanceManager->disconnectInstance($instance['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Disconnected successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function sendMessage() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance || $instance['status'] !== WhatsAppInstance::STATUS_CONNECTED) {
                echo json_encode([
                    'success' => false,
                    'error' => 'WhatsApp instance not connected'
                ]);
                return;
            }
            
            $phoneNumber = $_POST['phone_number'] ?? '';
            $message = $_POST['message'] ?? '';
            
            if (empty($phoneNumber) || empty($message)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Phone number and message are required'
                ]);
                return;
            }
            
            $evolutionAPI = new EvolutionAPI();
            $response = $evolutionAPI->sendTextMessage($instance['instance_name'], $phoneNumber, $message);
            
            if (isset($response['key']['id'])) {
                $this->messageModel->create(
                    $instance['id'],
                    $response['key']['id'],
                    $instance['phone_number'],
                    $phoneNumber,
                    WhatsAppMessage::TYPE_TEXT,
                    $message,
                    true,
                    date('Y-m-d H:i:s')
                );
            }
            
            echo json_encode([
                'success' => true,
                'message_id' => $response['key']['id'] ?? null,
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getContacts() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $search = $_GET['search'] ?? '';
            
            if ($search) {
                $contacts = $this->contactModel->searchContacts($instance['id'], $search, true);
            } else {
                $contacts = $this->contactModel->findByInstance($instance['id'], true);
            }
            
            echo json_encode([
                'success' => true,
                'contacts' => $contacts
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getMessages() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $phoneNumber = $_GET['phone_number'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if (empty($phoneNumber)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Phone number is required'
                ]);
                return;
            }
            
            $messages = $this->messageModel->findConversation($instance['id'], $phoneNumber, $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'messages' => array_reverse($messages), // Show oldest first
                'has_more' => count($messages) === $limit
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getConversations() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            $conversations = $this->messageModel->findRecentConversations($instance['id'], 20);
            
            // Enrich with contact information
            foreach ($conversations as &$conversation) {
                $contact = $this->contactModel->findByInstanceAndPhone($instance['id'], $conversation['contact_phone']);
                $conversation['contact_name'] = $contact['name'] ?? $conversation['contact_phone'];
                $conversation['contact_profile_picture'] = $contact['profile_picture'] ?? null;
            }
            
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function syncData() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance || $instance['status'] !== WhatsAppInstance::STATUS_CONNECTED) {
                echo json_encode([
                    'success' => false,
                    'error' => 'WhatsApp instance not connected'
                ]);
                return;
            }
            
            $this->instanceManager->syncInstanceData($instance['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sync initiated successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function refreshContacts() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance || $instance['status'] !== WhatsAppInstance::STATUS_CONNECTED) {
                echo json_encode([
                    'success' => false,
                    'error' => 'WhatsApp instance not connected'
                ]);
                return;
            }
            
            // Queue manual contact refresh with high priority
            require_once __DIR__ . '/../../Core/MessageQueue.php';
            $queue = new MessageQueue();
            $queue->push('sync_contacts', [
                'instance_id' => $instance['id'],
                'full_sync' => true
            ], MessageQueue::PRIORITY_HIGH, $instance['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Contact refresh started'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getSyncStatus() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found'
                ]);
                return;
            }
            
            // Get sync status from WhatsApp sync logs
            require_once __DIR__ . '/../../Api/Models/WhatsAppSyncLog.php';
            $syncLogModel = new WhatsAppSyncLog();
            
            $contactSync = $syncLogModel->getLastSyncByType($instance['id'], WhatsAppSyncLog::TYPE_CONTACTS);
            $messageSync = $syncLogModel->getLastSyncByType($instance['id'], WhatsAppSyncLog::TYPE_MESSAGES);
            $groupSync = $syncLogModel->getLastSyncByType($instance['id'], WhatsAppSyncLog::TYPE_GROUPS);
            
            $status = [
                'instance_status' => $instance['status'],
                'last_sync' => $instance['last_sync_at'],
                'contacts' => [
                    'status' => $contactSync['status'] ?? 'never',
                    'last_sync' => $contactSync['completed_at'] ?? null,
                    'processed_count' => $contactSync['processed_count'] ?? 0,
                    'created_count' => $contactSync['created_count'] ?? 0,
                    'updated_count' => $contactSync['updated_count'] ?? 0
                ],
                'messages' => [
                    'status' => $messageSync['status'] ?? 'never',
                    'last_sync' => $messageSync['completed_at'] ?? null,
                    'processed_count' => $messageSync['processed_count'] ?? 0,
                    'created_count' => $messageSync['created_count'] ?? 0
                ],
                'groups' => [
                    'status' => $groupSync['status'] ?? 'never',
                    'last_sync' => $groupSync['completed_at'] ?? null,
                    'processed_count' => $groupSync['processed_count'] ?? 0,
                    'created_count' => $groupSync['created_count'] ?? 0
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'sync_status' => $status
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function checkConnectionStatus() {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => true,
                    'status' => 'no_instance'
                ]);
                return;
            }
            
            // Check cached status first
            require_once __DIR__ . '/../../Core/Redis.php';
            $redis = Redis::getInstance();
            $cacheKey = "connection:{$instance['instance_name']}";
            $cachedStatus = $redis->get($cacheKey);
            
            if ($cachedStatus) {
                echo json_encode([
                    'success' => true,
                    'status' => $cachedStatus,
                    'cached' => true
                ]);
                return;
            }
            
            // Get real-time status from Evolution API
            $evolutionAPI = new EvolutionAPI();
            $response = $evolutionAPI->findInstance($instance['instance_name']);
            
            $status = 'disconnected';
            if (isset($response['connectionStatus'])) {
                switch ($response['connectionStatus']) {
                    case 'open':
                        $status = 'connected';
                        break;
                    case 'connecting':
                        $status = 'connecting';
                        break;
                    case 'close':
                    default:
                        $status = 'disconnected';
                        break;
                }
            }
            
            // Cache the status for 30 seconds
            $redis->set($cacheKey, $status, 30);
            
            // Update database if status changed
            if ($instance['status'] !== $status) {
                $this->instanceModel->updateStatus($instance['id'], $status);
            }
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'cached' => false,
                'checking' => false
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function shouldSyncContacts($instanceId) {
        // Check if we should sync contacts (once every hour)
        $instance = $this->instanceModel->findById($instanceId);
        if (!$instance || !$instance['last_sync_at']) {
            return true; // Never synced
        }
        
        $lastSync = strtotime($instance['last_sync_at']);
        $hourAgo = time() - 3600;
        
        return $lastSync < $hourAgo;
    }
    
    /**
     * API endpoint to get real-time connection state
     */
    public function getConnectionState() {
        header('Content-Type: application/json');
        
        try {
            if (!Helpers::isAuthenticated()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found',
                    'state' => 'no_instance'
                ]);
                return;
            }
            
            $evolutionAPI = new EvolutionAPI();
            $connectionState = $evolutionAPI->getConnectionState($instance['instance_name']);
            
            echo json_encode([
                'success' => true,
                'state' => $connectionState,
                'instance_name' => $instance['instance_name'],
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            Logger::getInstance()->error('Connection state API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get connection state',
                'state' => 'unknown'
            ]);
        }
    }
    
    
    /**
     * API endpoint to generate fresh QR code
     */
    public function generateQR() {
        header('Content-Type: application/json');
        
        try {
            if (!Helpers::isAuthenticated()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $instance = $this->instanceModel->findByUserId($userId);
            
            if (!$instance) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No WhatsApp instance found. Please create an instance first.',
                    'state' => 'no_instance'
                ]);
                return;
            }
            
            // Get current connection state first
            $evolutionAPI = new EvolutionAPI();
            $connectionState = $evolutionAPI->getConnectionState($instance['instance_name']);
            
            // If already connected, don't generate QR
            if ($connectionState === 'open') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Instance is already connected',
                    'state' => 'open',
                    'should_redirect' => true,
                    'redirect_url' => '/dashboard'
                ]);
                return;
            }
            
            // Use smart QR generation
            $qrData = $this->getSmartQRCode($instance, $connectionState);
            
            if ($qrData) {
                echo json_encode([
                    'success' => true,
                    'qr_code' => $qrData,
                    'state' => $connectionState,
                    'generated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to generate QR code',
                    'state' => $connectionState
                ]);
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error('Generate QR API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate QR code'
            ]);
        }
    }
}