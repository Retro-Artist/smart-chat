<?php
// src/Api/WhatsApp/WebhookHandler.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/MessageImporter.php';
require_once __DIR__ . '/../Models/Instance.php';
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
        $this->whatsappInstance = new Instance();
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
     * Handle QR code updates - FIXED VERSION
     */
    private function handleQRCodeUpdate($instanceName, $data)
    {
        try {
            // EvolutionAPI sends QR code in different possible fields
            $qrCode = $data['qrcode'] ?? $data['qr'] ?? $data['code'] ?? null;
            
            if ($qrCode) {
                // Update QR code in database (store raw QR code)
                $this->whatsappInstance->updateByName($instanceName, [
                    'qr_code' => $qrCode,
                    'status' => 'connecting'
                ]);

                $this->logger->info("QR code updated", [
                    'instance' => $instanceName,
                    'qr_length' => strlen($qrCode)
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

            foreach ($messages as $messageData) {
                $result = $this->messageImporter->importWhatsAppMessage($instanceName, $messageData);
                if ($result['success']) {
                    $processedCount++;
                    
                    // Trigger AI response if needed
                    if ($result['should_ai_respond'] ?? false) {
                        $this->triggerAIResponse($instanceName, $result['message'] ?? []);
                    }
                }
            }

            $this->logger->info("Messages processed", [
                'instance' => $instanceName,
                'processed' => $processedCount,
                'total' => count($messages)
            ]);

            return $this->successResponse("Processed {$processedCount} messages");

        } catch (Exception $e) {
            $this->logger->error("Failed to handle message upsert", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle message updates (delivery, read receipts, etc.)
     */
    private function handleMessageUpdate($instanceName, $data)
    {
        try {
            $messages = $data['messages'] ?? [$data];
            $updatedCount = 0;

            foreach ($messages as $messageUpdate) {
                $messageId = $messageUpdate['key']['id'] ?? null;
                if ($messageId) {
                    $result = $this->messageImporter->updateMessageStatus($messageId, 'delivered');
                    if ($result['success']) {
                        $updatedCount++;
                    }
                }
            }

            $this->logger->info("Message statuses updated", [
                'instance' => $instanceName,
                'updated' => $updatedCount
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
     * Handle contacts updates
     */
    private function handleContactsUpdate($instanceName, $data)
    {
        try {
            $contacts = $data['contacts'] ?? $data;
            $processedCount = 0;

            if (is_array($contacts)) {
                foreach ($contacts as $contact) {
                    // Process contact data - implement as needed
                    $processedCount++;
                }
            }

            $this->logger->info("Contacts updated", [
                'instance' => $instanceName,
                'processed' => $processedCount
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
     * Extract instance name from webhook data
     */
    private function extractInstanceName($data)
    {
        // Try different possible locations for instance name
        return $data['instance'] ?? 
               $data['instanceName'] ?? 
               $data['data']['instance'] ?? 
               $data['data']['instanceName'] ?? 
               'unknown';
    }

    /**
     * Trigger AI response for incoming messages
     */
    private function triggerAIResponse($instanceName, $messageData)
    {
        try {
            // Skip if message is from the bot itself
            if ($messageData['from_me'] ?? false) {
                return;
            }

            // Skip if auto-respond is disabled
            $instance = $this->whatsappInstance->findByName($instanceName);
            if (!$instance) {
                return;
            }

            $settings = json_decode($instance['settings'] ?? '{}', true);
            if (!($settings['auto_respond'] ?? true)) {
                return;
            }

            // TODO: Implement AI response logic
            // This would integrate with your OpenAI/Agent system
            $this->logger->info("AI response triggered", [
                'instance' => $instanceName,
                'message_id' => $messageData['id'] ?? 'unknown'
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to trigger AI response", [
                'instance' => $instanceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Return success response
     */
    private function successResponse($message)
    {
        return [
            'success' => true,
            'message' => $message
        ];
    }

    /**
     * Return error response
     */
    private function errorResponse($error)
    {
        return [
            'success' => false,
            'error' => $error
        ];
    }
}