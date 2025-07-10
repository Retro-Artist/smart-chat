<?php
// src/Api/Models/Thread.php - FIXED create method with proper data types

require_once __DIR__ . '/../../Core/Database.php';

class Thread {
    private static function getDB() {
        return Database::getInstance();
    }
    
    /**
     * FIXED: Create a new thread with proper data types
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
        
        try {
            $threadData = [
                'user_id' => (int)$userId,  // Ensure integer
                'title' => $title,
                'messages' => json_encode($messages),
                'message_count' => count($messages),
                'is_whatsapp_thread' => 0,  // Explicitly set as integer 0, not false
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            error_log("Thread::create - Creating thread with data: " . json_encode($threadData));
            
            $threadId = $db->insert('threads', $threadData);
            
            error_log("Thread::create - Thread created with ID: {$threadId}");
            
            return self::findById($threadId);
            
        } catch (Exception $e) {
            error_log("Thread::create - Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * FIXED: Create thread from WhatsApp contact with proper data types
     */
    public static function createFromWhatsAppContact($userId, $contactJid, $contactName, $instanceId, $contactPhone = null) {
        $db = self::getDB();
        
        $title = $contactName ?: $contactPhone ?: 'WhatsApp Contact';
        
        try {
            $threadData = [
                'user_id' => (int)$userId,  // Ensure integer
                'title' => $title,
                'messages' => json_encode([]),
                'message_count' => 0,
                'is_whatsapp_thread' => 1,  // Explicitly set as integer 1, not true
                'whatsapp_contact_jid' => $contactJid,
                'whatsapp_instance_id' => $instanceId ? (int)$instanceId : null,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $threadId = $db->insert('threads', $threadData);
            
            return self::findById($threadId);
            
        } catch (Exception $e) {
            error_log("Thread::createFromWhatsAppContact - Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Find thread by WhatsApp contact JID
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
     * FIXED: Get user threads (simplified query with proper error handling)
     */
    public static function getUserThreads($userId) {
        $db = self::getDB();
        
        try {
            error_log("Thread::getUserThreads - Getting threads for user: {$userId}");
            
            // Start with the simplest possible query
            $threads = $db->fetchAll("
                SELECT * FROM threads 
                WHERE user_id = ? AND (status = 'active' OR status IS NULL)
                ORDER BY 
                    CASE WHEN last_message_at IS NULL THEN created_at ELSE last_message_at END DESC
            ", [(int)$userId]);
            
            error_log("Thread::getUserThreads - Found " . count($threads) . " threads");
            return $threads;
            
        } catch (Exception $e) {
            error_log("Thread::getUserThreads - Error: " . $e->getMessage());
            // Super fallback to absolute simplest query
            try {
                $threads = $db->fetchAll("SELECT * FROM threads WHERE user_id = ? ORDER BY created_at DESC", [(int)$userId]);
                error_log("Thread::getUserThreads - Fallback query returned " . count($threads) . " threads");
                return $threads;
            } catch (Exception $e2) {
                error_log("Thread::getUserThreads - Even fallback failed: " . $e2->getMessage());
                return []; // Return empty array instead of failing
            }
        }
    }
    
    /**
     * Get recent threads
     */
    public static function getRecentThreads($userId, $limit = 5) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT * FROM threads 
            WHERE user_id = ? AND (status = 'active' OR status IS NULL)
            ORDER BY 
                CASE WHEN last_message_at IS NULL THEN created_at ELSE last_message_at END DESC
            LIMIT ?
        ", [(int)$userId, (int)$limit]);
    }
    
    /**
     * Add message with WhatsApp metadata support
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
        
        $db->update('threads', $updateData, 'id = ?', [(int)$threadId]);
        
        // Store WhatsApp metadata if present (but check if table exists first)
        if (isset($metadata['whatsapp_message_id'])) {
            try {
                self::storeWhatsAppMetadata($threadId, $metadata);
            } catch (Exception $e) {
                error_log("Failed to store WhatsApp metadata: " . $e->getMessage());
                // Don't fail the whole operation if WhatsApp metadata fails
            }
        }
        
        return $newMessage;
    }
    
    /**
     * Store WhatsApp message metadata (with error handling)
     */
    private static function storeWhatsAppMetadata($threadId, $metadata) {
        $db = self::getDB();
        
        // Check if table exists first
        $tableExists = $db->fetch("SHOW TABLES LIKE 'whatsapp_message_metadata'");
        if (!$tableExists) {
            error_log("whatsapp_message_metadata table does not exist, skipping metadata storage");
            return;
        }
        
        $db->insert('whatsapp_message_metadata', [
            'thread_id' => (int)$threadId,
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
     * Get messages with WhatsApp metadata (with error handling)
     */
    public static function getMessages($threadId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT messages FROM threads WHERE id = ?", [(int)$threadId]);
        
        if (!$thread || !$thread['messages']) {
            return [];
        }
        
        $messages = json_decode($thread['messages'], true);
        if (!is_array($messages)) {
            return [];
        }
        
        return $messages;
    }
    
    /**
     * Get messages in OpenAI API format
     */
    public static function getOpenAIMessages($threadId) {
        $messages = self::getMessages($threadId);
        $openaiMessages = [];
        
        foreach ($messages as $message) {
            // Only include messages that OpenAI API expects
            if (in_array($message['role'], ['system', 'user', 'assistant'])) {
                $openaiMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
        }
        
        return $openaiMessages;
    }
    
    /**
     * Set/replace all messages in a thread
     */
    public static function setMessages($threadId, $messages) {
        $db = self::getDB();
        
        // Validate messages format
        foreach ($messages as &$message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                throw new Exception('Invalid message format: role and content required');
            }
            
            // Add timestamp if missing
            if (!isset($message['timestamp'])) {
                $message['timestamp'] = date('c');
            }
        }
        
        // Update thread with new messages
        $updateData = [
            'messages' => json_encode($messages),
            'message_count' => count($messages),
            'last_message_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->update('threads', $updateData, 'id = ?', [(int)$threadId]);
    }
    
    /**
     * Trim old messages for performance
     */
    public static function trimMessages($threadId, $keepLastN = 50) {
        $messages = self::getMessages($threadId);
        
        if (count($messages) <= $keepLastN) {
            return false; // No trimming needed
        }
        
        // Keep only the last N messages
        $trimmedMessages = array_slice($messages, -$keepLastN);
        $removedCount = count($messages) - count($trimmedMessages);
        
        // Update thread with trimmed messages
        self::setMessages($threadId, $trimmedMessages);
        
        return $removedCount;
    }
    
    /**
     * Find thread by ID
     */
    public static function findById($threadId) {
        $db = self::getDB();
        return $db->fetch("SELECT * FROM threads WHERE id = ?", [(int)$threadId]);
    }
    
    /**
     * Check if thread belongs to user
     */
    public static function belongsToUser($threadId, $userId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT user_id FROM threads WHERE id = ?", [(int)$threadId]);
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
        ], 'id = ?', [(int)$threadId]);
    }
    
    /**
     * Archive thread
     */
    public static function archive($threadId) {
        $db = self::getDB();
        return $db->update('threads', [
            'status' => 'archived',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [(int)$threadId]);
    }
    
    /**
     * Delete thread completely
     */
    public static function delete($threadId) {
        $db = self::getDB();
        
        // Delete WhatsApp metadata first (if table exists)
        try {
            $tableExists = $db->fetch("SHOW TABLES LIKE 'whatsapp_message_metadata'");
            if ($tableExists) {
                $db->delete('whatsapp_message_metadata', 'thread_id = ?', [(int)$threadId]);
            }
        } catch (Exception $e) {
            error_log("Failed to delete WhatsApp metadata: " . $e->getMessage());
        }
        
        // Delete the thread
        return $db->delete('threads', 'id = ?', [(int)$threadId]);
    }
    
    /**
     * Get thread statistics
     */
    public static function getThreadStats($threadId) {
        $messages = self::getMessages($threadId);
        
        $stats = [
            'total_messages' => count($messages),
            'user_messages' => 0,
            'assistant_messages' => 0,
            'system_messages' => 0,
            'first_message_at' => null,
            'last_message_at' => null,
            'total_characters' => 0,
            'average_message_length' => 0
        ];
        
        if (empty($messages)) {
            return $stats;
        }
        
        $totalLength = 0;
        $timestamps = [];
        
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $length = strlen($content);
            
            // Count by role
            switch ($role) {
                case 'user':
                    $stats['user_messages']++;
                    break;
                case 'assistant':
                    $stats['assistant_messages']++;
                    break;
                case 'system':
                    $stats['system_messages']++;
                    break;
            }
            
            $totalLength += $length;
            
            if (isset($message['timestamp'])) {
                $timestamps[] = $message['timestamp'];
            }
        }
        
        $stats['total_characters'] = $totalLength;
        $stats['average_message_length'] = count($messages) > 0 ? round($totalLength / count($messages), 2) : 0;
        
        if (!empty($timestamps)) {
            sort($timestamps);
            $stats['first_message_at'] = $timestamps[0];
            $stats['last_message_at'] = end($timestamps);
        }
        
        return $stats;
    }
}