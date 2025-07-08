<?php
// src/Api/EvolutionAPI/WebhookHandler.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/MessageImporter.php';
require_once __DIR__ . '/../../Web/Models/WhatsAppInstance.php';
require_once __DIR__ . '/../Models/Agent.php';

class WebhookHandler
{
    private $db;
    private $logger;
    private $messageImporter;
    private $whatsappInstance;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->messageImporter = new MessageImporter();
        $this->whatsappInstance = new WhatsAppInstance();
    }

    /**
     * Main webhook handler - routes events to appropriate handlers
     */
    public function handle($requestData)
    {
        try {
            // Extract instance and event information
            $instanceName = $this->extractInstanceName($requestData);
            $event = $requestData['event'] ?? '';
            $data = $requestData['data'] ?? $requestData;

            $this->logger->info("Webhook received", [
                'instance' => $instanceName,
                'event' => $event,
                'data_keys' => array_keys($data)
            ]);

            // Route to appropriate handler based on event type
            switch ($event) {
                case 'qrcode.updated':
                case 'QRCODE_UPDATED':
                    return $this->handleQRCodeUpdate($instanceName, $data);

                case 'connection.update':
                case 'CONNECTION_UPDATE':
                    return $this->handleConnectionUpdate($instanceName, $data);

                case 'messages.upsert':
                case 'MESSAGES_UPSERT':
                    return $this->handleMessageUpsert($instanceName, $data);

                case 'messages.update':
                case 'MESSAGES_UPDATE':
                    return $this->handleMessageUpdate($instanceName, $data);

                case 'contacts.set':
                case 'CONTACTS_SET':
                case 'contacts.upsert':
                case 'CONTACTS_UPSERT':
                    return $this->handleContactsUpdate($instanceName, $data);

                default:
                    $this->logger->info("Unhandled webhook event: {$event}");
                    return $this->successResponse("Event ignored: {$event}");
            }

        } catch (Exception $e) {
            $this->logger->error("Webhook handling failed", [
                'error' => $e->getMessage(),
                'request_data' => $requestData
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle QR code updates
     */
    private function handleQRCodeUpdate($instanceName, $data)
    {
        try {
            $qrCode = $data['qrcode'] ?? $data['qr'] ?? null;
            
            if ($qrCode) {
                // Update QR code in database
                $this->whatsappInstance->updateByName($instanceName, [
                    'qr_code' => $qrCode,
                    'status' => 'connecting'
                ]);

                $this->logger->info("QR code updated", [
                    'instance' => $instanceName
                ]);
            }

            return $this->successResponse("QR code updated");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle QR code update", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle connection status updates
     */
    private function handleConnectionUpdate($instanceName, $data)
    {
        try {
            $state = $data['state'] ?? $data['instance']['state'] ?? 'unknown';
            
            // Map Evolution API states to our status
            $statusMap = [
                'open' => 'connected',
                'close' => 'disconnected',
                'connecting' => 'connecting',
                'qr' => 'connecting'
            ];
            
            $status = $statusMap[$state] ?? 'disconnected';
            
            // Prepare update data
            $updateData = ['status' => $status];
            
            // If connected, extract profile information
            if ($state === 'open' && isset($data['instance'])) {
                $instance = $data['instance'];
                $updateData['phone_number'] = $instance['wuid'] ?? null;
                $updateData['profile_name'] = $instance['profileName'] ?? null;
                $updateData['profile_picture'] = $instance['profilePictureUrl'] ?? null;
                $updateData['last_seen'] = date('Y-m-d H:i:s');
                $updateData['qr_code'] = null; // Clear QR code when connected
            }

            // Update instance status
            $this->whatsappInstance->updateByName($instanceName, $updateData);

            $this->logger->info("Connection status updated", [
                'instance' => $instanceName,
                'state' => $state,
                'status' => $status
            ]);

            return $this->successResponse("Connection status updated: {$status}");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle connection update", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle new/updated messages
     */
    private function handleMessageUpsert($instanceName, $data)
    {
        try {
            $messages = $data['messages'] ?? [$data];
            $processedCount = 0;
            $aiResponsesTriggered = 0;

            foreach ($messages as $messageData) {
                // Import message to thread system
                $result = $this->messageImporter->importWhatsAppMessage($instanceName, $messageData);
                
                if ($result['success']) {
                    $processedCount++;
                    
                    // Trigger AI response if needed
                    if ($result['should_ai_respond']) {
                        $aiResult = $this->triggerAIResponse($result['thread'], $result['message']);
                        if ($aiResult['success']) {
                            $aiResponsesTriggered++;
                        }
                    }
                }
            }

            $this->logger->info("Messages processed", [
                'instance' => $instanceName,
                'processed' => $processedCount,
                'ai_responses' => $aiResponsesTriggered,
                'total' => count($messages)
            ]);

            return $this->successResponse("Processed {$processedCount} messages, triggered {$aiResponsesTriggered} AI responses");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle message upsert", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle message status updates (read, delivered, etc.)
     */
    private function handleMessageUpdate($instanceName, $data)
    {
        try {
            $messages = $data['messages'] ?? [$data];
            $updatedCount = 0;

            foreach ($messages as $messageData) {
                $messageId = $messageData['key']['id'] ?? null;
                $status = $this->extractMessageStatus($messageData);
                
                if ($messageId && $status) {
                    $result = $this->messageImporter->updateMessageStatus($messageId, $status);
                    if ($result['success']) {
                        $updatedCount++;
                    }
                }
            }

            $this->logger->info("Message statuses updated", [
                'instance' => $instanceName,
                'updated' => $updatedCount,
                'total' => count($messages)
            ]);

            return $this->successResponse("Updated {$updatedCount} message statuses");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle message update", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle contact updates
     */
    private function handleContactsUpdate($instanceName, $data)
    {
        try {
            $contacts = $data['contacts'] ?? $data;
            $processedCount = 0;

            foreach ($contacts as $contactData) {
                $result = $this->messageImporter->importContact($instanceName, $contactData);
                if ($result['success']) {
                    $processedCount++;
                }
            }

            $this->logger->info("Contacts processed", [
                'instance' => $instanceName,
                'processed' => $processedCount,
                'total' => count($contacts)
            ]);

            return $this->successResponse("Processed {$processedCount} contacts");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle contacts update", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Trigger AI response for a message
     */
    private function triggerAIResponse($thread, $message)
    {
        try {
            // Get the appropriate agent for this thread
            $agent = $this->getAgentForThread($thread);
            
            if (!$agent) {
                // Use default agent or create one
                $agents = Agent::getUserAgents($thread['user_id']);
                if (empty($agents)) {
                    // No agents available
                    return ['success' => false, 'error' => 'No AI agents available'];
                }
                $agent = $agents[0]; // Use first available agent
            }

            // Execute agent with the message
            $response = $agent->execute($message['content'], $thread['id']);

            $this->logger->info("AI response generated", [
                'thread_id' => $thread['id'],
                'agent_id' => $agent->getId(),
                'response_length' => strlen($response)
            ]);

            return ['success' => true, 'response' => $response];

        } catch (Exception $e) {
            $this->logger->error("Failed to trigger AI response", [
                'thread_id' => $thread['id'],
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get appropriate agent for thread
     */
    private function getAgentForThread($thread)
    {
        // Check if thread has assigned agent
        if ($thread['agent_id']) {
            return Agent::findById($thread['agent_id']);
        }

        // Check for routing rules
        if ($thread['whatsapp_instance_id']) {
            $routing = $this->db->fetch(
                "SELECT agent_id FROM conversation_routing 
                 WHERE instance_id = ? AND (contact_jid = ? OR contact_jid IS NULL) 
                 ORDER BY contact_jid DESC LIMIT 1",
                [$thread['whatsapp_instance_id'], $thread['whatsapp_contact_jid']]
            );

            if ($routing && $routing['agent_id']) {
                return Agent::findById($routing['agent_id']);
            }
        }

        return null;
    }

    /**
     * Extract instance name from request data
     */
    private function extractInstanceName($requestData)
    {
        // Try various possible fields where instance name might be
        return $requestData['instance'] ?? 
               $requestData['instanceName'] ?? 
               $requestData['data']['instance'] ?? 
               $requestData['data']['instanceName'] ?? 
               'unknown';
    }

    /**
     * Extract message status from update data
     */
    private function extractMessageStatus($messageData)
    {
        $update = $messageData['update'] ?? [];
        
        if (isset($update['status'])) {
            $statusMap = [
                0 => 'sent',
                1 => 'delivered', 
                2 => 'read',
                3 => 'failed'
            ];
            
            return $statusMap[$update['status']] ?? 'sent';
        }

        return null;
    }

    /**
     * Return success response
     */
    private function successResponse($message)
    {
        return [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
    }

    /**
     * Return error response
     */
    private function errorResponse($message)
    {
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ];
    }
}