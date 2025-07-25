<?php
// src/Api/Models/WhatsAppMessage.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';

class WhatsAppMessage {
    private $db;
    
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';
    const TYPE_DOCUMENT = 'document';
    const TYPE_LOCATION = 'location';
    const TYPE_CONTACT = 'contact';
    const TYPE_STICKER = 'sticker';
    
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($instanceId, $messageId, $fromPhone, $toPhone, $messageType = self::TYPE_TEXT, $content = null, $isFromMe = false, $timestamp = null, $metadata = null) {
        $sql = "INSERT INTO whatsapp_messages 
                (instance_id, message_id, from_phone, to_phone, message_type, content, is_from_me, timestamp, status, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                content = VALUES(content),
                status = VALUES(status),
                metadata = VALUES(metadata)";
        
        $params = [
            $instanceId,
            $messageId,
            $fromPhone,
            $toPhone,
            $messageType,
            $content,
            $isFromMe ? 1 : 0,
            $timestamp ?: date('Y-m-d H:i:s'),
            self::STATUS_PENDING,
            $metadata ? json_encode($metadata) : null
        ];
        
        try {
            $this->db->query($sql, $params);
            return $this->findByInstanceAndMessageId($instanceId, $messageId);
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to create message: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM whatsapp_messages WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByInstanceAndMessageId($instanceId, $messageId) {
        $sql = "SELECT * FROM whatsapp_messages WHERE instance_id = ? AND message_id = ?";
        return $this->db->fetch($sql, [$instanceId, $messageId]);
    }
    
    public function findConversation($instanceId, $phoneNumber, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND (from_phone = ? OR to_phone = ?)
                ORDER BY timestamp DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $phoneNumber, $phoneNumber, $limit, $offset]);
    }
    
    public function findRecentConversations($instanceId, $limit = 20) {
        $sql = "SELECT 
                    CASE 
                        WHEN is_from_me = 1 THEN to_phone 
                        ELSE from_phone 
                    END as contact_phone,
                    MAX(timestamp) as last_message_time,
                    COUNT(*) as message_count,
                    (SELECT content FROM whatsapp_messages wm2 
                     WHERE wm2.instance_id = wm.instance_id 
                     AND ((wm2.from_phone = contact_phone AND wm2.is_from_me = 0) 
                          OR (wm2.to_phone = contact_phone AND wm2.is_from_me = 1))
                     ORDER BY wm2.timestamp DESC LIMIT 1) as last_message,
                    (SELECT message_type FROM whatsapp_messages wm3 
                     WHERE wm3.instance_id = wm.instance_id 
                     AND ((wm3.from_phone = contact_phone AND wm3.is_from_me = 0) 
                          OR (wm3.to_phone = contact_phone AND wm3.is_from_me = 1))
                     ORDER BY wm3.timestamp DESC LIMIT 1) as last_message_type,
                    SUM(CASE WHEN status != 'read' AND is_from_me = 0 THEN 1 ELSE 0 END) as unread_count
                FROM whatsapp_messages wm
                WHERE instance_id = ?
                GROUP BY contact_phone
                ORDER BY last_message_time DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $limit]);
    }
    
    public function findUnreadMessages($instanceId, $phoneNumber = null) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND is_from_me = 0 
                AND status != ?";
        
        $params = [$instanceId, self::STATUS_READ];
        
        if ($phoneNumber) {
            $sql .= " AND from_phone = ?";
            $params[] = $phoneNumber;
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findMessagesByType($instanceId, $messageType, $limit = 50) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? AND message_type = ?
                ORDER BY timestamp DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $messageType, $limit]);
    }
    
    public function findMediaMessages($instanceId, $phoneNumber = null, $limit = 50) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND message_type IN (?, ?, ?, ?, ?)";
        
        $params = [$instanceId, self::TYPE_IMAGE, self::TYPE_AUDIO, self::TYPE_VIDEO, self::TYPE_DOCUMENT, self::TYPE_STICKER];
        
        if ($phoneNumber) {
            $sql .= " AND (from_phone = ? OR to_phone = ?)";
            $params[] = $phoneNumber;
            $params[] = $phoneNumber;
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE whatsapp_messages SET status = ? WHERE id = ?";
        
        try {
            $this->db->query($sql, [$status, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update message status: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateMediaInfo($id, $mediaUrl, $mediaFilename, $mediaMimetype, $mediaSize) {
        $sql = "UPDATE whatsapp_messages 
                SET media_url = ?, media_filename = ?, media_mimetype = ?, media_size = ?
                WHERE id = ?";
        
        try {
            $this->db->query($sql, [$mediaUrl, $mediaFilename, $mediaMimetype, $mediaSize, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update message media info: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateQuotedMessage($id, $quotedMessage) {
        $sql = "UPDATE whatsapp_messages SET quoted_message = ? WHERE id = ?";
        
        try {
            $this->db->query($sql, [json_encode($quotedMessage), $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update quoted message: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAsRead($instanceId, $phoneNumber) {
        $sql = "UPDATE whatsapp_messages 
                SET status = ? 
                WHERE instance_id = ? 
                AND from_phone = ? 
                AND is_from_me = 0 
                AND status != ?";
        
        try {
            $this->db->query($sql, [self::STATUS_READ, $instanceId, $phoneNumber, self::STATUS_READ]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to mark messages as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM whatsapp_messages WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to delete message: " . $e->getMessage());
            return false;
        }
    }
    
    public function bulkCreate($instanceId, $messages) {
        $sql = "INSERT INTO whatsapp_messages 
                (instance_id, message_id, from_phone, to_phone, group_id, message_type, content, 
                 media_url, media_filename, media_mimetype, media_size, is_from_me, status, 
                 timestamp, reply_to_message_id, forwarded, quoted_message, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                content = VALUES(content),
                status = VALUES(status),
                metadata = VALUES(metadata)";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $count = 0;
            
            foreach ($messages as $message) {
                $params = [
                    $instanceId,
                    $message['message_id'],
                    $message['from_phone'],
                    $message['to_phone'],
                    $message['group_id'] ?? null,
                    $message['message_type'] ?? self::TYPE_TEXT,
                    $message['content'] ?? null,
                    $message['media_url'] ?? null,
                    $message['media_filename'] ?? null,
                    $message['media_mimetype'] ?? null,
                    $message['media_size'] ?? null,
                    isset($message['is_from_me']) ? ($message['is_from_me'] ? 1 : 0) : 0,
                    $message['status'] ?? self::STATUS_PENDING,
                    $message['timestamp'] ?? date('Y-m-d H:i:s'),
                    $message['reply_to_message_id'] ?? null,
                    isset($message['forwarded']) ? ($message['forwarded'] ? 1 : 0) : 0,
                    isset($message['quoted_message']) ? json_encode($message['quoted_message']) : null,
                    isset($message['metadata']) ? json_encode($message['metadata']) : null
                ];
                
                $stmt->execute($params);
                $count++;
            }
            
            Logger::getInstance()->info("Bulk message operation completed", [
                'instance_id' => $instanceId,
                'messages_processed' => $count
            ]);
            
            return $count;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to bulk create messages: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getMessageStats($instanceId) {
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN is_from_me = 1 THEN 1 ELSE 0 END) as sent_messages,
                    SUM(CASE WHEN is_from_me = 0 THEN 1 ELSE 0 END) as received_messages,
                    SUM(CASE WHEN message_type != 'text' THEN 1 ELSE 0 END) as media_messages,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_messages,
                    SUM(CASE WHEN status != 'read' AND is_from_me = 0 THEN 1 ELSE 0 END) as unread_messages
                FROM whatsapp_messages 
                WHERE instance_id = ?";
        
        return $this->db->fetch($sql, [$instanceId]);
    }
    
    public function getConversationStats($instanceId, $phoneNumber) {
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN is_from_me = 1 THEN 1 ELSE 0 END) as sent_messages,
                    SUM(CASE WHEN is_from_me = 0 THEN 1 ELSE 0 END) as received_messages,
                    SUM(CASE WHEN message_type != 'text' THEN 1 ELSE 0 END) as media_messages,
                    SUM(CASE WHEN status != 'read' AND is_from_me = 0 THEN 1 ELSE 0 END) as unread_messages,
                    MIN(timestamp) as first_message,
                    MAX(timestamp) as last_message
                FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND (from_phone = ? OR to_phone = ?)";
        
        return $this->db->fetch($sql, [$instanceId, $phoneNumber, $phoneNumber]);
    }
    
    public function searchMessages($instanceId, $searchTerm, $phoneNumber = null, $limit = 50) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND content LIKE ?";
        
        $params = [$instanceId, "%{$searchTerm}%"];
        
        if ($phoneNumber) {
            $sql .= " AND (from_phone = ? OR to_phone = ?)";
            $params[] = $phoneNumber;
            $params[] = $phoneNumber;
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getMessagesAfter($instanceId, $phoneNumber, $timestamp, $limit = 50) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND (from_phone = ? OR to_phone = ?)
                AND timestamp > ?
                ORDER BY timestamp ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $phoneNumber, $phoneNumber, $timestamp, $limit]);
    }
    
    public function getMessagesBefore($instanceId, $phoneNumber, $timestamp, $limit = 50) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND (from_phone = ? OR to_phone = ?)
                AND timestamp < ?
                ORDER BY timestamp DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $phoneNumber, $phoneNumber, $timestamp, $limit]);
    }
    
    public function getOldestUnprocessedMessage($instanceId) {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND is_from_me = 0
                AND status = ?
                ORDER BY timestamp ASC 
                LIMIT 1";
        
        return $this->db->fetch($sql, [$instanceId, self::STATUS_PENDING]);
    }
    
    public function cleanupOldMessages($instanceId, $olderThanDays = 30) {
        $sql = "DELETE FROM whatsapp_messages 
                WHERE instance_id = ? 
                AND timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        try {
            $result = $this->db->query($sql, [$instanceId, $olderThanDays]);
            $deletedCount = $result->rowCount();
            
            Logger::getInstance()->info("Cleaned up old messages", [
                'instance_id' => $instanceId,
                'deleted_count' => $deletedCount
            ]);
            
            return $deletedCount;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to cleanup old messages: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getMetadata($id) {
        $message = $this->findById($id);
        
        if (!$message || !$message['metadata']) {
            return null;
        }
        
        return json_decode($message['metadata'], true);
    }
    
    public function getQuotedMessage($id) {
        $message = $this->findById($id);
        
        if (!$message || !$message['quoted_message']) {
            return null;
        }
        
        return json_decode($message['quoted_message'], true);
    }
}