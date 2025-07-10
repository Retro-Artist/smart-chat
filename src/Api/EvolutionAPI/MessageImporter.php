<?php
// src/Api/EvolutionAPI/MessageImporter.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';
require_once __DIR__ . '/../Models/Thread.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';

class MessageImporter
{
    private $db;
    private $logger;
    private $whatsappInstance;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->whatsappInstance = new WhatsAppInstance();
    }

    /**
     * Import WhatsApp message into thread system
     */
    public function importWhatsAppMessage($instanceName, $messageData)
    {
        try {
            // Get instance info
            $instance = $this->whatsappInstance->findByName($instanceName);
            if (!$instance) {
                throw new Exception("WhatsApp instance not found: {$instanceName}");
            }

            // Extract message information
            $messageInfo = $this->extractMessageInfo($messageData);
            
            // Skip if message is from us (to avoid loops)
            if ($messageInfo['from_me']) {
                $this->logger->info("Skipping outgoing message: {$messageInfo['message_id']}");
                return null;
            }

            // Check if message already exists
            if ($this->messageExists($messageInfo['message_id'])) {
                $this->logger->info("Message already exists: {$messageInfo['message_id']}");
                return null;
            }

            // Find or create thread for contact
            $thread = $this->getOrCreateThread($instance, $messageInfo);
            
            // Add message to thread with WhatsApp metadata
            $message = Thread::addMessage($thread['id'], 'user', $messageInfo['content'], [
                'source' => 'whatsapp',
                'whatsapp_message_id' => $messageInfo['message_id'],
                'jid_from' => $messageInfo['jid_from'],
                'jid_to' => $messageInfo['jid_to'],
                'message_type' => $messageInfo['message_type'],
                'media_url' => $messageInfo['media_url'],
                'media_caption' => $messageInfo['media_caption'],
                'quoted_message_id' => $messageInfo['quoted_message_id'],
                'wa_timestamp' => $messageInfo['timestamp'],
                'status' => 'received'
            ]);

            $this->logger->info("Imported WhatsApp message", [
                'instance' => $instanceName,
                'thread_id' => $thread['id'],
                'message_id' => $messageInfo['message_id'],
                'contact' => $messageInfo['contact_name'] ?: $messageInfo['jid_from']
            ]);

            return [
                'success' => true,
                'thread' => $thread,
                'message' => $message,
                'should_ai_respond' => $this->shouldAIRespond($instance, $thread, $messageInfo)
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to import WhatsApp message", [
                'instance' => $instanceName,
                'error' => $e->getMessage(),
                'message_data' => $messageData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract structured information from Evolution API message data
     */
    private function extractMessageInfo($messageData)
    {
        // Handle different Evolution API message structures
        $message = $messageData['data'] ?? $messageData;
        
        // Get the actual message content
        $messageContent = '';
        $messageType = 'text';
        $mediaUrl = null;
        $mediaCaption = null;

        if (isset($message['message'])) {
            $msg = $message['message'];
            
            // Text message
            if (isset($msg['conversation'])) {
                $messageContent = $msg['conversation'];
            }
            // Extended text message
            elseif (isset($msg['extendedTextMessage']['text'])) {
                $messageContent = $msg['extendedTextMessage']['text'];
            }
            // Image message
            elseif (isset($msg['imageMessage'])) {
                $messageType = 'image';
                $messageContent = $msg['imageMessage']['caption'] ?? '[Image]';
                $mediaCaption = $msg['imageMessage']['caption'] ?? null;
                $mediaUrl = $msg['imageMessage']['url'] ?? null;
            }
            // Video message
            elseif (isset($msg['videoMessage'])) {
                $messageType = 'video';
                $messageContent = $msg['videoMessage']['caption'] ?? '[Video]';
                $mediaCaption = $msg['videoMessage']['caption'] ?? null;
                $mediaUrl = $msg['videoMessage']['url'] ?? null;
            }
            // Audio message
            elseif (isset($msg['audioMessage'])) {
                $messageType = 'audio';
                $messageContent = '[Audio Message]';
                $mediaUrl = $msg['audioMessage']['url'] ?? null;
            }
            // Document message
            elseif (isset($msg['documentMessage'])) {
                $messageType = 'document';
                $fileName = $msg['documentMessage']['fileName'] ?? 'Document';
                $messageContent = "[Document: {$fileName}]";
                $mediaUrl = $msg['documentMessage']['url'] ?? null;
            }
            // Sticker
            elseif (isset($msg['stickerMessage'])) {
                $messageType = 'sticker';
                $messageContent = '[Sticker]';
                $mediaUrl = $msg['stickerMessage']['url'] ?? null;
            }
        }

        // Extract contact information
        $jidFrom = $message['key']['remoteJid'] ?? '';
        $jidTo = $message['key']['participant'] ?? $jidFrom;
        $fromMe = $message['key']['fromMe'] ?? false;
        
        // Get contact name from pushName or phone number
        $contactName = $message['pushName'] ?? null;
        $contactPhone = $this->extractPhoneFromJid($jidFrom);
        
        // Get message timestamp
        $timestamp = $message['messageTimestamp'] ?? time();
        
        // Get quoted message if any
        $quotedMessageId = null;
        if (isset($message['message']['extendedTextMessage']['contextInfo']['quotedMessage'])) {
            $quotedMessageId = $message['message']['extendedTextMessage']['contextInfo']['stanzaId'] ?? null;
        }

        return [
            'message_id' => $message['key']['id'] ?? uniqid(),
            'jid_from' => $jidFrom,
            'jid_to' => $jidTo,
            'from_me' => $fromMe,
            'content' => $messageContent,
            'message_type' => $messageType,
            'media_url' => $mediaUrl,
            'media_caption' => $mediaCaption,
            'quoted_message_id' => $quotedMessageId,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'timestamp' => $timestamp,
            'is_group' => strpos($jidFrom, '@g.us') !== false
        ];
    }

    /**
     * Extract phone number from WhatsApp JID
     */
    private function extractPhoneFromJid($jid)
    {
        if (strpos($jid, '@s.whatsapp.net') !== false) {
            return str_replace('@s.whatsapp.net', '', $jid);
        }
        if (strpos($jid, '@g.us') !== false) {
            return null; // Group chat
        }
        return $jid;
    }

    /**
     * Check if message already exists
     */
    private function messageExists($messageId)
    {
        return $this->db->fetch(
            "SELECT id FROM whatsapp_message_metadata WHERE whatsapp_message_id = ?",
            [$messageId]
        ) !== null;
    }

    /**
     * Get or create thread for WhatsApp contact
     */
    private function getOrCreateThread($instance, $messageInfo)
    {
        // Try to find existing thread
        $thread = Thread::findByWhatsAppContact($messageInfo['jid_from'], $instance['id']);
        
        if (!$thread) {
            // Create new thread for contact
            $contactName = $messageInfo['contact_name'] ?: $messageInfo['contact_phone'] ?: 'WhatsApp Contact';
            
            $thread = Thread::createFromWhatsAppContact(
                $instance['user_id'],
                $messageInfo['jid_from'],
                $contactName,
                $instance['id'],
                $messageInfo['contact_phone']
            );
            
            $this->logger->info("Created new WhatsApp thread", [
                'thread_id' => $thread['id'],
                'contact_jid' => $messageInfo['jid_from'],
                'contact_name' => $contactName,
                'instance_id' => $instance['id']
            ]);
        } else {
            // Update contact info if we have better data
            if ($messageInfo['contact_name'] && $messageInfo['contact_name'] !== $thread['contact_name']) {
                Thread::updateWhatsAppContact(
                    $thread['id'],
                    $messageInfo['contact_name'],
                    $messageInfo['contact_phone']
                );
                
                // Refresh thread data
                $thread = Thread::findById($thread['id']);
            }
        }

        return $thread;
    }

    /**
     * Determine if AI should respond to this message
     */
    private function shouldAIRespond($instance, $thread, $messageInfo)
    {
        // Get instance settings
        $settings = json_decode($instance['settings'], true) ?: [];
        
        // Check if auto-respond is enabled
        if (!($settings['auto_respond'] ?? true)) {
            return false;
        }

        // Don't respond to group messages unless explicitly enabled
        if ($messageInfo['is_group'] && !($settings['respond_to_groups'] ?? false)) {
            return false;
        }

        // Check business hours if configured
        if (isset($settings['business_hours']) && !$this->isBusinessHours($settings['business_hours'])) {
            return false;
        }

        // Check if human handoff is active
        if ($settings['human_handoff_enabled'] ?? false) {
            // Check if this contact is in human mode
            $humanMode = $this->db->fetch(
                "SELECT is_human_mode FROM conversation_routing 
                 WHERE instance_id = ? AND contact_jid = ?",
                [$instance['id'], $messageInfo['jid_from']]
            );
            
            if ($humanMode && $humanMode['is_human_mode']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current time is within business hours
     */
    private function isBusinessHours($businessHours)
    {
        if (!$businessHours || !isset($businessHours['enabled']) || !$businessHours['enabled']) {
            return true; // Always available if not configured
        }

        $now = new DateTime();
        $currentDay = strtolower($now->format('l')); // monday, tuesday, etc.
        $currentTime = $now->format('H:i');

        $daySchedule = $businessHours['schedule'][$currentDay] ?? null;
        
        if (!$daySchedule || !$daySchedule['enabled']) {
            return false; // Not available on this day
        }

        return $currentTime >= $daySchedule['start'] && $currentTime <= $daySchedule['end'];
    }

    /**
     * Import contact information from WhatsApp
     */
    public function importContact($instanceName, $contactData)
    {
        try {
            $instance = $this->whatsappInstance->findByName($instanceName);
            if (!$instance) {
                return ['success' => false, 'error' => 'Instance not found'];
            }

            // Extract contact info
            $jid = $contactData['id'] ?? '';
            $name = $contactData['name'] ?? $contactData['pushName'] ?? '';
            $phone = $this->extractPhoneFromJid($jid);

            // Find existing thread for this contact
            $thread = Thread::findByWhatsAppContact($jid, $instance['id']);
            
            if ($thread && $name && $name !== $thread['contact_name']) {
                // Update contact information
                Thread::updateWhatsAppContact($thread['id'], $name, $phone);
                
                $this->logger->info("Updated contact information", [
                    'thread_id' => $thread['id'],
                    'jid' => $jid,
                    'old_name' => $thread['contact_name'],
                    'new_name' => $name
                ]);
            }

            return ['success' => true];

        } catch (Exception $e) {
            $this->logger->error("Failed to import contact", [
                'instance' => $instanceName,
                'error' => $e->getMessage(),
                'contact_data' => $contactData
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update message status (read, delivered, etc.)
     */
    public function updateMessageStatus($messageId, $status)
    {
        try {
            $this->db->update(
                'whatsapp_message_metadata',
                ['status' => $status],
                'whatsapp_message_id = ?',
                [$messageId]
            );

            return ['success' => true];

        } catch (Exception $e) {
            $this->logger->error("Failed to update message status", [
                'message_id' => $messageId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}