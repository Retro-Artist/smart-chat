<?php
// src/Api/Models/Thread.php - Complete enhanced version

require_once __DIR__ . '/../../Core/Database.php';

class Thread {
    private static function getDB() {
        return Database::getInstance();
    }
    
    /**
     * Create a new thread (existing method - unchanged)
     */
    public static function create($userId, $title, $systemMessage = null) {
        $db = self::getDB();
        
        $messages = [];
        if ($systemMessage) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage,
                'timestamp' => date('c')
            ];
        }
        
        $threadId = $db->insert('threads', [
            'user_id' => $userId,
            'title' => $title,
            'messages' => json_encode($messages),
            'message_count' => count($messages),
            'is_whatsapp_thread' => false, // Default to web thread
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return self::findById($threadId);
    }
    
    /**
     * NEW: Create thread from WhatsApp contact
     */
    public static function createFromWhatsAppContact($userId, $contactJid, $contactName, $instanceId, $contactPhone = null) {
        $db = self::getDB();
        
        // Generate meaningful title from contact info
        $title = $contactName ?: $contactPhone ?: 'WhatsApp Contact';
        
        $threadId = $db->insert('threads', [
            'user_id' => $userId,
            'title' => $title,
            'messages' => json_encode([]),
            'message_count' => 0,
            'is_whatsapp_thread' => true,
            'whatsapp_contact_jid' => $contactJid,
            'whatsapp_instance_id' => $instanceId,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return self::findById($threadId);
    }
    
    /**
     * NEW: Find thread by WhatsApp contact JID
     */
    public static function findByWhatsAppContact($contactJid, $instanceId = null) {
        $db = self::getDB();
        
        $sql = "SELECT * FROM threads WHERE whatsapp_contact_jid = ?";
        $params = [$contactJid];
        
        if ($instanceId) {
            $sql .= " AND whatsapp_instance_id = ?";
            $params[] = $instanceId;
        }
        
        return $db->fetch($sql, $params);
    }
    
    /**
     * Enhanced: Get user threads (includes WhatsApp)
     */
    public static function getUserThreads($userId) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT t.*, 
                   wi.phone_number as instance_phone,
                   wi.profile_name as instance_profile,
                   wi.status as instance_status
            FROM threads t 
            LEFT JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.user_id = ? AND t.status = 'active'
            ORDER BY 
                CASE WHEN t.last_message_at IS NULL THEN t.created_at ELSE t.last_message_at END DESC
        ", [$userId]);
    }
    
    /**
     * Enhanced: Add message with WhatsApp metadata support
     */
    public static function addMessage($threadId, $role, $content, $metadata = []) {
        $db = self::getDB();
        
        // Get current messages
        $messages = self::getMessages($threadId);
        
        // Create new message
        $newMessage = array_merge([
            'role' => $role,
            'content' => $content,
            'timestamp' => date('c')
        ], $metadata);
        
        // Add to messages array
        $messages[] = $newMessage;
        
        // Update thread with new messages
        $updateData = [
            'messages' => json_encode($messages),
            'message_count' => count($messages),
            'last_message_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('threads', $updateData, 'id = ?', [$threadId]);
        
        // Store WhatsApp metadata if present
        if (isset($metadata['whatsapp_message_id'])) {
            self::storeWhatsAppMetadata($threadId, $metadata);
        }
        
        return $newMessage;
    }
    
    /**
     * NEW: Store WhatsApp message metadata
     */
    private static function storeWhatsAppMetadata($threadId, $metadata) {
        $db = self::getDB();
        
        $db->insert('whatsapp_message_metadata', [
            'thread_id' => $threadId,
            'whatsapp_message_id' => $metadata['whatsapp_message_id'],
            'jid_from' => $metadata['jid_from'] ?? '',
            'jid_to' => $metadata['jid_to'] ?? '',
            'message_type' => $metadata['message_type'] ?? 'text',
            'media_url' => $metadata['media_url'] ?? null,
            'media_caption' => $metadata['media_caption'] ?? null,
            'quoted_message_id' => $metadata['quoted_message_id'] ?? null,
            'timestamp' => isset($metadata['wa_timestamp']) ? date('Y-m-d H:i:s', $metadata['wa_timestamp']) : null,
            'status' => $metadata['status'] ?? 'sent'
        ]);
    }
    
    /**
     * Enhanced: Get messages with WhatsApp metadata
     */
    public static function getMessages($threadId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT messages FROM threads WHERE id = ?", [$threadId]);
        
        if (!$thread || !$thread['messages']) {
            return [];
        }
        
        $messages = json_decode($thread['messages'], true);
        if (!is_array($messages)) {
            return [];
        }
        
        // Enrich messages with WhatsApp metadata
        $waMetadata = $db->fetchAll(
            "SELECT * FROM whatsapp_message_metadata WHERE thread_id = ? ORDER BY created_at ASC",
            [$threadId]
        );
        
        // Create metadata lookup by message ID
        $metadataLookup = [];
        foreach ($waMetadata as $meta) {
            $metadataLookup[$meta['whatsapp_message_id']] = $meta;
        }
        
        // Add metadata to messages where applicable
        foreach ($messages as &$message) {
            if (isset($message['whatsapp_message_id'])) {
                $message['whatsapp_metadata'] = $metadataLookup[$message['whatsapp_message_id']] ?? null;
            }
        }
        
        return $messages;
    }
    
    /**
     * Find thread by ID
     */
    public static function findById($threadId) {
        $db = self::getDB();
        return $db->fetch("SELECT * FROM threads WHERE id = ?", [$threadId]);
    }
    
    /**
     * Check if thread belongs to user
     */
    public static function belongsToUser($threadId, $userId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT user_id FROM threads WHERE id = ?", [$threadId]);
        return $thread && $thread['user_id'] == $userId;
    }
    
    /**
     * Update thread title
     */
    public static function updateTitle($threadId, $title) {
        $db = self::getDB();
        return $db->update('threads', [
            'title' => $title, 
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$threadId]);
    }
    
    /**
     * Archive thread
     */
    public static function archive($threadId) {
        $db = self::getDB();
        return $db->update('threads', [
            'status' => 'archived',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$threadId]);
    }
    
    /**
     * Delete thread
     */
    public static function delete($threadId) {
        $db = self::getDB();
        return $db->delete('threads', 'id = ?', [$threadId]);
    }
    
    /**
     * NEW: Get recent threads for dashboard
     */
    public static function getRecentThreads($userId, $limit = 5) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT t.*, 
                   wi.phone_number as instance_phone,
                   wi.status as instance_status,
                   CASE 
                       WHEN t.is_whatsapp_thread THEN CONCAT('📱 ', COALESCE(t.contact_name, t.contact_phone, 'WhatsApp Contact'))
                       ELSE t.title 
                   END as display_title
            FROM threads t 
            LEFT JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.user_id = ? AND t.status = 'active'
            ORDER BY 
                CASE WHEN t.last_message_at IS NULL THEN t.created_at ELSE t.last_message_at END DESC
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    /**
     * NEW: Get thread statistics including WhatsApp
     */
    public static function getThreadStats($threadId) {
        $db = self::getDB();
        
        $stats = $db->fetch("
            SELECT 
                t.message_count,
                t.is_whatsapp_thread,
                t.contact_name,
                t.contact_phone,
                t.whatsapp_contact_jid,
                wi.phone_number as instance_phone,
                wi.status as instance_status
            FROM threads t
            LEFT JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.id = ?
        ", [$threadId]);
        
        if (!$stats) {
            return null;
        }
        
        // Add WhatsApp-specific stats if applicable
        if ($stats['is_whatsapp_thread']) {
            $waStats = $db->fetch("
                SELECT 
                    COUNT(*) as wa_message_count,
                    COUNT(CASE WHEN message_type != 'text' THEN 1 END) as media_count,
                    MAX(timestamp) as last_wa_message
                FROM whatsapp_message_metadata 
                WHERE thread_id = ?
            ", [$threadId]);
            
            $stats = array_merge($stats, $waStats ?: []);
        }
        
        return $stats;
    }
    
    /**
     * NEW: Update contact information for WhatsApp threads
     */
    public static function updateWhatsAppContact($threadId, $contactName, $contactPhone = null, $contactAvatar = null) {
        $db = self::getDB();
        
        $updateData = [
            'contact_name' => $contactName,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($contactPhone) {
            $updateData['contact_phone'] = $contactPhone;
        }
        
        if ($contactAvatar) {
            $updateData['contact_avatar'] = $contactAvatar;
        }
        
        // Also update the title to match contact name
        if ($contactName) {
            $updateData['title'] = $contactName;
        }
        
        return $db->update('threads', $updateData, 'id = ?', [$threadId]);
    }
    
    /**
     * NEW: Check if thread is from WhatsApp
     */
    public static function isWhatsAppThread($threadId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT is_whatsapp_thread FROM threads WHERE id = ?", [$threadId]);
        return $thread && $thread['is_whatsapp_thread'];
    }
    
    /**
     * Add multiple messages at once (for agent conversations)
     */
    public static function addMessages($threadId, $newMessages) {
        $db = self::getDB();
        
        // Get current messages
        $messages = self::getMessages($threadId);
        
        // Add timestamp to new messages if not present
        foreach ($newMessages as &$message) {
            if (!isset($message['timestamp'])) {
                $message['timestamp'] = date('c');
            }
        }
        
        // Merge arrays
        $messages = array_merge($messages, $newMessages);
        
        // Update thread
        $updateData = [
            'messages' => json_encode($messages),
            'message_count' => count($messages),
            'last_message_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('threads', $updateData, 'id = ?', [$threadId]);
        
        return $newMessages;
    }
    
    /**
     * Get all threads for a user with optional filtering
     */
    public static function getAllUserThreads($userId, $filters = []) {
        $db = self::getDB();
        
        $sql = "
            SELECT t.*, 
                   wi.phone_number as instance_phone,
                   wi.profile_name as instance_profile,
                   wi.status as instance_status
            FROM threads t 
            LEFT JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.user_id = ?
        ";
        $params = [$userId];
        
        // Add filters
        if (isset($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        } else {
            $sql .= " AND t.status = 'active'";
        }
        
        if (isset($filters['is_whatsapp'])) {
            $sql .= " AND t.is_whatsapp_thread = ?";
            $params[] = $filters['is_whatsapp'] ? 1 : 0;
        }
        
        $sql .= " ORDER BY 
            CASE WHEN t.last_message_at IS NULL THEN t.created_at ELSE t.last_message_at END DESC
        ";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Update thread's last message timestamp
     */
    public static function updateLastMessageTime($threadId, $timestamp = null) {
        $db = self::getDB();
        
        if (!$timestamp) {
            $timestamp = date('Y-m-d H:i:s');
        }
        
        return $db->update('threads', [
            'last_message_at' => $timestamp,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$threadId]);
    }
    
    /**
     * Get message count for thread
     */
    public static function getMessageCount($threadId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT message_count FROM threads WHERE id = ?", [$threadId]);
        return $thread ? (int)$thread['message_count'] : 0;
    }
    
    /**
     * Search threads by title or content
     */
    public static function searchThreads($userId, $query, $limit = 20) {
        $db = self::getDB();
        
        $searchTerm = '%' . $query . '%';
        
        return $db->fetchAll("
            SELECT t.*, 
                   wi.phone_number as instance_phone,
                   wi.status as instance_status
            FROM threads t 
            LEFT JOIN whatsapp_instances wi ON t.whatsapp_instance_id = wi.id
            WHERE t.user_id = ? 
            AND t.status = 'active'
            AND (
                t.title LIKE ? 
                OR t.contact_name LIKE ?
                OR t.messages LIKE ?
            )
            ORDER BY t.last_message_at DESC
            LIMIT ?
        ", [$userId, $searchTerm, $searchTerm, $searchTerm, $limit]);
    }
}