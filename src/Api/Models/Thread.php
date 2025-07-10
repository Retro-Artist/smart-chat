<?php
// src/Api/Models/Thread.php - UPDATED for JSON message storage

require_once __DIR__ . '/../../Core/Database.php';

class Thread {
    private static function getDB() {
        return Database::getInstance();
    }
    
    /**
     * Get all threads for a user with cached stats
     */
    public static function getUserThreads($userId) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT 
                id,
                user_id,
                title,
                message_count,
                last_message_at,
                created_at,
                updated_at,
                status
            FROM threads 
            WHERE user_id = ? AND status = 'active'
            ORDER BY last_message_at DESC, updated_at DESC
        ", [$userId]);
    }
    
    /**
     * Find thread by ID
     */
    public static function findById($threadId) {
        $db = self::getDB();
        return $db->fetch("SELECT * FROM threads WHERE id = ?", [$threadId]);
    }
    
    /**
     * Create new thread with optional initial system message
     */
    public static function create($userId, $title = 'New Conversation', $systemMessage = null) {
        $db = self::getDB();
        
        // Prepare initial messages array
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
            'last_message_at' => count($messages) > 0 ? date('Y-m-d H:i:s') : null
        ]);
        
        return self::findById($threadId);
    }
    
    /**
     * Update thread title
     */
    public static function updateTitle($threadId, $title) {
        $db = self::getDB();
        $db->update('threads', 
            ['title' => $title], 
            'id = ?', 
            [$threadId]
        );
        
        return self::findById($threadId);
    }
    
    /**
     * Archive thread (soft delete)
     */
    public static function archive($threadId) {
        $db = self::getDB();
        return $db->update('threads', 
            ['status' => 'archived'], 
            'id = ?', 
            [$threadId]
        );
    }
    
    /**
     * Hard delete thread
     */
    public static function delete($threadId) {
        $db = self::getDB();
        return $db->delete('threads', 'id = ?', [$threadId]);
    }
    
    /**
     * Get messages from thread - returns OpenAI-compatible array
     */
    public static function getMessages($threadId) {
        $db = self::getDB();
        $thread = $db->fetch("SELECT messages FROM threads WHERE id = ?", [$threadId]);
        
        if (!$thread || !$thread['messages']) {
            return [];
        }
        
        $messages = json_decode($thread['messages'], true);
        return is_array($messages) ? $messages : [];
    }
    
    /**
     * Add single message to thread
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
        
        return $newMessage;
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
        
        return $messages;
    }
    
    /**
     * Replace entire conversation (useful for OpenAI assistant threads)
     */
    public static function setMessages($threadId, $messages) {
        $db = self::getDB();
        
        // Ensure all messages have timestamps
        foreach ($messages as &$message) {
            if (!isset($message['timestamp'])) {
                $message['timestamp'] = date('c');
            }
        }
        
        $updateData = [
            'messages' => json_encode($messages),
            'message_count' => count($messages),
            'last_message_at' => count($messages) > 0 ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('threads', $updateData, 'id = ?', [$threadId]);
        
        return $messages;
    }
    
    /**
     * Get OpenAI-compatible messages array (excludes metadata)
     */
    public static function getOpenAIMessages($threadId) {
        $messages = self::getMessages($threadId);
        
        // Clean messages for OpenAI API (keep only role and content)
        $cleanMessages = [];
        foreach ($messages as $message) {
            $cleanMessages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        return $cleanMessages;
    }
    
    /**
     * Check if thread belongs to user
     */
    public static function belongsToUser($threadId, $userId) {
        $db = self::getDB();
        $result = $db->fetch("
            SELECT COUNT(*) as count FROM threads 
            WHERE id = ? AND user_id = ? AND status = 'active'
        ", [$threadId, $userId]);
        
        return $result['count'] > 0;
    }
    
    /**
     * Get recent threads with better performance
     */
    public static function getRecentThreads($userId, $limit = 10) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT 
                id,
                user_id,
                title,
                message_count,
                last_message_at,
                created_at,
                status
            FROM threads 
            WHERE user_id = ? AND status = 'active'
            ORDER BY last_message_at DESC, updated_at DESC
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    /**
     * Search threads by title or message content
     */
    public static function searchThreads($userId, $query) {
        $db = self::getDB();
        $searchTerm = "%{$query}%";
        
        return $db->fetchAll("
            SELECT 
                id,
                user_id,
                title,
                message_count,
                last_message_at,
                created_at
            FROM threads 
            WHERE user_id = ? 
            AND status = 'active'
            AND (
                title LIKE ? 
                OR JSON_SEARCH(messages, 'one', ?, NULL, '$[*].content') IS NOT NULL
            )
            ORDER BY last_message_at DESC
        ", [$userId, $searchTerm, $searchTerm]);
    }
    
    /**
     * Get conversation statistics
     */
    public static function getThreadStats($threadId) {
        $messages = self::getMessages($threadId);
        
        $stats = [
            'total_messages' => count($messages),
            'user_messages' => 0,
            'assistant_messages' => 0,
            'system_messages' => 0,
            'first_message_at' => null,
            'last_message_at' => null
        ];
        
        foreach ($messages as $message) {
            // Count by role
            switch ($message['role']) {
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
            
            // Track timestamps
            if (isset($message['timestamp'])) {
                if (!$stats['first_message_at']) {
                    $stats['first_message_at'] = $message['timestamp'];
                }
                $stats['last_message_at'] = $message['timestamp'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean old messages (keep last N messages for performance)
     */
    public static function trimMessages($threadId, $keepLastN = 50) {
        $messages = self::getMessages($threadId);
        
        if (count($messages) <= $keepLastN) {
            return false; // No trimming needed
        }
        
        // Keep system messages + last N messages
        $systemMessages = array_filter($messages, fn($msg) => $msg['role'] === 'system');
        $otherMessages = array_filter($messages, fn($msg) => $msg['role'] !== 'system');
        
        // Keep only the last N non-system messages
        $trimmedOthers = array_slice($otherMessages, -$keepLastN);
        
        // Combine system messages + trimmed others
        $trimmedMessages = array_merge(array_values($systemMessages), $trimmedOthers);
        
        // Update thread
        self::setMessages($threadId, $trimmedMessages);
        
        return count($messages) - count($trimmedMessages); // Return number of messages removed
    }
    
    /**
     * Find thread by WhatsApp contact JID and instance
     */
    public static function findByWhatsAppContact($jid, $instanceId) {
        $db = self::getDB();
        return $db->fetch("
            SELECT * FROM threads 
            WHERE whatsapp_jid = ? AND whatsapp_instance_id = ? AND status = 'active'
        ", [$jid, $instanceId]);
    }
    
    /**
     * Create thread from WhatsApp contact
     */
    public static function createFromWhatsAppContact($userId, $jid, $contactName, $contactPhone, $instanceId) {
        $db = self::getDB();
        
        $threadId = $db->insert('threads', [
            'user_id' => $userId,
            'title' => $contactName ?: 'WhatsApp Contact',
            'whatsapp_jid' => $jid,
            'whatsapp_instance_id' => $instanceId,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'messages' => json_encode([]),
            'message_count' => 0,
            'last_message_at' => null,
            'source' => 'whatsapp'
        ]);
        
        return self::findById($threadId);
    }
    
    /**
     * Update WhatsApp contact information
     */
    public static function updateWhatsAppContact($threadId, $contactName, $contactPhone) {
        $db = self::getDB();
        return $db->update('threads', [
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'title' => $contactName ?: 'WhatsApp Contact',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$threadId]);
    }
}