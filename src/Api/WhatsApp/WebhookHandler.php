<?php
// src/Api/WhatsApp/WebhookHandler.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/../../Core/MessageQueue.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';
require_once __DIR__ . '/InstanceManager.php';

class WebhookHandler {
    private $queue;
    private $instanceModel;
    private $instanceManager;
    private $startTime;
    
    const MAX_PROCESSING_TIME_MS = 80;
    
    public function __construct() {
        $this->startTime = microtime(true);
        Logger::getInstance()->info("WebhookHandler constructor started");
        
        try {
            $this->queue = new MessageQueue();
            Logger::getInstance()->info("MessageQueue initialized");
            
            $this->instanceModel = new WhatsAppInstance();
            Logger::getInstance()->info("WhatsAppInstance model initialized");
            
            $this->instanceManager = new InstanceManager();
            Logger::getInstance()->info("InstanceManager initialized");
            
            Logger::getInstance()->info("WebhookHandler constructor completed");
        } catch (Exception $e) {
            Logger::getInstance()->error("Error in WebhookHandler constructor", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
    
    public function handleWebhook($payload) {
        try {
            Logger::getInstance()->info("Starting webhook validation", ['payload_keys' => array_keys($payload ?? [])]);
            
            if (!$this->validateWebhook($payload)) {
                Logger::getInstance()->warning("Webhook validation failed", ['payload' => $payload]);
                $this->respondError('Invalid webhook payload', 400);
                return;
            }
            
            Logger::getInstance()->info("Webhook validation passed, parsing event");
            $event = $this->parseEvent($payload);
            if (!$event) {
                Logger::getInstance()->warning("Event parsing failed", ['payload' => $payload]);
                $this->respondError('Unable to parse event', 400);
                return;
            }
            
            Logger::getInstance()->info("Event parsed successfully, processing", ['event_type' => $event['event_type']]);
            
            // Test if we can even reach processEvent
            Logger::getInstance()->info("About to call processEvent");
            $this->processEvent($event);
            Logger::getInstance()->info("processEvent completed");
            
            Logger::getInstance()->info("Event processed successfully");
            $this->respondSuccess();
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Webhook processing error: " . $e->getMessage(), [
                'payload' => $payload,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->respondError('Internal error', 500);
        }
    }
    
    private function validateWebhook($payload) {
        if (empty($payload)) {
            return false;
        }
        
        if (WEBHOOK_SIGNATURE_SECRET && isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])) {
            $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
            $expectedSignature = hash_hmac('sha256', json_encode($payload), WEBHOOK_SIGNATURE_SECRET);
            
            if (!hash_equals($signature, $expectedSignature)) {
                Logger::getInstance()->warning("Webhook signature validation failed");
                return false;
            }
        }
        
        return isset($payload['event']) && isset($payload['instance']);
    }
    
    private function parseEvent($payload) {
        $instanceName = $payload['instance'] ?? null;
        if (!$instanceName) {
            return null;
        }
        
        $instance = $this->instanceModel->findByInstanceName($instanceName);
        if (!$instance) {
            Logger::getInstance()->warning("Webhook received for unknown instance: {$instanceName}");
            return null;
        }
        
        return [
            'instance_id' => $instance['id'],
            'instance_name' => $instanceName,
            'event_type' => $payload['event'],
            'data' => $payload['data'] ?? $payload,
            'timestamp' => time(),
            'raw_payload' => $payload
        ];
    }
    
    private function processEvent($event) {
        $eventType = $event['event_type'];
        $instanceId = $event['instance_id'];
        
        Logger::getInstance()->info("Processing event", ['event_type' => $eventType, 'instance_id' => $instanceId]);
        
        switch ($eventType) {
            case 'MESSAGES_UPSERT':
            case 'messages.upsert':
                $this->handleMessagesUpsert($event);
                break;
                
            case 'MESSAGES_UPDATE':
            case 'messages.update':
                $this->handleMessagesUpdate($event);
                break;
                
            case 'CONTACTS_UPSERT':
            case 'contacts.upsert':
                $this->handleContactsUpsert($event);
                break;
                
            case 'CONNECTION_UPDATE':
            case 'connection.update':
                $this->handleConnectionUpdate($event);
                break;
                
            case 'PRESENCE_UPDATE':
            case 'presence.update':
                $this->handlePresenceUpdate($event);
                break;
                
            case 'qrcode.updated':
            case 'QRCODE_UPDATED':
                Logger::getInstance()->info("QR code updated event received", ['instance_id' => $instanceId]);
                $this->queueGenericEvent($event);
                break;
                
            default:
                Logger::getInstance()->info("Processing generic event", ['event_type' => $eventType]);
                $this->queueGenericEvent($event);
                break;
        }
        
        $this->checkProcessingTime();
    }
    
    private function handleMessagesUpsert($event) {
        $messages = $event['data']['messages'] ?? [];
        
        foreach ($messages as $messageData) {
            if ($this->isIncomingMessage($messageData)) {
                $this->queueIncomingMessage($event['instance_id'], $messageData);
            } else {
                $this->queueOutgoingMessage($event['instance_id'], $messageData);
            }
        }
    }
    
    private function handleMessagesUpdate($event) {
        $messages = $event['data']['messages'] ?? [];
        
        foreach ($messages as $messageData) {
            $this->queueMessageStatusUpdate($event['instance_id'], $messageData);
        }
    }
    
    private function handleContactsUpsert($event) {
        $contacts = $event['data']['contacts'] ?? [];
        
        if (!empty($contacts)) {
            $this->queue->push('process_contacts', [
                'instance_id' => $event['instance_id'],
                'contacts' => $contacts,
                'event_timestamp' => $event['timestamp']
            ], MessageQueue::PRIORITY_NORMAL, $event['instance_id']);
        }
    }
    
    private function handleConnectionUpdate($event) {
        try {
            Logger::getInstance()->info("Starting connection update handling", ['event_keys' => array_keys($event)]);
            
            $connectionData = $event['data'];
            $state = $connectionData['state'] ?? 'unknown';
            
            Logger::getInstance()->info("Extracted connection data", ['state' => $state, 'data_keys' => array_keys($connectionData)]);
            
            // Normalize connection state to match our system states
            $normalizedState = $this->normalizeConnectionState($state);
            
            Logger::getInstance()->info("State normalized", ['raw_state' => $state, 'normalized_state' => $normalizedState]);
            
            // Log connection state change
            Logger::getInstance()->info("Connection state update received", [
                'instance_id' => $event['instance_id'],
                'instance_name' => $event['instance_name'],
                'raw_state' => $state,
                'normalized_state' => $normalizedState,
                'connection_data' => $connectionData
            ]);
            
            Logger::getInstance()->info("About to update database connection state");
            // Update database immediately for critical states
            $this->updateInstanceConnectionState($event['instance_id'], $normalizedState, $connectionData);
            
            Logger::getInstance()->info("About to queue connection update");
            // Queue for additional processing
            $this->queue->pushHighPriority('connection_update', [
                'instance_id' => $event['instance_id'],
                'instance_name' => $event['instance_name'],
                'state' => $normalizedState,
                'raw_state' => $state,
                'connection_data' => $connectionData,
                'event_timestamp' => $event['timestamp']
            ], $event['instance_id']);
            
            Logger::getInstance()->info("About to handle state-specific actions", ['normalized_state' => $normalizedState]);
            // Handle state-specific actions
            switch ($normalizedState) {
                case 'open':
                    $this->instanceManager->onInstanceConnected($event['instance_id'], $connectionData);
                    $this->clearQRCache($event['instance_name']);
                    break;
                    
                case 'connecting':
                    Logger::getInstance()->info("Instance connecting", ['instance_name' => $event['instance_name']]);
                    break;
                    
                case 'close':
                case 'disconnected':
                case 'failed':
                    $this->instanceManager->onInstanceDisconnected($event['instance_id']);
                    $this->clearQRCache($event['instance_name']);
                    break;
            }
            
            Logger::getInstance()->info("Connection update handling completed successfully");
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Error in handleConnectionUpdate", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'event' => $event
            ]);
            throw $e; // Re-throw to be caught by main handler
        }
    }
    
    private function handlePresenceUpdate($event) {
        $presenceData = $event['data'];
        
        $this->queue->push('presence_update', [
            'instance_id' => $event['instance_id'],
            'from' => $presenceData['id'] ?? null,
            'presence' => $presenceData['presences'] ?? [],
            'event_timestamp' => $event['timestamp']
        ], MessageQueue::PRIORITY_LOW, $event['instance_id']);
    }
    
    private function queueIncomingMessage($instanceId, $messageData) {
        $messageId = $messageData['key']['id'] ?? uniqid('msg_');
        $fromPhone = $this->extractPhoneNumber($messageData['key']['remoteJid'] ?? '');
        $content = $this->extractMessageContent($messageData);
        
        if ($this->shouldProcessWithAI($messageData)) {
            $this->queue->pushHighPriority('process_incoming_message', [
                'instance_id' => $instanceId,
                'message_id' => $messageId,
                'from_phone' => $fromPhone,
                'content' => $content,
                'message_type' => $this->getMessageType($messageData),
                'timestamp' => $messageData['messageTimestamp'] ?? time(),
                'raw_message' => $messageData,
                'needs_ai_response' => true
            ], $instanceId);
        } else {
            $this->queue->push('store_message', [
                'instance_id' => $instanceId,
                'message_id' => $messageId,
                'from_phone' => $fromPhone,
                'content' => $content,
                'message_type' => $this->getMessageType($messageData),
                'timestamp' => $messageData['messageTimestamp'] ?? time(),
                'raw_message' => $messageData,
                'is_from_me' => false
            ], MessageQueue::PRIORITY_NORMAL, $instanceId);
        }
    }
    
    private function queueOutgoingMessage($instanceId, $messageData) {
        $messageId = $messageData['key']['id'] ?? uniqid('msg_');
        $toPhone = $this->extractPhoneNumber($messageData['key']['remoteJid'] ?? '');
        $content = $this->extractMessageContent($messageData);
        
        $this->queue->push('store_message', [
            'instance_id' => $instanceId,
            'message_id' => $messageId,
            'to_phone' => $toPhone,
            'content' => $content,
            'message_type' => $this->getMessageType($messageData),
            'timestamp' => $messageData['messageTimestamp'] ?? time(),
            'raw_message' => $messageData,
            'is_from_me' => true
        ], MessageQueue::PRIORITY_NORMAL, $instanceId);
    }
    
    private function queueMessageStatusUpdate($instanceId, $messageData) {
        $messageId = $messageData['key']['id'] ?? null;
        $status = $this->mapMessageStatus($messageData['update'] ?? []);
        
        if ($messageId && $status) {
            $this->queue->push('update_message_status', [
                'instance_id' => $instanceId,
                'message_id' => $messageId,
                'status' => $status,
                'update_data' => $messageData['update'] ?? [],
                'timestamp' => time()
            ], MessageQueue::PRIORITY_NORMAL, $instanceId);
        }
    }
    
    private function queueGenericEvent($event) {
        $this->queue->push('process_webhook_event', [
            'event_type' => $event['event_type'],
            'instance_id' => $event['instance_id'],
            'data' => $event['data'],
            'timestamp' => $event['timestamp']
        ], MessageQueue::PRIORITY_LOW, $event['instance_id']);
    }
    
    private function isIncomingMessage($messageData) {
        return !($messageData['key']['fromMe'] ?? false);
    }
    
    private function shouldProcessWithAI($messageData) {
        if ($messageData['key']['fromMe'] ?? false) {
            return false; // Don't process our own messages
        }
        
        if (isset($messageData['key']['remoteJid']) && 
            strpos($messageData['key']['remoteJid'], '@g.us') !== false) {
            return false; // Don't process group messages for now
        }
        
        $messageType = $this->getMessageType($messageData);
        if (!in_array($messageType, ['text', 'audio'])) {
            return false; // Only process text and audio messages with AI
        }
        
        return true;
    }
    
    private function extractPhoneNumber($jid) {
        return preg_replace('/[^0-9]/', '', explode('@', $jid)[0]);
    }
    
    private function extractMessageContent($messageData) {
        $message = $messageData['message'] ?? [];
        
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }
        
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }
        
        if (isset($message['imageMessage']['caption'])) {
            return $message['imageMessage']['caption'];
        }
        
        if (isset($message['videoMessage']['caption'])) {
            return $message['videoMessage']['caption'];
        }
        
        if (isset($message['documentMessage']['caption'])) {
            return $message['documentMessage']['caption'];
        }
        
        return null;
    }
    
    private function getMessageType($messageData) {
        $message = $messageData['message'] ?? [];
        
        if (isset($message['conversation']) || isset($message['extendedTextMessage'])) {
            return 'text';
        }
        
        if (isset($message['imageMessage'])) {
            return 'image';
        }
        
        if (isset($message['audioMessage'])) {
            return 'audio';
        }
        
        if (isset($message['videoMessage'])) {
            return 'video';
        }
        
        if (isset($message['documentMessage'])) {
            return 'document';
        }
        
        if (isset($message['locationMessage'])) {
            return 'location';
        }
        
        if (isset($message['contactMessage'])) {
            return 'contact';
        }
        
        if (isset($message['stickerMessage'])) {
            return 'sticker';
        }
        
        return 'text';
    }
    
    private function mapMessageStatus($updateData) {
        if (isset($updateData['status'])) {
            switch ($updateData['status']) {
                case 0:
                    return 'pending';
                case 1:
                    return 'sent';
                case 2:
                    return 'delivered';
                case 3:
                    return 'read';
                default:
                    return 'failed';
            }
        }
        
        if (isset($updateData['receiptType'])) {
            switch ($updateData['receiptType']) {
                case 'delivery':
                    return 'delivered';
                case 'read':
                    return 'read';
                case 'played':
                    return 'read';
                default:
                    return 'sent';
            }
        }
        
        return null;
    }
    
    /**
     * Normalize Evolution API connection states to our system states
     */
    private function normalizeConnectionState($rawState) {
        switch (strtolower($rawState)) {
            case 'open':
                return 'open';
            case 'connecting':
            case 'qr':
                return 'connecting';
            case 'close':
            case 'closed':
                return 'close';
            case 'disconnected':
                return 'disconnected';
            case 'failed':
            case 'timeout':
                return 'failed';
            case 'not_found':
                return 'not_found';
            default:
                return 'unknown';
        }
    }
    
    /**
     * Update instance connection state in database immediately
     */
    private function updateInstanceConnectionState($instanceId, $state, $connectionData) {
        try {
            // Map our states to database status constants
            $dbStatus = $this->mapStateToDBStatus($state);
            
            // Update instance status
            $this->instanceModel->updateStatus($instanceId, $dbStatus);
            
            // Extract phone number if available and update
            if (isset($connectionData['me']['id']) && $state === 'open') {
                $phoneNumber = $this->extractPhoneNumber($connectionData['me']['id']);
                if ($phoneNumber) {
                    $this->instanceModel->updatePhoneNumber($instanceId, $phoneNumber);
                }
            }
            
            // Cache the connection state for quick access
            require_once __DIR__ . '/../../Core/Redis.php';
            $redis = Redis::getInstance();
            $instance = $this->instanceModel->findById($instanceId);
            if ($instance) {
                $cacheKey = "connection:{$instance['instance_name']}";
                $redis->set($cacheKey, $state, 300); // Cache for 5 minutes
                
                // Update session authentication state for the user
                require_once __DIR__ . '/../../Core/Security.php';
                Security::updateWhatsAppAuthState($instance['user_id'], $state);
                
                Logger::getInstance()->info("Updated user session auth state", [
                    'user_id' => $instance['user_id'],
                    'state' => $state
                ]);
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update instance connection state", [
                'instance_id' => $instanceId,
                'state' => $state,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Map normalized states to database status constants
     */
    private function mapStateToDBStatus($state) {
        switch ($state) {
            case 'open':
                return WhatsAppInstance::STATUS_CONNECTED; // 'connected'
            case 'connecting':
                return WhatsAppInstance::STATUS_CONNECTING; // 'connecting'
            case 'close':
                return WhatsAppInstance::STATUS_DISCONNECTED; // 'disconnected'
            case 'disconnected':
                return WhatsAppInstance::STATUS_DISCONNECTED; // 'disconnected'
            case 'failed':
                return WhatsAppInstance::STATUS_FAILED; // 'failed'
            case 'not_found':
                return WhatsAppInstance::STATUS_DISCONNECTED; // 'disconnected'
            default:
                return WhatsAppInstance::STATUS_DISCONNECTED; // 'disconnected'
        }
    }
    
    /**
     * Clear QR code cache when connection state changes
     */
    private function clearQRCache($instanceName) {
        try {
            require_once __DIR__ . '/../../Core/Redis.php';
            $redis = Redis::getInstance();
            $qrCacheKey = "qr:{$instanceName}";
            $redis->delete($qrCacheKey);
            
            Logger::getInstance()->info("Cleared QR cache for state change", [
                'instance_name' => $instanceName
            ]);
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to clear QR cache", [
                'instance_name' => $instanceName,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function checkProcessingTime() {
        $elapsedMs = (microtime(true) - $this->startTime) * 1000;
        
        if ($elapsedMs > self::MAX_PROCESSING_TIME_MS) {
            Logger::getInstance()->warning("Webhook processing exceeded time limit", [
                'elapsed_ms' => $elapsedMs,
                'limit_ms' => self::MAX_PROCESSING_TIME_MS
            ]);
        }
    }
    
    private function respondSuccess() {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'processed_at' => date('Y-m-d H:i:s'),
            'processing_time_ms' => round((microtime(true) - $this->startTime) * 1000, 2)
        ]);
    }
    
    private function respondError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'processed_at' => date('Y-m-d H:i:s'),
            'processing_time_ms' => round((microtime(true) - $this->startTime) * 1000, 2)
        ]);
    }
    
    public function getProcessingStats() {
        return [
            'start_time' => $this->startTime,
            'current_time' => microtime(true),
            'elapsed_ms' => round((microtime(true) - $this->startTime) * 1000, 2),
            'max_processing_time_ms' => self::MAX_PROCESSING_TIME_MS
        ];
    }
    
    public static function handleRequest() {
        $handler = new self();
        
        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $handler->respondError('Invalid JSON payload', 400);
            return;
        }
        
        $handler->handleWebhook($payload);
    }
}